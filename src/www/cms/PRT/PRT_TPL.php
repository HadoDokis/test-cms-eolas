<?php
require '../../include/inc.bo_init.php';
Utilisateur::checkConnected();
require CLASS_DIR . 'class.db_page.php';
require CLASS_DIR . 'class.db_paragraphe.php';

$oParagraphe = new Paragraphe_TPL($_GET['idtf']);
if ($oParagraphe->exist()) {
    $row = $oParagraphe->getFields();
    $oPage = $oParagraphe->getPage();
    $PAR_COLONNE = $oParagraphe->getField('PAR_COLONNE');
} else {
    $oParagraphe_prec = new Paragraphe($_GET['idtf_prec']);
    if ($oParagraphe_prec->exist()) {
        $oPage = $oParagraphe_prec->getPage();
        $PAR_COLONNE = $oParagraphe_prec->getField('PAR_COLONNE');
        $PAR_POIDS = $oParagraphe_prec->getField('PAR_POIDS') + 1;
    } else {
        $oPage = new Page($_GET['ID_PAGE']);
        $PAR_COLONNE = $_GET['PAR_COLONNE'];
        $PAR_POIDS = 1;
    }
}
$oPage->checkAuthorized();
$oPage->lock();

$filtreTPL = " where TPL_AFFECTABLE=1 and TPL_COLONNE like " . $dbh->quote("%@" . $PAR_COLONNE . "@%") . "
    and DD_TEMPLATE.MOD_CODE in (select distinct(MOD_CODE) from SITE_MODULE where SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID()) . ")
    and (
    ID_TEMPLATE_GABARIT IS NULL
    or GAB_CODE = " . $dbh->quote(CMS::getCurrentSite()->getField('GAB_CODE')) . "
    )";
?>
<!DOCTYPE html>
<html>
<head>
<?php include('../../include/inc.bo_enTete.php') ?>
<script>
var tabRestrictable = new Array();
var tabRestrictable_pop = new Array();
<?php
$sql = "select DD_TEMPLATE.* from DD_TEMPLATE
left join DD_TEMPLATE_GABARIT on (DD_TEMPLATE.TPL_CODE = DD_TEMPLATE_GABARIT.TPL_CODE)
" . $filtreTPL . " and TPL_POPUP_RESTRICTION <> ''";

foreach ($dbh->query($sql) as $rowTemp) { ?>
tabRestrictable[tabRestrictable.length] = '<?php echo $rowTemp['TPL_CODE']?>';
tabRestrictable_pop[tabRestrictable_pop.length] = '<?php if ($rowTemp['TPL_POPUP_RESTRICTION'] != '') { ?><a href="<?php echo SERVER_ROOT . $rowTemp['TPL_POPUP_RESTRICTION'];?><?php echo (substr($rowTemp['TPL_POPUP_RESTRICTION'],-3) == 'php')?'?':'&'?>IDENTIFIANT=PAR_TPL_IDENTIFIANT&TEXTE=PAR_CONTENU" class="action popup"><?php echo gettext('Choisir')?></a> <a href="javascript:void(0)" onclick="document.getElementById(\'PAR_TPL_IDENTIFIANT\').value = \'\';document.getElementById(\'PAR_CONTENU\').value = \'\';return false;" class="action"><?php echo gettext('Effacer')?></a><?php } ?>';
<?php } ?>

