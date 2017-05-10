<?php
require '../include/inc.bo_init.php';
require CLASS_DIR . 'class.Pagination.php';
CMS::checkAccess(new Module('MOD_TRADUCTION'), array('PRO_TRADUCTION'));

$p = new Pagination();
if (!$p->onSearch()) {
    $p->setOrderBy('MOD_LIBELLE');
    $p->setMpp(-1);
}
$p->setFilter('SIT_CODE=' . $dbh->quote(CMS::getCurrentSite()->getID()));
$p->setCount('select count(distinct(DD_TRADUCTION.MOD_CODE)) from DD_TRADUCTION
    inner join SITE_MODULE using(MOD_CODE)');
?>
<!DOCTYPE html>
<html>
<head>
<?php include('../include/inc.bo_enTete.php') ?>
</head>
<body>
<div id="document">
  <?php $aMenuKey = array('CFG', 'SITE', 'MOD_TRADUCTION', 'LISTE'); include('../include/inc.bo_bandeau_haut.php') ?>
  <div id="corps">
    <div id="bo_contenu">
      <h2>Traductions</h2>
      <br>
      <?php echo $p->reglette()?>
      <table class="liste">
        <thead>
          <tr>
            <th> <?php echo $p->tri(gettext('Module'), 'MOD_LIBELLE')?> </th>
            <th> <?php echo gettext('Traductions')?> </th>
          </tr>
        </thead>
        <?php
        $sql = "select distinct(DD_MODULE.MOD_CODE), MOD_LIBELLE from DD_TRADUCTION
            inner join DD_MODULE using(MOD_CODE)
            inner join SITE_MODULE using(MOD_CODE)";
        foreach ($p->fetch($sql) as $rowListe) {?>
            <tr>
              <td><a href="cms_traduction.php?idtf=<?php echo $rowListe['MOD_CODE']?>"><?php echo secureInput(extraireLibelle($rowListe['MOD_LIBELLE']))?></a></td>
              <td class="aligncenter">
                    <?php
                    //On compte le nombre de codes de traduction liés au module en cours
                    $sql = "select count(TRA_CODE) from DD_TRADUCTION where MOD_CODE=". $dbh->quote($rowListe['MOD_CODE']);
                    $iTotal = $dbh->query($sql)->fetchColumn();
                    //On compte le nombre de libellés de traduction associés aux codes de traduction
                    $sql = "select count(TRADUCTION_SITE.TRA_CODE) from TRADUCTION_SITE
                        inner join DD_TRADUCTION using(TRA_CODE)
                        where DD_TRADUCTION.MOD_CODE=" . $dbh->quote($rowListe['MOD_CODE']) . " and SIT_CODE=". $dbh->quote(CMS::getCurrentSite()->getID());
                    $iTraduits = $dbh->query($sql)->fetchColumn();
                    echo $iTraduits . ' / ' . $iTotal;?>
              </td>
            </tr>
        <?php } ?>
      </table>
    </div>
  </div>
  <?php include('../include/inc.bo_bandeau_bas.php')?>
</div>
</body>
</html>
