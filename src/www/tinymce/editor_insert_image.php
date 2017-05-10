<?php
require '../include/inc.bo_init.php';
CMS::checkAccess(new Module('MOD_WEBOTHEQUE_IMAGE'));

require CLASS_DIR . 'class.Pagination.php';
require CLASS_DIR . 'class.db_webotheque.php';

$oWebotheque = new Webo_IMAGE($_GET['idtf']);
if (!$oWebotheque->checkAuthorized(false)) {
    $oWebotheque->checkShareAuthorized();
}
$row = $oWebotheque->getFields();
?>
<!DOCTYPE html>
<html>
<head>
    <?php include('../include/inc.bo_enTete.php')?>
    <script src="tiny_mce_popup.js"></script>
    <script>
    <?php if (CMS::getCurrentSite()->hasModule(new Module('MOD_ACCESSIBILITE'))) { ?>
    function preControl_formCreation(oForm) {
        // Si il existe une description ou si une légende est renseignée, il faut placer une alternative accessible
        if (oForm.elements['WEB_DESCRIPTIONACC'].checked || formCtrl.fx.isNotNull(oForm.elements['f_legende'].value)) {
            $('#label_f_alt').addClass('isNotNull');
        } else {
            $('#label_f_alt').removeClass('isNotNull');
        }

        return true;
    }
    <?php } ?>

    function postControl_formCreation(oForm) {
        var f_src = oForm.elements['f_src'].value;
        var f_id = oForm.elements['ID_WEBOTHEQUE'].value;
        var f_alt = oForm.elements['f_alt'].value;
        if (document.getElementById('f_align_left').checked) {
            var f_align = document.getElementById('f_align_left').value;
        } else if (document.getElementById('f_align_right').checked) {
            var f_align = document.getElementById('f_align_right').value;
        } else if (document.getElementById('f_align_none').checked) {
            var f_align = document.getElementById('f_align_none').value;
        }
        var f_format = (oForm.elements['f_format']) ? oForm.elements['f_format'].value : '';
        var f_longdesc = (oForm.elements['WEB_DESCRIPTIONACC'] && oForm.elements['WEB_DESCRIPTIONACC'].checked) ? '1' : '';
        var f_credit = (oForm.elements['WEB_CREDIT'] && oForm.elements['WEB_CREDIT'].checked) ? '1' : '';
        var f_popup = (oForm.elements['f_popup'] && oForm.elements['f_popup'].checked) ? '1' : '';
        var f_legende = oForm.elements['f_legende'].value;
        if (f_format != '') {
            f_src = SERVER_ROOT + 'tinymce/editor_generate_src.php?idtf=' + f_id + '&format=' + f_format;
        }
        var ed = tinyMCEPopup.editor;
        var elm = ed.selection.getNode();
        if (elm.nodeName.toLowerCase() != 'img') {
            var html = '<img id="__mce_tmp">';
            ed.execCommand("mceInsertContent", false, html);
            elm = ed.dom.get('__mce_tmp');
        }
        elm.setAttribute('src', f_src);
        elm.setAttribute('idtf', f_id);
        elm.setAttribute('alt', f_alt);
        elm.setAttribute('align', f_align);
        elm.setAttribute('longdesc', f_longdesc);
        elm.setAttribute('credit', f_credit);
        elm.setAttribute('format', f_format);
        elm.setAttribute('popup', f_popup);
        elm.setAttribute('legende', f_legende);
        elm.setAttribute('id', '');
        tinyMCEPopup.close();

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
    }
    </script>
    <base target="_self">
