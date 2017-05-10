<?php
require_once dirname(__FILE__) . '/../www/include/config.php';
require_once CLASS_DIR . 'class.DB.php';
require_once CLASS_DIR . 'class.db_webotheque.php';

echo "\n***************\n";
echo "Statistiques BO\n";
echo "***************\n";

$dbh = DB::getInstance();
$dateLastDay =  strtotime("-1 day", mktime(0,0,0));

$day     = date('d',$dateLastDay);
$HIC_MONTH   = date('m',$dateLastDay);
$HIC_YEAR    = date('Y',$dateLastDay);
$HIC_DATE = $HIC_MONTH.'/'.$HIC_YEAR;

$HID_JOUR = 'HID_JOUR'.$day;

$sql = "select count(HIC_DATE) from HISTORIQUE_CALENDRIER where HIC_DATE = " . $dbh->quote($HIC_DATE);
if ($dbh->query($sql)->fetchColumn() == 0) {
    $stmt = $dbh->prepare("insert into HISTORIQUE_CALENDRIER (
        HIC_DATE,
        HIC_MONTH,
        HIC_YEAR
        ) values(
        :HIC_DATE,
        :HIC_MONTH,
        :HIC_YEAR
        )");

    $stmt->bindValue(':HIC_DATE', $HIC_DATE, PDO::PARAM_STR);
    $stmt->bindValue(':HIC_MONTH', $HIC_MONTH, PDO::PARAM_STR);
    $stmt->bindValue(':HIC_YEAR', $HIC_YEAR, PDO::PARAM_STR);
    $stmt->execute();
}

$sqlSite = "select distinct SIT_CODE from DD_SITE";
foreach ($dbh->query($sqlSite)->fetchAll(PDO::FETCH_COLUMN) as $SIT_CODE) {
    echo "Traitements pour " . $SIT_CODE . "\n";
    agregeHistoriqueUtilisateur($SIT_CODE, $dateLastDay);
    agregeHistoriquePage($SIT_CODE, $dateLastDay);
    agregeHistoriqueWebotheque($SIT_CODE, $dateLastDay);
    agregeHistoriqueFormulaire($SIT_CODE, $dateLastDay);
    agregeHistoriqueModule($SIT_CODE, $dateLastDay);
    agregeHistoriqueAdmin($SIT_CODE, $dateLastDay);

}

