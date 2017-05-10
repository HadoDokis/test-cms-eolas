<?php
require '../include/inc.bo_init.php';
CMS::checkAccess(new Module('MOD_THEMATIQUE'), array ('PRO_THEMATIQUE'));
require CLASS_DIR . 'class.db_thematique.php';
require CLASS_DIR . 'class.db_page.php';

$oThematique = new Thematique($_GET['idtf']);
if ($oThematique->exist()) {
    $oThematique->checkAuthorized();
}
$row = $oThematique->getFields();
?>
<!DOCTYPE html>
<html>
<head>
<?php include('../include/inc.bo_enTete.php') ?>

</head>
<body>
<div id="document">
    <?php $aMenuKey = array('CFG', 'SITE', 'MOD_THEMATIQUE'); if (!$oThematique->exist()) $aMenuKey[]='ADD'; include('../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">
            <h2><?php echo ($row['THE_LIBELLE'] != '') ? secureInput($row['THE_LIBELLE']) : gettext('Nouvelle thematique');?></h2>
            <form method="post" action="cms_thematiqueSubmit.php" class="creation">
                <fieldset>
                <legend><?php echo gettext('Informations')?></legend>
                    <table>
                        <tfoot>
                            <tr>
                                <td colspan="2">
                                    <?php if ($oThematique->exist()) { ?>
                                    <input type="hidden" name="idtf" value="<?php echo $oThematique->getID()?>">
                                    <input type="submit" name="Update" value="<?php echo gettext('UPDATE')?>" class="modifier">
                                    <input type="hidden" name="Update" value="<?php echo gettext('UPDATE')?>">
                                    <input type="button" name="Delete" value="<?php echo gettext('DELETE')?>" class="supprimer"<?php if (!$oThematique->isDeletable()) echo ' disabled'?> onclick="if (confirm('<?php echo gettext('Etes-vous sur ?')?>')) window.location.href='cms_thematiqueSubmit.php?Delete=<?php echo $oThematique->getID()?>'">
                                    <?php } else { ?>
                                    <input type="submit" name="Insert" value="<?php echo gettext('INSERT')?>" class="ajouter">
                                    <input type="hidden" name="Insert" value="<?php echo gettext('INSERT')?>">
                                    <?php } ?>
                                </td>
                            </tr>
                        </tfoot>
                        <tbody>
                            <tr>
                                <th><label for="THE_LIBELLE"><?php echo gettext('Libelle')?></label></th>
                                <td><input type="text" name="THE_LIBELLE" id="THE_LIBELLE" value="<?php echo secureInput($row['THE_LIBELLE'])?>" size="50" required></td>
                            </tr>
                        </tbody>
                    </table>
                </fieldset>
                <?php
                if ($oThematique->exist()) {
                    foreach ($oThematique->getAffectes() as $LIA_LIBELLE => $row) {?>
                <fieldset>
                    <legend><?php echo secureInput($LIA_LIBELLE);?></legend>
                    <ul>
                    <?php foreach ($row as $affecte) {?>
                        <?php if ($affecte['LIA_CODE']=='OFF_PAGE' || $affecte['LIA_CODE']=='ON_PAGE') { ?>
                        <li><a href="cms_page.php?idtf=<?php echo $affecte['ID_LIAISON'] ?>"><?php echo secureInput($affecte['LIBELLE_AFFECTE']); ?> (<?php echo $affecte['ID_LIAISON'] ?>)</a></li>
                        <?php } else { ?>
                        <li><?php echo secureInput($affecte['LIBELLE_AFFECTE']); ?> (<?php echo $affecte['ID_LIAISON'] ?>)</li>
                        <?php } ?>
                    <?php } ?>
                    </ul>
                </fieldset>
                <?php
                    }
                } ?>
            </form>
        </div>
    </div>
    <?php include('../include/inc.bo_bandeau_bas.php')?>
</div>
</body>
</html>
