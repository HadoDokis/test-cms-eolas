<?php
require 'inc.fo_init.php';
require CLASS_DIR . 'class.db_webotheque.php';
require CLASS_DIR . 'class.File_management.php';

$WBT_CODE = empty($_GET['WBT_CODE']) ? 'WBT_DOCUMENT' : $_GET['WBT_CODE'];
$UPLOAD_PHYSIQUE = Webotheque::getUploadPhysicalDir($WBT_CODE);

$sql = "select * from WEBOTHEQUE where WBT_CODE= ".$dbh->quote($WBT_CODE)." and ID_WEBOTHEQUE = " . intval($_GET['idtf']) . "  and WEB_CHEMIN like" . $dbh->quote('%' . $_GET['path']);
$sqlAcc = "select * from WEBOTHEQUE where WBT_CODE= ".$dbh->quote($WBT_CODE)." and ID_WEBOTHEQUE = " . intval($_GET['idtf']) . "  and WEB_CHEMINACC like " . $dbh->quote('%' . $_GET['path']);

if ($row = $dbh->query($sql)->fetch(PDO::FETCH_ASSOC)) {
    ob_end_clean();
    header('Pragma: public');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Content-length: ' . filesize($UPLOAD_PHYSIQUE . $row['WEB_CHEMIN']));
    header('Content-disposition: attachment; filename="' . filenameToRfc1738($row['WEB_LIBELLE']) . '.' . end(explode('.', $row['WEB_CHEMIN'])) . '"');
    header('Content-type: ' . File_management::mimeType($UPLOAD_PHYSIQUE . $row['WEB_CHEMIN']));
    //Ouverture d'un flux en lecture sur le fichier à afficher
    if ($handle = fopen($UPLOAD_PHYSIQUE . $row['WEB_CHEMIN'], "r")) {
        //Lecture par morceau du fichier par bloc de 8192 octets (8Ko)
        while (!feof($handle)) {
            echo fread($handle, 8192);
            flush();
            ob_flush(); //Le flush n'est pas toujours pris en compte donc on force celui-ci(Cf doc PHP)
        }
        fclose($handle);
    }
} elseif ($row = $dbh->query($sqlAcc)->fetch(PDO::FETCH_ASSOC)) {
    ob_end_clean();
    header('Pragma: public');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Content-length: ' . filesize($UPLOAD_PHYSIQUE . $row['WEB_CHEMINACC']));
    header('Content-disposition: attachment; filename="' . filenameToRfc1738($row['WEB_LIBELLE']) . '.' . end(explode('.', $row['WEB_CHEMINACC'])) . '"');
    header('Content-type: ' . File_management::mimeType($UPLOAD_PHYSIQUE . $row['WEB_CHEMINACC']));
    //Ouverture d'un flux en lecture sur le fichier à afficher
    if ($handle = fopen($UPLOAD_PHYSIQUE . $row['WEB_CHEMINACC'], "r")) {
        //Lecture par morceau du fichier par bloc de 8192 octets (8Ko)
        while (!feof($handle)) {
            echo fread($handle, 8192);
            flush();
            ob_flush(); //Le flush n'est pas toujours pris en compte donc on force celui-ci(Cf doc PHP)
        }
        fclose($handle);
    }
} else {
    if (!$oPageSpeciale = CMS::getCurrentSite()->getSpecialePage('PGS_404')) {
        $oPageSpeciale = CMS::getCurrentSite()->getHomePage() ;
    }
    $oPageSpeciale->redirect();
}