/*********************************************************************************************************/
/* MISE A JOUR DES UTILISATEURS
/*********************************************************************************************************/
function agregeHistoriqueUtilisateur($SIT_CODE, $dateLastDay) {
    $dbh = DB::getInstance();
    $dateLastDay = intval($dateLastDay);
    $day     = date('d',$dateLastDay);
    $HIC_MONTH   = date('m',$dateLastDay);
    $HIC_YEAR    = date('Y',$dateLastDay);
    $HIC_DATE = $HIC_MONTH.'/'.$HIC_YEAR;
    $HID_JOUR = 'HID_JOUR'.$day;
    # Sélection de l'ensemble des utilisateurs ayant un role sur le site au jour J
    # et (union)
    # de l'ensemble des utilisateurs "historisé" sur le site durant toute la période (utilisateurs ayant pu être supprimés ou n'ayant plus de role sur le site au jour J de la période)
    $sql = "(select
                distinct u.ID_UTILISATEUR as IDENTIFIANT,
                trim(concat_ws(' ', UTI_PRENOM, UTI_NOM)) as INFO
            from UTILISATEUR u
            inner join ROLE on (u.ID_UTILISATEUR = ROLE.ID_UTILISATEUR and (ROLE.SIT_CODE = " . $dbh->quote($SIT_CODE) . " or PRO_CODE='PRO_ROOT') )
        ) UNION (
            select
                distinct historique.ID_UTILISATEUR as IDENTIFIANT,
                trim(concat_ws(' ', UTI_PRENOM, UTI_NOM, HIS_UTILISATEUR)) as INFO
            from HISTORIQUE_UTILISATEUR historique
            left join UTILISATEUR u on (u.ID_UTILISATEUR = historique.ID_UTILISATEUR)
            where historique.SIT_CODE = " . $dbh->quote($SIT_CODE) . "
                and historique.HIS_DATE >= " . strtotime("first day of ",$dateLastDay) . " and historique.HIS_DATE < " . (strtotime("+1 day", $dateLastDay)) . "
        )";

    if ($rows = $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC)) {
        foreach ($rows as $row) {
            $nbAction = $nbConnexion = 0;
            if ($rowPeriode = getHistoriquePeriode($SIT_CODE, $HIC_DATE, 'UTILISATEUR', $row['IDENTIFIANT'],$row['INFO'], $HID_JOUR)) {
                $aDay = array(
                    'ACTION'	=> 0,
                    'CONNEXION' => 0
                );
                $sqlPlus = "select
                              count(ID_HISTORIQUE_UTILISATEUR) as CONNEXION,
                              sum(HIS_NBACTION) as ACTION,
                              max(HIS_DATE) as DATE
                          from HISTORIQUE_UTILISATEUR
                          where SIT_CODE = " . $dbh->quote($SIT_CODE) . "
                                and HIS_DATE >= " . $dateLastDay . "
                                and HIS_DATE < " . (strtotime("+1 day", $dateLastDay)) . "
                                and ID_UTILISATEUR = ". $row['IDENTIFIANT'];
               if ($rowPlus = $dbh->query($sqlPlus)->fetch(PDO::FETCH_ASSOC)) {
                    $aDay['ACTION'] = intval($aDay['ACTION'])+$rowPlus['ACTION'];
                    $nbAction = $nbAction + $rowPlus['ACTION'];

                    $aDay['CONNEXION'] = intval($aDay['CONNEXION'])+$rowPlus['CONNEXION'];
                    $nbConnexion = $nbConnexion + $rowPlus['CONNEXION'];

                   $lastModification = $rowPlus['DATE'];
                }
                majHistoriqueInfoLibelle($rowPeriode['ID_HISTORIQUE_PERIODE'], $row['INFO']);
                majHistorique($rowPeriode['ID_HISTORIQUE_PERIODE'], $HID_JOUR, $aDay, $lastModification);

                //* Si le cron n'a pas tourné les jours précédants, on lance un rattrapage...
                $success = true;
                $prevDate = strtotime("-1 day", $dateLastDay);
                # Attention, si le cron de la veille (dernier jour d'un mois) n'a pas tourné, ce test n'est pas bon...
                while ($success && (date('m', $prevDate) == date('m',$dateLastDay))) {
                    $HID_JOUR_PREV = 'HID_JOUR'.date("d",$prevDate);
                    if (!empty($rowPeriode[$HID_JOUR_PREV])) {
                        $success = false;
                    } else {
                        $aDayPrev = array(
                            'ACTION'	=> 0,
                            'CONNEXION' => 0
                        );
                        $sql = "select
                              count(ID_HISTORIQUE_UTILISATEUR) as CONNEXION,
                              sum(HIS_NBACTION) as ACTION,
                              max(HIS_DATE) as DATE
                          from HISTORIQUE_UTILISATEUR
                          where SIT_CODE = " . $dbh->quote($SIT_CODE) . "
                                and HIS_DATE >= " . $prevDate . "
                                and HIS_DATE < " . (strtotime("+1 day", $prevDate)) . "
                                and ID_UTILISATEUR = ". $row['IDENTIFIANT'];
                        if ($rowPlus = $dbh->query($sql)->fetch(PDO::FETCH_ASSOC)) {
                            $aDayPrev = array(
                                'ACTION'	=> intval($rowPlus['ACTION']),
                                'CONNEXION' => intval($rowPlus['CONNEXION'])
                            );
                            $nbAction += intval($rowPlus['ACTION']);
                            $nbConnexion += intval($rowPlus['CONNEXION']);
                            if ($lastModification < $rowPlus['DATE']) {
                                $lastModification = $rowPlus['DATE'];
                            }
                        }
                        majHistorique($rowPeriode['ID_HISTORIQUE_PERIODE'], $HID_JOUR_PREV, $aDayPrev, $lastModification); #$lastModification pas bon
                        $prevDate = strtotime("-1 day", $prevDate);
                    }
                }
                //*/
                // On met à jour le total des actions
                $sql =  'update HISTORIQUE_PERIODE set
                        HID_TOTALACTION = HID_TOTALACTION+' . intval($nbAction) . ',
                        HID_TOTALCONNEXION = HID_TOTALCONNEXION+' . intval($nbConnexion) . '
                    where ID_HISTORIQUE_PERIODE = ' . intval($rowPeriode['ID_HISTORIQUE_PERIODE']);
                $dbh->exec($sql);
            }
        }
    }
}
/*********************************************************************************************************/
/* MISE A JOUR DES PAGES
/*********************************************************************************************************/
function agregeHistoriquePage($SIT_CODE, $dateLastDay) {
    $dbh = DB::getInstance();
    $dateLastDay = intval($dateLastDay);
    $day     = date('d',$dateLastDay);
    $HIC_MONTH   = date('m',$dateLastDay);
    $HIC_YEAR    = date('Y',$dateLastDay);
    $HIC_DATE = $HIC_MONTH.'/'.$HIC_YEAR;
    $HID_JOUR = 'HID_JOUR'.$day;
    # Sélection de l'ensemble des pages sur le site au jour J
    # et (union)
    # de l'ensemble des pages "historisé" sur le site durant toute la période (pages ayant pu être supprimées du site au cours de la période)
    $sql = "(select
                ID_PAGE as IDENTIFIANT,
                trim(PAG_TITRE) as INFO
            from OFF_PAGE
            where SIT_CODE = " . $dbh->quote($SIT_CODE) . "
        ) union (
            select
                distinct historique.ID_PAGE as IDENTIFIANT,
                trim(concat_ws(' ', PAG_TITRE, HIS_INFO)) as INFO
            from HISTORIQUE_PAGE historique
            left join OFF_PAGE p on (p.ID_PAGE = historique.ID_PAGE)
            where (historique.SIT_CODE = " . $dbh->quote($SIT_CODE) . ")
                and historique.HIS_DATE >= " . strtotime("first day of ",$dateLastDay) . " and historique.HIS_DATE < " . (strtotime("+1 day", $dateLastDay)) . "
        )";

    if ($rows = $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC)) {
        foreach ($rows as $row) {
            $nbAction = $lastModification = $nbMiseEnLigne = $nbMiseAValider = $nbMiseHorsLigne = 0;
            if ($rowPeriode = getHistoriquePeriode($SIT_CODE, $HIC_DATE, 'PAGE', $row['IDENTIFIANT'],$row['INFO'], $HID_JOUR)) {
                $aDay = array(
                    'ACTION'	=> 0,
                    'MISEENLIGNE' => 0
                );
                $sqlPlus = "select *
                          from HISTORIQUE_PAGE
                          where SIT_CODE = " . $dbh->quote($SIT_CODE) . "
                                and HIS_DATE >= " . $dateLastDay . "
                                and HIS_DATE < " . (strtotime("+1 day", $dateLastDay)) . "
                                and ID_PAGE = ". $row['IDENTIFIANT'];
                if ($rowsPlus = $dbh->query($sqlPlus)) {
                    foreach ($rowsPlus as $rowPlus) {
                        $aDay['ACTION'] = intval($aDay['ACTION'])+1;
                        $nbAction++;
                        if ($rowPlus['HIS_TYPE'] == 'WORKFLOW') {
                            if ($rowPlus['HIS_DETAIL'] == 'PST_ENLIGNE') {
                                $aDay['MISEENLIGNE'] = intval($aDay['MISEENLIGNE'])+1;
                                $nbMiseEnLigne++;
                            } else if ($rowPlus['HIS_DETAIL'] == 'PST_AVALIDER') {
                                $nbMiseAValider++;
                            } else if ($rowPlus['HIS_DETAIL'] == 'PST_HORSLIGNE') {
                                $nbMiseHorsLigne++;
                            }
                        }
                        if ($rowPlus['HIS_DATE'] > $lastModification) {
                            $lastModification = $rowPlus['HIS_DATE'];
                        }
                    }
                }
                majHistoriqueInfoLibelle($rowPeriode['ID_HISTORIQUE_PERIODE'], $row['INFO']);
                majHistorique($rowPeriode['ID_HISTORIQUE_PERIODE'], $HID_JOUR, $aDay, $lastModification);

                //* Si le cron n'a pas tourné les jours précédants, on lance un rattrapage...
                $success = true;
                $prevDate = strtotime("-1 day", $dateLastDay);
                while ($success && (date('m', $prevDate) == date('m',$dateLastDay))) {
                    $HID_JOUR_PREV = 'HID_JOUR'.date("d",$prevDate);
                    if (!empty($rowPeriode[$HID_JOUR_PREV])) {
                        $success = false;
                    } else {
                        $aDayPrev = array(
                            'ACTION'	=> 0,
                            'MISEENLIGNE' => 0
                        );
                        $sqlPlus = "select *
                            from HISTORIQUE_PAGE
                            where SIT_CODE = " . $dbh->quote($SIT_CODE) . "
                                and HIS_DATE >= " . $prevDate . "
                                and HIS_DATE < " . (strtotime("+1 day", $prevDate)) . "
                                and ID_PAGE = ". $row['IDENTIFIANT'];
                        if ($rowsPlus = $dbh->query($sqlPlus)) {
                            foreach ($rowsPlus as $rowPlus) {
                                $aDayPrev['ACTION'] = intval($aDayPrev['ACTION'])+1;
                                $nbAction++;
                                if ($rowPlus['HIS_TYPE'] == 'WORKFLOW') {
                                    if ($rowPlus['HIS_DETAIL'] == 'PST_ENLIGNE') {
                                        $aDayPrev['MISEENLIGNE'] = intval($aDayPrev['MISEENLIGNE'])+1;
                                        $nbMiseEnLigne++;
                                    } else if ($rowPlus['HIS_DETAIL'] == 'PST_AVALIDER') {
                                        $nbMiseAValider++;
                                    } else if ($rowPlus['HIS_DETAIL'] == 'PST_HORSLIGNE') {
                                        $nbMiseHorsLigne++;
                                    }
                                }
                                if ($lastModification < $rowPlus['HIS_DATE']) {
                                    $lastModification = $rowPlus['HIS_DATE'];
                                }
                            }
                        }
                        majHistorique($rowPeriode['ID_HISTORIQUE_PERIODE'], $HID_JOUR_PREV, $aDayPrev, $lastModification);
                        $prevDate = strtotime("-1 day", $prevDate);
                    }
                }
                //*/
                // On met à jour les totaux des actions
                $sql =  'update HISTORIQUE_PERIODE set
                        HID_TOTALACTION = HID_TOTALACTION+' . intval($nbAction) . ',
                        HID_TOTALENLIGNE = HID_TOTALENLIGNE+' . intval($nbMiseEnLigne) . ',
                        HID_TOTALAVALIDER = HID_TOTALAVALIDER+' . intval($nbMiseAValider) . ',
                        HID_TOTALHORSLIGNE = HID_TOTALHORSLIGNE+' . intval($nbMiseHorsLigne) . '
                        where ID_HISTORIQUE_PERIODE = ' . intval($rowPeriode['ID_HISTORIQUE_PERIODE']);
                $dbh->exec($sql);
            }
        }
    }
}

