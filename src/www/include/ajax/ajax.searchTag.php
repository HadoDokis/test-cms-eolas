<?php
require '../inc.bo_init.php';
Utilisateur::checkConnected();
$aSQL = array ();
for ($i = 1; $i <= 5; $i++) {
    $aSQL[] = "select distinct PAG_MOTCLE" . $i . " as PAG_MOTCLE from OFF_PAGE where PAG_MOTCLE" . $i . " like " . $dbh->quote($_GET['term'] . '%');
}
$sql = implode(' union distinct ', $aSQL);
$aRow = $dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN);
sort($aRow);
$aJSON = array();
foreach ($aRow as $PAG_MOTCLE) {
    $aJSON[]['value'] = $PAG_MOTCLE;
}
echo json_encode($aJSON);
