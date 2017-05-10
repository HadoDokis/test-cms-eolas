<?php
require '../include/inc.bo_init.php';

CMS::checkAccess(new Module('MOD_FORMULAIRE'), array ('PRO_FORMGEST'));

require CLASS_DIR . 'class.db_formulaire.php';

$sql = "select * from FORMULAIREGROUPE where ID_FORMULAIREGROUPE=" . intval($_GET['idtf']);
if (!$row = $dbh->query($sql)->fetch(PDO :: FETCH_ASSOC)) {
    $_GET['idtf'] = -1;
    $row['ID_FORMULAIRE'] = $_GET['ID_FORMULAIRE'];
    $nbID_FORMULAIREQUESTION = 0;
} else {
    //On calcul si le groupe peut être supprimé
    $sql = "select count(ID_FORMULAIREQUESTION) from FORMULAIREQUESTION where ID_FORMULAIREGROUPE = " . intval($_GET['idtf']);
    $nbID_FORMULAIREQUESTION = $dbh->query($sql)->fetchColumn();
}
?>
<!DOCTYPE html>
<html>
<head>
    <?php include('../include/inc.bo_enTete.php')?>
</head>
<body id="popup">
<?php include('../include/inc.bo_bandeau_hautPopup.php') ?>
<div id="bo_contenuPopup">
  <h2><?php echo gettext('Groupe')?></h2>
  <form method="post" action="frm_formulaireGroupePopupSubmit.php" class="creation">
    <table>
      <tfoot>
        <tr>
          <td colspan="2"><input type="hidden" name="ID_FORMULAIRE" value="<?php echo $row['ID_FORMULAIRE']?>">
            <input type="hidden" name="FMG_POIDS" value="<?php echo secureInput($_GET['FMG_POIDS'])?>">
            <?php if ($_GET['idtf'] != -1) { ?>
            <input type="hidden" name="idtf" value="<?php echo secureInput($_GET['idtf'])?>">
            <input type="submit" name="Update" value="<?php echo gettext('UPDATE')?>" class="modifier">
            <input type="hidden" name="Update" value="1">
            <input type="button" name="Delete" value="<?php echo gettext('DELETE')?>" class="supprimer"<?php if ($nbID_FORMULAIREQUESTION != 0) echo ' disabled' ?> onclick="if (confirm('<?php echo gettext('Etes-vous sur ?')?>')) window.location.href='frm_formulaireGroupePopupSubmit.php?Delete=<?php echo $_GET['idtf'] ?>&amp;ID_FORMULAIRE=<?php echo $row['ID_FORMULAIRE']?>'">
            <?php } else { ?>
            <input type="submit" name="Insert" value="<?php echo gettext('INSERT')?>" class="ajouter">
            <input type="hidden" name="Insert" value="1">
            <?php } ?>
          </td>
      </tfoot>
      <tbody>
        <tr>
          <th><label for="FMG_LIBELLE"><?php echo gettext('Libelle')?></label></th>
          <td><input name="FMG_LIBELLE" type="text" id="FMG_LIBELLE" value="<?php echo secureInput($row['FMG_LIBELLE'])?>" size="50" required></td>
        </tr>
        <tr>
          <th><label>
            <?php echo gettext('Visible') ?>
            </label></th>
          <td><input type="radio" name="FMG_VISIBLE" id="FMG_VISIBLE_1" value="1" <?php if ($row['FMG_VISIBLE'] == '1' || $row['FMG_VISIBLE'] == '' ) echo 'checked'?>>
            <label for="FMG_VISIBLE_1">
            <?php echo gettext('Oui')?>
            </label> <input type="radio" name="FMG_VISIBLE" id="FMG_VISIBLE_0" value="0" <?php if ($row['FMG_VISIBLE'] == '0') echo 'checked'?>>
            <label for="FMG_VISIBLE_0">
            <?php echo gettext('Non')?>
            </label> </td>
        </tr>
        <tr>
          <th><label>
            <?php echo gettext('Libelle visible') ?>
            </label></th>
          <td><input type="radio" name="FMG_LIBELLEVISIBLE" id="FMG_LIBELLEVISIBLE_1" value="1" <?php if ($row['FMG_LIBELLEVISIBLE'] == '1' || $row['FMG_LIBELLEVISIBLE'] == '' ) echo 'checked'?>>
            <label for="FMG_LIBELLEVISIBLE_1">
            <?php echo gettext('Oui')?>
            </label> <input type="radio" name="FMG_LIBELLEVISIBLE" id="FMG_LIBELLEVISIBLE_0" value="0" <?php if ($row['FMG_LIBELLEVISIBLE'] == '0') echo 'checked'?>>
            <label for="FMG_LIBELLEVISIBLE_0">
            <?php echo gettext('Non')?>
            </label> </td>
        </tr>
      </tbody>
    </table>
  </form>
</div>
<?php include('../include/inc.bo_bandeau_basPopup.php')?>
</body>
</html>
