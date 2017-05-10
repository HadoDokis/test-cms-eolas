<?php
require '../include/inc.bo_init.php';

$wbt_type = '';
switch ($_REQUEST['WBT_CODE']) {
    case 'WBT_DOCUMENT':
        $wbt_type = 'document';
        break;
    case 'WBT_FLASH':
        $wbt_type = 'flash';
        break;
    case 'WBT_IMAGE':
        $wbt_type = 'image';
        break;
    case 'WBT_LIENEXTERNE':
        $wbt_type = 'lienExterne';
        break;
    case 'WBT_VIDEO':
        $wbt_type = 'video';
        break;
    case 'WBT_VIDEOEXTERNE':
        $wbt_type = 'videoExterne';
        break;
    case 'WBT_MUSIC':
        $wbt_type = 'music';
        break;
    case 'WBT_WIDGET':
        $wbt_type = 'widget';
        break;
}
CMS::checkAccess(new Module('MOD_WEBOTHEQUE_' . strtoupper($wbt_type)), array('PRO_WEB' . strtoupper($wbt_type), 'PRO_WEBROOT'));
$weboClass = 'Webo_' . strtoupper($wbt_type);

require CLASS_DIR . 'class.db_webotheque.php';
require CLASS_DIR . 'class.Link.php';
require CLASS_DIR . 'class.Editor.php';
require CLASS_DIR . 'class.File_management.php';

