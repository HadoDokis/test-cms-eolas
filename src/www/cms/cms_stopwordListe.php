<?php
require '../include/inc.bo_init.php';
require CLASS_DIR . 'class.Pagination.php';
CMS::checkAccess(new Module('MOD_RECHERCHE'), array ('PRO_RECHERCHE'));

$p = new Pagination();
$filtre = 'SIT_CODE=' . $dbh->quote(CMS::getCurrentSite()->getID());
if ($p->onSearch()) {
    if (!empty($_GET['STP_LIBELLE'])) {
        $filtre .= " and STP_LIBELLE" . $p->makeLike('STP_LIBELLE');
    }
} else {
    $p->setOrderBy('STP_LIBELLE');
    $p->setMpp(50);
}
$p->setFilter($filtre);
$p->setCount("select count(STP_LIBELLE) from STOPWORD_SITE");
?>
<!DOCTYPE html>
<html>
<head>
<?php include('../include/inc.bo_enTete.php') ?>
</head>
<body>
<div id="document">
    <?php $aMenuKey = array('CFG', 'SITE', 'MOD_RECHERCHE', 'LISTE'); include('../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">
            <h2>Stopwords</h2>
            <form method="get" action="<?php echo PHP_SELF?>" class="filtre">
                <fieldset>
                    <legend><?php echo gettext('MOTEUR_RECHERCHE')?></legend>
                    <table>
                        <tbody>
                            <tr>
                                <th><label for="STP_LIBELLE">Libellé</label></th>
                                <td><input type="text" name="STP_LIBELLE" id="STP_LIBELLE" value="<?php echo $p->getParam('STP_LIBELLE')?>" size="30"></td>
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
                        <th><?php echo $p->tri(gettext('Libelle'), 'STP_LIBELLE')?></th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $sql = "select * from STOPWORD_SITE";
                foreach ($p->fetch($sql) as $rowListe) {?>
                    <tr>
                        <td><a href="cms_stopword.php?idtf=<?php echo $rowListe['STP_LIBELLE']?>"><?php echo secureInput($rowListe['STP_LIBELLE'])?></a></td>
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
