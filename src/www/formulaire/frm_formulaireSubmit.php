<?php
require '../include/inc.bo_init.php';
require CLASS_DIR . 'class.Editor.php';
CMS::checkAccess(new Module('MOD_FORMULAIRE'), array ('PRO_FORMGEST'));

require CLASS_DIR . 'class.db_formulaire.php';
require_once CLASS_DIR . 'class.db_page.php';

if (isset ($_POST['Insert'])) {
    $stmt = $dbh->prepare("insert into FORMULAIRE (
        SIT_CODE,
        ID_FORMULAIRECATEGORIE,
        FRM_LIBELLE,
        FRM_LIBELLE_BOUTON,
        FRM_TRACABLE,
        FRM_TRACEIP,
        FRM_NOTIFICATION,
        FRM_NOTIFICATION_EMAIL,
        FRM_MENTION_CNIL,
        FRM_MESSAGEREPONSE,
        FRM_EXPEDITEUR_NOM,
        FRM_EXPEDITEUR_EMAIL
        ) values (
        :SIT_CODE,
        :ID_FORMULAIRECATEGORIE,
        :FRM_LIBELLE,
        :FRM_LIBELLE_BOUTON,
        :FRM_TRACABLE,
        :FRM_TRACEIP,
        :FRM_NOTIFICATION,
        :FRM_NOTIFICATION_EMAIL,
        :FRM_MENTION_CNIL,
        :FRM_MESSAGEREPONSE,
        :FRM_EXPEDITEUR_NOM,
        :FRM_EXPEDITEUR_EMAIL
        )");
    $stmt->bindValue(':SIT_CODE', CMS::getCurrentSite()->getID(), PDO::PARAM_STR);
    $stmt->bindValue(':ID_FORMULAIRECATEGORIE', is_numeric($_POST['ID_FORMULAIRECATEGORIE']) ? $_POST['ID_FORMULAIRECATEGORIE'] : null, PDO::PARAM_INT);
    $stmt->bindValue(':FRM_LIBELLE', $_POST['FRM_LIBELLE'], PDO::PARAM_STR);
    $stmt->bindValue(':FRM_LIBELLE_BOUTON', gettext('Envoyer'), PDO::PARAM_STR);
    $stmt->bindValue(':FRM_TRACABLE', intval($_POST['FRM_TRACABLE']), PDO::PARAM_INT);
    $stmt->bindValue(':FRM_TRACEIP', intval($_POST['FRM_TRACEIP']), PDO::PARAM_INT);
    $stmt->bindValue(':FRM_NOTIFICATION', $_POST['FRM_NOTIFICATION'], PDO::PARAM_INT);
    $stmt->bindValue(':FRM_NOTIFICATION_EMAIL', $_POST['FRM_NOTIFICATION_EMAIL'], PDO::PARAM_STR);
    $stmt->bindValue(':FRM_MENTION_CNIL', $_POST['FRM_MENTION_CNIL'], PDO::PARAM_INT);
    $stmt->bindValue(':FRM_MESSAGEREPONSE', $_POST['FRM_MESSAGEREPONSE'], PDO::PARAM_STR);
    $stmt->bindValue(':FRM_EXPEDITEUR_NOM', $_POST['FRM_EXPEDITEUR_NOM'], PDO::PARAM_STR);
    $stmt->bindValue(':FRM_EXPEDITEUR_EMAIL', $_POST['FRM_EXPEDITEUR_EMAIL'], PDO::PARAM_STR);
    $stmt->execute();
    $oFormulaire = new formulaire($dbh->lastInsertID());
    $oFormulaire->historize('CREATION', 'FORMULAIRE');
    _traitementCommun($oFormulaire->getID());
    setMsg(gettext('INSERT_OK'));
    header('Location:' . SERVER_ROOT . 'formulaire/frm_formulaire.php?idtf=' . $oFormulaire->getID());
    exit ();

} elseif (isset ($_POST['Update'])) {
    $oFormulaire = new Formulaire($_POST['idtf']);
    $oFormulaire->checkAuthorized();
    $stmt = $dbh->prepare("update FORMULAIRE set
        ID_FORMULAIRECATEGORIE = :ID_FORMULAIRECATEGORIE,
        FRM_LIBELLE = :FRM_LIBELLE,
        FRM_TRACABLE = :FRM_TRACABLE,
        FRM_TRACEIP = :FRM_TRACEIP,
        FRM_NOTIFICATION = :FRM_NOTIFICATION,
        FRM_NOTIFICATION_EMAIL = :FRM_NOTIFICATION_EMAIL,
        FRM_MENTION_CNIL = :FRM_MENTION_CNIL,
        FRM_MESSAGEREPONSE = :FRM_MESSAGEREPONSE,
        FRM_EXPEDITEUR_NOM = :FRM_EXPEDITEUR_NOM,
        FRM_EXPEDITEUR_EMAIL = :FRM_EXPEDITEUR_EMAIL
        where ID_FORMULAIRE = :idtf and SIT_CODE=:SIT_CODE");
    $stmt->bindValue(':ID_FORMULAIRECATEGORIE', is_numeric($_POST['ID_FORMULAIRECATEGORIE']) ? $_POST['ID_FORMULAIRECATEGORIE'] : null, PDO::PARAM_INT);
    $stmt->bindValue(':FRM_LIBELLE', $_POST['FRM_LIBELLE'], PDO::PARAM_STR);
    $stmt->bindValue(':FRM_TRACABLE', intval($_POST['FRM_TRACABLE']), PDO::PARAM_INT);
    $stmt->bindValue(':FRM_TRACEIP', intval($_POST['FRM_TRACEIP']), PDO::PARAM_INT);
    $stmt->bindValue(':FRM_NOTIFICATION', $_POST['FRM_NOTIFICATION'], PDO::PARAM_INT);
    $stmt->bindValue(':FRM_NOTIFICATION_EMAIL', $_POST['FRM_NOTIFICATION_EMAIL'], PDO::PARAM_STR);
    $stmt->bindValue(':FRM_MENTION_CNIL', $_POST['FRM_MENTION_CNIL'], PDO::PARAM_INT);
    $stmt->bindValue(':FRM_MESSAGEREPONSE', $_POST['FRM_MESSAGEREPONSE'], PDO::PARAM_STR);
    $stmt->bindValue(':FRM_EXPEDITEUR_NOM', empty($_POST['FRM_EXPEDITEUR_NOM']) ? EMAIL_FROMNAME : $_POST['FRM_EXPEDITEUR_NOM'], PDO::PARAM_STR);
    $stmt->bindValue(':FRM_EXPEDITEUR_EMAIL', empty($_POST['FRM_EXPEDITEUR_EMAIL']) ? EMAIL_FROM : $_POST['FRM_EXPEDITEUR_EMAIL'], PDO::PARAM_STR);
    $stmt->bindValue(':idtf', $oFormulaire->getID(), PDO::PARAM_INT);
    $stmt->bindValue(':SIT_CODE', CMS::getCurrentSite()->getID(), PDO::PARAM_STR);
    $stmt->execute();

    _traitementCommun($oFormulaire->getID());
    $oFormulaire->historize('MODIFICATION', 'FORMULAIRE');

    // Purge du cache de l'ensemble du site
    Page::clearCache();

    setMsg(gettext('UPDATE_OK'));
    header('Location:' . SERVER_ROOT . 'formulaire/frm_formulaire.php?idtf=' . $oFormulaire->getID());
    exit ();

} elseif (isset ($_POST['UpdateBouton'])) {
    $oFormulaire = new Formulaire($_POST['idtf']);
    $oFormulaire->checkAuthorized();
    $stmt = $dbh->prepare("update FORMULAIRE set
        FRM_LIBELLE_BOUTON = :FRM_LIBELLE_BOUTON
        where ID_FORMULAIRE = :idtf and SIT_CODE=:SIT_CODE");
    $stmt->bindValue(':FRM_LIBELLE_BOUTON', $_POST['FRM_LIBELLE_BOUTON'], PDO::PARAM_STR);
    $stmt->bindValue(':idtf', $oFormulaire->getID(), PDO::PARAM_INT);
    $stmt->bindValue(':SIT_CODE', CMS::getCurrentSite()->getID(), PDO::PARAM_STR);
    $stmt->execute();
    $oFormulaire->historize('MODIFICATION', 'FORMULAIRE');
    Editor::updateContent($_POST['FRM_ACCUSERECEPTION'], 'FORMULAIRE', 'FRM_ACCUSERECEPTION', 'ID_FORMULAIRE', $oFormulaire->getID());

    // Purge du cache de l'ensemble du site
    Page::clearCache();

    setMsg(gettext('UPDATE_OK'));
    header('Location:' . SERVER_ROOT . 'formulaire/frm_formulaire.php?idtf=' . $oFormulaire->getID());
    exit ();

} elseif (is_numeric($_GET['Delete'])) {
    $oFormulaire = new Formulaire($_GET['Delete']);
    $oFormulaire->checkAuthorized();
    if ($oFormulaire->delete()) {
        setMsg(gettext('DELETE_OK'));
    }
    header('Location:' . SERVER_ROOT . 'formulaire/frm_formulaireListe.php');
    exit ();

} elseif (isset($_POST['Duplicate'])) {
    $oFormulaire = new Formulaire($_POST['idtf']);
    $oFormulaire->checkAuthorized();
    $stmt = $dbh->prepare("insert into FORMULAIRE (
        SIT_CODE,
        ID_FORMULAIRECATEGORIE,
        FRM_LIBELLE,
        FRM_LIBELLE_BOUTON,
        FRM_TRACABLE,
        FRM_TRACEIP,
        FRM_NOTIFICATION,
        FRM_NOTIFICATION_EMAIL,
        FRM_ACCUSERECEPTION,
        FRM_MENTION_CNIL,
        FRM_MESSAGEREPONSE,
        FRM_EXPEDITEUR_NOM,
        FRM_EXPEDITEUR_EMAIL
        ) select
        SIT_CODE,
        ID_FORMULAIRECATEGORIE,
        :FRM_LIBELLE,
        FRM_LIBELLE_BOUTON,
        FRM_TRACABLE,
        FRM_TRACEIP,
        FRM_NOTIFICATION,
        FRM_NOTIFICATION_EMAIL,
        FRM_ACCUSERECEPTION,
        FRM_MENTION_CNIL,
        FRM_MESSAGEREPONSE,
        FRM_EXPEDITEUR_NOM,
        FRM_EXPEDITEUR_EMAIL
        from FORMULAIRE
        where ID_FORMULAIRE = :idtf");
    $stmt->bindValue(':FRM_LIBELLE', 'Copie - '.$_POST['FRM_LIBELLE'], PDO::PARAM_STR);
    $stmt->bindValue(':idtf', $oFormulaire->getID(), PDO::PARAM_INT);
    $stmt->execute();
    $oFormulaireNew = new Formulaire($dbh->lastInsertID());
    $oFormulaireNew->historize('CREATION', 'FORMULAIRE', "Duplication du formulaire : " . $oFormulaire->getField('FRM_LIBELLE'));

    // Duplication des utilisateurs associés
    $sql = "select ID_UTILISATEUR from FORMULAIRE_UTILISATEUR where ID_FORMULAIRE = ".intval($_POST['idtf']);
    $stmt = $dbh->prepare("insert into FORMULAIRE_UTILISATEUR (ID_FORMULAIRE, ID_UTILISATEUR) values (:idtf, :ID_UTILISATEUR)");
    foreach ($dbh->query($sql)->fetchAll() as $rowUtilisateur) {
        $stmt->bindValue(':idtf', $oFormulaireNew->getID(), PDO::PARAM_INT);
        $stmt->bindValue(':ID_UTILISATEUR', $rowUtilisateur['ID_UTILISATEUR'], PDO::PARAM_INT);
        $stmt->execute();
    }

    // Duplication des groupes de questions associés
    $sql = "select * from FORMULAIREGROUPE where ID_FORMULAIRE = ".intval($_POST['idtf']);

    $stmtGroupe = $dbh->prepare("insert into FORMULAIREGROUPE (
        ID_FORMULAIRE,
        FMG_LIBELLE,
        FMG_POIDS,
        FMG_VISIBLE,
        FMG_LIBELLEVISIBLE
        ) values (
        :ID_FORMULAIRE,
        :FMG_LIBELLE,
        :FMG_POIDS,
        :FMG_VISIBLE,
        :FMG_LIBELLEVISIBLE
        )");
    foreach ($dbh->query($sql)->fetchAll() as $groupe) {
        $stmtGroupe->bindValue(':FMG_LIBELLE', $groupe['FMG_LIBELLE'], PDO::PARAM_STR);
        $stmtGroupe->bindValue(':FMG_POIDS', $groupe['FMG_POIDS'], PDO::PARAM_INT);
        $stmtGroupe->bindValue(':FMG_VISIBLE', $groupe['FMG_VISIBLE'], PDO::PARAM_INT);
        $stmtGroupe->bindValue(':FMG_LIBELLEVISIBLE', $groupe['FMG_LIBELLEVISIBLE'], PDO::PARAM_INT);
        $stmtGroupe->bindValue(':ID_FORMULAIRE', $oFormulaireNew->getID(), PDO::PARAM_INT);
        $stmtGroupe->execute();
        $idtfGroupe = $dbh->lastInsertID();

        // Duplication des questions propres à ce groupe
        $stmt = $dbh->prepare("insert into FORMULAIREQUESTION (
            ID_FORMULAIREGROUPE,
            QTY_CODE,
            QST_LIBELLE,
            QST_POIDS,
            QST_VISIBLE,
            QST_OBLIGATOIRE,
            QST_HEIGHT,
            QST_WIDTH,
            QST_MAXLENGTH,
            QST_VALEUR,
            QST_MULTIPLE,
            QST_ENTETE,
            QST_LIBELLEVISIBLE,
            QST_COMMENTAIRE
        ) select
            :ID_FORMULAIREGROUPE,
            QTY_CODE,
            QST_LIBELLE,
            QST_POIDS,
            QST_VISIBLE,
            QST_OBLIGATOIRE,
            QST_HEIGHT,
            QST_WIDTH,
            QST_MAXLENGTH,
            QST_VALEUR,
            QST_MULTIPLE,
            QST_ENTETE,
            QST_LIBELLEVISIBLE,
            QST_COMMENTAIRE
        from FORMULAIREQUESTION
        where ID_FORMULAIREGROUPE = :idtf");

        $stmt->bindValue(':idtf', $groupe['ID_FORMULAIREGROUPE'], PDO::PARAM_INT);
        $stmt->bindValue(':ID_FORMULAIREGROUPE', $idtfGroupe, PDO::PARAM_INT);
        $stmt->execute();
    }

    setMsg(gettext('INSERT_OK'));
    header('Location:' . SERVER_ROOT . 'formulaire/frm_formulaire.php?idtf=' . $oFormulaireNew->getID());
    exit ();
}
function _traitementCommun($idtf)
{
    $dbh = DB::getInstance();
    //utilisateur
    $stmt = $dbh->prepare("delete from FORMULAIRE_UTILISATEUR where ID_FORMULAIRE = :idtf");
    $stmt->bindValue(':idtf', $idtf, PDO::PARAM_INT);
    $stmt->execute();
    if (is_array($_POST['ID_UTILISATEUR'])) {
        foreach ($_POST['ID_UTILISATEUR'] as $val) {
            $stmt = $dbh->prepare("insert into FORMULAIRE_UTILISATEUR (ID_FORMULAIRE, ID_UTILISATEUR) values (:idtf, :ID_UTILISATEUR)");
            $stmt->bindValue(':idtf', $idtf, PDO::PARAM_INT);
            $stmt->bindValue(':ID_UTILISATEUR', $val, PDO::PARAM_INT);
            $stmt->execute();
        }
    }

    //etat
    $_aFRM_ETATREPONSE = explode("\n", str_replace("\r", '', $_POST['FRM_ETATREPONSE']));
    //Nettoyage des états
    $_temp = array ();
    foreach ($_aFRM_ETATREPONSE as $val) {
        $val = trim($val);
        if (!empty ($val)) {
            $_temp[] = $val;
        }
    }
    $_aFRM_ETATREPONSE = $_temp;
    $sql = "select distinct(REP_ETAT) from FORMULAIREREPONSE where ID_FORMULAIRE=" . intval($idtf) . " and REP_ETAT !='' and REP_ETAT is not null";
    foreach ($dbh->query($sql) as $rowTemp) {
        if (!in_array($rowTemp['REP_ETAT'], $_aFRM_ETATREPONSE)) {
            $_aFRM_ETATREPONSE[] = $rowTemp['REP_ETAT'];
            setMsg(gettext('Suppression impossible') . ' : ' . $rowTemp['REP_ETAT'], 'ERROR');
        }
    }

    $stmt = $dbh->prepare("update FORMULAIRE set FRM_ETATREPONSE = :FRM_ETATREPONSE where ID_FORMULAIRE = :idtf");
    $stmt->bindValue(':FRM_ETATREPONSE', implode("\n", $_aFRM_ETATREPONSE), PDO::PARAM_STR);
    $stmt->bindValue(':idtf', $idtf, PDO::PARAM_INT);
    $stmt->execute();

    //categorie
    if ($_POST['CAT_LIBELLE'] != '') {
        $stmt = $dbh->prepare("insert into FORMULAIRECATEGORIE (
            CAT_IDPARENT,
            SIT_CODE,
            CAT_LIBELLE
            ) values (
            :CAT_IDPARENT,
            :SIT_CODE,
            :CAT_LIBELLE
            )");
        $stmt->bindValue(':CAT_IDPARENT', is_numeric($_POST['ID_FORMULAIRECATEGORIE']) ? $_POST['ID_FORMULAIRECATEGORIE'] : null, PDO::PARAM_INT);
        $stmt->bindValue(':SIT_CODE', CMS::getCurrentSite()->getID(), PDO::PARAM_STR);
        $stmt->bindValue(':CAT_LIBELLE', $_POST['CAT_LIBELLE'], PDO::PARAM_STR);
        $stmt->execute();

        $stmt = $dbh->prepare("update FORMULAIRE set ID_FORMULAIRECATEGORIE = :ID_FORMULAIRECATEGORIE where ID_FORMULAIRE= :idtf and SIT_CODE=:SIT_CODE");
        $stmt->bindValue(':ID_FORMULAIRECATEGORIE', $dbh->lastInsertID(), PDO::PARAM_INT);
        $stmt->bindValue(':idtf', $idtf, PDO::PARAM_INT);
        $stmt->bindValue(':SIT_CODE', CMS::getCurrentSite()->getID(), PDO::PARAM_STR);
        $stmt->execute();
    }
}
