<?php
require '../include/inc.bo_init.php';
require CLASS_DIR . 'class.Pagination.php';
require CLASS_DIR . 'class.db_webotheque.php';

if (isset($_POST['isMULTI']) || strpos($_GET['idtf'], '@') != 0) {
    $aIdtf = isset($_POST['isMULTI']) ? $_POST['ID_WEBOTHEQUE'] : explode('@', $_GET['idtf']);
    $row['WEB_LARGEUR'] = 160;//en dur ensuite dans le parsing
    $row['WEB_HAUTEUR'] = 20;//en dur ensuite dans le parsing
    $row['WEB_LIBELLE'] = 'Sélection de plusieurs fichiers';
    foreach ($aIdtf as $idtf) {
        $oWebotheque = new Webotheque($idtf);
        if (!$oWebotheque->checkAuthorized(false)) {
            $oWebotheque->checkShareAuthorized();
        }
    }
    $ID_WEBOTHEQUE = implode('@', $aIdtf);
} else {
    $oWebotheque = new Webotheque($_GET['idtf']);
    if (!$oWebotheque->checkAuthorized(false)) {
        $oWebotheque->checkShareAuthorized();
    }
    $row = $oWebotheque->getFields();
    $ID_WEBOTHEQUE = $oWebotheque->getID();
}
CMS::checkAccess(new Module(str_replace('WBT_', 'MOD_WEBOTHEQUE_', $oWebotheque->getField('WBT_CODE'))));

//format
if (in_array($oWebotheque->getField('WBT_CODE'), array('WBT_FLASH', 'WBT_VIDEO'))) {
    $sql = "select * from DD_IMAGEFORMAT
        where IMF_AFFECTABLE=1 and GAB_CODE=" . $dbh->quote(CMS::getCurrentSite()->getField('GAB_CODE')) . "
        order by IMF_LIBELLE";
    $aFormat = $dbh->query($sql)->fetchAll(PDO :: FETCH_ASSOC);
} else {
    $aFormat = array();
}

if ($oWebotheque->getField('WBT_CODE') == 'WBT_FLASH') {
    $titre = "Choisir un élément flash";
} elseif ($oWebotheque->getField('WBT_CODE') == 'WBT_MUSIC') {
    $titre = "Choisir un élément audio";
} elseif ($oWebotheque->getField('WBT_CODE') == 'WBT_VIDEO') {
    $titre = "Choisir une vidéo";
} elseif ($oWebotheque->getField('WBT_CODE') == 'WBT_VIDEOEXTERNE') {
    $tire = "Choisir une vidéo externe";
} else {
    $titre = "Choisir un élément de webothèque";
}
?>
<!DOCTYPE html>
<html>
<head>
    <?php include('../include/inc.bo_enTete.php')?>
    <script src="tiny_mce_popup.js"></script>
    <script src="plugins/cms/js/cms.js"></script>
    <script>
    function postControl_formCreation(oForm) {
        var largeur = '<?php echo $row['WEB_LARGEUR']?>';
        var hauteur = '<?php echo $row['WEB_HAUTEUR']?>';
        var f_format = '';
        var f_align = oForm.elements['f_align'].value;
        var ed = tinyMCEPopup.editor;
        ed.execCommand("mceBeginUndoLevel");
        insererMedia(ed, '<?php echo $ID_WEBOTHEQUE ?>', '<?php echo $oWebotheque->getField('WBT_CODE')?>', largeur , hauteur, f_align, f_format);
        ed.execCommand('mceEndUndoLevel');
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
                            <input type="button" value="<?php echo gettext('Retour')?>" class="retour" onclick="window.location.href='editor_select_media.php?WBT_CODE=<?php echo $oWebotheque->getField('WBT_CODE')?>'">
                        </td>
                    </tr>
                </tfoot>
                <tbody>
                    <tr>
                        <th><label><?php echo gettext('Libelle')?></label></th>
                        <td><?php echo secureInput($row['WEB_LIBELLE'])?></td>
                    </tr>
                    <tr>
                        <th><label for="f_align">Alignement</label></th>
                        <td>
                            <select name="f_align" id="f_align">
                                <option value="">100%</option>
                                <option value="left"<?php if ($_GET['align'] == 'left') echo ' selected'?>>50% gauche</option>
                                <option value="right"<?php if ($_GET['align'] == 'right') echo ' selected'?>>50% droite</option>
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