/*********************************************************************************************************/
/* MISE A JOUR DES ELEMENTS DE LA WEBOTHEQUE
/*********************************************************************************************************/
function agregeHistoriqueWebotheque($SIT_CODE, $dateLastDay) {
    $dbh = DB::getInstance();
    $dateLastDay = intval($dateLastDay);
    $day     = date('d',$dateLastDay);
    $HIC_MONTH   = date('m',$dateLastDay);
    $HIC_YEAR    = date('Y',$dateLastDay);
    $HIC_DATE = $HIC_MONTH.'/'.$HIC_YEAR;
    $HID_JOUR = 'HID_JOUR'.$day;
    # @TODO : traiter l'activation ou désactivation des modules
    $sql = "select WBT_CODE as IDENTIFIANT
            from DD_WEBOTHEQUETYPE";
    if ($rows = $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC)) {
        foreach ($rows as $row) {
            $nbAction = 0;
            if ($rowPeriode = getHistoriquePeriode($SIT_CODE, $HIC_DATE, 'WEBOTHEQUE', $row['IDENTIFIANT'],Webotheque::$_aTraduction[$row['IDENTIFIANT']], $HID_JOUR)) {

                $aDay = array(
                    'ACTION'	=> 0
                );
                $sqlPlus = "select
                                count(ID_HISTORIQUE_WEBOTHEQUE) as ACTION,
                                max(HIS_DATE) as DATE
                            from HISTORIQUE_WEBOTHEQUE
                            where SIT_CODE = " . $dbh->quote($SIT_CODE) . "
                                and HIS_DATE >= " . $dateLastDay . "
                                and HIS_DATE < " . (strtotime("+1 day", $dateLastDay)) . "
                                and WBT_CODE = ". $dbh->quote($row['IDENTIFIANT']);
                if ($rowPlus = $dbh->query($sqlPlus)->fetch(PDO::FETCH_ASSOC)) {
                    $aDay['ACTION'] = $nbAction = $rowPlus['ACTION'];
                    $lastModification = $rowPlus['DATE'];
                }
                majHistorique($rowPeriode['ID_HISTORIQUE_PERIODE'], $HID_JOUR, $aDay,  $lastModification);

                //* Si le cron n'a pas tourné les jours précédants, on lance un rattrapage...
                $success = true;
                $prevDate = strtotime("-1 day", $dateLastDay);
                while ($success && (date('m', $prevDate) == date('m',$dateLastDay))) {
                    $HID_JOUR_PREV = 'HID_JOUR'.date("d",$prevDate);
                    if (!empty($rowPeriode[$HID_JOUR_PREV])) {
                        $success = false;
                    } else {
                        $aDayPrev = array(
                            'ACTION' => 0
                        );
                        $sqlPlus = "select
                                        count(ID_HISTORIQUE_WEBOTHEQUE) as NB_ACTION,
                                        max(HIS_DATE) as DATE
                                    from HISTORIQUE_WEBOTHEQUE historique
                                    where SIT_CODE = " . $dbh->quote($SIT_CODE) . "
                                        and HIS_DATE >= " . $prevDate . "
                                        and HIS_DATE < " . (strtotime("+1 day", $prevDate)) . "
                                        and WBT_CODE = ". $dbh->quote($row['IDENTIFIANT']);
                        if ($rowPlus = $dbh->query($sqlPlus)->fetch(PDO::FETCH_ASSOC)) {
                            $aDayPrev = array(
                                'ACTION'	=> intval($rowPlus['NB_ACTION'])
                            );
                            $nbAction += intval($rowPlus['NB_ACTION']);
                            if ($lastModification < $rowPlus['DATE']) {
                                $lastModification = $rowPlus['DATE'];
                            }
                        }
                        majHistorique($rowPeriode['ID_HISTORIQUE_PERIODE'], $HID_JOUR_PREV, $aDayPrev, $lastModification);
                        $prevDate = strtotime("-1 day", $prevDate);
                    }
                }
                //*/
                // On met à jour les totaux des actions
                $sql =  'update HISTORIQUE_PERIODE set
                        HID_TOTALACTION = HID_TOTALACTION+' . intval($nbAction) . '
                        where ID_HISTORIQUE_PERIODE = ' . intval($rowPeriode['ID_HISTORIQUE_PERIODE']);
                $dbh->exec($sql);
            }
        }
    }
}

