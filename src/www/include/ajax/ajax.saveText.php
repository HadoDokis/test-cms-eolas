<?php
require '../inc.bo_init.php';
Utilisateur::checkConnected();
$dbh = DB::getInstance();

$sqlUpdate = "update LIAISON_".$_POST['type']." set LIA_TEXT = ".$dbh->quote($_POST['text'])." where ID_LIAISON_".$_POST['type']." = ".intval($_POST['idtf']);
$dbh->exec($sqlUpdate);
return 1;
