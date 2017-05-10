<?php
require '../include/inc.bo_init.php';
require_once CLASS_DIR . 'class.db_page.php';
CMS::checkAccess(new Module('MOD_RECHERCHE'), array ('PRO_ROOT_SITE'));

if (isset($_POST['Update'])) {
    if (is_array($_POST['SIT_CODE'])) {
        $aRevertSharedSite = CMS::getCurrentSite()->getRevertSharedSites();
        foreach ($_POST['SIT_CODE'] as $key=>$SIT_CODE) {
            if (!isset($aRevertSharedSite[$SIT_CODE])) {
                unset($_POST['SIT_CODE'][$key]);
            }
        }
    }
    $stmt = $dbh->prepare("update DD_SITE set SIT_RECHERCHE=:SIT_RECHERCHE where SIT_CODE=:SIT_CODE");
    $stmt->bindValue(':SIT_RECHERCHE', (is_array($_POST['SIT_CODE'])) ? '@' . implode('@', $_POST['SIT_CODE']) . '@' : '', PDO :: PARAM_STR);
    $stmt->bindValue(':SIT_CODE', CMS::getCurrentSite()->getID(), PDO :: PARAM_STR);
    $stmt->execute();

    // Purge du cache de l'ensemble du site
    Page::clearCache();

    setMsg(gettext('UPDATE_OK'));
    header('Location:' . SERVER_ROOT . 'cms/cms_recherche.php');
    exit();
}
