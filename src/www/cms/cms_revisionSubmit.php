<?php
require '../include/inc.bo_init.php';
Utilisateur::checkConnected();

require CLASS_DIR . 'class.Editor.php';
require CLASS_DIR . 'class.Link.php';
require CLASS_DIR . 'class.db_page.php';
require CLASS_DIR . 'class.db_revision.php';
require CLASS_DIR . 'class.db_paragraphe.php';

if (is_numeric($_GET['CreateRevision'])) {
    $oPage = new Page($_GET['CreateRevision']);
    $oPage->load();
    $oPage->createRevision();

    setMsg(gettext('UPDATE_OK'));

    header('Location:' . SERVER_ROOT . 'cms/cms_page.php?idtf=' . $_GET['CreateRevision']);
    exit ();
} elseif (is_numeric($_GET['DeleteRevision'])) {
    $oRevision = new Revision($_GET['DeleteRevision']);
    $idPage = $oRevision->getField('ID_PAGE');
    if ($oRevision->exist() && $oRevision->checkAuthorized()) {
        $oRevision->delete();
    }

    setMsg(gettext('DELETE_OK'));

    header('Location:' . SERVER_ROOT . 'cms/cms_revisionListe.php?idtf=' . $idPage);
    exit ();

} elseif (is_numeric($_GET['RemAllRev'])) {
    $oPage = new Page($_GET['RemAllRev']);
    $oPage->deleteRevision();

    setMsg(gettext('DELETE_OK'));

    header('Location:' . SERVER_ROOT . 'cms/cms_page.php?idtf=' . $_GET['RemAllRev']);
    exit ();
} elseif (is_numeric($_GET['Revert'])) {
    require_once CLASS_DIR .'class.db_revision.php';
    $oRevision = new Revision($_GET['Revert']);
    try {
        $oRevision->revert();
        setMsg(gettext('UPDATE_OK'));
    } catch (Exception $e) {
        setMsg($e->getMessage(), 'ERROR');
    }

    Utilisateur::getConnected()->initSession(CMS::getCurrentSite()->getID(), false);

    header('Location:' . SERVER_ROOT . 'cms/cms_page.php?idtf='.$oRevision->getField('ID_PAGE'));
    exit ();
}
