<?php
/**
 * Attention à bien laisser les mixes de GET/POST/REQUEST (cedric@eolas.fr)
 * modifié suite au ticket #12717 (krishn.soobroyen@businessdecision.com)
 */
require '../../include/inc.bo_init.php';
Utilisateur::checkConnected();
require CLASS_DIR . 'class.db_page.php';
require CLASS_DIR . 'class.db_paragraphe.php';
require CLASS_DIR . 'class.Link.php';

if (isset($_REQUEST['Insert'])) {

    $PRT_CODE = _checkPRT_CODE();
    $oPage = new Page($_REQUEST['ID_PAGE']);
    $oPage->checkAuthorized();
    $oPage->lock();
    if (is_numeric($_POST['ID_PARAGRAPHE_TEMP'])) {
        $stmt = $dbh->prepare('delete from OFF_PARAGRAPHE where ID_PARAGRAPHE=:ID_PARAGRAPHE_TEMP');
        $stmt->bindValue(':ID_PARAGRAPHE_TEMP', $_POST['ID_PARAGRAPHE_TEMP'], PDO::PARAM_INT);
        $stmt->execute();
    }
    //Cas où l'on a plusieurs paragraphe (Copie de paragraphe par exemple)
    if (is_array($_POST['ID_PARAGRAPHE'])) {
        $modeTmp = 'ON_';
        $details = gettext('partage_du_paragraphe_X_de_la_page_Y');
        if (isset($_POST['MODECOPIE'])) {
            $modeTmp = $_POST['MODECOPIE'];
            if ($modeTmp == 'OFF_') {
                $details = gettext('copie_du_paragraphe_X_de_la_page_Y');
            } else {
                $details = gettext('copie_du_paragraphe_en_ligne_X_de_la_page_Y');
            }
        }
        $sql = "select * from " . $modeTmp . "PARAGRAPHE where ID_PARAGRAPHE in (" . implode(',', $_POST['ID_PARAGRAPHE']) . ") order by PAR_POIDS";
        $_aParagraphe = $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        //#12717
        $stmt = $dbh->prepare("insert into OFF_PARAGRAPHE (
                ID_PAGE,
                PRT_CODE,
                PRS_CODE,
                TPL_CODE,
                PAR_TITRE,
                PAR_TPL_IDENTIFIANT,
                PAR_POIDS,
                PAR_COLONNE,
                PAR_HERITABLE,
                PAR_DATEMODIFICATION,
                PAR_BROUILLON
                ) values (
                :ID_PAGE,
                :PRT_CODE,
                :PRS_CODE,
                :TPL_CODE,
                :PAR_TITRE,
                :PAR_TPL_IDENTIFIANT,
                :PAR_POIDS,
                :PAR_COLONNE,
                :PAR_HERITABLE,
                :PAR_DATEMODIFICATION,
                null
                )");


        $stmt->bindValue(':ID_PAGE', $oPage->getID(), PDO::PARAM_INT);
        $stmt->bindValue(':PRT_CODE', $PRT_CODE, PDO::PARAM_STR);
        $stmt->bindValue(':TPL_CODE', ( isset($_POST['TPL_CODE']) ) ? $_POST['TPL_CODE'] : null, PDO::PARAM_STR);
        $stmt->bindValue(':PAR_COLONNE', $_POST['PAR_COLONNE'], PDO::PARAM_STR);
        $stmt->bindValue(':PAR_HERITABLE', intval($_POST['PAR_HERITABLE']), PDO::PARAM_INT); //intval pour éviter 'null'
        $stmt->bindValue(':PAR_DATEMODIFICATION', time(), PDO::PARAM_INT);
        foreach ($_aParagraphe as $paragraphe) {

            $oPage->insertParagraphe($_REQUEST['PAR_POIDS'], $_POST['PAR_COLONNE']);

            if ( isset($_POST['TPL_CODE']) && !is_null($_POST['TPL_CODE']) && ($_POST['TPL_CODE'] == 'TPL_PARTAGE') ) {
                $stmt->bindValue(':PRS_CODE',null, PDO::PARAM_STR);
                $stmt->bindValue(':PAR_TPL_IDENTIFIANT', $paragraphe['ID_PARAGRAPHE'], PDO::PARAM_STR);
                $stmt->bindValue(':PAR_TITRE', null, PDO::PARAM_STR);
            } else {
                $stmt->bindValue(':PRS_CODE',($paragraphe['PRS_CODE'] != '') ? $paragraphe['PRS_CODE'] : null, PDO::PARAM_STR);
                $stmt->bindValue(':PAR_TPL_IDENTIFIANT', ( isset($_POST['PAR_TPL_IDENTIFIANT']) ) ? $_POST['PAR_TPL_IDENTIFIANT'] : '', PDO::PARAM_STR);
                $stmt->bindValue(':PAR_TITRE', $paragraphe['PAR_TITRE'], PDO::PARAM_STR);
            }
            $stmt->bindValue(':PAR_POIDS', $_REQUEST['PAR_POIDS'], PDO::PARAM_INT);

            $stmt->execute();
            $idtf = $dbh->lastInsertID($idtf);

            $Paragraphe_class = 'Paragraphe' . substr($PRT_CODE, 3);
            $oParagraphe = new $Paragraphe_class($idtf);
            $histoDetail = sprintf($details, $paragraphe['ID_PARAGRAPHE'], $paragraphe['ID_PAGE']); ;
            $histoDetail .= !empty($paragraphe['PAR_TITRE'])?' ('.$paragraphe['PAR_TITRE'].')':'';
            $oPage->historize('CREATION', 'PARAGRAPHE', $histoDetail, $idtf);

            if (isset($_POST['IS_PARTAGE'])) {
                //specifique partage
                $oParagraphe->parse($_POST['PAR_CONTENU']); //pour autre que copie
                //ajout pour En Savoir plus
                $oParagraphe->attachLiaison();
                $oParagraphe->inherit();

            } else {

                $oParagraphe->parse($paragraphe['PAR_CONTENU']);

                //On copie les liaisons
                //Liaisons pour en savoir plus
                //Liaisons webotheque
                $sqlLiaisonsEnSavoirPlus = "select * from LIAISON_WEBOTHEQUE
                where LIA_CODE = '".$modeTmp."PARAGRAPHE'
                and LIA_TYPE = ''
                and ID_LIAISON = ".intval($paragraphe['ID_PARAGRAPHE']) . " order by LIA_ORDRE asc";

                foreach ($dbh->query($sqlLiaisonsEnSavoirPlus)->fetchAll(PDO::FETCH_ASSOC) as $aLiaison) {
                    Link::insertWebotheque('OFF_PARAGRAPHE', $idtf, $aLiaison['ID_WEBOTHEQUE'], null, '', '', $aLiaison['LIA_TEXT']);
                }

                //Liaisons pages - interne
                $sqlLiaisonsEnSavoirPlus = "select * from LIAISON_PAGE
                where LIA_CODE = '".$modeTmp."PARAGRAPHE'
                and LIA_TYPE = ''
                and ID_LIAISON = ".intval($paragraphe['ID_PARAGRAPHE']) . " order by LIA_ORDRE asc";

                foreach ($dbh->query($sqlLiaisonsEnSavoirPlus)->fetchAll(PDO::FETCH_ASSOC) as $aLiaison) {
                    Link::insertPage('OFF_PARAGRAPHE', $idtf, $aLiaison['ID_PAGE'], null, null, '', '', $aLiaison['LIA_TEXT']);
                }

            }
            $_REQUEST['PAR_POIDS']++;

        }
    } else {
        $oPage->insertParagraphe($_REQUEST['PAR_POIDS'], $_POST['PAR_COLONNE']);
        $stmt = $dbh->prepare("insert into OFF_PARAGRAPHE (
            ID_PAGE,
            PRT_CODE,
            TPL_CODE,
            PAR_TITRE,
            PAR_TPL_IDENTIFIANT,
            PAR_POIDS,
            PAR_COLONNE,
            PAR_HERITABLE,
            PAR_MOBILEHIDDEN,
            PAR_DATEMODIFICATION,
            PAR_BROUILLON
            ) values (
            :ID_PAGE,
            :PRT_CODE,
            :TPL_CODE,
            :PAR_TITRE,
            :PAR_TPL_IDENTIFIANT,
            :PAR_POIDS,
            :PAR_COLONNE,
            :PAR_HERITABLE,
            :PAR_MOBILEHIDDEN,
            :PAR_DATEMODIFICATION,
            null
            )");
        $stmt->bindValue(':ID_PAGE', $oPage->getID(), PDO::PARAM_INT);
        $stmt->bindValue(':PRT_CODE', $PRT_CODE, PDO::PARAM_STR);
        $stmt->bindValue(':TPL_CODE', $_POST['TPL_CODE'], PDO::PARAM_STR);
        $stmt->bindValue(':PAR_TITRE', $_POST['PAR_TITRE'], PDO::PARAM_STR);
        $stmt->bindValue(':PAR_TPL_IDENTIFIANT', $_POST['PAR_TPL_IDENTIFIANT'], PDO::PARAM_STR);
        $stmt->bindValue(':PAR_POIDS', $_REQUEST['PAR_POIDS'], PDO::PARAM_INT);
        $stmt->bindValue(':PAR_COLONNE', $_POST['PAR_COLONNE'], PDO::PARAM_STR);
        $stmt->bindValue(':PAR_HERITABLE', intval($_POST['PAR_HERITABLE']), PDO::PARAM_INT); //intval pour éviter 'null'
        $stmt->bindValue(':PAR_MOBILEHIDDEN', intval($_POST['PAR_MOBILEHIDDEN']), PDO::PARAM_INT); //intval pour éviter 'null'
        $stmt->bindValue(':PAR_DATEMODIFICATION', time(), PDO::PARAM_INT);
        $stmt->execute();
        $idtf = $dbh->lastInsertID($idtf);

        $Paragraphe_class = 'Paragraphe' . substr($PRT_CODE, 3);
        $oParagraphe = new $Paragraphe_class($idtf);

        // Pour les applications externes, on enregistre la liaison
        if (is_numeric($_POST['ID_WEBOTHEQUE']) && ($oParagraphe instanceof Paragraphe_APPLIEXTERNE)) {
            require_once CLASS_DIR . 'class.db_webotheque.php';
            $oWebo = new Webo_LIENEXTERNE($_POST['ID_WEBOTHEQUE']);
            if ($oWebo->checkAuthorized(false) || $oWebo->checkShareAuthorized(false)) {
                Link::insertWebotheque('OFF_PARAGRAPHE', $oParagraphe->getID(), $_POST['ID_WEBOTHEQUE'], null, '');
            }
        }

        //ajout pour En Savoir plus
        $oParagraphe->attachLiaison();

        $oParagraphe->inherit();
        $oParagraphe->parse($_POST['PAR_CONTENU']);
        $oPage->historize('CREATION', 'PARAGRAPHE', $_POST['PAR_TITRE'], $idtf);
    }
    $oPage->setDateModification();
    $oPage->reorderParagraphes($_POST['PAR_COLONNE']);
    $oPage->resetStatut();
    header('Location:' . SERVER_ROOT . 'cms/cms_pseudo.php?idtf=' . $oPage->getID() . '&PFM=1#par' . $idtf);
    exit();
} elseif (isset($_REQUEST['Update'])) {
    $PRT_CODE = _checkPRT_CODE();
    $Paragraphe_class = 'Paragraphe' . substr($PRT_CODE, 3);
    $oParagraphe = new $Paragraphe_class($_REQUEST['idtf']);
    $oPage = $oParagraphe->getPage();
    $oPage->checkAuthorized();
    $oPage->lock();

    $stmt = $dbh->prepare("update OFF_PARAGRAPHE set
        TPL_CODE=:TPL_CODE,
        PAR_TITRE=:PAR_TITRE,
        PAR_TPL_IDENTIFIANT=:PAR_TPL_IDENTIFIANT,
        PAR_HERITABLE=:PAR_HERITABLE,
        PAR_MOBILEHIDDEN=:PAR_MOBILEHIDDEN,
        PAR_DATEMODIFICATION=:PAR_DATEMODIFICATION,
        PAR_BROUILLON=null
        where ID_PARAGRAPHE=:idtf");
    $stmt->bindValue(':TPL_CODE', $_POST['TPL_CODE'], PDO::PARAM_STR);
    $stmt->bindValue(':PAR_TITRE', $_POST['PAR_TITRE'], PDO::PARAM_STR);
    $stmt->bindValue(':PAR_TPL_IDENTIFIANT', $_POST['PAR_TPL_IDENTIFIANT'], PDO::PARAM_STR);
    $stmt->bindValue(':PAR_HERITABLE', intval($_POST['PAR_HERITABLE']), PDO::PARAM_INT); //intval pour éviter 'null'
    $stmt->bindValue(':PAR_MOBILEHIDDEN', intval($_POST['PAR_MOBILEHIDDEN']), PDO::PARAM_INT); //intval pour éviter 'null'
    $stmt->bindValue(':PAR_DATEMODIFICATION', time(), PDO::PARAM_INT);
    $stmt->bindValue(':idtf', $_REQUEST['idtf'], PDO::PARAM_INT);
    $stmt->execute();

    if ($_POST['PAR_HERITABLE']) {
        //recharger l'objet
        $oParagraphe = new $Paragraphe_class($_REQUEST['idtf']);
        $oParagraphe->inherit();
    } else {
        $oParagraphe->deleteInherited();
    }

    $oParagraphe->parse($_POST['PAR_CONTENU']);

    // Pour les applications externes, on enregistre la liaison
    if (is_numeric($_POST['ID_WEBOTHEQUE']) && ($oParagraphe instanceof Paragraphe_APPLIEXTERNE)) {
        require_once CLASS_DIR . 'class.db_webotheque.php';
        Link::delete('OFF_PARAGRAPHE', $oParagraphe->getID(), 'ALL');
        $oWebo = new Webo_LIENEXTERNE($_POST['ID_WEBOTHEQUE']);
        if ($oWebo->checkAuthorized(false) || $oWebo->checkShareAuthorized(false)) {
            Link::insertWebotheque('OFF_PARAGRAPHE', $oParagraphe->getID(), $_POST['ID_WEBOTHEQUE'], null, '');
        }
    }

    $oPage->resetStatut();
    $oPage->setDateModification();
    if ($PRT_CODE == 'PRT_PARTAGE') {
        $details = gettext('partage_du_paragraphe_X_de_la_page_Y');
        $oParagraphePartage = new Paragraphe($_POST['PAR_TPL_IDENTIFIANT'], 'ON_');
        $histoDetail = sprintf($details, $oParagraphePartage->getID(), $oParagraphePartage->getField('ID_PAGE'));
        $titrePartage = $oParagraphePartage->getField('PAR_TITRE');
        $histoDetail .= !empty($titrePartage)?' ('.$titrePartage.')':'';
        $oPage->historize('MODIFICATION', 'PARAGRAPHE', $histoDetail, $oParagraphe->getID());
    } else {
        $oPage->historize('MODIFICATION', 'PARAGRAPHE', $_POST['PAR_TITRE'], $oParagraphe->getID());
    }
    header('Location:' . SERVER_ROOT . 'cms/cms_pseudo.php?idtf=' . $oPage->getID() . '&PFM=1#par' . $oParagraphe->getID());
    exit();
} elseif (is_numeric($_GET['Delete'])) {
    $oParagraphe = new Paragraphe($_GET['Delete']);
    $oPage = $oParagraphe->getPage();
    $oPage->checkAuthorized();
    $oPage->lock();
    $oParagraphe->delete();
    $oPage->reorderParagraphes($oParagraphe->getField('PAR_COLONNE'));
    $oPage->resetStatut();
    $oPage->setDateModification();
    if ($oParagraphe->getField('PRT_CODE') == 'PRT_PARTAGE') {
        // On cherche la référence ON
        $oParagraphePartage = new Paragraphe($oParagraphe->getField('PAR_TPL_IDENTIFIANT'), 'ON_');
        // Si non, on cherche la référence OFF pour récupérer les info de l'historique (cas d'une page hors ligne)...
        if (!$oParagraphePartage || !$oParagraphePartage->exist()) {
            $oParagraphePartage = new Paragraphe($oParagraphe->getField('PAR_TPL_IDENTIFIANT'), 'OFF_');
        }
        if ($oParagraphePartage && $oParagraphePartage->exist()) {
            $details = gettext('suppression_du_paragraphe_partage_X_de_la_page_Y');
            $histoDetail = sprintf($details, $oParagraphePartage->getField('ID_PARAGRAPHE'), $oParagraphePartage->getField('ID_PAGE'));
            $sPAR_TITRE = $oParagraphePartage->getField('PAR_TITRE');
            $histoDetail .= !empty($sPAR_TITRE)?' ('.$sPAR_TITRE.')':'';
            $oPage->historize('SUPPRESSION', 'PARAGRAPHE', $histoDetail, $_GET['Delete']);
        } else {
            $oPage->historize('SUPPRESSION', 'PARAGRAPHE', $oParagraphe->getField('PAR_TITRE'), $_GET['Delete']);
        }
    } else {
        $oPage->historize('SUPPRESSION', 'PARAGRAPHE', $oParagraphe->getField('PAR_TITRE'), $_GET['Delete']);
    }

    header('Location:' . SERVER_ROOT . 'cms/cms_pseudo.php?idtf=' . $oPage->getID() . '&PFM=1#par' . $oParagraphe->getID());
    exit();
} elseif (is_numeric($_GET['Up'])) {
    $oParagraphe = new Paragraphe($_GET['Up']);
    $oPage = $oParagraphe->getPage();
    $oPage->checkAuthorized();
    $oPage->lock();
    $oParagraphe->up();
    $oPage->resetStatut();
    $oPage->setDateModification();
    $histoDetail = gettext('deplacement_du_paragraphe');
    if ($oParagraphe->getField('PAR_TITRE')) {
        $histoDetail .= ' ' . $oParagraphe->getField('PAR_TITRE');
    }
    $oPage->historize('MODIFICATION', 'PARAGRAPHE', $histoDetail, $oParagraphe->getID());
    header('Location:' . SERVER_ROOT . 'cms/cms_pseudo.php?idtf=' . $oPage->getID() . '&PFM=1#par' . $oParagraphe->getID());
    exit();
} elseif (is_numeric($_GET['Down'])) {
    $oParagraphe = new Paragraphe($_GET['Down']);
    $oPage = $oParagraphe->getPage();
    $oPage->checkAuthorized();
    $oPage->lock();
    $oParagraphe->down();
    $oPage->resetStatut();
    $oPage->setDateModification();
    $histoDetail = gettext('deplacement_du_paragraphe');
    if ($oParagraphe->getField('PAR_TITRE')) {
        $histoDetail .= ' ' . $oParagraphe->getField('PAR_TITRE');
    }
    $oPage->historize('MODIFICATION', 'PARAGRAPHE', $histoDetail, $oParagraphe->getID());
    header('Location:' . SERVER_ROOT . 'cms/cms_pseudo.php?idtf=' . $oPage->getID() . '&PFM=1#par' . $oParagraphe->getID());
    exit();
} elseif (is_numeric($_GET['Left'])) {
    $oParagraphe = new Paragraphe($_GET['Left']);
    $oPage = $oParagraphe->getPage();
    $oPage->checkAuthorized();
    $oPage->lock();
    $oParagraphe->twist('PAR_LEFT');
    $oPage->resetStatut();
    $oPage->setDateModification();
    $histoDetail = gettext('deplacement_du_paragraphe');
    if ($oParagraphe->getField('PAR_TITRE')) {
        $histoDetail .= ' ' . $oParagraphe->getField('PAR_TITRE');
    }
    $oPage->historize('MODIFICATION', 'PARAGRAPHE', $histoDetail, $oParagraphe->getID());
    header('Location:' . SERVER_ROOT . 'cms/cms_pseudo.php?idtf=' . $oPage->getID() . '&PFM=1#par' . $oParagraphe->getID());
    exit();
} elseif (is_numeric($_GET['Right'])) {
    $oParagraphe = new Paragraphe($_GET['Right']);
    $oPage = $oParagraphe->getPage();
    $oPage->checkAuthorized();
    $oPage->lock();
    $oParagraphe->twist('PAR_RIGHT');
    $oPage->resetStatut();
    $oPage->setDateModification();
    $histoDetail = gettext('deplacement_du_paragraphe');
    if ($oParagraphe->getField('PAR_TITRE')) {
        $histoDetail .= ' ' . $oParagraphe->getField('PAR_TITRE');
    }
    $oPage->historize('MODIFICATION', 'PARAGRAPHE', $histoDetail, $oParagraphe->getID());
    header('Location:' . SERVER_ROOT . 'cms/cms_pseudo.php?idtf=' . $oPage->getID() . '&PFM=1#par' . $oParagraphe->getID());
    exit();
} elseif (isset($_GET['PRS_CODE'])) {
    $oParagraphe = new Paragraphe($_GET['idtf']);
    $oPage = $oParagraphe->getPage();
    $oPage->checkAuthorized();
    $oPage->lock();
    $sql =($_GET['PRS_CODE'] != '') ? "update OFF_PARAGRAPHE set PRS_CODE=" . $dbh->quote($_GET['PRS_CODE']) . ", PRS_WIDTH='' where ID_PARAGRAPHE=" . $oParagraphe->getID() : "update OFF_PARAGRAPHE set PRS_CODE=null, PRS_WIDTH='' where ID_PARAGRAPHE=" . $oParagraphe->getID();
    $dbh->exec($sql);
    $oPage->resetStatut();
    $oPage->setDateModification();
    $histoDetail = gettext('modification_du_style_du_paragraphe');
    if ($oParagraphe->getField('PAR_TITRE')) {
        $histoDetail .= ' ' . $oParagraphe->getField('PAR_TITRE');
    }
    $oPage->historize('MODIFICATION', 'PARAGRAPHE', $histoDetail, $oParagraphe->getID());

    //Dans le cas d'un héritage avec styles, il faut mettre à jour le style du paragraphe héritable sur les paragraphes hérités
    if ($oParagraphe->getField('PAR_HERITABLE') == 2) {
        $sql =($_GET['PRS_CODE'] != '')
            ? "update OFF_PARAGRAPHE set PRS_CODE=" . $dbh->quote($_GET['PRS_CODE']) . ", PRS_WIDTH=''
                where TPL_CODE='TPL_HERITAGE' and PAR_TPL_IDENTIFIANT=" . $oParagraphe->getID()
            : "update OFF_PARAGRAPHE set PRS_CODE=null, PRS_WIDTH=''
                where TPL_CODE='TPL_HERITAGE' and PAR_TPL_IDENTIFIANT=" . $oParagraphe->getID();
        $dbh->exec($sql);
    }

    header('Location:' . SERVER_ROOT . 'cms/cms_pseudo.php?idtf=' . $oPage->getID() . '&PFM=1#par' . $oParagraphe->getID());
    exit();
} elseif (isset($_GET['PRS_WIDTH'])) {
    $oParagraphe = new Paragraphe($_GET['idtf']);
    $oPage = $oParagraphe->getPage();
    $oPage->checkAuthorized();
    $oPage->lock();
    $sql = "update OFF_PARAGRAPHE set PRS_WIDTH=" . $dbh->quote($_GET['PRS_WIDTH']) . " where ID_PARAGRAPHE=" . $oParagraphe->getID();
    $dbh->exec($sql);
    $oPage->resetStatut();
    $oPage->setDateModification();
    $histoDetail = gettext('modification_du_style_du_paragraphe');
    if ($oParagraphe->getField('PAR_TITRE')) {
        $histoDetail .= ' ' . $oParagraphe->getField('PAR_TITRE');
    }
    $oPage->historize('MODIFICATION', 'PARAGRAPHE', $histoDetail, $oParagraphe->getID());

    //Dans le cas d'un héritage avec styles, il faut mettre à jour le style du paragraphe héritable sur les paragraphes hérités
    if ($oParagraphe->getField('PAR_HERITABLE') == 2) {
        $sql = "update OFF_PARAGRAPHE set
            PRS_WIDTH=" . $dbh->quote($_GET['PRS_WIDTH']) . "
            where TPL_CODE='TPL_HERITAGE' and PAR_TPL_IDENTIFIANT=" . $oParagraphe->getID();
        $dbh->exec($sql);
    }

    header('Location:' . SERVER_ROOT . 'cms/cms_pseudo.php?idtf=' . $oPage->getID() . '&PFM=1#par' . $oParagraphe->getID());
    exit();
} elseif (isset($_GET['DragDrop'])) {
    header("Content-type: text/plain; charset=utf-8");
    $aParagraphe = array_filter(explode(',', str_replace('editPar', '', $_GET['order'])));
    if (count($aParagraphe)>0) {
        $oParagraphe = new Paragraphe($aParagraphe[0]);
        $oPage = $oParagraphe->getPage();
        $oPage->checkAuthorized();
        $oPage->lock();
        $stmt = $dbh->prepare("update OFF_PARAGRAPHE set PAR_POIDS=:PAR_POIDS where ID_PARAGRAPHE=:ID_PARAGRAPHE and ID_PAGE=:ID_PAGE");
        $PAR_POIDS = 1;
        $stmt->bindValue(':ID_PAGE', $oPage->getID(), PDO::PARAM_INT);
        foreach ($aParagraphe as $ID_PARAGRAPHE) {
            $stmt->bindParam(':PAR_POIDS', $PAR_POIDS, PDO::PARAM_INT);
            $stmt->bindValue(':ID_PARAGRAPHE', $ID_PARAGRAPHE, PDO::PARAM_INT);
            $stmt->execute();
            $PAR_POIDS++;
        }
        $oPage->resetStatut();
        $oPage->setDateModification();
        $oPage->historize('MODIFICATION', 'PARAGRAPHE', gettext('deplacement_de_paragraphes_DnD'));
        echo 'OK';
    }
    exit();
} elseif (is_numeric($_POST['deleteTmp'])) {
    $oParagraphe = new Paragraphe_TXT($_POST['deleteTmp']);
    $oParagraphe->delete();
    exit();
} elseif (is_numeric($_POST['updateTmp'])) {
    require_once(CLASS_DIR . 'class.Editor.php');
    Editor::updateContent($_POST['PAR_CONTENU'], 'OFF_PARAGRAPHE', 'PAR_BROUILLON', 'ID_PARAGRAPHE', $_POST['updateTmp']);
    exit();
} elseif (is_numeric($_POST['undoSaveTmp'])) {
    $stmt = $dbh->prepare('update OFF_PARAGRAPHE set PAR_BROUILLON=null where ID_PARAGRAPHE=:ID_PARAGRAPHE');
    $stmt->bindValue(':ID_PARAGRAPHE', $_POST['undoSaveTmp'], PDO::PARAM_INT);
    $stmt->execute();
    exit();
}

function _checkPRT_CODE()
{
    $PRT_CODE = $_REQUEST['PRT_CODE'];
    switch ($PRT_CODE) {
        case 'PRT_COPIE' :
            $PRT_CODE = 'PRT_TXT';
            $_POST['TPL_CODE'] = null;
            //$_POST['PAR_COLONNE'];
            $_POST['PAR_TPL_IDENTIFIANT'] = '';
            break;
        case 'PRT_FORMULAIRE' :
            $_POST['TPL_CODE'] = 'TPL_FORMULAIRE';
            $_POST['PAR_COLONNE'] = 'PAR_CENTRAL';
            $_POST['PAR_TPL_IDENTIFIANT'] = intval($_POST['ID_FORMULAIRE']);
            break;
        case 'PRT_HAUTDEPAGE' :
            $_POST['TPL_CODE'] = null;
            $_POST['PAR_COLONNE'] = 'PAR_CENTRAL';
            $_POST['PAR_TPL_IDENTIFIANT'] = '';
            break;
        case 'PRT_PARTAGE' :
            $_POST['TPL_CODE'] = 'TPL_PARTAGE';
            $_POST['PAR_COLONNE'] = $_REQUEST['PAR_COLONNE'];
            $_POST['PAR_TPL_IDENTIFIANT'] = intval($_GET['ID_PARAGRAPHE']);
            break;
        case 'PRT_SOMMAIRE' :
            $_POST['TPL_CODE'] = 'TPL_SOMMAIREPAGE';
            //$_POST['PAR_COLONNE'];
            $_POST['PAR_TPL_IDENTIFIANT'] = intval($_POST['ID_PAGE']);
            break;
        case 'PRT_TPL' :
            //$_POST['TPL_CODE'];
            //$_POST['PAR_COLONNE'];
            //$_POST['PAR_TPL_IDENTIFIANT'];
            break;
        case 'PRT_TXT' :
            $_POST['TPL_CODE'] = null;
            //$_POST['PAR_COLONNE'];
            $_POST['PAR_TPL_IDENTIFIANT'] = '';
            break;
        case 'PRT_WIDGET' :
            $_POST['TPL_CODE'] = null;
            //$_POST['PAR_COLONNE'];
            $_POST['PAR_TPL_IDENTIFIANT'] = '';
            $_POST['PAR_CONTENU'] = intval($_POST['ID_WEBOTHEQUE']);
            break;
        case 'PRT_APPLIEXTERNE' :
            $_POST['TPL_CODE'] = 'TPL_APPLIEXTERNE';
            $_POST['PAR_COLONNE'] = 'PAR_CENTRAL';
            $_POST['PAR_TPL_IDENTIFIANT'] = intval($_POST['ID_WEBOTHEQUE']) . '@';
            $_POST['PAR_TPL_IDENTIFIANT'] .= intval($_POST['APP_LARGEUR']) . '@';
            $_POST['PAR_TPL_IDENTIFIANT'] .= intval($_POST['APP_LARGEUR_FIXE']) . '@';
            $_POST['PAR_TPL_IDENTIFIANT'] .= intval($_POST['APP_HAUTEUR']) . '@';
            $_POST['PAR_TPL_IDENTIFIANT'] .= intval($_POST['APP_FRAMEBORDER']) . '@';
            $_POST['PAR_TPL_IDENTIFIANT'] .= $_POST['APP_SCROLLING'] . '@';
            $_POST['PAR_TPL_IDENTIFIANT'] .= intval($_POST['APP_MARGINHEIGHT']) . '@';
            $_POST['PAR_TPL_IDENTIFIANT'] .= intval($_POST['APP_MARGINWIDTH']) . '@';
            $_POST['PAR_TPL_IDENTIFIANT'] .= $_POST['APP_TITLE'] . '@';
            break;
        default :
            die('Mauvais PRT_CODE : ' . $PRT_CODE);
    }
    return $PRT_CODE;
}
