<?php
require '../include/inc.bo_init.php';
CMS::checkAccess(new Module('MOD_THEMATIQUE'), array ('PRO_THEMATIQUE'));

require CLASS_DIR . 'class.Pagination.php';
require CLASS_DIR . 'class.db_thematique.php';

$p = new Pagination();
$filtre = " SIT_CODE=".$dbh->quote(CMS::getCurrentSite()->getID());
if ($p->onSearch()) {
    if (!empty($_GET['THE_LIBELLE']))
        $filtre .= " and THE_LIBELLE ". $p->makeLike('THE_LIBELLE');
} else {
    $p->setOrderBy('THE_LIBELLE');
}

$sql = "select count(ID_THEMATIQUE) from THEMATIQUE";

$p->setFilter($filtre);
$p->setCount($sql);
?>
<!DOCTYPE html>
<html>
<head>
<?php include('../include/inc.bo_enTete.php') ?>
</head>
<body>
<div id="document">
    <?php $aMenuKey = array('CFG', 'SITE', 'MOD_THEMATIQUE', 'LISTE'); include('../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">
            <h2><?php echo gettext('Thematiques')?></h2>
            <form method="get" action="<?php echo PHP_SELF?>" class="filtre">
                <fieldset>
                    <legend><?php echo gettext('MOTEUR_RECHERCHE')?></legend>
                    <table>
                        <tbody>
                            <tr>
                                <th><label for="THE_LIBELLE"><?php echo gettext('Libelle')?></label></th>
                                <td><input name="THE_LIBELLE" type="text" id="THE_LIBELLE" value="<?php echo $p->getParam('THE_LIBELLE')?>" size="20" maxlength="255"></td>
                                <td><?php echo $p->actionRecherche()?></td>
                            </tr>
                        </tbody>
                    </table>
                </fieldset>
            </form>
<?php
echo $p->reglette();
if ($p->getNb() > 0) {
?>
            <table class="liste">
                <thead>
                    <tr>
                        <th><?php echo $p->tri(gettext('Libelle'), 'THE_LIBELLE')?></th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $sql = "select * from THEMATIQUE";
                foreach ($p->fetch($sql) as $rowListe) {?>
                    <tr>
                        <td class="aligncenter"><a href="cms_thematique.php?idtf=<?php echo $rowListe['ID_THEMATIQUE']?>"><?php echo secureInput($rowListe['THE_LIBELLE'])?></a></td>
                   </tr>
                <?php } ?>
                </tbody>
            </table>
<?php } ?>
        </div>
    </div>
    <?php include('../include/inc.bo_bandeau_bas.php')?>
</div>
</body>
</html>
