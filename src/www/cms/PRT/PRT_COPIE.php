<?php
require '../../include/inc.bo_init.php';
Utilisateur::checkConnected();
require CLASS_DIR . 'class.db_page.php';
require CLASS_DIR . 'class.db_paragraphe.php';
require CLASS_DIR . 'class.Arbo.php';

$oParagraphe_prec = new Paragraphe($_GET['idtf_prec']);
if ($oParagraphe_prec->exist()) {
    $oPage = $oParagraphe_prec->getPage();
    $PAR_COLONNE = $oParagraphe_prec->getField('PAR_COLONNE');
    $PAR_POIDS = $oParagraphe_prec->getField('PAR_POIDS') + 1;
} else {
    $oPage = new Page($_GET['ID_PAGE']);
    $PAR_COLONNE = $_GET['PAR_COLONNE'];
    $PAR_POIDS = (is_numeric($_GET['PAR_POIDS'])) ? $_GET['PAR_POIDS'] : 1;
}
$oPage->checkAuthorized();
$oPage->lock();

$aParagraphe = array ();
$aSite = CMS::getCurrentSite()->getRevertSharedSites();
$aSite[CMS::getCurrentSite()->getID()] = CMS::getCurrentSite();

$aSiteMode = array();
foreach ($aSite as $sitCode => $unSite) {
    $aSiteMode['OFF@'.$sitCode] = $unSite;
    $aSiteMode['ON@'.$sitCode] = $unSite;
}

if (empty($_GET['SIT_CODE'])) {
    $_GET['SIT_CODE'] = 'OFF@'.CMS::getCurrentSite()->getID();
    $getSitCode = CMS::getCurrentSite()->getID();
    $modeUsed = 'OFF_';
} else {
    $aSitCodeMode = explode('@', $_GET['SIT_CODE']);
    $getSitCode = $aSitCodeMode[1];
    $modeUsed = $aSitCodeMode[0].'_';
    if (!array_key_exists($getSitCode, $aSite)) {
        die(gettext('Ressource_non_disponible'));
    }

}
if (is_numeric($_GET['ID_PAGE_DEST'])) {
    $oPageDest = new Page($_GET['ID_PAGE_DEST'], $modeUsed);
    $PAG_TITRE_MENU = $oPageDest->getField('PAG_TITRE_MENU');
    if ($oPageDest->getField('SIT_CODE') != CMS::getCurrentSite()->getID()) {
        if (!$oPageDest->checkShareAuthorized(false)) {
            die(gettext('Ressource_non_disponible'));
        }
        $PAG_TITRE_MENU = '[' . $aSite[$oPageDest->getField('SIT_CODE')]->getField('SIT_LIBELLE') . '] ' . $PAG_TITRE_MENU;
    }
    // recup des paragraphes TXT de la même colonne
    $sql = "select * from ".$modeUsed."PARAGRAPHE where ID_PAGE=" . $oPageDest->getID() . " and PAR_COLONNE=" . $dbh->quote($PAR_COLONNE). " and PRT_CODE='PRT_TXT' order by PAR_POIDS";
    foreach ($dbh->query($sql) as $rowTemp) {
        $aParagraphe[$rowTemp['ID_PARAGRAPHE']] = ($rowTemp['PAR_TITRE'] != '') ? secureInput($rowTemp['PAR_TITRE']) : '<em>' . gettext('Sans titre') . ' (' . $rowTemp['ID_PARAGRAPHE'] . ')</em>';
    }
}

$oArbo = new Arbo('COPIEPARTAGE', array('ID_PAGE'=>$oPage->getID(), 'PAR_POIDS'=>$PAR_POIDS, 'PAR_COLONNE'=>$PAR_COLONNE, 'SIT_CODE'=>secureInput($_GET['SIT_CODE'])), $modeUsed);
?>
<!DOCTYPE html>
<html>
<head>
    <?php include('../../include/inc.bo_enTete.php') ?>
    <script>
        $(document).ready(cmsBO.initArbo);
        function postControl_formCreation(oForm)
        {
            for (var i=0; i<document.getElementsByName('ID_PARAGRAPHE[]').length; i++) {
                if (document.getElementsByName('ID_PARAGRAPHE[]')[i].checked) {
                    return true;
                }
            }
            alert("<?php echo gettext('Veuillez selectionner un paragraphe')?>");
            return false;
        }
        function selectAll(param)
        {
            for (i=0; i<document.getElementsByName('ID_PARAGRAPHE[]').length; i++) {
                document.getElementsByName('ID_PARAGRAPHE[]')[i].checked = param;
            }
        }
    </script>
