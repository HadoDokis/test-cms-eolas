<?php
require '../include/inc.bo_init.php';

$WBT_CODE = $_POST['WBT_CODE'];

CMS::checkAccess(new Module(str_replace('WBT_', 'MOD_WEBOTHEQUE_', $WBT_CODE)), array('PRO_WEBROOT'));

require CLASS_DIR . 'class.db_webotheque.php';
require CLASS_DIR . 'class.File_management.php';

$wbt_type = '';
switch ($WBT_CODE) {
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
}

//Traitement de l'import des éléments
if (isset($_POST['import']) && isset($_POST['add_import'])) {

    $ID_WEBOTHEQUECATEGORIE = $_POST['ID_WEBOTHEQUECATEGORIE'];
    //Insertion d'une éventuelle nouvelle catégorie
    if ($_POST['CAT_LIBELLE'] != '') {
        $stmt = $dbh->prepare("insert into WEBOTHEQUECATEGORIE (
                SIT_CODE,
                WBT_CODE,
                CAT_IDPARENT,
                CAT_LIBELLE
                ) values (
                :SIT_CODE,
                :WBT_CODE,
                :CAT_IDPARENT,
                :CAT_LIBELLE
                )");
        $stmt->bindValue(':SIT_CODE', CMS::getCurrentSite()->getID(), PDO :: PARAM_STR);
        $stmt->bindValue(':WBT_CODE', $WBT_CODE, PDO :: PARAM_STR);
        $stmt->bindValue(':CAT_IDPARENT', (is_numeric($_POST['ID_WEBOTHEQUECATEGORIE'])) ? $_POST['ID_WEBOTHEQUECATEGORIE'] : null, PDO :: PARAM_INT);
        $stmt->bindValue(':CAT_LIBELLE', $_POST['CAT_LIBELLE'], PDO :: PARAM_STR);
        $stmt->execute();
        $ID_WEBOTHEQUECATEGORIE = $dbh->lastInsertID();
    }
    $errorMsg = '';
    $bErreurBoucle = $erreurMd5 = false;
    $weboClass = 'Webo_' . strtoupper($wbt_type);
    //Parcours des fichiers cochés et ajout dans la webothèque dans la catégorie choisie
    foreach ($_POST['add_import'] as $numImport) {
        $oWebo = new $weboClass();
        //Insertion de chaque élément en webothèque
        //Si une erreur intervient sur un fichier, on stoppe la boucle
        if ($idtf = $oWebo->checkMD5(Webotheque::getImportFtpPhysicalDir($WBT_CODE) . $_POST['WEB_FILE_'.$numImport])) {
            setMsg(sprintf(gettext('md5check_fail'), $idtf), 'ERROR');
            $erreurMd5 = true;
        }
        if(!$erreurMd5 && !Webotheque::insertWebotheque($wbt_type,
                                        ($_POST['WEB_LIBELLE_'.$numImport]!=''?$_POST['WEB_LIBELLE_'.$numImport]:current(explode('.', $_POST['WEB_FILE_'.$numImport]))),
                                        $ID_WEBOTHEQUECATEGORIE,
                                        $_POST['WEB_FILE_'.$numImport],
                                        true,
                                        $errorMsg,
                                        $oWebo->getMD5(),
                                        'FTP')) {
            $bErreurBoucle = true;
            break;
        }
    }
    if (!$bErreurBoucle) {
       setMsg(gettext('INSERT_OK'));
    } elseif (!$erreurMd5) {
       setMsg($errorMsg, 'ERROR');
    }
} elseif (isset($_POST['Delete']) && isset($_POST['add_delete'])) {
    $FTP_DIR = Webotheque::getImportFtpPhysicalDir($WBT_CODE);
    foreach ($_POST['add_delete'] as $file) {
        if (file_exists($FTP_DIR . $file)) {
            unlink($FTP_DIR . $file);
        }
    }
    setMsg(gettext('DELETE_OK'));
}
//Redirection vers la liste des fichiers
header('Location:' . SERVER_ROOT . 'webotheque/web_importFTP.php?WBT_CODE=' . $WBT_CODE);
exit();
