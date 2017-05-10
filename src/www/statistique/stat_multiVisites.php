<?php
require '../include/inc.bo_init.php';
Utilisateur::checkConnected();

$dbh = DB::getInstance();

// recuperation des sites auquel l'utilisateur a accès
$sqlSites = 'select SIT_LIBELLE, SIT_CODE, SIT_GA_TAG, SIT_GA_ID, SIT_GA_KEYFILE FROM DD_SITE';
if (!Utilisateur::getConnected()->isRoot(true)) {
    $sqlSites = ' inner join ROLE using (SIT_CODE) where ID_UTILISATEUR = '.Utilisateur::getConnected()->getId().' and PRO_CODE = "PRO_ROOT_SITE"';
}

$aSites = $dbh->query($sqlSites)->fetchAll(PDO::FETCH_ASSOC);

$aDonneesInconnues  = array();
$aMarqueur          = array();
$aSitesWithMarqueur = array();
$siteCount = 0;
foreach ($aSites as $site) {
    if (empty($site['SIT_GA_TAG']) || empty($site['SIT_GA_ID']) || empty($site['SIT_GA_KEYFILE'])) {
        $aDonneesInconnues[$site['SIT_CODE']] = $site['SIT_LIBELLE'];
    } else {
        preg_match('/UA-\d{8}-\d{1}/', $site['SIT_GA_TAG'], $marqueur);
        $aMarqueur[$marqueur[0]][$site['SIT_CODE']] =  $site['SIT_LIBELLE'];
        $aSitesWithMarqueur[] = $site['SIT_LIBELLE'];
        $siteCount++;
    }
}

