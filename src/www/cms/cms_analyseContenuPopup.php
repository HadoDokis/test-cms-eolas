<?php
require '../include/inc.bo_init.php';
Utilisateur::checkConnected();
require CLASS_DIR . 'class.db_page.php';

$oPage = new Page($_GET['idtf']);
$oPage->checkAuthorized();
$row = $oPage->getFields();

$sql = "select STP_LIBELLE from STOPWORD_SITE where SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID());
$aStopwords = $dbh->query($sql)->fetchAll(PDO :: FETCH_COLUMN);

$aContenu = array();
$aLien = array();
$aAlt = array();
//Sur les paragraphes de la page
$sql = "select PAR_CONTENU, PAR_CONTENUTEXTE, PAR_TITRE from OFF_PARAGRAPHE where ID_PAGE=" . $oPage->getID() . " and PRT_CODE='PRT_TXT'";
foreach ($dbh->query($sql, PDO :: FETCH_ASSOC) as $rowTemp) {
    $aContenu[] = $rowTemp['PAR_TITRE'];
    $aContenu[] = $rowTemp['PAR_CONTENUTEXTE'];
    //Traitement des alts
    $_tab = array();
    preg_match_all('|alt="([^"]*)"|uU', str_replace('alt=""', 'alt="<em>' . gettext('-- VIDE --') . '</em>"', $rowTemp['PAR_CONTENU']), $_tab);
    if (sizeof($_tab[1]) > 0) {
        $aAlt = array_merge($aAlt, $_tab[1]);
    }
    //Traitement des liens
    $_tab = array();
    preg_match_all('|<a [^>]+>(.*)</a>|uU', preg_replace('/<img [^>]*>/u', '<em>' . gettext('-- IMAGE --') . '</em>', $rowTemp['PAR_CONTENU']), $_tab);
    if (sizeof($_tab[1]) > 0) {
        $aLien = array_merge($aLien, $_tab[1]);
    }
}
//Sur les paragraphes partagés
$sql = "select t2.PAR_CONTENU, t2.PAR_CONTENUTEXTE, t2.PAR_TITRE from OFF_PARAGRAPHE as t1
    inner join OFF_PARAGRAPHE as t2 on t1.PAR_TPL_IDENTIFIANT=t2.ID_PARAGRAPHE
    where t1.ID_PAGE=" . $oPage->getID() . " and t1.PRT_CODE = 'PRT_PARTAGE'";
foreach ($dbh->query($sql, PDO :: FETCH_ASSOC) as $rowTemp) {
    $aContenu[] = $rowTemp['PAR_TITRE'];
    $aContenu[] = $rowTemp['PAR_CONTENUTEXTE'];
    //Traitement des alts
    $_tab = array();
    preg_match_all('|alt="([^"]*)"|uU', str_replace('alt=""', 'alt="<em>' . gettext('-- VIDE --') . '</em>"', $rowTemp['PAR_CONTENU']), $_tab);
    if (sizeof($_tab[1]) > 0) {
        $aAlt = array_merge($aAlt, $_tab[1]);
    }
    //Traitement des liens
    $_tab = array();
    preg_match_all('|<a [^>]+>(.*)</a>|uU', preg_replace('/<img [^>]*>/u', '<em>' . gettext('-- IMAGE --') . '</em>', $rowTemp['PAR_CONTENU']), $_tab);
    if (sizeof($_tab[1]) > 0) {
        $aLien = array_merge($aLien, $_tab[1]);
    }
}
$aContenu[] = $row['PAG_TITRE'];
$aContenu[] = $row['PAG_ACCROCHE'];
$aContenu[] = $row['PAG_TITRE_REFERENCEMENT'];
$aContenu[] = $row['PAG_METADESCRIPTION'];

//Traitement du contenu des paragraphes de la page et partagés
$aOccurence = array();
foreach ($aContenu as $texte) {
    //On traite le texte au cas où il y ait des entités html présentes
    //L'expression régulière active le mode utf-8 (u)
    //	et recherche tous ce qui n'est pas (^) caractères (\p) avec les propriétés "lettre" accentués compris ({L})
    $tab = preg_split('/[^\p{L}]/u', html_entity_decode($texte, ENT_QUOTES, 'UTF-8'));
    foreach ($tab as $key => $val) {
        if (mb_strlen($val) < 3 || in_array($val, $aStopwords)) {
            unset($tab[$key]);
        } else {
            $tab[$key] = mb_strtolower($val);
        }
    }
    $aOccurence = array_merge($aOccurence, $tab);
}
unset($aContenu);

$aOccurence = array_count_values($aOccurence);
$totalMot = array_sum($aOccurence);
arsort($aOccurence);
$aOccurence = array_slice($aOccurence, 0, 20);

$aAlt = array_count_values($aAlt);
arsort($aAlt);

$aLien = array_count_values($aLien);
arsort($aLien);
?>
<!DOCTYPE html>
<html>
<head>
    <?php include('../include/inc.bo_enTete.php')?>
    <script src="<?php echo SERVER_ROOT ?>include/js/onglet.js"></script>
</head>
<body id="popup">
    <?php include('../include/inc.bo_bandeau_hautPopup.php')?>
    <div id="bo_contenuPopup" class="creation">
        <h2><?php echo gettext('Analyse du contenu')?></h2>
        <fieldset class="tab">
            <legend><?php echo gettext('Contenu')?></legend>
            <table class="liste">
                <thead>
                    <tr>
                        <th><?php echo gettext('Mot')?></th>
                        <th><?php echo gettext('Occurence')?></th>
                        <th><?php echo gettext('Densite')?></th>
                    </tr>
                </thead>
                <?php foreach ($aOccurence as $key => $val) { ?>
                <tr>
                    <td><?php echo $key?></td>
                    <td class="alignright"><?php echo $val?></td>
                    <td class="alignright"><?php echo number_format($val / $totalMot * 100, 2, ',', ' ')?> %</td>
                </tr>
                <?php } ?>
            </table>
        </fieldset>
        <fieldset class="tab">
            <legend><?php echo gettext('Alt')?></legend>
            <table class="liste">
                <thead>
                    <tr>
                        <th><?php echo gettext('Libelle')?></th>
                        <th><?php echo gettext('Occurence')?></th>
                    </tr>
                </thead>
                <?php foreach ($aAlt as $key => $val) { ?>
                <tr>
                    <td><?php echo $key?></td>
                    <td class="alignright"><?php echo $val?></td>
                </tr>
                <?php } ?>
            </table>
        </fieldset>
        <fieldset class="tab">
            <legend><?php echo gettext('Lien')?></legend>
            <table class="liste">
                <thead>
                    <tr>
                        <th><?php echo gettext('Libelle')?></th>
                        <th><?php echo gettext('Occurence')?></th>
                    </tr>
                </thead>
                <?php foreach ($aLien as $key => $val) { ?>
                <tr>
                    <td><?php echo $key?></td>
                    <td class="alignright"><?php echo $val?></td>
                </tr>
                <?php } ?>
            </table>
        </fieldset>
    </div>
    <?php include('../include/inc.bo_bandeau_basPopup.php')?>
</body>
</html>