</head>
<body>
<div id="document">
    <?php include('../../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu" class="creation">
            <fieldset style="position:relative;">
                <legend><?php echo gettext('Copie de paragraphe')?></legend>
                <?php if (sizeof($aSiteMode) > 1) { ?>
                <div style="position:absolute; right:20px; top:20px;">
                    <label for="SIT_CODE"><?php echo gettext('Site courant')?></label>
                    <select id="SIT_CODE" name="SIT_CODE" onchange="window.location.href='<?php echo PHP_SELF?>?ID_PAGE=<?php echo $oPage->getID()?>&amp;PAR_POIDS=<?php echo $PAR_POIDS?>&amp;PAR_COLONNE=<?php echo $PAR_COLONNE?>&amp;SIT_CODE='+this.value;">
                    <?php foreach ($aSiteMode as $sitModeCode => $_oSite) { ?>
                        <option value="<?php echo $sitModeCode?>"<?php if ($_GET['SIT_CODE'] == $sitModeCode) echo ' selected';?>>
                            <?php
                            $aSitModeCodeTxt = explode('@', $sitModeCode);
                            $txtAjout = ($aSitModeCodeTxt[0] == 'OFF')? ' : contribution' : ' : en ligne';
                            echo secureInput($_oSite->getField('SIT_LIBELLE')).$txtAjout;
                            ?>
                        </option>
                    <?php } ?>
                    </select>
                </div>
                <?php } ?>
                <form action="PRT_Submit.php" method="post" id="formCreation" class="creation">
                    <table>
                        <tfoot>
                            <tr>
                                <td colspan="2">
                                    <input type="hidden" name="PRT_CODE" value="PRT_COPIE">
                                    <input type="hidden" name="ID_PAGE" value="<?php echo $oPage->getID()?>">
                                    <input type="hidden" name="PAR_POIDS" value="<?php echo $PAR_POIDS?>">
                                    <input type="hidden" name="PAR_COLONNE" value="<?php echo $PAR_COLONNE?>">
                                    <input type="hidden" name="MODECOPIE" value="<?php echo $modeUsed?>">
                                    <input type="submit" name="Insert" value="<?php echo gettext('INSERT')?>" class="ajouter">
                                    <input type="button" name="retour" onclick="window.location.href='../cms_pseudo.php?idtf=<?php echo $oPage->getID()?>&amp;PFM=1'" value="<?php echo gettext('Retour')?>" class="retour">
                                </td>
                            </tr>
                        </tfoot>
                        <tbody>
                            <tr>
                                <td colspan="2"><?php echo Arbo::action() ?></td>
                            </tr>
                            <tr>
                                <th><label><?php echo gettext('Page')?></label></th>
                                <td><?php echo secureInput($PAG_TITRE_MENU)?></td>
                            </tr>
                            <tr>
                                <th><label><?php echo gettext('Paragraphe')?></label></th>
                                <td>
                                <?php if (sizeof($aParagraphe) == 0) {?>
                                    Aucun
                                <?php } else { ?>
                                    <ul>
                                        <?php foreach ($aParagraphe as $key => $val) { ?>
                                        <li>
                                            <input type="checkbox" name="ID_PARAGRAPHE[]" id="ID_PARAGRAPHE_<?php echo $key?>" value="<?php echo $key?>">
                                            <label for="ID_PARAGRAPHE_<?php echo secureInput($key)?>"><?php echo $val?></label>
                                        </li>
                                        <?php } ?>
                                        <li><a href="javascript:selectAll(true);"><?php echo gettext('Tout selectionner');?></a>&nbsp;/&nbsp;<a href="javascript:selectAll(false);"><?php echo gettext('Tout deselectionner');?></a></li>
                                    </ul>
                                <?php } ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </form>
                <?php echo $oArbo->draw($aSite[$getSitCode]->getHomePage()->getID()) ?>
            </fieldset>
        </div>
    </div>
    <?php include('../../include/inc.bo_bandeau_bas.php')?>
</div>
</body>
</html>
