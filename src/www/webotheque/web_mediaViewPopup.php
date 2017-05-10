<?php
require '../include/inc.bo_init.php';
Utilisateur::checkConnected();
require CLASS_DIR . 'class.db_webotheque.php';

$oWebotheque = new Webotheque($_GET['idtf']);
$oWebotheque->checkAuthorized();

//On traite les cas où, soit le type n'est pas correct, soit le module n'est pas activé
$WBT_CODE = $oWebotheque->getField('WBT_CODE');
if (!in_array($WBT_CODE, array('WBT_FLASH', 'WBT_VIDEO')) || !CMS::getCurrentSite()->hasModule(new Module(str_replace('WBT_', 'MOD_WEBOTHEQUE_', $WBT_CODE)))) {
    die('Ressource non disponible');
}
$weboClass = 'Webo_' . str_replace('WBT_', '', $WBT_CODE);
$oWebotheque = new $weboClass($_GET['idtf']);
?>
<!DOCTYPE html>
<html>
<head>
    <?php include('../include/inc.bo_enTete.php')?>
    <?php if ($WBT_CODE == 'WBT_VIDEO') { ?>
    <script src="<?php echo SERVER_ROOT ?>include/flashplayer/jwplayer/jwplayer.js"></script>
    <script src="<?php echo SERVER_ROOT ?>include/js/audiodescription.js"></script>
    <?php } ?>
</head>
<body id="popup">
    <?php include('../include/inc.bo_bandeau_hautPopup.php')?>
    <div id="bo_contenuPopup">
        <h2><?php echo secureInput($oWebotheque->getField('WEB_LIBELLE'))?></h2>
        <div class="aligncenter"><?php echo $oWebotheque->getHTML()?></div>
    </div>
    <?php include('../include/inc.bo_bandeau_basPopup.php')?>
</body>
</html>
