<?php
$dbh   = DB::getInstance();
$oPage = CMS::getCurrentSite()->getCurrentPage();

//Poids des différents éléments pris en compte lors d'une recherche sur une page
define('MOTS_CLES_1', 100);
define('MOTS_CLES_2', 60);
define('MOTS_CLES_3', 40);
define('MOTS_CLES_4', 30);
define('MOTS_CLES_5', 20);
define('TITRE_PAGE', 10);
define('ACCROCHE_PAGE', 8);
define('PARAGRAPHE', 5);
define('DOCUMENT', 1);
// Poids supplémentaire quand plusieurs mots clés trouvés simultanément
define('MOT_SUPPLEMENTAIRE', 50);
//Nombre max de paragraphes affichés pour une page
define('NB_PARAGRAPHE', 3);

//on initialise les tableaux
$aPAGE_SITE = $aPAGE_OTHER = $aPARAGRAPHE = $aDOC = $aFINAL = $aFINAL_OTHER = array ();

// recherche référencement ?
$oRechercheReferencement = false;
if (is_numeric($_GET['idRef'])) {
    require_once CLASS_DIR . 'class.db_rechercheReferencement.php';
    $oRechercheReferencement = new RechercheReferencement($_GET['idRef']);
    if ($oRechercheReferencement->exist()) {
        CMS::replaceTITLE($oRechercheReferencement->getField('REC_TITLE'));
        $_GET['searchString'] = $oRechercheReferencement->getField('REC_EXPRESSION');
    } else {
        $oRechercheReferencement = false;
    }
}


