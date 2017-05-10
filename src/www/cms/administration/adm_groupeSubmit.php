<?php
require '../../include/inc.bo_init.php';
CMS::checkAccess(new Module('MOD_EXTRANET'), array('PRO_ROOT_SITE'));
require CLASS_DIR . 'class.db_groupe.php';

if (isset ($_POST['Insert'])) {
    $stmt = $dbh->prepare("insert into GROUPE (
        SIT_CODE,
        GRP_LIBELLE,
        GRP_DEFAUT_UTILISATEUR
        ) values (
        :SIT_CODE,
        :GRP_LIBELLE,
        :GRP_DEFAUT_UTILISATEUR
        )");
    $stmt->bindValue(':SIT_CODE', CMS::getCurrentSite()->getID(), PDO :: PARAM_STR);
    $stmt->bindValue(':GRP_LIBELLE', $_POST['GRP_LIBELLE'], PDO :: PARAM_STR);
    $stmt->bindValue(':GRP_DEFAUT_UTILISATEUR', $_POST['GRP_DEFAUT_UTILISATEUR'], PDO :: PARAM_INT);
    $stmt->execute();
    $idtf = $dbh->lastInsertID();

    $oGroupe = new Groupe($idtf);
    $oGroupe->share($_POST['SIT_CODE']);

    setMsg(gettext('INSERT_OK'));

    header('Location:' . SERVER_ROOT . 'cms/administration/adm_groupe.php?idtf=' . $oGroupe->getID());
    exit ();
} elseif (isset ($_POST['Update'])) {
    $oGroupe = new Groupe($_POST['idtf']);
    $oGroupe->checkAuthorized();
    $stmt = $dbh->prepare("update GROUPE set
        GRP_LIBELLE = :GRP_LIBELLE,
        GRP_DEFAUT_UTILISATEUR = :GRP_DEFAUT_UTILISATEUR
        where ID_GROUPE = :idtf");
    $stmt->bindValue(':GRP_LIBELLE', $_POST['GRP_LIBELLE'], PDO :: PARAM_STR);
    $stmt->bindValue(':GRP_DEFAUT_UTILISATEUR', $_POST['GRP_DEFAUT_UTILISATEUR'], PDO :: PARAM_INT);
    $stmt->bindValue(':idtf', $_POST['idtf'], PDO :: PARAM_INT);
    $stmt->execute();

    $oGroupe->share($_POST['SIT_CODE']);

    setMsg(gettext('UPDATE_OK'));

    header('Location:' . SERVER_ROOT . 'cms/administration/adm_groupe.php?idtf=' . $oGroupe->getID());
    exit ();

} elseif (is_numeric($_GET['Delete'])) {
    $oGroupe = new Groupe($_GET['Delete']);
    $oGroupe->checkAuthorized();
    if ($oGroupe->delete()) {
        setMsg(gettext('DELETE_OK'));
    }
    header('Location:' . SERVER_ROOT . 'cms/administration/adm_groupeListe.php');
    exit ();
}
