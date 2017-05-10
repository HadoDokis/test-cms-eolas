<?php
require '../include/inc.bo_init.php';
CMS::checkAccess(new Module('MOD_REFERENCEMENT'), array('PRO_REFERENCEMENT'));
require CLASS_DIR . 'class.db_rechercheReferencement.php';
require CLASS_DIR . 'class.Pagination.php';

$p = new Pagination();
$filtre = "SIT_CODE=". $dbh->quote(CMS::getCurrentSite()->getID());
if ($p->onSearch()) {
    if (!empty($_GET['REC_TITLE'])) {
        $filtre .= " and REC_TITLE" . $p->makeLike('REC_TITLE');
    }
    if (!empty($_GET['REC_EXPRESSION'])) {
        $filtre .= " and REC_EXPRESSION" . $p->makeLike('REC_EXPRESSION');
    }
} else {
    $p->setOrderBy('REC_TITLE');
}
$p->setFilter($filtre);
$p->setCount("select count(ID_RECHERCHEREFERENCEMENT) from RECHERCHEREFERENCEMENT");
?>
<!DOCTYPE html>
<html>
<head>
<?php include('../include/inc.bo_enTete.php') ?>
</head>
<body>
<div id="document">
    <?php $aMenuKey = array('CFG', 'SITE', 'MOD_REFERENCEMENT', 'LISTE'); include('../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">
            <h2>Recherches DMK</h2>
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
                                <th><label for="REC_TITLE">Title</label></th>
                                <td><input type="text" name="REC_TITLE" id="REC_TITLE" value="<?php echo $p->getParam('REC_TITLE')?>" size="30"></td>
                                <th><label for="REC_EXPRESSION">Expression</label></th>
                                <td><input type="text" name="REC_EXPRESSION" id="REC_EXPRESSION" value="<?php echo $p->getParam('REC_EXPRESSION')?>" size="30"></td>
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
                        <th><?php echo $p->tri('Title', 'REC_TITLE')?></th>
                        <th>Expression</th>
                    </tr>
                </thead>
                <?php
                $sql = "select * from RECHERCHEREFERENCEMENT";
                foreach ($p->fetch($sql) as $rowListe) {?>
                <tr>
                    <td><a href="cms_rechercheReferencement.php?idtf=<?php echo $rowListe['ID_RECHERCHEREFERENCEMENT']?>"><?php echo secureInput($rowListe['REC_TITLE'])?></a></td>
                    <td><?php echo secureInput($rowListe['REC_EXPRESSION'])?></td>
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
