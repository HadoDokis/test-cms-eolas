<?php
require '../include/inc.bo_init.php';
$WBT_CODE = $_GET['WBT_CODE'];
CMS::checkAccess(new Module(str_replace('WBT_', 'MOD_WEBOTHEQUE_', $WBT_CODE)), array('PRO_WEBROOT'));
require CLASS_DIR . 'class.db_webotheque.php';
require CLASS_DIR . 'class.db_webothequeCategorie.php';
require CLASS_DIR . 'class.File_management.php';

//Génération d'un libelle dynamique en fonction du type traité
$wbt_libelle = $wbt_type = "";
if ($WBT_CODE=='WBT_IMAGE') {
    $wbt_libelle = gettext('Images');
    $wbt_type = 'image';
} elseif ($WBT_CODE=='WBT_DOCUMENT') {
    $wbt_libelle = gettext('Documents');
    $wbt_type = 'document';
} elseif ($WBT_CODE=='WBT_FLASH') {
    $wbt_libelle = gettext('Flash');
    $wbt_type = 'flash';
} elseif ($WBT_CODE=='WBT_MUSIC') {
    $wbt_libelle = gettext('Audios');
    $wbt_type = 'music';
} elseif ($WBT_CODE=='WBT_VIDEO') {
    $wbt_libelle = gettext('Videos');
    $wbt_type = 'video';
}
$weboClass = 'Webo_' . strtoupper($wbt_type);
$oWebo = new $weboClass();
?>
<!DOCTYPE html>
<html>
<head>
    <?php include('../include/inc.bo_enTete.php') ?>
