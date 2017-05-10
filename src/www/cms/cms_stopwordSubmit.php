<?php
require '../include/inc.bo_init.php';
require_once CLASS_DIR . 'class.db_page.php';
CMS::checkAccess(new Module('MOD_RECHERCHE'), array('PRO_RECHERCHE'));

if (isset($_POST['Insert']) || isset($_POST['Update'])) {
    $_POST['STP_LIBELLE'] = preg_replace('/[^a-z]/', '', reduceToISO646(mb_strtolower($_POST['STP_LIBELLE'])));

    if (isset($_POST['Insert'])) {
        setMsg(gettext('INSERT_OK'));
    } else {
        $sql = "delete from STOPWORD_SITE where STP_LIBELLE=" . $dbh->quote($_POST['idtf']) . " and SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID());
        $dbh->exec($sql);
        setMsg(gettext('UPDATE_OK'));
    }

    $stmt = $dbh->prepare("replace into STOPWORD_SITE (
        STP_LIBELLE,
        SIT_CODE
        ) values (
        :STP_LIBELLE,
        :SIT_CODE
        )");
    $stmt->bindValue(':STP_LIBELLE', $_POST['STP_LIBELLE'], PDO::PARAM_STR);
    $stmt->bindValue(':SIT_CODE', CMS::getCurrentSite()->getID(), PDO::PARAM_STR);
    $stmt->execute();
} elseif (isset($_GET['Delete'])) {
    $sql = "delete from STOPWORD_SITE where STP_LIBELLE=" . $dbh->quote($_GET['Delete']) . " and SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID());
    $dbh->exec($sql);
    setMsg(gettext('DELETE_OK'));
}

// Purge du cache de l'ensemble du site
Page::clearCache();

header('Location:' . SERVER_ROOT . 'cms/cms_stopwordListe.php');
exit ();
