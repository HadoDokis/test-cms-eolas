<?php
require '../../include/inc.bo_init.php';
CMS::checkAccess(new Module('MOD_CORE'), array('PRO_ROOT'));

if (empty($_GET['LNG_CODE'])) {
    $_GET['LNG_CODE'] = CMS::getCurrentSite()->getField('SIT_LANGUE');
}

$availableModules = array_map(array($dbh, 'quote'), array_keys(CMS::getCurrentSite()->getModules()));
$sql = "select * from DD_TRADUCTION
    inner join DD_MODULE using (MOD_CODE)
    where MOD_CODE in (".implode(',', $availableModules).") and MOD_CODE=" . $dbh->quote($_GET['idtf']) . " order by TRA_DESCRIPTION, TRA_CODE";
$aTraduction = $dbh->query($sql)->fetchAll(PDO :: FETCH_ASSOC);
if (count($aTraduction) == 0) {
    header('location:' . SERVER_ROOT . 'cms/administration/adm_traductionListe.php');
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <?php include('../../include/inc.bo_enTete.php') ?>
</head>
<body>
<div id="document">
  <?php $aMenuKey = array('CFG', 'PTF', 'TRADUCTION'); include('../../include/inc.bo_bandeau_haut.php') ?>
  <div id="corps">
    <div id="bo_contenu">
      <h2><?php echo secureInput(extraireLibelle($aTraduction[0]['MOD_LIBELLE']));?></h2>
      <form method="post" action="adm_traductionSubmit.php" class="creation">
        <fieldset>
        <legend>Libellés</legend>
        <table>
          <tfoot>
            <tr>
              <td colspan="2">
                <input type="hidden" name="MOD_CODE" value="<?php echo secureInput($_GET['idtf'])?>">
                <input type="hidden" name="LNG_CODE" value="<?php echo secureInput($_GET['LNG_CODE'])?>">
                <input type="submit" name="Update" value="<?php echo gettext('UPDATE')?>" class="modifier">
              </td>
            </tr>
          </tfoot>
          <tbody>
            <?php foreach ($aTraduction as $rowListe) {?>
                <tr>
                  <th><label for="<?php echo $rowListe['TRA_CODE']?>" title="<?php echo $rowListe['TRA_CODE']?>"><?php echo ($rowListe['TRA_DESCRIPTION'] != '') ? secureInput($rowListe['TRA_DESCRIPTION']) : '<em>'.$rowListe['TRA_CODE'].'</em>'?></label></th>
                  <td>
                    <?php
                    $sql = "select * from TRADUCTION_LANGUE
                        where TRA_CODE='". $rowListe['TRA_CODE']."' and LNG_CODE=". $dbh->quote($_GET['LNG_CODE']);
                    $rowTemp = $dbh->query($sql)->fetch(PDO :: FETCH_ASSOC);
                    if ($rowListe['TRA_MULTILIGNE']) {?>
                    <textarea name="<?php echo $rowListe['TRA_CODE']?>" id="<?php echo $rowListe['TRA_CODE']?>" cols="50" rows="5"><?php echo secureInput($rowTemp['TRA_LIBELLE'])?></textarea>
                    <?php } else { ?>
                    <input name="<?php echo $rowListe['TRA_CODE']?>" type="text" id="<?php echo $rowListe['TRA_CODE']?>" value="<?php echo secureInput($rowTemp['TRA_LIBELLE'])?>" size="80" maxlength="255">
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
  <?php include('../../include/inc.bo_bandeau_bas.php')?>
</div>
</body>
</html>
