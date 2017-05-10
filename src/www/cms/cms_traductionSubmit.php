<?php
require '../include/inc.bo_init.php';
require_once CLASS_DIR . 'class.db_page.php';
CMS::checkAccess(new Module('MOD_TRADUCTION'), array('PRO_TRADUCTION'));

if (isset($_POST['Update'])) {
    //On supprime toutes les traductions du site pour le module en cours
    $sql = "delete from TRADUCTION_SITE
        where TRA_CODE in (select TRA_CODE from DD_TRADUCTION where MOD_CODE=". $dbh->quote($_POST['MOD_CODE']) . ")
        and SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID());
    $dbh->exec($sql);

    //On prepare la requete d'insertion
    $stmt = $dbh->prepare("insert into TRADUCTION_SITE (
        TRA_CODE,
        SIT_CODE,
        TRA_LIBELLE
        ) values (
        :TRA_CODE,
        :SIT_CODE,
        :TRA_LIBELLE
        )");
    $stmt->bindValue(':SIT_CODE', CMS::getCurrentSite()->getID(), PDO :: PARAM_STR);

    //On insère toutes les nouvelles traductions du site pour le module en cours
    foreach ($_POST as $k=>$v) {
        if (!in_array($k, array('Update', 'MOD_CODE')) && trim($v) != '') {
            $stmt->bindValue(':TRA_CODE', $k, PDO :: PARAM_STR);
            $stmt->bindValue(':TRA_LIBELLE', $v, PDO :: PARAM_STR);
            $stmt->execute();
        }
    }

    //On invalide le cache
    unset($_SESSION['_tabI18N'][$_POST['MOD_CODE']]);

    // Purge du cache de l'ensemble du site
    Page::clearCache();

    setMSG(gettext('UPDATE_OK'));
    header('location:' . SERVER_ROOT . 'cms/cms_traduction.php?idtf='. $_POST['MOD_CODE']);
    exit();
}
