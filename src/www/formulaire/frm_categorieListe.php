<?php
require '../include/inc.bo_init.php';
CMS::checkAccess(new Module('MOD_FORMULAIRE'), array('PRO_FORMGEST'));
require_once CLASS_DIR . 'class.db_formulaireCategorie.php';
require CLASS_DIR . 'class.Arbo.php';

if (isset($_GET['Clear'])) {
    FormulaireCategorie::clearCategorie();
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
    <?php $aMenuKey = array('FRM', 'CAT'); include('../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">
            <h2>Dossiers</h2>
            <?php echo Arbo::action() ?>
            <div class="bo_arbo">
                <?php echo FormulaireCategorie::getArbo() ?>
            </div>
            <form action="<?php echo PHP_SELF?>" method="get">
                <p class="action">
                    <input type="submit" name="Clear" value="Supprimer les dossiers sans éléments" class="supprimer">
                </p>
            </form>
        </div>
    </div>
    <?php include('../include/inc.bo_bandeau_bas.php')?>
</div>
</body>
</html>
