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

$oSite = New Site($_GET['SIT_CODE']);
if ($oSite->exist()) {
    $titre = gettext('Historique_du') . ' ' . gettext('Site') . ' : ' . $oSite->getField('SIT_LIBELLE');
} else {
    return;
}

$filtre = ' h.SIT_CODE = ' . $dbh->quote($_GET['SIT_CODE']) . '
            and HID_TYPEDONNEE =  "UTILISATEUR"
            and HID_TOTALACTION = 0
            and HID_TOTALCONNEXION = 0
            and cal.HIC_YEAR = ' . $dbh->quote($_GET['year']);

if (isset($_GET['month']) && !empty($_GET['month'])) {
     $filtre .= ' and cal.HIC_MONTH = ' . $dbh->quote($_GET['month']);
    $dateDebut = mktime(0,0,0,$_GET['month'], 1, $_GET['year']);
    $dateFin = strtotime("next month",$dateDebut);
} else {
    $dateDebut = mktime(0,0,0,1, 1, $_GET['year']);
    $dateFin = strtotime("next year",$dateDebut);
}
// Après avoir initialisé les différents variables,
// nous réinitialions la date de fin à la date du jour (borne non incluse)
// si elle est, initialement, supérieure (info cohérante affichée dans l'export et les popup détaillées)
$dateFin = ($dateFin > mktime(0,0,0))?mktime(0,0,0):$dateFin;
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
        <h3>Du <?php echo date('d/m/Y', intval($dateDebut)) . ' au ' . date('d/m/Y', strtotime("-1 day", intval($dateFin)))?></h3>
        <div class="onglet_panels">
        <?php
            $pUser = new Pagination('user');
            $pUser->setOrderBy('HID_LASTDATE desc');
            $pUser->setFilter($filtre);
            $pUser->setCount("select count(distinct HID_IDENTIFIANT) from HISTORIQUE_CALENDRIER cal
                              inner join HISTORIQUE_PERIODE h on(h.HIC_DATE = cal.HIC_DATE)");

            $sql = "select
                        distinct HID_IDENTIFIANT,
                        HID_INFO as NOM,
                        UTI_LASTCONNEXION
                        from HISTORIQUE_CALENDRIER cal
                     inner join HISTORIQUE_PERIODE h on(h.HIC_DATE = cal.HIC_DATE)
                     left join UTILISATEUR u on(u.ID_UTILISATEUR = HID_IDENTIFIANT)";?>

            <fieldset class="tab" >
                <legend><?php echo gettext('Utilisateurs inactifs')?></legend>
                <p class="aligncenter"><?php echo $pUser->reglette()?></p>
                <?php if ($pUser->getNb() > 0) {?>
                <table class="liste">
                    <tr>
                        <th><?php echo gettext('N°')?></th>
                        <th><?php echo gettext('Utilisateur')?></th>
                        <th><?php echo gettext('Derniere connexion')?></th>
                    </tr>
                    <?php foreach ($pUser->fetch($sql) as $rowListe) {?>
                        <tr>
                            <td class="aligncenter"><?php echo secureInput($rowListe['HID_IDENTIFIANT'])?></td>
                            <td class="aligncenter"><?php echo secureInput($rowListe['NOM'])?></td>
                            <td class="aligncenter"><?php echo !empty($rowListe['UTI_LASTCONNEXION']) ? date('d/m/Y', intval($rowListe['UTI_LASTCONNEXION'])) : '-'?></td>
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
