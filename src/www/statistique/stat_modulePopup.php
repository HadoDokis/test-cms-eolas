<?php
require '../include/inc.bo_init.php';
Utilisateur::checkConnected();
require CLASS_DIR . 'class.db_historique.php';
require CLASS_DIR . 'class.db_page.php';
require CLASS_DIR . 'class.db_webotheque.php';
require CLASS_DIR . 'class.db_formulaire.php';
require CLASS_DIR . 'class.Pagination.php';

if (!isset($_GET['HEX_CODE'])) {
    die(gettext('Ressource_non_disponible'));
}
$filtre = " e.SIT_CODE = " . $dbh->quote(CMS::getCurrentSite()->getID()) . "
            and e.HEX_CODE = " . $dbh->quote($_GET['HEX_CODE']) . "
            and e.HIS_DATE >= " . intval($_GET['dateDebut']) . "
            and e.HIS_DATE < " . intval($_GET['dateFin']);

$p = new Pagination('module');
$p->setOrderBy('e.ID_HISTORIQUE_EXTERNE desc');
$p->setFilter($filtre);
$p->setCount("select count(distinct e.ID_HISTORIQUE_EXTERNE)
                      from HISTORIQUE_EXTERNE e");

?>
<!DOCTYPE html>
<html>
<head>
    <?php include '../include/inc.bo_enTete.php';?>
</head>
<body id="popup">
    <?php include('../include/inc.bo_bandeau_hautPopup.php') ?>
    <div id="bo_contenuPopup">
        <h2><?php echo secureInput(gettext('Historique_du') . ' ' . gettext('module') . ' : ' . $_GET['LIBELLE'])?></h2>
        <h3>Du <?php echo secureInput(date('d/m/Y', intval($_GET['dateDebut'])) . ' au ' . date('d/m/Y', strtotime("-1 day", intval($_GET['dateFin']))))?></h3>
        <p class="aligncenter"><?php echo $p->reglette()?></p>
        <?php
        $sql = "select *, e.HIS_DETAIL as DETAIL, e.HIS_DATE as DATEHISTO, trim(concat_ws(' ', UTI_PRENOM, UTI_NOM, HIS_UTILISATEUR)) as LIBELLE
                from HISTORIQUE_EXTERNE e
                left join DD_HISTORIQUE_EXTERNE he on (he.HEX_CODE = e.HEX_CODE)
                left join HISTORIQUE_UTILISATEUR u on (u.ID_HISTORIQUE_UTILISATEUR = e.ID_HISTORIQUE_UTILISATEUR)
                left join UTILISATEUR ut on (ut.ID_UTILISATEUR = u.ID_UTILISATEUR)";

            if ($p->getNb() > 0) {?>
            <table class="liste">
                <tr>
                    <th><?php echo gettext('Date') ?></th>
                    <th><?php echo gettext('Utilisateur') ?></th>
                    <th><?php echo gettext('N°') ?></th>
                    <th><?php echo gettext('Libelle')?></th>
                    <th><?php echo gettext('Action')?></th>
                    <th><?php echo gettext('Detail')?></th>
                </tr>
                <?php foreach ($p->fetch($sql) as $rowModule) {
                    $aInfoModule = Historique::getInfoModule($rowModule['HEX_TABLE'], $rowModule['HEX_CHAMP_ID'], $rowModule['HIS_IDENTIFIANT']);?>
                    <tr>
                        <td class="aligncenter"><?php echo date('d/m/Y H:i', intval($rowModule['DATEHISTO']))?></td>
                        <td class="aligncenter"><?php echo secureInput($rowModule['LIBELLE'])?></td>
                        <td class="aligncenter"><?php echo secureInput($rowModule['HIS_IDENTIFIANT'])?></td>
                        <td><?php echo secureInput($aInfoModule ? $aInfoModule[$rowModule['HEX_CHAMP_LIBELLE']] : $rowModule['HIS_INFO'])?></td>
                        <td><?php echo secureInput($aHistoriqueAction[$rowModule['HIS_ACTION']]['FICHE']);?></td>
                        <td><?php echo secureInput($rowModule['DETAIL']);?></td>
                    </tr>
                <?php }?>
            </table>
        <?php }?>
    </div>
    <?php include('../include/inc.bo_bandeau_basPopup.php') ?>
</body>
</html>
