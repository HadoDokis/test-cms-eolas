<?php
require '../include/inc.bo_init.php';
Utilisateur::checkConnected();
require CLASS_DIR . 'class.Link.php';
require CLASS_DIR . 'class.Editor.php';
require CLASS_DIR . 'class.db_page.php';
require CLASS_DIR . 'class.db_paragraphe.php';
require '../include/lib.workflow.php';

if (isset ($_POST['Insert'])) {
    $oPageParent = new Page($_POST['PAG_IDPERE']);
    $oPageParent->checkAuthorized();

    if ($_POST['PAG_TITRE_REFERENCEMENT'] == '') {
        $_POST['PAG_TITRE_REFERENCEMENT'] = $_POST['PAG_TITRE'];
    }
    if ($_POST['PAG_METADESCRIPTION'] == '') {
        $_POST['PAG_METADESCRIPTION'] = $_POST['PAG_ACCROCHE'];
    }
    if ($_POST['PAG_URLREWRITING'] == '') {
        $_POST['PAG_URLREWRITING'] = $_POST['PAG_TITRE_REFERENCEMENT'];
    }

    //decalage des pages suivantes
    $stmt = $dbh->prepare("update OFF_PAGE set PAG_POIDS=PAG_POIDS + 1 where PAG_IDPERE=:PAG_IDPERE and PAG_POIDS>=:PAG_POIDS");
    $stmt->bindValue(':PAG_IDPERE', $_POST['PAG_IDPERE'], PDO::PARAM_INT);
    $stmt->bindValue(':PAG_POIDS', $_POST['PAG_POIDS'], PDO::PARAM_INT);
    $stmt->execute();

    $stmt = $dbh->prepare("insert into OFF_PAGE (
        SIT_CODE,
        PST_CODE,
        PGS_CODE,
        PSS_CODE,
        ID_STYLEDYNAMIQUE,
        PAG_IDPERE,
        PAG_TITRE,
        PAG_TITRE_MENU,
        PAG_TITRE_REFERENCEMENT,
        PAG_TITLE,
        PAG_URLREWRITING,
        PAG_POIDS,
        PAG_VISIBLE_MENU,
        PAG_MASQUERGAUCHE,
        PAG_MASQUERDROITE,
        PAG_EXCLURECHERCHE,
        PAG_NOINDEX,
        PAG_ACCROCHE,
        PAG_METADESCRIPTION,
        PAG_HEAD,
        PAG_MOTCLE1,
        PAG_MOTCLE2,
        PAG_MOTCLE3,
        PAG_MOTCLE4,
        PAG_MOTCLE5,
        PAG_DATEONLINE,
        PAG_DATEOFFLINE,
        PAG_DATEMODIFICATION,
        PAG_GOOGLEFREQUENCE,
        PAG_GOOGLEPRIORITE,
        PAG_COMMENTAIREACTIF,
        PAG_STYLEDEFAUTHERITABLE,
        PAG_STYLEPERSOHERITABLE,
        PAG_HTTPS,
        PAG_CACHE,
        PAG_DATEMISEAJOUR
        ) values (
        :SIT_CODE,
        :PST_CODE,
        :PGS_CODE,
        :PSS_CODE,
        :ID_STYLEDYNAMIQUE,
        :PAG_IDPERE,
        :PAG_TITRE,
        :PAG_TITRE_MENU,
        :PAG_TITRE_REFERENCEMENT,
        :PAG_TITLE,
        :PAG_URLREWRITING,
        :PAG_POIDS,
        :PAG_VISIBLE_MENU,
        :PAG_MASQUERGAUCHE,
        :PAG_MASQUERDROITE,
        :PAG_EXCLURECHERCHE,
        :PAG_NOINDEX,
        :PAG_ACCROCHE,
        :PAG_METADESCRIPTION,
        :PAG_HEAD,
        :PAG_MOTCLE1,
        :PAG_MOTCLE2,
        :PAG_MOTCLE3,
        :PAG_MOTCLE4,
        :PAG_MOTCLE5,
        :PAG_DATEONLINE,
        :PAG_DATEOFFLINE,
        :PAG_DATEMODIFICATION,
        'weekly',
        '0.5',
        :PAG_COMMENTAIREACTIF,
        :PAG_STYLEDEFAUTHERITABLE,
        :PAG_STYLEPERSOHERITABLE,
        :PAG_HTTPS,
        :PAG_CACHE,
        :PAG_DATEMISEAJOUR
        )");
    $stmt->bindValue(':SIT_CODE', CMS::getCurrentSite()->getID(), PDO::PARAM_STR);
    $stmt->bindValue(':PST_CODE', Page::getInitialStatut(), PDO::PARAM_STR);
    $stmt->bindValue(':PGS_CODE', (!empty ($_POST['PGS_CODE'])) ? $_POST['PGS_CODE'] : null, PDO::PARAM_STR);
    $stmt->bindValue(':PSS_CODE', (!empty ($_POST['PSS_CODE'])) ? $_POST['PSS_CODE'] : null, PDO::PARAM_STR);
    $stmt->bindValue(':ID_STYLEDYNAMIQUE', (is_numeric($_POST['ID_STYLEDYNAMIQUE'])) ? $_POST['ID_STYLEDYNAMIQUE'] : null, PDO::PARAM_INT);
    $stmt->bindValue(':PAG_IDPERE', $_POST['PAG_IDPERE'], PDO::PARAM_INT);
    $stmt->bindValue(':PAG_TITRE', $_POST['PAG_TITRE'], PDO::PARAM_STR);
    $stmt->bindValue(':PAG_TITRE_MENU', $_POST['PAG_TITRE_MENU'], PDO::PARAM_STR);
    $stmt->bindValue(':PAG_TITRE_REFERENCEMENT', $_POST['PAG_TITRE_REFERENCEMENT'], PDO::PARAM_STR);
    $stmt->bindValue(':PAG_TITLE', $_POST['PAG_TITLE'], PDO::PARAM_STR);
    $stmt->bindValue(':PAG_URLREWRITING', filenameToRfc1738(mb_convert_case(trim($_POST['PAG_URLREWRITING']), MB_CASE_LOWER)), PDO::PARAM_STR);
    $stmt->bindValue(':PAG_POIDS', $_POST['PAG_POIDS'], PDO::PARAM_INT);
    $stmt->bindValue(':PAG_VISIBLE_MENU', !empty($_POST['PAG_VISIBLE_MENU']), PDO::PARAM_INT);
    $stmt->bindValue(':PAG_MASQUERGAUCHE', !empty($_POST['PAG_MASQUERGAUCHE']), PDO::PARAM_INT);
    $stmt->bindValue(':PAG_MASQUERDROITE', !empty($_POST['PAG_MASQUERDROITE']), PDO::PARAM_INT);
    $stmt->bindValue(':PAG_EXCLURECHERCHE', !empty($_POST['PAG_EXCLURECHERCHE']), PDO::PARAM_INT);
    $stmt->bindValue(':PAG_NOINDEX', !empty($_POST['PAG_NOINDEX']), PDO::PARAM_INT);
    $stmt->bindValue(':PAG_ACCROCHE', $_POST['PAG_ACCROCHE'], PDO::PARAM_STR);
    $stmt->bindValue(':PAG_METADESCRIPTION', $_POST['PAG_METADESCRIPTION'], PDO::PARAM_STR);
    $stmt->bindValue(':PAG_HEAD', $_POST['PAG_HEAD'], PDO::PARAM_STR);
    $stmt->bindValue(':PAG_MOTCLE1', $_POST['PAG_MOTCLE1'], PDO::PARAM_STR);
    $stmt->bindValue(':PAG_MOTCLE2', $_POST['PAG_MOTCLE2'], PDO::PARAM_STR);
    $stmt->bindValue(':PAG_MOTCLE3', $_POST['PAG_MOTCLE3'], PDO::PARAM_STR);
    $stmt->bindValue(':PAG_MOTCLE4', $_POST['PAG_MOTCLE4'], PDO::PARAM_STR);
    $stmt->bindValue(':PAG_MOTCLE5', $_POST['PAG_MOTCLE5'], PDO::PARAM_STR);
    $stmt->bindValue(':PAG_DATEONLINE', $oPageParent->getField('PAG_DATEONLINE'), PDO::PARAM_INT);
    $stmt->bindValue(':PAG_DATEOFFLINE', $oPageParent->getField('PAG_DATEOFFLINE'), PDO::PARAM_INT);
    $stmt->bindValue(':PAG_DATEMODIFICATION', time(), PDO::PARAM_INT);
    $stmt->bindValue(':PAG_COMMENTAIREACTIF', (isset($_POST['PAG_COMMENTAIREACTIF']))? $_POST['PAG_COMMENTAIREACTIF'] : 0, PDO::PARAM_INT);
    $stmt->bindValue(':PAG_STYLEDEFAUTHERITABLE', (empty($_POST['affecterStyleFils']))? 0 : $_POST['affecterStyleFils'], PDO::PARAM_INT);
    $stmt->bindValue(':PAG_STYLEPERSOHERITABLE', (empty($_POST['affecterStylePersoFils']))? 0 : $_POST['affecterStylePersoFils'], PDO::PARAM_INT);
    $stmt->bindValue(':PAG_HTTPS', !empty($_POST['PAG_HTTPS']), PDO::PARAM_INT);
    $stmt->bindValue(':PAG_CACHE', !empty($_POST['PAG_CACHE']), PDO::PARAM_INT);
    $stmt->bindValue(':PAG_DATEMISEAJOUR', unixtime($_POST['PAG_DATEMISEAJOUR']), PDO::PARAM_INT);//peut être vide donc null
    $stmt->execute();
    $idtf = $dbh->lastInsertID();

    if (is_array($_POST['ID_THEMATIQUE']) && count($_POST['ID_THEMATIQUE'])>0) {
        foreach ($_POST['ID_THEMATIQUE'] as $ID_THEMATIQUE) {
            Link::insertThematique('OFF_PAGE', $idtf, $ID_THEMATIQUE);
        }
    }

    $oPage = new Page($idtf);
    $oPage->attachLiaison();
    $oPage->historize('CREATION', 'PAGE');
    traitementCommun($oPage);

    Utilisateur::getConnected()->initSession(CMS::getCurrentSite()->getID(), false);

    // paragraphes hérités
    // on les prend en sens inverse car le dernier inséré se retrouve en haut
    $sql = "select * from OFF_PARAGRAPHE where ID_PAGE in (" . implode(',', $oPage->getParentsID()) . ") and PAR_HERITABLE != 0 order by PAR_POIDS desc";
    foreach ($dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $oParagraphe = new Paragraphe($row['ID_PARAGRAPHE'], 'OFF_');
        $oParagraphe->setFields($row);
        $oParagraphe->inherit();
    }

    header('Location:' . SERVER_ROOT . 'cms/cms_pseudo.php?PFM=1&idtf=' . $idtf);
    exit ();

} elseif (isset ($_POST['UpdateRef'])) {
    $oPage = new Page($_POST['idtf']);
    $oPage->checkAuthorized();
    $oPage->lock();

    if ($_POST['PAG_TITRE_REFERENCEMENT'] == '') {
        $_POST['PAG_TITRE_REFERENCEMENT'] = $oPage->getField('PAG_TITRE');
    }
    if ($_POST['PAG_METADESCRIPTION'] == '') {
        $_POST['PAG_METADESCRIPTION'] = $oPage->getField('PAG_METADESCRIPTION');
    }
    if ($_POST['PAG_URLREWRITING'] == '') {
        $_POST['PAG_URLREWRITING'] = $_POST['PAG_TITRE_REFERENCEMENT'];
    }

    //Update référencement OFF_PAGE + ON_PAGE
    $stmt = $dbh->prepare("update OFF_PAGE set
        PAG_URLREWRITING=:PAG_URLREWRITING,
        PAG_TITRE_REFERENCEMENT=:PAG_TITRE_REFERENCEMENT,
        PAG_METADESCRIPTION=:PAG_METADESCRIPTION,
        PAG_HEAD=:PAG_HEAD,
        PAG_GOOGLEFREQUENCE=:PAG_GOOGLEFREQUENCE,
        PAG_GOOGLEPRIORITE=:PAG_GOOGLEPRIORITE,
        PAG_NOINDEX=:PAG_NOINDEX
        where ID_PAGE=:idtf");
    $stmt->bindValue(':PAG_URLREWRITING', filenameToRfc1738(mb_convert_case(trim($_POST['PAG_URLREWRITING']), MB_CASE_LOWER)), PDO::PARAM_STR);
    $stmt->bindValue(':PAG_TITRE_REFERENCEMENT', $_POST['PAG_TITRE_REFERENCEMENT'], PDO::PARAM_STR);
    $stmt->bindValue(':PAG_METADESCRIPTION', $_POST['PAG_METADESCRIPTION'], PDO::PARAM_STR);
    $stmt->bindValue(':PAG_HEAD', $_POST['PAG_HEAD'], PDO::PARAM_STR);
    $stmt->bindValue(':PAG_GOOGLEFREQUENCE', $_POST['PAG_GOOGLEFREQUENCE'], PDO::PARAM_STR);
    $stmt->bindValue(':PAG_GOOGLEPRIORITE', $_POST['PAG_GOOGLEPRIORITE'], PDO::PARAM_STR);
    $stmt->bindValue(':PAG_NOINDEX', !empty($_POST['PAG_NOINDEX']), PDO::PARAM_INT);
    $stmt->bindValue(':idtf', $_POST['idtf'], PDO::PARAM_INT);
    $stmt->execute();

    $stmt = $dbh->prepare("update ON_PAGE set
        PAG_URLREWRITING = :PAG_URLREWRITING,
        PAG_TITRE_REFERENCEMENT=:PAG_TITRE_REFERENCEMENT,
        PAG_METADESCRIPTION=:PAG_METADESCRIPTION,
        PAG_HEAD=:PAG_HEAD,
        PAG_GOOGLEFREQUENCE=:PAG_GOOGLEFREQUENCE,
        PAG_GOOGLEPRIORITE=:PAG_GOOGLEPRIORITE,
        PAG_NOINDEX=:PAG_NOINDEX
        where ID_PAGE=:idtf");
    $stmt->bindValue(':PAG_URLREWRITING', filenameToRfc1738(mb_convert_case(trim($_POST['PAG_URLREWRITING']), MB_CASE_LOWER)), PDO::PARAM_STR);
    $stmt->bindValue(':PAG_TITRE_REFERENCEMENT', $_POST['PAG_TITRE_REFERENCEMENT'], PDO::PARAM_STR);
    $stmt->bindValue(':PAG_METADESCRIPTION', $_POST['PAG_METADESCRIPTION'], PDO::PARAM_STR);
    $stmt->bindValue(':PAG_HEAD', $_POST['PAG_HEAD'], PDO::PARAM_STR);
    $stmt->bindValue(':PAG_GOOGLEFREQUENCE', $_POST['PAG_GOOGLEFREQUENCE'], PDO::PARAM_STR);
    $stmt->bindValue(':PAG_GOOGLEPRIORITE', $_POST['PAG_GOOGLEPRIORITE'], PDO::PARAM_STR);
    $stmt->bindValue(':PAG_NOINDEX', !empty($_POST['PAG_NOINDEX']), PDO::PARAM_INT);
    $stmt->bindValue(':idtf', $_POST['idtf'], PDO::PARAM_INT);
    $stmt->execute();

    //maj liens internes sur ON_PAGE
    $sql = "update ON_PARAGRAPHE set PAR_APARSER=1 where ID_PARAGRAPHE in
        (select distinct(ID_LIAISON) from LIAISON_PAGE where LIA_CODE='ON_PARAGRAPHE' and ID_PAGE= " . $oPage->getID() . ")";
    $dbh->exec($sql);
    $oPage->setDateModification();
    $oPage->historize('MODIFICATION', 'REFERENCEMENT');

    // Purge du cache de l'ensemble des sites
    Page::clearAllCache();

    if (!$oPage->setURLAlternative($_POST['URA_LIBELLE'])) {
        setMsg('Une Url alternative n\'est pas valide.', 'ERROR');
    } else {
        setMsg(gettext('UPDATE_OK'));
    }

    header('Location:' . SERVER_ROOT . 'cms/cms_page.php?idtf=' . $_POST['idtf']);
    exit ();

} elseif (isset($_POST['Update'])) {
    /**
    * Si 'affecter etat aux pages filles'
    * =>  on applique la date ET l'état à toutes les pages filles ayant le meme état
    *  -> Si etat est 'mettre hors ligne', tous les pages filles sont aussi affectes
    *  -> Sinon, apres test 'WKF_PRE_FONCTION' si existe, seul les pages filles avec le meme etat sont affectes
    * Si 'affecter etat aux pages filles' pas coche
    *  -> apres test 'WKF_PRE_FONCTION' si existe, seul la page est modifiée
    **/
    $oPage = new Page($_POST['idtf']);
    $oPage->checkAuthorized();
    $oPage->lock();

    $_aChildren = array ($oPage->getID());
    $statutPageInitiale = $oPage->getField('PST_CODE');

    $affectationFils = false;

    $aIDPAGE_HISTO_MODIF_PAGE = array($oPage->getID()); // Tableau des ID_PAGE pour lesquelles nous devons ajouter un historique du type Page modifiée
    $aPAGE_HISTO_MODIF_WORK = array(); // Tableau contenant les infos sur les pages (array({ID_PAGE}, {PST_CODE})) pour lesquelles nous devons ajouter un historique du type Workflow modifiée

    //Si pas de sélection d'un nouvel état mais que la page n'est pas "A rédiger", c'est qu'il y a bien modification du workflow
    if (!is_numeric($_POST['ID_WORKFLOW']) && ($statutPageInitiale != Page::getInitialStatut())) {
        $sqlWrkFlw = "select ID_WORKFLOW from WORKFLOW where PST_CODE_IN = " . $dbh->quote($oPage->getField('PST_CODE')) . " and PST_CODE_OUT = " . $dbh->quote(Page::getInitialStatut());
        $_POST['ID_WORKFLOW']= $dbh->query($sqlWrkFlw)->fetchColumn();
    }
    // On récupère les info associées au workflow final
    if (isset($_POST['ID_WORKFLOW']) && is_numeric($_POST['ID_WORKFLOW'])) {
        $sql = "select * from WORKFLOW where ID_WORKFLOW=" . intval($_POST['ID_WORKFLOW']);
        $rowWorkflow = $dbh->query($sql)->fetch(PDO::FETCH_ASSOC);
    }

    // Dans le cas d'une affectation du workflow aux enfants, on recupere les ids de ces enfants (dans le même état) avant toutes modifications
    if (isset($_POST['affecterWorkflowFils'])) {
        $_aChildren = array_merge($_aChildren, $oPage->getChildrenID(" and PST_CODE=" . $dbh->quote($oPage->getField('PST_CODE'))));
        $affectationFils = true;
        // Traitement particulier pour la mise "hors ligne" afin d'ordonner les enfants par ordre de niveau
        // et eviter les echecs sur les fonctions de contrôle "pre" et "post"
        if (isset($rowWorkflow) && !empty($rowWorkflow['WKF_POST_FONCTION'])) {
            if ($rowWorkflow['WKF_POST_FONCTION'] == 'post_PST_HORSLIGNE') {
                $aChildren = array(0 => array($oPage));
                $oPage->getChildrenByLevel($aChildren);
                krsort($aChildren);
                foreach ($aChildren as $k=>$ac) {
                    foreach ($ac as $pg) {
                        // On ne souhaite pas de doublon au sein des id_page
                        $i = array_search($pg->getID(), $_aChildren);
                        if ($i !== false) {
                            unset($_aChildren[$i]);
                        }
                        array_unshift($_aChildren,$pg->getID());
                    }
                }
            }
        }
    }

    //affectation du style à tous les fils
    if (isset($_POST['affecterStyleFils']) && $_POST['affecterStyleFils'] == 1) {
        $filter = (empty ($_POST['PSS_CODE'])) ? " and PSS_CODE IS NOT NULL" : " and (PSS_CODE not like ".$dbh->quote($_POST['PSS_CODE'])." or PSS_CODE IS NULL)";

        // Sélection des fils dans l'état initial (historique de type "Modification")
        $filterPST_AREDIGER = $filter . ' and PST_CODE='. $dbh->quote(Page::getInitialStatut());
        $aStyleID_PAGE['MODIFICATION_PAGE'] = $oPage->getChildrenID($filterPST_AREDIGER);
        // Sélection des fils dans l'état autre que initial (historique de type "Workflow")
        $filterPST_NOTAREDIGER = $filter . ' and PST_CODE<>'. $dbh->quote(Page::getInitialStatut());
        $aStyleID_PAGE['MODIFICATION_WORKFLOW'] = $oPage->getChildrenID($filterPST_NOTAREDIGER);
        foreach ($aStyleID_PAGE as $k => $aID_PAGE) {
            if (sizeof($aID_PAGE) > 0) {
                switch ($k) {
                    case 'MODIFICATION_PAGE' :
                        $stmt = $dbh->prepare("update OFF_PAGE set PSS_CODE=:PSS_CODE where ID_PAGE in (" . implode(',', $aID_PAGE) . ")");
                        $stmt->bindValue(':PSS_CODE', (!empty ($_POST['PSS_CODE'])) ? $_POST['PSS_CODE'] : null, PDO::PARAM_INT);
                        $stmt->execute();
                        foreach ($aID_PAGE as $id_page) {
                            // On ne souhaite pas de doublon au sein des info "Modifications"
                            if (!in_array($id_page, $aIDPAGE_HISTO_MODIF_PAGE)) {
                                    $aIDPAGE_HISTO_MODIF_PAGE[] = $id_page;
                            }
                        }
                    break;
                    case 'MODIFICATION_WORKFLOW' :
                        $stmt = $dbh->prepare("update OFF_PAGE set PSS_CODE=:PSS_CODE, PST_CODE=:PST_CODE where ID_PAGE in (" . implode(',', $aID_PAGE) . ")");
                        $stmt->bindValue(':PSS_CODE', (!empty ($_POST['PSS_CODE'])) ? $_POST['PSS_CODE'] : null, PDO::PARAM_INT);
                        $stmt->bindValue(':PST_CODE', Page::getInitialStatut(), PDO::PARAM_STR);
                        $stmt->execute();
                        foreach ($aID_PAGE as $id_page) {
                            // On ne souhaite pas de doublon au sein des info workflow
                            if (!in_array(array($id_page, Page::getInitialStatut()), $aPAGE_HISTO_MODIF_WORK)) {
                                $aPAGE_HISTO_MODIF_WORK[] = array($id_page, Page::getInitialStatut());
                            }
                        }
                    break;
                }

            }
        }
    }

    //affectation du style perso à tous les fils
    if (!empty($_POST['affecterStylePersoFils'])) {
        $filter = !is_numeric ($_POST['ID_STYLEDYNAMIQUE']) ? " and ID_STYLEDYNAMIQUE IS NOT NULL" : " and ID_STYLEDYNAMIQUE<>" . intval($_POST['ID_STYLEDYNAMIQUE']);

        // Sélection des fils dans l'état initial (historique de type "Modification")
        $filterPST_AREDIGER = $filter . ' and PST_CODE='. $dbh->quote(Page::getInitialStatut());
        $aStyleID_PAGE['MODIFICATION_PAGE'] = $oPage->getChildrenID($filterPST_AREDIGER);
        // Sélection des fils dans l'état autre que initial (historique de type "Workflow")
        $filterPST_NOTAREDIGER = $filter . ' and PST_CODE<>'. $dbh->quote(Page::getInitialStatut());
        $aStyleID_PAGE['MODIFICATION_WORKFLOW'] = $oPage->getChildrenID($filterPST_NOTAREDIGER);
        foreach ($aStyleID_PAGE as $k => $aID_PAGE) {
            if (sizeof($aID_PAGE) > 0) {
                switch ($k) {
                    case 'MODIFICATION_PAGE' :
                        $stmt = $dbh->prepare("update OFF_PAGE set ID_STYLEDYNAMIQUE=:ID_STYLEDYNAMIQUE where ID_PAGE in (" . implode(',', $aID_PAGE) . ")");
                        $stmt->bindValue(':ID_STYLEDYNAMIQUE', (is_numeric($_POST['ID_STYLEDYNAMIQUE'])) ? $_POST['ID_STYLEDYNAMIQUE'] : null, PDO::PARAM_INT);
                        $stmt->execute();
                        //à mettre à jour historique si workfow modifié pour les enfants
                        foreach ($aID_PAGE as $id_page) {
                            // On ne souhaite pas de doublon au sein des info "Modifications"
                            if (!in_array($id_page, $aIDPAGE_HISTO_MODIF_PAGE)) {
                                $aIDPAGE_HISTO_MODIF_PAGE[] = $id_page;
                            }
                        }
                        break;
                    case 'MODIFICATION_WORKFLOW' :
                        $stmt = $dbh->prepare("update OFF_PAGE set ID_STYLEDYNAMIQUE=:ID_STYLEDYNAMIQUE, PST_CODE=:PST_CODE where ID_PAGE in (" . implode(',', $aID_PAGE) . ")");
                        $stmt->bindValue(':ID_STYLEDYNAMIQUE', (is_numeric($_POST['ID_STYLEDYNAMIQUE'])) ? $_POST['ID_STYLEDYNAMIQUE'] : null, PDO::PARAM_INT);
                        $stmt->bindValue(':PST_CODE', Page::getInitialStatut(), PDO::PARAM_STR);
                        $stmt->execute();
                        //à mettre à jour historique si workfow modifié pour les enfants
                        foreach ($aID_PAGE as $id_page) {
                            // On ne souhaite pas de doublon au sein des info workflow
                            if (!in_array(array($id_page, Page::getInitialStatut()), $aPAGE_HISTO_MODIF_WORK)) {
                                $aPAGE_HISTO_MODIF_WORK[] = array($id_page, Page::getInitialStatut());
                            }
                        }
                        break;
                }
            }
        }
    }

    if ($_POST['PAG_TITRE_REFERENCEMENT'] == '') {
        $_POST['PAG_TITRE_REFERENCEMENT'] = $_POST['PAG_TITRE'];
    }
    if ($_POST['PAG_METADESCRIPTION'] == '') {
        $_POST['PAG_METADESCRIPTION'] = $_POST['PAG_ACCROCHE'];
    }
    if ($_POST['PAG_URLREWRITING'] == '') {
        $_POST['PAG_URLREWRITING'] = $_POST['PAG_TITRE_REFERENCEMENT'];
    }

    $stmt = $dbh->prepare("update OFF_PAGE set
        PGS_CODE=:PGS_CODE,
        PSS_CODE=:PSS_CODE,
        ID_STYLEDYNAMIQUE=:ID_STYLEDYNAMIQUE,
        PAG_TITRE=:PAG_TITRE,
        PAG_TITRE_MENU=:PAG_TITRE_MENU,
        PAG_URLREWRITING = :PAG_URLREWRITING,
        PAG_TITRE_REFERENCEMENT=:PAG_TITRE_REFERENCEMENT,
        PAG_TITLE=:PAG_TITLE,
        PAG_VISIBLE_MENU=:PAG_VISIBLE_MENU,
        PAG_MASQUERGAUCHE=:PAG_MASQUERGAUCHE,
        PAG_MASQUERDROITE=:PAG_MASQUERDROITE,
        PAG_EXCLURECHERCHE=:PAG_EXCLURECHERCHE,
        PAG_GOOGLEFREQUENCE=:PAG_GOOGLEFREQUENCE,
        PAG_GOOGLEPRIORITE=:PAG_GOOGLEPRIORITE,
        PAG_NOINDEX=:PAG_NOINDEX,
        PAG_ACCROCHE=:PAG_ACCROCHE,
        PAG_METADESCRIPTION=:PAG_METADESCRIPTION,
        PAG_HEAD=:PAG_HEAD,
        PAG_MOTCLE1=:PAG_MOTCLE1,
        PAG_MOTCLE2=:PAG_MOTCLE2,
        PAG_MOTCLE3=:PAG_MOTCLE3,
        PAG_MOTCLE4=:PAG_MOTCLE4,
        PAG_MOTCLE5=:PAG_MOTCLE5,
        PAG_DATEONLINE=:PAG_DATEONLINE,
        PAG_DATEOFFLINE=:PAG_DATEOFFLINE,
        PAG_COMMENTAIREACTIF=:PAG_COMMENTAIREACTIF,
        PAG_STYLEDEFAUTHERITABLE=:PAG_STYLEDEFAUTHERITABLE,
        PAG_STYLEPERSOHERITABLE=:PAG_STYLEPERSOHERITABLE,
        PAG_HTTPS=:PAG_HTTPS,
        PAG_CACHE=:PAG_CACHE,
        PAG_DATEMISEAJOUR=:PAG_DATEMISEAJOUR
        where ID_PAGE=:idtf");
    $stmt->bindValue(':PGS_CODE', (!empty ($_POST['PGS_CODE'])) ? $_POST['PGS_CODE'] : null, PDO::PARAM_STR);
    $stmt->bindValue(':PSS_CODE', (!empty ($_POST['PSS_CODE'])) ? $_POST['PSS_CODE'] : null, PDO::PARAM_STR);
    $stmt->bindValue(':ID_STYLEDYNAMIQUE', (is_numeric($_POST['ID_STYLEDYNAMIQUE'])) ? $_POST['ID_STYLEDYNAMIQUE'] : null, PDO::PARAM_INT);
    $stmt->bindValue(':PAG_TITRE', $_POST['PAG_TITRE'], PDO::PARAM_STR);
    $stmt->bindValue(':PAG_TITRE_MENU', $_POST['PAG_TITRE_MENU'], PDO::PARAM_STR);
    $stmt->bindValue(':PAG_URLREWRITING', filenameToRfc1738(mb_convert_case(trim($_POST['PAG_URLREWRITING']), MB_CASE_LOWER)), PDO::PARAM_STR);
    $stmt->bindValue(':PAG_TITRE_REFERENCEMENT', $_POST['PAG_TITRE_REFERENCEMENT'], PDO::PARAM_STR);
    $stmt->bindValue(':PAG_TITLE', $_POST['PAG_TITLE'], PDO::PARAM_STR);
    $stmt->bindValue(':PAG_VISIBLE_MENU', !empty($_POST['PAG_VISIBLE_MENU']), PDO::PARAM_INT);
    $stmt->bindValue(':PAG_MASQUERGAUCHE', !empty($_POST['PAG_MASQUERGAUCHE']), PDO::PARAM_INT);
    $stmt->bindValue(':PAG_MASQUERDROITE', !empty($_POST['PAG_MASQUERDROITE']), PDO::PARAM_INT);
    $stmt->bindValue(':PAG_EXCLURECHERCHE', !empty($_POST['PAG_EXCLURECHERCHE']), PDO::PARAM_INT);
    $stmt->bindValue(':PAG_GOOGLEFREQUENCE', $_POST['PAG_GOOGLEFREQUENCE'], PDO::PARAM_STR);
    $stmt->bindValue(':PAG_GOOGLEPRIORITE', $_POST['PAG_GOOGLEPRIORITE'], PDO::PARAM_STR);
    $stmt->bindValue(':PAG_NOINDEX', !empty($_POST['PAG_NOINDEX']), PDO::PARAM_INT);
    $stmt->bindValue(':PAG_ACCROCHE', $_POST['PAG_ACCROCHE'], PDO::PARAM_STR);
    $stmt->bindValue(':PAG_METADESCRIPTION', $_POST['PAG_METADESCRIPTION'], PDO::PARAM_STR);
    $stmt->bindValue(':PAG_HEAD', $_POST['PAG_HEAD'], PDO::PARAM_STR);
    $stmt->bindValue(':PAG_MOTCLE1', $_POST['PAG_MOTCLE1'], PDO::PARAM_STR);
    $stmt->bindValue(':PAG_MOTCLE2', $_POST['PAG_MOTCLE2'], PDO::PARAM_STR);
    $stmt->bindValue(':PAG_MOTCLE3', $_POST['PAG_MOTCLE3'], PDO::PARAM_STR);
    $stmt->bindValue(':PAG_MOTCLE4', $_POST['PAG_MOTCLE4'], PDO::PARAM_STR);
    $stmt->bindValue(':PAG_MOTCLE5', $_POST['PAG_MOTCLE5'], PDO::PARAM_STR);
    $stmt->bindValue(':PAG_DATEONLINE', unixtime($_POST['PAG_DATEONLINE']), PDO::PARAM_INT);
    $stmt->bindValue(':PAG_DATEOFFLINE', unixtime($_POST['PAG_DATEOFFLINE']), PDO::PARAM_INT);
    $stmt->bindValue(':PAG_COMMENTAIREACTIF', (isset($_POST['PAG_COMMENTAIREACTIF']))? $_POST['PAG_COMMENTAIREACTIF'] : 0, PDO::PARAM_INT);
    $stmt->bindValue(':PAG_STYLEDEFAUTHERITABLE', (empty($_POST['affecterStyleFils']))? 0 : $_POST['affecterStyleFils'], PDO::PARAM_INT);
    $stmt->bindValue(':PAG_STYLEPERSOHERITABLE', (empty($_POST['affecterStylePersoFils']))? 0 : $_POST['affecterStylePersoFils'], PDO::PARAM_INT);
    $stmt->bindValue(':PAG_HTTPS', !empty($_POST['PAG_HTTPS']), PDO::PARAM_INT);
    $stmt->bindValue(':PAG_CACHE', !empty($_POST['PAG_CACHE']), PDO::PARAM_INT);
    $stmt->bindValue(':PAG_DATEMISEAJOUR', unixtime($_POST['PAG_DATEMISEAJOUR']), PDO::PARAM_INT);//peut être vide donc null
    $stmt->bindValue(':idtf', $_POST['idtf'], PDO::PARAM_INT);
    $stmt->execute();

    Link::delete('OFF_PAGE', $_POST['idtf']);

    if (is_array($_POST['ID_THEMATIQUE']) && count($_POST['ID_THEMATIQUE'])>0) {
        foreach ($_POST['ID_THEMATIQUE'] as $ID_THEMATIQUE) {
            Link::insertThematique('OFF_PAGE', $_POST['idtf'], $ID_THEMATIQUE);
        }
    }

    // Affectation des dates à l'ensemble des enfants
    if (isset($_POST['affecterDateFils'])) {
        // Sélection des fils dans l'état initial (historique de type "Modification")
        $filterPST_AREDIGER = ' and PST_CODE='. $dbh->quote(Page::getInitialStatut());
        $aDateID_PAGE['MODIFICATION_PAGE'] = $oPage->getChildrenID($filterPST_AREDIGER);
        // Sélection des fils dans l'état autre que initial (historique de type "Workflow")
        $filterPST_NOTAREDIGER = ' and PST_CODE<>'. $dbh->quote(Page::getInitialStatut());
        $aDateID_PAGE['MODIFICATION_WORKFLOW'] = $oPage->getChildrenID($filterPST_NOTAREDIGER);
        foreach ($aDateID_PAGE as $k => $aID_PAGE) {
            if (sizeof($aID_PAGE) > 0) {
                switch ($k) {
                    case 'MODIFICATION_PAGE' :
                        $stmt = $dbh->prepare("update OFF_PAGE set PAG_DATEONLINE=:PAG_DATEONLINE, PAG_DATEOFFLINE=:PAG_DATEOFFLINE where ID_PAGE in (" . implode(',', $aID_PAGE) . ")");
                        $stmt->bindValue(':PAG_DATEONLINE', unixtime($_POST['PAG_DATEONLINE']), PDO::PARAM_INT);
                        $stmt->bindValue(':PAG_DATEOFFLINE', unixtime($_POST['PAG_DATEOFFLINE']), PDO::PARAM_INT);
                        $stmt->execute();
                        foreach ($aID_PAGE as $id_page) {
                            // On ne souhaite pas de doublon au sein des info "Modifications"
                            if (!in_array($id_page, $aIDPAGE_HISTO_MODIF_PAGE)) {
                                $aIDPAGE_HISTO_MODIF_PAGE[] = $id_page;
                            }
                        }
                    break;
                    case 'MODIFICATION_WORKFLOW' :
                        $stmt = $dbh->prepare("update OFF_PAGE set PAG_DATEONLINE=:PAG_DATEONLINE, PAG_DATEOFFLINE=:PAG_DATEOFFLINE, PST_CODE=:PST_CODE where ID_PAGE in (" . implode(',', $aID_PAGE) . ")");
                        $stmt->bindValue(':PAG_DATEONLINE', unixtime($_POST['PAG_DATEONLINE']), PDO::PARAM_INT);
                        $stmt->bindValue(':PAG_DATEOFFLINE', unixtime($_POST['PAG_DATEOFFLINE']), PDO::PARAM_INT);
                        $stmt->bindValue(':PST_CODE', Page::getInitialStatut(), PDO::PARAM_STR);
                        $stmt->execute();
                        foreach ($aID_PAGE as $id_page) {
                            // On ne souhaite pas de doublon au sein des info workflow
                            if (!in_array(array($id_page, Page::getInitialStatut()), $aPAGE_HISTO_MODIF_WORK)) {
                                $aPAGE_HISTO_MODIF_WORK[] = array($id_page, Page::getInitialStatut());
                            }
                        }
                    break;
                }
            }
        }
    }

    // Mise à jour des groupes éventuels
    $aInfoHistorique = traitementCommun($oPage);
    // Traitement des simples modifications
    if (!empty($aInfoHistorique['MODIFICATION_PAGE'])) {
        foreach ($aInfoHistorique['MODIFICATION_PAGE'] as $id_page) {
            // On ne souhaite pas de doublon au sein des info "Modifications"
            if (!in_array($id_page, $aIDPAGE_HISTO_MODIF_PAGE)) {
                $aIDPAGE_HISTO_MODIF_PAGE[] = $id_page;
            }
        }
    }
    // Traitement des modifications sur les sorkflow
    if (!empty($aInfoHistorique['MODIFICATION_WORKFLOW'])) {
        foreach ($aInfoHistorique['MODIFICATION_WORKFLOW'] as $id_page) {
            // On ne souhaite pas de doublon au sein des info workflow :
            // Pour chacun des élément mis à jour, on check si une précédente ré-initialisation du statut est déjà présente (mise àjour des styles, dates, etc)
            // Si c'est le cas, on n'insère pas la nouvelle ré-initialisation dudit satut
            if (!in_array(array($id_page, Page::getInitialStatut()), $aPAGE_HISTO_MODIF_WORK)) {
                $aPAGE_HISTO_MODIF_WORK[] = array($id_page, Page::getInitialStatut());
            }
        }
    }

    $msgErrorWorkflow = '';
    //changement d'etat (à faire après l'update)
    if (isset($rowWorkflow) && !empty($rowWorkflow)) {
        /***modification workflow***/
        $aInfoWorkflow = process_workflow($oPage, $_aChildren, $rowWorkflow, $affectationFils, $msgErrorWorkflow);
        if (!empty($aInfoWorkflow)) {
            // Pour chacun des élément mis à jour, on check :
            // * Si une précédente ré-initialisation du statut n'est pas déjà présente (mise àjour des styles, dates, etc)
            // * Si une modification du statut identique est déjà présente
            // Si c'est le cas, on retire cette précédente info non souhaitée
            foreach ($aInfoWorkflow as $aOneInfoWorkflow) {
                $i = array_search(array($aOneInfoWorkflow[0], Page::getInitialStatut()), $aPAGE_HISTO_MODIF_WORK);
                if ($i !== false) {
                    unset($aPAGE_HISTO_MODIF_WORK[$i]);
                }
                $i = array_search(array($aOneInfoWorkflow[0], $aOneInfoWorkflow[1]), $aPAGE_HISTO_MODIF_WORK);
                if ($i !== false) {
                    unset($aPAGE_HISTO_MODIF_WORK[$i]);
                }
            }
            $aPAGE_HISTO_MODIF_WORK = array_merge($aPAGE_HISTO_MODIF_WORK,$aInfoWorkflow);
        }
    // Si pas de Workflow, c'est que la statut initial est "A rédiger" ($statutPageInitiale == Page::getInitialStatut())
    // et que l'état suivant est identique
    // (pas de changement du workflow mais seulement modification des pages sur les dates)
    } else {
        //affectation des dates uniquement à tous les enfants
        if (sizeof($_aChildren) > 0) {
            $stmt = $dbh->prepare("update OFF_PAGE set PAG_DATEONLINE=:PAG_DATEONLINE, PAG_DATEOFFLINE=:PAG_DATEOFFLINE where ID_PAGE in (" . implode(',', $_aChildren) . ")");
            $stmt->bindValue(':PAG_DATEONLINE', unixtime($_POST['PAG_DATEONLINE']), PDO::PARAM_INT);
            $stmt->bindValue(':PAG_DATEOFFLINE', unixtime($_POST['PAG_DATEOFFLINE']), PDO::PARAM_INT);
            $stmt->execute();
            foreach ($_aChildren as $idChild) {
                $aIDPAGE_HISTO_MODIF_PAGE[] = $idChild;
            }
        }
    }

    //* Traitement des historiques
    $aIDPAGE_HISTO_DATEMODIF = array();
    // Modifications sur les workflows
    if (!empty($aPAGE_HISTO_MODIF_WORK)) {
        foreach ($aPAGE_HISTO_MODIF_WORK as $aInfoWorkflow) {
            if (empty($aInfoWorkflow)) continue;
            $aIDPAGE_HISTO_DATEMODIF[] = $aInfoWorkflow[0];
            $oPageHisto = new Page($aInfoWorkflow[0]);
            $oPageHisto->historize('MODIFICATION', 'WORKFLOW', $aInfoWorkflow[1]);
        }
    }
    // On ne met à jour l'historique de modification des pages que sur les éléments dont le workflow n'est pas mis à jour
    if (!empty($aIDPAGE_HISTO_MODIF_PAGE)) {
        $aIDPAGE_HISTO_MODIF_PAGE = array_unique($aIDPAGE_HISTO_MODIF_PAGE);
        if (!empty($aIDPAGE_HISTO_DATEMODIF)) {
            $aIDPAGE_HISTO_MODIF_PAGE = array_diff($aIDPAGE_HISTO_MODIF_PAGE, $aIDPAGE_HISTO_DATEMODIF);
        }
        foreach ($aIDPAGE_HISTO_MODIF_PAGE as $ID_PAGE) {
            if (empty($ID_PAGE)) continue;
            $aIDPAGE_HISTO_DATEMODIF[] = $ID_PAGE;
            $oPageHisto = new Page($ID_PAGE);
            $oPageHisto->historize('MODIFICATION', 'PAGE');
        }
    }

    if (!empty($aIDPAGE_HISTO_DATEMODIF)) {
        $aIDPAGE_HISTO_DATEMODIF = array_unique($aIDPAGE_HISTO_DATEMODIF);
        foreach ($aIDPAGE_HISTO_DATEMODIF as $ID_PAGE) {
            if (empty($ID_PAGE)) continue;
            $oPageHisto = new Page($ID_PAGE);
            $oPageHisto->setDateModification();
        }
    }
    // FIN Traitement des historiques */

    Utilisateur::getConnected()->initSession(CMS::getCurrentSite()->getID(), false);

    $msgErrorUrlAlt = '';
    if (!$oPage->setURLAlternative($_POST['URA_LIBELLE'])) {
        $msgErrorUrlAlt .= 'Une Url alternative n\'est pas valide.<br>';

    }

    if (!empty($msgErrorWorkflow) || !empty($msgErrorUrlAlt)) {
        setMsg($msgErrorWorkflow.$msgErrorUrlAlt, 'ERROR');
    } else {
        setMsg(gettext('UPDATE_OK'));
    }
    header('Location:' . SERVER_ROOT . 'cms/cms_page.php?idtf=' . $_POST['idtf']);
    exit ();

} elseif (is_numeric($_GET['ID_WORKFLOW'])) {
    $oPage = new Page($_GET['idtf']);
    $oPage->checkAuthorized();
    $oPage->lock();

    $sql = "select * from WORKFLOW where ID_WORKFLOW=" . intval($_GET['ID_WORKFLOW']);
    $rowWorkflow = $dbh->query($sql)->fetch(PDO::FETCH_ASSOC);

    // Modification de la date de mise à jour si mise en ligne
    if (isset($_GET['majDate']) && $rowWorkflow['PST_CODE_OUT'] == 'PST_ENLIGNE') {
        $sql = "update OFF_PAGE set PAG_DATEMISEAJOUR=" . time() . " where ID_PAGE=" . $oPage->getID();
        $dbh->exec($sql);
    }

    $modified = false;
    if ($rowWorkflow['WKF_POST_FONCTION'] != '') {
        if (!call_user_func($rowWorkflow['WKF_POST_FONCTION'], $oPage, $rowWorkflow['PST_CODE_OUT'], false)) {
            $typeNotification = $_GET['from']=='cms_page.php'?'ERROR':'NOTIFICATION_PSEUDO';
            if ($rowWorkflow['WKF_POST_FONCTION'] == 'post_PST_ENLIGNE') {
                setMsg(gettext('workflow_erreur_pre_enligne'), $typeNotification);
            } elseif ($rowWorkflow['WKF_POST_FONCTION'] == 'post_PST_HORSLIGNE') {
                setMsg(gettext('workflow_erreur_pre_horsligne'), $typeNotification);
            } else {
                setMsg('Unknown Error : Failed call_user_func in cms_pageSubmit. Please Contact your site administrator.', $typeNotification);
            }
        } else {
            $modified = true;
            // Purge du cache de l'ensemble des sites
            Page::clearAllCache();
        }
    } else if ($rowWorkflow['PST_CODE_OUT'] != '') {
        $sql = "update OFF_PAGE set PST_CODE=" . $dbh->quote($rowWorkflow['PST_CODE_OUT']) . " where ID_PAGE=" . $oPage->getID();
        $dbh->exec($sql);
        $modified = true;
    }

    if ($modified) {
        Utilisateur::getConnected()->initSession(CMS::getCurrentSite()->getID(), false);
        $oPage->setDateModification();
        $oPage->historize('MODIFICATION', 'WORKFLOW', $rowWorkflow['PST_CODE_OUT']);
        if ($_GET['from']=='cms_page.php') {
            setMsg(gettext('UPDATE_OK'), 'NOTIFICATION');
        }
    }

    header('Location:' . SERVER_ROOT . 'cms/' . $_GET['from'] . '?idtf=' . $_GET['idtf']);
    exit ();

} elseif (isset ($_POST['UpdateLight'])) {
    $oPage = new Page($_POST['idtf']);
    $oPage->checkAuthorized();
    $oPage->lock();

    if ($_POST['PAG_TITRE_REFERENCEMENT'] == '') {
        $_POST['PAG_TITRE_REFERENCEMENT'] = $_POST['PAG_TITRE'];
    }
    if ($_POST['PAG_METADESCRIPTION'] == '') {
        $_POST['PAG_METADESCRIPTION'] = $_POST['PAG_ACCROCHE'];
    }

    $stmt = $dbh->prepare("update OFF_PAGE set
        PAG_TITRE=:PAG_TITRE,
        PAG_TITRE_MENU=:PAG_TITRE_MENU,
        PAG_TITRE_REFERENCEMENT=:PAG_TITRE_REFERENCEMENT,
        PAG_TITLE=:PAG_TITLE,
        PAG_VISIBLE_MENU=:PAG_VISIBLE_MENU,
        PAG_MASQUERGAUCHE=:PAG_MASQUERGAUCHE,
        PAG_MASQUERDROITE=:PAG_MASQUERDROITE,
        PAG_ACCROCHE=:PAG_ACCROCHE,
        PAG_METADESCRIPTION=:PAG_METADESCRIPTION
        where ID_PAGE=:idtf");
    $stmt->bindValue(':PAG_TITRE', $_POST['PAG_TITRE'], PDO::PARAM_STR);
    $stmt->bindValue(':PAG_TITRE_MENU', $_POST['PAG_TITRE_MENU'], PDO::PARAM_STR);
    $stmt->bindValue(':PAG_TITRE_REFERENCEMENT', $_POST['PAG_TITRE_REFERENCEMENT'], PDO::PARAM_STR);
    $stmt->bindValue(':PAG_TITLE', $_POST['PAG_TITLE'], PDO::PARAM_STR);
    $stmt->bindValue(':PAG_VISIBLE_MENU', $_POST['PAG_VISIBLE_MENU'], PDO::PARAM_INT);
    $stmt->bindValue(':PAG_MASQUERGAUCHE', $_POST['PAG_MASQUERGAUCHE'], PDO::PARAM_INT);
    $stmt->bindValue(':PAG_MASQUERDROITE', $_POST['PAG_MASQUERDROITE'], PDO::PARAM_INT);
    $stmt->bindValue(':PAG_ACCROCHE', $_POST['PAG_ACCROCHE'], PDO::PARAM_STR);
    $stmt->bindValue(':PAG_METADESCRIPTION', $_POST['PAG_METADESCRIPTION'], PDO::PARAM_STR);
    $stmt->bindValue(':idtf', $_POST['idtf'], PDO::PARAM_INT);
    $stmt->execute();

    //changement d'etat (à faire après l'update)
    if ($oPage->getField('PST_CODE') != Page::getInitialStatut()) {
        $oPage->resetStatut();
        $oPage->historize('MODIFICATION', 'WORKFLOW', Page::getInitialStatut());
    } else {
        $oPage->historize('MODIFICATION', 'PAGE');
    }
    $oPage->setDateModification();

    echo '<script>window.opener.location.href="cms_pseudo.php?idtf=' . $_POST['idtf'] . '&PFM=' . $_POST['PFM'] . '"; window.close();</script>';
    exit ();
} elseif (is_numeric($_GET['Delete'])) {
    $oPage = new Page($_GET['Delete']);
    $oPage->checkAuthorized();
    $oPage->lock();
    $oPage->delete();

    Utilisateur::getConnected()->initSession(CMS::getCurrentSite()->getID(), false);

    header('Location:' . SERVER_ROOT . 'cms/cms_pageArbo.php');
    exit ();
}
/**
 * Mise à jour des informations sur les groupes lorsque le module extranet est actif
 *
 * @param Page $oPage Page sur laquelle appliquer les informations associées aux groupes
 *
 * @return array $aInfoHistorique tableau contenant les id_pages impacté par des modifications ou un changement de statut ($aInfoHistorique = array('MODIFICATION_PAGE' => array(), 'MODIFICATION_WORKFLOW' => array());)
 */
function traitementCommun($oPage)
{
    $dbh = DB::getInstance();
    $aInfoHistorique = array('MODIFICATION_PAGE' => array(), 'MODIFICATION_WORKFLOW' => array());

    if (CMS::getCurrentSite()->hasModule(new Module('MOD_EXTRANET'))) {
        $tabInsert = $tabDelete = array ($oPage->getID());
        if ($_POST['affectationFils']) {
            $tabInsert = array_merge($tabInsert, $oPage->getChildrenID());
            if ($_POST['affectationFils'] == 'REPLACE') {
                $tabDelete = $tabInsert;
            }
        }
        $sql = "delete from GROUPE_OFF_PAGE where ID_PAGE in (" . implode(',', $tabDelete) . ")";
        $dbh->exec($sql);
        if (is_array($_POST['ID_GROUPE'])) {
            if ($_POST['affectationFils'] == 'ADD') {
                $sql = "delete from GROUPE_OFF_PAGE where ID_PAGE in (" . implode(',', $tabInsert) . ") and ID_GROUPE in (" . implode(',', array_map('intval', $_POST['ID_GROUPE'])) . ")";
                $dbh->exec($sql);
            }
            $stmt = $dbh->prepare("insert into GROUPE_OFF_PAGE (
                ID_GROUPE, ID_PAGE
                ) values (
                :ID_GROUPE, :ID_PAGE)");
            foreach ($tabInsert as $ID_PAGE) {
                $stmt->bindValue(':ID_PAGE', $ID_PAGE, PDO::PARAM_INT);
                foreach ($_POST['ID_GROUPE'] as $ID_GROUPE) {
                    $stmt->bindValue(':ID_GROUPE', $ID_GROUPE, PDO::PARAM_INT);
                    $stmt->execute();
                }
            }
        }

        // Gestion de l'historique et des modifications du workflow sur les éventuelles pages enfants (!!pas la page courante!!)
        if ($_POST['affectationFils']) {
            $sql = 'select ID_PAGE from OFF_PAGE where ID_PAGE<>'.$dbh->quote($oPage->getID()).' and ID_PAGE in (' . implode(',', $tabInsert) . ') and PST_CODE = '. $dbh->quote(Page::getInitialStatut());
            $aTCID_PAGE['MODIFICATION_PAGE'] = $dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN);
            $sql = 'select ID_PAGE from OFF_PAGE where ID_PAGE<>'.$dbh->quote($oPage->getID()).' and ID_PAGE in (' . implode(',', $tabInsert) . ') and PST_CODE <> '. $dbh->quote(Page::getInitialStatut());
            $aTCID_PAGE['MODIFICATION_WORKFLOW'] = $dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($aTCID_PAGE['MODIFICATION_PAGE'])) {
                $aInfoHistorique['MODIFICATION_PAGE'] = $aTCID_PAGE['MODIFICATION_PAGE'];
            }
            if (!empty($aTCID_PAGE['MODIFICATION_WORKFLOW'])) {
                $stmt = $dbh->prepare("update OFF_PAGE set PST_CODE=:PST_CODE where ID_PAGE in (" . implode(',', $aTCID_PAGE['MODIFICATION_WORKFLOW']) . ") and ID_PAGE<>:ID_PAGE");
                $stmt->bindValue(':PST_CODE', Page::getInitialStatut(), PDO::PARAM_STR);
                $stmt->bindValue(':ID_PAGE', $oPage->getID(), PDO::PARAM_INT);
                $stmt->execute();
                $aInfoHistorique['MODIFICATION_WORKFLOW'] = $aTCID_PAGE['MODIFICATION_WORKFLOW'];
            }
        }
    }
    return $aInfoHistorique;
}
/**
 * Fonction de mise à jour des worklows d'une ou plusieurs pages
 * @param Page  $oPage            Objet page sur lequel appliquer la modification du workflow
 * @param array $_aChildren       Tableau des identifiant des pages enfant auquel appliquer également la mise à jour
 * @param array $rowWorkflow      Tableau contenant les infos associées au workflow à appliquer
 * @param bool  $affectationFils  Bolléen précisant si le workflow doit s'appliquer aux enfants
 * @param strin $msgErrorWorkflow Message d'erreur éventuel passé par référence lors de la modification du workflow
 *
 * @return array $aInfoWorkflow Tableau contenant les info sur l'historique des modifications des workflows des pages
 */
function process_workflow($oPage, $_aChildren, $rowWorkflow, $affectationFils = false, &$msgErrorWorkflow)
{
    $dbh = DB::getInstance();
    $aInfoWorkflow = array();
    //S'il y a un fonction pour la mise a jour du workflow
    if ($rowWorkflow['WKF_POST_FONCTION'] != '') {
        $aPageAModifier = array();

        foreach ($_aChildren as $id) {
            $oPageChild = new Page($id);

            $doPRETESTFONCTION = true;

            if ($rowWorkflow['WKF_PRE_FONCTION'] != '') {

                if ($rowWorkflow['WKF_PRE_FONCTION'] == 'pre_PST_ENLIGNE') {
                    $doPRETESTFONCTION = call_user_func($rowWorkflow['WKF_PRE_FONCTION'], $oPageChild, $_aChildren, unixtime($_POST['PAG_DATEONLINE']) );
                } elseif ($rowWorkflow['WKF_PRE_FONCTION'] == 'pre_PST_HORSLIGNE') {
                    $doPRETESTFONCTION = call_user_func($rowWorkflow['WKF_PRE_FONCTION'], $oPageChild, $affectationFils, unixtime($_POST['PAG_DATEONLINE']) );
                } else {
                    $doPRETESTFONCTION = "UNKNOWN_FUNC";
                }

            }

            if ($doPRETESTFONCTION) {
                //on fait pas l'appel a |call_user_func($rowWorkflow['WKF_POST_FONCTION'], $oPageChild, $rowWorkflow['PST_CODE_OUT'], $affectationFils);| tout de suite
                //afin de ne pas modifier une partie des pages filles seulement
                $aPageAModifier[] = $oPageChild;
            } else {
                //message erreur associé
                switch ($rowWorkflow['PST_CODE_OUT']) {
                    case 'PST_ENLIGNE':
                      $msgErrorWorkflow = gettext('workflow_erreur_pre_enligne');
                      $aPageAModifier = array();
                    break;

                    case 'PST_HORSLIGNE':
                      $msgErrorWorkflow = gettext('workflow_erreur_pre_horsligne');
                      $aPageAModifier = array();
                    break;

                    default:
                        $msgErrorWorkflow = 'Erreur non identifié. Veuillez contacter votre administrateur réseau.';
                        $aPageAModifier = array();
                    break;
                }
                break; //break from for loop - on ne vas plus en avant, la sauvegarde etat ne peut etre faits
            }
        }

        if (count($aPageAModifier) > 0) {
            //affectation des dates à tous les enfants
            $aPagesDateModify = array();
            foreach ($aPageAModifier as $unePageAModifier) {
                $aPagesDateModify[] = $unePageAModifier->getID();
            }
            $stmt = $dbh->prepare("update OFF_PAGE set PAG_DATEONLINE=:PAG_DATEONLINE, PAG_DATEOFFLINE=:PAG_DATEOFFLINE where ID_PAGE in (" . implode(',', $aPagesDateModify) . ")");
            $stmt->bindValue(':PAG_DATEONLINE', unixtime($_POST['PAG_DATEONLINE']), PDO::PARAM_INT);
            $stmt->bindValue(':PAG_DATEOFFLINE', unixtime($_POST['PAG_DATEOFFLINE']), PDO::PARAM_INT);
            $stmt->execute();

            $maj = false;
            if ($rowWorkflow['PST_CODE_OUT'] == 'PST_HORSLIGNE') {
                $maj = $affectationFils; // Pour la mise hors ligne, le 3em param est l'affectation ou non aux enfants
            }
            //maj workflow
            foreach ($aPageAModifier as $unePageAModifier) {
                call_user_func($rowWorkflow['WKF_POST_FONCTION'], $unePageAModifier, $rowWorkflow['PST_CODE_OUT'], $maj, true);
                $aInfoWorkflow[] = array($unePageAModifier->getID(), $rowWorkflow['PST_CODE_OUT']);
            }
            // Purge du cache de l'ensemble des sites
            Page::clearAllCache();
        }
    } else {
        //SI pas de fonction specifique pour la mise a jour du workflow
        //affectation des dates et etat à tous les enfants
        if (sizeof($_aChildren) > 0) {
            $sqlUpdate = "update OFF_PAGE set PAG_DATEONLINE=".unixtime($_POST['PAG_DATEONLINE']).", PAG_DATEOFFLINE=".unixtime($_POST['PAG_DATEOFFLINE']).", PST_CODE =".$dbh->quote($rowWorkflow['PST_CODE_OUT'])." where ID_PAGE in (" . implode(',', $_aChildren) . ")";
            $dbh->exec($sqlUpdate);

            foreach ($_aChildren as $idChild) {
                $aInfoWorkflow[] = array($idChild, $rowWorkflow['PST_CODE_OUT']);
            }
        }
    }
    return $aInfoWorkflow;
}
