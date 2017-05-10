<?php
require '../include/inc.bo_init.php';
$WBT_CODE = $_GET['WBT_CODE'];
$modCode  = str_replace('WBT_', 'MOD_WEBOTHEQUE_', $WBT_CODE);
$proCode  = str_replace('WBT_', 'PRO_WEB', $WBT_CODE);
CMS::checkAccess(new Module($modCode), array($proCode, 'PRO_WEBROOT'));
require CLASS_DIR . 'class.db_webotheque.php';
require CLASS_DIR . 'class.db_webothequeCategorie.php';
require CLASS_DIR . 'class.File_management.php';

//Génération d'un libelle dynamique en fonction du type traité
$wbt_libelle = "";
if ($WBT_CODE=='WBT_IMAGE') {
    $wbt_libelle = gettext('Images');
} elseif ($WBT_CODE=='WBT_DOCUMENT') {
    $wbt_libelle = gettext('Documents');
}
?>
<!DOCTYPE html>
<html>
<head>
<?php include('../include/inc.bo_enTete.php') ?>
<script src="<?php echo SERVER_ROOT ?>include/js/plupload/js/plupload.full.js"></script>
<script src="<?php echo SERVER_ROOT ?>include/js/multiUpload.js"></script>
<?php
if (file_exists(PHYSICAL_PATH.'include/js/plupload/js/i18n/'.substr($_SESSION['S_LNG_CODE'], 0, 2).'.js')) {
    echo '<script src="' . SERVER_ROOT . 'include/js/plupload/js/i18n/'.substr($_SESSION['S_LNG_CODE'], 0, 2).'.js"></script>';
}
?>
<script>
    multiUpload.settings.wbt_code = '<?php echo $WBT_CODE ?>';
    multiUpload.settings.max_file_size = '<?php echo File_management::getMaxUpload() ?>';
    multiUpload.settings.errors.uploadExtention = '<?php echo escapeJS(gettext('Extension incorrecte')) ?>';
    multiUpload.settings.errors.uploadSize = '<?php echo escapeJS(gettext('taille maximum')) ?>';
    multiUpload.settings.type.libelle = '<?php echo escapeJS($wbt_libelle) ?>';
    multiUpload.settings.type.extentions = '<?php echo str_replace('.', '', implode(',', Webotheque::getExtension($WBT_CODE))) ?>';
</script>
</head>
<body>
<div id="document">
    <?php $aMenuKey = array('WEB', str_replace('WBT_', 'MOD_WEBOTHEQUE_', $_REQUEST['WBT_CODE']), str_replace('WBT_', '', $_REQUEST['WBT_CODE']), 'MULTI'); include('../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">
            <h2><?php echo secureInput($wbt_libelle . " - Import multiple"); ?></h2>
            <form method="post" action="#" class="creation" enctype="multipart/form-data">
                <fieldset>
                    <legend><?php echo gettext('Proprietes')?></legend>
                    <table>
                        <tfoot>
                            <tr>
                                <td colspan="2">
                                    <input type="button" class="ajouter" name="uploadfiles" id="uploadfiles" value="<?php echo gettext('impMult_telecharger_fichiers') ?>">
                                    <input type="button" class="supprimer" name="resetfiles" id="resetfiles" value="<?php echo gettext('impMult_vider_fichiers') ?>">
                                </td>
                            </tr>
                        </tfoot>
                        <tbody>
                            <tr>
                                <th><label for="<?php echo (WebothequeCategorie::getNb($WBT_CODE) == 0) ? 'CAT_LIBELLE' : 'ID_WEBOTHEQUECATEGORIE'?>" class="isNotNull"><?php echo gettext('Categorie')?> *</label></th>
                                <td>
                                    <select name="ID_WEBOTHEQUECATEGORIE" id="ID_WEBOTHEQUECATEGORIE" class="disableDuringImport">
                                        <?php echo WebothequeCategorie::getSelectOptions($WBT_CODE, null) ?>
                                    </select> /
                                    <input type="text" name="CAT_LIBELLE" id="CAT_LIBELLE" size="30" placeholder="<?php echo gettext('Ajouter_dossier')?>" class="disableDuringImport">
                                    <input type="hidden" id="WBT_CODE" name="WBT_CODE" value="<?php echo $WBT_CODE ?>">
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <div id="dropzone">Faites glisser vos fichiers, images  dans cette zone ou sélectionnez des fichiers sur votre ordinateur</div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">Taille maximum par fichier : <?php echo File_management::getMaxUpload()?></td>
                            </tr>
                            <tr>
                                <td colspan="2" id="uploadContainer">
                                    <a class="btnAction" id="pickfiles" href="#">Sélectionner des fichiers sur votre ordinateur</a>
                                    <div id="filelist" class="clearfix"><?php echo gettext('impMult_votre_navigateur_ne_supporte_pas') ?></div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </fieldset>
            </form>
        </div>
    </div>
    <?php include('../include/inc.bo_bandeau_bas.php') ?>
</div>
</body>
</html>
