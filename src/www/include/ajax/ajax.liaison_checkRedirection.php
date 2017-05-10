<?php
require '../inc.bo_init.php';
Utilisateur::checkConnected();
$dbh = DB::getInstance();
$from = $_GET['from'];
$to = $_GET['to'];
$aIdPages = array();
$aIdPages[] = intval($from);

$sql = "select ID_PAGE_REDIRECT from OFF_PAGE where ID_PAGE = ".intval($to);
$idRedirection = $dbh->query($sql)->fetchColumn();
$isPermitted = true;

//variable afin de limiter le nombre d'iteration - limit donc a 10 redirections / iterations
$maxRedirections = 10;
$countRed = 0;
while (!is_null($idRedirection) && intval($idRedirection) > 0) {
    if (in_array($idRedirection, $aIdPages) || $countRed > $maxRedirections) {
        $isPermitted = false;
        break;
    } else {
        $aIdPages[] = $idRedirection;
    }

    $sql = "select ID_PAGE_REDIRECT from OFF_PAGE where ID_PAGE = ".intval($idRedirection);
    $idRedirection = $dbh->query($sql)->fetchColumn();
    $countRed++;
}

echo ($isPermitted)? 1 : 0;
