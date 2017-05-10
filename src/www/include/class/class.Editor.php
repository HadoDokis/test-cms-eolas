<?php

/**
 * Classe de gestion du contenu issue d'un éditeur riche
 * @package CMS
 */
class Editor
{

    /**
     * Ecrit les appels pour intialiser l'éditeur (balises <script> notamment)
     *
     * @return void
     */
    public static function header($forBO = true)
    {
        $lang = $forBO ? substr(Utilisateur::getConnected()->getField('LNG_CODE'), 0, 2) : substr(CMS::getCurrentSite()->getField('LNG_CODE'), 0, 2);
        require_once PHYSICAL_PATH . 'tinymce/tiny_mce_gzip.php';
        TinyMCE_Compressor::renderTag(array(
            'url' => SERVER_ROOT . 'tinymce/tiny_mce_gzip.php',
            'plugins' => 'cms,contextmenu,fullscreen,paste,searchreplace,table',
            'themes' => 'advanced',
            'languages' => $lang,
            'source' => true
        ));
        echo '<script src="' . SERVER_ROOT . 'tinymce/editor_init.js.php?language=' . $lang . '&amp;editor=' . CMS::getCurrentSite()->getField('GBS_CODE') . '"></script>';
    }

    /**
     * Cette fonction ne doit etre appelée que depuis le BO (fichier submit)
     * Avant de faire un Editor::updateContent() , il est necessaire de faire un Link::delete() qui va supprimer les liaisons
     *
     * @param string $contenu
     *            texte à parser. Attention celui-ci peut eventuellement etre modifié (passage par référence)
     * @param string $tableName
     *            nom de la table à mettre à jour
     * @param string $fieldName
     *            nom du champ à mettre à jour
     * @param string $idName
     *            nom du champ 'identifiant' l'enregistrement à mettre à jour
     * @param int|varchar $id
     *            identifiant de l'enregistrement concerné
     * @return void
     */
    public static function updateContent($contenu, $tableName, $fieldName, $idName, $id)
    {
        self::parse($contenu, CMS::getCurrentSite()->getHomePage(), $tableName, $id);
        $dbh = DB::getInstance();
        $sql = 'update ' . $tableName . ' set ' . $fieldName . '=' . $dbh->quote($contenu) . ' where ' . $idName . '=' . $dbh->quote($id);
        $dbh->exec($sql);
    }

    /**
     * Cette fonction doit etre appelée lors de l'affichage de contenu (DE, TPL notamment)
     *
     * @param string $contenu
     *            texte à traiter
     * @param Page $oPage
     * @return string
     */
    public static function displayContent($contenu, Page $oPage)
    {
        return self::parse($contenu, $oPage);
    }

