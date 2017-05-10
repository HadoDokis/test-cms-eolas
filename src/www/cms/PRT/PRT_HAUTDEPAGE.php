<?php
require '../../include/inc.bo_init.php';
Utilisateur::checkConnected();
require CLASS_DIR . 'class.db_page.php';
require CLASS_DIR . 'class.db_paragraphe.php';

$oParagraphe = new Paragraphe_HAUTDEPAGE($_GET['idtf']);
if ($oParagraphe->exist()) {
    $row = $oParagraphe->getFields();
    $oPage = $oParagraphe->getPage();
} else {
    $oParagraphe_prec = new Paragraphe($_GET['idtf_prec']);
    if ($oParagraphe_prec->exist()) {
        $oPage = $oParagraphe_prec->getPage();
        $PAR_POIDS = $oParagraphe_prec->getField('PAR_POIDS') + 1;
    } else {
        $oPage = new Page($_GET['ID_PAGE']);
        $PAR_POIDS = 1;
    }
}
$oPage->checkAuthorized();
$oPage->lock();
?>
<!DOCTYPE html>
<html>
<head>
<?php include('../../include/inc.bo_enTete.php') ?>
</head>
<body>
<div id="document">
  <?php include('../../include/inc.bo_bandeau_haut.php') ?>
  <div id="corps">
    <div id="bo_contenu">
      <form method="post" action="PRT_Submit.php" class="creation">
        <fieldset>
        <legend><?php echo gettext('Haut de page')?></legend>
        <table>
          <tfoot>
            <tr>
              <td colspan="2">
                <?php if ($oParagraphe->exist()) { ?>
                <input type="hidden" name="idtf" value="<?php echo $oParagraphe->getID()?>">
                <input type="submit" name="Update" value="<?php echo gettext('UPDATE')?>" class="modifier">
                <input type="hidden" name="Update" value="Update">
                <?php } else { ?>
                <input type="hidden" name="ID_PAGE" value="<?php echo $oPage->getID()?>">
                <input type="hidden" name="PAR_POIDS" value="<?php echo $PAR_POIDS?>">
                <input type="submit" name="Insert" value="<?php echo gettext('INSERT')?>" class="ajouter">
                <input type="hidden" name="Insert" value="Insert">
                <?php } ?>
                <input type="hidden" name="PRT_CODE" value="PRT_HAUTDEPAGE">
                <input type="button" name="retour" onclick="window.location.href='../cms_pseudo.php?idtf=<?php echo $oPage->getID()?>&amp;PFM=1'" value="<?php echo gettext('Retour')?>" class="retour">
              </td>
            </tr>
          </tfoot>
          <tbody>
            <tr>
              <th><label for="PAR_CONTENU"><?php echo gettext('Libelle')?></label></th>
              <td><input name="PAR_CONTENU" type="text" id="PAR_CONTENU" value="<?php echo secureInput(($row['PAR_CONTENU'] == '') ? gettext('Haut de page') : $row['PAR_CONTENU'])?>" size="50" maxlength="50" required>
                <script>document.getElementById('PAR_CONTENU').focus()</script>
              </td>
            </tr>
            <tr>
              <th><label><?php echo gettext('Heritable')?></label></th>
              <td><input type="radio" name="PAR_HERITABLE" id="PAR_HERITABLE_2" value="2"<?php if ($row['PAR_HERITABLE']==2) echo ' checked'?>>
                <label for="PAR_HERITABLE_2"><?php echo gettext('Oui')?> <?php echo gettext('avec_styles')?></label>
                <input type="radio" name="PAR_HERITABLE" id="PAR_HERITABLE_1" value="1"<?php if ($row['PAR_HERITABLE']==1) echo ' checked'?>>
                <label for="PAR_HERITABLE_1"><?php echo gettext('Oui')?> <?php echo gettext('sans_styles')?></label>
                <input type="radio" name="PAR_HERITABLE" id="PAR_HERITABLE_0" value="0"<?php if (!$row['PAR_HERITABLE'] ||$row['PAR_HERITABLE']==0) echo ' checked'?>>
                <label for="PAR_HERITABLE_0"><?php echo gettext('Non')?></label></td>
            </tr>
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
