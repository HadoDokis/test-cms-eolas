<?php
require '../include/inc.bo_init.php';
require CLASS_DIR . 'class.db_formulaireCategorie.php';

CMS::checkAccess(new Module('MOD_FORMULAIRE'), array('PRO_FORMGEST'));

if (isset($_GET['idtfParent'])) {
    $oCategorieParent = new FormulaireCategorie($_GET['idtfParent']);
    $oCategorieParent->checkAuthorized();
    $row['CAT_IDPARENT'] = $oCategorieParent->getID();

} else {
    $oCategorie = new FormulaireCategorie($_GET['idtf']);
    $oCategorie->checkAuthorized();
    $row = $oCategorie->getFields();
}
?>
<!DOCTYPE html>
<html>
<head>
    <?php include('../include/inc.bo_enTete.php') ?>
</head>
<body>
<div id="document">
    <?php $aMenuKey = array('FRM', 'CAT'); include('../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">
            <h2>Dossiers</h2>
            <form method="post" action="frm_categorieSubmit.php" class="creation">
                <fieldset>
                    <legend><?php echo gettext('Proprietes')?></legend>
                    <table>
                        <tfoot>
                            <tr>
                                <td colspan="2">
                                    <?php if ($oCategorie) { ?>
                                    <input type="hidden" name="idtf" value="<?php echo $oCategorie->getID() ?>">
                                    <input type="submit" name="Update" value="<?php echo gettext('UPDATE')?>" class="modifier">
                                    <input type="button" name="Delete" value="<?php echo gettext('DELETE')?>" class="supprimer"<?php if (!$oCategorie->isDeletable()) echo ' disabled'?> onclick="if (confirm('<?php echo gettext('Etes-vous sur ?')?>')) window.location.href='frm_categorieSubmit.php?Delete=<?php echo $oCategorie->getID()?>'">
                                    <?php } else { ?>
                                    <input type="submit" name="Insert" value="<?php echo gettext('INSERT')?>" class="ajouter">
                                    <?php } ?>
                                </td>
                            </tr>
                        </tfoot>
                        <tbody>
                            <tr>
                                <th><label for="CAT_LIBELLE"><?php echo gettext('Libelle')?></label></th>
                                <td><input name="CAT_LIBELLE" type="text" id="CAT_LIBELLE" value="<?php echo secureInput($row['CAT_LIBELLE'])?>" size="40" required></td>
                            </tr>
                            <?php if ($row['CAT_IDPARENT']) { ?>
                            <tr>
                                <th><label for="CAT_IDPARENT"><?php echo gettext('Placement')?></label></th>
                                <td>
                                    <select name="CAT_IDPARENT" id="CAT_IDPARENT" required>
                                    <?php echo FormulaireCategorie::getSelectOptions($row['CAT_IDPARENT'], null, $row['ID_FORMULAIRECATEGORIE']) ?>
                                    </select>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </fieldset>
            </form>
        </div>
    </div>
    <?php include('../include/inc.bo_bandeau_bas.php')?>
</div>
</body>
</html>
