<?php
require '../include/inc.bo_init.php';
Utilisateur::checkConnected();
require CLASS_DIR . 'class.db_historique.php';
require CLASS_DIR . 'class.db_page.php';
require CLASS_DIR . 'class.db_webotheque.php';
require CLASS_DIR . 'class.db_formulaire.php';
require CLASS_DIR . 'class.Pagination.php';

if (!isset($_GET['WBT_CODE'])) {
    die(gettext('Ressource_non_disponible'));
}
$filtre = " h.SIT_CODE = " . $dbh->quote(CMS::getCurrentSite()->getID()) . "
                and h.WBT_CODE = " . $dbh->quote($_GET['WBT_CODE']) . "
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
        <h2><?php echo gettext('Historique_de_la') . ' ' . gettext('webotheque') . ' : ' . secureInput(Webotheque::$_aTraduction[$_GET['WBT_CODE']])?></h2>
        <h3>Du <?php echo date('d/m/Y', intval($_GET['dateDebut'])) . ' au ' . date('d/m/Y', strtotime("-1 day", intval($_GET['dateFin'])))?></h3>
        <?php
        $p = new Pagination();
        $p->setOrderBy('h.ID_HISTORIQUE_WEBOTHEQUE desc');
        $p->setFilter($filtre);
        $p->setCount("select count(distinct h.ID_HISTORIQUE_WEBOTHEQUE)
                      from HISTORIQUE_WEBOTHEQUE h
                      left join WEBOTHEQUE w on (w.ID_WEBOTHEQUE = h.ID_WEBOTHEQUE)");

         $sql = "select h.*, h.HIS_DATE as DATEHISTO, w.WEB_LIBELLE, trim(concat_ws(' ', UTI_PRENOM, UTI_NOM, HIS_UTILISATEUR)) as LIBELLE
                      from HISTORIQUE_WEBOTHEQUE h
                      left join WEBOTHEQUE w on (w.ID_WEBOTHEQUE = h.ID_WEBOTHEQUE)
                      left join HISTORIQUE_UTILISATEUR hu on (hu.ID_HISTORIQUE_UTILISATEUR = h.ID_HISTORIQUE_UTILISATEUR)
                      left join UTILISATEUR u on (u.ID_UTILISATEUR = hu.ID_UTILISATEUR)";

        if ($p->getNb() > 0) {?>
            <p class="aligncenter"><?php echo $p->reglette()?></p>
            <table class="liste">
                <tr>
                    <th><?php echo gettext('Date') ?></th>
                    <th><?php echo gettext('Utilisateur')?></th>
                    <th><?php echo gettext('N°')?></th>
                    <th><?php echo gettext('Libelle')?></th>
                    <th><?php echo gettext('Action')?></th>
                    <th><?php echo gettext('Detail')?></th>
                </tr>
                <?php foreach ($p->fetch($sql) as $rowWebo) {
                    $classWebo = 'Webo_' . str_replace('WBT_', '', $rowWebo['WBT_CODE']);
                    $oWebo = new $classWebo($rowWebo['ID_WEBOTHEQUE']);?>
                    <tr>
                        <td class="aligncenter"><?php echo date('d/m/Y H:i', $rowWebo['DATEHISTO']) ?></td>
                        <td class="aligncenter"><?php echo secureInput($rowWebo['LIBELLE'])?></td>
                        <td class="aligncenter"><?php echo $rowWebo['ID_WEBOTHEQUE']?></td>
                        <td><?php echo secureInput($oWebo->exist() ? $oWebo->getField('WEB_LIBELLE') : $rowWebo['HIS_INFO'])?></td>
                        <td><?php echo secureInput($aHistoriqueAction[$rowWebo['HIS_ACTION']]['FICHE'])?></td>
                        <td><?php echo secureInput($rowWebo['HIS_DETAIL']);?></td>
                    </tr>
                <?php }?>
            </table>
        <?php }?>
    </div>
    <?php include('../include/inc.bo_bandeau_basPopup.php') ?>
</body>
</html>
