<?php
require '../include/inc.bo_init.php';
Utilisateur::checkConnected();
require CLASS_DIR . 'class.db_historique.php';
require CLASS_DIR . 'class.db_page.php';
require CLASS_DIR . 'class.db_webotheque.php';
require CLASS_DIR . 'class.db_formulaire.php';
require CLASS_DIR . 'class.Pagination.php';

if (!isset($_GET['SIT_CODE'])) {
    die(gettext('Ressource_non_disponible'));
}

$filtre = " SIT_CODE = " . $dbh->quote($_GET['SIT_CODE']) . "
            and ID_PAGE not in (select distinct ID_PAGE
                                    from  HISTORIQUE_PAGE h
                                    where
                                    SIT_CODE = " . $dbh->quote($_GET['SIT_CODE']) . "
                                    and HIS_DATE >= " . intval($_GET['dateDebut']) . "
                                    and HIS_DATE < " . intval($_GET['dateFin']) . ")";

$oSite = new Site($_GET['SIT_CODE']);
if ($oSite->exist()) {
    $titre = gettext('Historique_du') . ' ' . gettext('Site') . ' : ' . $oSite->getField('SIT_LIBELLE');
} else {
    return;
}
?>
<!DOCTYPE html>
<html>
<head>
    <?php include '../include/inc.bo_enTete.php';?>
    <script src="<?php echo SERVER_ROOT ?>include/js/onglet.js"></script>
</head>
<body id="popup">
    <?php include('../include/inc.bo_bandeau_hautPopup.php') ?>
    <div id="bo_contenuPopup">
        <h2><?php echo secureInput($titre)?></h2>
        <h3>Du <?php echo date('d/m/Y', intval($_GET['dateDebut'])) . ' au ' . date('d/m/Y', strtotime("-1 day", intval($_GET['dateFin'])))?></h3>
        <div class="onglet_panels">
        <?php
            $pPage = new Pagination('page');
            $pPage->setOrderBy('PAG_DATEMODIFICATION desc');
            $pPage->setFilter($filtre);
            $pPage->setCount("select count(distinct ID_PAGE) from OFF_PAGE");

            $sql = "select
                        ID_PAGE,
                        PAG_TITRE,
                        PAG_DATEMODIFICATION
            from OFF_PAGE";?>

            <fieldset class="tab" >
                <legend><?php echo gettext('Pages inactives')?></legend>
                <p class="aligncenter"><?php echo $pPage->reglette()?></p>
                <?php if ($pPage->getNb() > 0) {?>
                <table class="liste">
                    <tr>
                        <th><?php echo gettext('N°')?></th>
                        <th><?php echo gettext('Page')?></th>
                        <th><?php echo gettext('Derniere modification')?></th>
                    </tr>
                    <?php foreach ($pPage->fetch($sql) as $rowListe) {?>
                        <tr>
                            <td class="aligncenter"><?php echo secureInput($rowListe['ID_PAGE'])?></td>
                            <td class="aligncenter"><?php echo secureInput($rowListe['PAG_TITRE'])?></td>
                            <td class="aligncenter"><?php echo !empty($rowListe['PAG_DATEMODIFICATION']) ? date('d/m/Y', intval($rowListe['PAG_DATEMODIFICATION'])) : '-'?></td>
                        </tr>
                    <?php }?>
                </table>
                <?php }?>
            </fieldset>
            </div>
        </div>
    <?php include('../include/inc.bo_bandeau_basPopup.php') ?>
</body>
</html>
