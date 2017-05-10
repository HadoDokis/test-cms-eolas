<?php
require_once CLASS_DIR . 'class.db_generic.php';
require_once CLASS_DIR . 'class.db_page.php';

class Revision extends Generic
{
    /**
     * @var boolean
     */
    public static $display = false;

    /**
     * @var Revision
     */
    private static $_oInstance = null;

    /**
     * @var Page_Revision
     */
    private $_oPage = null;

    public function __construct($idtf)
    {
        parent::__construct($idtf);
        self::$_oInstance = $this;
        $this->loadPage();
    }

    public static function getModuleCode()
    {
        return 'MOD_CORE';
    }

    /**
     * Charge le tableau $_fields avec les valeurs de la table
     *
     * @return void
     */
    public function load()
    {
        $sql = "select * from REVISION where ID_REVISION=" . $this->getID();
        if (($this->getID()>0) && ($row = $this->dbh->query($sql)->fetch(PDO::FETCH_ASSOC))) {
            $this->setFields($row);
        } else {
            $this->_idtf = -1;
            $this->setFields(array ());
        }
    }

    public function checkAuthorized($strict = true)
    {

        if ($this->exist() && intval($this->getField('ID_PAGE')) > 0) {
            $oPageCA = new Page(intval($this->getField('ID_PAGE')), 'OFF_');

            return $oPageCA->checkAuthorized($strict);
        }

        if ($strict) {
            header('Location:' . SERVER_ROOT . 'cms/index.php?logout=1');
            exit ();
        }

        return false;
    }

    /**
     * Supprime une révision
     *
     * @return boolean
     */
    public function delete()
    {
        if (!$this->isDeletable()) {
            return false;
        }
        $this->_oPage->setDateModification();
        $this->_oPage->historize('SUPPRESSION', 'REVISION', 'Suppression de la révision du '.date('d/m/Y H:i', $this->getField('REV_DATECREATION')), null, $this->getID());

        $this->_oPage->delete();
        $aSql = array("delete from REVISION_GROUPE where ID_REVISION=" . $this->getID(),
                      "delete from REVISION_ROLE   where ID_REVISION=" . $this->getID(),
                      "delete from REVISION        where ID_REVISION=" . $this->getID());
        foreach($aSql as $sql) $this->dbh->exec($sql);

        return true;
    }

    public function isDeletable()
    {
        return $this->exist();
    }

    public function loadPage()
    {
        if ($this->_oPage === null && $this->exist()) {
            $this->_oPage = new Page_Revision($this);
        }
    }

    /**
     *
     * @return Page_Revision
     */
    public function getPage()
    {
        $this->loadPage();

        return $this->_oPage;
    }

