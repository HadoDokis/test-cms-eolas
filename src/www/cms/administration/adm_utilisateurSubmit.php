<?php
require '../../include/inc.bo_init.php';
require_once CLASS_DIR . 'class.db_historique.php';
CMS::checkAccess(new Module('MOD_CORE'));

$_POST['UTI_NOM'] = mb_convert_case($_POST['UTI_NOM'], MB_CASE_UPPER);
$_POST['UTI_PRENOM'] = mb_convert_case($_POST['UTI_PRENOM'], MB_CASE_TITLE);

if (isset ($_POST['Insert']) && Utilisateur::getConnected()->isRoot()) {
    $stmt = $dbh->prepare("insert into UTILISATEUR (
        SIT_CODE,
        ID_LDAP,
        LNG_CODE,
        ID_PAGE,
        UTI_CIVILITE,
        UTI_NOM,
        UTI_PRENOM,
        UTI_EMAIL,
        UTI_TELEPHONE,
        UTI_FAX,
        UTI_FONCTION,
        UTI_ORGANISME,
        UTI_ADRESSE,
        UTI_CODEPOSTAL,
        UTI_VILLE,
        UTI_PAYS,
        UTI_DATECREATION,
        UTI_DATEMODIFICATION
        ) values (
        :SIT_CODE,
        :ID_LDAP,
        :LNG_CODE,
        :ID_PAGE,
        :UTI_CIVILITE,
        :UTI_NOM,
        :UTI_PRENOM,
        :UTI_EMAIL,
        :UTI_TELEPHONE,
        :UTI_FAX,
        :UTI_FONCTION,
        :UTI_ORGANISME,
        :UTI_ADRESSE,
        :UTI_CODEPOSTAL,
        :UTI_VILLE,
        :UTI_PAYS,
        :UTI_DATECREATION,
        :UTI_DATEMODIFICATION
        )");
    $stmt->bindValue(':SIT_CODE', CMS::getCurrentSite()->getID(), PDO::PARAM_STR);
    $stmt->bindValue(':ID_LDAP', (is_numeric($_POST['ID_LDAP'])) ? $_POST['ID_LDAP'] : null, PDO::PARAM_INT);
    $stmt->bindValue(':LNG_CODE', $_POST['LNG_CODE'], PDO::PARAM_STR);
    $stmt->bindValue(':ID_PAGE', (is_numeric($_POST['ID_PAGE'])) ? $_POST['ID_PAGE'] : null, PDO::PARAM_INT);
    $stmt->bindValue(':UTI_CIVILITE', $_POST['UTI_CIVILITE'], PDO::PARAM_STR);
    $stmt->bindValue(':UTI_NOM', $_POST['UTI_NOM'], PDO::PARAM_STR);
    $stmt->bindValue(':UTI_PRENOM', $_POST['UTI_PRENOM'], PDO::PARAM_STR);
    $stmt->bindValue(':UTI_EMAIL', $_POST['UTI_EMAIL'], PDO::PARAM_STR);
    $stmt->bindValue(':UTI_TELEPHONE', $_POST['UTI_TELEPHONE'], PDO::PARAM_STR);
    $stmt->bindValue(':UTI_FAX', $_POST['UTI_FAX'], PDO::PARAM_STR);
    $stmt->bindValue(':UTI_FONCTION', $_POST['UTI_FONCTION'], PDO::PARAM_STR);
    $stmt->bindValue(':UTI_ORGANISME', $_POST['UTI_ORGANISME'], PDO::PARAM_STR);
    $stmt->bindValue(':UTI_ADRESSE', $_POST['UTI_ADRESSE'], PDO::PARAM_STR);
    $stmt->bindValue(':UTI_CODEPOSTAL', $_POST['UTI_CODEPOSTAL'], PDO::PARAM_STR);
    $stmt->bindValue(':UTI_VILLE', $_POST['UTI_VILLE'], PDO::PARAM_STR);
    $stmt->bindValue(':UTI_PAYS', $_POST['UTI_PAYS'], PDO::PARAM_STR);
    $stmt->bindValue(':UTI_DATECREATION', time(), PDO::PARAM_INT);
    $stmt->bindValue(':UTI_DATEMODIFICATION', time(), PDO::PARAM_INT);
    $stmt->execute();
    $idtf = $dbh->lastInsertID();

    $oUtilisateur= new Utilisateur($idtf);
    $oUtilisateur->manageGroupes($_POST['ID_GROUPE']);
    $oUtilisateur->manageProfils($_POST['PRO_ROOT'], $_POST['PRO_CODE']);
    Historique::historizeAdmin('CREATION', 'UTILISATEUR', $oUtilisateur->getID());

    _traitementCommun($idtf);

    setMsg(gettext('INSERT_OK'));
    header('Location:' . SERVER_ROOT . 'cms/administration/adm_utilisateur.php?idtf=' . $oUtilisateur->getID());
    exit ();

} elseif (isset ($_POST['Update'])) {
    if (!Utilisateur::getConnected()->isRoot()) {
        $_POST['idtf'] = Utilisateur::getConnected()->getID();
    }
    $oUtilisateur = new Utilisateur($_POST['idtf']);
    if ($oUtilisateur->getID() != Utilisateur::getConnected()->getID()) {
        $oUtilisateur->checkAuthorized();
    }
    if (!Utilisateur::getConnected()->isRoot()) {
        $_POST['ID_LDAP'] = $oUtilisateur->getField('ID_LDAP');
    }
    $stmt = $dbh->prepare("update UTILISATEUR set
        ID_LDAP = :ID_LDAP,
        ID_PAGE = :ID_PAGE,
        UTI_CIVILITE=:UTI_CIVILITE,
        UTI_NOM=:UTI_NOM,
        UTI_PRENOM=:UTI_PRENOM,
        UTI_EMAIL=:UTI_EMAIL,
        UTI_TELEPHONE=:UTI_TELEPHONE,
        UTI_FAX=:UTI_FAX,
        UTI_FONCTION=:UTI_FONCTION,
        UTI_ORGANISME=:UTI_ORGANISME,
        UTI_ADRESSE=:UTI_ADRESSE,
        UTI_CODEPOSTAL=:UTI_CODEPOSTAL,
        UTI_VILLE=:UTI_VILLE,
        UTI_PAYS=:UTI_PAYS,
        UTI_DATEMODIFICATION=:UTI_DATEMODIFICATION,
        UTI_PWD_MUSTBECHANGED=:UTI_PWD_MUSTBECHANGED,
        LNG_CODE = :LNG_CODE
        where ID_UTILISATEUR=:idtf");
    $stmt->bindValue(':ID_LDAP', (is_numeric($_POST['ID_LDAP'])) ? $_POST['ID_LDAP'] : null, PDO::PARAM_INT);
    $stmt->bindValue(':ID_PAGE', (is_numeric($_POST['ID_PAGE'])) ? $_POST['ID_PAGE'] : null, PDO::PARAM_INT);
    $stmt->bindValue(':UTI_CIVILITE', $_POST['UTI_CIVILITE'], PDO::PARAM_STR);
    $stmt->bindValue(':UTI_NOM', $_POST['UTI_NOM'], PDO::PARAM_STR);
    $stmt->bindValue(':UTI_PRENOM', $_POST['UTI_PRENOM'], PDO::PARAM_STR);
    $stmt->bindValue(':UTI_EMAIL', $_POST['UTI_EMAIL'], PDO::PARAM_STR);
    $stmt->bindValue(':UTI_TELEPHONE', $_POST['UTI_TELEPHONE'], PDO::PARAM_STR);
    $stmt->bindValue(':UTI_FAX', $_POST['UTI_FAX'], PDO::PARAM_STR);
    $stmt->bindValue(':UTI_FONCTION', $_POST['UTI_FONCTION'], PDO::PARAM_STR);
    $stmt->bindValue(':UTI_ORGANISME', $_POST['UTI_ORGANISME'], PDO::PARAM_STR);
    $stmt->bindValue(':UTI_ADRESSE', $_POST['UTI_ADRESSE'], PDO::PARAM_STR);
    $stmt->bindValue(':UTI_CODEPOSTAL', $_POST['UTI_CODEPOSTAL'], PDO::PARAM_STR);
    $stmt->bindValue(':UTI_VILLE', $_POST['UTI_VILLE'], PDO::PARAM_STR);
    $stmt->bindValue(':UTI_PAYS', $_POST['UTI_PAYS'], PDO::PARAM_STR);
    $stmt->bindValue(':UTI_DATEMODIFICATION', time(), PDO::PARAM_INT);
    $stmt->bindValue(':UTI_PWD_MUSTBECHANGED', (is_numeric($_POST['ID_LDAP'])) ? 0 : $oUtilisateur->getField('UTI_PWD_MUSTBECHANGED'), PDO::PARAM_INT); // On ne peut pas demander à un utilisateur LDAP de changer son mot de passe
    $stmt->bindValue(':LNG_CODE', $_POST['LNG_CODE'], PDO::PARAM_STR);
    $stmt->bindValue(':idtf', $_POST['idtf'], PDO::PARAM_INT);
    $stmt->execute();

    if ($_POST['idtf'] == Utilisateur::getConnected()->getID()) {
        $_SESSION['S_LNG_CODE']    = $_POST['LNG_CODE'];
        setcookie('C_LNG_CODE', $_POST['LNG_CODE'], time() + 86400 * 365, '/');
    }

    if (Utilisateur::getConnected()->isRoot()) {
        $oUtilisateur->manageGroupes($_POST['ID_GROUPE']);
        $oUtilisateur->manageProfils($_POST['PRO_ROOT'], $_POST['PRO_CODE']);
    }

    Historique::historizeAdmin('MODIFICATION', 'UTILISATEUR', $oUtilisateur->getID());

    _traitementCommun($_POST['idtf']);

    if ($oUtilisateur->getID() == Utilisateur::getConnected()->getID()) {
        Utilisateur::getConnected()->initSession(CMS::getCurrentSite()->getID());
    }

    setMsg(gettext('UPDATE_OK'));
    header('Location:' . SERVER_ROOT . 'cms/administration/adm_utilisateur.php?idtf=' . $oUtilisateur->getID());
    exit ();
} elseif (in_array($_GET['Lock'], array(0, 1)) && is_numeric($_GET['idtf']) && Utilisateur::getConnected()->isRoot()) {
    $oUtilisateur = new Utilisateur($_GET['idtf']);
    $oUtilisateur->checkAuthorized();

    $stmt = $dbh->prepare("update UTILISATEUR set UTI_STATUT_LOCKED=:UTI_STATUT_LOCKED,
            UTI_STATUT_BLOCKED=:UTI_STATUT_BLOCKED,
            UTI_AUTH_INFO=:UTI_AUTH_INFO
        where ID_UTILISATEUR=:idtf");
    $stmt->bindValue(':UTI_STATUT_LOCKED', $_GET['Lock'], PDO::PARAM_INT);
    $stmt->bindValue(':UTI_STATUT_BLOCKED', 0, PDO::PARAM_INT); // L'action sur le verrou supprime les informations liées au blocage
    $stmt->bindValue(':UTI_AUTH_INFO', !empty($_GET['Lock']) ? serialize(array('datetime' => time())) : null, PDO::PARAM_STMT);
    $stmt->bindValue(':idtf',$oUtilisateur->getID(), PDO::PARAM_INT);
    $stmt->execute();
    if ($_GET['Lock'] == 1) {
        $detailHisto = "Verrouillage du compte";
    } else {
        $detailHisto = "Déverrouillage du compte";
    }
    Historique::historizeAdmin('MODIFICATION', 'UTILISATEUR', $oUtilisateur->getID(), $detailHisto);

    setMsg(gettext('UPDATE_OK'));
    header('Location:' . SERVER_ROOT . 'cms/administration/adm_utilisateur.php?idtf=' . $oUtilisateur->getID());
    exit ();
} elseif (is_numeric($_GET['Unblock']) && Utilisateur::getConnected()->isRoot()) {
    $oUtilisateur = new Utilisateur($_GET['Unblock']);
    $oUtilisateur->checkAuthorized();
    if ($oUtilisateur->getField('UTI_STATUT_BLOCKED') == 1) {
        $stmt = $dbh->prepare("update UTILISATEUR set UTI_STATUT_BLOCKED=:UTI_STATUT_BLOCKED,
            UTI_AUTH_INFO=:UTI_AUTH_INFO
        where ID_UTILISATEUR=:idtf");
        $stmt->bindValue(':UTI_STATUT_BLOCKED', 0, PDO::PARAM_INT);
        $stmt->bindValue(':UTI_AUTH_INFO', null, PDO::PARAM_STR);
        $stmt->bindValue(':idtf',$oUtilisateur->getID(), PDO::PARAM_INT);
        $stmt->execute();
        Historique::historizeAdmin('MODIFICATION', 'UTILISATEUR', $oUtilisateur->getID(), 'Déblocage manuelle du compte');
    }

    setMsg(gettext('UPDATE_OK'));
    header('Location:' . SERVER_ROOT . 'cms/administration/adm_utilisateur.php?idtf=' . $oUtilisateur->getID());
    exit ();
} elseif (isset ($_POST['UpdateShare'])) {
    $oUtilisateur = new Utilisateur($_POST['idtf']);
    $oUtilisateur->checkShareAuthorized();
    $oUtilisateur->manageProfils($_POST['PRO_ROOT'], $_POST['PRO_CODE']);
    Historique::historizeAdmin('MODIFICATION', 'UTILISATEUR', $oUtilisateur->getID(), gettext('modification_des_proprietes_partagees_du_contributeur'));

    setMsg(gettext('UPDATE_OK'));
    header('Location:' . SERVER_ROOT . 'cms/administration/adm_utilisateurShare.php?idtf=' . $oUtilisateur->getID());
    exit ();
} elseif (is_numeric($_GET['Delete']) && Utilisateur::getConnected()->isRoot()) {
    $oUtilisateur = new Utilisateur($_GET['Delete']);
    $oUtilisateur->checkAuthorized();
    if ($oUtilisateur->delete()) {
        setMsg(gettext('DELETE_OK'));
    }
    header('Location:' . SERVER_ROOT . 'cms/administration/adm_utilisateurListe.php');
    exit ();
} elseif (is_numeric($_GET['Activate']) && Utilisateur::getConnected()->isRoot()) {
    $oUtilisateur = new Utilisateur($_GET['Activate']);
    $oUtilisateur->checkAuthorized();
    try {
        $oUtilisateur->generateRecoveryPwdNotification(true);
    } catch (Exception $e) {
        setMsg($e->getMessage(), 'ERROR');
        header('Location:' . SERVER_ROOT . 'cms/administration/adm_utilisateur.php?idtf=' . $oUtilisateur->getID());
        exit ();
    }
    setMsg("La demande d'activation de compte a bien été envoyée à l'utilisateur.");
    header('Location:' . SERVER_ROOT . 'cms/administration/adm_utilisateur.php?idtf=' . $oUtilisateur->getID());
    exit ();
} elseif (Utilisateur::getConnected()->isRoot() && is_numeric($_GET['pwdMustBeChanged'])) {
    $oUtilisateur = new Utilisateur($_GET['pwdMustBeChanged']);
    $oUtilisateur->checkAuthorized();
    $row = $oUtilisateur->getFields();
    if ($oUtilisateur->exist()
        && !empty($row['UTI_PASSWORD']) && empty($row['ID_LDAP'])
        && empty($row['UTI_STATUT_LOCKED'])
    ) {
        $stmt = $dbh->prepare("update UTILISATEUR
            set UTI_PWD_MUSTBECHANGED=:UTI_PWD_MUSTBECHANGED
            where ID_UTILISATEUR=:idtf");
        $stmt->bindValue(':UTI_PWD_MUSTBECHANGED', 1, PDO::PARAM_INT);
        $stmt->bindValue(':idtf',$oUtilisateur->getID(), PDO::PARAM_INT);
        $stmt->execute();
        setMsg("La demande de changement de mot de passe lors de la prochaine connexion de l'utilisateur a bien été enregistrée.");
    } else {
        setMsg("La demande de changement de mot de passe ne peut être réalisée car le compte ne remplit pas les conditions nécessaires.", 'ERROR');
    }
    header('Location:' . SERVER_ROOT . 'cms/administration/adm_utilisateur.php?idtf=' . $oUtilisateur->getID());
    exit ();
}

function _traitementCommun($idtf)
{
    $dbh = DB::getInstance();

    //verif email unique
    if ($_POST['UTI_EMAIL'] != '') {
        $sql = "select count(ID_UTILISATEUR) from UTILISATEUR where UTI_EMAIL=" . $dbh->quote($_POST['UTI_EMAIL']) . " and ID_UTILISATEUR<>" . intval($idtf);
        if ($dbh->query($sql)->fetchColumn() > 0) {
            setMsg(gettext('E-mail deja attribue') . ' : ' . $_POST['UTI_EMAIL'], 'ERROR');
            header('Location:' . SERVER_ROOT . 'cms/administration/adm_utilisateur.php?idtf=' . intval($idtf));
            exit ();
        }
    }

    //verif login unique
    if ($_POST['UTI_LOGIN'] != '') {
        $sql = "select count(ID_UTILISATEUR) from UTILISATEUR where UTI_LOGIN=" . $dbh->quote($_POST['UTI_LOGIN']) . " and ID_UTILISATEUR<>" . intval($idtf);
        if ($dbh->query($sql)->fetchColumn() > 0) {
            setMsg(gettext('Identifiant deja attribue') . ' : ' . $_POST['UTI_LOGIN'], 'ERROR');
            header('Location:' . SERVER_ROOT . 'cms/administration/adm_utilisateur.php?idtf=' . intval($idtf));
            exit ();
        }
    }
    $sql = "update UTILISATEUR set UTI_LOGIN=" . $dbh->quote($_POST['UTI_LOGIN']) . " where ID_UTILISATEUR=" . intval($idtf);
    $dbh->exec($sql);
}
