<?php
require '../include/inc.bo_init.php';
CMS::checkAccess(new Module('MOD_LANGUISME'));
?>
<!DOCTYPE html>
<html>
<head>
    <?php include('../include/inc.bo_enTete.php');?>
    <script src="tiny_mce_popup.js"></script>
    <script>
    function postControl_formCreation(oForm) {
        var f_lang = oForm.elements['f_lang'].value;
        var ed = tinyMCEPopup.editor;
        var elm = ed.selection.getNode();
        elm = ed.dom.getParent(elm, 'span');
        if (!elm) {
            ed.execCommand("mceInsertContent", false, '<span lang="'+ f_lang +'">'+ ed.selection.getContent({format : 'html'}) +'</span>');
        } else {
            elm.setAttribute('lang', f_lang);
        }
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
        <h2>Choisir une langue</h2>
        <form method="get" action="<?php echo PHP_SELF?>" class="creation" id="formCreation">
            <table>
                <tfoot>
                    <tr>
                        <td colspan="2"><input type="submit" value="<?php echo gettext('Valider')?>" class="ajouter"></td>
                    </tr>
                </tfoot>
                <tbody>
                    <tr>
                        <th><label for="f_lang"><?php echo gettext('Langue')?></label></th>
                        <td>
                            <select name="f_lang" id="f_lang" required>
                                <option value="">&nbsp;</option>
                                <?php foreach (CMS::getLangueArray() as $key => $val) { ?>
                                <option value="<?php echo $key?>"<?php if ($key == $_GET['f_lang']) echo ' selected'?>><?php echo $val?></option>
                                <?php } ?>
                            </select>
                        </td>
                    </tr>
                </tbody>
            </table>
        </form>
    </div>
    <?php include('../include/inc.bo_bandeau_basPopup.php')?>
</body>
</html>