/*********************************************************************************************************/
/* MISE A JOUR DES FORMULAIRES DYNAMQIUES
/*********************************************************************************************************/
function agregeHistoriqueFormulaire($SIT_CODE, $dateLastDay) {
    $dbh = DB::getInstance();
    $dateLastDay = intval($dateLastDay);
    $day     = date('d',$dateLastDay);
    $HIC_MONTH   = date('m',$dateLastDay);
    $HIC_YEAR    = date('Y',$dateLastDay);
    $HIC_DATE = $HIC_MONTH.'/'.$HIC_YEAR;
    $HID_JOUR = 'HID_JOUR'.$day;

    # @TODO : traiter l'activation ou désactivation du module formulaire
    # Sélection de l'ensemble des formulaires "historisé" sur le site durant toute la période (formulaires ayant pu être supprimés au jour J de la période)
    $sql = "select
                distinct historique.ID_FORMULAIRE as IDENTIFIANT,
                trim(concat_ws(' ', FRM_LIBELLE, HIS_INFO)) as INFO
            from HISTORIQUE_FORMULAIRE historique
            left join FORMULAIRE f on(f.ID_FORMULAIRE = historique.ID_FORMULAIRE)
            where historique.SIT_CODE = " . $dbh->quote($SIT_CODE) . "
                and historique.HIS_DATE >= " . strtotime("first day of ",$dateLastDay) . " and historique.HIS_DATE < " . (strtotime("+1 day", $dateLastDay));

    if ($rows = $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC)) {
        foreach ($rows as $row) {
            $nbAction = 0;
            if ($rowPeriode = getHistoriquePeriode($SIT_CODE, $HIC_DATE, 'FORMULAIRE', $row['IDENTIFIANT'],$row['INFO'], $HID_JOUR)) {
                $aDay = array(
                    'ACTION'	=> 0
                );
                $sqlPlus = "select
                                count(ID_HISTORIQUE_FORMULAIRE) as ACTION,
                                max(HIS_DATE) as DATE
                            from HISTORIQUE_FORMULAIRE
                            where SIT_CODE = " . $dbh->quote($SIT_CODE) . "
                                and HIS_DATE >= " . $dateLastDay . "
                                and HIS_DATE < " . (strtotime("+1 day", $dateLastDay)) . "
                                and ID_FORMULAIRE = ". $row['IDENTIFIANT'];
                if ($rowPlus = $dbh->query($sqlPlus)->fetch(PDO::FETCH_ASSOC)) {
                    $aDay['ACTION'] = $nbAction = $rowPlus['ACTION'];
                    $lastModification = $rowPlus['DATE'];
                }
                majHistoriqueInfoLibelle($rowPeriode['ID_HISTORIQUE_PERIODE'], $row['INFO']);
                majHistorique($rowPeriode['ID_HISTORIQUE_PERIODE'], $HID_JOUR, $aDay,  $lastModification);

                //* Si le cron n'a pas tourné les jours précédants, on lance un rattrapage...
                $success = true;
                $prevDate = strtotime("-1 day", $dateLastDay);
                while ($success && (date('m', $prevDate) == date('m',$dateLastDay))) {
                    $HID_JOUR_PREV = 'HID_JOUR'.date("d",$prevDate);
                    if (!empty($rowPeriode[$HID_JOUR_PREV])) {
                        $success = false;
                    } else {
                        $aDayPrev = array(
                            'ACTION'	=> 0
                        );
                        $sqlPlus = "select
                                        count(ID_HISTORIQUE_FORMULAIRE) as NB_ACTION,
                                        max(HIS_DATE) as DATE
                                    from HISTORIQUE_FORMULAIRE historique
                                    where SIT_CODE = " . $dbh->quote($SIT_CODE) . "
                                        and HIS_DATE >= " . $prevDate . "
                                        and HIS_DATE < " . (strtotime("+1 day", $prevDate)) . "
                                        and ID_FORMULAIRE = ". $row['IDENTIFIANT'];
                        if ($rowPlus = $dbh->query($sqlPlus)->fetch(PDO::FETCH_ASSOC)) {
                            $aDayPrev = array(
                                'ACTION'	=> intval($rowPlus['NB_ACTION'])
                            );
                            $nbAction += intval($rowPlus['NB_ACTION']);
                            if ($lastModification < $rowPlus['DATE']) {
                                $lastModification = $rowPlus['DATE'];
                            }
                        }
                        majHistorique($rowPeriode['ID_HISTORIQUE_PERIODE'], $HID_JOUR_PREV, $aDayPrev, $lastModification);
                        $prevDate = strtotime("-1 day", $prevDate);
                    }
                }
                //*/
                // On met à jour les totaux des actions
                $sql =  'update HISTORIQUE_PERIODE set
                        HID_TOTALACTION = HID_TOTALACTION+' . intval($nbAction) . '
                        where ID_HISTORIQUE_PERIODE = ' . intval($rowPeriode['ID_HISTORIQUE_PERIODE']);
                $dbh->exec($sql);
            }
        }
    }
}