/**********************/
/* NOUVELLE RECHERCHE */
/**********************/
if (!empty($_GET['searchString']) && (!isset($_SESSION['tpl_recherche']) || !isset($_GET['indexFINAL']))) {
    //on va chercher les stopwords
    $sql = "select STP_LIBELLE from STOPWORD_SITE where SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID());
    $aStopwords = $dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN);

    //traitement de la chaine de recherche
    $searchString = str_replace(array ('<', '>', '.', ','), array ('&lt;', '&gt;', ' ', ' '), trim($_GET['searchString']));
    if ($searchString{0} == '"' && $searchString{strlen($searchString)-1} == '"') {
        $tabKeyword = array(substr($searchString, 1, -1));
    } else {
        $tabKeyword = array_unique(array_merge(explode(' ', $searchString), array ($searchString))); //on met la chaine complete + les mots à l'unicité
    }

    foreach ($tabKeyword as $key => $mot) {
        unset($tabKeyword[$key]);
        if (mb_strlen($mot) >= 2 && !in_array(mb_strtolower($mot), $aStopwords)) {
            $mot_regexp = _genererRegexp($mot);
            if ($mot_regexp != '') {
                $tabKeyword[$mot] = $mot_regexp;
            }
        }
    }

    if (count($tabKeyword) > 0) {
        // preparation filtre (site code + page interdite)
        $filtreSupplementaire = "and PAG_EXCLURECHERCHE=0 and " . CMS::$mode . "PAGE.SIT_CODE in ('" . CMS::getCurrentSite()->getID();
        $sql = "select SIT_RECHERCHE from DD_SITE where SIT_CODE='" . CMS::getCurrentSite()->getID() . "'";
        $SIT_RECHERCHE = $dbh->query($sql)->fetchColumn();

        if ($SIT_RECHERCHE != '') {
            $filtreSupplementaire .= "','" . str_replace('@', "','", $SIT_RECHERCHE);
        }
        $filtreSupplementaire .= "')";

        if (sizeof(Page::getForbiddenID(CMS::$mode)) > 0) {
            $filtreSupplementaire .= " and " . CMS::$mode . "PAGE.ID_PAGE not in(" . implode(',', Page::getForbiddenID(CMS::$mode)) . ")";
        }

        //preparation des types de donnees externes
        $sql = "select DD_RECHERCHE.* from DD_RECHERCHE
            inner join SITE_MODULE on DD_RECHERCHE.MOD_CODE=SITE_MODULE.MOD_CODE
            where SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID()) . " and REC_RESULTATTITRE <> ''";
        $aEXTERNE = $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        /***************************/
        /* ITERATION DES MOTS-CLES */
        /***************************/
        $aTmpPAGE_SITE = $aTmpPAGE_OTHER = $aTmpExterne = array();
        foreach ($tabKeyword as $mot => $mot_regexp) {
            $mot_regexp = "[[:<:]]" . $mot_regexp;
            /*********/
            /* PAGES */
            /*********/
            //Recherche sur les mots clés
            for ($i = 1; $i <= 5; $i++) {
                $sql = "select ID_PAGE, SIT_CODE from " . CMS::$mode . "PAGE where PAG_MOTCLE" . $i . "=" . $dbh->quote($mot) . $filtreSupplementaire;
                foreach ($dbh->query($sql, PDO::FETCH_ASSOC) as $row) {
                    if ($row['SIT_CODE'] == CMS::getCurrentSite()->getID()) {
                        $aPAGE_SITE[$row['ID_PAGE']] += constant('MOTS_CLES_' . $i);
                    } else {
                        $aPAGE_OTHER[$row['ID_PAGE']] += constant('MOTS_CLES_' . $i);
                    }
                }
            }

            //Recherche sur le titre des pages
            $sql = "select ID_PAGE, SIT_CODE from " . CMS::$mode . "PAGE where PAG_TITRE regexp " . $dbh->quote($mot_regexp) . $filtreSupplementaire;
            foreach ($dbh->query($sql, PDO::FETCH_ASSOC) as $row) {
                if ($row['SIT_CODE'] == CMS::getCurrentSite()->getID()) {
                    $aPAGE_SITE[$row['ID_PAGE']] += TITRE_PAGE;
                } else {
                    $aPAGE_OTHER[$row['ID_PAGE']] += TITRE_PAGE;
                }
            }

            //Recherche sur l'accroche
            $sql = "select ID_PAGE, SIT_CODE from " . CMS::$mode . "PAGE where PAG_ACCROCHE regexp " . $dbh->quote($mot_regexp) . $filtreSupplementaire;
            foreach ($dbh->query($sql, PDO::FETCH_ASSOC) as $row) {
                if ($row['SIT_CODE'] == CMS::getCurrentSite()->getID()) {
                    $aPAGE_SITE[$row['ID_PAGE']] += ACCROCHE_PAGE;
                } else {
                    $aPAGE_OTHER[$row['ID_PAGE']] += ACCROCHE_PAGE;
                }
            }

            //Recherche sur les paragraphes (titre + contenu)
            $sql = "select " . CMS::$mode . "PAGE.ID_PAGE, ID_PARAGRAPHE, SIT_CODE
                                    from " . CMS::$mode . "PARAGRAPHE inner join " . CMS::$mode . "PAGE using(ID_PAGE)
                                    where (PAR_TITRE like " . $dbh->quote('%' . $mot . '%') . " or PAR_CONTENUTEXTE like " . $dbh->quote('%' . $mot . '%') . ")" . $filtreSupplementaire;

            foreach ($dbh->query($sql, PDO::FETCH_ASSOC) as $row) {
                if ($row['SIT_CODE'] == CMS::getCurrentSite()->getID()) {
                    $aPAGE_SITE[$row['ID_PAGE']] += PARAGRAPHE;
                } else {
                    $aPAGE_OTHER[$row['ID_PAGE']] += PARAGRAPHE;
                }

                $aPARAGRAPHE[$row['ID_PAGE']][$row['ID_PARAGRAPHE']] += 1;
            }

            //Recherche sur les documents référencés par les pages
            $sql = "select " . CMS::$mode . "PAGE.ID_PAGE, " . CMS::$mode . "PARAGRAPHE.ID_PARAGRAPHE, " . CMS::$mode . "PAGE.SIT_CODE, WEBOTHEQUE.ID_WEBOTHEQUE, WEB_CHEMIN, WEB_LIBELLE from WEBOTHEQUE
                inner join LIAISON_WEBOTHEQUE on WEBOTHEQUE.ID_WEBOTHEQUE=LIAISON_WEBOTHEQUE.ID_WEBOTHEQUE
                inner join " . CMS::$mode . "PARAGRAPHE on ID_LIAISON = " . CMS::$mode . "PARAGRAPHE.ID_PARAGRAPHE
                inner join " . CMS::$mode . "PAGE using(ID_PAGE)
                where LIA_CODE = '" . CMS::$mode . "PARAGRAPHE' " . $filtreSupplementaire . "
                and LIAISON_WEBOTHEQUE.ID_WEBOTHEQUE in (select ID_WEBOTHEQUE from WEBOTHEQUEINDEXER where IND_TEXTE like " . $dbh->quote('% ' . $mot . ' %') . ")";

            foreach ($dbh->query($sql, PDO::FETCH_ASSOC) as $row) {
                if ($row['SIT_CODE'] == CMS::getCurrentSite()->getID()) {
                    $aPAGE_SITE[$row['ID_PAGE']] += DOCUMENT;
                } else {
                    $aPAGE_OTHER[$row['ID_PAGE']] += DOCUMENT;
                }

                $aPARAGRAPHE[$row['ID_PAGE']][$row['ID_PARAGRAPHE']] += 1;
                $aDOC[$row['ID_PAGE']][$row['ID_WEBOTHEQUE']] = array ('WEB_LIBELLE' => $row['WEB_LIBELLE'], 'WEB_CHEMIN' => $row['WEB_CHEMIN']);
            }

            //On parcours les keywords pour rechercher ceux qui ont déja été retrouvés
            //On donne plus d'importance à une page contenant les deux mots.
            foreach ($aTmpPAGE_SITE as $idPage => $poids) {
                if ($aPAGE_SITE[$idPage] > $poids) {
                    $aPAGE_SITE[$idPage] += MOT_SUPPLEMENTAIRE;
                }
            }
            foreach ($aTmpPAGE_OTHER as $idPage => $poids) {
                if ($aPAGE_OTHER[$idPage] > $poids) {
                    $aPAGE_OTHER[$idPage] += MOT_SUPPLEMENTAIRE;
                }
            }

            $aTmpPAGE_SITE = $aPAGE_SITE;
            $aTmpPAGE_OTHER = $aPAGE_OTHER;

            /********************/
            /* DONNEES EXTERNES */
            /********************/
            foreach ($aEXTERNE as $key => $record) {
                if ($record['PGS_CODE'] == '' || !($oPageDestination = CMS::getCurrentSite()->getSpecialePage($record['PGS_CODE']))) {
                    $oPageDestination = CMS::getCurrentSite()->getCurrentPage();
                }

                if ($record['REC_CHAMP1'] != '' && $record['REC_POIDS1'] != '') {
                    $sql = "select " . $record['REC_TABLE'] . "." . $record['REC_IDENTIFIANT'] . ", " . $record['REC_RESULTATTITRE'] . " as TITRE, " . $record['REC_RESULTATDESCRIPTION'] . " as CONTENU from " . $record['REC_TABLE'] . " where " . $record['REC_CHAMP1'] . " regexp ? " . $record['REC_FILTRE'];
                    eval("\$sql = \"$sql\";");
                    $stmt = $dbh->prepare($sql);
                    $stmt->bindValue(1, $mot_regexp, PDO::PARAM_STR);
                    $stmt->execute();

                    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                        if (is_array($aEXTERNE[$key]['RESULT'][$row[$record['REC_IDENTIFIANT']]])) {
                            $aEXTERNE[$key]['RESULT'][$row[$record['REC_IDENTIFIANT']]]['POIDS'] += $record['REC_POIDS1'];
                        } else {
                            $aEXTERNE[$key]['RESULT'][$row[$record['REC_IDENTIFIANT']]] = array (
                                'POIDS' => $record['REC_POIDS1'],
                                'ANCHOR' => $oPageDestination->getAnchor(array('PAR_TPL_IDENTIFIANT' => $row[$record['REC_IDENTIFIANT']], 'TPL_CODE' => $record['TPL_CODE'])),
                                'TITLE' => '',
                                'TITRE' => _highlight($tabKeyword, encode($row['TITRE'])),
                                'CONTENU' => '<p>' . _highlight($tabKeyword, encode(strip_tags($row['CONTENU']))) . '</p>');
                        }
                    }
                    $stmt->closeCursor();
                }

                if ($record['REC_CHAMP2'] != '' && $record['REC_POIDS2'] != '') {
                    $sql = "select " . $record['REC_TABLE'] . "." . $record['REC_IDENTIFIANT'] . ", " . $record['REC_RESULTATTITRE'] . " as TITRE, " . $record['REC_RESULTATDESCRIPTION'] . " as CONTENU from " . $record['REC_TABLE'] . " where " . $record['REC_CHAMP2'] . " regexp ? " . $record['REC_FILTRE'];
                    eval("\$sql = \"$sql\";");
                    $stmt = $dbh->prepare($sql);
                    $stmt->bindValue(1, $mot_regexp, PDO::PARAM_STR);
                    $stmt->execute();

                    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                        if (is_array($aEXTERNE[$key]['RESULT'][$row[$record['REC_IDENTIFIANT']]])) {
                            $aEXTERNE[$key]['RESULT'][$row[$record['REC_IDENTIFIANT']]]['POIDS'] += $record['REC_POIDS2'];
                        } else {
                            $aEXTERNE[$key]['RESULT'][$row[$record['REC_IDENTIFIANT']]] = array (
                                'POIDS' => $record['REC_POIDS2'],
                                'ANCHOR' => $oPageDestination->getAnchor(array('PAR_TPL_IDENTIFIANT' => $row[$record['REC_IDENTIFIANT']], 'TPL_CODE' => $record['TPL_CODE'])),
                                'TITLE' => '',
                                'TITRE' => _highlight($tabKeyword, encode($row['TITRE'])),
                                'CONTENU' => '<p>' . _highlight($tabKeyword, encode(strip_tags($row['CONTENU']))) . '</p>');
                        }
                    }
                    $stmt->closeCursor();
                }
            }

            /**********************************************************************************************/
            foreach ($aTmpExterne as $ext_code => $value) {
                $tmpEXT_CODE = $aTmpExterne[$ext_code]['RESULT'];

                if (is_array($tmpEXT_CODE)) {
                    foreach ($tmpEXT_CODE as $idDE => $aResult) {
                        if ($aEXTERNE[$ext_code]['RESULT'][$idDE]['POIDS']>$aResult['POIDS']) {
                            $aEXTERNE[$ext_code]['RESULT'][$idDE]['POIDS'] += MOT_SUPPLEMENTAIRE;
                        }
                    }
                }
            }
            $aTmpExterne = $aEXTERNE;
            /**********************************************************************************************/

        }

        /**************/
        /* PAGES SITE */
        /**************/
        if (sizeof($aPAGE_SITE) > 0) {
            $sql = "select * from " . CMS::$mode . "PAGE where ID_PAGE in (" . implode(',', array_keys($aPAGE_SITE)) . ")";

            foreach ($dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $oPageTemp = new Page($row['ID_PAGE'], CMS::$mode);
                $oPageTemp->setFields($row);
                $CONTENU = '';

                if ($row['PAG_ACCROCHE'] != '') {
                    $CONTENU .= '<p>' . _highlight($tabKeyword, encode($row['PAG_ACCROCHE'])) . '</p>';
                } elseif (is_array($aPARAGRAPHE[$row['ID_PAGE']])) {
                    //Si il existe des paragraphes associés à cette page on les affiche
                    $_tab = $aPARAGRAPHE[$row['ID_PAGE']];
                    arsort($_tab);
                    $_tab = array_slice(array_keys($_tab), 0, NB_PARAGRAPHE);

                    $sql = "select * from " . CMS::$mode . "PARAGRAPHE where ID_PARAGRAPHE in (" . implode(',', $_tab) . ") and TPL_CODE = '' order by PAR_POIDS";
                    foreach ($dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row2) {
                        $Paragraphe_class = 'Paragraphe' . substr($row2['PRT_CODE'], 3);
                        $oParaTemp = new $Paragraphe_class ($row2['ID_PARAGRAPHE'], CMS::$mode);
                        $oParaTemp->setFields($row2);
                        $CONTENU .= '<p>' . _highlight($tabKeyword, $oParaTemp->display(), true) . '</p>';
                    }
                }

                if (is_array($aDOC[$row['ID_PAGE']])) {
                    $_tab = array_keys($aDOC[$row['ID_PAGE']]);
                    $CONTENU .= '<ul>';

                    foreach ($aDOC[$row['ID_PAGE']] as $ID_WEBOTHEQUE => $rowWEBO) {
                        $CONTENU .= '<li><a href="' . UPLOAD_DOCUMENT . $rowWEBO['WEB_CHEMIN'] . '" class="document">' . encode($rowWEBO['WEB_LIBELLE'], false) . '</a></li>';
                    }
                    $CONTENU .= '</ul>';
                }

                $aFINAL[] = array (
                    'POIDS' => $aPAGE_SITE[$row['ID_PAGE']],
                    'ANCHOR' => $oPageTemp->getAnchor(),
                    'TITLE' => encode($row['PAG_TITLE'], false),
                    'TITRE' => _highlight($tabKeyword, encode($row['PAG_TITRE_MENU'])),
                    'CONTENU' => $CONTENU);
            }
        }

        /***************/
        /* PAGES OTHER */
        /***************/
        if (sizeof($aPAGE_OTHER) > 0) {
            $sql = "select * from " . CMS::$mode . "PAGE where ID_PAGE in (" . implode(',', array_keys($aPAGE_OTHER)) . ")";
            foreach ($dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $oPageTemp = new Page($row['ID_PAGE'], CMS::$mode);
                $oPageTemp->setFields($row);
                $CONTENU = '';

                if ($row['PAG_ACCROCHE'] != '') {
                    $CONTENU .= '<p>' . _highlight($tabKeyword, encode($row['PAG_ACCROCHE'])) . '</p>';
                } elseif (is_array($aPARAGRAPHE[$rowTPL['ID_PAGE']])) {
                    //Si il existe des paragraphes associés à cette page on les affiche
                    $_tab = $aPARAGRAPHE[$rowTPL['ID_PAGE']];
                    arsort($_tab);
                    $_tab = array_slice(array_keys($_tab), 0, NB_PARAGRAPHE);

                    $sql = "select * from " . CMS::$mode . "PARAGRAPHE where ID_PARAGRAPHE in (" . implode(',', $_tab) . ") and TPL_CODE = '' order by PAR_POIDS";
                    foreach ($dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row2) {
                        $Paragraphe_class = 'Paragraphe' . substr($row2['PRT_CODE'], 3);
                        $oParaTemp = new $Paragraphe_class ($row2['ID_PARAGRAPHE'], CMS::$mode);
                        $oParaTemp->setFields($row2);
                        $CONTENU .= '<p>' . _highlight($tabKeyword, $oParaTemp->display(), true) . '</p>';
                    }
                }

                if (is_array($aDOC[$row['ID_PAGE']])) {
                    $_tab = array_keys($aDOC[$row['ID_PAGE']]);
                    $CONTENU .= '<ul>';
                    foreach ($aDOC[$row['ID_PAGE']] as $ID_WEBOTHEQUE => $rowWEBO) {
                        $CONTENU .= '<li><a href="' . UPLOAD_DOCUMENT . $rowWEBO['WEB_CHEMIN'] . '" class="document">' . encode($rowWEBO['WEB_LIBELLE'], false) . '</a></li>';
                    }
                    $CONTENU .= '</ul>';
                }

                $aFINAL_OTHER[] = array (
                    'POIDS' => $aPAGE_OTHER[$row['ID_PAGE']],
                    'ANCHOR' => $oPageTemp->getAnchor(),
                    'TITLE' => encode($row['PAG_TITLE'], false),
                    'TITRE' => _highlight($tabKeyword, encode($row['PAG_TITRE_MENU'])),
                    'CONTENU' => $CONTENU);
            }
        }

        /********************/
        /* DONNEES EXTERNES */
        /********************/
        foreach ($aEXTERNE as $EXT_CODE => $record) {
            if (is_array($record['RESULT'])) {
                foreach ($record['RESULT'] as $RESULT) {
                    $aFINAL[] = $RESULT;
                }
            }
        }

        /********************************/
        /* ORDONNANCEMENT DES RESULTATS */
        /********************************/
        function _POIDS($a, $b)
        {
            return ($a['POIDS'] < $b['POIDS']);
        }
        usort($aFINAL, '_POIDS');
        usort($aFINAL_OTHER, '_POIDS');
        $_SESSION['tpl_recherche']['aFINAL'] = $aFINAL;
        $_SESSION['tpl_recherche']['aFINAL_OTHER'] = $aFINAL_OTHER;
    }
} elseif (isset($_SESSION['tpl_recherche']) && !empty($_GET['searchString'])) {
    $aFINAL = $_SESSION['tpl_recherche']['aFINAL'];
    $aFINAL_OTHER = $_SESSION['tpl_recherche']['aFINAL_OTHER'];
}

