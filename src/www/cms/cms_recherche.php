<?php
require '../include/inc.bo_init.php';
CMS::checkAccess(new Module('MOD_RECHERCHE'), array ('PRO_ROOT_SITE'));
?>
<!DOCTYPE html>
<html>
<head>
    <?php include('../include/inc.bo_enTete.php') ?>
    <script>
    function postControl_formCreation(oForm)
    {
        selectAll('SIT_CODE');
        return true;
    }
    </script>
</head>
<body>
<div id="document">
    <?php $aMenuKey = array('CFG', 'SITE', 'MOD_RECHERCHE', 'MULTI'); include('../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">
            <h2>Recherche Multi-sites</h2>
            <form method="post" action="cms_rechercheSubmit.php" id="formCreation" class="creation">
                <fieldset>
                    <legend><?php echo gettext('Informations')?></legend>
                        <table>
                            <tfoot>
                                <tr>
                                  <td colspan="2"><input type="submit" name="Update" value="<?php echo gettext('UPDATE')?>" class="modifier"></td>
                                </tr>
                            </tfoot>
                            <tbody>
                                <tr>
                                <th><label><?php echo gettext('Sites')?></label></th>
                                <td>
                                    <table class="selection">
                                        <tr>
                                            <th><?php echo gettext('Affecte(s)')?></th>
                                            <th>&nbsp;</th>
                                            <th><?php echo gettext('Disponible(s)')?></th>
                                        </tr>
                                        <tr>
                                            <td>
                                                <select name="SIT_CODE[]" id="SIT_CODE" size="6" multiple ondblclick="DeplaceCritere(document.getElementById('SIT_CODE'), document.getElementById('SIT_CODE_ALL'));">
                                                <?php
                                                $aRevertSharedSite = CMS::getCurrentSite()->getRevertSharedSites();
                                                $sql = "select * from DD_SITE where SIT_CODE in ('" . str_replace('@', "','", CMS::getCurrentSite()->getField('SIT_RECHERCHE')) . "') order by SIT_LIBELLE";
                                                foreach ($dbh->query($sql, PDO :: FETCH_ASSOC) as $rowTemp) {
                                                    unset($aRevertSharedSite[$rowTemp['SIT_CODE']]);?>
                                                    <option value="<?php echo $rowTemp['SIT_CODE']?>"><?php echo secureInput($rowTemp['SIT_LIBELLE'])?></option>
                                                <?php } ?>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="button" name="Button" value="&lt;&lt;" onclick="DeplaceCritere(document.getElementById('SIT_CODE_ALL'), document.getElementById('SIT_CODE'));">
                                                <input type="button" name="Button2" value="&gt;&gt;" onclick="DeplaceCritere(document.getElementById('SIT_CODE'), document.getElementById('SIT_CODE_ALL'));">
                                            </td>
                                            <td>
                                                <select name="SIT_CODE_ALL[]" id="SIT_CODE_ALL" size="6" multiple ondblclick="DeplaceCritere(document.getElementById('SIT_CODE_ALL'), document.getElementById('SIT_CODE'));">
                                                <?php foreach ($aRevertSharedSite as $SIT_CODE=>$_oSite) {?>
                                                    <option value="<?php echo $SIT_CODE?>"><?php echo secureInput($_oSite->getField('SIT_LIBELLE'))?></option>
                                                <?php } ?>
                                                </select>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
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
