<?php
require '../../include/inc.bo_init.php';
CMS::checkAccess(new Module('MOD_CORE'), array('PRO_ROOT'));
require CLASS_DIR . 'class.db_template.php';

$oTemplate = new Template($_GET['idtf']);
if (!$oTemplate->exist()) {
    header('Location:' . SERVER_ROOT . 'cms/administration/adm_templateListe.php');
    exit();
}
$row = $oTemplate->getFields();
$aKey = $oTemplate->getKeys();
?>
<!DOCTYPE html>
<html>
<head>
<?php include('../../include/inc.bo_enTete.php')?>
<script>
function postControl_formCreation(oForm)
{
    var aUnwantedRegExp  = new Array(":", "/", "?", "#", "[", "\\]", "@", "!", "$", "&", "'",   "(", ")", "*", "+", ",", ";", "=", '\\s',    "<", ">", "\"",   "%", "{", "}", "|", "\\\\", "\\^", "~", "`");
    var aUnwantedLiteral = new Array(":", "/", "?", "#", "[", "]",   "@", "!", "$", "&", " ' ", "(", ")", "*", "+", ",", ";", "=", 'Espace', "<", ">", " \" ", "%", "{", "}", "|", "\\",   "^",   "~", "`");
    for (var j = 0; j < aUnwantedRegExp.length ; j++) {
        var r = new RegExp('[' + aUnwantedRegExp[j] + ']', 'i');
        if (document.getElementById('TPL_URLCODE').value.match(r)) {
            alert("<?php printf(escapeJS(gettext('Le caractere %s est interdit')), '\'" + aUnwantedLiteral[j] + "\'')?>");
            document.getElementById('TPL_URLCODE').focus();

            return false;
        }
        if (aUnwantedLiteral[j] != '[' && aUnwantedLiteral[j] != ']' && document.getElementById('TPL_REWRITEURL').value.match(r)) {
            alert("<?php printf(escapeJS(gettext('Le caractere %s est interdit')), '\'" + aUnwantedLiteral[j] + "\'')?>");
            document.getElementById('TPL_REWRITEURL').focus();

            return false;
        }
    }

    var aUsed = new Array();
    <?php
    $sql = "select * from DD_TEMPLATE
        where TPL_URLCODE is not null and TPL_URLCODE <> '' and TPL_CODE <>" . $dbh->quote($oTemplate->getID());
    foreach ($dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $rowListe) { ?>
        aUsed[aUsed.length] = '<?php echo escapeJS(secureInput($rowListe['TPL_URLCODE'])) ?>';
    <?php } ?>

    for (i = 0; i < aUsed.length; i++) {
        var r = new RegExp('^' + aUsed[i] + '$', 'i');
        if (document.getElementById('TPL_URLCODE').value.match(r)) {
            alert('"' + document.getElementById('TPL_URLCODE').value + ' est déjà utilisé pour un autre template');
            return false;
        }
    }

    var aInput = document.getElementsByTagName('INPUT').getElementsByClassName('param');
    var aCaracteresInterdits = new Array('@', ':');
    for (var i = 0 ; i < aInput.length ; i++) {
        if (aInput[i].type=='text') {
            for (var j = 0 ; j < aCaracteresInterdits.length ; j++) {
                if (aInput[i].value.match(aCaracteresInterdits[j])) {
                    alert("<?php printf(escapeJS(gettext('Le caractere %s est interdit')), '\'" + aCaracteresInterdits[j] + "\'')?>");
                    aInput[i].focus();
                    return false;
                }
            }
        }
    }
    return true;
}
</script>
</head>
<body>
<div id="document">
    <?php $aMenuKey = array('CFG', 'PTF', 'TPL'); include('../../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">
            <h2>
                <a href="adm_templateListe.php?MOD_CODE=<?php echo $oTemplate->getModule()->getID()?>&amp;Find=1"><?php echo secureInput(extraireLibelle($oTemplate->getModule()->getField('MOD_LIBELLE')))?></a> :
                <?php echo secureInput(extraireLibelle($row['TPL_LIBELLE']))?>
            </h2>
            <form method="post" action="adm_templateSubmit.php" id="formCreation" class="creation">
                <fieldset>
                    <legend>Propriétés</legend>
                    <table>
                        <tfoot>
                            <tr>
                                <td colspan="2">
                                    <input type="hidden" name="idtf" value="<?php echo secureInput($oTemplate->getID())?>">
                                    <input type="submit" name="Update" value="<?php echo gettext('UPDATE')?>" class="modifier">
                                </td>
                            </tr>
                        </tfoot>
                        <tbody>
                            <tr>
                                <th><label for="TPL_URLCODE">Nom du template dans URL</label></th>
                                <td><input type="text" name="TPL_URLCODE" id="TPL_URLCODE" value="<?php echo secureInput($row['TPL_URLCODE'])?>" size="40"></td>

    <?php if ($aKey) { ?>
        <?php if ($oTemplate->getField('TPL_REWRITEMETHOD')) { ?>
                                <th><label for="TPL_REWRITEURL">URL personnalisée</label></th>
                                <td><input type="text" name="TPL_REWRITEURL" id="TPL_REWRITEURL" value="<?php echo secureInput($row['TPL_REWRITEURL'])?>" size="50"></td>
        <?php } ?>
                            <?php
                            $i = 0 ;
                            foreach ($aParam = $oTemplate->getProperties() as $key => $val) {
                                if ($i % 2 == 0) {
                                    echo '</tr><tr>';
                                }?>
                                <th><label for="VALEUR_PARAM_<?php echo $i ?>"><?php echo secureInput($key) ?></label></th>
                                <td>
                                    <?php if (!empty($_GET['caractereInterdit']) && $_GET['caractereInterdit'] == 'VALEUR_PARAM_' . $i) {?>
                                    <p class="alert"><?php echo gettext('Caracteres interdits') ?> : "@", ":"</p>
                                    <?php } ?>
                                    <input class="param" type="text" name="VALEUR_PARAM_<?php echo $i ?>" id="VALEUR_PARAM_<?php echo $i ?>" value="<?php echo secureInput($val)?>" size="60">
                                    <input type="hidden" name="LIBELLE_PARAM_<?php echo $i ?>" value="<?php echo secureInput($key)?>">
                                </td>

                            <?php
                                $i++;
                            } ?>

    <?php } ?>
                            </tr>
                        </tbody>
                    </table>
                </fieldset>

    <?php if ($aKey) { ?>
                <fieldset class="demiLeft">
                    <legend>Clés prédéfinies</legend>
                    <table>
                        <?php foreach (Template::$aDefaultKeys as $key=>$val) { ?>
                        <tr>
                            <th><label><?php echo $key?></label></th>
                            <td><?php echo secureInput($val)?></td>
                        </tr>
                        <?php } ?>
                    </table>
                </fieldset>
                <fieldset class="demiRight">
                    <legend>Clés spécifiques</legend>
                    <table>
                        <?php foreach ($aKey as $key=>$val) { ?>
                        <tr>
                            <th><label><?php echo $key?></label></th>
                            <td><?php echo secureInput($val)?></td>
                        </tr>
                        <?php } ?>
                    </table>
                </fieldset>
    <?php } ?>

                <fieldset class="clear">
                    <legend>Utilisation sur le site</legend>
                    <?php
                    $_SIT_CODE = '';
                    $sql = "select * from OFF_PARAGRAPHE
                        inner join OFF_PAGE using (ID_PAGE)
                        inner join DD_SITE using (SIT_CODE)
                        where TPL_CODE=" . $dbh->quote($oTemplate->getID());
                    if (Utilisateur::getConnected()->isSEO() || !Utilisateur::getConnected()->isRoot(true)) {
                        $sql .= " and DD_SITE.SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID());
                    }
                    $sql .= " order by SIT_LIBELLE, PAG_TITRE_MENU ";
                    if ($aRow = $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC)) {
                        foreach ($aRow as $_row) {
                            if ($_row['SIT_CODE'] != $_SIT_CODE) {
                                if ($_SIT_CODE != '') {
                                    echo '</ul>';
                                }
                                $_SIT_CODE= $_row['SIT_CODE'];
                                echo '<h4>' . secureInput($_row['SIT_LIBELLE']) . '</h4><ul>';
                            } ?>
                        <li><a href="../cms_pseudo.php?idtf=<?php echo $_row['ID_PAGE']?>#par<?php echo $_row['ID_PARAGRAPHE']?>"><?php echo secureInput($_row['PAG_TITRE_MENU'])?></a></li>
                    <?php }
                        echo '</ul>';
                    ?>
                    <?php } elseif ($row['TPL_AFFECTABLE']) { ?>
                    <p>Ce template n'est pas affecté directement sur une page</p>
                    <?php } else { ?>
                    <p>Ce template n'est pas affectable directement sur une page</p>
                    <?php } ?>
                </fieldset>
            </form>
        </div>
    </div>
    <?php include('../../include/inc.bo_bandeau_bas.php')?>
</div>
</body>
</html>