/*********************************************************************************************************/
/* MISE A JOUR DES MODULES
/*********************************************************************************************************/
function agregeHistoriqueModule($SIT_CODE, $dateLastDay) {
    $dbh = DB::getInstance();
    $dateLastDay = intval($dateLastDay);
    $day     = date('d',$dateLastDay);
    $HIC_MONTH   = date('m',$dateLastDay);
    $HIC_YEAR    = date('Y',$dateLastDay);
    $HIC_DATE = $HIC_MONTH.'/'.$HIC_YEAR;
    $HID_JOUR = 'HID_JOUR'.$day;

    # @TODO : traiter l'activation ou désactivation des modules
    # Sélection de l'ensemble des modules "historisé" sur le site durant toute la période
    $sql = "select
                distinct historique.HEX_CODE as IDENTIFIANT,
                MOD_LIBELLE as INFO
            from HISTORIQUE_EXTERNE historique
            left join DD_HISTORIQUE_EXTERNE dhe on(dhe.HEX_CODE = historique.HEX_CODE)
            left join DD_MODULE m on (m.MOD_CODE = dhe.MOD_CODE)
                and historique.HIS_DATE >= " . strtotime("first day of ",$dateLastDay) . " and historique.HIS_DATE < " . (strtotime("+1 day", $dateLastDay));

    if ($rows = $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC)) {
        foreach ($rows as $row) {
            $nbAction = 0;
            if ($rowPeriode = getHistoriquePeriode($SIT_CODE, $HIC_DATE, 'EXTERNE', $row['IDENTIFIANT'],$row['INFO'], $HID_JOUR)) {
                $aDay = array(
                    'ACTION'	=> 0
                );
                $sqlPlus = "select
                                count(ID_HISTORIQUE_EXTERNE) as ACTION,
                                max(HIS_DATE) as DATE
                            from HISTORIQUE_EXTERNE
                            where SIT_CODE = " . $dbh->quote($SIT_CODE) . "
                                and HIS_DATE >= " . $dateLastDay . "
                                and HIS_DATE < " . (strtotime("+1 day", $dateLastDay)) . "
                                and HEX_CODE = ". $dbh->quote($row['IDENTIFIANT']);
                if ($rowPlus = $dbh->query($sqlPlus)->fetch(PDO::FETCH_ASSOC)) {
                    $aDay['ACTION'] = $nbAction = $rowPlus['ACTION'];
                    $lastModification = $rowPlus['DATE'];
                }
                majHistorique($rowPeriode['ID_HISTORIQUE_PERIODE'], $HID_JOUR, $aDay,  $lastModification);

                //* Si le cron n'a pas tourné les jours précédants, on lance un rattrapage...
                $success = true;
                $prevDate = strtotime("-1 day", $dateLastDay);
                while ($success && (date('m', $prevDate) == date('m',$dateLastDay))) {
                    $HID_JOUR_PREV = 'HID_JOUR'.date("d",$prevDate);
                    if (!empty($rowPeriode[$HID_JOUR_PREV])) {
                        $success = false;
                    } else {
                        $aDayPrev = array(
                            'ACTION' => 0
                        );
                        $sqlPlus = "select
                                        count(ID_HISTORIQUE_EXTERNE) as NB_ACTION,
                                        max(HIS_DATE) as DATE
                                    from HISTORIQUE_EXTERNE historique
                                    where SIT_CODE = " . $dbh->quote($SIT_CODE) . "
                                        and HIS_DATE >= " . $prevDate . "
                                        and HIS_DATE < " . (strtotime("+1 day", $prevDate)) . "
                                        and HEX_CODE = ". $dbh->quote($row['IDENTIFIANT']);
                      if ($rowPlus = $dbh->query($sqlPlus)->fetch(PDO::FETCH_ASSOC)) {
                            $aDayPrev = array(
                                'ACTION'	=> intval($rowPlus['NB_ACTION'])
                            );
                            $nbAction += intval($rowPlus['NB_ACTION']);
                            if ($lastModification < $rowPlus['DATE']) {
                                $lastModification = $rowPlus['DATE'];
                            }
                        }
                        majHistorique($rowPeriode['ID_HISTORIQUE_PERIODE'], $HID_JOUR_PREV, $aDayPrev, $lastModification);
                        $prevDate = strtotime("-1 day", $prevDate);
                    }
                }
                //*/
                // On met à jour les totaux des actions
                $sql =  'update HISTORIQUE_PERIODE set
                        HID_TOTALACTION = HID_TOTALACTION+' . intval($nbAction) . '
                        where ID_HISTORIQUE_PERIODE = ' . intval($rowPeriode['ID_HISTORIQUE_PERIODE']);
                $dbh->exec($sql);
            }
        }
    }
}

