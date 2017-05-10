<?php
require '../include/inc.bo_init.php';
CMS::checkAccess(new Module('MOD_WEBOTHEQUE_VIDEOEXTERNE'), array('PRO_WEBVIDEOEXTERNE', 'PRO_WEBROOT'));
require CLASS_DIR . 'class.db_webotheque.php';
require CLASS_DIR . 'class.db_webothequeCategorie.php';
require CLASS_DIR . 'class.Editor.php';

$oWebotheque = new Webo_VIDEOEXTERNE($_GET['idtf']);
if ($oWebotheque->exist()) {
    $oWebotheque->checkAuthorized();
}
$row = $oWebotheque->getFields();
?>
<!DOCTYPE html>
<html>
<head>
    <?php include('../include/inc.bo_enTete.php') ?>
    <?php Editor::header();?>
    <script src="<?php echo SERVER_ROOT ?>include/js/ajx_liaisonWeboModule.js"></script>
    <script>
        $(document).ready(function () {ajaxLiaisonWeboModule.init(<?php echo $oWebotheque->getID()?>);});
        <?php if (WEB_DESCRIPTION) { ?>
        editorInit('paragraphe', new Array('WEB_DESCRIPTION'));
        <?php } ?>
    </script>
</head>
<body>
<div id="document">
    <?php $aMenuKey = array('WEB', 'MOD_WEBOTHEQUE_VIDEOEXTERNE', 'VIDEOEXTERNE'); if (!$oWebotheque->exist()) $aMenuKey[]='ADD'; include('../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">
            <h2><?php echo $oWebotheque->exist() ? secureInput($row['WEB_LIBELLE']) : 'Nouvelle vidéo externe' ?></h2>
            <form method="post" action="web_webothequeSubmit.php" class="creation" enctype="multipart/form-data">
                <fieldset>
                    <legend><?php echo gettext('Proprietes')?></legend>
                    <table>
                        <tfoot>
                            <tr>
                                <td colspan="2">
                                    <input type="hidden" name="WBT_CODE" value="<?php echo $oWebotheque->code?>">
                                    <?php if (!$oWebotheque->exist()) { ?>
                                    <input type="submit" name="Insert" value="<?php echo gettext('INSERT')?>" class="ajouter">
                                    <?php } else { ?>
                                    <input type="hidden" name="idtf"  value="<?php echo $oWebotheque->getID();?>">
                                    <input type="submit" name="Update" value="<?php echo gettext('UPDATE')?>" class="modifier">
                                    <input type="button" name="Delete" value="<?php echo gettext('DELETE')?>" class="supprimer"<?php if (!$oWebotheque->isDeletable()) echo ' disabled'?> onclick="if (confirm('<?php echo gettext('Etes-vous sur ?')?>')) window.location.href='web_webothequeSubmit.php?Delete=<?php echo $oWebotheque->getID()?>&amp;WBT_CODE=<?php echo $oWebotheque->code?>'">
                                    <?php } ?>
                                </td>
                            </tr>
                        </tfoot>
                        <tbody>
                            <?php if ($oWebotheque->exist()) { ?>
                            <tr>
                                <th><label><?php echo gettext('Dernier redacteur')?></label></th>
                                <td><?php echo secureInput($oWebotheque->getUtilisateurInfo() . ' - ' . date('d/m/Y H:i', $oWebotheque->getField('WEB_DATEMODIFICATION')))?></td>
                            </tr>
                            <?php } ?>
                            <tr>
                                <th><label for="WEB_LIBELLE"><?php echo gettext('Libelle')?></label></th>
                                <td><input name="WEB_LIBELLE" type="text" id="WEB_LIBELLE" value="<?php echo secureInput($row['WEB_LIBELLE'])?>" size="40" required></td>
                            </tr>
                            <tr>
                                <th><label for="<?php echo (WebothequeCategorie::getNb($oWebotheque->code) == 0) ? 'CAT_LIBELLE' : 'ID_WEBOTHEQUECATEGORIE'?>"><?php echo gettext('Categorie')?></label></th>
                                <td>
                                    <select name="ID_WEBOTHEQUECATEGORIE" id="ID_WEBOTHEQUECATEGORIE"<?php if (WebothequeCategorie::getNb($oWebotheque->code) != 0) echo ' required'?>>
                                        <option value="">&nbsp;</option>
                                        <?php echo WebothequeCategorie::getSelectOptions($oWebotheque->code, $row['ID_WEBOTHEQUECATEGORIE']) ?>
                                    </select>
                                    <input type="text" name="CAT_LIBELLE" id="CAT_LIBELLE" size="30" placeholder="<?php echo gettext('Ajouter_dossier')?>"<?php if (WebothequeCategorie::getNb($oWebotheque->code) == 0) echo ' required'?>>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="WEB_DESCRIPTIONACC"><?php echo gettext('Code')?></label></th>
                                <td><textarea id="WEB_DESCRIPTIONACC" name="WEB_DESCRIPTIONACC" rows="12" cols="60" required><?php echo secureInput($row['WEB_DESCRIPTIONACC'])?></textarea></td>
                            </tr>
                            <?php if (WEB_DESCRIPTION) {?>
                            <tr>
                                <th><label for="WEB_DESCRIPTION"><?php echo gettext('Description')?></label></th>
                                <td><textarea id="WEB_DESCRIPTION" name="WEB_DESCRIPTION" style="width:100%" rows="20" cols="60"><?php echo secureInput($row['WEB_DESCRIPTION'])?></textarea></td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </fieldset>
                <?php $oWebotheque->genereReferantsListe(); $oWebotheque->genereReferantsListe(false);?>
            </form>
        </div>
    </div>
    <?php include('../include/inc.bo_bandeau_bas.php')?>
</div>
</body>
</html>
