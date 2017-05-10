<?php
require '../../include/inc.bo_init.php';
require CLASS_DIR . 'class.db_alerte.php';
CMS::checkAccess(new Module('MOD_CORE'), array('PRO_ROOT_SITE'));
if (($_GET['idtf'] == '') && Utilisateur::getConnected()->isRoot(true)) {
    $h2 = gettext('Alerte_plateforme');
    $SIT_CODE = '';
} else {
    $h2 = gettext('Alerte_site');
    $SIT_CODE = CMS::getCurrentSite()->getID();
}
$oAlerte = new Alerte($SIT_CODE);
$row = $oAlerte->getFields();
?>
<!DOCTYPE html>
<html>
<head>
<?php include('../../include/inc.bo_enTete.php') ?>
</head>
<body>
<div id="document">
    <?php $aMenuKey = array('CFG', (empty($SIT_CODE) ? 'PTF' : 'SITE'), 'ALERTE'); include('../../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">
            <h2><?php echo secureInput($h2)?></h2>
            <form method="post" action="adm_alerteSubmit.php" class="creation">
                <fieldset>
                    <legend><?php echo gettext('Informations')?></legend>
                    <table>
                        <tfoot>
                            <tr>
                                <td colspan="2">
                                    <input type="hidden" name="idtf" value="<?php echo secureInput($SIT_CODE)?>">
                                    <?php if ($oAlerte->exist()) { ?>
                                    <input type="submit" name="Update" value="<?php echo gettext('UPDATE')?>" class="modifier">
                                    <input type="hidden" name="Update" value="1">
                                    <input type="button" name="Delete" value="<?php echo gettext('DELETE')?>" class="supprimer<?php if (!$oAlerte->isDeletable()) echo ' disabled'?>" onclick="if (confirm('<?php echo gettext('Etes-vous sur ?')?>')) window.location.href='adm_alerteSubmit.php?Delete=<?php echo $oAlerte->getID()?>'"<?php if (!$oAlerte->isDeletable()) echo ' disabled'?>>
                                    <?php } else { ?>
                                    <input type="submit" name="Insert" value="<?php echo gettext('INSERT')?>" class="ajouter">
                                    <input type="hidden" name="Insert" value="1">
                                    <?php } ?>
                                </td>
                            </tr>
                        </tfoot>
                        <tbody>
                            <tr>
                                <th><label for="ALT_MESSAGE">Message</label> <div class="helper">Texte restitué sur le tableau de bord</div></th>
                                <td><textarea name="ALT_MESSAGE" id="ALT_MESSAGE" cols="80" rows="10" required><?php echo secureInput($row['ALT_MESSAGE'])?></textarea></td>
                            </tr>
                            <tr>
                                <th><label>Date de blocage</label> <div class="helper">Date à partir de laquelle le site est bloqué</div></th>
                                <td>
                                    <input name="ALT_DATE" type="text" id="ALT_DATE" value="<?php if ($row['ALT_DATE'] != '' ) echo date('d/m/Y', $row['ALT_DATE'])?>" data-type="date" data-subtype="now">
                                    (
                                    <select name="ALT_DATE_HEURE">
                                        <?php for ($i=0; $i<24; $i++) {?>
                                        <option value="<?php echo $i?>"<?php if ($row['ALT_DATE'] != '' && date('H', $row['ALT_DATE']) == $i) echo ' selected';?>><?php echo str_pad($i, 2, '0', STR_PAD_LEFT)?></option>
                                        <?php } ?>
                                    </select>h
                                    <select name="ALT_DATE_MINUTE">
                                        <?php for ($i=0; $i<60; $i++) {?>
                                        <option value="<?php echo $i?>"<?php if ($row['ALT_DATE'] != '' && date('i', $row['ALT_DATE']) == $i) echo ' selected';?>><?php echo str_pad($i, 2, '0', STR_PAD_LEFT)?></option>
                                        <?php } ?>
                                    </select>min
                                    )
                                    <br>Si non renseignée, alors l'alerte n'est pas bloquante
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </fieldset>
            </form>
        </div>
    </div>
    <?php include('../../include/inc.bo_bandeau_bas.php')?>
</div>
</body>
</html>