/*********************************************************************************************************/
/* MISE A JOUR DES ELEMENTS DE L'ADMINISTRATION
/*********************************************************************************************************/
function agregeHistoriqueAdmin($SIT_CODE, $dateLastDay) {
    $dbh = DB::getInstance();
    $dateLastDay = intval($dateLastDay);
    $day     = date('d',$dateLastDay);
    $HIC_MONTH   = date('m',$dateLastDay);
    $HIC_YEAR    = date('Y',$dateLastDay);
    $HIC_DATE = $HIC_MONTH.'/'.$HIC_YEAR;
    $HID_JOUR = 'HID_JOUR'.$day;

    # Sélection de l'ensemble des types d'historique ADMIN
    $sql = "select
                distinct historique.HIS_TYPE as IDENTIFIANT,
                HIS_TYPE as INFO
            from HISTORIQUE_ADMIN historique
            where SIT_CODE = " . $dbh->quote($SIT_CODE);

    if ($rows = $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC)) {
        foreach ($rows as $row) {
            $nbAction = 0;
            if ($rowPeriode = getHistoriquePeriode($SIT_CODE, $HIC_DATE, 'ADMIN', $row['IDENTIFIANT'],$row['INFO'], $HID_JOUR)) {
                $aDay = array(
                    'ACTION'	=> 0
                );
                $sqlPlus = "select
                                count(ID_HISTORIQUE_ADMIN) as ACTION,
                                max(HIS_DATE) as DATE
                            from HISTORIQUE_ADMIN
                            where SIT_CODE = " . $dbh->quote($SIT_CODE) . "
                                and HIS_DATE >= " . $dateLastDay . "
                                and HIS_DATE < " . (strtotime("+1 day", $dateLastDay)) . "
                                and HIS_TYPE = ". $dbh->quote($row['IDENTIFIANT']);
                if ($rowPlus = $dbh->query($sqlPlus)->fetch(PDO::FETCH_ASSOC)) {
                    $aDay['ACTION'] = $nbAction = $rowPlus['ACTION'];
                    $lastModification = $rowPlus['DATE'];
                }
                majHistorique($rowPeriode['ID_HISTORIQUE_PERIODE'], $HID_JOUR, $aDay,  $lastModification);

                //* Si le cron n'a pas tourné les jours précédants, on lance un rattrapage...
                $success = true;
                $prevDate = strtotime("-1 day", $dateLastDay);
                while ($success && (date('m', $prevDate) == date('m',$dateLastDay))) {
                    $HID_JOUR_PREV = 'HID_JOUR'.date("d",$prevDate);
                    if (!empty($rowPeriode[$HID_JOUR_PREV])) {
                        $success = false;
                    } else {
                        $aDayPrev = array(
                            'ACTION' => 0
                        );
                        $sqlPlus = "select
                                        count(ID_HISTORIQUE_ADMIN) as NB_ACTION,
                                        max(HIS_DATE) as DATE
                                    from HISTORIQUE_ADMIN historique
                                    where SIT_CODE = " . $dbh->quote($SIT_CODE) . "
                                        and HIS_DATE >= " . $prevDate . "
                                        and HIS_DATE < " . (strtotime("+1 day", $prevDate)) . "
                                        and HIS_TYPE = ". $dbh->quote($row['IDENTIFIANT']);
                      if ($rowPlus = $dbh->query($sqlPlus)->fetch(PDO::FETCH_ASSOC)) {
                            $aDayPrev = array(
                                'ACTION'	=> intval($rowPlus['NB_ACTION'])
                            );
                            $nbAction += intval($rowPlus['NB_ACTION']);
                            if ($lastModification < $rowPlus['DATE']) {
                                $lastModification = $rowPlus['DATE'];
                            }
                        }
                        majHistorique($rowPeriode['ID_HISTORIQUE_PERIODE'], $HID_JOUR_PREV, $aDayPrev, $lastModification);
                        $prevDate = strtotime("-1 day", $prevDate);
                    }
                }
                //*/
                // On met à jour les totaux des actions
                $sql =  'update HISTORIQUE_PERIODE set
                        HID_TOTALACTION = HID_TOTALACTION+' . intval($nbAction) . '
                        where ID_HISTORIQUE_PERIODE = ' . intval($rowPeriode['ID_HISTORIQUE_PERIODE']);
                $dbh->exec($sql);
            }
        }
    }
}