if ($_GET['indexFINAL'] == '') {
    $_GET['indexFINAL'] = 1;
}
if ($_GET['indexOTHER'] == '') {
    $_GET['indexOTHER'] = 1;
}

$_GET['mpp'] = '10';
$tailleFINAL = sizeof($aFINAL);
$aFINAL = array_slice($aFINAL, ($_GET['indexFINAL'] - 1) * $_GET['mpp'], $_GET['mpp']);
$tailleFINAL_OTHER = sizeof($aFINAL_OTHER);
$aFINAL_OTHER = array_slice($aFINAL_OTHER, ($_GET['indexOTHER'] - 1) * $_GET['mpp'], $_GET['mpp']);
?>

<div class="tpl_recherche">
<?php if ($oRechercheReferencement) { ?>
    <div class="clearfix">
        <?php echo $oRechercheReferencement->getDescription(); ?>
    </div>
<?php } ?>
<?php
if ($_GET['PAG_TITLE'] == '') {
    //On récupère le buffer pour modifier l'élément title (qu'il soit différent d'une page à l'autre)
    $ob = ob_get_contents();
    ob_clean();
    $ob = preg_replace('/<title>[^<]*<\/title>/si', '<title>' .
    encode($oPage->getField('PAG_TITRE_REFERENCEMENT'), false) . ' : ' . $oTemplate->i18n('resultats_sur') . ' ' . encode('"' . $_GET['searchString'] . '"', false) .
    ' - ' . (ceil($tailleFINAL / $_GET['mpp']) != 0 ? $oTemplate->i18n('Page') . ' ' . $_GET['indexFINAL'] . '/' . ceil($tailleFINAL / $_GET['mpp']) : $oTemplate->i18n('Aucun_resultat')) .
     (CMS::getCurrentSite()->getField('SIT_TITLE') != '' ? ' - ' . CMS::getCurrentSite()->getField('SIT_TITLE') : '') . '</title>', $ob);
    echo $ob;
}

