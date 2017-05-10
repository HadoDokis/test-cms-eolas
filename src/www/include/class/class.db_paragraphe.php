<?php
require_once CLASS_DIR . 'class.db_ajax.php';

class Paragraphe extends Ajax
{
    /**
     * @var string Mode CMS
     */
    protected $mode;

    /**
     * @var string Type de Paragraphe
     */
    protected $code = null;

    /**
     * @var Page
     */
    protected $_oPage = null;

    /**
     * @var bool
     */
    protected $_editable = true;

    private static $_currentTemplateRestriction = null;
    private static $_currentTemplate = null;
    private static $_render = true;
    private $_templateRestriction = null;

    public function __construct($idtf, $mode = 'OFF_', $oPage = null)
    {
        parent::__construct($mode.'PARAGRAPHE', 'ID_PARAGRAPHE', $idtf);
        $this->mode = $mode;
        if (!is_null($oPage)) {
            $this->_oPage = $oPage;
        }
    }

    public static function getModuleCode()
    {
        return 'MOD_CORE';
    }

    public function checkAuthorized($strict = true)
    {
        if ($this->exist() && $this->getField('ID_PAGE') != null && intval($this->getField('ID_PAGE')) > 0) {
            require_once CLASS_DIR . 'class.db_page.php';
            $oPage = new Page($this->getField('ID_PAGE'), $this->mode);
            if ($oPage->exist() && $oPage->getField('SIT_CODE') == CMS::getCurrentSite()->getID()) {
                return true;
            }
        }
        if ($strict) {
            header('Location:' . SERVER_ROOT . 'cms/index.php?logout=1');
            exit ();
        }

        return false;
    }

    public function isEditable()
    {
        return $this->_editable;
    }

    /**
     * Retourne l'idtf du paragraphe source ou faux sinon
     */
    public function isInherited()
    {
        if ($this->getField('TPL_CODE') == 'TPL_HERITAGE') {
            return $this->getField('PAR_TPL_IDENTIFIANT');
        }

        return false;
    }

