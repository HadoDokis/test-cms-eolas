<?php
require '../include/inc.bo_init.php';
Utilisateur::checkConnected();
require CLASS_DIR . 'class.db_historique.php';
require CLASS_DIR . 'class.db_page.php';
require CLASS_DIR . 'class.db_webotheque.php';
require CLASS_DIR . 'class.db_formulaire.php';
require CLASS_DIR . 'class.Pagination.php';

if (!isset($_GET['ID_PAGE']) || !is_numeric($_GET['ID_PAGE'])) {
    die(gettext('Ressource_non_disponible'));
}

$filtre = " p.SIT_CODE = " . $dbh->quote(CMS::getCurrentSite()->getID()) . "
            and p.HIS_DATE >= " . intval($_GET['dateDebut']) . "
            and p.HIS_DATE < " . intval($_GET['dateFin']);

$oPage = new Page($_GET['ID_PAGE']);
$arbo = false;
if (isset($_GET['arbo'])) {
    $arbo = true;
    if (!$oPage->exist()) {
        die(gettext('Ressource_non_disponible'));
    }
    $aIdChild = $oPage->getChildrenID();
    $aIdChild[] = $oPage->getID();
    $filtre .= ' and p.ID_PAGE in (' . implode(',',$aIdChild) . ')';
    $titre = 'Historique de la rubrique ' . $oPage->getField('PAG_TITRE');
} else {
    if ($oPage->exist()) {
        $titre = 'Historique de la page ' . $oPage->getField('PAG_TITRE');
    } else {
        $sqlLibelle = "select HIS_INFO from HISTORIQUE_PAGE where ID_PAGE = " . $_GET['ID_PAGE'] . " limit 1";
        $libellePage = $dbh->query($sqlLibelle)->fetchColumn();
        $titre = 'Historique de la page ';
        $titre .= $libellePage;
        $titre .= ' (page supprimée)';
    }
    $filtre .= ' and p.ID_PAGE = ' . $_GET['ID_PAGE'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <?php include '../include/inc.bo_enTete.php';?>
</head>
<body id="popup">
    <?php include('../include/inc.bo_bandeau_hautPopup.php') ?>
    <div id="bo_contenuPopup">
        <h2><?php echo secureInput($titre);?></h2>
        <h3>Du <?php echo date('d/m/Y', intval($_GET['dateDebut'])) . ' au ' . date('d/m/Y', strtotime("-1 day", intval($_GET['dateFin'])))?></h3>
        <?php
        $pPage = new Pagination('page');
        $pPage->setOrderBy('p.ID_HISTORIQUE_PAGE desc');
        $pPage->setFilter($filtre);
        $pPage->setCount("select count(distinct p.ID_HISTORIQUE_PAGE)
                      from HISTORIQUE_PAGE p
                      left join HISTORIQUE_UTILISATEUR u on (u.ID_HISTORIQUE_UTILISATEUR = p.ID_HISTORIQUE_UTILISATEUR)");

         $sql = "select p.*, p.HIS_DETAIL as DETAIL, p.HIS_DATE as DATEHISTO, trim(concat_ws(' ', UTI_PRENOM, UTI_NOM, HIS_UTILISATEUR)) as LIBELLE
                 from HISTORIQUE_PAGE p
                 left join HISTORIQUE_UTILISATEUR u on (u.ID_HISTORIQUE_UTILISATEUR = p.ID_HISTORIQUE_UTILISATEUR)
                 left join UTILISATEUR ut on (ut.ID_UTILISATEUR = u.ID_UTILISATEUR)";

        if ($pPage->getNb() > 0) {
            $aWorkflow = $dbh->query("select PST_CODE, PST_LIBELLE from DD_PAGESTATUT")->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE | PDO::FETCH_COLUMN);?>
            <p class="aligncenter"><?php echo $pPage->reglette()?></p>
            <table class="liste">
                <tr>
                    <th><?php echo gettext('Date') ?></th>
                    <th><?php echo gettext('Contributeur') ?></th>
                    <th><?php echo gettext('N°') ?></th>
                    <th><?php echo gettext('Page')?></th>
                    <th><?php echo gettext('Action')?></th>
                    <th><?php echo gettext('Detail')?></th>
                </tr>
                <?php foreach ($pPage->fetch($sql) as $rowPage) {
                    $oPage = new Page($rowPage['ID_PAGE']);?>
                    <tr>
                        <td class="aligncenter"><?php echo date('d/m/Y H:i', intval($rowPage['DATEHISTO']))?></td>
                        <td class="aligncenter"><?php echo secureInput($rowPage['LIBELLE'])?></td>
                        <td class="aligncenter"><?php echo intval($rowPage['ID_PAGE'])?></td>
                        <td><?php echo secureInput($oPage->exist() ? $oPage->getField('PAG_TITRE_MENU') : $rowPage['HIS_INFO']);?></td>
                        <td>
                            <?php echo $aHistoriqueAction[$rowPage['HIS_ACTION']][$rowPage['HIS_TYPE']]?>
                            <?php echo $rowPage['HIS_TYPE'] == 'PARAGRAPHE' ? ' n°' . intval($rowPage['ID_PARAGRAPHE']) : '';?>
                        </td>
                        <?php if ($rowPage['HIS_TYPE'] == 'WORKFLOW') {?>
                            <td class="aligncenter <?php echo $rowPage['DETAIL']?>">
                                <?php echo secureInput(extraireLibelle($aWorkflow[$rowPage['DETAIL']]));?>
                            </td>
                        <?php } else {?>
                            <td>
                                <?php echo secureInput($rowPage['DETAIL']);?>
                            </td>
                        <?php }?>
                    </tr>
                <?php }?>
            </table>
        <?php }?>
    </div>
    <?php include('../include/inc.bo_bandeau_basPopup.php') ?>
</body>
</html>
