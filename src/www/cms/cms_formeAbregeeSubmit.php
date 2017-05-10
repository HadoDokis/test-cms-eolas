<?php
require '../include/inc.bo_init.php';
require_once CLASS_DIR . 'class.db_page.php';
CMS::checkAccess(new Module('MOD_ABREVIATION'), array('PRO_ABREVIATION'));
require (CLASS_DIR . 'class.db_abreviation.php');

if (isset($_POST['Insert'])) {
    $stmt = $dbh->prepare("insert into ABREVIATION (
        ABR_ABREVIATION,
        ABR_LIBELLE,
        ABR_LANGUE,
        ABR_TAGNAME,
        SIT_CODE
        ) values (
        :ABR_ABREVIATION,
        :ABR_LIBELLE,
        :ABR_LANGUE,
        :ABR_TAGNAME,
        :SIT_CODE
        )");
    $stmt->bindValue(':ABR_ABREVIATION', trim($_POST['ABR_ABREVIATION']), PDO :: PARAM_STR);
    $stmt->bindValue(':ABR_LIBELLE', trim($_POST['ABR_LIBELLE']), PDO :: PARAM_STR);
    $stmt->bindValue(':ABR_LANGUE', $_POST['ABR_LANGUE'], PDO :: PARAM_STR);
    $stmt->bindValue(':ABR_TAGNAME', $_POST['ABR_TAGNAME'], PDO :: PARAM_STR);
    $stmt->bindValue(':SIT_CODE', CMS::getCurrentSite()->getID(), PDO :: PARAM_STR);
    $stmt->execute();
    $idtf = $dbh->lastInsertID();

    // Purge du cache de l'ensemble du site
    Page::clearCache();

    setMsg(gettext('INSERT_OK'));
    header('Location:' . SERVER_ROOT . 'cms/cms_formeAbregee.php?idtf=' . $idtf);
    exit ();
} elseif (isset($_POST['Update'])) {
    $oAbreviation = new Abreviation($_POST['idtf']);
    $oAbreviation->checkAuthorized();

    $stmt = $dbh->prepare("update ABREVIATION
        set ABR_ABREVIATION=:ABR_ABREVIATION,
        ABR_LIBELLE=:ABR_LIBELLE,
        ABR_LANGUE=:ABR_LANGUE,
        ABR_TAGNAME =:ABR_TAGNAME
        where ID_ABREVIATION=:idtf");
    $stmt->bindValue(':ABR_ABREVIATION', trim($_POST['ABR_ABREVIATION']), PDO :: PARAM_STR);
    $stmt->bindValue(':ABR_LIBELLE', trim($_POST['ABR_LIBELLE']), PDO :: PARAM_STR);
    $stmt->bindValue(':ABR_LANGUE', $_POST['ABR_LANGUE'], PDO :: PARAM_STR);
    $stmt->bindValue(':ABR_TAGNAME', $_POST['ABR_TAGNAME'], PDO :: PARAM_STR);
    $stmt->bindValue(':idtf', $_POST['idtf'], PDO :: PARAM_INT);
    $stmt->execute();

    // Purge du cache de l'ensemble du site
    Page::clearCache();

    setMsg(gettext('UPDATE_OK'));
    header('Location:' . SERVER_ROOT . 'cms/cms_formeAbregee.php?idtf=' . $_POST['idtf']);
    exit();
} elseif (is_numeric($_GET['Delete'])) {
    $oAbreviation = new Abreviation($_GET['Delete']);
    $oAbreviation->checkAuthorized();

    if ($oAbreviation->delete()) {
        setMsg(gettext('DELETE_OK'));
    }

    // Purge du cache de l'ensemble du site
    Page::clearCache();

    header('Location:' . SERVER_ROOT . 'cms/cms_formeAbregeeListe.php');
    exit();
}
