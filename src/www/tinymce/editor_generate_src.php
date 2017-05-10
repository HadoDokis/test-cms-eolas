<?php
require '../include/inc.bo_init.php';
require CLASS_DIR . 'class.db_webotheque.php';
require CLASS_DIR . 'class.File_management.php';

$oWebotheque = new Webo_IMAGE($_GET['idtf']);
if (!$oWebotheque->checkAuthorized(false)) {
    $oWebotheque->checkShareAuthorized();
}
if ($src = $oWebotheque->getSRC($_GET['format'])) {
    $src = realpath(PHYSICAL_PATH . $src);
    ob_end_clean();
    header('Content-type: ' . File_management::mimeType($src));
    //Ouverture d'un flux en lecture sur le fichier à afficher
    if ($handle = fopen($src, 'r')) {
        //Lecture par morceau du fichier par bloc de 8192 octets (8Ko)
        while (!feof($handle)) {
            echo fread($handle, 8192);
            flush();
            ob_flush(); //Le flush n'est pas toujours pris en compte donc on force celui-ci(Cf doc PHP)
        }
        fclose($handle);
    }
}
