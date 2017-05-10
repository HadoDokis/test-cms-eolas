<?php
require '../include/inc.bo_init.php';
CMS::checkAccess(new Module('MOD_ABREVIATION'), array('PRO_ABREVIATION'));
require CLASS_DIR . 'class.db_abreviation.php';

$oAbreviation = new Abreviation($_GET['idtf']);
if ($oAbreviation->exist()) {
    $oAbreviation->checkAuthorized();
}
$row = $oAbreviation->getFields();
?>
<!DOCTYPE html>
<html>
<head>
<?php include('../include/inc.bo_enTete.php') ?>
</head>
<body>
<div id="document">
  <?php $aMenuKey = array('CFG', 'SITE', 'MOD_ABREVIATION'); if (!$oAbreviation->exist()) $aMenuKey[]='ADD'; include('../include/inc.bo_bandeau_haut.php') ?>
  <div id="corps">
    <div id="bo_contenu">
      <h2><?php echo gettext('Forme abregee')?></h2>
      <form method="post" action="cms_formeAbregeeSubmit.php" class="creation">
        <fieldset>
        <legend><?php echo gettext('Informations')?></legend>
        <table>
          <tfoot>
            <tr>
              <td colspan="2">
                <?php if ($oAbreviation->exist()) { ?>
                <input type="hidden" name="idtf" value="<?php echo $oAbreviation->getID()?>">
                <input type="submit" name="Update" value="<?php echo gettext('UPDATE')?>" class="modifier">
                <input type="button" name="Delete" value="<?php echo gettext('DELETE')?>" class="supprimer" onclick="if (confirm('<?php echo gettext('Etes-vous sur ?')?>')) window.location.href='cms_formeAbregeeSubmit.php?Delete=<?php echo $oAbreviation->getID()?>'">
                <?php } else { ?>
                <input type="submit" name="Insert" value="<?php echo gettext('INSERT')?>" class="ajouter">
                <?php } ?>
              </td>
            </tr>
          </tfoot>
          <tbody>
            <tr>
              <th><label for="ABR_ABREVIATION"><?php echo gettext('Abreviation')?></label></th>
              <td><input name="ABR_ABREVIATION" type="text" id="ABR_ABREVIATION" value="<?php echo secureInput($row['ABR_ABREVIATION'])?>" size="10" required></td>
            </tr>
            <tr>
              <th><label for="ABR_LIBELLE"><?php echo gettext('Libelle')?></label></th>
              <td><input name="ABR_LIBELLE" type="text" id="ABR_LIBELLE" value="<?php echo secureInput($row['ABR_LIBELLE'])?>" size="50" required></td>
            </tr>
            <tr>
              <th><label for="ABR_LANGUE"><?php echo gettext('Langue')?></label></th>
              <td><select name="ABR_LANGUE" id="ABR_LANGUE" required>
                  <?php foreach (CMS :: getLangueArray() as $key => $val) { ?>
                  <option value="<?php echo $key?>"<?php if ($key == $row['ABR_LANGUE'] || ($key==CMS::getCurrentSite()->getField('SIT_SHORT_LANGUE') && $row['ABR_LANGUE'] == '')) echo ' selected';?>><?php echo $val?></option>
                  <?php } ?>
                </select></td>
            </tr>
            <tr>
              <th><label for="ABR_TAGNAME"><?php echo gettext('Type')?></label></th>
              <td><select name="ABR_TAGNAME" id="ABR_TAGNAME" required>
                  <option value="">&nbsp;</option>
                  <?php foreach (Abreviation :: getTagnameArray() as $k=>$v) { ?>
                  <option value="<?php echo $k?>"<?php if ($k == $row['ABR_TAGNAME']) echo ' selected'?>>
                  <?php echo $v?></option>
                  <?php } ?>
                </select> </td>
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
