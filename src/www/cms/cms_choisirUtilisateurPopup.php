<?php
require '../include/inc.bo_init.php';

Utilisateur::checkConnected();
require (CLASS_DIR . 'class.Pagination.php');

if (isset($_GET['IDENTIFIANT'])) {
    $_SESSION['temp']['IDENTIFIANT'] = $_GET['IDENTIFIANT']; //obligatoire
}
if (isset($_GET['TEXTE'])) {
    $_SESSION['temp']['TEXTE'] = $_GET['TEXTE']; //optionnel
}

$p = new Pagination();
if ($p->onSearch()) {
    $filtre = "1=1";
    if (!empty($_GET['UTI_NOM']))
        $filtre .= " and (UTI_NOM" . $p->makeLike('UTI_NOM') . " or UTI_PRENOM" . $p->makeLike('UTI_NOM') . ")";
    if (!empty($_GET['UTI_EMAIL']))
        $filtre .= " and UTI_EMAIL" . $p->makeLike('UTI_EMAIL');
} else {
    $filtre = "1=0";
    $p->setOrderBy('UTI_NOM');
}
$p->setFilter($filtre);
$p->setCount("select count(distinct(ID_UTILISATEUR)) from UTILISATEUR");
?>
<!DOCTYPE html>
<html>
<head>
<?php include('../include/inc.bo_enTete.php')?>
<script>
function maj(id, libelle)
{
    <?php if ($_GET['IDENTIFIANT'] != '') { ?>
        window.opener.document.getElementById('<?php echo $_SESSION['temp']['IDENTIFIANT']?>').value = id;
        if (window.opener.document.getElementById('<?php echo $_SESSION['temp']['TEXTE']?>')) {
            window.opener.document.getElementById('<?php echo $_SESSION['temp']['TEXTE']?>').value = libelle;
        }
    <?php }?>
    <?php if ($_GET['AJAX'] != '') {?>
        window.opener.ajaxLiaison.getAjax('<?php echo $_GET['AJAX'] ?>','utilisateur','insert',id);
    <?php } ?>
<?php if ($_GET['NOCLOSE'] == '') {?>
    window.close();
<?php } ?>
}
</script>
</head>
<body id="popup">
<?php include('../include/inc.bo_bandeau_hautPopup.php')?>
<div id="bo_contenuPopup">
  <h2><?php echo gettext('Choisir un utilisateur')?></h2>
  <form method="get" action="<?php echo PHP_SELF?>" class="filtre">
    <fieldset>
    <legend><?php echo gettext('MOTEUR_RECHERCHE')?></legend>
    <table>
      <tfoot>
        <tr>
          <td colspan="2">
          <input type="hidden" name="AJAX" value="<?php echo $_GET['AJAX'];?>">
          <?php echo $p->actionRecherche()?></td>
        </tr>
      </tfoot>
      <tbody>
        <tr>
          <th><label for="UTI_NOM"> <?php echo gettext('Nom')?> / <?php echo gettext('Prenom')?></label></th>
          <td><input type="text" name="UTI_NOM" id="UTI_NOM" value="<?php echo $p->getParam('UTI_NOM')?>" size="30"></td>
        </tr>
        <tr>
          <th><label for="UTI_EMAIL"> <?php echo gettext('Email')?></label></th>
          <td><input type="text" name="UTI_EMAIL" id="UTI_EMAIL" value="<?php echo $p->getParam('UTI_EMAIL')?>" size="30"></td>
        </tr>
      </tbody>
    </table>
    </fieldset>
  </form>
  <?php
if ($p->onSearch() || $p->getNb() > 0) {
    echo $p->reglette();
    if ($p->getNb() > 0) {
?>
  <table class="liste">
    <thead>
      <tr>
        <th><?php echo $p->tri(gettext('Utilisateur'), 'UTI_NOM')?></th>
        <th><?php echo gettext('Email')?> </th>
        <th><?php echo gettext('Telephone')?></th>
      </tr>
    </thead>
    <?php
$sql = "select * from UTILISATEUR";
foreach ($p->fetch($sql) as $rowListe) {
    ?>
    <tr>
      <td><a href="javascript:maj(<?php echo $rowListe['ID_UTILISATEUR']?>, '<?php echo escapeJS($rowListe['UTI_NOM'] . ' ' . $rowListe['UTI_PRENOM'])?>')"><?php echo secureInput($rowListe['UTI_NOM'] . ' ' . $rowListe['UTI_PRENOM'])?></a></td>
      <td><?php echo secureInput($rowListe['UTI_EMAIL'])?></td>
      <td><?php echo secureInput($rowListe['UTI_TELEPHONE'])?></td>
    </tr>
    <?php } ?>
  </table>
  <?php } } ?>
</div>
<?php include('../include/inc.bo_bandeau_basPopup.php')?>
</body>
</html>