    /**
     * @param $PAR_CONTENU Le contenu à parser
     */
    public function parse($PAR_CONTENU)
    {
        $PAR_CONTENUPARSE = $this->_parse($PAR_CONTENU);

        // on retraite la chaine pour enlever les éléments de type "block" [et quelques "inline"] de façon à laisser tout de même un espace et améliorer la recherche
        $PAR_CONTENUTEXTE = html2Text($PAR_CONTENUPARSE);

        $stmt = $this->dbh->prepare("update " . $this->mode . "PARAGRAPHE set
            PAR_CONTENU=:PAR_CONTENU,
            PAR_CONTENUPARSE=:PAR_CONTENUPARSE,
            PAR_CONTENUTEXTE=:PAR_CONTENUTEXTE,
            PAR_APARSER=0
            where ID_PARAGRAPHE=:ID_PARAGRAPHE");
        $stmt->bindValue(':PAR_CONTENU', $PAR_CONTENU, PDO::PARAM_STR);
        $stmt->bindValue(':PAR_CONTENUPARSE', $PAR_CONTENUPARSE, PDO::PARAM_STR);
        $stmt->bindValue(':PAR_CONTENUTEXTE', $PAR_CONTENUTEXTE, PDO::PARAM_STR);
        $stmt->bindValue(':ID_PARAGRAPHE', $this->getID(), PDO::PARAM_INT);
        $stmt->execute();

        return $PAR_CONTENUPARSE;
    }

    /**
     * A redéfinir pour les types de paragraphe nécessitant un traitement du contenu
     * @param $PAR_CONTENU le contenu à parser
     */
    protected function _parse($PAR_CONTENU)
    {
        if ($this->code == null) {
            die(__METHOD__ . " : Appel directe de la méthode, il faut passer par une classe dérivée");
        }

        return '';
    }

    public function load()
    {
        $sql = "select * from " . $this->mode . "PARAGRAPHE where ID_PARAGRAPHE=" . $this->getID();
        if ($this->code != null) {
            $sql .= " and PRT_CODE=" . $this->dbh->quote($this->code);
        }
        if ($row = $this->dbh->query($sql)->fetch(PDO::FETCH_ASSOC)) {
            $this->setFields($row);
        } else {
            $this->_idtf = -1;
            $this->setFields(array ());
        }
    }

    public function getMode()
    {
        return $this->mode;
    }

    public function setTemplateRestriction($restriction)
    {
        $this->_templateRestriction = $restriction;
    }

    public static function setCurrentTemplate($oTemplate)
    {
        self::$_currentTemplate = $oTemplate;
    }

    public static function setCurrentTemplateRestriction($restriction)
    {
        self::$_currentTemplateRestriction = $restriction;
    }

    public static function noRender()
    {
        self::$_render = false;
    }

    /**
     * @return Template
     */
    public static function getCurrentTemplate()
    {
        return self::$_currentTemplate;
    }

    public static function getCurrentTemplateRestriction()
    {
        return self::$_currentTemplateRestriction;
    }

    /**
     * @param $copyMode vrai si la suppression sert pour une copie (OFF -> ON)
     */
    public function delete($copyMode = false)
    {
        if (!$this->isDeletable()) {
            return false;
        }

        if ($copyMode && $this->mode == 'ON_') {
            // Lors de la copie OFF -> ON,
            // si le paragraphe OFF n'existe plus, on supprime les références en ON (les références OFF ont été supprimées lors de la suppression du paragraphe en OFF)
            $sql = "select ID_PARAGRAPHE from OFF_PARAGRAPHE where ID_PARAGRAPHE=" . $this->getID();
            if (!$this->dbh->query($sql)->fetchColumn()) {
                $this->deleteInherited();
                $this->deleteShared();
            }
        } else {
            $this->deleteInherited();
            $this->deleteShared();
        }

        // On est en mode OFF_, on supprime alors ces paragraphes quiont été hérité de la liste des revisions
        if ($this->mode == 'OFF_') {

            $sql = "select ID_PARAGRAPHE, ID_REVISION from REVISION_PARAGRAPHE where TPL_CODE='TPL_HERITAGE' and PAR_TPL_IDENTIFIANT =" . $this->getID();
            $aRevisionParagraphesData = $this->dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            require_once CLASS_DIR. 'class.db_revision.php';
            foreach ($aRevisionParagraphesData as $aRevisionParagrapheData) {
                $oRevision = new Revision($aRevisionParagrapheData['ID_REVISION']);
                $oRevision->load();
                $oRevisonPara = new Paragraphe_Revision($aRevisionParagrapheData['ID_PARAGRAPHE'], $oRevision, $this->mode);
                $oRevisonPara->load();
                $oRevisonPara->delete();
            }
        }

        $sql = "update " . $this->mode . "PARAGRAPHE set PAR_APARSER=1 where ID_PARAGRAPHE in
            (select ID_LIAISON from LIAISON_PAGE where LIA_CODE='" . $this->mode . "PARAGRAPHE' and ID_PARAGRAPHE=" . $this->getID() . ")";
        $sql = "delete from LIAISON_WEBOTHEQUE where LIA_CODE='" . $this->mode . "PARAGRAPHE' and ID_LIAISON=" . $this->getID();
        $this->dbh->exec($sql);
        $sql = "delete from LIAISON_PAGE where LIA_CODE='" . $this->mode . "PARAGRAPHE' and ID_LIAISON=" . $this->getID();
        $this->dbh->exec($sql);
        $sql = "update LIAISON_PAGE set ID_PARAGRAPHE=null where ID_PARAGRAPHE=" . $this->getID();
        $this->dbh->exec($sql);
        $sql = "delete from " . $this->mode . "PARAGRAPHE where ID_PARAGRAPHE=" . $this->getID();
        $this->dbh->exec($sql);
    }

    public function isDeletable()
    {
        return true;
    }

    /**
     * Suppression des paragraphes partagés
     */
    public function deleteShared()
    {
        $sql = "select * from " . $this->mode . "PARAGRAPHE where TPL_CODE='TPL_PARTAGE' and PAR_TPL_IDENTIFIANT=" . $this->getID();
        foreach ($this->dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $oParagraphe = new Paragraphe($row['ID_PARAGRAPHE'], $this->mode);
            if ($this->mode == "OFF_") {
                $details = gettext('suppression_du_paragraphe_partage_X_de_la_page_Y');
                $histoDetail = sprintf($details, $this->getID(), $this->getField('ID_PAGE'));
                $sPAR_TITRE = $this->getField('PAR_TITRE');
                $histoDetail .= !empty($sPAR_TITRE)?' ('.$sPAR_TITRE.')':'';
                $oPageShared = new Page($oParagraphe->getField('ID_PAGE'), 'OFF_');
                $oPageShared->historize('SUPPRESSION', 'PARAGRAPHE', $histoDetail, $row['ID_PARAGRAPHE']);
            }
            $oParagraphe->delete();
        }
    }

    /**
     * Supprime tous les paragraphes hérités du paragraphe courant
     * @param $oPage un objet Page optionnel qui défini le point de départ des suppressions
     */
    public function deleteInherited($oPage = null)
    {
        $sql = "select ID_PARAGRAPHE from " . $this->mode . "PARAGRAPHE where TPL_CODE='TPL_HERITAGE' and PAR_TPL_IDENTIFIANT=" . $this->getID();
        if ($oPage != null) {
            $sql .= " and ID_PAGE in (" . implode(',', array_merge($oPage->getChildrenID(), array($oPage->getID()))) . ")";
        }
        foreach ($this->dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN) as $idtf) {
            $oPara = new Paragraphe($idtf, $this->mode);
            if ($this->mode == "OFF_") {
                $oPageHerited = new Page($oPara->getField("ID_PAGE"), $this->mode);
                $details = gettext('suppression_du_paragraphe_herite_X_de_la_page_Y');
                $histoDetail = sprintf($details, $this->getField('ID_PARAGRAPHE'), $this->getField('ID_PAGE'));
                $sPAR_TITRE = $this->getField('PAR_TITRE');
                $histoDetail .= !empty($sPAR_TITRE)?' ('.$sPAR_TITRE.')':'';
                $oPageHerited->historize('SUPPRESSION', 'PARAGRAPHE', $histoDetail, $idtf);
            }
            $oPara->delete();
        }
    }

    /**
     * Propage le paragraphe à tous les descendants si non déjà présent
     */
    public function inherit()
    {
        if ($this->getField('PAR_HERITABLE')) {
            //filtre pour exclure les paragraphes qui seraient déjà hérités
            $sql = "select ID_PAGE from " . $this->mode . "PARAGRAPHE where TPL_CODE='TPL_HERITAGE' and PAR_TPL_IDENTIFIANT=" . $this->getID();
            $aID = $this->dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN);
            $filtre = (sizeof($aID) == 0) ? '' : ' and ID_PAGE not in (' . implode(',', $aID) . ')';

            //en mode ON_  il faut récupérer les ID_PARAGRAPHE correspondant du OFF_ pour l'insertion
            if ($this->mode == 'ON_') {
                $sql = "select ID_PAGE, ID_PARAGRAPHE from OFF_PARAGRAPHE where TPL_CODE='TPL_HERITAGE' and PAR_TPL_IDENTIFIANT=" . $this->getID();
                $aIDbis = $this->dbh->query($sql)->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE | PDO::FETCH_COLUMN);
            }