if (!empty($aMarqueur)) {

    $missingAccountData = false;
    $noData             = false;

    // on construit un tableau des SIT_CODE à utiliser pour la récupération des données
    $aSIT_CODE = array();
    foreach ($aMarqueur as $marqueur) {
        $aSIT_CODE[] = key($marqueur); // on prend le premier site pour ce marqueur
    }

    $startTime = strtotime("-30 days", mktime(0,0,0)); // Il y a 30 jours
    $endTime   = strtotime("yesterday"); // Hier à minuit

    $startTimePreviousMonth = mktime(0,0,0, date('m')-2);
    $endTimePreviousMonth   = mktime(0,0,0, date('m')-1, date('d', strtotime('yesterday')));

    $aDataDate = array();
    $i = $startTime;
    while ($i <= $endTime) {
        $aDataDate[$i] = 0;
        $i = strtotime("+1 day", $i);
    }

    if (is_numeric($_GET['Export'])) {

        set_time_limit(0);
        header("Content-type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=\"ExportStatistiquesVisites.csv\"");

        $f = fopen("php://temp", 'w');
        // ajout d'un première ligne avec les noms du site
        fputcsv($f, array(implode(', ', $aSitesWithMarqueur)), ';');
        // ajout entête
        $data = array(
                gettext('Date'),
                'Visites',
                'Pages par visite',
                'Temps moyen',
                'Nouvelles visites',
                'Taux de rebond'
            );
        fputcsv($f, $data, ';');

        $sqlStats = 'select GAD_DATE,
                            SUM(GAD_VISITS) as VISITES,
                            SUM(GAD_PAGEVIEWS) as PAGEVIEWS,
                            SUM(GAD_TIMEONSITE) as TIMEONSITE,
                            SUM(GAD_NEWVISITS) as NEWVISITS,
                            SUM(GAD_BOUNCES) as BOUNCES,
                            SUM(GAD_ENTRANCES) as ENTRANCES
                       from STAT_GA_DETAIL
                       where GAD_DATE >= '.$startTime.'
                       and GAD_DATE  <= '.$endTime.'
                       and SIT_CODE in ("'.implode('", "',$aSIT_CODE).'")
                       group by GAD_DATE
                       order by GAD_DATE';

        if ($aStats = $dbh->query($sqlStats)->fetchAll(PDO::FETCH_ASSOC)) {
            ksort($aStats);
        }

        $aTotalVisites = 0;
        $aTotalPages = 0;
        $aTotalTemps = 0;
        $aTotalNouvelles = 0;
        $aTotalRebond = 0;

        // ajout des données du tableau
        foreach ($aStats as $line) {
            $data = array(
                   date("d/m/Y", $line['GAD_DATE']),
                   formatNum($line['VISITES'], $line['VISITES']),
                   formatNum($line['VISITES'], $line['PAGEVIEWS']/$line['VISITES']),
                   formatTime($line['TIMEONSITE']/$line['VISITES']),
                   formatNum($line['VISITES'], ($line['NEWVISITS']/$line['VISITES'])*100).'%',
                   formatNum($line['ENTRANCES'], ($line['BOUNCES']/$line['ENTRANCES'])*100).'%',
               );
            fputcsv($f, $data, ';');

            $aTotalVisites += $line['VISITES'];
            $aTotalPages += $line['PAGEVIEWS'];
            $aTotalTemps += $line['TIMEONSITE'];
            $aTotalNouvelles += $line['NEWVISITS'];
            $aTotalRebond += $line['BOUNCES'];
            $totalEntrances += $line['ENTRANCES'];
        }

        $data = array(
               gettext('total'),
               formatNum($aTotalVisites, $aTotalVisites),
               formatNum($aTotalVisites, $aTotalPages/$aTotalVisites),
               formatTime($aTotalTemps/$aTotalVisites),
               formatNum($aTotalVisites, ($aTotalNouvelles/$aTotalVisites)*100).'%',
               formatNum($totalEntrances, ($aTotalRebond/$totalEntrances)*100).'%'
           );
        fputcsv($f, $data, ';');

        rewind($f);
        echo utf8_decode(stream_get_contents($f));
        exit();
    }

    /** RECUPERATION DES DONNESS POUR LES COURBES **/

    $sqlVisites = 'select GAD_DATE, SUM(GAD_VISITS) as TOTAL
                   from STAT_GA_DETAIL
                   where GAD_DATE >= '.$startTime.'
                   and GAD_DATE  <= '.$endTime.'
                   and SIT_CODE in ("'.implode('", "',$aSIT_CODE).'")
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
                         and SIT_CODE in ("'.implode('", "',$aSIT_CODE).'")
                         and GAM_CODE = "organic"
                         order by GAD_DATE';
    if ($aVisitesMoteur = $dbh->query($sqlVisitesMoteur)->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_UNIQUE | PDO::FETCH_GROUP)) {
        $aVisitesMoteur = array_replace($aDataDate, $aVisitesMoteur);
        ksort($aVisitesMoteur);
    }

    /** RECUPERATION DES DONNES POUR LE TABLEAU **/

    // récupération de données pour le mois précédent
    $sqlLastMonth = 'select sum(GAD_VISITS) as GAD_VISITS,
                  sum(GAD_PAGEVIEWS) as GAD_PAGEVIEWS
                  from STAT_GA_DETAIL
                  where GAD_DATE >= '.$startTimePreviousMonth.'
                  and GAD_DATE  <= '.$endTimePreviousMonth.'
                  and SIT_CODE in ("'.implode('", "',$aSIT_CODE).'")';

    $rowLastMonth = $dbh->query($sqlLastMonth)->fetch(PDO::FETCH_ASSOC);

    // récupération de données pour le mois courant
    $sqlCurrentMonth = 'select
                  GAM_CODE,
                  GAM_LIBELLE,
                  sum(GAD_VISITS) as GAD_VISITS,
                  sum(GAD_PAGEVIEWS) as GAD_PAGEVIEWS,
                  sum(GAD_TIMEONSITE) as GAD_TIMEONSITE,
                  sum(GAD_NEWVISITS) as GAD_NEWVISITS,
                  sum(GAD_BOUNCES) as GAD_BOUNCES,
                  sum(GAD_ENTRANCES) as GAD_ENTRANCES
                  from STAT_GA_DETAIL
                  inner join STAT_GA_MEDIUM using (GAM_CODE)
                  where GAD_DATE >= '.$startTime.'
                  and GAD_DATE  <= '.$endTime.'
                  and SIT_CODE in ("'.implode('", "',$aSIT_CODE).'")
                  group by GAM_CODE
                  order by GAM_ORDRE';

    $aStat = $dbh->query($sqlCurrentMonth)->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($aStat)) {
        $aGAD_VISITS      = array();
        $aGAD_PAGEVIEWS   = array();
        $aGAD_TIMEONSITE  = array();
        $aGAD_NEWVISITS   = array();
        $aGAD_BOUNCES     = array();
        $aGAD_ENTRANCES   = array();

        foreach ($aStat as $row) {
            $aMedium[$row['GAM_CODE']] = $row['GAM_LIBELLE'];
            $aGAD_VISITS[$row['GAM_CODE']]     = $row['GAD_VISITS'];
            $aGAD_PAGEVIEWS[$row['GAM_CODE']]  = $row['GAD_PAGEVIEWS'];
            $aGAD_TIMEONSITE[$row['GAM_CODE']] = $row['GAD_TIMEONSITE'];
            $aGAD_NEWVISITS[$row['GAM_CODE']]  = $row['GAD_NEWVISITS'];
            $aGAD_BOUNCES[$row['GAM_CODE']]    = $row['GAD_BOUNCES'];
            $aGAD_ENTRANCES[$row['GAM_CODE']]  = $row['GAD_ENTRANCES'];
        }
    } else {
        $noData = true;
    }

} else {
    $missingAccountData = true;
} ?>

