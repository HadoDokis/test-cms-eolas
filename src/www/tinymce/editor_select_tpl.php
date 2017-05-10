<?php
require '../include/inc.bo_init.php';
CMS::checkAccess(new Module('MOD_CORE'));

$filtreTPL = " where TPL_LINKABLE=1 and TPL_COLONNE like '%@PAR_CENTRAL@%'
    and DD_TEMPLATE.MOD_CODE in (select distinct(MOD_CODE) from SITE_MODULE where SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID()) . ")
    and (
    ID_TEMPLATE_GABARIT IS NULL
    or GAB_CODE = " . $dbh->quote(CMS::getCurrentSite()->getField('GAB_CODE')) . "
    )";
?>
<!DOCTYPE html>
<html>
<head>
    <?php include('../include/inc.bo_enTete.php')?>
    <script src="tiny_mce_popup.js"></script>
    <script src="plugins/cms/js/cms.js"></script>
    <script>
    function postControl_formCreation(oForm) {
        var f_id = $('#TPL_CODE').val();
        var f_typelien = 'LienTemplate';
        var f_title = $('#f_title').val();
        var f_nofollow = $('#f_nofollow').prop('checked') ? '1' : '';
        var f_ancre = $('#PAR_TPL_IDENTIFIANT').val();
        var f_libelle_ancre = $('#PAR_CONTENU').val();
        var ed = tinyMCEPopup.editor;
        ed.execCommand("mceBeginUndoLevel");
        insererLien(ed, f_id, f_typelien, f_title, f_nofollow, f_ancre, f_libelle_ancre);
        ed.execCommand("mceEndUndoLevel");
        tinyMCEPopup.close();
        return false;
    }

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

    function updateRestriction() {
        $('#restrictionTPL').hide();
        document.getElementById('label_PAR_TPL_IDENTIFIANT').className = '';
        for (i=0; i<tabRestrictable.length; i++) {
            if (document.getElementById('TPL_CODE').value == tabRestrictable[i]) {
                $('#restrictionTPL').show();
                document.getElementById('label_PAR_TPL_IDENTIFIANT').className = 'isNotNull';
                $('#popRestriction').html(tabRestrictable_pop[i]);
                return true;
            }
        }
        return false;
    }

    function Init() {
        var cLinks= document.getElementsByTagName("link");
        for (var i=0; cLinks[i]; i++) {
            if (/tinymce/.test(cLinks[i].href)) {
                cLinks[i].disabled = true;
            }
        }
        // Suppression et réinitialisation du gestionnaire de formulaire depuis la méthode de chargement de popup de Tiny
        removeEventLst(window, "load", formCtrl.load);
        for(i = 0; i < document.getElementsByTagName('form').length; i++ ) removeEventLst(document.getElementsByTagName('form')[i], "submit", formCtrl.control);
        formCtrl.load();
        updateRestriction();
    }
    </script>
    <base target="_self">
</head>
<body id="popup" onload="tinyMCEPopup.executeOnLoad(Init());">
    <?php include('../include/inc.bo_bandeau_hautPopup.php')?>
    <div id="bo_contenuPopup">
        <h2>Choisir un template</h2>
            <form method="get" action="<?php echo PHP_SELF?>" class="creation" id="formCreation">
            <table>
                <tfoot>
                    <tr>
                        <td colspan="2">
                            <input type="hidden" name="f_ancre" id="f_ancre" value="<?php echo secureInput($_GET['ancre'])?>">
                            <input type="submit" value="<?php echo gettext('Valider')?>" class="ajouter">
                        </td>
                    </tr>
                </tfoot>
                <tbody>
                    <tr>
                        <th><label for="TPL_CODE"><?php echo gettext('Template')?></label></th>
                        <td>
                            <select name="TPL_CODE" id="TPL_CODE" onchange="document.getElementById('PAR_TPL_IDENTIFIANT').value = ''; updateRestriction();" required>
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
                                        $MOD_LIBELLE = $rowTemp['MOD_LIBELLE'];?>
                                <optgroup label="<?php echo extraireLibelle($MOD_LIBELLE)?>">
                                <?php } ?>
                                <option value="<?php echo $rowTemp['TPL_CODE']?>"<?php if ($rowTemp['TPL_CODE'] == $_GET['idtf']) echo ' selected';?>><?php echo extraireLibelle($rowTemp['TPL_LIBELLE'])?></option>
                                <?php } ?>
                                </optgroup>
                            </select>
                        </td>
                    </tr>
                    <tr id="restrictionTPL">
                        <th><label for="PAR_TPL_IDENTIFIANT" class="isNotNull" id="label_PAR_TPL_IDENTIFIANT"><?php echo gettext('Restriction')?>* </label></th>
                        <td>
                            <input type="hidden" name="PAR_TPL_IDENTIFIANT" id="PAR_TPL_IDENTIFIANT" value="<?php echo secureInput($_GET['ancre'])?>" class="alignright">
                            <input type="text" readonly class="disabled" name="PAR_CONTENU" id="PAR_CONTENU" value="<?php if (!empty($_GET['ancre']) && !empty($_GET['libelle_ancre'])) echo secureInput($_GET['libelle_ancre']);?>" size="60">
                            <span id="popRestriction"></span>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="f_title"><?php echo gettext('Titre survol')?></label></th>
                        <td><input name="f_title" type="text" id="f_title" value="<?php echo secureInput(utf8_decode($_GET['title']))?>" size="40"></td>
                    </tr>
                    <tr>
                        <th><label>Référencement</label></th>
                        <td>
                            <input type="checkbox" name="f_nofollow" id="f_nofollow" value="1"<?php if ($_GET['nofollow']) echo ' checked'?>>
                            <label for="f_nofollow">Ne pas suivre le lien (ajout d'un attribut rel="nofollow")</label>
                        </td>
                    </tr>
                </tbody>
            </table>
        </form>
    </div>
    <?php include('../include/inc.bo_bandeau_basPopup.php')?>
</body>
</html>