if ($tailleFINAL > 0) {
    echo _navigation($tailleFINAL, array ('searchString' => $_GET['searchString'], 'indexOTHER' => $_GET['indexOTHER']), 'indexFINAL');
?>
    <ul class="liste">
        <?php foreach ($aFINAL as $record) { ?>
        <li class="item">
            <div class="itemInfo">
                <h3><a <?php echo $record['ANCHOR']; echo ' title="' . ($record['TITLE'] != '' ? $record['TITLE'] : '') . '"'?>>
                    <?php echo $record['TITRE'] ?>
                </a></h3>
                <div class="description">
                    <?php echo $record['CONTENU'] ?>
                </div>
            </div>
        </li>
        <?php } ?>
    </ul>
    <?php
        echo _navigation($tailleFINAL, array ('searchString' => $_GET['searchString'], 'indexOTHER' => $_GET['indexOTHER']), 'indexFINAL', true);
} else { ?>
    <h3><?php echo $oTemplate->i18n('Aucun_resultat') ?></h3>
<?php } ?>

<?php if ($tailleFINAL_OTHER > 0) {
    echo _navigation($tailleFINAL_OTHER, array ('searchString' => $_GET['searchString'], 'indexFINAL' => $_GET['indexFINAL']), 'indexOTHER') ?>

    <ul class="liste">
        <?php foreach ($aFINAL_OTHER as $record) { ?>
        <li class="item">
            <div class="itemInfo">
                <h3><a <?php echo $record['ANCHOR']; echo ' title="' . ($record['TITLE'] != '' ? $record['TITLE'] : '') . '"'?>>
                    <?php echo $record['TITRE'] ?>
                </a></h3>
                <div class="description">
                    <?php echo $record['CONTENU'] ?>
                </div>
            </div>
        </li>
        <?php } ?>
    </ul>
<?php }