if (isset($_POST['Insert'])) {
    $oWebotheque = new $weboClass();
    $oWebotheque->preTraitement();
    if ($idtf = $oWebotheque->checkMD5()) {
        setMsg(sprintf(gettext('md5check_fail'), $idtf), 'ERROR');
        if (isset($_POST['fromPopup'])) {
            header('Location:' . SERVER_ROOT . $_POST['fromPopup'] . '&idtf=' . $idtf);
        } else {
            header('Location:' . SERVER_ROOT . 'webotheque/web_' . $wbt_type . '.php?idtf=' . $idtf);
        }
        exit();
    }
    $stmt = $dbh->prepare("insert into WEBOTHEQUE (
        ID_WEBOTHEQUECATEGORIE,
        SIT_CODE,
        ID_UTILISATEUR,
        WBT_CODE,
        WEB_LIBELLE,
        WEB_DESCRIPTIONACC,
        WEB_DATECREATION,
        WEB_DATEMODIFICATION,
        WEB_MD5
        ) values (
        :ID_WEBOTHEQUECATEGORIE,
        :SIT_CODE,
        :ID_UTILISATEUR,
        :WBT_CODE,
        :WEB_LIBELLE,
        :WEB_DESCRIPTIONACC,
        :WEB_DATECREATION,
        :WEB_DATEMODIFICATION,
        :WEB_MD5
        )");
    $stmt->bindValue(':ID_WEBOTHEQUECATEGORIE', (is_numeric($_POST['ID_WEBOTHEQUECATEGORIE'])) ? $_POST['ID_WEBOTHEQUECATEGORIE'] : null, PDO::PARAM_INT);
    $stmt->bindValue(':SIT_CODE', CMS::getCurrentSite()->getID(), PDO::PARAM_STR);
    $stmt->bindValue(':ID_UTILISATEUR', Utilisateur::getConnected()->getID(), PDO::PARAM_INT);
    $stmt->bindValue(':WBT_CODE', 'WBT_' . strtoupper($wbt_type), PDO::PARAM_STR);
    $stmt->bindValue(':WEB_LIBELLE', $_POST['WEB_LIBELLE'], PDO::PARAM_STR);
    $stmt->bindValue(':WEB_DESCRIPTIONACC', isset($_POST['WEB_DESCRIPTIONACC']) ? $_POST['WEB_DESCRIPTIONACC'] : '', PDO::PARAM_STR);
    $stmt->bindValue(':WEB_DATECREATION', time(), PDO::PARAM_INT);
    $stmt->bindValue(':WEB_DATEMODIFICATION', time(), PDO::PARAM_INT);
    $stmt->bindValue(':WEB_MD5', $oWebotheque->getMD5(), PDO::PARAM_STR);
    $stmt->execute();
    $oWebotheque = new $weboClass($dbh->lastInsertID());
    if (WEB_DESCRIPTION) {
        Editor::updateContent($_POST['WEB_DESCRIPTION'], 'WEBOTHEQUE', 'WEB_DESCRIPTION', 'ID_WEBOTHEQUE', $oWebotheque->getID());
    }
    $oWebotheque->historize('CREATION');
    $oWebotheque->postTraitement();
    $oWebotheque->updateCategorie($_POST['CAT_LIBELLE'], $_POST['ID_WEBOTHEQUECATEGORIE']);

    if (isset($_POST['fromPopup'])) {
        header('Location:' . SERVER_ROOT . $_POST['fromPopup'] . '&idtf=' . $oWebotheque->getID());
    } else {
        setMsg(gettext('INSERT_OK'));
        header('Location:' . SERVER_ROOT . 'webotheque/web_' . $wbt_type . '.php?idtf=' . $oWebotheque->getID());
    }
    exit();

} elseif (isset($_POST['Update'])) {
    $oWebotheque = new $weboClass($_POST['idtf']);
    $oWebotheque->checkAuthorized();
    $oWebotheque->preTraitement();

    if ($idtf = $oWebotheque->checkMD5()) {
        setMsg(sprintf(gettext('md5check_fail'), $idtf), 'ERROR');
        header('Location:' . SERVER_ROOT . 'webotheque/web_' . $wbt_type . '.php?idtf=' . $oWebotheque->getID());
        exit();
    }

    $stmt = $dbh->prepare("update WEBOTHEQUE set
        ID_WEBOTHEQUECATEGORIE=:ID_WEBOTHEQUECATEGORIE,
        ID_UTILISATEUR=:ID_UTILISATEUR,
        WEB_LIBELLE=:WEB_LIBELLE,
        WEB_DESCRIPTIONACC=:WEB_DESCRIPTIONACC,
        WEB_DATEMODIFICATION=:WEB_DATEMODIFICATION,
        WEB_MD5=:WEB_MD5
        where ID_WEBOTHEQUE=:idtf");
    $stmt->bindValue(':ID_WEBOTHEQUECATEGORIE', (is_numeric($_POST['ID_WEBOTHEQUECATEGORIE'])) ? $_POST['ID_WEBOTHEQUECATEGORIE'] : null, PDO::PARAM_INT);
    $stmt->bindValue(':ID_UTILISATEUR', Utilisateur::getConnected()->getID(), PDO::PARAM_INT);
    $stmt->bindValue(':WEB_LIBELLE', $_POST['WEB_LIBELLE'], PDO::PARAM_STR);
    $stmt->bindValue(':WEB_DESCRIPTIONACC', isset($_POST['WEB_DESCRIPTIONACC']) ? $_POST['WEB_DESCRIPTIONACC'] : '', PDO::PARAM_STR);
    $stmt->bindValue(':WEB_DATEMODIFICATION', time(), PDO::PARAM_INT);
    $stmt->bindValue(':WEB_MD5', $oWebotheque->getMD5(), PDO::PARAM_STR);
    $stmt->bindValue(':idtf', $oWebotheque->getID(), PDO::PARAM_INT);
    $stmt->execute();
    Link::delete('WEBOTHEQUE', $oWebotheque->getID());
    if (WEB_DESCRIPTION) {
        Editor::updateContent($_POST['WEB_DESCRIPTION'], 'WEBOTHEQUE', 'WEB_DESCRIPTION', 'ID_WEBOTHEQUE', $oWebotheque->getID());
    }
    $oWebotheque->load(); // Rechargement afin d'avoir les infos mises à jour au sein de l'objet (notamment lors de l'upload d'un nouveau fichier)
    //à faire après le Link::delete pour bien gérer les liaisons éventuelles de WEB_DESCRIPTIONACC
    $oWebotheque->historize('MODIFICATION');
    $oWebotheque->postTraitement();
    $oWebotheque->updateReferants();
    $oWebotheque->updateCategorie($_POST['CAT_LIBELLE'], $_POST['ID_WEBOTHEQUECATEGORIE']);
    setMsg(gettext('UPDATE_OK'));

    header('Location:' . SERVER_ROOT . 'webotheque/web_' . $wbt_type . '.php?idtf=' . $oWebotheque->getID());
    exit();

} elseif (is_numeric($_GET['Delete'])) {
    $oWebotheque = new $weboClass($_GET['Delete']);
    $oWebotheque->checkAuthorized();
    if ($oWebotheque->delete()) {
        setMsg(gettext('DELETE_OK'));
    }
    header('Location:' . SERVER_ROOT . 'webotheque/web_' . $wbt_type . 'Liste.php');
    exit();

} elseif (isset($_POST['massDelete'])) {
    foreach ($_POST['del_web'] as $idtf) {
        $oWebotheque = new $weboClass($idtf);
        $oWebotheque->checkAuthorized();
        $oWebotheque->delete(false, true);
    }
    header('Location:' . SERVER_ROOT . 'webotheque/web_' . $wbt_type . 'Liste.php?Find=1');
    exit();

} elseif (isset($_GET['service'], $_GET['image'], $_GET['title'], $_GET['type'], $_GET['idtf'], $_GET['WBT_CODE']) &&
    $_GET['service'] == 'pixlr' && $_GET['WBT_CODE'] == 'WBT_IMAGE' && is_numeric($_GET['idtf'])
) {

    $oWebotheque = new $weboClass($_GET['idtf']);
    $oWebotheque->checkAuthorized();
    //on récupère l'image depuis le serveur pixlr
    $data = file_get_contents($_GET['image']);
    if (false === $data) {

        setMsg(gettext('Fichier') . ' : ' . gettext('erreur') . ' Le téléchargement de l\'image a échoué', 'ERROR');
        header('Location:' . SERVER_ROOT . 'webotheque/web_image.php?idtf=' . $oWebotheque->getID());
        exit();
    }
    //Enregistrement de l'image et suppression d'une éventuelle ancienne vignette
    require_once CLASS_DIR . 'class.File_management.php';
    $file  = UPLOAD_IMAGE_PHYSIQUE . dirname($oWebotheque->getField('WEB_CHEMIN')) . '/pixlr_' . $oWebotheque->getID() . substr($oWebotheque->getField('WEB_CHEMIN'), strrpos($oWebotheque->getField('WEB_CHEMIN'), '.'));
    $thumb = UPLOAD_IMAGE_PHYSIQUE . dirname($oWebotheque->getField('WEB_CHEMIN')) . '/THUMB/pixlr_' . $oWebotheque->getID() . substr($oWebotheque->getField('WEB_CHEMIN'), strrpos($oWebotheque->getField('WEB_CHEMIN'), '.'));

    file_put_contents($file, $data);
    File_management::deleteFromName($thumb);
    header('Location:' . SERVER_ROOT . 'webotheque/web_image.php?idtf=' . $oWebotheque->getID() . '&closepixlr=1');
    exit();
}
