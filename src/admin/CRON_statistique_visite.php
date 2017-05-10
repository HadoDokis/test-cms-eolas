<?php
require_once dirname(__FILE__) . '/../www/include/config.php';
require_once dirname(__FILE__) . '/../www/include/config_module_admin.php';
require_once CLASS_DIR . 'class.DB.php';
require_once CLASS_DIR . 'class.GoogleAnalytics.php';

echo "\n***************\n";
echo "Statistiques FO\n";
echo "***************\n";

$dbh = DB::getInstance();
$outOfDate = mktime(0, 0, 0, date('m') - 2, date('d'));

echo "Suppression des données antérieures au " . date('d/m/Y', $outOfDate) . "\n";
$sql = 'delete from STAT_GA_DETAIL where GAD_DATE < ' . $outOfDate;
$dbh->exec($sql);

echo "Récuperation des mediums\n";
$sql = 'select GAM_CODE from STAT_GA_MEDIUM';
$aMedium = $dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN);

$sql = "select SIT_CODE, SIT_LIBELLE, SIT_GA_ID, SIT_GA_KEYFILE, SIT_GA_ID_SITE from DD_SITE
    where SIT_GA_ID<>'' and SIT_GA_KEYFILE<>'' and SIT_GA_ID_SITE is not null";
$aSite = $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
foreach ($aSite as $row) {
    echo "Traitements pour " . $row['SIT_CODE'] . "\n";
    try {
        $ga = new gapi($row['SIT_GA_ID'], UPLOAD_EXTERNE_PHYSIQUE . $row['SIT_GA_KEYFILE']);
    } catch (Exception $e) {
        echo "Erreur : " . $e->getMessage() . "\n";
        if (defined('MAIL_REPORT_TO')) {
            mail(implode(',', unserialize(MAIL_REPORT_TO)), 'CRON statistiques de visites GA : site ' . $row['SIT_LIBELLE'], $e->getMessage() . '<br>' . $e->getTraceAsString());
        }
        continue;
    }
    // set the date range we want for the report - format is YYYY-MM-DD
    $time = strtotime('yesterday');
    $yesterday = date('Y-m-d', $time);

    echo "\tRécuperation rapport par indicateur\n";
    try {
        $ga->requestReportData($row['SIT_GA_ID_SITE'], array(
            'medium'
        ), array(
            'visits',
            'pageviews',
            'timeOnSite',
            'newVisits',
            'bounces',
            'entrances'
        ), array(
            'medium'
        ), null, $yesterday, $yesterday);
        $aReport = $ga->getResults();
    } catch (Exception $e) {
        echo "Erreur : " . $e->getMessage() . "\n";
        if (defined('MAIL_REPORT_TO')) {
            mail(implode(',', unserialize(MAIL_REPORT_TO)), 'CRON statistiques de visites GA : site ' . $row['SIT_LIBELLE'], $e->getMessage() . '<br>' . $e->getTraceAsString());
        }
        continue;
    }
    if (is_array($aReport) && ! empty($aReport)) {
        foreach ($aReport as $rowDetail) {
            if (in_array($rowDetail->getMedium(), $aMedium)) {
                $sql = 'insert into STAT_GA_DETAIL (GAM_CODE, SIT_CODE, GAD_DATE, GAD_VISITS, GAD_PAGEVIEWS, GAD_TIMEONSITE, GAD_NEWVISITS, GAD_BOUNCES, GAD_ENTRANCES)
                    values (' . $dbh->quote($rowDetail->getMedium()) . ',
                            ' . $dbh->quote($row['SIT_CODE']) . ',
                            ' . $time . ',
                            ' . $dbh->quote($rowDetail->getVisits()) . ',
                            ' . $dbh->quote($rowDetail->getPageviews()) . ',
                            ' . $dbh->quote($rowDetail->getTimeOnSite()) . ',
                            ' . $dbh->quote($rowDetail->getNewVisits()) . ',
                            ' . $dbh->quote($rowDetail->getBounces()) . ',
                            ' . $dbh->quote($rowDetail->getEntrances()) . ')';
                try {
                    $dbh->exec($sql);
                } catch (Exception $e) {
                    echo "Erreur : " . $e->getMessage() . "\n";
                }
            } else {
                echo "Medium inconnu : " . $rowDetail->getMedium() . "\n";
            }
        }
    } else {
        echo "Pas de données à intégrer\n";
    }
}
