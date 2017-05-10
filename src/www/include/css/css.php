<?php
require '../config.php';
require CLASS_DIR . 'class.DB.php';
require '../lib.common.php';
header("Content-type: text/css; charset=utf-8");
$dbh = DB::getInstance();

//dans l'éditeur HTML ?
if (!empty($_GET['editor'])) {
    $sql = "select GBS_EDITOR_PATH from DD_GABARITSTYLE where GBS_CODE = " . $dbh->quote($_GET['editor']);
    if ($GBS_EDITOR_PATH = $dbh->query($sql)->fetch(PDO::FETCH_COLUMN)) {
        include(PHYSICAL_PATH . $GBS_EDITOR_PATH);
    }
}

//style perso ?
if (!empty($_GET['idtf'])) {
    $sql = "select STY_CSS from STYLEDYNAMIQUE where ID_STYLEDYNAMIQUE = " . intval($_GET['idtf']);
    if ($STY_CSS = $dbh->query($sql)->fetch(PDO::FETCH_COLUMN)) {
        echo $STY_CSS;
    }
}
