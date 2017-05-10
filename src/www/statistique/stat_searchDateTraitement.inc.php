<?php
$byMonth = true;
$param = "";
$classTabSelectMonth = "selected";
$classTabSelectYear = "";

if (isset($_GET['submitCurrentMonth']) && !empty($_GET['submitCurrentMonth'])) {
    $_GET['selectMonth'] = date('m');
    $_GET['selectYear'] = date('Y');
    $_GET['submitMonth'] = 'Ok';
} else if (isset($_GET['submitCurrentYear']) && !empty($_GET['submitCurrentYear'])) {
    $_GET['selectYear'] = date('Y');
    $_GET['submitYear'] = 'Ok';
}

if (isset($_GET['submitMonth']) && !empty($_GET['submitMonth'])
    && valideDate('01/'.$_GET['selectMonth'].'/'.$_GET['selectYear'], 'fr_FR')) {
    $month = strlen($_GET['selectMonth']) > 1 ? $_GET['selectMonth'] :  '0'.$_GET['selectMonth'];
    $year = $_GET['selectYear'];
    $dateDebut = mktime(0,0,0,$month, 1, $year);
    $dateFin = strtotime("next month",$dateDebut);
    $param = "&amp;submitMonth=ok&amp;selectMonth=" . secureInput($_GET['selectMonth']) . "&amp;selectYear=" . secureInput($_GET['selectYear']);
} else if (isset($_GET['submitYear']) && !empty($_GET['submitYear'])
    && is_numeric($_GET['selectYear']) && strlen($_GET['selectYear']) == 4) {
    $year = $_GET['selectYear'];
    $month = 1;
    $dateDebut = mktime(0,0,0,1, 1, $year);
    $dateFin = strtotime("next year",$dateDebut);
    $byMonth = false;
    $param = "&amp;submitYear=ok&amp;selectYear=" . secureInput($_GET['selectYear']);
    $classTabSelectMonth = "";
    $classTabSelectYear = "selected";
} else {
    // Si pas de mois sélectionné, on part de la veille (pour éviter d'avoir aucun résultat un 1er de mois)
    $dateLastDay =  strtotime("-1 day", mktime(0,0,0));
    $year = date('Y', $dateLastDay);
    $_GET['selectYear'] = date('Y');
    $month = $_GET['selectMonth'] = date('m', $dateLastDay);
    $dateDebut = mktime(0,0,0,$month, 1, $year);
    $dateFin = strtotime("next month",$dateDebut);
    if (isset($_GET['submitDJYear']) && !empty($_GET['submitDJYear'])) {
        $_GET['submitMonth'] = '';
        $_GET['selectYear'] = $year;
        $_GET['submitYear'] = 'Ok';
        $byMonth = false;
        $dateDebut = mktime(0,0,0,1, 1, $year);
        $dateFin = strtotime("next year",$dateDebut);
        $classTabSelectMonth = "";
        $classTabSelectYear = "selected";
    }
}

$aDataDate = array();
if ($byMonth) {
    $i = $dateDebut;
    while ($i <= $dateFin) {
        $aDataDate[str_replace(' ', '0', strftime('%e', $i))] = 0;
        $i = strtotime("+1 day", $i);
    }
} else {
    for ($i=1; $i<13; $i++) {
        $aDataDate[strftime('%B', mktime(0,0,0,$i,15))] = 0;
    }
}
// Après avoir initialisé les différents tableaux nécessaires aux graphiques,
// nous réinitialions la date de fin à la date du jour (borne non incluse)
// si elle est, initialement, supérieure (info cohérante affichée dans l'export et les popup détaillées)
$dateFin = ($dateFin > mktime(0,0,0))?mktime(0,0,0):$dateFin;
