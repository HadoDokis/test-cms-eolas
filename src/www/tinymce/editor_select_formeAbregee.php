<?php
require '../include/inc.bo_init.php';

CMS::checkAccess(new Module('MOD_ABREVIATION'));

require CLASS_DIR . 'class.db_abreviation.php';

$selectTagname = false;
if (isset ($_GET['f_acronyme'])) {
    $selectTagname = true;
    $sql = "select * from ABREVIATION where ABR_ABREVIATION=" . $dbh->quote(strip_tags($_GET['f_acronyme']));
    if ($row = $dbh->query($sql)->fetch(PDO :: FETCH_ASSOC)) {
        $_GET['f_title'] = $row['ABR_LIBELLE'];
        $_GET['f_lang'] = $row['ABR_LANGUE'];
        $_GET['f_tagname'] = $row['ABR_TAGNAME'];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <?php include('../include/inc.bo_enTete.php')?>
    <script src="tiny_mce_popup.js"></script>
    <script>
    function postControl_formCreation(oForm) {
        var f_title = oForm.elements['f_title'].value;
        var f_lang = oForm.elements['f_lang'].value;
        var f_tagname = oForm.elements['f_tagname'].value;

        var ed = tinyMCEPopup.editor;
        ed.execCommand("mceBeginUndoLevel");
        var elm = ed.selection.getNode();
        elm = ed.dom.getParent(elm, 'a');
        if (!elm) {
            ed.execCommand("mceInsertContent", false, '<'+ f_tagname +' lang="'+ f_lang +'" title="'+ f_title +'">'+ ed.selection.getContent({format : 'html'}) +'</'+ f_tagname +'>');
        } else {
            elm.setAttribute('title', title);
            elm.setAttribute('lang', lang);
        }
        ed.execCommand("mceEndUndoLevel");
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
        <form method="get" action="<?php echo PHP_SELF?>" class="creation" id="formCreation">
        <h2>Choisir une forme abrégée</h2>
            <table>
                <tfoot>
                    <tr>
                        <td colspan="2"><input type="submit" value="<?php echo gettext('Valider')?>" class="ajouter"></td>
                    </tr>
                </tfoot>
                <tbody>
                    <tr>
                        <th><label for="f_title"><?php echo gettext('Libelle')?></label></th>
                        <td><input name="f_title" type="text" id="f_title" value="<?php echo secureInput($_GET['f_title'])?>" size="45" maxlength="80" required></td>
                    </tr>
                    <tr>
                        <th><label for="f_lang"><?php echo gettext('Langue')?></label></th>
                        <td>
                            <select name="f_lang" id="f_lang" required>
                                <?php foreach (CMS::getLangueArray() as $key => $val) {?>
                                <option value="<?php echo $key?>"<?php if ($key == $_GET['f_lang'] || ($key==CMS::getCurrentSite()->getField('SIT_SHORT_LANGUE') && $_GET['f_lang']=='')) echo ' selected'?>><?php echo $val?></option>
                                <?php } ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="f_tagname"><?php echo gettext('Type')?></label></th>
                        <td>
                            <?php if ($selectTagname) {?>
                            <select name="f_tagname" id="f_tagname" required>
                                <option value="">&nbsp;</option>
                                <?php foreach (Abreviation::getTagnameArray() as $k=>$v) { ?>
                                <option value="<?php echo $k?>"<?php if ($k == $_GET['f_tagname']) echo ' selected'?>><?php echo $v?></option>
                                <?php } ?>
                            </select>
                            <?php } else { ?>
                            <input type="hidden" name="f_tagname" id="f_tagname" value="<?php echo secure($_GET['f_tagname'])?>">
                            <?php echo Abreviation::getTagnameArray($_GET['f_tagname'])?>
                            <?php } ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </form>
    </div>
    <?php include('../include/inc.bo_bandeau_basPopup.php')?>
</body>
</html>
