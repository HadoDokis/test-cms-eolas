<?php
require '../../include/inc.bo_init.php';
require CLASS_DIR . 'class.Pagination.php';
CMS::checkAccess(new Module('MOD_CORE'), array('PRO_ROOT'));

$availableModules = array_map(array($dbh, 'quote'), array_keys(CMS::getCurrentSite()->getModules()));
$filtre = 'MOD_CODE in ('.implode(',', $availableModules).')';

if (empty($_GET['LNG_CODE'])) {
    $_GET['LNG_CODE'] = CMS::getCurrentSite()->getField('SIT_LANGUE');
}

$p = new Pagination();
$p->setFilter($filtre);
if (!$p->onSearch()) {
    $p->setOrderBy('MOD_LIBELLE');
    $p->setMpp(-1);
}

$p->setCount('select count(distinct(DD_MODULE.MOD_CODE)) from DD_MODULE inner join DD_TRADUCTION using(MOD_CODE)');
?>
<!DOCTYPE html>
<html>
<head>
<?php include('../../include/inc.bo_enTete.php') ?>
</head>
<body>
<div id="document">
    <?php $aMenuKey = array('CFG', 'PTF', 'TRADUCTION', 'LISTE_' . $_GET['LNG_CODE']); include('../../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">
            <h2>Libellés</h2>
            <br>
            <?php echo $p->reglette()?>
            <table class="liste">
                <thead>
                    <tr>
                        <th><?php echo $p->tri(gettext('Module'), 'MOD_LIBELLE')?></th>
                        <th><?php echo gettext('Traductions')?></th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $sql = "select distinct(DD_MODULE.MOD_CODE), MOD_LIBELLE from DD_MODULE inner join DD_TRADUCTION using(MOD_CODE)";
                foreach ($p->fetch($sql) as $rowListe) { ?>
                    <tr>
                        <td><a href="adm_traduction.php?idtf=<?php echo $rowListe['MOD_CODE']?>&amp;LNG_CODE=<?php echo secureInput($_GET['LNG_CODE'])?>"><?php echo secureInput(extraireLibelle($rowListe['MOD_LIBELLE']))?></a></td>
                        <td class="aligncenter">
                            <?php
                            $sql = "select count(TRA_CODE) from DD_TRADUCTION where MOD_CODE=". $dbh->quote($rowListe['MOD_CODE']);
                            $iTotal = $dbh->query($sql)->fetchColumn();
                            $sql = "select count(TRADUCTION_LANGUE.TRA_CODE) from TRADUCTION_LANGUE
                                inner join DD_TRADUCTION using(TRA_CODE)
                                where DD_TRADUCTION.MOD_CODE=" . $dbh->quote($rowListe['MOD_CODE']) . " and LNG_CODE=". $dbh->quote($_GET['LNG_CODE']);
                            $iTraduits = $dbh->query($sql)->fetchColumn();
                            echo $iTraduits . ' / ' . $iTotal;?>
                        </td>
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
