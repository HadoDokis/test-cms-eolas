<?php
require '../include/inc.bo_init.php';
CMS::checkAccess(new Module('MOD_CORE'), array('PRO_ROOT_SITE'));
require CLASS_DIR . 'class.db_styleDynamique.php';

$oStyleDynamique = new StyleDynamique($_GET['idtf']);
$row = $oStyleDynamique->getFields();
?>
<!DOCTYPE html>
<html>
<head>
    <?php include('../include/inc.bo_enTete.php') ?>
</head>
<body id="popup">
    <?php include('../include/inc.bo_bandeau_hautPopup.php')?>
    <div id="bo_contenuPopup">
        <h2><?php echo gettext('Nouveau style dynamique')?></h2>
        <form method="post" action="cms_styleDynamiquePopupSubmit.php" class="creation">
            <fieldset>
                <legend><?php echo gettext('Informations')?></legend>
                <table>
                    <tfoot>
                        <tr>
                            <td colspan="2">
                                <?php if ($oStyleDynamique->exist()) { ?>
                                <input type="hidden" name="idtf" value="<?php echo $oStyleDynamique->getID()?>">
                                <input type="submit" name="Update" value="<?php echo gettext('UPDATE')?>" class="modifier">
                                <input type="button" name="Delete" value="<?php echo gettext('DELETE')?>" class="supprimer"<?php if (!$oStyleDynamique->isDeletable()) echo ' disabled'?> onclick="if (confirm('<?php echo gettext('Etes-vous sur ?')?>')) window.location.href='cms_styleDynamiquePopupSubmit.php?Delete=<?php echo $oStyleDynamique->getID()?>'">
                                <?php } else { ?>
                                <input type="submit" name="Insert" value="<?php echo gettext('INSERT')?>" class="ajouter">
                                <?php } ?>
                            </td>
                        </tr>
                    </tfoot>
                    <tbody>
                        <tr>
                            <th><label for="STY_LIBELLE"><?php echo gettext('Libelle')?></label></th>
                            <td><input name="STY_LIBELLE" type="text" id="STY_LIBELLE" value="<?php echo secureInput($row['STY_LIBELLE'])?>" size="30" required></td>
                        </tr>
                        <tr>
                            <th><label for="STY_CSS"><?php echo gettext('Style')?></label></th>
                            <td><textarea name="STY_CSS" id="STY_CSS" style="width:100%" rows="20" cols="50"><?php echo secureInput($row['STY_CSS'])?></textarea></td>
                        </tr>
                    </tbody>
                </table>
            </fieldset>
        </form>
    </div>
    <?php include('../include/inc.bo_bandeau_basPopup.php')?>
</body>
</html>
