<?php
require '../../include/inc.bo_init.php';
CMS::checkAccess(new Module('MOD_CORE'), array('PRO_ROOT'));

$sql = "select SIT_CODE, MOD_CODE from SITE_MODULE";
$aSiteModule = $dbh->query($sql)->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_COLUMN);

$aModule = array();
$sql = "select * from DD_MODULE order by MOD_GROUPE, MOD_LIBELLE";
foreach ($dbh->query($sql, PDO::FETCH_ASSOC) as $rowTemp) {
    $aModule[$rowTemp['MOD_GROUPE']][$rowTemp['MOD_CODE']] = $rowTemp['MOD_LIBELLE'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <?php include('../../include/inc.bo_enTete.php') ?>
</head>
<body>
<div id="document">
    <?php $aMenuKey = array('CFG', 'PTF', 'SITE', 'MODULE'); include('../../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu" style="overflow:scroll">
            <h2>Modules par site</h2>
            <table class="liste">
                <thead>
                    <tr>
                        <th rowspan="2">Site</th>
                        <?php foreach ($aModule as $key=>$tab) { ?>
                        <th colspan="<?php echo count($tab)?>"><?php echo secureInput(extraireLibelle($key))?></th>
                        <?php } ?>
                    </tr>
                    <tr style="font-size:85%">
                        <?php foreach ($aModule as $key=>$tab) { ?>
                            <?php foreach ($tab as $key=>$val) { ?>
                        <th title="<?php echo secureInput(extraireLibelle($val))?>"><?php echo resume(secureInput(extraireLibelle($val)), 3, '', '.')?></th>
                            <?php } ?>
                        <?php } ?>
                    </tr>
                </thead>
                <tbody>
                <?php
                $sql = "select * from DD_SITE order by SIT_LIBELLE";
                foreach ($dbh->query($sql, PDO::FETCH_ASSOC) as $rowListe) { ?>
                   <tr>
                        <td><a href="adm_site.php?idtf=<?php echo $rowListe['SIT_CODE']?>"><?php echo secureInput($rowListe['SIT_LIBELLE'])?></a></td>
                        <?php foreach ($aModule as $key=>$tab) { ?>
                            <?php foreach ($tab as $key=>$val) { ?>
                        <td class="aligncenter" title="<?php echo secureInput($rowListe['SIT_LIBELLE'])?> : <?php echo secureInput(extraireLibelle($val))?>"><?php if (in_array($key, $aSiteModule[$rowListe['SIT_CODE']])) echo 'X'?></td>
                            <?php } ?>
                        <?php } ?>
                   </tr>
               <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php include('../../include/inc.bo_bandeau_bas.php')?>
</div>
</body>
</html>
