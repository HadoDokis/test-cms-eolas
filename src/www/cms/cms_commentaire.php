<?php
require '../include/inc.bo_init.php';
require (CLASS_DIR . 'class.db_commentaire.php');
//require ('../include/config_module_commentaire.php');
$dhb = DB :: getInstance();

$oExterne = new Commentaire($_GET['idtf']);

if (!$oExterne->exist()) {
    setMsg(gettext("com_commentaire_pas_retrouve"));
    header('Location:' . SERVER_ROOT . 'cms/cms_commentaireListe.php');
}

$aTypeInfo = $oExterne->getTypeInfo();
CMS::checkAccess(new Module('MOD_COMMENTAIRE'), array($aTypeInfo['PRO_CODE']));

$oExterne->checkAuthorized();
$row = $oExterne->getFields();

require_once CLASS_DIR . $aTypeInfo['CLI_CLASSFILE'];
$classCible = new $aTypeInfo['CLI_CLASSNOM']($row['COM_IDLIAISON']);

$libCible = secureInput($classCible->getLibelleTypeCommentaire()) . ' : ' . '<a href="'.$aTypeInfo['CLI_CHEMINFICHE'].$row['COM_IDLIAISON'].'">'. secureInput($classCible->getLibelleCommentaire()) . '</a>';

?>
<!DOCTYPE html>
<html>
<head>
<?php include('../include/inc.bo_enTete.php') ?>
</head>
<body>
<div id="document">
    <?php $aMenuKey = array('CFG', 'SITE', 'MOD_COMMENTAIRE'); include('../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">
            <h2><?php echo $libCible; ?></h2>
            <form method="post" action="cms_commentaireSubmit.php" id="formCreation" class="creation">
                <fieldset>
                    <legend><?php echo gettext('Information')?></legend>
                    <table>
                        <tfoot>
                            <tr>
                                <td colspan="2">
                                    <?php if ($oExterne->exist()) { ?>
                                    <input type="hidden" name="idtf" value="<?php echo $oExterne->getID()?>">
                                    <input type="submit" name="Update" value="<?php echo gettext('UPDATE')?>" class="modifier">
                                    <input type="button" name="Delete" value="<?php echo gettext('DELETE')?>" class="supprimer"<?php if (!$oExterne->isDeletable()) echo ' disabled'?> onclick="if (confirm('Etes-vous sur ?')) window.location.href='cms_commentaireSubmit.php?Delete=<?php echo $oExterne->getID()?>'">
                                    <?php } ?>
                                </td>
                            </tr>
                        </tfoot>
                        <tbody>
                            <tr>
                                <th><label for="COM_MESSAGE"><?php echo gettext('com_commentaire')?></label></th>
                                <td><textarea rows="10" cols="60" name="COM_MESSAGE" id="COM_MESSAGE" required><?php echo secureInput($row['COM_MESSAGE']);?></textarea></td>
                            </tr>
                            <tr>
                                <th><?php echo gettext('com_date_depot')?></th>
                                <td>
                                    <?php echo date(gettext('com_date_format'), $row['COM_DATE'])?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php echo gettext('com_depositaire')?></th>
                                <td>
                                    <?php
                                    if (!empty($row['ID_UTILISATEUR'])) {
                                        if (Utilisateur::getConnected()->isRoot()) {
                                           echo '<a href="' . SERVER_ROOT . 'cms/administration/adm_utilisateur.php?idtf=' . intval($row['ID_UTILISATEUR']) . '">' . secureInput($row['COM_PSEUDO']) . '</a>';
                                        }
                                    } else {
                                        echo secureInput($row['COM_PSEUDO']);
                                    }
                                    echo '&nbsp;' . '<a href="mailto:' . $row['COM_MAIL'] . '">' . secureInput($row['COM_MAIL']) . '</a>';
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php echo gettext('com_cible')?></th>
                                <td>
                                    <?php
                                    echo $libCible;
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="COM_ETAT"><?php echo gettext('com_etat')?></label></th>
                                <td>
                                    <select name="COM_ETAT" id="COM_ETAT">
                                        <option value="MODERATION"<?php if($row['COM_ETAT'] == 'MODERATION') echo ' selected'?>><?php echo gettext('com_etat_en_attente_de_moderation')?></option>
                                        <option value="REFUS"<?php if($row['COM_ETAT'] == 'REFUS') echo ' selected'?>><?php echo gettext('com_etat_refuse')?></option>
                                        <option value="VALIDE"<?php if($row['COM_ETAT'] == 'VALIDE') echo ' selected'?>><?php echo gettext('com_etat_valide')?></option>
                                    </select>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </fieldset>
            </form>
        </div>
        <?php include('../include/inc.bo_bandeau_bas.php')?>
    </div>
</div>
</body>
</html>