    /**
     * Retour à cette révision
     *
     * @return boolean True = succès, False = echec
     */
    public function revert()
    {
        if (!$this->exist()) {
            return false;
        }

        // Display mode
        Revision::$display = true;

        require_once CLASS_DIR . 'class.Link.php';

        // Vérification des droits sur la page
        $oPageOff = new Page($this->getField('ID_PAGE'), 'OFF_');
        $oPageOff->checkAuthorized();
        $oPageOff->lock();

        // Création d'une révision de la page OFF Actuel - pas de revision de la page actuel creer , voir ticket #10336
        /*$oPageOff = new Page($this->getField('ID_PAGE'), 'OFF_');
        try {
            $oPageOff->createRevision();
        } catch (Exception $e) {
            die($e->getTraceAsString());
        }*/
        unset($oPageOff);
        $this->_oPage->load();

        // Fusion des données de la page OFF et de la Révision
        $stmt = $this->dbh->prepare("update OFF_PAGE set
                ID_PAGE_REDIRECT=:ID_PAGE_REDIRECT,
                ID_WEBOTHEQUE_IMAGE=:ID_WEBOTHEQUE_IMAGE,
                ID_WEBOTHEQUE_LIENEXTERNE=:ID_WEBOTHEQUE_LIENEXTERNE,
                PGS_CODE=:PGS_CODE,
                PSS_CODE=:PSS_CODE,
                ID_STYLEDYNAMIQUE=:ID_STYLEDYNAMIQUE,
                PAG_TITRE=:PAG_TITRE,
                PAG_TITRE_MENU=:PAG_TITRE_MENU,
                PAG_TITRE_REFERENCEMENT=:PAG_TITRE_REFERENCEMENT,
                PAG_TITLE=:PAG_TITLE,
                PAG_ACCROCHE=:PAG_ACCROCHE,
                PAG_METADESCRIPTION=:PAG_METADESCRIPTION,
                PAG_VISIBLE_MENU=:PAG_VISIBLE_MENU,
                PAG_MOTCLE1=:PAG_MOTCLE1,
                PAG_MOTCLE2=:PAG_MOTCLE2,
                PAG_MOTCLE3=:PAG_MOTCLE3,
                PAG_MOTCLE4=:PAG_MOTCLE4,
                PAG_MOTCLE5=:PAG_MOTCLE5,
                PAG_DATEMISEAJOUR=:PAG_DATEMISEAJOUR,
                PAG_URLREWRITING=:PAG_URLREWRITING,
                PAG_GOOGLEFREQUENCE=:PAG_GOOGLEFREQUENCE,
                PAG_GOOGLEPRIORITE=:PAG_GOOGLEPRIORITE,
                PAG_EXCLURECHERCHE=:PAG_EXCLURECHERCHE,
                PAG_MASQUERGAUCHE=:PAG_MASQUERGAUCHE,
                PAG_MASQUERDROITE=:PAG_MASQUERDROITE,
                PAG_COMMENTAIREACTIF=:PAG_COMMENTAIREACTIF,
                PAG_HTTPS=:PAG_HTTPS,
                PAG_CACHE=:PAG_CACHE
                where ID_PAGE=:ID_PAGE");

        $ID_PAGE_REDIRECT = $this->_oPage->getInsertField('ID_PAGE_REDIRECT');
        $ID_PAGE_REDIRECT = !empty($ID_PAGE_REDIRECT)? $ID_PAGE_REDIRECT : null;
        $ID_WEBOTHEQUE_IMAGE = $this->_oPage->getInsertField('ID_WEBOTHEQUE_IMAGE');
        $ID_WEBOTHEQUE_IMAGE = !empty($ID_WEBOTHEQUE_IMAGE)? $ID_WEBOTHEQUE_IMAGE : null;
        $ID_WEBOTHEQUE_LIENEXTERNE = $this->_oPage->getInsertField('ID_WEBOTHEQUE_LIENEXTERNE');
        $ID_WEBOTHEQUE_LIENEXTERNE = !empty($ID_WEBOTHEQUE_LIENEXTERNE)? $ID_WEBOTHEQUE_LIENEXTERNE : null;
        $PGS_CODE = $this->_oPage->getInsertField('PGS_CODE');
        $PGS_CODE = !empty($PGS_CODE)? $PGS_CODE : null;
        $PSS_CODE = $this->_oPage->getInsertField('PSS_CODE');
        $PSS_CODE = !empty($PSS_CODE)? $PSS_CODE : null;
        $ID_STYLEDYNAMIQUE = $this->_oPage->getInsertField('ID_STYLEDYNAMIQUE');
        $ID_STYLEDYNAMIQUE = is_numeric($ID_STYLEDYNAMIQUE) ? $ID_STYLEDYNAMIQUE : null;
        $PAG_DATEMISEAJOUR = $this->_oPage->getInsertField('PAG_DATEMISEAJOUR');
        $PAG_DATEMISEAJOUR = !empty($PAG_DATEMISEAJOUR)? $PAG_DATEMISEAJOUR : null;

        $stmt->bindValue(':ID_PAGE_REDIRECT',          $ID_PAGE_REDIRECT                                         , PDO::PARAM_INT);
        $stmt->bindValue(':ID_WEBOTHEQUE_IMAGE',       $ID_WEBOTHEQUE_IMAGE                                      , PDO::PARAM_INT);
        $stmt->bindValue(':ID_WEBOTHEQUE_LIENEXTERNE', $ID_WEBOTHEQUE_LIENEXTERNE                                , PDO::PARAM_INT);
        $stmt->bindValue(':PGS_CODE',                  $PGS_CODE                                                 , PDO::PARAM_STR);
        $stmt->bindValue(':PSS_CODE',                  $PSS_CODE                                                 , PDO::PARAM_STR);
        $stmt->bindValue(':ID_STYLEDYNAMIQUE',         $ID_STYLEDYNAMIQUE                                        , PDO::PARAM_INT);
        $stmt->bindValue(':PAG_TITRE',                 $this->_oPage->getInsertField('PAG_TITRE'                ), PDO::PARAM_STR);
        $stmt->bindValue(':PAG_TITRE_MENU',            $this->_oPage->getInsertField('PAG_TITRE_MENU'           ), PDO::PARAM_STR);
        $stmt->bindValue(':PAG_TITRE_REFERENCEMENT',   $this->_oPage->getInsertField('PAG_TITRE_REFERENCEMENT'  ), PDO::PARAM_STR);
        $stmt->bindValue(':PAG_TITLE',                 $this->_oPage->getInsertField('PAG_TITLE'                ), PDO::PARAM_STR);
        $stmt->bindValue(':PAG_ACCROCHE',              $this->_oPage->getInsertField('PAG_ACCROCHE'             ), PDO::PARAM_STR);
        $stmt->bindValue(':PAG_METADESCRIPTION',       $this->_oPage->getInsertField('PAG_METADESCRIPTION'      ), PDO::PARAM_STR);
        $stmt->bindValue(':PAG_VISIBLE_MENU',          $this->_oPage->getInsertField('PAG_VISIBLE_MENU'         ), PDO::PARAM_INT);
        $stmt->bindValue(':PAG_MOTCLE1',               $this->_oPage->getInsertField('PAG_MOTCLE1'              ), PDO::PARAM_STR);
        $stmt->bindValue(':PAG_MOTCLE2',               $this->_oPage->getInsertField('PAG_MOTCLE2'              ), PDO::PARAM_STR);
        $stmt->bindValue(':PAG_MOTCLE3',               $this->_oPage->getInsertField('PAG_MOTCLE3'              ), PDO::PARAM_STR);
        $stmt->bindValue(':PAG_MOTCLE4',               $this->_oPage->getInsertField('PAG_MOTCLE4'              ), PDO::PARAM_STR);
        $stmt->bindValue(':PAG_MOTCLE5',               $this->_oPage->getInsertField('PAG_MOTCLE5'              ), PDO::PARAM_STR);
        $stmt->bindValue(':PAG_DATEMISEAJOUR',         $PAG_DATEMISEAJOUR                                        , PDO::PARAM_STR);
        $stmt->bindValue(':PAG_URLREWRITING',          $this->_oPage->getInsertField('PAG_URLREWRITING'         ), PDO::PARAM_STR);
        $stmt->bindValue(':PAG_GOOGLEFREQUENCE',       $this->_oPage->getInsertField('PAG_GOOGLEFREQUENCE'      ), PDO::PARAM_STR);
        $stmt->bindValue(':PAG_GOOGLEPRIORITE',        $this->_oPage->getInsertField('PAG_GOOGLEPRIORITE'       ), PDO::PARAM_STR);
        $stmt->bindValue(':PAG_EXCLURECHERCHE',        $this->_oPage->getInsertField('PAG_EXCLURECHERCHE'       ), PDO::PARAM_INT);
        $stmt->bindValue(':PAG_MASQUERGAUCHE',         $this->_oPage->getInsertField('PAG_MASQUERGAUCHE'        ), PDO::PARAM_INT);
        $stmt->bindValue(':PAG_MASQUERDROITE',         $this->_oPage->getInsertField('PAG_MASQUERDROITE'        ), PDO::PARAM_INT);
        $stmt->bindValue(':PAG_COMMENTAIREACTIF',      $this->_oPage->getInsertField('PAG_COMMENTAIREACTIF'     ), PDO::PARAM_INT);
        $stmt->bindValue(':PAG_HTTPS',                 $this->_oPage->getInsertField('PAG_HTTPS'                ), PDO::PARAM_INT);
        $stmt->bindValue(':PAG_CACHE',                 $this->_oPage->getInsertField('PAG_CACHE'                ), PDO::PARAM_INT);
        $stmt->bindValue(':ID_PAGE',                   $this->getField              ('ID_PAGE'                  ), PDO::PARAM_INT);
        $stmt->execute();

        $oPageOff = new Page($this->getField('ID_PAGE'), 'OFF_');
        $oPageOff->load();

        Link::delete('OFF_PAGE', $oPageOff->getID(), 'ALL');

        $sqlLiaisonsWeb = "select * from LIAISON_WEBOTHEQUE where ID_REVISION = ".$this->getID()." and LIA_CODE = 'REVISION_PAGE' order by LIA_ORDRE asc";
        foreach ($this->dbh->query($sqlLiaisonsWeb)->fetchAll(PDO::FETCH_ASSOC) as $aLiaison) {
            Link::insertWebotheque('OFF_PAGE', $oPageOff->getID(), $aLiaison['ID_WEBOTHEQUE'], null, $aLiaison['LIA_TYPE'], $aLiaison['LIA_TEMP'], $aLiaison['LIA_TEXT']);
        }

        $sqlLiaisonsPage = "select * from LIAISON_PAGE where ID_REVISION = ".$this->getID()." and LIA_CODE = 'REVISION_PAGE' order by LIA_ORDRE asc";
        foreach ($this->dbh->query($sqlLiaisonsPage)->fetchAll(PDO::FETCH_ASSOC) as $aLiaison) {
            Link::insertPage('OFF_PAGE', $oPageOff->getID(), $aLiaison['ID_PAGE'], null, null, $aLiaison['LIA_TYPE'], $aLiaison['LIA_TEMP'], $aLiaison['LIA_TEXT']);
        }

        $aParagrapheID    = $aParagraphe = array();

        // Récupération des paragraphes de la révision
        foreach ($this->_oPage->getParagraphes() as $oParagraphe) {
            // On verifie que chaque paragraphe hérités existe encore en mode OFF_
            if ($oParagraphe->getField('TPL_CODE') == 'TPL_HERITAGE') {
                $oP = new Paragraphe($oParagraphe->getField('PAR_TPL_IDENTIFIANT'), 'OFF_');
                if (!$oP->exist() || $oP->getField('PAR_HERITABLE')==0) {
                    // Le parent n'existe plus, on passe...
                    continue;
                }
                unset($oP);
            }  // On verifie que chaque paragraphe partagés existent encore en mode ON_
            elseif ($oParagraphe->getField('TPL_CODE') == 'TPL_PARTAGE') {
                $oP = new Paragraphe($oParagraphe->getField('PAR_TPL_IDENTIFIANT'), 'ON_');
                if (!$oP->exist()) {
                    // Le parent n'existe plus, on supprime et passe au suivant...
                    $oParaManquant = new Paragraphe_Revision($oParagraphe->getID(), new Revision($this->getID()));
                    $oParaManquant->delete();
                    continue;
                }
                unset($oP);
            }
            $aParagraphe[] = $oParagraphe->getFields();
            // On récupére la liste d'ID des paragraphes présent dans la révision
            $aParagrapheID[] = $oParagraphe->getID();
        }

        // Parcours des anciens paragraphes, sauvegarde des paragraphes hérités, et suppression
        $aParagraphesHeritesNouveau = array();
        foreach ($oPageOff->getParagraphes() as $oParagraphe) {
            // On supprime tous les paragraphes de la page OFF
            // Sauf s'il est hérité, et il n'est pas dans la révision
            if ($oParagraphe->getField('TPL_CODE') == 'TPL_HERITAGE' && !in_array($oParagraphe->getID(), $aParagrapheID)) {
                $aParagraphesHeritesNouveau[] = $oParagraphe;
            } else {
                $oParagraphe->delete();
            }
        }

        //placement des nouveaux paragraphes herites en tete et suppression
        $aParagrapheTmp = array();
        foreach ($aParagraphesHeritesNouveau as $oParagrapheHeriteNouveau) {
            $aParagrapheTmp[] = $oParagrapheHeriteNouveau->getFields();
            $oParagrapheHeriteNouveau->delete();
        }
        unset($oParagrapheHeriteNouveau);

        foreach ($aParagraphe as $aParagrapheExistant) {
            $aParagrapheTmp[] = $aParagrapheExistant;
        }
        $aParagraphe = $aParagrapheTmp;
        unset($aParagrapheTmp);

        // Insertion / remplacement des nouveaux paragraphes
        $stmt = $this->dbh->prepare("replace into OFF_PARAGRAPHE (
                    ID_PARAGRAPHE,
                    ID_PAGE,
                    PRT_CODE,
                    PRS_CODE,
                    PRS_WIDTH,
                    TPL_CODE,
                    PAR_TPL_IDENTIFIANT,
                    PAR_POIDS,
                    PAR_TITRE,
                    PAR_CONTENU,
                    PAR_CONTENUPARSE,
                    PAR_CONTENUTEXTE,
                    PAR_APARSER,
                    PAR_COLONNE,
                    PAR_HERITABLE,
                    PAR_DATEMODIFICATION,
                    PAR_BROUILLON
                    ) values (
                    :ID_PARAGRAPHE,
                    :ID_PAGE,
                    :PRT_CODE,
                    :PRS_CODE,
                    :PRS_WIDTH,
                    :TPL_CODE,
                    :PAR_TPL_IDENTIFIANT,
                    :PAR_POIDS,
                    :PAR_TITRE,
                    :PAR_CONTENU,
                    :PAR_CONTENUPARSE,
                    :PAR_CONTENUTEXTE,
                    1,
                    :PAR_COLONNE,
                    :PAR_HERITABLE,
                    :PAR_DATEMODIFICATION,
                    :PAR_BROUILLON
                    )");

        foreach ($aParagraphe as $rowParagraphe) {
            $stmt->bindValue(':ID_PARAGRAPHE',        $rowParagraphe['ID_PARAGRAPHE'], PDO::PARAM_INT);
            $stmt->bindValue(':ID_PAGE',              $rowParagraphe['ID_PAGE'], PDO::PARAM_INT);
            $stmt->bindValue(':PRT_CODE',             $rowParagraphe['PRT_CODE'], PDO::PARAM_STR);
            $stmt->bindValue(':PRS_CODE',             (!empty($rowParagraphe['PRS_CODE']) ? $rowParagraphe['PRS_CODE'] : null), PDO::PARAM_STR);
            $stmt->bindValue(':PRS_WIDTH',            $rowParagraphe['PRS_WIDTH'], PDO::PARAM_STR);
            $stmt->bindValue(':TPL_CODE',             (!empty($rowParagraphe['TPL_CODE']) ? $rowParagraphe['TPL_CODE'] : null), PDO::PARAM_STR);
            $stmt->bindValue(':PAR_TPL_IDENTIFIANT',  $rowParagraphe['PAR_TPL_IDENTIFIANT'], PDO::PARAM_INT);
            $stmt->bindValue(':PAR_POIDS',            $rowParagraphe['PAR_POIDS'], PDO::PARAM_INT);
            $stmt->bindValue(':PAR_TITRE',            $rowParagraphe['PAR_TITRE'], PDO::PARAM_STR);
            $stmt->bindValue(':PAR_CONTENU',          $rowParagraphe['PAR_CONTENU'], PDO::PARAM_STR);
            $stmt->bindValue(':PAR_CONTENUPARSE',     $rowParagraphe['PAR_CONTENUPARSE'], PDO::PARAM_STR);
            $stmt->bindValue(':PAR_CONTENUTEXTE',     $rowParagraphe['PAR_CONTENUTEXTE'], PDO::PARAM_STR);
            $stmt->bindValue(':PAR_COLONNE',          $rowParagraphe['PAR_COLONNE'], PDO::PARAM_STR);
            $stmt->bindValue(':PAR_HERITABLE',        $rowParagraphe['PAR_HERITABLE'], PDO::PARAM_INT);
            $stmt->bindValue(':PAR_DATEMODIFICATION', isset($rowParagraphe['PAR_DATEMODIFICATION'])? $rowParagraphe['PAR_DATEMODIFICATION'] : null, PDO::PARAM_INT);
            $stmt->bindValue(':PAR_BROUILLON',        isset($rowParagraphe['PAR_BROUILLON'])? $rowParagraphe['PAR_BROUILLON'] : null, PDO::PARAM_STR);
            $stmt->execute();
            $Paragraphe_class = 'Paragraphe' . substr($rowParagraphe['PRT_CODE'], 3);
            $oParagraphe = new $Paragraphe_class ($rowParagraphe['ID_PARAGRAPHE'], 'OFF_');
            $oParagraphe->inherit();
        }

        // copie des liaisons (obligatoire à cause des liens hors éditeur qui ne seront pas reparsés)
        //on prend que les liaisons pour la revision et dont les paragraphes ont ete rajoute a la page
        // Test sur objet webotheque existe ?? pas requis -> cles etrangere
        $sqlLiaisonsWeb = "select LIAISON_WEBOTHEQUE.*, REVISION_PARAGRAPHE.ID_PARAGRAPHE from LIAISON_WEBOTHEQUE
        inner join REVISION_PARAGRAPHE on (
        REVISION_PARAGRAPHE.ID_REVISIONPARAGRAPHE = LIAISON_WEBOTHEQUE.ID_LIAISON
        and REVISION_PARAGRAPHE.ID_PAGE=" . $oPageOff->getID() . "
        and REVISION_PARAGRAPHE.ID_REVISION = " .$this->getID().")
        where LIAISON_WEBOTHEQUE.ID_REVISION = ".$this->getID()."
        and LIAISON_WEBOTHEQUE.LIA_CODE = 'REVISION_PARAGRAPHE'
        and REVISION_PARAGRAPHE.ID_PARAGRAPHE in (select ID_PARAGRAPHE from OFF_PARAGRAPHE where ID_PAGE = ".$oPageOff->getID().") order by LIA_ORDRE asc";
        foreach ($this->dbh->query($sqlLiaisonsWeb)->fetchAll(PDO::FETCH_ASSOC) as $aLiaison) {
            Link::insertWebotheque('OFF_PARAGRAPHE', $aLiaison['ID_PARAGRAPHE'], $aLiaison['ID_WEBOTHEQUE'], null, $aLiaison['LIA_TYPE'], $aLiaison['LIA_TEMP'], $aLiaison['LIA_TEXT']);
        }

        $sqlLiaisonsPage = "select REVISION_PARAGRAPHE.ID_PARAGRAPHE as ID_LIAISON_PARAGRAPHE, LIAISON_PAGE.* from LIAISON_PAGE
        inner join REVISION_PARAGRAPHE on (
        REVISION_PARAGRAPHE.ID_REVISIONPARAGRAPHE = LIAISON_PAGE.ID_LIAISON
        and REVISION_PARAGRAPHE.ID_PAGE=" . $oPageOff->getID() . "
        and REVISION_PARAGRAPHE.ID_REVISION = " .$this->getID().")
        where LIAISON_PAGE.ID_REVISION = ".$this->getID()."
        and LIAISON_PAGE.LIA_CODE = 'REVISION_PARAGRAPHE'
        and REVISION_PARAGRAPHE.ID_PARAGRAPHE in (select ID_PARAGRAPHE from OFF_PARAGRAPHE where ID_PAGE = ".$oPageOff->getID().") order by LIA_ORDRE asc";
        foreach ($this->dbh->query($sqlLiaisonsPage)->fetchAll(PDO::FETCH_ASSOC) as $aLiaison) {
            Link::insertPage('OFF_PARAGRAPHE', $aLiaison['ID_LIAISON_PARAGRAPHE'], $aLiaison['ID_PAGE'], $aLiaison['ID_PARAGRAPHE'], null, $aLiaison['LIA_TYPE'], $aLiaison['LIA_TEMP'], $aLiaison['LIA_TEXT']);
        }

        //if (CMS::getCurrentSite()->hasModule(new Module('MOD_EXTRANET'))) {
        // copie des groupes REVISION => OFF
        $sql = "delete from GROUPE_OFF_PAGE where ID_PAGE=" . $oPageOff->getID();
        $this->dbh->exec($sql);
        $sql = "insert into GROUPE_OFF_PAGE (ID_GROUPE, ID_PAGE) select ID_GROUPE, " . $oPageOff->getID() . " from REVISION_GROUPE where ID_REVISION = " . $this->getID();
        $this->dbh->exec($sql);
        //}
        // Roles
        $sql = "delete from ROLE where ID_PAGE=" . $oPageOff->getID();
        $this->dbh->exec($sql);
        $sql = "insert into ROLE (SIT_CODE, ID_UTILISATEUR, PRO_CODE, ID_PAGE) select SIT_CODE, ID_UTILISATEUR, PRO_CODE, " . $oPageOff->getID() . " from REVISION_ROLE where ID_REVISION = " . $this->getID();
        $this->dbh->exec($sql);

        $oPageOff->resetStatut();
        $oPageOff->setDateModification();
        // Historisation de la reversion de la revision
        $oPageOff->historize('MODIFICATION', 'PAGE', gettext('retour_a_la_revision_du'). ' '. date('d/m/Y H:i', $this->getField('REV_DATECREATION')));
        return true;
    }

}