            $stmt = $this->dbh->prepare("insert into " . $this->mode . "PARAGRAPHE (
                ID_PARAGRAPHE,
                ID_PAGE,
                PRT_CODE,
                TPL_CODE,
                PAR_TPL_IDENTIFIANT,
                PAR_POIDS,
                PAR_COLONNE,
                PRS_CODE,
                PRS_WIDTH
                ) values (
                :ID_PARAGRAPHE,
                :ID_PAGE,
                :PRT_CODE,
                'TPL_HERITAGE',
                :PAR_TPL_IDENTIFIANT,
                " . (($this->getField('PRT_CODE') == 'PRT_HAUTDEPAGE') ? 9999 : 0). ",
                :PAR_COLONNE,
                :PRS_CODE,
                :PRS_WIDTH
                )");
            $stmt->bindValue(':PRT_CODE', $this->getField('PRT_CODE'), PDO::PARAM_STR);
            $stmt->bindValue(':PAR_TPL_IDENTIFIANT', $this->getID(), PDO::PARAM_INT);
            $stmt->bindValue(':PAR_COLONNE', $this->getField('PAR_COLONNE'), PDO::PARAM_STR);
            //Dans le cas d'un héritage avec style, on affecte les styles existants sinon non
            $stmt->bindValue(':PRS_CODE', ($this->getField('PAR_HERITABLE')==2?(($this->getField('PRS_CODE') != '')?$this->getField('PRS_CODE'):null):null), PDO::PARAM_STR);
            $stmt->bindValue(':PRS_WIDTH', ($this->getField('PAR_HERITABLE')==2?(($this->getField('PRS_WIDTH') != '')?$this->getField('PRS_WIDTH'):null):null), PDO::PARAM_STR);

            foreach ($this->getPage()->getChildrenID($filtre) as $idtf) {
                if ($this->mode == 'ON_' && !is_numeric($aIDbis[$idtf])) {
                    //il n'y a plus de correspondance en OFF (page déplacée) => on n'hérite pas
                    continue;
                }
                $stmt->bindValue(':ID_PARAGRAPHE', ($this->mode == 'ON_') ? $aIDbis[$idtf] : null, PDO::PARAM_INT);
                $oPageChild = new Page($idtf, $this->mode);
                $stmt->bindValue(':ID_PAGE', $idtf, PDO::PARAM_INT);
                $stmt->execute();
                if ($this->mode == 'OFF_') {
                    $details = gettext('heritage_du_paragraphe_X_de_la_page_Y');
                    $histoDetail = sprintf($details, $this->getField('ID_PARAGRAPHE'), $idtf);
                    $sPAR_TITRE = $this->getField('PAR_TITRE');
                    $histoDetail .= !empty($sPAR_TITRE)?' ('. $sPAR_TITRE.')':'';
                    $oPageChild->historize('CREATION', 'PARAGRAPHE', $histoDetail, $this->dbh->lastInsertId());
                }
                $oPageChild->reorderParagraphes($this->getField('PAR_COLONNE'));
            }

