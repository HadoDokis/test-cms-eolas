<?php
require '../include/inc.bo_init.php';
CMS::checkAccess(new Module('MOD_REFERENCEMENT'), array('PRO_REFERENCEMENT'));
require CLASS_DIR . 'class.db_rechercheReferencement.php';
require CLASS_DIR . 'class.Sitemap.php';
require CLASS_DIR . 'class.Editor.php';

$oRechercheReferencement = new RechercheReferencement($_GET['idtf']);
$row = $oRechercheReferencement->getFields();
if ($row['REC_GOOGLEPRIORITE'] == '') {
    $row['REC_GOOGLEPRIORITE'] = Sitemap::DEFAULT_PRIORITY;
}
if ($row['REC_GOOGLEFREQUENCE'] == '') {
    $row['REC_GOOGLEFREQUENCE'] = Sitemap::DEFAULT_FREQUENCY;
}
?>
<!DOCTYPE html>
<html>
<head>
    <?php include('../include/inc.bo_enTete.php') ?>
    <?php Editor::header() ?>
    <script>
        editorInit('module', new Array('REC_DESCRIPTION'));
    </script>
</head>
<body>
<div id="document">
    <?php $aMenuKey = array('CFG', 'SITE', 'MOD_REFERENCEMENT'); if (!$oRechercheReferencement->exist()) $aMenuKey[]='ADD'; include('../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">
            <h2><?php echo $oRechercheReferencement->exist() ? secureInput($oRechercheReferencement->getLibelle()) : 'Nouvelle recherche DMK' ?></h2>
            <form method="post" action="cms_rechercheReferencementSubmit.php" class="creation">
                <fieldset>
                    <legend><?php echo gettext('Informations')?></legend>
                    <table>
                        <tfoot>
                            <tr>
                                <td colspan="2">
                                    <?php if ($oRechercheReferencement->exist()) { ?>
                                    <input type="hidden" name="idtf" value="<?php echo $oRechercheReferencement->getID()?>">
                                    <input type="submit" name="Update" value="<?php echo gettext('UPDATE')?>" class="modifier">
                                    <input type="button" name="Delete" value="<?php echo gettext('DELETE')?>" class="supprimer confirm" data-href="cms_rechercheReferencementSubmit.php?Delete=<?php echo $oRechercheReferencement->getID()?>">
                                    <?php } else { ?>
                                    <input type="submit" name="Insert" value="<?php echo gettext('INSERT')?>" class="ajouter">
                                    <?php } ?>
                                </td>
                            </tr>
                        </tfoot>
                        <tbody>
                            <tr>
                                <th><label for="REC_TITLE">Title</label></th>
                                <td><input name="REC_TITLE" type="text" id="REC_TITLE" value="<?php echo secureInput($row['REC_TITLE'])?>" size="50" required></td>
                            </tr>
                            <tr>
                                <th><label for="REC_EXPRESSION">Expression recherchée</label></th>
                                <td><input name="REC_EXPRESSION" type="text" id="REC_EXPRESSION" value="<?php echo secureInput($row['REC_EXPRESSION'])?>" size="40" required></td>
                            </tr>
                            <tr>
                                <th><label for="REC_RESUME">Résumé</label></th>
                                <td><textarea name="REC_RESUME" cols="60" rows="4" id="REC_RESUME" data-maxchar="200" required><?php echo secureInput($row['REC_RESUME'])?></textarea></td>
                            </tr>
                            <tr>
                                <th><label for="REC_DESCRIPTION">Description</label></th>
                                <td><textarea name="REC_DESCRIPTION" cols="60" rows="8" id="REC_DESCRIPTION" style="width:100%"><?php echo secureInput($row['REC_DESCRIPTION'])?></textarea></td>
                            </tr>
                            <tr>
                                <th><label for="REC_GOOGLEPRIORITE">Priorité</label></th>
                                <td>
                                    <select name="REC_GOOGLEPRIORITE" id="REC_GOOGLEPRIORITE" required>
                                        <?php foreach (Sitemap::getPriorityList() as $EXT_GOOGLEPRIORITE) {?>
                                        <option value="<?php echo secureInput($EXT_GOOGLEPRIORITE) ?>"<?php if($EXT_GOOGLEPRIORITE == $row['REC_GOOGLEPRIORITE']) echo ' selected'?>><?php echo secureInput($EXT_GOOGLEPRIORITE) ?></option>
                                        <?php } ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="REC_GOOGLEFREQUENCE">Fréquence</label></th>
                                <td>
                                    <select name="REC_GOOGLEFREQUENCE" id="REC_GOOGLEFREQUENCE" required>
                                        <?php foreach (Sitemap::getFrequenceList() as $EXT_GOOGLEFREQUENCE) { ?>
                                        <option value="<?php echo secureInput($EXT_GOOGLEFREQUENCE) ?>"<?php if($EXT_GOOGLEFREQUENCE == $row['REC_GOOGLEFREQUENCE']) echo ' selected'?>><?php echo secureInput($EXT_GOOGLEFREQUENCE) ?></option>
                                        <?php } ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="REC_GOOGLELASTMOD">Dernière modification</label></th>
                                <td><input name="REC_GOOGLELASTMOD" type="text" id="REC_GOOGLELASTMOD" value="<?php if ($row['REC_GOOGLELASTMOD'] > 0) echo date('d/m/Y', $row['REC_GOOGLELASTMOD'])?>" data-type="date" data-subtype="now" required></td>
                            </tr>
                        </tbody>
                    </table>
                </fieldset>
            </form>
        </div>
    </div>
    <?php include('../include/inc.bo_bandeau_bas.php')?>
</div>
</body>
</html>
