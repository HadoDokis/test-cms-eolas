<?php
require '../include/inc.bo_init.php';
CMS::checkAccess(new Module('MOD_LANGUISME'), array('PRO_LANGUISME'));
require CLASS_DIR . 'class.db_languisme.php';

$oLanguisme = new Languisme($_GET['idtf']);
if ($oLanguisme->exist()) {
        $oLanguisme->checkAuthorized();
}
$row = $oLanguisme->getFields();
?>
<!DOCTYPE html>
<html>
<head>
    <?php include('../include/inc.bo_enTete.php') ?>
</head>
<body>
<div id="document">
    <?php $aMenuKey = array('CFG', 'SITE', 'MOD_LANGUISME'); if (!$oLanguisme->exist()) $aMenuKey[]='ADD'; include('../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">
            <h2><?php echo gettext('Languisme')?></h2>
            <form method="post" action="cms_languismeSubmit.php" class="creation">
                <fieldset>
                    <legend><?php echo gettext('Informations')?></legend>
                    <table>
                        <tfoot>
                            <tr>
                                <td colspan="2">
                                    <?php if ($oLanguisme->exist()) { ?>
                                    <input type="hidden" name="idtf" value="<?php echo $oLanguisme->getID()?>">
                                    <input type="submit" name="Update" value="<?php echo gettext('UPDATE')?>" class="modifier">
                                    <input type="button" name="Delete" value="<?php echo gettext('DELETE')?>" class="supprimer" onclick="if (confirm('<?php echo gettext('Etes-vous sur ?')?>')) window.location.href='cms_languismeSubmit.php?Delete=<?php echo $oLanguisme->getID()?>'">
                                    <?php } else { ?>
                                    <input type="submit" name="Insert" value="<?php echo gettext('INSERT')?>" class="ajouter">
                                    <?php } ?>
                                </td>
                            </tr>
                        </tfoot>
                        <tbody>
                            <tr>
                                <th><label for="LNG_LIBELLE"><?php echo gettext('Libelle')?></label></th>
                                <td><input name="LNG_LIBELLE" type="text" id="LNG_LIBELLE" value="<?php echo secureInput($row['LNG_LIBELLE'])?>" size="50" required></td>
                            </tr>
                            <tr>
                                <th><label for="LNG_LANGUE"><?php echo gettext('Langue')?></label></th>
                                <td>
                                    <select name="LNG_LANGUE" id="LNG_LANGUE" required>
                                        <?php foreach (CMS::getLangueArray() as $key => $val) { ?>
                                        <option value="<?php echo $key?>"<?php if ($key == $row['LNG_LANGUE'] || ($key=='fr' && $row['LNG_LANGUE'] == '')) echo ' selected'?>><?php echo $val?></option>
                                        <?php } ?>
                                    </select>
                                </td>
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
