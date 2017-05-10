<?php
require '../include/inc.bo_init.php';
CMS::checkAccess(new Module('MOD_LANGUISME'), array('PRO_LANGUISME'));
require CLASS_DIR . 'class.Pagination.php';

$p = new Pagination();
$filtre = "SIT_CODE=". $dbh->quote(CMS::getCurrentSite()->getID());
if ($p->onSearch()) {
    if (!empty($_GET['LNG_LIBELLE'])) {
        $filtre .= " and LNG_LIBELLE" . $p->makeLike('LNG_LIBELLE');
    }
} else {
    $p->setOrderBy('LNG_LIBELLE');
}
$p->setFilter($filtre);
$p->setCount("select count(ID_LANGUISME) from LANGUISME");
?>
<!DOCTYPE html>
<html>
<head>
<?php include('../include/inc.bo_enTete.php') ?>
</head>
<body>
<div id="document">
    <?php $aMenuKey = array('CFG', 'SITE', 'MOD_LANGUISME', 'LISTE'); include('../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">
            <h2>Languismes</h2>
            <form method="get" action="<?php echo PHP_SELF?>" class="filtre">
                <fieldset>
                    <legend><?php echo gettext('MOTEUR_RECHERCHE')?></legend>
                    <table>
                        <tbody>
                            <tr>
                                <th><label for="LNG_LIBELLE"><?php echo gettext('Libelle')?></label></th>
                                <td><input type="text" name="LNG_LIBELLE" id="LNG_LIBELLE" value="<?php echo $p->getParam('LNG_LIBELLE')?>" size="30"></td>
                                <td><?php echo $p->actionRecherche()?></td>
                            </tr>
                        </tbody>
                    </table>
                </fieldset>
            </form>
            <?php
echo $p->reglette();
if ($p->getNb() > 0) { ?>
            <table class="liste">
                <thead>
                    <tr>
                        <th><?php echo $p->tri(gettext('Libelle'), 'LNG_LIBELLE')?></th>
                        <th><?php echo $p->tri(gettext('Langue'), 'LNG_LANGUE')?></th>
                    </tr>
                </thead>
                <?php
                $sql = "select * from LANGUISME";
                foreach ($p->fetch($sql) as $rowListe) {?>
                <tr>
                    <td class="aligncenter"><a href="cms_languisme.php?idtf=<?php echo $rowListe['ID_LANGUISME']?>"><?php echo secureInput($rowListe['LNG_LIBELLE'])?></a></td>
                    <td class="aligncenter"><?php echo secureInput($rowListe['LNG_LANGUE'])?></td>
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
