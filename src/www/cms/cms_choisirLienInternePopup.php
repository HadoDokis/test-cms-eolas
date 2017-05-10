<?php
require '../include/inc.bo_init.php';
Utilisateur::checkConnected();
require CLASS_DIR . 'class.Arbo.php';

$oArbo = new Arbo('LIENINTERNE');

$aSite = CMS::getCurrentSite()->getRevertSharedSites();
$aSite[CMS::getCurrentSite()->getID()] = CMS::getCurrentSite();

if (empty($_GET['SIT_CODE'])) {
    $_GET['SIT_CODE'] = CMS::getCurrentSite()->getID();
} elseif (!array_key_exists($_GET['SIT_CODE'], $aSite)) {
    die(gettext('Ressource_non_disponible'));
}
?>
<!DOCTYPE html>
<html>
<head>
    <?php include '../include/inc.bo_enTete.php' ?>
    <script>
        $(document).ready(cmsBO.initArbo);

        function choixLien(id, libelle)
        {
        <?php if ($_GET['IDENTIFIANT'] != '') { ?>
            <?php if ($_GET['CONCATENATION'] != '') { ?>
                if (window.opener.document.getElementById('<?php echo secureInput($_GET['IDENTIFIANT'])?>').value == '') {
                    window.opener.document.getElementById('<?php echo secureInput($_GET['IDENTIFIANT'])?>').value = id;
                } else {
                    window.opener.document.getElementById('<?php echo secureInput($_GET['IDENTIFIANT'])?>').value = window.opener.document.getElementById('<?php echo secureInput($_GET['IDENTIFIANT'])?>').value + '@' + id;
                }
                <?php if ($_GET['TEXTE'] != '') {?>
                window.opener.document.getElementById('<?php echo secureInput($_GET['TEXTE'])?>').value = window.opener.document.getElementById('<?php echo secureInput($_GET['IDENTIFIANT'])?>').value;
                <?php } ?>
           <?php } else { ?>
                window.opener.document.getElementById('<?php echo secureInput($_GET['IDENTIFIANT'])?>').value = id;
                <?php if ($_GET['TEXTE'] != '') { ?>
                    window.opener.document.getElementById('<?php echo secureInput($_GET['TEXTE'])?>').value = libelle + ' (' + id + ')';
                <?php } ?>
            <?php } ?>
        <?php } ?>

        <?php if ($_GET['AJAX'] != '') {?>
            window.opener.ajaxLiaison.getAjax('<?php echo secureInput($_GET['AJAX']) ?>','page','insert',id);
        <?php } ?>

        <?php if ($_GET['NOCLOSE'] == '') {?>
            window.close();
        <?php } ?>
        }
    </script>
</head>
<body id="popup">
    <?php include('../include/inc.bo_bandeau_hautPopup.php') ?>
    <div id="bo_contenuPopup">
        <h2>Choisir une page</h2>
        <?php if (count($aSite) > 1) {?>
        <div style="position: absolute; right: 20px;">
            <select id="SIT_CODE" name="SIT_CODE" onchange="window.location.href='<?php echo PHP_SELF?>?SIT_CODE='+this.value+'&IDENTIFIANT=<?php echo urlencode($_GET['IDENTIFIANT'])?>&AJAX=<?php echo urlencode($_GET['AJAX'])?>&CONCATENATION=<?php echo urlencode($_GET['CONCATENATION'])?>&TEXTE=<?php echo urlencode($_GET['TEXTE'])?>&NOCLOSE=<?php echo urlencode($_GET['NOCLOSE'])?>';">
                <?php foreach ($aSite as $_oSite) { ?>
                <option value="<?php echo $_oSite->getID()?>"<?php if ($_GET['SIT_CODE'] == $_oSite->getID()) echo ' selected';?>>
                    <?php echo secureInput($_oSite->getField('SIT_LIBELLE'))?>
                </option>
                <?php } ?>
            </select>
        </div>
        <?php } ?>
        <?php echo Arbo::action()?>
        <?php echo $oArbo->draw($aSite[$_GET['SIT_CODE']]->getHomePage()->getID()) ?>
    </div>
    <?php include('../include/inc.bo_bandeau_basPopup.php') ?>
</body>
</html>
