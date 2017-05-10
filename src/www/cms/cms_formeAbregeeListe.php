<?php
require '../include/inc.bo_init.php';
CMS::checkAccess(new Module('MOD_ABREVIATION'), array('PRO_ABREVIATION'));
require (CLASS_DIR . 'class.db_abreviation.php');
require (CLASS_DIR . 'class.Pagination.php');

$p = new Pagination();
$filtre = "SIT_CODE=". $dbh->quote(CMS::getCurrentSite()->getID());
if ($p->onSearch()) {
    if (!empty($_GET['ABR_ABREVIATION'])) {
        $filtre .= " and ABR_ABREVIATION" . $p->makeLike('ABR_ABREVIATION');
    }
    if (!empty($_GET['ABR_LIBELLE'])) {
        $filtre .= " and ABR_LIBELLE" . $p->makeLike('ABR_LIBELLE');
    }
} else {
    $p->setOrderBy('ABR_ABREVIATION');
}
$p->setFilter($filtre);
$p->setCount("select count(ID_ABREVIATION) from ABREVIATION");
?>
<!DOCTYPE html>
<html>
<head>
<?php include('../include/inc.bo_enTete.php') ?>
</head>
<body>
<div id="document">
    <?php $aMenuKey = array('CFG', 'SITE', 'MOD_ABREVIATION', 'LISTE'); include('../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">
            <h2><?php echo gettext('Formes abregees')?></h2>
            <form method="get" action="<?php echo PHP_SELF?>" class="filtre">
                <fieldset>
                    <legend><?php echo gettext('MOTEUR_RECHERCHE')?></legend>
                    <table>
                        <tfoot>
                            <tr>
                                <td colspan="4"><?php echo $p->actionRecherche()?></td>
                            </tr>
                        </tfoot>
                        <tbody>
                            <tr>
                                <th><label for="ABR_ABREVIATION"><?php echo gettext('Abreviation')?></label></th>
                                <td><input type="text" name="ABR_ABREVIATION" id="ABR_ABREVIATION" value="<?php echo $p->getParam('ABR_ABREVIATION')?>" size="30"></td>
                                <th><label for="ABR_LIBELLE"><?php echo gettext('Libelle')?></label></th>
                                <td><input type="text" name="ABR_LIBELLE" id="ABR_LIBELLE" value="<?php echo $p->getParam('ABR_LIBELLE')?>" size="30"></td>
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
                        <th><?php echo $p->tri(gettext('Abreviation'), 'ABR_ABREVIATION')?></th>
                        <th><?php echo $p->tri(gettext('Libelle'), 'ABR_LIBELLE')?></th>
                        <th><?php echo $p->tri(gettext('Langue'), 'ABR_LANGUE')?></th>
                        <th><?php echo $p->tri(gettext('Type'), 'ABR_TAGNAME')?></th>
                    </tr>
                </thead>
                <?php
$sql = "select * from ABREVIATION";
foreach ($p->fetch($sql) as $rowListe) { ?>
                <tr>
                    <td class="aligncenter"><a href="cms_formeAbregee.php?idtf=<?php echo $rowListe['ID_ABREVIATION']?>"><?php echo secureInput($rowListe['ABR_ABREVIATION'])?></a></td>
                    <td><?php echo secureInput($rowListe['ABR_LIBELLE'])?></td>
                    <td class="aligncenter"><?php echo secureInput($rowListe['ABR_LANGUE'])?></td>
                    <td class="aligncenter"><?php echo Abreviation :: getTagnameArray($rowListe['ABR_TAGNAME'])?></td>
                </tr>
                <?php } ?>
            </table>
            <?php } ?>
        </div>
    </div>
    <?php include('../include/inc.bo_bandeau_bas.php')?>
</div>
</body>
</html>