function updateRestriction()
{
    $('#restrictionTPL').hide();
    $('#label_PAR_TPL_IDENTIFIANT').removeClass('isNotNull');
    for (i=0; i<tabRestrictable.length; i++) {
            if ($('#TPL_CODE').val() == tabRestrictable[i]) {
                $('#label_PAR_TPL_IDENTIFIANT').addClass('isNotNull');
                $('#restrictionTPL').show();
                $('#popRestriction').html(tabRestrictable_pop[i]);

                return true;
            }
    }

    return false;
}
$(document).ready(updateRestriction);
</script>
</head>
<body>
<div id="document">
    <?php include('../../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">
            <form method="post" action="PRT_Submit.php" class="creation">
                <fieldset>
                    <legend><?php echo gettext('Template')?></legend>
                    <table>
                        <tfoot>
                            <tr>
                                <td colspan="2">
                                    <?php if ($oParagraphe->exist()) { ?>
                                    <input type="hidden" name="idtf" value="<?php echo $oParagraphe->getID()?>">
                                    <input type="submit" name="Update" value="<?php echo gettext('UPDATE')?>" class="modifier">
                                    <input type="hidden" name="Update" value="Update">
                                    <?php } else { ?>
                                    <input type="hidden" name="ID_PAGE" value="<?php echo $oPage->getID()?>">
                                    <input type="hidden" name="PAR_POIDS" value="<?php echo $PAR_POIDS?>">
                                    <input type="hidden" name="PAR_COLONNE" value="<?php echo $PAR_COLONNE?>">
                                    <input type="submit" name="Insert" value="<?php echo gettext('INSERT')?>" class="ajouter">
                                    <input type="hidden" name="Insert" value="Insert">
                                    <?php } ?>
                                    <input type="hidden" name="PRT_CODE" value="PRT_TPL">
                                    <input type="button" name="retour" onclick="window.location.href='../cms_pseudo.php?idtf=<?php echo $oPage->getID()?>&amp;PFM=1'" value="<?php echo gettext('Retour')?>" class="retour">
                                </td>
                            </tr>
                        </tfoot>
                        <tbody>
                            <tr>
                                <th><label for="PAR_TITRE"><?php echo gettext('Titre')?></label></th>
                                <td><input name="PAR_TITRE" type="text" id="PAR_TITRE" value="<?php echo secureInput($row['PAR_TITRE'])?>" size="80" maxlength="200"><script>document.getElementById('PAR_TITRE').focus()</script></td>
                            </tr>
                            <tr>
                                <th><label>Affichage</label></th>
                                <td>
                                    <input type="checkbox" name="PAR_MOBILEHIDDEN" id="PAR_MOBILEHIDDEN" value="1"<?php if ($row['PAR_MOBILEHIDDEN']) echo ' checked'?>>
                                    <label for="PAR_MOBILEHIDDEN">Masquer sur mobile</label>
                                </td>
                            </tr>
                            <tr>
                                <th><label><?php echo gettext('Heritable')?></label></th>
                                <td>
                                    <input type="radio" name="PAR_HERITABLE" id="PAR_HERITABLE_2" value="2"<?php if ($row['PAR_HERITABLE']==2) echo ' checked'?>><label for="PAR_HERITABLE_2"><?php echo gettext('Oui')?> <?php echo gettext('avec_styles')?></label>
                                    <input type="radio" name="PAR_HERITABLE" id="PAR_HERITABLE_1" value="1"<?php if ($row['PAR_HERITABLE']==1) echo ' checked'?>><label for="PAR_HERITABLE_1"><?php echo gettext('Oui')?> <?php echo gettext('sans_styles')?></label>
                                    <input type="radio" name="PAR_HERITABLE" id="PAR_HERITABLE_0" value="0"<?php if (!$row['PAR_HERITABLE'] ||$row['PAR_HERITABLE']==0) echo ' checked'?>><label for="PAR_HERITABLE_0"><?php echo gettext('Non')?></label>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="TPL_CODE"><?php echo gettext('Template')?></label></th>
                                <td>
                                    <select name="TPL_CODE" id="TPL_CODE" onchange="document.getElementById('PAR_TPL_IDENTIFIANT').value = '';document.getElementById('PAR_CONTENU').value = '';updateRestriction()" required>
                                        <option value="">&nbsp;</option>
                                        <?php
                                        $sql = "select DD_TEMPLATE.*, DD_MODULE.* from DD_TEMPLATE
                                            inner join DD_MODULE using (MOD_CODE)
                                            left join DD_TEMPLATE_GABARIT on (DD_TEMPLATE.TPL_CODE = DD_TEMPLATE_GABARIT.TPL_CODE)
                                            " . $filtreTPL . "
                                            order by MOD_LIBELLE, TPL_LIBELLE";
                                        $MOD_LIBELLE = '';
                                        foreach ($dbh->query($sql) as $rowTemp) {
                                            if ($MOD_LIBELLE != $rowTemp['MOD_LIBELLE']) {
                                                if ($MOD_LIBELLE != '') {
                                                    echo '</optgroup>';
                                                }
                                                $MOD_LIBELLE = $rowTemp['MOD_LIBELLE']; ?>
                                        <optgroup label="<?php echo secureInput(extraireLibelle($MOD_LIBELLE))?>">
                                        <?php } ?>
                                        <option value="<?php echo $rowTemp['TPL_CODE']?>"<?php if ($rowTemp['TPL_CODE'] == $row['TPL_CODE']) echo ' selected';?>><?php echo secureInput(extraireLibelle($rowTemp['TPL_LIBELLE']))?></option>
                                        <?php } ?>
                                        </optgroup>
                                    </select>
                                </td>
                            </tr>
                            <tr id="restrictionTPL">
                                <th><label for="PAR_TPL_IDENTIFIANT" id="label_PAR_TPL_IDENTIFIANT" class="isNotNull"><?php echo gettext('Restriction')?></label></th>
                                <td>
                                    <input type="hidden" name="PAR_TPL_IDENTIFIANT" id="PAR_TPL_IDENTIFIANT" value="<?php echo secureInput($row['PAR_TPL_IDENTIFIANT'])?>" required>
                                    <input type="text" readonly  name="PAR_CONTENU" id="PAR_CONTENU" value="<?php echo secureInput($row['PAR_CONTENU'])?>" size="60">
                                    <span id="popRestriction"></span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </fieldset>
            </form>
        </div>
    </div>
    <?php include('../../include/inc.bo_bandeau_bas.php')?>
</div>
</body>
</html>
