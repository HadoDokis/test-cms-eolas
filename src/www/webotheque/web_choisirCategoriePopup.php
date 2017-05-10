<?php
require '../include/inc.bo_init.php';
Utilisateur::checkConnected();
require CLASS_DIR . 'class.db_webothequeCategorie.php';
require CLASS_DIR . 'class.Arbo.php';

$WBT_CODE = $_GET['WBT_CODE'];
$modCode  = str_replace('WBT_', 'MOD_WEBOTHEQUE_', $WBT_CODE);

//On traite les cas où, soit le type n'est pas fourni, soit le module n'est pas activé
if (!isset($WBT_CODE) || !CMS::getCurrentSite()->hasModule(new Module($modCode))) {
    die('Ressource non disponible');
}

$aSite = CMS::getCurrentSite()->getRevertSharedSites();
$aSite[CMS::getCurrentSite()->getID()] = CMS::getCurrentSite();

if (empty($_GET['SIT_CODE'])) {
    $_GET['SIT_CODE'] = CMS::getCurrentSite()->getID();
} elseif (!array_key_exists($_GET['SIT_CODE'], $aSite)) {
    die('Ressource non disponible');
}

$filtre_sites = ' and SIT_CODE in (' . implode(',', array_map(array($dbh, 'quote'), array_keys($aSite))) . ') ';
?>
<!DOCTYPE html>
<html>
<head>
    <?php include('../include/inc.bo_enTete.php'); ?>
    <script>
        $(document).ready(cmsBO.initArbo);

        function maj(id, libelle)
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
            window.opener.ajaxLiaison.getAjax('<?php echo secureInput($_GET['AJAX']) ?>', 'externe', 'insert',id);
        <?php } ?>

        <?php if ($_GET['NOCLOSE'] == '') {?>
            window.close();
        <?php } ?>
        }
    </script>
</head>
<body id="popup">
    <?php include('../include/inc.bo_bandeau_hautPopup.php') ?>
    <div id="bo_contenuPopup" style="position:relative;">
        <h2>Choisir un dossier</h2>
        <?php if (count($aSite) > 1) {?>
        <div style="position:absolute; right:20px; top:70px;">
            <label for="SIT_CODE"><?php echo gettext('Site courant')?></label>
            <select id="SIT_CODE" name="SIT_CODE" onchange="window.location.href='<?php echo PHP_SELF?>?SIT_CODE='+this.value+'&IDENTIFIANT=<?php echo urldecode($_GET['IDENTIFIANT'])?>&TEXTE=<?php echo urldecode($_GET['TEXTE'])?>&WBT_CODE=<?php echo urldecode($WBT_CODE) ?>';">
            <?php foreach ($aSite as $_oSite) { ?>
                <option value="<?php echo $_oSite->getID()?>"<?php if ($_GET['SIT_CODE'] == $_oSite->getID()) echo ' selected';?>>
                    <?php echo secureInput($_oSite->getField('SIT_LIBELLE'))?>
                </option>
            <?php } ?>
            </select>
        </div>
        <?php } ?>
        <?php echo Arbo::action() ?>
        <div class="bo_arbo">
            <?php echo WebothequeCategorie::getArbo($_GET['WBT_CODE'], null, $_GET['SIT_CODE'], true) ?>
        </div>
    </div>
    <?php include('../include/inc.bo_bandeau_basPopup.php')?>
</body>
</html>
