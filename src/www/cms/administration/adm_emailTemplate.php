<?php
require '../../include/inc.bo_init.php';
CMS::checkAccess(new Module('MOD_CORE'), array('PRO_ROOT'));
require CLASS_DIR . 'class.db_emailTemplate.php';
require CLASS_DIR . 'class.CMSMailer.php';
require CLASS_DIR . 'class.Editor.php';

$oEmailTemplate = new EmailTemplate($_GET['idtf']);
if (!$oEmailTemplate->exist()) {
    header('Location:' . SERVER_ROOT . 'cms/administration/adm_emailTemplateListe.php');
    exit();
}
$oModule = $oEmailTemplate->getModule();
$row = $oEmailTemplate->getFields();
?>
<!DOCTYPE html>
<html>
<head>
    <?php include '../../include/inc.bo_enTete.php' ?>
    <?php Editor::header() ?>
    <script>
    editorInit('minimal', new Array('EMT_BODYHTML'));
    </script>
</head>
<body>
<div id="document">
    <?php $aMenuKey = array('CFG', 'PTF', 'EMT'); include('../../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">
            <h2><a href="adm_emailTemplateListe.php?MOD_CODE=<?php echo $oModule->getID()?>&amp;Find=1"><?php echo extraireLibelle($oModule->getField('MOD_LIBELLE')) ?></a> : <?php echo secureInput($row['EMT_DESCRIPTION'])?></h2>
            <form method="post" action="adm_emailTemplateSubmit.php"  class="creation">
                <fieldset>
                    <legend><?php echo gettext('Proprietes')?></legend>
                    <table>
                        <tfoot>
                            <tr>
                                <td colspan="2">
                                    <input type="hidden" name="idtf" value="<?php echo $oEmailTemplate->getID()?>">
                                    <input type="submit" name="Update" value="<?php echo gettext('UPDATE')?>" class="modifier">
                                </td>
                            </tr>
                        </tfoot>
                        <tbody>
                            <tr>
                                <th><label for="EMT_DESCRIPTION">Description</label></th>
                                <td><input name="EMT_DESCRIPTION" id="EMT_DESCRIPTION" type="text" size="60" value="<?php echo secureInput($row['EMT_DESCRIPTION'])?>" required></td>
                            </tr>
                            <tr>
                                <th><label for="EMT_EXPEDITEURFROM">Expéditeur</label></th>
                                <td>
                                    <input type="radio" name="EMT_EXPEDITEUR" id="EMT_EXPEDITEUR_CMS" value="CMS"<?php if ($row['EMT_EXPEDITEUR'] == 'CMS') echo ' checked'?>>
                                    <label for="EMT_EXPEDITEUR_CMS">Plateforme CMS (<?php echo EMAIL_FROM?> / <?php echo EMAIL_FROMNAME ?>)</label>
                                    <br>
                                    <input type="radio" name="EMT_EXPEDITEUR" id="EMT_EXPEDITEUR_SITE" value="SITE"<?php if ($row['EMT_EXPEDITEUR'] == 'SITE') echo ' checked'?>>
                                    <label for="EMT_EXPEDITEUR_SITE">Site courant (<?php echo CMS::getCurrentSite()->getField('SIT_EMAIL')?>)</label>
                                    <br>
                                    <input type="radio" name="EMT_EXPEDITEUR" id="EMT_EXPEDITEUR_USER" value="USER"<?php if ($row['EMT_EXPEDITEUR'] == 'USER') echo ' checked'?>>
                                    <label for="EMT_EXPEDITEUR_USER">Contributeur connecté (si envoi depuis BO)</label>
                                    <br>
                                    <input type="radio" name="EMT_EXPEDITEUR" id="EMT_EXPEDITEUR_FROM" value="FROM"<?php if ($row['EMT_EXPEDITEUR'] == 'FROM') echo ' checked'?>>
                                    <label for="EMT_EXPEDITEUR_FROM">Email spécifique</label>
                                    <input name="EMT_EXPEDITEURFROM" id="EMT_EXPEDITEURFROM" type="email" size="40" value="<?php echo secureInput($row['EMT_EXPEDITEURFROM'])?>" placeholder="email">
                                    <input name="EMT_EXPEDITEURFROMNAME" id="EMT_EXPEDITEURFROMNAME" type="text" size="40" value="<?php echo secureInput($row['EMT_EXPEDITEURFROMNAME'])?>" placeholder="nom">
                                </td>
                            </tr>
                            <tr>
                                <th><label for="EMT_SUJET">Objet de l'email</label></th>
                                <td><input name="EMT_SUJET" id="EMT_SUJET" type="text" size="70" value="<?php echo secureInput($row['EMT_SUJET'])?>" required></td>
                            </tr>
                            <tr>
                               <th>
                                    <label for="EMT_BODYHTML">Contenu de l'email</label>
                                    <div class="helper">Contenu texte de l'email
                                        <br>---------------------------------------------------<br>
                                        <p>Les textes en [MAJUSCULE] sont des clés qui correspondent à des données CMS.eolas</p>
                                        <p>Chaque texte [CLE] sera remplacé par sa valeur lors de l'envoi du mail</p>
                                        <p>Voir la liste des clés possibles ci aprés</p>
                                    </div>
                                </th>
                                <td><textarea name="EMT_BODYHTML" id="EMT_BODYHTML" cols="90" rows="20" required><?php echo secureInput($row['EMT_BODYHTML'])?></textarea></td>
                            </tr>
                        </tbody>
                    </table>
                </fieldset>
                <fieldset class="demiLeft">
                    <legend>Clés prédéfinies <span class="helper">Liste des données génériques</span></legend>
                    <table>
                        <?php foreach (CMSMailer::$aKey as $key=>$val) { ?>
                        <tr>
                            <th><label><?php echo $key?></label></th>
                            <td><?php echo secureInput($val)?></td>
                        </tr>
                        <?php } ?>
                    </table>
                </fieldset>
                <?php if ($aKey = $oEmailTemplate->getKeys()) { ?>
                <fieldset class="demiRight">
                    <legend>Clés spécifiques <span class="helper">Liste des données CMS.Eolas disponibles</span></legend>
                    <table>
                        <?php foreach ($aKey as $key) { ?>
                        <tr>
                            <th><label>[<?php echo $key['EMK_LIBELLE']?>]</label></th>
                            <td><?php echo secureInput($key['EMK_DESCRIPTION'])?></td>
                        </tr>
                        <?php } ?>
                    </table>
                </fieldset>
                <?php } ?>
            </form>
        </div>
    </div>
    <?php include '../../include/inc.bo_bandeau_bas.php' ?>
</div>
</body>
</html>