    /**
     * Parse le contenu et le retourne
     *
     * @param string $contenu
     *            texte à parser. Attention celui-ci peut eventuellement etre modifié (passage par référence)
     * @param Page $oPage
     *            page où à lieu l'affichage
     * @param string $LIA_CODE
     *            code de la liaison demandant le parsing (si vide seulement en mode display : pas de maj des liaisons)
     * @param int $ID_LIAISON
     *            identifiant de la liaison demandant le parsing (si vide seulement en mode display : pas de maj des liaisons)
     * @param int $ID_REVISION
     *            Identifiant de la révision
     * @param bool $control
     *            si faux alors certains controles ne sont pas appliqués (cas pour ASPMail)
     * @return string
     */
    public static function parse(& $contenu, Page $oPage, $LIA_CODE = false, $ID_LIAISON = false, $ID_REVISION = null, $control = true)
    {
        require_once CLASS_DIR . 'class.db_webotheque.php';
        require_once CLASS_DIR . 'class.db_module.php';
        require_once CLASS_DIR . 'class.Link.php';

        if ($oPage->getField('SIT_CODE') == CMS::getCurrentSite()->getID()) {
            $oSite = CMS::getCurrentSite();
        } else {
            $oSite = new Site($oPage->getField('SIT_CODE'));
        }
        $enabled = array(
            'MOD_WEBOTHEQUE_DOCUMENT' => $oSite->hasModule(new Module('MOD_WEBOTHEQUE_DOCUMENT')),
            'MOD_WEBOTHEQUE_FLASH' => $oSite->hasModule(new Module('MOD_WEBOTHEQUE_FLASH')),
            'MOD_WEBOTHEQUE_IMAGE' => $oSite->hasModule(new Module('MOD_WEBOTHEQUE_IMAGE')),
            'MOD_WEBOTHEQUE_LIENEXTERNE' => $oSite->hasModule(new Module('MOD_WEBOTHEQUE_LIENEXTERNE')),
            'MOD_WEBOTHEQUE_MUSIC' => $oSite->hasModule(new Module('MOD_WEBOTHEQUE_MUSIC')),
            'MOD_WEBOTHEQUE_VIDEO' => $oSite->hasModule(new Module('MOD_WEBOTHEQUE_VIDEO')),
            'MOD_WEBOTHEQUE_VIDEOEXTERNE' => $oSite->hasModule(new Module('MOD_WEBOTHEQUE_VIDEOEXTERNE')),
            'MOD_CORE' => $oSite->hasModule(new Module('MOD_CORE')),
            'MOD_EAM' => $oSite->hasModule(new Module('MOD_EAM'))
        );

        if (! $LIA_CODE) {
            // en mode display, inutile de chercher à purger le contenu source
            $contenu = trim($contenu);
            // <p>&nbsp;</p> seul
            if ($contenu == '<p>&nbsp;</p>') {
                $contenu = '';
            }
            // supprime <em></em> ou <strong></strong> ou <p></p> (mais pas <p>&nbsp;</p> !
            $contenu = str_replace('<em></em>', '', $contenu);
            $contenu = str_replace('<em> </em>', '', $contenu);
            $contenu = str_replace('<strong></strong>', '', $contenu);
            $contenu = str_replace('<strong> </strong>', '', $contenu);
            $contenu = str_replace('<p></p>', '', $contenu);
            $contenu = str_replace('<p> </p>', '', $contenu);
            // supprime commentaires <!-- -->
            $contenu = preg_replace('/<!--.*-->/Usu', '', $contenu);
            // supprime classe vide
            $contenu = preg_replace('/<([^>]*)class=""([^>]*)>/u', '<\\1\\2>', $contenu);
            // supprime classe "none"
            $contenu = str_replace(' align="none"', ' ', $contenu);
            // force le href à #
            $contenu = preg_replace('/href="([^"]*)"/u', 'href="#"', $contenu);
            // remplace les '/>'
            $contenu = preg_replace('/[ ]*\/>/u', '>', $contenu);
            // supprime espace en double
            $contenu = preg_replace('/\s+/u', ' ', $contenu);
        }
        $contenuParse = $contenu;

        // remplace les 'align' par des classes (img et td)
        $contenuParse = preg_replace('/ align="([^"]*)"/u', ' class="align\\1"', $contenuParse);

        // récupération des liens
        preg_match_all('/(<a [^>]+>)(.*)<\/a>/Uis', $contenuParse, $aLien);
        for ($i = count($aLien[1]) - 1; $i >= 0; $i --) {
            preg_match('/rel="([^"]*)"/i', $aLien[1][$i], $rel);
            if (! $control && is_array($rel) && $rel[1] == 'noParse') {
                $lienFinal = $aLien[0][$i]; // le lien d'origine
            } else {
                $purgeContenu = false;
                $lienFinal = $aLien[2][$i]; // au départ, uniquement le texte

                // recuperation des infos des liens
                preg_match('/id="([^"]*)"/i', $aLien[1][$i], $idLien);
                preg_match('/ancre="([^"]*)"/i', $aLien[1][$i], $ancre);
                preg_match('/typelien="([^"]*)"/i', $aLien[1][$i], $typeLien);
                preg_match('/title="([^"]*)"/i', $aLien[1][$i], $title);
                preg_match('/class="([^"]*)"/i', $aLien[1][$i], $class);
                preg_match('/style="([^"]*)"/i', $aLien[1][$i], $style);

                $strTitle = ($title[1] != '') ? ' ' . $title[0] : '';
                $aClass = ($class[1] != '') ? explode(' ', $class[1]) : array();
                $strRel = ($rel[1] != '') ? ' ' . $rel[0] : '';
                $strStyle = (! $control && $style[1] != '') ? ' ' . $style[0] : ''; // on garde le style seulement si pas de controle

                switch (strtolower($typeLien[1])) {
                    case 'lieninterne':
                        if (is_numeric($idLien[1]) && $lienFinal != '') {
                            $oPageTemp = new Page($idLien[1], $oPage->getMode());
                            if ($oPageTemp->exist() && ($oPageTemp->getField('SIT_CODE') == CMS::getCurrentSite()->getID() || $oPageTemp->checkShareAuthorized(false))) {
                                require_once CLASS_DIR . 'class.db_paragraphe.php';
                                $oParagraphe = new Paragraphe($ancre[1], $oPage->getMode());
                                if (! $oParagraphe->exist()) {
                                    $ancre[1] = null;
                                }
                                $lienFinal = '<a ' . $oPageTemp->getAnchor(array(), (($ancre[1] != null) ? 'par' . $ancre[1] : ''), $aClass) . $strTitle . $strStyle . '>' . $lienFinal . '</a>';
                                if ($LIA_CODE != '' && $ID_LIAISON != '') {
                                    Link::insertPage($LIA_CODE, $ID_LIAISON, $oPageTemp->getID(), $ancre[1], $ID_REVISION);
                                }
                            } elseif ($oPage->getMode() == 'OFF_') {
                                $contenu = str_replace($aLien[0][$i], $lienFinal, $contenu);
                            }
                        }
                        break;
                    case 'liendocument':
                        if ($enabled['MOD_WEBOTHEQUE_DOCUMENT']) {
                            if (is_numeric($idLien[1]) && $lienFinal != '') {
                                $oWeboTemp = new Webo_DOCUMENT($idLien[1]);
                                if ($oWeboTemp->checkAuthorized(false) || $oWeboTemp->checkShareAuthorized(false)) {
                                    $lienFinal = '<a ' . $oWeboTemp->getAnchor($aClass, $ancre[1]) . $strTitle . $strStyle . $strRel . '>' . $lienFinal . '</a>';
                                    if ($LIA_CODE != '' && $ID_LIAISON != '') {
                                        Link::insertWebotheque($LIA_CODE, $ID_LIAISON, $oWeboTemp->getID(), $ID_REVISION);
                                    }
                                } elseif ($oPage->getMode() == 'OFF_') {
                                    $contenu = str_replace($aLien[0][$i], $lienFinal, $contenu);
                                }
                            }
                        } else {
                            $purgeContenu = true;
                        }
                        break;
                    case 'lienimage':
                        if ($enabled['MOD_WEBOTHEQUE_IMAGE']) {
                            if (is_numeric($idLien[1]) && $lienFinal != '') {
                                $oWeboTemp = new Webo_IMAGE($idLien[1]);
                                if ($oWeboTemp->checkAuthorized(false) || $oWeboTemp->checkShareAuthorized(false)) {
                                    $lienFinal = '<a href="' . $oWeboTemp->getSRC() . '" class="' . implode(' ', array_merge($aClass, array(
                                        'image',
                                        'lightbox'
                                    ))) . '" ' . $strTitle . $strStyle . $strRel . '>' . $lienFinal . '</a>';
                                    if ($LIA_CODE != '' && $ID_LIAISON != '') {
                                        Link::insertWebotheque($LIA_CODE, $ID_LIAISON, $oWeboTemp->getID(), $ID_REVISION);
                                    }
                                } elseif ($oPage->getMode() == 'OFF_') {
                                    $contenu = str_replace($aLien[0][$i], $lienFinal, $contenu);
                                }
                            }
                        } else {
                            $purgeContenu = true;
                        }
                        break;
                    case 'lienexterne':
                        if ($enabled['MOD_WEBOTHEQUE_LIENEXTERNE']) {
                            if (is_numeric($idLien[1]) && $lienFinal != '') {
                                $oWeboTemp = new Webo_LIENEXTERNE($idLien[1]);
                                if ($oWeboTemp->checkAuthorized(false) || $oWeboTemp->checkShareAuthorized(false)) {
                                    $lienFinal = '<a ' . $oWeboTemp->getAnchor($aClass) . $strTitle . $strStyle . $strRel . '>' . $lienFinal . '</a>';
                                    if ($LIA_CODE != '' && $ID_LIAISON != '') {
                                        Link::insertWebotheque($LIA_CODE, $ID_LIAISON, $oWeboTemp->getID(), $ID_REVISION);
                                    }
                                } elseif ($oPage->getMode() == 'OFF_') {
                                    $contenu = str_replace($aLien[0][$i], $lienFinal, $contenu);
                                }
                            }
                        } else {
                            $purgeContenu = true;
                        }
                        break;
                    case 'lientemplate':
                        if ($enabled['MOD_CORE']) {
                            if ($idLien[1] != '' && $lienFinal != '') {
                                $aParam = array(
                                    'TPL_CODE' => $idLien[1]
                                );
                                if (is_array($ancre) && $ancre[1] != '') {
                                    $aParam['PAR_TPL_IDENTIFIANT'] = $ancre[1];
                                }
                                if (! $oPageTPL = CMS::getCurrentSite()->getSpecialePageByTemplate($idLien[1], $oPage->getMode())) {
                                    $oPageTPL = $oPage;
                                }
                                $tplAnchor = $oPageTPL->getAnchor($aParam, '', $aClass);
                                if (http_response_code() != 404) {
                                    $lienFinal = '<a ' . $tplAnchor . $strTitle . $strStyle . $strRel . '>' . $lienFinal . '</a>';
                                }
                                if (http_response_code() != 200) {
                                    http_response_code(200);
                                }
                            }
                        } else {
                            $purgeContenu = true;
                        }
                        break;
                    case 'eamlien':
                        if ($enabled['MOD_EAM']) {
                            preg_match('/type="([^"]*)"/i', $aLien[1][$i], $aTypeEam);
                            $lienFinal = '<a href="' . $aTypeEam[1] . '"' . $strTitle . $strStyle . $strRel . '>' . $lienFinal . '</a>';
                        } else {
                            $purgeContenu = true;
                        }
                        break;
                    default:
                        $purgeContenu = true;
                }
                if ($purgeContenu) {
                    $contenu = str_replace($aLien[0][$i], $lienFinal, $contenu);
                }
            }
            $contenuParse = str_replace($aLien[0][$i], $lienFinal, $contenuParse);
        }

        // récupération des images
        if ($enabled['MOD_WEBOTHEQUE_IMAGE']) {
            preg_match_all('/<img [^>]*>/is', $contenuParse, $aIMG);
            foreach ($aIMG[0] as $IMG_origine) {
                preg_match('/rel="([^"]*)"/i', $IMG_origine, $rel);
                if (! $control && is_array($rel) && $rel[1] == 'noParse') {
                    $IMG_finale = $IMG_origine; // l'image d'origine
                } else {
                    $IMG_finale = ''; // au départ vide, on recréé tout
                    preg_match('/idtf="([0-9]*)"/i', $IMG_origine, $idtf);
                    $oWeboTemp = new Webo_IMAGE($idtf[1]);
                    if ($oWeboTemp->checkAuthorized(false) || $oWeboTemp->checkShareAuthorized(false)) {
                        if ($LIA_CODE != '' && $ID_LIAISON != '') {
                            Link::insertWebotheque($LIA_CODE, $ID_LIAISON, $oWeboTemp->getID(), $ID_REVISION);
                        }

                        preg_match('/format="([^"]*)"/i', $IMG_origine, $format);
                        preg_match('/alt="([^>"]*)"/i', $IMG_origine, $alt);
                        preg_match_all('/class="([^"]*)"/i', $IMG_origine, $aClass); // il peut y avoir temporairement plusieurs attributs 'class' à cause du remplacement de align
                        preg_match('/longdesc="([^"]*)"/i', $IMG_origine, $longDesc);
                        preg_match('/credit="([^"]*)"/i', $IMG_origine, $credit);
                        preg_match('/popup="([^"]*)"/i', $IMG_origine, $popup);
                        preg_match('/legende="([^"]*)"/i', $IMG_origine, $legende);
                        preg_match('/style="([^"]*)"/i', $IMG_origine, $style);
                        $strStyle = (! $control && $style[1] != '') ? ' ' . $style[0] : ''; // on garde le style seulement si pas de control

                        $IMG_finale = $oWeboTemp->getHTML($format[1], $alt[1], implode(' ', $aClass[1]), empty($popup[1]) ? false : '', $legende[1], empty($credit[1]), empty($longDesc[1]) ? false : $oPage, $strStyle);

                        // on corrige le contenu original
                        $IMG_corrigee = preg_replace('/src="([^"]*)"/ui', 'src="' . $oWeboTemp->getSRC($format[1]) . '"', $IMG_origine);
                        $IMG_corrigee = preg_replace('/class="align/ui', 'align="', $IMG_corrigee);
                        $contenu = str_replace(preg_replace('/class="align/ui', 'align="', $IMG_origine), $IMG_corrigee, $contenu);
                    } else {
                        // pas de cible, on supprime du contenu
                        $contenu = str_replace(preg_replace('/class="align/ui', 'align="', $IMG_origine), '', $contenu);
                    }
                }
                $contenuParse = str_replace($IMG_origine, $IMG_finale, $contenuParse);
            }
        } else {
            $contenuParse = preg_replace('/<img [^>]*>/is', '', $contenuParse);
            $contenu = preg_replace('/<img [^>]*>/is', '', $contenu);
        }

        // récupération des Medias (doit être traité en fin de fonction car les médias ont une alternative qui peux également être parsée)
        preg_match_all('/(<object [^>]+>)(.*)<\/object>/Uis', $contenuParse, $aObject);
        foreach ($aObject[0] as $object_origine) {
            $object_final = '';

            preg_match('/id="([a-zA-Z_]*)_([0-9@]*)_?([a-zA-Z]*)"/i', $object_origine, $idObject);

            // Récupération du WBT_CODE
            $WBT_CODE = $idObject[1];

            // Récupération du MOD_CODE
            $modCode = str_replace('WBT_', 'MOD_WEBOTHEQUE_', $WBT_CODE);

            // Récupération de l'idtf
            $idtf = $idObject[2];

            preg_match('/width="([0-9]*)"/i', $object_origine, $width);
            preg_match('/height="([0-9]*)"/i', $object_origine, $height);

            $width = $width[1];
            $height = $height[1];

            // Si le média existe et qu'il est du bon type
            if ($WBT_CODE && in_array($WBT_CODE, array(
                'WBT_FLASH',
                'WBT_VIDEO',
                'WBT_VIDEOEXTERNE',
                'WBT_MUSIC'
            )) && array_key_exists($modCode, $enabled) && $enabled[$modCode]) {
                // On récupère l'alignement
                $align = count($idObject) > 2 ? $idObject[3] : '';

                $oWeboTemp = false;
                switch ($WBT_CODE) {
                    case 'WBT_FLASH':
                        $oWeboTemp = new Webo_FLASH($idtf);
                        $className = 'mceItemFlash';
                        break;
                    case 'WBT_VIDEO':
                        $oWeboTemp = new Webo_VIDEO($idtf);
                        $className = 'mceItemVideo';
                        break;
                    case 'WBT_VIDEOEXTERNE':
                        $oWeboTemp = new Webo_VIDEOEXTERNE($idtf);
                        $className = 'mceItemVideoExterne';
                        if ($align) {
                            $width = '50%';
                            $height = 300;
                        } else {
                            $width = '100%';
                            $height = 400;
                        }
                        break;
                    case 'WBT_MUSIC':
                        $aIdtf = explode('@', $idtf);
                        $className = 'mceItemMusic';
                        if (sizeof($aIdtf) == 1) {
                            $oWeboTemp = new Webo_MUSIC($idtf);
                        }
                        break;
                }

                if ($oWeboTemp && ($oWeboTemp->checkAuthorized(false) || $oWeboTemp->checkShareAuthorized(false))) {
                    // On insère l'éventuelle liaison
                    if ($LIA_CODE != '' && $ID_LIAISON != '') {
                        Link::insertWebotheque($LIA_CODE, $ID_LIAISON, $oWeboTemp->getID(), $ID_REVISION);
                    }
                    $object_final = $oWeboTemp->getHTML($align, $oWeboTemp->getField('WEB_LARGEUR'), $oWeboTemp->getField('WEB_HAUTEUR'));
                    $editorObject = '<object id="' . $WBT_CODE . '_' . $oWeboTemp->getID() . '_' . ($align != '' ? $align : 'none') . '" class="' . $className . '"  width="' . (! $width ? $oWeboTemp->getField('WEB_LARGEUR') : $width) . '" height="' . (! $height ? $oWeboTemp->getField('WEB_HAUTEUR') : $height) . '" align="' . $align . '"></object>';
                } elseif (count($aIdtf) > 1 && $WBT_CODE == 'WBT_MUSIC') {
                    foreach ($aIdtf as $key => $idtf) {
                        $oWeboTemp = new Webo_MUSIC($idtf);
                        if ($oWeboTemp->checkAuthorized(false) || $oWeboTemp->checkShareAuthorized(false)) {
                            // On insère les éventuelles liaison
                            if ($LIA_CODE != '' && $ID_LIAISON != '') {
                                Link::insertWebotheque($LIA_CODE, $ID_LIAISON, $oWeboTemp->getID(), $ID_REVISION);
                            }
                        } else {
                            unset($aIdtf[$key]);
                        }
                    }
                    $object_final = Webo_MUSIC::getHTMLMulti($aIdtf, $align);
                    $editorObject = '<object id="' . $WBT_CODE . '_' . implode('@', $aIdtf) . '_' . ($align != '' ? $align : 'none') . '" class="' . $className . '"  width="160" height="20" align="' . $align . '"></object>';
                } else {
                    $editorObject = '';
                }
                // on met à jour la source de l'éditeur WYSIWYG
                // en n'oubliant pas de transformer les class en align
                $contenu = str_replace(str_replace('class="align', 'align="', $object_origine), $editorObject, $contenu);
            } else {
                // On remplace les class="alignX" par des align="X" qu'on avait initialement
                $object_origineBis = preg_replace('/class="align([^"]*)"/ui', 'align="\\1"', $object_origine);
                $contenu = str_replace($object_origineBis, '', $contenu);
            }
            // On met à jour le contenu parsé
            $contenuParse = str_replace($object_origine, $object_final, $contenuParse);
        }

        return $contenuParse;
    }
}