            //Dans le cas d'un héritage avec styles, il faut mettre à jour le style du paragraphe héritable sur les paragraphes hérités
            if ($this->getField('PAR_HERITABLE')==2) {
                $sql = "update " . $this->mode . "PARAGRAPHE set
                    PRS_CODE=".(($this->getField('PRS_CODE')!='')?$this->dbh->quote($this->getField('PRS_CODE')):'null').",
                    PRS_WIDTH=".$this->dbh->quote($this->getField('PRS_WIDTH'))."
                    where TPL_CODE='TPL_HERITAGE' and PAR_TPL_IDENTIFIANT=" . $this->getID();
                $this->dbh->exec($sql);
            }
        }
    }

    /**
     * Retourne la page déjà chargée (load) si elle existe ou faux sinon
     * @return mixed un objet Page ou false
     */
    public function getPage()
    {
        if (is_null($this->_oPage)) {
            $oPageTemp = new Page($this->getField('ID_PAGE'), $this->mode);
            $this->_oPage = ($oPageTemp->exist()) ? $oPageTemp : false;
        }

        return $this->_oPage;
    }

    private function _getTemplateLibelle()
    {
        if ($this->getField('TPL_CODE') == '' ) {
            return '';
        }
        $sql = "select TPL_LIBELLE, MOD_LIBELLE from DD_TEMPLATE
            inner join DD_MODULE using (MOD_CODE)
            where TPL_CODE=" . $this->dbh->quote($this->getField('TPL_CODE'));
        $row = $this->dbh->query($sql)->fetch(PDO::FETCH_ASSOC);

        return extraireLibelle($row['MOD_LIBELLE']) . ' : ' . extraireLibelle($row['TPL_LIBELLE']);
    }

    private function _getEditButtons()
    {
        if (!CMS::$edition || $this->mode!='OFF_') {
            return '';
        }
        $retour = '<div id="dragBar_' . $this->getID() . '" class="'.($this->getField('PAR_BROUILLON')!=''?'brouillon ':'').'pseudo_boutons_edition">';
        if ($this->getField('TPL_CODE')!='') {
            $retour .= '<em>'.$this->_getTemplateLibelle().'</em>';
        }

        $retour .= '<span class="boutons">';
        $sql = "select min(PAR_POIDS), max(PAR_POIDS) from OFF_PARAGRAPHE where ID_PAGE=" . $this->getField('ID_PAGE') . " and PAR_COLONNE='" . $this->getField('PAR_COLONNE') . "'";
        $rowTemp = $this->dbh->query($sql)->fetch(PDO::FETCH_NUM);
        $minPoids = $rowTemp[0];
        $maxPoids = $rowTemp[1];
        // Le span[class=moveBar] est utilisé dans "yui.cms.DragDrop.js" pour mettre à jour les boutons de déplacements après un drag & drop
        // !!! Les chemin des images est également utilisés dans ce fichier js ==> En cas de modif faire la répercution
        $retour .= '<span class="moveBar">';
        if ($this->getField('PAR_POIDS') > $minPoids) {
            $retour .= '<a href="PRT/PRT_Submit.php?Up=' . $this->getID() . '" title="' . gettext('Monter') . '"><img src="../images/pseudo_upParagraphe.gif" alt="' . gettext('Monter') . '"></a>';
        }
        if ($this->getField('PAR_POIDS') < $maxPoids) {
            $retour .= '<a href="PRT/PRT_Submit.php?Down=' . $this->getID() . '" title="' . gettext('Descendre') . '"><img src="../images/pseudo_downParagraphe.gif" alt="' . gettext('Descendre') . '"></a>';
        }
        $oPage = $this->getPage();
        if (($this->getField('PAR_COLONNE') == 'PAR_RIGHT') && $oPage->hasLeftColumn()) {
            $retour .= '<a href="PRT/PRT_Submit.php?Left=' . $this->getID() . '" title="' . gettext('Deplacer a gauche') . '"><img src="../images/pseudo_leftParagraphe.gif" alt="' . gettext('Deplacer a gauche') . '"></a>';
        } elseif (($this->getField('PAR_COLONNE') == 'PAR_LEFT') && $oPage->hasRightColumn()) {
            $retour .= '<a href="PRT/PRT_Submit.php?Right=' . $this->getID() . '" title="' . gettext('Deplacer a droite') . '"><img src="../images/pseudo_rightParagraphe.gif" alt="' . gettext('Deplacer a droite') . '"></a>';
        }
        $retour .= '</span>';
        if ($_idtf = $this->isInherited()) {
            $_oPara = new Paragraphe($_idtf);
            $retour .= '<a href="cms_pseudo.php?idtf=' . $_oPara->getPage()->getID() . '&amp;PFM=1#par' . $_idtf . '"><img src="../images/pseudo_PRT_HERITAGE.png" alt=""></a>';
        } else {
            if ($this->isEditable()) {
                $retour .= '<a href="PRT/' . $this->getField('PRT_CODE') . '.php?idtf=' . $this->getID() . '" title="' . gettext('Modifier') . '"><img src="../images/pseudo_' . $this->getField('PRT_CODE') . '.png" alt="' . gettext('Modifier') . '"></a>';
            }
            $retour .= '<a href="#" onclick="if(confirm(\'' . gettext('Etes-vous sur ?') . '\')) window.location.href=\'PRT/PRT_Submit.php?Delete=' . $this->getID() . '\'; return false" title="' . gettext('Supprimer') . '"><img src="../images/pseudo_' . $this->getField('PRT_CODE') . '_del.png" alt="' . gettext('Supprimer') . '"></a>';
        }
        $retour .= '</span>';

        $sql = "select * from DD_PARAGRAPHESTYLE where (PRT_CODE_ARRAY = '' or PRT_CODE_ARRAY like '%@" . $this->getField('PRT_CODE') . "@%')
            and (PRS_COLONNE = '' or PRS_COLONNE like '%@" . $this->getField('PAR_COLONNE') . "@%')
            and GAB_CODE=" . $this->dbh->quote(CMS::getCurrentSite()->getField('GAB_CODE')) . "
            order by PRS_LIBELLE";
        $_aPRS_CODE = $this->dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        if (sizeof($_aPRS_CODE) > 0) {
            $PRS_WIDTH = '';
            $retour .= '<select name="PRS_CODE" onchange="window.location.href=\'PRT/PRT_Submit.php?idtf=' . $this->getID() . '&amp;PRS_CODE=\'+this.value" title="Style">';
            $retour .= '<option value="">--STYLE--</option>';
            foreach ($_aPRS_CODE as $rowTemp) {
                if ($this->getField('PRS_CODE') == $rowTemp['PRS_CODE']) {
                    $selected = ' selected';
                    if ($rowTemp['PRS_WIDTH'] != '') {
                        $PRS_WIDTH = $rowTemp['PRS_WIDTH'];
                    }
                } else {
                    $selected = '';
                }
                $retour .= '<option value="' . $rowTemp['PRS_CODE'] . '"' . $selected . '>' . $rowTemp['PRS_LIBELLE'] . '</option>';
            }
            $retour .= '</select>';
            if ($PRS_WIDTH != '' && ($this->getField('PAR_COLONNE') == 'PAR_CENTRAL')) {
                $retour .= '<select name="PRS_WIDTH" onchange="window.location.href=\'PRT/PRT_Submit.php?idtf=' . $this->getID() . '&amp;PRS_WIDTH=\'+this.value" title="Style">';
                $retour .= '<option value="">--' . gettext('LARGEUR') . '--</option>';
                foreach (explode('@', substr($PRS_WIDTH, 1, -1)) as $val) {
                    list ($key, $val) = explode(':', $val);
                    $key = 'PRS_WIDTH_' . $key;
                    $selected = ($this->getField('PRS_WIDTH') == $key) ? ' selected' : '';
                    $retour .= '<option value="' . $key . '"' . $selected . '>' . $val . '</option>';
                }
                $retour .= '</select>';
            }
        }

        return $retour . '</div>';
    }

    /**
     * Retourne le contenu du paragraphe
     * @param $withEnclosure si faux seul le contenu du paragraphe est restitué (ou simple inclusion du tpl dans le cas d'un TPL_CODE)
     */
    public function display($withEnclosure = true)
    {
        $retour = '';
        if ($withEnclosure) {
            //la div englobante
            $aClass = array('paragraphe');
            $aClass[] = strtolower(str_replace('PRT_', '', $this->getField('PRT_CODE')));
            if ($this->getField('PAR_MOBILEHIDDEN')) {
                $aClass[] = 'mobile_hidden';
            }
            if (CMS::$edition) {
                $retour .= '<div id="editPar' . $this->getID() . '" class="edition';
                if ($this->getField('PRS_WIDTH') != '') {
                    $retour .=  ' ' . $this->getField('PRS_WIDTH');
                }
                $retour .= '"><div class="editionInner">';
                //la barre des boutons d'édition
                $retour .= $this->_getEditButtons();
            } elseif (CMS::isMobile() && $this->getField('PAR_MOBILEHIDDEN') && CMS::getCurrentSite()->getField('SIT_TXTMOBILEHIDDEN') != '') {
                if ($this->getPage()->isHome()) {
                    return '';
                }
                return '<p class="txt_mobile_hidden">' . encode(CMS::getCurrentSite()->getField('SIT_TXTMOBILEHIDDEN'), false) . '</p>';
            }
            if ($this->getField('PRS_CODE') != '') {
                $aClass[] = $this->getField('PRS_CODE');
            }
            if (!CMS::$edition && $this->getField('PRS_WIDTH') != '') {
                $aClass[] = $this->getField('PRS_WIDTH');
            }
            if ($this->getField('TPL_CODE') != '') {
                $aClass[] = $this->getField('TPL_CODE');
                if ($this->getField('TPL_CODE') == 'TPL_HERITAGE') {
                    $_oParagraphe = new Paragraphe($this->getField('PAR_TPL_IDENTIFIANT'), CMS::$mode);
                    if ($_oParagraphe->exist() && $_oParagraphe->getField('TPL_CODE') != '') {
                        $aClass[] = $_oParagraphe->getField('TPL_CODE');
                    }
                }
            }
            $retour .= '<div id="par' . $this->getID() . '" class="' . implode(' ', $aClass) . '">';

            //la div intérieure
            $retour .= '<div class="innerParagraphe">';
        }

        //le titre
        if ($this->getField('PAR_TITRE') != '') {
            $retour .= '<h2>' . encode($this->getField('PAR_TITRE')) . '</h2>';
        }

        //le contenu
        $typeRedactionel = false;
        if ($this->getField('TPL_CODE') != '') {
            if($this->getField('TPL_CODE') == 'TPL_HERITAGE' || $this->getField('TPL_CODE') == 'TPL_PARTAGE') $typeRedactionel = true;
            // TEMPLATE //
            require_once CLASS_DIR . 'class.db_template.php';
            $oTemplate = new Template($this->getField('TPL_CODE'));
            if ($oTemplate->isEnabled(CMS::getCurrentSite())) {
                if ($this->_templateRestriction == null) {
                    self::setCurrentTemplateRestriction($this->getField('PAR_TPL_IDENTIFIANT'));
                } else {
                    self::setCurrentTemplateRestriction($this->_templateRestriction);
                }
                self::setCurrentTemplate($oTemplate);
                echo $retour;
                self::$_render = true;

                /* #9650: Gestion des modules et des templates par gabarit */
                $gabCodeSite = CMS::getCurrentSite()->getField('GAB_CODE');
                if (file_exists(PHYSICAL_PATH . '/include/tpl/' . $gabCodeSite . '/' . $oTemplate->getField('TPL_PAGE'))) {
                    include (PHYSICAL_PATH . '/include/tpl/' . $gabCodeSite . '/' . $oTemplate->getField('TPL_PAGE'));
                } else {
                    include (PHYSICAL_PATH . '/include/tpl/' . $oTemplate->getField('TPL_PAGE'));
                }

                if (!self::$_render && !CMS::$edition) {
                    //rien à afficher
                    if ($retour != '') {
                        $ob = ob_get_contents();
                        ob_clean();
                        echo mb_substr($ob, 0, mb_strrpos($ob, $retour));
                    }

                    return '';
                }
                $retour = '';
            } elseif (CMS::$mode == 'OFF_' && CMS::$edition) {
                $oModTemp = $oTemplate->getModule();
                $retour .= encode(sprintf(gettext('Module %s ou template %s non disponible'), extraireLibelle($oModTemp->getField('MOD_LIBELLE')), extraireLibelle($oTemplate->getField('TPL_LIBELLE'))));
            } else {
                return ''; // on affiche rien dans le paragraphe
            }
        } else {
            // REDACTIONNEL //
            $typeRedactionel = true;
            $retour .= ($this->getField('PAR_APARSER'))
                ? $this->parse($this->getField('PAR_CONTENU'))
                : $this->getField('PAR_CONTENUPARSE');
        }
        if ($withEnclosure) {
            //la fin des divs
            $retour .= '</div>';//innerParagraphe
            if ($typeRedactionel && CMS::getCurrentSite()->hasModule(new Module('MOD_ENSAVOIRPLUS')) && file_exists(PHYSICAL_PATH . 'include/gabarit_site/'.CMS::getCurrentSite()->getField('GAB_CODE').'/inc.enSavoirPlus.php')) {
                include PHYSICAL_PATH . 'include/gabarit_site/' . CMS::getCurrentSite()->getField('GAB_CODE') . '/inc.enSavoirPlus.php';
            }
            $retour .= '</div>';//paragraphe
            if (CMS::$edition) {
                $retour .= '</div>';//editionInner
                //la barre de boutons d'insertion
                $retour .= $this->getPage()->getParagrapheButtons($this);
                $retour .= '</div>';//edition
            }
        }

        return $retour;
    }

    public function up()
    {
        if (!$this->exist()) {
            return;
        }
        // augmenter le poids précédant
        $stmt = $this->dbh->prepare("select ID_PARAGRAPHE from OFF_PARAGRAPHE
            where PAR_POIDS<:PAR_POIDS and ID_PAGE=:ID_PAGE and PAR_COLONNE=:PAR_COLONNE order by PAR_POIDS desc");
        $stmt->bindValue(':PAR_POIDS', $this->getField('PAR_POIDS'), PDO::PARAM_INT);
        $stmt->bindValue(':ID_PAGE', $this->getField('ID_PAGE'), PDO::PARAM_INT);
        $stmt->bindValue(':PAR_COLONNE', $this->getField('PAR_COLONNE'), PDO::PARAM_STR);
        $stmt->execute();
        $sql = "update OFF_PARAGRAPHE set PAR_POIDS=PAR_POIDS+1 where ID_PARAGRAPHE=" . intval($stmt->fetchColumn());
        $stmt->closeCursor();
        $this->dbh->exec($sql);

        // diminuer mon poids
        $sql = "update OFF_PARAGRAPHE set PAR_POIDS=PAR_POIDS-1 where ID_PARAGRAPHE=" . $this->getID();
        $this->dbh->exec($sql);
    }

    public function down()
    {
        if (!$this->exist()) {
            return;
        }
        // diminuer le poids suivant
        $stmt = $this->dbh->prepare("select ID_PARAGRAPHE from OFF_PARAGRAPHE
            where PAR_POIDS>:PAR_POIDS and ID_PAGE=:ID_PAGE and PAR_COLONNE=:PAR_COLONNE order by PAR_POIDS");
        $stmt->bindValue(':PAR_POIDS', $this->getField('PAR_POIDS'), PDO::PARAM_INT);
        $stmt->bindValue(':ID_PAGE', $this->getField('ID_PAGE'), PDO::PARAM_INT);
        $stmt->bindValue(':PAR_COLONNE', $this->getField('PAR_COLONNE'), PDO::PARAM_STR);
        $stmt->execute();
        $sql = "update OFF_PARAGRAPHE set PAR_POIDS=PAR_POIDS-1 where ID_PARAGRAPHE=" . intval($stmt->fetchColumn());
        $stmt->closeCursor();
        $this->dbh->exec($sql);

        // augmenter mon poids
        $sql = "update OFF_PARAGRAPHE set PAR_POIDS=PAR_POIDS+1 where ID_PARAGRAPHE=" . $this->getID();
        $this->dbh->exec($sql);
    }

    public function twist($PAR_COLONNE)
    {
        $oPage = $this->getPage();

        // on positionne le module en dernière position
        $stmt = $this->dbh->prepare("select max(PAR_POIDS)+1 from OFF_PARAGRAPHE where PAR_COLONNE=:PAR_COLONNE and ID_PAGE=:ID_PAGE");
        $stmt->bindValue(':ID_PAGE', $oPage->getID(), PDO::PARAM_INT);
        $stmt->bindValue(':PAR_COLONNE', $PAR_COLONNE, PDO::PARAM_STR);
        $stmt->execute();
        $PAR_POID = intval($stmt->fetchColumn());
        $stmt->closeCursor();
        if ($PAR_POID == 0) {
            $PAR_POID = 1;
        }
        $stmt = $this->dbh->prepare("update OFF_PARAGRAPHE set PAR_COLONNE=:PAR_COLONNE, PAR_POIDS=:PAR_POIDS where ID_PARAGRAPHE=:ID_PARAGRAPHE");
        $stmt->bindValue(':PAR_COLONNE', $PAR_COLONNE, PDO::PARAM_STR);
        $stmt->bindValue(':PAR_POIDS', $PAR_POID, PDO::PARAM_INT);
        $stmt->bindValue(':ID_PARAGRAPHE', $this->getID(), PDO::PARAM_INT);
        $stmt->execute();

        // on recalcule les poids des colonnes
        $oPage->reorderParagraphes('PAR_LEFT');
        $oPage->reorderParagraphes('PAR_RIGHT');
    }
}

class Paragraphe_APPLIEXTERNE extends Paragraphe
{
    public function __construct($idtf, $mode = 'OFF_', $oPage = null)
    {
        parent::__construct($idtf, $mode, $oPage);
        $this->code = 'PRT_APPLIEXTERNE';
    }
}

class Paragraphe_FORMULAIRE extends Paragraphe
{
    public function __construct($idtf, $mode = 'OFF_', $oPage = null)
    {
        parent::__construct($idtf, $mode, $oPage);
        $this->code = 'PRT_FORMULAIRE';
    }
}

class Paragraphe_HAUTDEPAGE extends Paragraphe
{
    public function __construct($idtf, $mode = 'OFF_', $oPage = null)
    {
        parent::__construct($idtf, $mode, $oPage);
        $this->code = 'PRT_HAUTDEPAGE';
    }

    protected function _parse($PAR_CONTENU)
    {
        return '<p class="hautDePage"><a href="#document">' . $PAR_CONTENU . '</a></p>';
    }
}

class Paragraphe_PARTAGE extends Paragraphe
{
    public function __construct($idtf, $mode = 'OFF_', $oPage = null)
    {
        parent::__construct($idtf, $mode, $oPage);
        $this->code = 'PRT_PARTAGE';
    }
}

class Paragraphe_SOMMAIRE extends Paragraphe
{
    public function __construct($idtf, $mode = 'OFF_', $oPage = null)
    {
        parent::__construct($idtf, $mode, $oPage);
        $this->code = 'PRT_SOMMAIRE';
    }
}

class Paragraphe_TPL extends Paragraphe
{
    public function __construct($idtf, $mode = 'OFF_', $oPage = null)
    {
        parent::__construct($idtf, $mode, $oPage);
        $this->code = 'PRT_TPL';
    }
}

class Paragraphe_TXT extends Paragraphe
{
    public function __construct($idtf, $mode = 'OFF_', $oPage = null)
    {
        parent::__construct($idtf, $mode, $oPage);
        $this->code = 'PRT_TXT';
    }

    protected function _parse(& $PAR_CONTENU)
    {
        require_once (CLASS_DIR . 'class.Link.php');
        require_once (CLASS_DIR . 'class.Editor.php');
        Link::delete($this->mode . 'PARAGRAPHE', $this->getID());

        return Editor::parse($PAR_CONTENU, $this->getPage(), $this->mode . 'PARAGRAPHE', $this->getID());
    }

    public static function createTempParagraphe($ID_PAGE,$PAR_COLONNE,$PAR_POIDS)
    {
        $dbh = DB::getInstance();
        $stmt = $dbh->prepare("insert into OFF_PARAGRAPHE (ID_PAGE,PRT_CODE,PAR_COLONNE,PAR_POIDS) values (:ID_PAGE,'PRT_TXT',:PAR_COLONNE,:PAR_POIDS)");
        $stmt->bindValue(':ID_PAGE',$ID_PAGE,PDO::PARAM_INT);
        $stmt->bindValue(':PAR_COLONNE',$PAR_COLONNE,PDO::PARAM_STR);
        $stmt->bindValue(':PAR_POIDS',$PAR_POIDS,PDO::PARAM_INT);
        $stmt->execute();

        return new Paragraphe_TXT($dbh->lastInsertId());
    }
}

class Paragraphe_WIDGET extends Paragraphe
{
    public function __construct($idtf, $mode = 'OFF_', $oPage = null)
    {
        parent::__construct($idtf, $mode, $oPage);
        $this->code = 'PRT_WIDGET';
    }

    /**
     * $PAR_CONTENU contient l'identifiant du widget
     */
    protected function _parse($PAR_CONTENU)
    {
        include_once CLASS_DIR . 'class.db_webotheque.php';
        include_once CLASS_DIR . 'class.Link.php';
        Link::delete($this->mode . 'PARAGRAPHE', $this->getID());
        $oWebo = new Webo_WIDGET($PAR_CONTENU);
        if ($oWebo->checkAuthorized(false) || $oWebo->checkShareAuthorized(false)) {
            Link::insertWebotheque($this->mode . 'PARAGRAPHE', $this->getID(), $PAR_CONTENU);

            return $oWebo->getField('WEB_DESCRIPTIONACC');
        }

        return '';
    }
}

class Paragraphe_Revision extends Paragraphe
{
    /**
     * @var string
     */
    protected $code = null;

    /**
     * @var Page
     */
    protected $_oPage = null;

    /**
     * @var bool
     */
    protected $_editable = false;

    /**
     * @var Paragraphe
     */
    protected $_oParagrapheRef = null;

    /**
     * @var Revision
     */
    protected $_oRevision = null;

    /**
     * Constructeur
     *
     * @param int      $idtf
     * @param Revision $oRevision
     * @param string   $mode
     */
    public function __construct($idtf, Revision $oRevision, $mode = 'OFF_')
    {
        parent::__construct($idtf, $mode);
        $this->_oParagrapheRef = new Paragraphe($idtf, 'OFF_');
        $this->_oRevision      = $oRevision;
    }

    public function load()
    {
        $sql = "select *
                from REVISION_PARAGRAPHE
                where ID_PARAGRAPHE=" . $this->getID() ."
                and ID_REVISION=" . $this->_oRevision->getID();
        if ($this->code != null) {
            $sql .= " and PRT_CODE=" . $this->dbh->quote($this->code);
        }
        if ($row = $this->dbh->query($sql)->fetch(PDO::FETCH_ASSOC)) {
            $this->setFields($row);
        } else {
            $this->_idtf = -1;
            $this->setFields(array ());
        }
    }

    public function delete()
    {
        if (!$this->isDeletable()) {
            return false;
        }
        $aSql = array
        (
            "delete from LIAISON_WEBOTHEQUE
            where LIA_CODE='REVISION_PARAGRAPHE'
            and ID_LIAISON=" . $this->getField('ID_REVISIONPARAGRAPHE') . "
            and ID_REVISION = " .$this->_oRevision->getID(),

            "delete from LIAISON_PAGE
            where LIA_CODE='REVISION_PARAGRAPHE'
            and ID_LIAISON=" . $this->getField('ID_REVISIONPARAGRAPHE') . "
            and ID_REVISION = " .$this->_oRevision->getID(),

            "delete from REVISION_PARAGRAPHE
            where ID_PARAGRAPHE=" . $this->getID() . "
            and ID_REVISION = " .$this->_oRevision->getID()
        );
        foreach ($aSql as $sql) {
            $this->dbh->exec($sql);
        }
    }

    public function isDeletable()
    {
        return true;
    }

    public function isEditable()
    {
        return false;
    }

    /**
     * Retourne la page déjà chargée (load) si elle existe ou faux sinon
     *
     * @return Page
     */
    public function getPage()
    {
        if ($this->_oPage == null) {
            $oPageTemp = new Page_Revision($this->_oRevision, $this->mode);
            $this->_oPage = ($oPageTemp->exist()) ? $oPageTemp : false;
        }

        return $this->_oPage;
    }

    /**
     *
     * @param $PAR_CONTENU Le contenu à parser
     * @return string le contenu parsé
     */
    public function parse($PAR_CONTENU)
    {
        $PAR_CONTENUPARSE = $this->_parse($PAR_CONTENU);

        // on retraite la chaine pour enlever les éléments de type "block" [et quelques "inline"] de façon à laisser tout de même un espace et améliorer la recherche
        $PAR_CONTENUTEXTE = html2Text($PAR_CONTENUPARSE);

        $stmt = $this->dbh->prepare("
            update REVISION_PARAGRAPHE set
                PAR_CONTENU=:PAR_CONTENU,
                PAR_CONTENUPARSE=:PAR_CONTENUPARSE,
                PAR_CONTENUTEXTE=:PAR_CONTENUTEXTE,
                PAR_APARSER=1
            where ID_PARAGRAPHE=:ID_PARAGRAPHE
            and ID_REVISION=:ID_REVISION");
        $stmt->bindValue(':PAR_CONTENU',      $PAR_CONTENU,               PDO :: PARAM_STR);
        $stmt->bindValue(':PAR_CONTENUPARSE', $PAR_CONTENUPARSE,          PDO :: PARAM_STR);
        $stmt->bindValue(':PAR_CONTENUTEXTE', $PAR_CONTENUTEXTE,          PDO :: PARAM_STR);
        $stmt->bindValue(':ID_PARAGRAPHE',    $this->getID(),             PDO :: PARAM_INT);
        $stmt->bindValue(':ID_REVISION',      $this->_oRevision->getID(), PDO :: PARAM_INT);
        $stmt->execute();

        return $PAR_CONTENUPARSE;

    }
}

class Paragraphe_Revision_FORMULAIRE extends Paragraphe_Revision
{
    public function __construct($idtf, $mode = 'OFF_')
    {
        parent::__construct($idtf, $mode);
        $this->code = 'PRT_FORMULAIRE';
    }
}

class Paragraphe_Revision_HAUTDEPAGE extends Paragraphe_Revision
{
    public function __construct($idtf, $mode = 'OFF_')
    {
        parent::__construct($idtf, $mode);
        $this->code = 'PRT_HAUTDEPAGE';
    }
}

class Paragraphe_Revision_PARTAGE extends Paragraphe_Revision
{
    public function __construct($idtf, $mode = 'OFF_')
    {
        parent::__construct($idtf, $mode);
        $this->code = 'PRT_PARTAGE';
    }
}

class Paragraphe_Revision_SOMMAIRE extends Paragraphe_Revision
{
    public function __construct($idtf, $mode = 'OFF_')
    {
        parent::__construct($idtf, $mode);
        $this->code = 'PRT_SOMMAIRE';
    }
}

class Paragraphe_Revision_TPL extends Paragraphe_Revision
{
    public function __construct($idtf, $mode = 'OFF_')
    {
        parent::__construct($idtf, $mode);
        $this->code = 'PRT_TPL';
    }
}

class Paragraphe_Revision_TXT extends Paragraphe_Revision
{
    public function __construct($idtf, $mode = 'OFF_')
    {
        parent::__construct($idtf, $mode);
        $this->code = 'PRT_TXT';
    }

    protected function _parse(& $PAR_CONTENU)
    {
        require_once (CLASS_DIR . 'class.Link.php');
        require_once (CLASS_DIR . 'class.Editor.php');
        Link::delete('REVISION_PARAGRAPHE', $this->getField('ID_REVISIONPARAGRAPHE'));

        return Editor::parse($PAR_CONTENU, $this->getPage(), 'REVISION_PARAGRAPHE', $this->getField('ID_REVISIONPARAGRAPHE'), $this->_oRevision->getID());
    }
}

class Paragraphe_Revision_WIDGET extends Paragraphe_Revision
{
    public function __construct($idtf, $mode = 'OFF_')
    {
        parent::__construct($idtf, $mode);
        $this->code = 'PRT_WIDGET';
    }
}

class Paragraphe_Revision_APPLIEXTERNE extends Paragraphe_Revision
{
    public function __construct($idtf, $mode = 'OFF_')
    {
        parent::__construct($idtf, $mode);
        $this->code = 'PRT_APPLIEXTERNE';
    }
}
