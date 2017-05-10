<?php
require '../include/inc.bo_init.php';
Utilisateur::checkConnected();
require CLASS_DIR . 'class.db_historique.php';
require CLASS_DIR . 'class.db_page.php';
require CLASS_DIR . 'class.db_webotheque.php';
require CLASS_DIR . 'class.db_formulaire.php';
require CLASS_DIR . 'class.Pagination.php';

if (!isset($_GET['ID_FORMULAIRE']) || !is_numeric($_GET['ID_FORMULAIRE'])) {
    die(gettext('Ressource_non_disponible'));
}
$filtre = " h.SIT_CODE = " . $dbh->quote(CMS::getCurrentSite()->getID()) . "
            and h.ID_FORMULAIRE = " . $dbh->quote($_GET['ID_FORMULAIRE']) . "
            and h.HIS_DATE >= " . intval($_GET['dateDebut']) . "
            and h.HIS_DATE < " . intval($_GET['dateFin']);

$oFormulaire = new Formulaire($_GET['ID_FORMULAIRE']);
if ($oFormulaire->exist()) {
    $titre = gettext('Historique_du') . ' ' . gettext('formulaire') . ' : ' . $oFormulaire->getField('FRM_LIBELLE');
} else {
    $sqlLibelle = "select HIS_INFO from HISTORIQUE_FORMULAIRE where ID_FORMULAIRE = " . intval($_GET['ID_FORMULAIRE']) . " limit 1";
    $titre = gettext('Historique_du') . ' ' . gettext('formulaire') . ' ';
    $titre .= $dbh->query($sqlLibelle)->fetchColumn();
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
        <h2><?php echo secureInput($titre)?></h2>
        <h3>Du <?php echo date('d/m/Y', intval($_GET['dateDebut'])) . ' au ' . date('d/m/Y', strtotime("-1 day", intval($_GET['dateFin'])))?></h3>
        <?php
        $p = new Pagination();
        $p->setOrderBy('h.ID_HISTORIQUE_FORMULAIRE desc');
        $p->setFilter($filtre);
        $p->setCount("select count(distinct h.ID_HISTORIQUE_FORMULAIRE)
                      from HISTORIQUE_FORMULAIRE h
                      left join FORMULAIRE f on (f.ID_FORMULAIRE = h.ID_FORMULAIRE)");

         $sql = "select h.*, h.HIS_DATE as DATEHISTO, f.FRM_LIBELLE, trim(concat_ws(' ', UTI_PRENOM, UTI_NOM, HIS_UTILISATEUR)) as LIBELLE
                      from HISTORIQUE_FORMULAIRE h
                      left join FORMULAIRE f on (f.ID_FORMULAIRE = h.ID_FORMULAIRE)
                      left join HISTORIQUE_UTILISATEUR hu on (hu.ID_HISTORIQUE_UTILISATEUR = h.ID_HISTORIQUE_UTILISATEUR)
                      left join UTILISATEUR u on (u.ID_UTILISATEUR = hu.ID_UTILISATEUR)";

        if ($p->getNb() > 0) {?>
            <p class="aligncenter"><?php echo $p->reglette()?></p>
            <table class="liste">
                <tr>
                    <th><?php echo gettext('Date') ?></th>
                    <th><?php echo gettext('Utilisateur')?></th>
                    <th><?php echo gettext('Action')?></th>
                    <th><?php echo gettext('Detail')?></th>
                </tr>
                <?php foreach ($p->fetch($sql) as $rowForm) {?>
                    <tr>
                        <td class="aligncenter"><?php echo date('d/m/Y H:i', $rowForm['DATEHISTO']) ?></td>
                        <td class="aligncenter"><?php echo secureInput($rowForm['LIBELLE'])?></td>
                        <td><?php echo secureInput($aHistoriqueAction[$rowForm['HIS_ACTION']]['FICHE'])?></td>
                        <td><?php echo secureInput($rowForm['HIS_DETAIL']);?></td>
                    </tr>
                <?php }?>
            </table>
        <?php }?>
    </div>
    <?php include('../include/inc.bo_bandeau_basPopup.php') ?>
</body>
</html>