<script>
(function () {
    "use strict";
    var checkAll = {
        init: function () {
            $('input[data-role="checkall"]').each(function () {
                $(this).click(checkAll.toogleSelectAll);
                checkAll.getRelated(this).click(function () {
                    var $relatedCheckbox = $('input[data-role="checkall"][data-rel="' + $(this).data('rel') + '"]'),
                        bChecked = (checkAll.getRelated($relatedCheckbox[0]).not(':checked').length === 0);
                    $relatedCheckbox.prop('checked', bChecked);
                });
            });
        },
        toogleSelectAll: function () {
            checkAll.getRelated(this).prop('checked', $(this).prop('checked'));
        },
        getRelated: function (elmt) {
            return $('input[data-rel="' + $(elmt).data('rel') + '"]:not(:disabled)')
                .not(elmt);
        }
    };
    $(document).ready(checkAll.init);
} ());
</script>
</head>
<body>
<div id="document">
    <?php $aMenuKey = array('WEB', str_replace('WBT_', 'MOD_WEBOTHEQUE_', $_REQUEST['WBT_CODE']), str_replace('WBT_', '', $_REQUEST['WBT_CODE']), 'FTP'); include('../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">
            <h2><?php echo secureInput($wbt_libelle) . " - " . gettext('importFTP_ajout'); ?></h2>
            <form method="post" action="web_importFTPSubmit.php" class="creation" enctype="multipart/form-data">
                <fieldset>
                    <legend><?php echo gettext('importFTP_fichiers')?></legend>
                    <table>
                        <tfoot>
                            <tr>
                                <td colspan="2">
                                    <input type="hidden" value="<?php echo $WBT_CODE ?>" name="WBT_CODE">
                                    <input type="submit" name="import" value="<?php echo gettext('importFTP_insert') ?>" class="ajouter">
                                </td>
                            </tr>
                        </tfoot>
                        <tbody>
                        <?php if (!$hd = @opendir(Webotheque::getImportFtpPhysicalDir($WBT_CODE))) {?>
                            <tr>
                                <td colspan="2"><?php echo gettext('importFTP_erreur_repertoire') ?></td>
                            </tr>
                        <?php } else { ?>
                            <tr>
                                <td colspan="2">
                                    <table class="liste">
                                        <thead>
                                            <tr>
                                                <th><input type="checkbox" name="add_import_all" id="add_import_all" data-role="checkall" data-rel="checkImport" value=""></th>
                                                <th><?php echo gettext('importFTP_nom_fichier')?></th>
                                                <th><?php echo gettext('Poids')?></th>
                                                <th><?php echo gettext('Libelle')?></th>
                                            </tr>
                                        </thead>
                        <?php
                        //Initialisation des compteurs
                        $_nbFichiers = $_nbErreurs = 0;
                        $num =0;
                        $aMD5Error = array();
                        //Parcours du dossier fichier par fichier
                        while (($file = readdir($hd)) !== false) {
                            //On prend tout sauf les répertoires
                            if (!is_dir($file)) {
                                $num++;
                                //Booleen permettant de savoir si le fichier est valide pour la webothèque
                                $weboValide=false;
                                //le fichier est valide s'il l'extension est autorisée
                                if (in_array(".".strtolower(end(explode('.', $file))), Webotheque::getExtension($WBT_CODE) )) {
                                    $weboValide=true;
                                } else {
                                    $_nbErreurs++;
                                }
                                $_nbFichiers++;
                                $bCheckMd5Failed = false;
                                if ($idtfDoublon = $oWebo->checkMD5(Webotheque::getImportFtpPhysicalDir($WBT_CODE) . $file)) {
                                    $oWeboDoublon = new $weboClass($idtfDoublon);
                                    $bCheckMd5Failed = $oWeboDoublon->exist();
                                    $aMD5Error[$file] = $oWeboDoublon;
                                    continue;
                                } ?>
                            <tr class="<?php echo ($bCheckMd5Failed ? ' md5Failed' : '')?>">
                                <td class="aligncenter">
                                    <input type="checkbox" data-rel="checkImport" data-rel="checkImport" <?php if (!$weboValide || $bCheckMd5Failed) {echo ' class="checkbox disabled" disabled';} else {echo ' class="checkbox"';}?> name="add_import[]" value="<?php echo $num ?>" onclick="document.getElementById('add_import_all').checked=false;" >
                                </td>
                                <td>
                                    <?php echo secureInput($file);?>
                                    <input type="hidden" value="<?php echo secureInput($file);?>" name="WEB_FILE_<?php echo $num ?>">
                                </td>
                                <td class="alignright"><?php echo File_management::displayFileSize(filesize(Webotheque::getImportFtpPhysicalDir($WBT_CODE) . $file)) ?></td>
                                <td>
                                <?php if ($weboValide) {?>
                                    <input name="WEB_LIBELLE_<?php echo $num ?>" type="text" id="WEB_LIBELLE" value="<?php echo current(explode('.', $file)) ?>" size="40" maxlength="255">
                                <?php } ?>
                                </td>
                            </tr>
                            <?php }
                        }
                        //Fermeture du flux de lecture
                        closedir($hd);
                        ?>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <div class="blocNavigation">
                        <?php
                        //Affichage des compteurs de resultats
                        if ($_nbFichiers == 0) { ?>
                            <?php echo gettext('importFTP_noResultats')?>
                        <?php } else { ?>
                            <?php echo $_nbFichiers?> <?php echo gettext('importFTP_nbResultats')?>
                        <?php }
                        if ($_nbErreurs > 0) { ?>
                            <br><?php echo $_nbErreurs ?> <?php echo gettext('importFTP_nbErreurs')?>
                        <?php } ?>
                        </div>
                    </td>
                </tr>
            <?php } ?>
                <tr>
                    <th><label for="<?php echo (WebothequeCategorie::getNb($WBT_CODE) == 0) ? 'CAT_LIBELLE' : 'ID_WEBOTHEQUECATEGORIE'?>" class="isNotNull"><?php echo gettext('Categorie')?> *</label></th>
                    <td>
                        <select name="ID_WEBOTHEQUECATEGORIE" id="ID_WEBOTHEQUECATEGORIE">
                        <?php echo WebothequeCategorie::getSelectOptions($WBT_CODE, null) ?>
                        </select>
                        <input type="text" name="CAT_LIBELLE" id="CAT_LIBELLE" size="30" placeholder="<?php echo gettext('Ajouter_dossier')?>">
                    </td>
                </tr>

                <?php if ($WBT_CODE == 'WBT_VIDEO' || $WBT_CODE == 'WBT_FLASH') { ?>
                <tr>
                    <th><label for="WEB_LARGEUR"><?php echo gettext('Largeur')?></label></th>
                    <td><input name="WEB_LARGEUR" type="text" id="WEB_LARGEUR" value="600" size="4" maxlength="6" data-type="integer" required> px</td>
                </tr>
                <tr>
                    <th><label for="WEB_HAUTEUR"><?php echo gettext('Hauteur')?></label></th>
                    <td><input name="WEB_HAUTEUR" type="text" id="WEB_HAUTEUR" value="400" size="4" maxlength="6" data-type="integer" required> px</td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
        </fieldset>
        <?php if (count($aMD5Error) > 0) { ?>
        <fieldset class="creation">
            <legend><?php echo gettext('fichiers_deja_existants') ?></legend>
            <table class="liste">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="add_delete_all" data-role="checkall" data-rel="checkDelete" name="add_delete_all" value=""></th>
                        <th><?php echo gettext('importFTP_nom_fichier')?></th>
                        <th><?php echo gettext('Poids')?></th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <td colspan="3" class="alignleft">
                            <input type="submit" class="supprimer" name="Delete" value="<?php echo gettext('suppression_massive')?>">
                        </td>
                    </tr>
                </tfoot>
                <tbody>
                    <?php foreach ($aMD5Error as $file => $oWeboDoublon) { ?>
                    <tr>
                        <td class="aligncenter">
                            <input type="checkbox" data-rel="checkDelete" name="add_delete[]" value="<?php echo secureInput($file) ?>">
                        </td>
                        <td><?php echo secureInput($file);?></td>
                        <td class="alignright"><?php echo File_management::displayFileSize(filesize(Webotheque::getImportFtpPhysicalDir($WBT_CODE) . $file)) ?></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </fieldset>
        <?php } ?>
      </form>
    </div>
  </div>
  <?php include('../include/inc.bo_bandeau_bas.php')?>
</div>
</body>
</html>
