<?php
require '../include/inc.bo_init.php';
require_once CLASS_DIR . 'class.db_page.php';
CMS::checkAccess(new Module('MOD_LANGUISME'), array('PRO_LANGUISME'));
require CLASS_DIR . 'class.db_languisme.php';

if (isset($_POST['Insert'])) {
    $stmt = $dbh->prepare("insert into LANGUISME (
        LNG_LIBELLE,
        LNG_LANGUE,
        SIT_CODE
        ) values (
        :LNG_LIBELLE,
        :LNG_LANGUE,
        :SIT_CODE
        )");
    $stmt->bindValue(':LNG_LIBELLE', trim($_POST['LNG_LIBELLE']), PDO :: PARAM_STR);
    $stmt->bindValue(':LNG_LANGUE', $_POST['LNG_LANGUE'], PDO :: PARAM_STR);
    $stmt->bindValue(':SIT_CODE', CMS::getCurrentSite()->getID(), PDO :: PARAM_STR);
    $stmt->execute();
    $idtf = $dbh->lastInsertID();

    // Purge du cache de l'ensemble du site
    Page::clearCache();

    setMsg(gettext('INSERT_OK'));

    header('Location:' . SERVER_ROOT . 'cms/cms_languisme.php?idtf=' . $idtf);
    exit ();
} elseif (isset($_POST['Update'])) {
    $oLanguisme = new Languisme($_POST['idtf']);
    $oLanguisme->checkAuthorized();

    $stmt = $dbh->prepare("update LANGUISME
            set LNG_LIBELLE = :LNG_LIBELLE,
            LNG_LANGUE = :LNG_LANGUE
            where ID_LANGUISME = :idtf");
    $stmt->bindValue(':LNG_LIBELLE', trim($_POST['LNG_LIBELLE']), PDO :: PARAM_STR);
    $stmt->bindValue(':LNG_LANGUE', $_POST['LNG_LANGUE'], PDO :: PARAM_STR);
    $stmt->bindValue(':idtf', $_POST['idtf'], PDO :: PARAM_INT);
    $stmt->execute();

    // Purge du cache de l'ensemble du site
    Page::clearCache();

    setMsg(gettext('UPDATE_OK'));

    header('Location:' . SERVER_ROOT . 'cms/cms_languisme.php?idtf=' . $_POST['idtf']);
    exit();
} elseif (is_numeric($_GET['Delete'])) {
    $oLanguisme = new Languisme($_GET['Delete']);
    $oLanguisme->checkAuthorized();

    if ($oLanguisme->delete()) {
        setMsg(gettext('DELETE_OK'));
    }

    // Purge du cache de l'ensemble du site
    Page::clearCache();

    header('Location:' . SERVER_ROOT . 'cms/cms_languismeListe.php');
    exit();
}