/**
 * @param $taille nb resultat total
 * @param $aParam tableau param
 * @param $index identifiant contenant l'index de la page courante
 * @param $_lang langue
 */
function _navigation($taille, $aParam, $index, $regletteSeulement = false)
{
    $oTemplate = Paragraphe::getCurrentTemplate();
    $oPage = CMS::getCurrentSite()->getCurrentPage();

    //Nombre de pages par réglette
    $nbPageReglette = 4;
    //Calcul du nombre de page
    $nbPage = ceil($taille / $_GET['mpp']);
    //Calcul du nombre de résultat sur cette page
    $nbResultat = ($taille <= $_GET['mpp']) ? $taille : (($_GET[$index] != $nbPage) ? $_GET['mpp'] : ($taille - ($_GET['mpp'] * ($nbPage -1))));

    $retour = '<div class="blocNavigation">';
    if (!$regletteSeulement) {
        $retour .= "<div class='resultatNavigation'>";
        $retour .= $nbResultat . ' ';
        $retour .= ($nbResultat > 1) ? $oTemplate->i18n('resultats_sur') : $oTemplate->i18n('resultat_sur');
        $retour .= $taille . " - " . $oTemplate->i18n('Page') . " ";
        $retour .= $_GET[$index] . '/' . $nbPage;
        $retour .= "</div>";
    }

    if ($nbPage > 1) {
        $retour .= "<div class='regletteNavigation'>";
        //Première page
        if ($_GET[$index] > $nbPageReglette +1)
            $retour .= '<span>[<a ' . $oPage->getAnchor(array_merge(array ($index => '1'), $aParam)) . ' title="' . $oTemplate->i18n('Consulter_premiere_page') . '">' . $oTemplate->i18n('Premiere') . '</a>]</span>';
        //Saut arriere
        if ($_GET[$index] > (2 * $nbPageReglette +1))
            $retour .= '<span>[<a ' . $oPage->getAnchor(array_merge(array ($index => ($_GET[$index] - 2 * $nbPageReglette)), $aParam)) . ' title="' . $oTemplate->i18n('Consulter_page_X', array($_GET[$index] - 2 * $nbPageReglette)) . '">&lt;&lt;</a>]</span>';

        //Si l'index courant est supérieur à la longueur de la réglette on affiche la réglette devant
        $debut = ($_GET[$index] > $nbPageReglette) ? $_GET[$index] - $nbPageReglette : 1;
        //Si l'index courant est inférieur à la longueur de la réglette on affiche la réglette après
        $fin = ($_GET[$index] < $nbPage - $nbPageReglette) ? $_GET[$index] + $nbPageReglette : $nbPage;

        for ($i = $debut; $i <= $fin; $i++) {
            $retour .= ($_GET[$index] == $i) ? '<span class="selected">[<strong>' . $i . '</strong>]</span>' : '<span>[<a ' . $oPage->getAnchor(array_merge(array ($index => $i), $aParam)) . ' title="' . $oTemplate->i18n('Consulter_page_X', array($i)) . '">' . $i . '</a>]</span>';
        }

        //Saut avant
        if ($_GET[$index] < ($nbPage -2 * $nbPageReglette))
            $retour .= '<span>[<a ' . $oPage->getAnchor(array_merge(array ($index => ($_GET[$index] + 2 * $nbPageReglette)), $aParam)) . ' title="' . $oTemplate->i18n('Consulter_page_X', array($_GET[$index] + 2 * $nbPageReglette)) . '" >&gt;&gt;</a>]</span>';
        //Dernière page
        if ($_GET[$index] < ($nbPage - $nbPageReglette))
            $retour .= '<span>[<a ' . $oPage->getAnchor(array_merge(array ($index => $nbPage), $aParam)) . ' title="' . $oTemplate->i18n('Consulter_derniere_page') . '">' . $oTemplate->i18n('Derniere') . '</a>]</span>';

        $retour .= "</div>";

        //maj des balise suivant et precedent dans le header

        if ($_GET[$index] > 1) {
            $link = '<link rel="prev" href="' . $oPage->getURL(array_merge(array ($index => ($_GET[$index] - 1)), $aParam)) . '" />';
            CMS::addHEADER($link);
        }
        if ($_GET[$index] < $nbPage) {
            $link = '<link rel="next" href="' . $oPage->getURL(array_merge(array ($index => ($_GET[$index] + 1)), $aParam)) . '" />';
            CMS::addHEADER($link);
        }



    }
    $retour .= "</div>";

    return $retour;
}

