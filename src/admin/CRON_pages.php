<?php
require_once dirname(__FILE__) . '/../www/include/config.php';
require_once CLASS_DIR . 'class.DB.php';
require_once CLASS_DIR . 'class.db_page.php';
require_once CLASS_DIR . 'class.db_paragraphe.php';
require_once dirname(__FILE__) . '/../www/include/lib.workflow.php';

echo "\n****\n";
echo "PAGE\n";
echo "****\n";

echo "Gestion de la mise en ligne et hors ligne des pages\n";

// on va chercher les cronables
$dbh = DB::getInstance();
$sql = "select * from WORKFLOW where WKF_CRONABLE=1";
foreach ($dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
    switch ($row['PST_CODE_OUT']) {
        case 'PST_ENLIGNE':
            $sql = "select * from OFF_PAGE where PST_CODE= " . $dbh->quote($row['PST_CODE_IN']) . " ORDER BY PAG_IDPERE asc, ID_PAGE ASC";
            break;
        case 'PST_HORSLIGNE':
            $sql = "select * from OFF_PAGE where PST_CODE= " . $dbh->quote($row['PST_CODE_IN']) . " ORDER BY PAG_IDPERE desc, ID_PAGE DESC";
            break;
        default:
            $sql = "select * from OFF_PAGE where PST_CODE= " . $dbh->quote($row['PST_CODE_IN']);
            break;
    }
    $cpt = 0;
    foreach ($dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $rowPage) {
        $oPage = new Page($rowPage['ID_PAGE']);
        $oPage->setFields($rowPage);
        if (call_user_func($row['WKF_POST_FONCTION'], $oPage, $row['PST_CODE_OUT'])) {
            $cpt ++;
            $oPage->historize('MODIFICATION', 'WORKFLOW', $row['PST_CODE_OUT']);
        }
    }
    echo "\t" . $row['PST_CODE_IN'] . ' - ' . $row['PST_CODE_OUT'] . ' : ' . $cpt . "\n";
}

echo "Remise à parser des paragraphes ON_ et OFF_\n";
$sql = 'update OFF_PARAGRAPHE set PAR_APARSER=1';
$dbh->exec($sql);
$sql = 'update ON_PARAGRAPHE set PAR_APARSER=1';
$dbh->exec($sql);

echo "Purge du cache de l'ensemble des sites\n";
Page::clearAllCache();
