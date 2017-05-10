<?php
require '../include/inc.bo_init.php';
require_once CLASS_DIR . 'class.db_page.php';
CMS::checkAccess(new Module('MOD_THEMATIQUE'), array('PRO_THEMATIQUE'));

require CLASS_DIR . 'class.db_thematique.php';
require CLASS_DIR . 'class.Link.php';

if (isset($_POST['Insert'])) {
    $stmt = $dbh->prepare("insert into THEMATIQUE (
        THE_LIBELLE,
        SIT_CODE
        ) values (
        :THE_LIBELLE,
        :SIT_CODE
        )");
    $stmt->bindValue(':THE_LIBELLE', $_POST['THE_LIBELLE'], PDO::PARAM_STR);
    $stmt->bindValue(':SIT_CODE', CMS::getCurrentSite()->getID(), PDO::PARAM_STR);
    $stmt->execute();
    $idtf = $dbh->lastInsertID();
    // Purge du cache de l'ensemble des sites
    Page::clearAllCache();
    setMsg(gettext('INSERT_OK'));
    header('Location:' . SERVER_ROOT . 'cms/cms_thematique.php?idtf=' . $idtf);
    exit();
} elseif (isset($_POST['Update'])) {
    $oThematique = new Thematique($_POST['idtf']);
    $oThematique->checkAuthorized();
    $stmt = $dbh->prepare("update THEMATIQUE set
        THE_LIBELLE=:THE_LIBELLE
        where ID_THEMATIQUE=:idtf");
    $stmt->bindValue(':THE_LIBELLE', $_POST['THE_LIBELLE'], PDO::PARAM_STR);
    $stmt->bindValue(':idtf', $oThematique->getID(), PDO::PARAM_INT);
    $stmt->execute();
    // Purge du cache de l'ensemble des sites
    Page::clearAllCache();
    setMsg(gettext('UPDATE_OK'));
    header('Location:' . SERVER_ROOT . 'cms/cms_thematique.php?idtf=' . $oThematique->getID());
    exit();
} elseif (is_numeric($_GET['Delete'])) {
    $oThematique = new Thematique($_GET['Delete']);
    $oThematique->checkAuthorized();
    if ($oThematique->delete()) {
        setMsg(gettext('DELETE_OK'));
    }
    // Purge du cache de l'ensemble des sites
    Page::clearAllCache();
    header('Location:' . SERVER_ROOT . 'cms/cms_thematiqueListe.php');
    exit();
}
