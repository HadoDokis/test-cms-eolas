<?php
require '../../include/inc.bo_init.php';
CMS::checkAccess(new Module('MOD_CORE'), array('PRO_ROOT'));

if (empty($_GET['LNG_CODE'])) {
    $_GET['LNG_CODE'] = CMS::getCurrentSite()->getField('SIT_LANGUE');
}

$sql = "select * from STOPWORD_LANGUE where STP_LIBELLE=" . $dbh->quote($_GET['idtf']) . " and LNG_CODE=" . $dbh->quote($_GET['LNG_CODE']);
if (!$row = $dbh->query($sql)->fetch(PDO :: FETCH_ASSOC)) {
    $_GET['idtf'] = -1;
}
?>
<!DOCTYPE html>
<html>
<head>
<?php include('../../include/inc.bo_enTete.php') ?>
</head>
<body>
<div id="document">
    <?php $aMenuKey = array('CFG', 'PTF', 'STOPWORD'); if ($_GET['idtf'] == -1) $aMenuKey[] = 'ADD_' . $_GET['LNG_CODE']; include('../../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">
            <h2><?php echo gettext('Stopword')?></h2>
            <form method="post" action="adm_stopwordSubmit.php" class="creation">
                <fieldset>
                    <legend><?php echo gettext('Informations')?></legend>
                    <table>
                        <tfoot>
                            <tr>
                                <td colspan="2">
                                    <input type="hidden" name="LNG_CODE" value="<?php echo secureInput($_GET['LNG_CODE'])?>">
                                    <?php if ($_GET['idtf'] != -1) { ?>
                                    <input type="hidden" name="idtf" value="<?php echo secureInput($_GET['idtf'])?>">
                                    <input type="submit" name="Update" value="<?php echo gettext('UPDATE')?>" class="modifier">
                                    <input type="button" name="Delete" value="<?php echo gettext('DELETE')?>" class="supprimer" onclick="if (confirm('<?php echo gettext('Etes-vous sur ?')?>')) window.location.href='adm_stopwordSubmit.php?Delete=<?php echo secureInput($_GET['idtf'])?>&amp;LNG_CODE=<?php echo secureInput($_GET['LNG_CODE'])?>'">
                                    <?php } else { ?>
                                    <input type="submit" name="Insert" value="<?php echo gettext('INSERT')?>" class="ajouter">
                                    <?php } ?>
                                </td>
                            </tr>
                        </tfoot>
                        <tbody>
                            <tr>
                                <th><label for="STP_LIBELLE"><?php echo gettext('Libelle')?></label></th>
                                <td><input name="STP_LIBELLE" type="text" id="STP_LIBELLE" value="<?php echo secureInput($row['STP_LIBELLE'])?>" size="30" required></td>
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
