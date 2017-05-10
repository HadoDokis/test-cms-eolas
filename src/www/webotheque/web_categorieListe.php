<?php
require '../include/inc.bo_init.php';
CMS::checkAccess(new Module(str_replace('WBT_', 'MOD_WEBOTHEQUE_', $_GET['WBT_CODE'])), array('PRO_WEB' . str_replace('WBT_', '', $_GET['WBT_CODE']), 'PRO_WEBROOT'));
require CLASS_DIR . 'class.db_webothequeCategorie.php';
require CLASS_DIR . 'class.Arbo.php';

if (isset($_GET['Clear']) && isset($_GET['WBT_CODE'])) {
    WebothequeCategorie::clearCategorie($_GET['WBT_CODE']);
}
?>
<!DOCTYPE html>
<html>
<head>
    <?php include('../include/inc.bo_enTete.php'); ?>
    <script>
        $(document).ready(cmsBO.initArbo);
    </script>
</head>
<body>
<div id="document">
    <?php $aMenuKey = array('WEB', str_replace('WBT_', 'MOD_WEBOTHEQUE_', $_REQUEST['WBT_CODE']), 'CAT'); include('../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">
            <h2 class="titreArbo">Dossiers</h2>
            <?php echo Arbo::action() ?>
            <div class="bo_arbo">
                <?php echo WebothequeCategorie::getArbo($_GET['WBT_CODE']) ?>
            </div>
            <form action="<?php echo PHP_SELF?>" method="get">
                <p class="action">
                    <input type="submit" name="Clear" value="Supprimer les dossiers sans éléments" class="supprimer">
                    <input type="hidden" name="WBT_CODE" value="<?php echo secureInput($_GET['WBT_CODE'])?>">
                </p>
            </form>
        </div>
    </div>
    <?php include('../include/inc.bo_bandeau_bas.php')?>
</div>
</body>
</html>
