<?php
require '../include/inc.bo_init.php';
require CLASS_DIR . 'class.db_webothequeCategorie.php';

if (isset ($_POST['Insert'])) {
    $oCategorie = new WebothequeCategorie($_POST['CAT_IDPARENT']);
    $oCategorie->checkAuthorized();
    $WBT_CODE = $oCategorie->getField('WBT_CODE');
    CMS::checkAccess(new Module(str_replace('WBT_', 'MOD_WEBOTHEQUE_', $WBT_CODE)), array('PRO_WEB'.str_replace('WBT_', '', $WBT_CODE), 'PRO_WEBROOT'));

    $stmt = $dbh->prepare("insert into WEBOTHEQUECATEGORIE (
        SIT_CODE,
        WBT_CODE,
        CAT_IDPARENT,
        CAT_LIBELLE
        ) values (
        :SIT_CODE,
        :WBT_CODE,
        :CAT_IDPARENT,
        :CAT_LIBELLE);");
    $stmt->bindValue(':SIT_CODE', CMS::getCurrentSite()->getID(), PDO::PARAM_STR);
    $stmt->bindValue(':WBT_CODE', $WBT_CODE, PDO::PARAM_STR);
    $stmt->bindValue(':CAT_IDPARENT', is_numeric($_POST['CAT_IDPARENT']) ? $_POST['CAT_IDPARENT'] : null, PDO::PARAM_INT);
    $stmt->bindValue(':CAT_LIBELLE', $_POST['CAT_LIBELLE'], PDO::PARAM_STR);
    $stmt->execute();
    $idtf = $dbh->lastInsertID();

    setMsg(gettext('INSERT_OK'));
    header('Location:' . SERVER_ROOT . 'webotheque/web_categorieListe.php?WBT_CODE=' . $WBT_CODE);
    exit ();

} elseif (isset($_POST['Update'])) {
    $oCategorie = new WebothequeCategorie($_POST['idtf']);
    $oCategorie->checkAuthorized();
    $WBT_CODE = $oCategorie->getField('WBT_CODE');
    CMS::checkAccess(new Module(str_replace('WBT_', 'MOD_WEBOTHEQUE_', $WBT_CODE)), array('PRO_WEB'.str_replace('WBT_', '', $WBT_CODE), 'PRO_WEBROOT'));
    $stmt = $dbh->prepare("update WEBOTHEQUECATEGORIE set
        CAT_LIBELLE=:CAT_LIBELLE,
        CAT_IDPARENT=:CAT_IDPARENT
        where ID_WEBOTHEQUECATEGORIE=:idtf");
    $stmt->bindValue(':CAT_LIBELLE', $_POST['CAT_LIBELLE'], PDO::PARAM_STR);
    $stmt->bindValue(':CAT_IDPARENT', is_numeric($_POST['CAT_IDPARENT']) ? $_POST['CAT_IDPARENT'] : null, PDO::PARAM_INT);
    $stmt->bindValue(':idtf', $_POST['idtf'], PDO::PARAM_INT);
    $stmt->execute();

    setMsg(gettext('UPDATE_OK'));
    header('Location:' . SERVER_ROOT . 'webotheque/web_categorie.php?idtf=' . $oCategorie->getID());
    exit ();

} elseif (isset($_GET['Delete'])) {
    $oCategorie = new WebothequeCategorie($_GET['Delete']);
    $oCategorie->checkAuthorized();
    $WBT_CODE = $oCategorie->getField('WBT_CODE');
    CMS::checkAccess(new Module(str_replace('WBT_', 'MOD_WEBOTHEQUE_', $WBT_CODE)), array('PRO_WEB'.str_replace('WBT_', '', $WBT_CODE), 'PRO_WEBROOT'));
    if ($oCategorie->isDeletable()) {
        $oCategorie->delete();
    }

    setMsg(gettext('DELETE_OK'));
    header('Location:' . SERVER_ROOT . 'webotheque/web_categorieListe.php?WBT_CODE='.$WBT_CODE);
    exit ();
}
