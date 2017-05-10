<?php
require '../../include/inc.bo_init.php';
CMS::checkAccess(new Module('MOD_CORE'), array('PRO_ROOT'));
require CLASS_DIR . 'class.Pagination.php';

$p = new Pagination();
if ($p->onSearch()) {
    $filtre = "1=1";
    if (!empty($_GET['SIT_LIBELLE']))
        $filtre .= " and (SIT_LIBELLE " . $p->makeLike('SIT_LIBELLE') . " or SIT_CODE " . $p->makeLike('SIT_LIBELLE') . ")";
    $p->setFilter($filtre);
} else {
    $p->setOrderBy('SIT_LIBELLE');
}
$sql = "select count(SIT_CODE) from DD_SITE";
$p->setCount($sql);
?>
<!DOCTYPE html>
<html>
<head>
<?php include('../../include/inc.bo_enTete.php') ?>
</head>
<body>
<div id="document">
    <?php $aMenuKey = array('CFG', 'PTF', 'SITE', 'LISTE'); include('../../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">
            <h2><?php echo gettext('Sites')?></h2>
            <form method="get" action="<?php echo PHP_SELF?>" class="filtre">
                <fieldset>
                    <legend><?php echo gettext('MOTEUR_RECHERCHE')?></legend>
                    <table>
                        <tbody>
                            <tr>
                                <th><label for="SIT_LIBELLE"><?php echo gettext('Libelle')?></label></th>
                                <td><input name="SIT_LIBELLE" type="text" id="SIT_LIBELLE" value="<?php echo $p->getParam('SIT_LIBELLE')?>" size="30"></td>
                                <td><?php echo $p->actionRecherche()?></td>
                            </tr>
                        </tbody>
                    </table>
                </fieldset>
            </form>
<?php
echo $p->reglette();
if ($p->getNb() > 0) {?>
            <table class="liste">
                <thead>
                    <tr>
                        <th><?php echo $p->tri('Libellé', 'SIT_LIBELLE')?></th>
                        <th>Langue</th>
                        <th>Gabarit</th>
                        <th>Nom de domaine</th>
                        <th>Email</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $sql = "select * from DD_SITE
                    inner join DD_LANGUE using (LNG_CODE)
                    inner join DD_GABARIT on DD_SITE.GAB_CODE=DD_GABARIT.GAB_CODE";
                foreach ($p->fetch($sql) as $rowListe) { ?>
                   <tr>
                        <td><a href="adm_site.php?idtf=<?php echo $rowListe['SIT_CODE']?>"><?php echo secureInput($rowListe['SIT_LIBELLE'])?></a></td>
                        <td class="aligncenter"><?php echo secureInput($rowListe['LNG_LIBELLE'])?></td>
                        <td class="aligncenter"><?php echo secureInput(extraireLibelle($rowListe['GAB_LIBELLE']))?></td>
                        <td class="aligncenter"><?php echo secureInput($rowListe['SIT_HOST'])?></td>
                        <td class="aligncenter"><?php echo secureInput($rowListe['SIT_EMAIL'])?></td>
                   </tr>
                <?php } ?>
                </tbody>
            </table>
<?php } ?>
        </div>
    </div>
    <?php include('../../include/inc.bo_bandeau_bas.php')?>
</div>
</body>
</html>
