<?php
require '../../include/inc.bo_init.php';
Utilisateur::checkConnected();
require CLASS_DIR . 'class.db_page.php';
require CLASS_DIR . 'class.db_paragraphe.php';
require CLASS_DIR . 'class.Arbo.php';

$oParagraphe = new Paragraphe_PARTAGE($_GET['idtf']);
if ($oParagraphe->exist()) {
    $oPage = $oParagraphe->getPage();
    $PAR_COLONNE = $oParagraphe->getField('PAR_COLONNE');
    if (!isset ($_GET['ID_PAGE_DEST'])) {
        $sql = "select * from ON_PARAGRAPHE inner join ON_PAGE using(ID_PAGE) where ID_PARAGRAPHE=" . $oParagraphe->getField('PAR_TPL_IDENTIFIANT');
        $row = $dbh->query($sql)->fetch(PDO::FETCH_ASSOC);
        $_GET['ID_PAGE_DEST'] = $row['ID_PAGE'];
        if (!isset($_GET['SIT_CODE'])) {
            $_GET['SIT_CODE'] = $row['SIT_CODE'];
        }
    }
    //action pour l'envoi GET
    $action = 'Update=1&amp;PRT_CODE=PRT_PARTAGE&amp;idtf=' . $oParagraphe->getID();
} else {
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
}
$oPage->checkAuthorized();
$oPage->lock();

$aSite = CMS::getCurrentSite()->getRevertSharedSites();
$aSite[CMS::getCurrentSite()->getID()] = CMS::getCurrentSite();

if (empty($_GET['SIT_CODE'])) {
    $_GET['SIT_CODE'] = CMS::getCurrentSite()->getID();
} elseif (!array_key_exists($_GET['SIT_CODE'], $aSite)) {
    die(gettext('Ressource_non_disponible'));
}

$aParagraphe = array ();
if (is_numeric($_GET['ID_PAGE_DEST'])) {
    $oPageDest = new Page($_GET['ID_PAGE_DEST'], 'ON_');
    $PAG_TITRE_MENU = $oPageDest->getField('PAG_TITRE_MENU');
    if ($oPageDest->getField('SIT_CODE') != CMS::getCurrentSite()->getID()) {
        if (!$oPageDest->checkShareAuthorized(false)) {
            die(gettext('Ressource_non_disponible'));
        }
        $PAG_TITRE_MENU = '[' . $aSite[$oPageDest->getField('SIT_CODE')]->getField('SIT_LIBELLE') . '] ' . $PAG_TITRE_MENU;
    }
    // recup des paragraphes TXT de la même colonne
    $sql = "select * from ON_PARAGRAPHE where ID_PAGE=" . $oPageDest->getID() . " and PAR_COLONNE=" . $dbh->quote($PAR_COLONNE). " and PRT_CODE='PRT_TXT' order by PAR_POIDS";
    foreach ($dbh->query($sql) as $rowTemp) {
        $aParagraphe[$rowTemp['ID_PARAGRAPHE']] = ($rowTemp['PAR_TITRE'] != '') ? secureInput($rowTemp['PAR_TITRE']) : '<em>' . gettext('Sans titre') . ' (' . $rowTemp['ID_PARAGRAPHE'] . ')</em>';
    }
}

$oArbo = new Arbo('COPIEPARTAGE', array('idtf' => $oParagraphe->getID(),'ID_PAGE' => $oPage->getID(), 'PAR_POIDS' => $PAR_POIDS,'PAR_COLONNE' => $PAR_COLONNE, 'SIT_CODE' => $_GET['SIT_CODE']), 'ON_');
?>
<!DOCTYPE html>
<html>
<head>
    <?php include('../../include/inc.bo_enTete.php') ?>
    <script>
        $(document).ready(cmsBO.initArbo);
    <?php if (!$oParagraphe->exist()) {?>
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
    <?php }?>
    </script>
</head>
<body>
<div id="document">
    <?php include('../../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu" class="creation">
            <fieldset style="position:relative;">
                <legend><?php echo gettext('Partage de paragraphe')?></legend>
                <?php if (sizeof($aSite) > 1) { ?>
                <div style="position:absolute; right:20px; top:20px;">
                    <label for="SIT_CODE"><?php echo gettext('Site courant')?></label>
                    <select id="SIT_CODE" name="SIT_CODE" onchange="window.location.href='<?php echo PHP_SELF?>?idtf=<?php echo $oParagraphe->getID()?>&amp;ID_PAGE=<?php echo $oPage->getID()?>&amp;PAR_POIDS=<?php echo $PAR_POIDS?>&amp;PAR_COLONNE=<?php echo urlencode($PAR_COLONNE) ?>&amp;SIT_CODE='+this.value;">
                    <?php foreach ($aSite as $_oSite) { ?>
                        <option value="<?php echo $_oSite->getID()?>"<?php if ($_GET['SIT_CODE'] == $_oSite->getID()) echo ' selected';?>>
                            <?php echo secureInput($_oSite->getField('SIT_LIBELLE'))?>
                        </option>
                    <?php } ?>
                    </select>
                </div>
                <?php } ?>
                <?php if (!$oParagraphe->exist()) {?>
                <form action="PRT_Submit.php" method="post" id="formCreation" class="creation">
                <?php }?>
                    <table>
                        <tfoot>
                            <tr>
                                <td colspan="2">
                                    <?php if (!$oParagraphe->exist()) {?>
                                    <input type="hidden" name="PRT_CODE" value="PRT_PARTAGE">
                                    <input type="hidden" name="IS_PARTAGE" value="1">
                                    <input type="hidden" name="ID_PAGE" value="<?php echo $oPage->getID()?>">
                                    <input type="hidden" name="PAR_POIDS" value="<?php echo $PAR_POIDS?>">
                                    <input type="hidden" name="PAR_COLONNE" value="<?php echo $PAR_COLONNE?>">
                                    <input type="submit" name="Insert" value="<?php echo gettext('INSERT')?>" class="ajouter">
                                    <?php }?>
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
                                    <?php foreach ($aParagraphe as $key=>$val) { ?>
                                        <li>
                                            <?php if (!$oParagraphe->exist()) { ?>
                                            <input type="checkbox" name="ID_PARAGRAPHE[]" id="ID_PARAGRAPHE_<?php echo $key?>" value="<?php echo $key?>">
                                            <label for="ID_PARAGRAPHE_<?php echo $key?>"><?php echo $val?></label>
                                            <?php } else {?>
                                            <a href="PRT_Submit.php?<?php echo $action?>&amp;ID_PARAGRAPHE=<?php echo $key?>">
                                            <?php echo $val?>
                                            </a>
                                            <?php }?>
                                        </li>
                                    <?php } ?>
                                    <?php if (!$oParagraphe->exist()) { ?>
                                        <li><a href="javascript:selectAll(true);"><?php echo gettext('Tout selectionner');?></a>&nbsp;/&nbsp;<a href="javascript:selectAll(false);"><?php echo gettext('Tout deselectionner');?></a></li>
                                    <?php }?>
                                    </ul>
                                <?php } ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                <?php if (!$oParagraphe->exist()) {?>
                </form>
                <?php }?>
            <?php echo $oArbo->draw($aSite[$_GET['SIT_CODE']]->getHomePage()->getID()) ?>
            </fieldset>
        </div>
    </div>
    <?php include('../../include/inc.bo_bandeau_bas.php')?>
</div>
</body>
</html>