</head>
<body id="popup" onload="tinyMCEPopup.executeOnLoad(Init());">
    <?php include('../include/inc.bo_bandeau_hautPopup.php')?>
    <div id="bo_contenuPopup">
        <h2>Choisir une image</h2>
        <form method="get" action="<?php echo PHP_SELF?>" class="creation" id="formCreation">
            <table>
                <tfoot>
                    <tr>
                        <td colspan="2">
                            <input type="hidden" name="f_src" id="f_src" value="<?php echo $oWebotheque->getSRC()?>">
                            <input type="hidden" name="ID_WEBOTHEQUE" id="ID_WEBOTHEQUE" value="<?php echo $oWebotheque->getID()?>">
                            <input type="submit" value="<?php echo gettext('Valider')?>" class="ajouter">
                            <input type="button" value="<?php echo gettext('Retour')?>" class="retour" onclick="window.location.href='editor_select_image.php'">
                        </td>
                    </tr>
                </tfoot>
                <tbody>
                    <tr>
                        <th><label><?php echo gettext('Libelle')?></label></th>
                        <td><?php echo secureInput($row['WEB_LIBELLE'])?></td>
                    </tr>
                    <tr>
                        <th>&nbsp;</th>
                        <td><img src="<?php echo $oWebotheque->getThumbSRC()?>" alt=""></td>
                    </tr>
                    <tr>
                        <th><label for="f_alt" id="label_f_alt"> <?php echo gettext('Alternative')?></label></th>
                        <td><input type="text" name="f_alt" id="f_alt" value="<?php echo secureInput($_GET['alt'])?>" size="40"></td>
                    </tr>
                    <tr>
                        <th><label for="f_legende"><?php echo gettext('Legende')?></label></th>
                        <td><input type="text" name="f_legende" id="f_legende" value="<?php echo secureInput($_GET['legende']);?>" size="40"></td>
                    </tr>
                    <?php if ($row['WEB_DESCRIPTIONACC'] != '') { ?>
                    <tr>
                        <th><label for="WEB_DESCRIPTIONACC"><?php echo gettext('Description longue')?></label></th>
                        <td><input type="checkbox" name="WEB_DESCRIPTIONACC" id="WEB_DESCRIPTIONACC" value="1"<?php if ($_GET['longdesc']) echo ' checked'?> class="checkbox"></td>
                    </tr>
                    <?php } ?>
                    <?php if ($row['WEB_CREDIT'] != '') { ?>
                    <tr>
                        <th><label for="WEB_CREDIT"><?php echo gettext('Masquer credit')?></label></th>
                        <td><input type="checkbox" name="WEB_CREDIT" id="WEB_CREDIT" value="1"<?php if ($_GET['credit']) echo ' checked'?> class="checkbox"></td>
                    </tr>
                    <?php } ?>
                    <tr>
                        <th><label><?php echo gettext('Alignement')?></label></th>
                        <td>
                            <input type="radio" name="f_align" id="f_align_left" value="left"<?php if ($_GET['align'] == 'left' || $_GET['align'] == '') echo ' checked'?>>
                            <label for="f_align_left"><img src="<?php echo SERVER_ROOT ?>images/align_left.png" alt=""> <?php echo gettext('gauche')?></label>
                            <input type="radio" name="f_align" id="f_align_right" value="right"<?php if ($_GET['align'] == 'right') echo ' checked'?>>
                            <label for="f_align_right"><img src="<?php echo SERVER_ROOT ?>images/align_right.png" alt=""> <?php echo gettext('droite')?></label>
                            <input type="radio" name="f_align" id="f_align_none" value="none"<?php if ($_GET['align'] == 'none' || $_GET['align'] == 'null') echo ' checked'?>>
                            <label for="f_align_none"><img src="<?php echo SERVER_ROOT ?>images/align_none.png" alt=""> <?php echo gettext('aucun')?></label>
                        </td>
                    </tr>
                    <?php
                    $sql = "select * from DD_IMAGEFORMAT
                        where IMF_AFFECTABLE=1 and GAB_CODE=" . $dbh->quote(CMS::getCurrentSite()->getField('GAB_CODE')) . "
                        order by IMF_LIBELLE";
                    $aFormat = $dbh->query($sql)->fetchAll(PDO :: FETCH_ASSOC);
                    if (sizeof($aFormat) > 0) { ?>
                    <tr>
                        <th><label for="f_format"><?php echo gettext('Format')?></label></th>
                        <td>
                            <select name="f_format" id="f_format">
                                <option value=""><?php echo gettext('Original')?> (<?php echo $row['WEB_LARGEUR']?>*<?php echo $row['WEB_HAUTEUR']?>)</option>
                                <?php foreach ($aFormat as $rowTemp) {?>
                                <option value="<?php echo $rowTemp['IMF_CODE']?>"<?php if ($_GET['format'] == $rowTemp['IMF_CODE']) echo ' selected'?>><?php echo extraireLibelle($rowTemp['IMF_LIBELLE'])?></option>
                                <?php } ?>
                            </select>
                            <input type="checkbox" name="f_popup" id="f_popup" value="1"<?php if ($_GET['popup'] != '') echo ' checked'?> class="checkbox">
                            <label for="f_popup"><?php echo gettext('Ouvrir en grand')?></label>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </form>
    </div>
    <?php include('../include/inc.bo_bandeau_basPopup.php')?>
</body>
</html>