/*********************************************************************************************************/
/*********************** MET A JOUR LA LIGNE D'HISTORIQUE POUR LE JOUR DONNEE ****************************/
/*********************************************************************************************************/
/**
 * Met à jour l'agrégation des données d'un historique pour un jour donné d'une certaine période
 * @param Int    $ID_HISTORIQUE_PERIODE Identifiant de l'historique agrégé conserné (période)
 * @param String $HID_JOUR              Nom de la colonne du champ concerné correspondant au jour de la période à mettre à jour
 * @param Array  $aDay                  Tableau contenant les info de l'historique
 * @param Int    $lastDate              Timestamp correspondant à la dernière modification / connection
 */
function majHistorique($ID_HISTORIQUE_PERIODE, $HID_JOUR, $aDay, $lastDate = '') {
    $dbh = DB::getInstance();
    $stmt = $dbh->prepare('update HISTORIQUE_PERIODE set
            '. $HID_JOUR .'=:LASTDAY,
            HID_LASTDATE=if(isnull(HID_LASTDATE) || (HID_LASTDATE<'.intval($lastDate).'),'.intval($lastDate).',HID_LASTDATE)
        where ID_HISTORIQUE_PERIODE = :idtf');

    $stmt->bindValue(':LASTDAY', serialize($aDay), PDO::PARAM_STR);
    $stmt->bindValue(':idtf', $ID_HISTORIQUE_PERIODE, PDO::PARAM_INT);
    $stmt->execute();
}
/**
 * Met à jour le libellé principal associé à une agrégation des données d'un historique pour une période donnée
 * @param Int    $ID_HISTORIQUE_PERIODE Identifiant de l'historique agrégé conserné
 * @param String $HID_INFO              Libellé à mettre à jour
 */
function majHistoriqueInfoLibelle($ID_HISTORIQUE_PERIODE, $HID_INFO) {
    $dbh = DB::getInstance();
    $stmt = $dbh->prepare('update HISTORIQUE_PERIODE set
            HID_INFO=:HID_INFO
        where ID_HISTORIQUE_PERIODE = :idtf');

    $stmt->bindValue(':HID_INFO', $HID_INFO, PDO::PARAM_STR);
    $stmt->bindValue(':idtf', $ID_HISTORIQUE_PERIODE, PDO::PARAM_INT);
    $stmt->execute();
}
/********************************************************************************/
/***** RECHERCHE SI LA PERIODE POUR UN ELEMENT DONNE EXISTE DEJA EN BDD**********/
/********************************************************************************/
/**
 * Retourne les info d'un historique agrégé pour un site, une période et un type d'historique
 * en vérifiant que les info du jour souhaité sont bien vides au sein de la base
 * (pour vérifier que l'agrégation n'a pas déjà été faire).
 * Si la période n'est pas initialisée, un enregistrement est inséré pour le type de donné
 * @param String $SIT_CODE        Code du site de l'historique agrégé souhaité
 * @param String $HIC_DATE        Période de la table HISTORIQUE_CALENDRIER concerné
 * @param String $HID_TYPEDONNEE  Type de la donnée de l'historique souhaité (ADMIN, FORMULAIRE, PAGE, UTILISATEUR, WEBOTHEQUE, etc)
 * @param String $HID_IDENTIFIANT Identifiant de l'historique agrégé souhaité
 * @param String $HID_INFO        Libellé de l'historique à utiliser lors de l'initialisation éventuelle de la période
 * @param String $HID_JOUR        Nom de la colonne correspondant au jour de la période permettant de vérifier que l'agrégation n'a pas déjà été faire
 *
 * @return Mixed Retourne le contenu de la période concernée si le jour demandé est bien vide, false sinon
 */
function getHistoriquePeriode($SIT_CODE, $HIC_DATE, $HID_TYPEDONNEE, $HID_IDENTIFIANT, $HID_INFO, $HID_JOUR) {
    $dbh = DB::getInstance();
    $sql = "select * from HISTORIQUE_PERIODE
            where SIT_CODE = " . $dbh->quote($SIT_CODE) . "
            and HIC_DATE = " . $dbh->quote($HIC_DATE) . "
            and HID_TYPEDONNEE = " . $dbh->quote($HID_TYPEDONNEE) . "
            and HID_IDENTIFIANT = " . $dbh->quote($HID_IDENTIFIANT);

    if ($aRows = $dbh->query($sql)->fetch(PDO::FETCH_ASSOC)) {
        if (empty($aRows[$HID_JOUR])) {
            return $aRows;
        }
        return false;
    }

    $stmt = $dbh->prepare("insert into HISTORIQUE_PERIODE (
        SIT_CODE,
        HIC_DATE,
        HID_TYPEDONNEE,
        HID_IDENTIFIANT,
        HID_INFO
        ) values(
        :SIT_CODE,
        :HIC_DATE,
        :HID_TYPEDONNEE,
        :HID_IDENTIFIANT,
        :HID_INFO
        )");

    $stmt->bindValue(':SIT_CODE',  $SIT_CODE, PDO::PARAM_STR);
    $stmt->bindValue(':HIC_DATE', $HIC_DATE, PDO::PARAM_STR);
    $stmt->bindValue(':HID_TYPEDONNEE',  $HID_TYPEDONNEE, PDO::PARAM_STR);
    $stmt->bindValue(':HID_IDENTIFIANT',  $HID_IDENTIFIANT, PDO::PARAM_STR);
    $stmt->bindValue(':HID_INFO',  $HID_INFO, PDO::PARAM_STR);
    $stmt->execute();
    $sql = "select * from HISTORIQUE_PERIODE where ID_HISTORIQUE_PERIODE = " . $dbh->lastInsertId();
    return $dbh->query($sql)->fetch(PDO::FETCH_ASSOC);
}