<!DOCTYPE html>
<html>
<head>
<?php include('../include/inc.bo_enTete.php');
if (!$missingAccountData && !$noData) { ?>
    <script src="<?php echo SERVER_ROOT ?>include/js/jquery/flot/jquery.flot.js"></script>
    <script src="<?php echo SERVER_ROOT ?>include/js/jquery/flot/jquery.flot.categories.js"></script>
    <link rel="stylesheet" href="<?php echo SERVER_ROOT ?>include/js/jquery/flot/jquery.flot.css">
    <?php
    // courbes
    include 'stat_visitesCourbes.inc.php';
} ?>
    <script type="text/javascript">
        $(document).ready(function () {
            $('.site_concerne').click(function () {
                if ($('#site_concerne').hasClass('invisible')) {
                    $('#site_concerne').removeClass('invisible');
                } else {
                    $('#site_concerne').addClass('invisible');
                }
            });

            $('.site_non_concerne').click(function () {
                if ($('#site_non_concerne').hasClass('invisible')) {
                    $('#site_non_concerne').removeClass('invisible');
                } else {
                    $('#site_non_concerne').addClass('invisible');
                }
            });
        });
    </script>
    <style>
       h3 {cursor: pointer; text-decoration : underline;}
    </style>
</head>
<body>
<div id="document">
    <?php $aMenuKey = array('TDB', 'SMS', 'VST'); include('../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">
            <h2>
                <?php
                echo ucfirst(gettext('tous_mes_sites')) . ' : statistiques des visites';
                /*if (!$missingAccountData && !$noData) {
                    echo secureInput(' : ' . sprintf(gettext('visites_du_%s_au_%s'), date('d/m/Y',$startTime), date('d/m/Y',mktime(0,0,0, date('m'), date('d')-1))));
                }*/
                ?>
            </h2>

            <?php

            if (!$missingAccountData && !$noData) { ?>
                <h3 class="site_concerne"><img src="<?php echo SERVER_ROOT?>images/push_down.png" alt="<?php echo gettext('Voir')?>"><?php echo gettext('sites_concernes');?></h3>
                 <p id="site_concerne" class="invisible">
                    <?php $i = 0;
                    foreach ($aMarqueur as $aSite) {
                        foreach ($aSite as $site) {
                            if ($i > 0) {
                                echo  ' | ';
                            }
                            echo '<span style="color:#2680A9">' . secureInput($site) . '</span>';
                            $i++;
                        }
                    }?>
                </p>
                <?php if (!empty($aDonneesInconnues)) {?>
                    <h3 class="site_non_concerne"><img src="<?php echo SERVER_ROOT?>images/push_down.png" alt="<?php echo gettext('Voir')?>"><?php echo gettext('sites_non_concernes');?></h3>
                    <p id="site_non_concerne" class="invisible">
                        <?php $i = 0;
                        foreach ($aDonneesInconnues as $SIT_CODE => $SIT_LIBELLE) {
                            if ($i > 0) {
                                echo  ' | ';
                            }
                            echo '<a href="/cms/administration/adm_site.php?idtf='.$SIT_CODE.'#fieldset_2">'.secureInput($SIT_LIBELLE).'</a>';
                            $i++;
                        }?>
                    </p>
                <?php }?>
                <div class="visites_container">
                    <div id="placeholder" class="visites_placeholder"></div>
                </div>

                <h4 class="aligncenter">Fréquentation par source</h4>

                <?php // visites
                foreach ($aMedium as $gam_code => $libelle) {
                    $currentMonthVisite += $aGAD_VISITS[$gam_code];
                }
                $ratioVisite = $rowLastMonth['GAD_VISITS'] ? number_format((($currentMonthVisite-$rowLastMonth['GAD_VISITS'])/$rowLastMonth['GAD_VISITS'])*100, 0) : 100;
                ?>
                <p id="visites" class="detailVisite">
                    <?php echo $currentMonthVisite .' '; echo $currentMonthVisite > 1 ? 'visites' : 'visite'; ?>
                    <span class="note">
                         <?php
                            if ($ratioVisite > 0) {
                                echo '>';
                            } else if ($ratioVisite < 0) {
                                echo '<';
                            } else if ($ratioVisite == 0) {
                                echo '=';
                            }
                        ?>
                        (<?php echo ($ratioVisite>0?'+':'').$ratioVisite.'% '.gettext('par_rapport_au_mois_precedent') ?>)
                    </span>
                </p>

                <?php // pages vues
                foreach ($aMedium as $gam_code => $libelle) {
                    $currentMonthPageVues += $aGAD_PAGEVIEWS[$gam_code];
                }
                $ratioPageVues = $rowLastMonth['GAD_PAGEVIEWS'] ? number_format((($currentMonthPageVues-$rowLastMonth['GAD_PAGEVIEWS'])/$rowLastMonth['GAD_PAGEVIEWS'])*100, 0) : 100;
                ?>

                <p id="pagesVues" class="detailVisite">
                    <?php echo $currentMonthPageVues .' '; echo $currentMonthPageVues > 1 ? gettext('pages_vues') : gettext('page_vue'); ?>
                    <span class="note">
                        <?php
                            if ($ratioPageVues > 0) {
                                echo '>';
                            } else if ($ratioPageVues < 0) {
                                echo '<';
                            } else if ($ratioPageVues == 0) {
                                echo '=';
                            }
                        ?>
                        (<?php echo ($ratioPageVues>0?'+':'').$ratioPageVues.'% '.gettext('par_rapport_au_mois_precedent') ?>)
                    </span>
                </p>

                <p id="export" class="detailVisite">
                    <a class="btnAction" href="stat_multiVisites.php?Export=1"><?php echo gettext('Exporter_resultats'); ?></a>
                </p>

                <?php include 'stat_visites.inc.php';

                 // vérification de l'unicité du marqueur Google Analytics
                $aMarqueurSite = array();
                foreach ($aSites as $site) {
                    preg_match('/UA-\d{8}-\d{1}/', $site['SIT_GA_TAG'], $marqueur);
                    if (!empty($marqueur[0])) {
                        // on compose un tableau avec
                        //     - le marqueur pour clé
                        //     - un sous tableau contenant les sites utilisant ce marqueur
                        $aMarqueurSite[$marqueur[0]][] =  $site['SIT_LIBELLE'];
                    }
                }

                if (!empty($aMarqueurSite)) {
                    foreach ($aMarqueurSite as $marqueur => $aSites) {
                        // si on a plus d'un site pour le marqueur courant
                        if (count($aSites) > 1) { ?>
                        <p class="aligncenter note">
                            <?php echo  sprintf(gettext('meme_marqueur_%s'), implode(', ', $aSites)) ?>
                        </p>
                    <?php }
                    }
                }

            } else { ?>

                <p class="aligncenter">
                    <?php echo gettext('pas_de_compte_et_marqueur'); ?>
                </p>

            <?php } ?>

        </div>
    </div>
    <?php include('../include/inc.bo_bandeau_bas.php')?>
</div>
</body>
</html>
