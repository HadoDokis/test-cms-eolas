<?php
require '../include/inc.bo_init.php';
require CLASS_DIR . 'class.Pagination.php';
require CLASS_DIR . 'class.db_alerte.php';
require CLASS_DIR . 'class.db_historique.php';
Utilisateur::checkConnected();

$_aPage = array (-1);
foreach (Utilisateur::getConnected()->getProfils() as $tabPage) {
    if (is_array($tabPage)) {
        $_aPage = array_unique(array_merge($_aPage, $tabPage));
    }
}
$aWorkflow = $dbh->query("select PST_CODE, PST_LIBELLE from DD_PAGESTATUT")->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE | PDO::FETCH_COLUMN);

if (isset($_GET['removeMaxVisite']) && Utilisateur::getConnected()->isRoot()) {
    CMS::getCurrentSite()->resetMaxVist();
    header('Location:' . SERVER_ROOT . 'cms/cms_index.php');
    exit();
}
if (isset($_GET['ancrageMenu'])) {
    if (isset($_COOKIE['C_ancrageMenu'])) {
        setcookie('C_ancrageMenu', '', 0, '/');
    } else {
        setcookie('C_ancrageMenu', 1, time() + 86400 * 365, '/');
    }
    header('Location:' . SERVER_ROOT . 'cms/cms_index.php');
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <?php include('../include/inc.bo_enTete.php') ?>
    <script src="<?php echo SERVER_ROOT ?>include/js/onglet.js"></script>
    <script src="<?php echo SERVER_ROOT ?>include/js/jquery/flot/jquery.flot.js"></script>
    <script src="<?php echo SERVER_ROOT ?>include/js/jquery/flot/jquery.flot.categories.js"></script>
    <link rel="stylesheet" href="<?php echo SERVER_ROOT ?>include/js/jquery/flot/jquery.flot.css">
</head>
<body>
<div id="document" class="accueil">
    <?php $aMenuKey = array('TDB', 'VE'); include('../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">

            <?php if ($oAlerte = Alerte::getCurrent(CMS::getCurrentSite()->getID())) { ?>
            <div class="bo_alerte">
                <div class="left"><img src="../images/pictoBoAlerte.png" alt="Alerte"></div>
                <div class="right"><?php echo nl2br(secureInput($oAlerte->getField('ALT_MESSAGE')))?></div>
            </div>
            <?php } ?>

            <div class="blocAccueil donneesPages">
                <h3>Données sur les pages</h3>
                <fieldset class="tab">
                    <legend><?php echo gettext('Dernieres_pages_modifies');?></legend>
                    <?php
                    $p = new Pagination('a');
                    $p->setFilter("HISTORIQUE_PAGE.ID_PAGE in (" . implode(',', $_aPage) . ")");
                    $p->setMPP(10);
                    $p->setMaxResult(50);
                    $p->setOrderBy('ID_HISTORIQUE_PAGE desc');
                    $p->setCount("select count(ID_HISTORIQUE_PAGE) from HISTORIQUE_PAGE");
                    $sql = "select PAG_TITRE_MENU, HISTORIQUE_PAGE.*, trim(concat_ws(' ', UTI_NOM, UTI_PRENOM, HIS_UTILISATEUR)) HIS_UTILISATEUR from HISTORIQUE_PAGE
                            inner join OFF_PAGE on HISTORIQUE_PAGE.ID_PAGE = OFF_PAGE.ID_PAGE
                            left join HISTORIQUE_UTILISATEUR on HISTORIQUE_PAGE.ID_HISTORIQUE_UTILISATEUR = HISTORIQUE_UTILISATEUR.ID_HISTORIQUE_UTILISATEUR
                            left join UTILISATEUR on HISTORIQUE_UTILISATEUR.ID_UTILISATEUR = UTILISATEUR.ID_UTILISATEUR";
                    echo $p->reglette();
                    if ($p->getNb() > 0) {
                    ?>
                    <table class="liste">
                        <thead>
                            <tr>
                                <th><?php echo gettext('Date') ?></th>
                                <th><?php echo gettext('Page')?></th>
                                <th><?php echo gettext('Utilisateur')?></th>
                                <th><?php echo gettext('Action')?></th>
                                <th><?php echo gettext('Detail')?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($p->fetch($sql) as $rowListe) {?>
                            <tr>
                                <td class="aligncenter"><?php echo date('d/m/Y H:i', $rowListe['HIS_DATE']) ?></td>
                                <td>
                                    <?php if (Utilisateur::getConnected()->isRoot() || (is_array($_aPage) && in_array($rowListe['ID_PAGE'], $_aPage))) {?>
                                        <a href="cms_pseudo.php?idtf=<?php echo $rowListe['ID_PAGE'] ?>"><?php echo secureInput($rowListe['PAG_TITRE_MENU'])?></a>
                                    <?php } else {?>
                                        <?php echo secureInput($rowListe['PAG_TITRE_MENU'])?>
                                    <?php }?>
                                </td>
                                <td><?php echo secureInput($rowListe['HIS_UTILISATEUR'])?></td>
                                <td>
                                    <?php echo secureInput($aHistoriqueAction[$rowListe['HIS_ACTION']][$rowListe['HIS_TYPE']])?>
                                    <?php echo $rowListe['HIS_TYPE'] == 'PARAGRAPHE' ? ' n°' . intval($rowListe['ID_PARAGRAPHE']) : '';?>
                                </td>
                                <?php if ($rowListe['HIS_TYPE'] == 'WORKFLOW') {?>
                                    <td class="aligncenter <?php echo $rowListe['HIS_DETAIL']?>">
                                        <?php echo secureInput(extraireLibelle($aWorkflow[$rowListe['HIS_DETAIL']]));?>
                                    </td>
                                <?php } else {?>
                                    <td class="aligncenter">
                                        <?php echo secureInput($rowListe['HIS_DETAIL']);?>
                                    </td>
                                <?php }?>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                    <?php } ?>
                </fieldset>

                <fieldset class="tab">
                    <legend><?php echo gettext('Dernieres_pages_mise_en_ligne') ?></legend>
                    <?php
                    $p = new Pagination('b');
                    $p->setFilter("ID_PAGE in (" . implode(',', $_aPage) . ")");
                    $p->setMPP(10);
                    $p->setMaxResult(30);
                    $p->setOrderBy('PAG_DATEMISEENLIGNE desc');
                    $p->setCount("select count(ID_PAGE) from ON_PAGE");
                    $sql = "select * from ON_PAGE";
                    echo $p->reglette();
                    if ($p->getNb() > 0) {
                    ?>
                    <table class="liste">
                        <thead>
                            <tr>
                                <th><?php echo gettext('Date') ?></th>
                                <th><?php echo gettext('Page')?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($p->fetch($sql) as $rowListe) {?>
                            <tr>
                                <td class="aligncenter"><?php echo date('d/m/Y H:i', intval($rowListe['PAG_DATEMISEENLIGNE']))?></td>
                                <td>
                                    <?php if (Utilisateur::getConnected()->isRoot() || (is_array($_aPage) && in_array($rowListe['ID_PAGE'], $_aPage))) {?>
                                        <a href="cms_pseudo.php?idtf=<?php echo intval($rowListe['ID_PAGE'])?>"><?php echo secureInput($rowListe['PAG_TITRE_MENU'])?></a>
                                    <?php } else {?>
                                        <?php echo secureInput($rowListe['PAG_TITRE_MENU'])?>
                                    <?php }?>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                    <?php } ?>
                </fieldset>

                <fieldset class="tab">
                    <legend><?php echo gettext('Pages peu mises a jour') ?> (auto)</legend>
                    <?php
                    $p = new Pagination('c');
                    $p->setFilter("ID_PAGE in (" . implode(',', $_aPage) . ") and PST_CODE <> 'PST_HORSLIGNE'");
                    $p->setMPP(10);
                    $p->setMaxResult(20);
                    $p->setOrderBy('PAG_DATEMODIFICATION');
                    $p->setCount("select count(ID_PAGE) from OFF_PAGE");
                    $sql = "select * from OFF_PAGE";
                    echo $p->reglette();
                    if ($p->getNb() > 0) {
                    ?>
                    <table class="liste">
                        <thead>
                            <tr>
                                <th><?php echo gettext('Date') ?></th>
                                <th><?php echo gettext('Page')?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($p->fetch($sql) as $rowListe) {?>
                            <tr>
                                <td class="aligncenter"><?php echo date('d/m/Y H:i', intval($rowListe['PAG_DATEMODIFICATION'])) ?></td>
                                <td>
                                    <?php if (Utilisateur::getConnected()->isRoot() || (is_array($_aPage) && in_array($rowListe['ID_PAGE'], $_aPage))) {?>
                                        <a href="cms_pseudo.php?idtf=<?php echo intval($rowListe['ID_PAGE'])?>"><?php echo secureInput($rowListe['PAG_TITRE_MENU'])?></a>
                                    <?php } else {?>
                                        <?php echo secureInput($rowListe['PAG_TITRE_MENU'])?>
                                    <?php }?>
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                    <?php } ?>
                </fieldset>

                <fieldset class="tab">
                    <legend><?php echo gettext('Pages peu mises a jour') ?> (manuel)</legend>
                    <?php
                    $p = new Pagination('d');
                    $p->setFilter("ID_PAGE in (" . implode(',', $_aPage) . ") and PST_CODE <> 'PST_HORSLIGNE' and PAG_DATEMISEAJOUR is not null");
                    $p->setMPP(10);
                    $p->setMaxResult(30);
                    $p->setOrderBy('PAG_DATEMISEAJOUR');
                    $p->setCount("select count(ID_PAGE) from OFF_PAGE");
                    $sql = "select * from OFF_PAGE";
                    echo $p->reglette();
                    if ($p->getNb() > 0) {
                    ?>
                    <table class="liste">
                        <thead>
                            <tr>
                                <th><?php echo gettext('Date') ?></th>
                                <th><?php echo gettext('Page')?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($p->fetch($sql) as $rowListe) {?>
                            <tr>
                                <td class="aligncenter"><?php echo date('d/m/Y', $rowListe['PAG_DATEMISEAJOUR']) ?></td>
                                <td><a href="cms_pseudo.php?idtf=<?php echo $rowListe['ID_PAGE'] ?>"><?php echo secureInput($rowListe['PAG_TITRE_MENU'])?></a></td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                    <?php } ?>
                </fieldset>

                <?php
                if (Historique::isModuleActif()) {
                    $filtre =  " e.SIT_CODE = " . $dbh->quote(CMS::getCurrentSite()->getID()) ;?>
                    <fieldset class="tab">
                        <legend><?php echo gettext('Derniers_modules_modifies') ?></legend>
                        <?php
                        $p = new Pagination('m');
                        $p->setFilter($filtre);
                        $p->setMPP(10);
                        $p->setMaxResult(20);
                        $p->setOrderBy('e.ID_HISTORIQUE_EXTERNE desc');
                        $p->setCount("select count(distinct e.ID_HISTORIQUE_EXTERNE)
                            from HISTORIQUE_EXTERNE e
                            left join DD_HISTORIQUE_EXTERNE he on (he.HEX_CODE = e.HEX_CODE)");
                        $sql = "select *, e.HIS_DETAIL as DETAIL, e.HIS_DATE as DATEHISTO, trim(concat_ws(' ', UTI_PRENOM, UTI_NOM, HIS_UTILISATEUR)) as LIBELLE, m.MOD_LIBELLE
                            from HISTORIQUE_EXTERNE e
                            left join DD_HISTORIQUE_EXTERNE he on (he.HEX_CODE = e.HEX_CODE)
                            inner join HISTORIQUE_UTILISATEUR u on (u.ID_HISTORIQUE_UTILISATEUR = e.ID_HISTORIQUE_UTILISATEUR)
                            left join UTILISATEUR ut on (ut.ID_UTILISATEUR = u.ID_UTILISATEUR)
                            left join DD_MODULE m on (m.MOD_CODE = he.MOD_CODE)
                            left join MODULE_PROFIL mp on (mp.MOD_CODE = m.MOD_CODE)";
                        echo $p->reglette();
                        if ($p->getNb() > 0) {
                        ?>
                        <table class="liste">
                            <thead>
                                <tr>
                                    <th><?php echo gettext('Date') ?></th>
                                    <th><?php echo gettext('Modules')?></th>
                                    <th><?php echo gettext('Fiche')?></th>
                                    <th><?php echo gettext('Utilisateur')?></th>
                                    <th><?php echo gettext('Action')?></th>
                                    <th><?php echo gettext('Detail')?></th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($p->fetch($sql) as $rowListe) {?>
                                <?php $aInfoModule = Historique::getInfoModule($rowListe['HEX_TABLE'], $rowListe['HEX_CHAMP_ID'], $rowListe['HIS_IDENTIFIANT']);?>
                                    <tr>
                                        <td class="aligncenter"><?php echo date('d/m/Y H:i', intval($rowListe['DATEHISTO'])) ?></td>
                                        <td class="aligncenter"><?php echo secureInput(extraireLibelle($rowListe['MOD_LIBELLE']))?></td>
                                        <td><?php echo secureInput($aInfoModule ? $aInfoModule[$rowListe['HEX_CHAMP_LIBELLE']] : $rowListe['HIS_INFO'])?></td>
                                        <td class="aligncenter"><?php echo secureInput($rowListe['LIBELLE'])?></td>
                                        <td><?php echo secureInput($aHistoriqueAction[$rowListe['HIS_ACTION']]['FICHE'])?></td>
                                        <td><?php echo secureInput($rowListe['DETAIL']);?></td>
                                    </tr>
                            <?php } ?>
                            </tbody>
                        </table>
                        <?php } ?>
                    </fieldset>
                <?php } ?>
            </div>

            <div class="blocAccueil statsFrequentation clearfix">
                <h3>Statistiques Front-Office</h3>
                <?php $_oSite = CMS::getCurrentSite(); ?>
                <div class="gauche<?php if (!$_oSite->getField('SIT_GA_TAG') || !$_oSite->getField('SIT_GA_ID') || !$_oSite->getField('SIT_GA_KEYFILE')) echo ' large'; ?>">
                    <div class="visiteurs">
                        <h4>Visiteurs actifs</h4>
                        <p class="nombre"><?php echo CMS::getCurrentSite()->getNbUserOnline()?></p>
                        <p class="aligncenter">Visiteurs simultanés (1 min)</p>
                    </div>
                    <div class="record">
                        <h4>Record de fréquentation</h4>
                        <p class="nombre"><?php echo CMS::getCurrentSite()->getField('SIT_MAXONLINEUSER')?></p>
                        <?php if (CMS::getCurrentSite()->getField('SIT_DATEMAXONLINEUSER')) { ?>
                        <p class="aligncenter">Visiteurs simultanés le <?php echo date('d/m/Y', CMS::getCurrentSite()->getField('SIT_DATEMAXONLINEUSER'))?></p>
                        <?php } ?>
                    </div>
                    <?php if (Utilisateur::getConnected()->isRoot()) { ?>
                    <a class="raz" href="?removeMaxVisite=1">Remettre à zéro</a>
                    <?php } ?>
                </div>

                <?php if ($_oSite->getField('SIT_GA_TAG') && $_oSite->getField('SIT_GA_ID') && $_oSite->getField('SIT_GA_KEYFILE')) { ?>
                    <div class="droite">
                        <?php
                        $missingAccountData = false;

                        $startTime = strtotime("-30 days", mktime(0,0,0)); // Il y a 30 jours
                        $endTime   = strtotime("yesterday"); // Hier à minuit

                        $aDataDate = array();
                        $i = $startTime;
                        while ($i <= $endTime) {
                            $aDataDate[$i] = 0;
                            $i = strtotime("+1 day", $i);
                        }

                        /** RECUPERATION DES DONNESS POUR LES COURBES **/
                        $sqlVisites = 'select GAD_DATE, SUM(GAD_VISITS) as TOTAL
                            from STAT_GA_DETAIL
                            where GAD_DATE >= '.$startTime.'
                            and GAD_DATE  <= '.$endTime.'
                            and SIT_CODE = '.$dbh->quote(CMS::getCurrentSite()->getID()).'
                            group by GAD_DATE
                            order by GAD_DATE';
                        if ($aVisites = $dbh->query($sqlVisites)->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_UNIQUE | PDO::FETCH_GROUP)) {
                            $aVisites = array_replace($aDataDate, $aVisites);
                            ksort($aVisites);
                        }

                        $sqlVisitesMoteur = 'select GAD_DATE, GAD_VISITS
                            from STAT_GA_DETAIL
                            where GAD_DATE >= '.$startTime.'
                            and GAD_DATE  <= '.$endTime.'
                            and SIT_CODE = '.$dbh->quote(CMS::getCurrentSite()->getID()).'
                            and GAM_CODE = "organic"
                            order by GAD_DATE';
                        if ($aVisitesMoteur = $dbh->query($sqlVisitesMoteur)->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_UNIQUE | PDO::FETCH_GROUP)) {
                            $aVisitesMoteur = array_replace($aDataDate, $aVisitesMoteur);
                            ksort($aVisitesMoteur);
                        }

                        // courbes
                        include '../statistique/stat_visitesCourbes.inc.php'; ?>
                        <div class="visites_container">
                            <div id="placeholder" class="visites_placeholder"></div>
                        </div>
                    </div>
                <?php } ?>
            </div>

            <div class="bo_actu_cms">
                <div class="supportActu blocAccueil"><?php CMS::getRSSFeed(); ?></div>
                <div class="webmarketingActu blocAccueil"><?php CMS::getRSSWebMarketFeed(); ?></div>
            </div>

        </div>
    </div>
    <?php include('../include/inc.bo_bandeau_bas.php') ?>
</div>
</body>
</html>
