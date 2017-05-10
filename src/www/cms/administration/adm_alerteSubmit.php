<?php
require '../../include/inc.bo_init.php';
require CLASS_DIR . 'class.db_alerte.php';
CMS::checkAccess(new Module('MOD_CORE'), array('PRO_ROOT_SITE'));

if (isset($_POST['Insert'])) {
    $SIT_CODE = (($_POST['idtf'] == '') && Utilisateur::getConnected()->isRoot(true)) ? null : CMS::getCurrentSite()->getID();
    $stmt = $dbh->prepare("insert into ALERTE (
        SIT_CODE,
        ALT_MESSAGE,
        ALT_DATE
        ) values (
        :SIT_CODE,
        :ALT_MESSAGE,
        :ALT_DATE
        )");
    $stmt->bindValue(':SIT_CODE', $SIT_CODE, PDO::PARAM_STR);
    $stmt->bindValue(':ALT_MESSAGE', $_POST['ALT_MESSAGE'], PDO::PARAM_STR);
    $stmt->bindValue(':ALT_DATE', _unixtime($_POST['ALT_DATE']), PDO::PARAM_INT);
    $stmt->execute();

    setMsg(gettext('INSERT_OK'));
    header('Location:' . SERVER_ROOT . 'cms/administration/adm_alerte.php?idtf=' . $SIT_CODE);
    exit ();
} elseif (isset($_POST['Update'])) {
    $SIT_CODE = (($_POST['idtf'] == '') && Utilisateur::getConnected()->isRoot(true)) ? null : CMS::getCurrentSite()->getID();
    if (empty($SIT_CODE)) {
        $stmt = $dbh->prepare("update ALERTE set
            ALT_MESSAGE = :ALT_MESSAGE,
            ALT_DATE = :ALT_DATE
            where SIT_CODE is null");
    } else {
        $stmt = $dbh->prepare("update ALERTE set
            ALT_MESSAGE = :ALT_MESSAGE,
            ALT_DATE = :ALT_DATE
            where SIT_CODE = :SIT_CODE");
        $stmt->bindValue(':SIT_CODE', $SIT_CODE, PDO::PARAM_STR);
    }
    $stmt->bindValue(':ALT_MESSAGE', $_POST['ALT_MESSAGE'], PDO::PARAM_STR);
    $stmt->bindValue(':ALT_DATE', _unixtime($_POST['ALT_DATE']), PDO::PARAM_INT);
    $stmt->execute();

    setMsg(gettext('UPDATE_OK'));
    header('Location:' . SERVER_ROOT . 'cms/administration/adm_alerte.php?idtf=' . $SIT_CODE);
    exit ();

} elseif (isset($_GET['Delete'])) {
    $SIT_CODE = (($_GET['Delete'] == '') && Utilisateur::getConnected()->isRoot(true)) ? null : CMS::getCurrentSite()->getID();
    $oAlerte = new Alerte($SIT_CODE);
    if ($oAlerte->delete()) {
        setMsg(gettext('DELETE_OK'));
    }
    header('Location:' . SERVER_ROOT . 'cms/administration/adm_alerte.php?idtf=' . $SIT_CODE);
    exit ();
}
function _unixtime()
{
    $time = unixtime($_POST['ALT_DATE']);
    if (!is_null($time)) {
        $time += $_POST['ALT_DATE_HEURE'] * 3600 + $_POST['ALT_DATE_MINUTE'] * 60;
    }

    return $time;
}
