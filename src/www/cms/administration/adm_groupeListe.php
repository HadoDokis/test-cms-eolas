<?php
require '../../include/inc.bo_init.php';
CMS::checkAccess(new Module('MOD_EXTRANET'), array('PRO_ROOT_SITE'));
require CLASS_DIR . 'class.db_groupe.php';
require CLASS_DIR . 'class.Pagination.php';

$p = new Pagination();
$filtre = "GROUPE.SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID());
if ($p->onSearch()) {
    if (!empty($_GET['GRP_LIBELLE'])) {
        $filtre .= " and GRP_LIBELLE" . $p->makeLike('GRP_LIBELLE');
    }
} else {
    $p->setOrderBy('GRP_LIBELLE');
}
$p->setFilter($filtre);
$p->setCount("select count(ID_GROUPE) from GROUPE");
?>
<!DOCTYPE html>
<html>
<head>
<?php include('../../include/inc.bo_enTete.php') ?>
</head>
<body>
<div id="document">
    <?php $aMenuKey = array('CFG', 'USER', 'GROUPE', 'LISTE'); include('../../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">
            <h2><?php echo gettext('Groupes')?></h2>
            <form method="get" action="<?php echo PHP_SELF?>" class="filtre">
                <fieldset>
                    <legend><?php echo gettext('MOTEUR_RECHERCHE')?></legend>
                    <table>
                        <tfoot>
                            <tr>
                                <td colspan="2"><?php echo $p->actionRecherche()?></td>
                            </tr>
                        </tfoot>
                        <tbody>
                            <tr>
                                <th><label for="GRP_LIBELLE"><?php echo gettext('Libelle')?></label></th>
                                <td><input type="text" name="GRP_LIBELLE" id="GRP_LIBELLE" value="<?php echo $p->getParam('GRP_LIBELLE')?>" size="30"></td>
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
                        <th><?php echo $p->tri(gettext('Libelle'), 'GRP_LIBELLE')?></th>
                        <th><?php echo $p->tri(gettext('Membres'), 'nb')?></th>
                        <?php if (CMS::getCurrentSite()->getSharedSites() > 0) { ?>
                        <th><?php echo gettext('Ouverture')?></th>
                        <?php } ?>
                    </tr>
                </thead>
                <tbody>
                <?php
                $sql = "select GROUPE.ID_GROUPE, GRP_LIBELLE, count(ID_UTILISATEUR) as NB from GROUPE
                    left join GROUPE_UTILISATEUR on GROUPE.ID_GROUPE=GROUPE_UTILISATEUR.ID_GROUPE";
                foreach ($p->fetch($sql, "GROUPE.ID_GROUPE, GRP_LIBELLE") as $rowListe) {
                    ?>
                    <tr>
                        <td><a href="adm_groupe.php?idtf=<?php echo $rowListe['ID_GROUPE']?>"><?php echo secureInput($rowListe['GRP_LIBELLE'])?></a></td>
                        <td class="alignright"><?php echo $rowListe['NB']?></td>
                        <?php if (CMS::getCurrentSite()->getSharedSites() > 0) {?>
                        <td>
                            <?php
                            $sql = "select SIT_LIBELLE from DD_SITE
                                inner join GROUPE_SITE using (SIT_CODE)
                                where ID_GROUPE=" . $rowListe['ID_GROUPE'] . "
                                order by SIT_LIBELLE";
                            $aSITE_LIBELLE = $dbh->query($sql)->fetchAll(PDO :: FETCH_COLUMN);
                            if (count($aSITE_LIBELLE) > 0) {
                                echo '<ul>';
                                foreach ($aSITE_LIBELLE as $SITE_LIBELLE) echo '<li>'.secureInput($SITE_LIBELLE).'</li>';
                                echo '</ul>';
                            } ?>
                        </td>
                        <?php } ?>
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
