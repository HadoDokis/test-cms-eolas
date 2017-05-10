<?php
require '../include/inc.bo_init.php';
CMS::checkAccess(new Module('MOD_TRADUCTION'), array('PRO_TRADUCTION'));

$sql = "select * from DD_MODULE inner join DD_TRADUCTION using(MOD_CODE) where MOD_CODE=" . $dbh->quote($_GET['idtf']);
if (!$row = $dbh->query($sql)->fetch(PDO :: FETCH_ASSOC)) {
    header('location:' . SERVER_ROOT . 'cms/cms_traductionListe.php');
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
<?php include('../include/inc.bo_enTete.php') ?>
</head>
<body>
<div id="document">
  <?php $aMenuKey = array('CFG', 'SITE', 'MOD_TRADUCTION'); include('../include/inc.bo_bandeau_haut.php') ?>
  <div id="corps">
    <div id="bo_contenu">
      <h2><?php echo (($row['MOD_LIBELLE'] != '') ? secureInput(extraireLibelle($row['MOD_LIBELLE'])) : gettext('Termes generiques'));?></h2>
      <form method="post" action="cms_traductionSubmit.php" class="creation">
        <fieldset>
        <legend>Textes</legend>
        <table>
          <tfoot>
            <tr>
              <td colspan="2"><input type="hidden" name="MOD_CODE" value="<?php echo secureInput($row['MOD_CODE'])?>">
                <input type="submit" name="Update" value="<?php echo gettext('UPDATE')?>" class="modifier"></td>
            </tr>
          </tfoot>
          <tbody>
            <?php
            $sql = "select * from DD_TRADUCTION where MOD_CODE=" . $dbh->quote($_GET['idtf']) . " order by TRA_DESCRIPTION, TRA_CODE";
            foreach ($dbh->query($sql)->fetchAll(PDO :: FETCH_ASSOC) as $rowListe) {?>
                <tr>
                  <th><label for="<?php echo secureInput($rowListe['TRA_CODE'])?>" title="<?php echo secureInput($rowListe['TRA_CODE'])?>"><?php echo ($rowListe['TRA_DESCRIPTION'] != '') ? secureInput($rowListe['TRA_DESCRIPTION']) : '<em>'.secureInput($rowListe['TRA_CODE']).'</em>'?></label></th>
                  <td>
                    <?php
                    $sql = "select * from TRADUCTION_SITE
                        where TRA_CODE='". $rowListe['TRA_CODE']."' and SIT_CODE=". $dbh->quote(CMS::getCurrentSite()->getID());
                    $rowTemp = $dbh->query($sql)->fetch(PDO :: FETCH_ASSOC);
                    if ($rowListe['TRA_MULTILIGNE']) {?>
                    <textarea name="<?php echo secureInput($rowListe['TRA_CODE'])?>" id="<?php echo secureInput($rowListe['TRA_CODE'])?>" cols="50" rows="5"><?php echo secureInput($rowTemp['TRA_LIBELLE'])?></textarea>
                    <?php } else { ?>
                    <input name="<?php echo secureInput($rowListe['TRA_CODE'])?>" type="text" id="<?php echo secureInput($rowListe['TRA_CODE'])?>" value="<?php echo secureInput($rowTemp['TRA_LIBELLE'])?>" size="80" maxlength="255">
                    <?php } ?>
                  </td>
                </tr>
            <?php } ?>
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
