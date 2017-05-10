<?php
require '../include/inc.bo_init.php';
Utilisateur::checkConnected();
require CLASS_DIR . 'class.db_historique.php';
require CLASS_DIR . 'class.db_page.php';
require CLASS_DIR . 'class.db_webotheque.php';
require CLASS_DIR . 'class.db_formulaire.php';
require CLASS_DIR . 'class.Pagination.php';

if ($_GET['HIS_TYPE'] == "SITE") {
    $titre = CMS::getCurrentSite()->getField('SIT_LIBELLE') . ' : ' . gettext('Historique_du') . ' ' . gettext('site');
} else if ($_GET['HIS_TYPE'] == "UTILISATEUR") {
    $titre = CMS::getCurrentSite()->getField('SIT_LIBELLE') . ' : ' . gettext('Historique_des') . ' ' . gettext('utilisateurs');
} else {
    die(gettext('Ressource_non_disponible'));
}
$filtre = " h.SIT_CODE = " . $dbh->quote(CMS::getCurrentSite()->getID()) . "
            and h.HIS_TYPE = " . $dbh->quote($_GET['HIS_TYPE']) . "
            and h.HIS_DATE >= " . intval($_GET['dateDebut']) . "
            and h.HIS_DATE < " . intval($_GET['dateFin']);
?>
<!DOCTYPE html>
<html>
<head>
    <?php include '../include/inc.bo_enTete.php';?>
</head>
<body id="popup">
    <?php include('../include/inc.bo_bandeau_hautPopup.php') ?>
    <div id="bo_contenuPopup">
        <h2><?php echo secureInput($titre)?></h2>
        <h3>Du <?php echo date('d/m/Y', intval($_GET['dateDebut'])) . ' au ' . date('d/m/Y', strtotime("-1 day", intval($_GET['dateFin'])))?></h3>

        <?php
        $p = new Pagination();
        $p->setOrderBy('h.ID_HISTORIQUE_ADMIN desc');
        $p->setFilter($filtre);
        $p->setCount("select count(distinct h.ID_HISTORIQUE_ADMIN)
                          from HISTORIQUE_ADMIN h");
        $sql = "select h.*,
            trim(concat_ws(' ', contrib.UTI_PRENOM, contrib.UTI_NOM, hcontrib.HIS_UTILISATEUR)) as CONTRIBUTEUR
            from HISTORIQUE_ADMIN h
            left join HISTORIQUE_UTILISATEUR hcontrib on (hcontrib.ID_HISTORIQUE_UTILISATEUR = h.ID_HISTORIQUE_UTILISATEUR)
            left join UTILISATEUR contrib on (contrib.ID_UTILISATEUR = hcontrib.ID_UTILISATEUR)";
        if ($p->getNb() > 0) {
        ?>
            <p class="aligncenter"><?php echo $p->reglette()?></p>
            <table class="liste">
                <tr>
                    <th><?php echo gettext('Date') ?></th>
                    <th><?php echo gettext('Utilisateur')?></th>
                    <th><?php echo gettext('Fiche modifiée')?></th>
                    <th><?php echo gettext('Action')?></th>
                    <th><?php echo gettext('Detail')?></th>
                </tr>
                <?php
                foreach ($p->fetch($sql) as $rowAdmin) {
                    $libelle = '';
                    if ($rowAdmin['HIS_TYPE'] == 'SITE') {
                        $oSite = new Site($rowAdmin['HIS_IDENTIFIANT']);
                        // Le site doit normallement toujours exister
                        if ($oSite && $oSite->exist()) {
                            $libelle = $oSite->getField('SIT_LIBELLE');
                        }
                    } else {
                        $libelle = $rowAdmin['HIS_UTILISATEUR'];
                        if (empty($libelle)) {
                            $oUser = new Utilisateur($rowAdmin['HIS_IDENTIFIANT']);
                            if ($oUser && $oUser->exist()) {
                                $libelle = $oUser->getField('UTI_PRENOM') . ' ' . $oUser->getField('UTI_NOM');
                            }
                        }
                    }
                    $libelle .= ' (' . $rowAdmin['HIS_IDENTIFIANT'] . ')';
                ?>
                    <tr>
                        <td class="aligncenter"><?php echo date('d/m/Y H:i', intval($rowAdmin['HIS_DATE']));?></td>
                        <td class="aligncenter"><?php echo secureInput($rowAdmin['CONTRIBUTEUR'])?></td>
                        <td><?php echo secureInput($libelle)?></td>
                        <td><?php echo secureInput($aHistoriqueAction[$rowAdmin['HIS_ACTION']][$rowAdmin['HIS_TYPE']])?></td>
                        <td><?php echo secureInput($rowAdmin['HIS_DETAIL']);?></td>
                    </tr>
                <?php } ?>
            </table>
        <?php }?>
        </div>
    <?php include('../include/inc.bo_bandeau_basPopup.php') ?>
</body>
</html>
