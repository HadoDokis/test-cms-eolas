<?php
session_start(); // Activation des sessions
//Pour chacune des requêtes stockées dans la session, on remplit un tableau permettant de comptabiliser chacune des requêtes
foreach ($_SESSION['DEBUG_INFO']['DB'] as $query => $info) {
    $nbIteration[$query]  = count($info);
}
//Tri des informations par nombre de requetes décroissantes s'il y a des requêtes présentes
if ($nbIteration) {
    array_multisort($nbIteration, SORT_NUMERIC, SORT_DESC, $_SESSION['DEBUG_INFO']['DB']);
}
//Génération du tableau d'affichage
echo '
    <table>
    <tr>
        <th style="width:5%;text-align:center">Nb</th>
        <th style="width:5%;text-align:center">Temps</th>
        <th style="width:60%">Requête</th>
        <th style="width:30%">Appels</th>
    </tr>';
$queryCount = 0;
foreach ($_SESSION['DEBUG_INFO']['DB'] as $key=>$val) {
    $times =0;
    $s = '<hr>';
    foreach ($val as $iter) {
        $times += $iter['DURATION'];
        $s .= $iter['FILE'] . '<hr>';
    }
    echo '<tr>';
    echo '<td style="text-align:center;">'.count($val).'</td>';
    echo '<td style="text-align:center;"> '.round($times*1000,2).'ms</td>';
    echo '<td style="">'.htmlspecialchars($key, ENT_QUOTES, 'UTF-8').'</td>';
    echo '<td>'.$s.'</td>';
    echo '</tr>';
    $queryCount += count($val);
}
echo '</table><script>$("#cmsToggleRequests").html(\''.$queryCount.'\');</script>';
$_SESSION['DEBUG_INFO']['DB'] = array();
