<?php
require '../include/inc.bo_init.php';
Utilisateur::checkConnected();
require CLASS_DIR . 'class.db_page.php';
require CLASS_DIR . 'class.db_revision.php';
require CLASS_DIR . 'class.Pagination.php';
require CLASS_DIR . 'class.db_historique.php';

$oPage = new Page($_GET['idtf']);
$oPage->checkAuthorized();

$aWorkflow = $dbh->query("select PST_CODE, PST_LIBELLE from DD_PAGESTATUT")->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE | PDO::FETCH_COLUMN);

$p = new Pagination();
if (!$p->onSearch()) {
    $p->setOrderBy('HISTORIQUE_PAGE.ID_HISTORIQUE_PAGE desc');
    $p->setParam('idtf', $oPage->getID());
}
$p->setFilter('HISTORIQUE_PAGE.ID_PAGE = ' . $oPage->getID());
$p->setCount("select count(ID_HISTORIQUE_PAGE) from HISTORIQUE_PAGE");
?>
<!DOCTYPE html>
<html>
<head>
<?php include('../include/inc.bo_enTete.php')?>
<script>
function showRevision(rev)
{
    window.opener.location.href='cms_revisionListe.php?idtf=<?php echo $oPage->getID()?>&rev=' + rev + '&details=1';
    window.close();
    window.opener.focus();
}
function revertRevision(rev)
{
    if (confirm('<?php echo escapeJS(gettext('Etes-vous sur ?'))?>')) {
        window.opener.location.href='cms_revisionSubmit.php?Revert=' + rev;
        window.close();
        window.opener.focus();
    }
}
</script>
</head>
<body id="popup">
<?php include('../include/inc.bo_bandeau_hautPopup.php')?>
<div id="bo_contenuPopup">
    <h2><?php echo gettext('Historique et revisions')?></h2>

    <?php
echo $p->reglette();
if ($p->getNb() > 0) {
?>
    <table class="liste">
        <thead>
            <tr>
                <th><?php echo $p->tri(gettext('Date'), 'HIS_DATE')?></th>
                <th><?php echo $p->tri(gettext('Utilisateur'), 'HIS_UTILISATEUR')?></th>
                <th><?php echo $p->tri(gettext('N°'), 'ID_PARAGRAPHE')?></th>
                <th><?php echo gettext('Action')?></th>
                <th><?php echo gettext('Detail')?></th>
                <?php if (!Utilisateur::getConnected()->isSEO() && $oPage->checkAuthorized(false)) { ?>
                <th><?php echo gettext('Revision')?></th>
                <?php } ?>
            </tr>
        </thead>
        <tbody>
        <?php
        $sql = "select HISTORIQUE_PAGE.*,
            trim(concat_ws(' ', UTI_NOM, UTI_PRENOM, HIS_UTILISATEUR)) HIS_UTILISATEUR
            from HISTORIQUE_PAGE
            left join HISTORIQUE_UTILISATEUR on HISTORIQUE_PAGE.ID_HISTORIQUE_UTILISATEUR = HISTORIQUE_UTILISATEUR.ID_HISTORIQUE_UTILISATEUR
            left join UTILISATEUR on HISTORIQUE_UTILISATEUR.ID_UTILISATEUR = UTILISATEUR.ID_UTILISATEUR";
        foreach ($p->fetch($sql) as $rowListe) {?>
            <tr>
                <td class="aligncenter"><?php echo date('d/m/Y H:i', $rowListe['HIS_DATE'])?></td>
                <td><?php echo secureInput($rowListe['HIS_UTILISATEUR'])?></td>
                <td class="aligncenter">
                    <?php if (!empty($rowListe['ID_PARAGRAPHE'])) {
                        echo $rowListe['ID_PARAGRAPHE'];
                    }?>
                </td>
                <td><?php echo $aHistoriqueAction[$rowListe['HIS_ACTION']][$rowListe['HIS_TYPE']]?></td>
                <?php if ($rowListe['HIS_TYPE'] == 'WORKFLOW') {?>
                    <td class="aligncenter <?php echo $rowListe['HIS_DETAIL']?>">
                        <?php echo extraireLibelle($aWorkflow[$rowListe['HIS_DETAIL']]);?>
                    </td>
                <?php } else {?>
                    <td>
                        <?php echo secureInput($rowListe['HIS_DETAIL']);?>
                    </td>
                <?php }?>
                <?php if (!Utilisateur::getConnected()->isSEO() && $oPage->checkAuthorized(false)) { ?>
                <td class="aligncenter">
                    <?php
                    if (is_numeric($rowListe['ID_REVISION'])) {
                       $oRevision = new Revision($rowListe['ID_REVISION']);
                       if ($oRevision->exist()) {
                    ?>
                    <a href="javascript:void(0);" onclick="showRevision(<?php echo $rowListe['ID_REVISION']?>)"><?php echo gettext('Visualiser') ?></a>
                    <?php
                       } else {
                    ?>
                    <?php echo gettext('cette_revision_est_supprime') ?>
                    <?php
                       }
                    }
                    ?>
                </td>
            <?php } ?>
            </tr>
        <?php } ?>
        </tbody>
    </table>
    <?php } ?>
</div>
<?php include('../include/inc.bo_bandeau_basPopup.php')?>
</body>
</html>
