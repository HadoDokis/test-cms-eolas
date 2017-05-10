<?php
require '../include/inc.bo_init.php';
require CLASS_DIR . 'class.Pagination.php';
require CLASS_DIR . 'class.db_webotheque.php';
require CLASS_DIR . 'class.File_management.php';

if ($_GET['typelien'] == 'LienExterne') {
    $oWebotheque = new Webo_LIENEXTERNE($_GET['idtf']);
    $modCode = 'MOD_WEBOTHEQUE_LIENEXTERNE';
    $titre = "Choisir un lien externe";
} elseif ($_GET['typelien'] == 'LienDocument') {
    $oWebotheque = new Webo_DOCUMENT($_GET['idtf']);
    $modCode = 'MOD_WEBOTHEQUE_DOCUMENT';
    $titre = "Choisir un document";
} elseif ($_GET['typelien'] == 'LienImage') {
    $oWebotheque = new Webo_IMAGE($_GET['idtf']);
    $modCode = 'MOD_WEBOTHEQUE_IMAGE';
    $titre = "Choisir une image";
}

if (!$oWebotheque->checkAuthorized(false)) {
    $oWebotheque->checkShareAuthorized();
}
$row = $oWebotheque->getFields();
CMS::checkAccess(new Module($modCode));
?>
<!DOCTYPE html>
<html>
<head>
    <?php include('../include/inc.bo_enTete.php')?>
    <script src="tiny_mce_popup.js"></script>
    <script src="plugins/cms/js/cms.js"></script>
    <script>
    function postControl_formCreation(oForm) {
        var f_id = <?php echo $oWebotheque->getID()?>;
        var f_typelien = '<?php echo $_GET['typelien']?>';
        var f_title = $('#f_title').val();
        var f_nofollow = $('#f_nofollow').prop('checked') ? '1' : '';
        var f_ancre = $('#WEB_CHEMINACC').prop('checked') ? '1' : '';
        var ed = tinyMCEPopup.editor;
        ed.execCommand("mceBeginUndoLevel");
        insererLien(ed, f_id, f_typelien, f_title, f_nofollow, f_ancre);
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
        <h2><?php echo $titre?></h2>
        <form method="get" action="<?php echo PHP_SELF?>" class="creation" id="formCreation">
            <table>
                <tfoot>
                    <tr>
                        <td colspan="2">
                            <input type="submit" value="<?php echo gettext('Valider')?>" class="ajouter">
                            <input type="button" value="<?php echo gettext('Retour')?>" class="retour" onclick="window.location.href='editor_select_webotheque.php?typelien=<?php echo $_GET['typelien']?>'">
                        </td>
                    </tr>
                </tfoot>
                <tbody>
                    <tr>
                        <th><label><?php echo gettext('Libelle')?></label></th>
                        <td><?php echo secureInput($row['WEB_LIBELLE'])?></td>
                    </tr>
                    <?php if ($row['WEB_TAILLE']) { ?>
                    <tr>
                        <th><label><?php echo gettext('Poids')?></label></th>
                        <td><?php echo File_management::displayFileSize($row['WEB_TAILLE']) ?></td>
                    </tr>
                    <?php } ?>
                    <?php if ($row['WEB_CHEMINACC']) {?>
                    <tr>
                        <th><label><?php echo gettext('Alternative accessible')?></label></th>
                        <td>
                            <input type="checkbox" name="WEB_CHEMINACC" id="WEB_CHEMINACC" value="1"<?php if ($_GET['ancre'] != '') echo ' checked'?>>
                            <label for="WEB_CHEMINACC">Choisir l'alternative</label>
                        </td>
                    </tr>
                        <?php if ($row['WEB_TAILLEACC']) { ?>
                    <tr>
                        <th><label><?php echo gettext('Poids')?></label></th>
                        <td><?php echo File_management::displayFileSize($row['WEB_TAILLEACC']) ?></td>
                    </tr>
                        <?php } ?>
                    <?php } ?>
                    <tr>
                        <th><label for="f_title"><?php echo gettext('Titre survol')?></label></th>
                        <td><input type="text" name="f_title" id="f_title" value="<?php echo secureInput($_GET['title'])?>" size="40"></td>
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