/**
 * @param $mots tableau des mots recherchés
 * @param $str chaine à highlighter
 * @param $blnExtraits faut-il rogner $str autours des mots trouvés
 */
function _highlight($aMots, $str, $blnExtraits = false)
{
    // on retraite la chaine pour enlever les br & p de façon à laisser tout de même un espace
    $str = str_replace(array (
        '<br>',
        '</h2>',
        '</h3>',
        '</h4>',
        '</h5>',
        '</h6>',
        '</li>',
        '</p>',
        '</td>'
    ), '. ', $str);
    // on vire les éléments HTML indésirables
    $str = strip_tags($str, '<acronym><span><abbr>');
    // on capte des extraits autours des mots
    if ($blnExtraits) {
        $zpattern = '/(^|\\.\\s+)([^.]|\\.(?!\\s+))*(' . implode('|', $aMots) . ')([^.]|\\.(?!\\s+)|\\.(?=\\s*\\<\\/acronym\\>)|\\.(?=\\s*\\<\\/span\\>)|\\.(?=\\s*\\<\\/abbr\\>))*($|\\.\\s+)/ism';
        if (preg_match_all($zpattern, $str, $arrMatches)) {
            $str = '...' . implode('...<br>...', $arrMatches[0]) . '...';
        }
    }
    foreach ($aMots as $mot_regexp) {
        $str = preg_replace('/((?!(<[^>]*)[\W])|^)(' . $mot_regexp . ')(?!(<[^>]*>))/uims', '$1<strong>$3</strong>', $str);
    }

    return $str;
}

function _genererRegexp($str)
{
    $str = preg_quote($str, '/');
    $tabLettres[] = 'aáâàä';//ãåÀÁÂÄÅ';
    $tabLettres[] = 'eéêèë';//ÉÊËÈ';
    $tabLettres[] = 'iíîìï';//ÌÍÎÏ';
    $tabLettres[] = 'oóôòö';//õÒÓÔÕÖ';
    $tabLettres[] = 'uúûùü';//ÙÚÛÜ';
    $tabLettres[] = 'cç';//Ç';
    /*
    $tabLettres[] = 'dÐ';
    $tabLettres[] = 'sšŠ';
    $tabLettres[] = 'nñÑ';
    $tabLettres[] = 'yýÿÝŸ';
    $tabLettres[] = 'zžŽ';
    */
    foreach ($tabLettres as $val) {
        $str = preg_replace('/([' . $val . '])/ui', '(' . implode('|', mb_str_split($val)) . ')', $str);
    }

    return $str;
}

function mb_str_split($str)
{
    $result = array ();
    for ($i = 0; $i < mb_strlen($str); $i++) {
        $result[] = mb_substr($str, $i, 1);
    }

    return $result;
} ?>
</div>
