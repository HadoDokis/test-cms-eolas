<?php
require '../../include/inc.bo_init.php';
CMS::checkAccess(new Module('MOD_CORE'), array('PRO_ROOT_SITE'));
require CLASS_DIR . 'class.db_page.php';

$oUtilisateur = new Utilisateur($_GET['idtf']);
//on vérifie qu'il s'agit bien d'un utilisateur 'partagé'
$oUtilisateur->checkShareAuthorized();
$row = $oUtilisateur->getFields();
$oSite = new Site($row['SIT_CODE']);
?>
<!DOCTYPE html>
<html>
<head>
    <?php include('../../include/inc.bo_enTete.php') ?>
    <script>
    function postControl_formCreation(oForm)
    {
        selectAll('PRO_CODE');

        return true;
    }
    </script>
</head>
<body>
<div id="document">
    <?php $aMenuKey = array('CFG', 'USER', 'UTILISATEUR'); include('../../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">
            <h2><?php echo secureInput($row['UTI_NOM'] . ' ' . $row['UTI_PRENOM'])?></h2>
            <form method="post" action="adm_utilisateurSubmit.php" id="formCreation" class="creation">
                <fieldset>
                    <legend><?php echo gettext('Contribution')?></legend>
                    <table>
                        <tfoot>
                            <tr>
                                <td colspan="2">
                                    <input type="hidden" name="idtf" id="idtf" value="<?php echo secureInput($oUtilisateur->getID())?>">
                                    <input type="submit" name="UpdateShare" value="<?php echo gettext('UPDATE')?>" class="modifier">
                                </td>
                            </tr>
                        </tfoot>
                        <tbody>
                            <?php if (Utilisateur::getConnected()->isRoot(true)) {?>
                            <tr>
                                <th>
                                    <label>
                                    <?php
                                        $sql = "select PRO_LIBELLE from DD_PROFIL where PRO_CODE='PRO_ROOT'";
                                        echo extraireLibelle($dbh->query($sql)->fetchColumn());
                                        $sql = "select count(PRO_CODE) from ROLE where ID_UTILISATEUR=" . $oUtilisateur->getID() . " and PRO_CODE='PRO_ROOT'";
                                        $isRoot = $dbh->query($sql)->fetchColumn();
                                        $selfRootEdit = Utilisateur::getConnected()->getID() == $oUtilisateur->getID();?>
                                    </label>
                                </th>
                                <td>
                                    <input name="PRO_ROOT" id="PRO_ROOT_1" type="radio" value="1"<?php if ($isRoot) echo ' checked'?><?php if ($selfRootEdit) echo ' disabled';?>>
                                    <label for="PRO_ROOT_1"><?php echo gettext('Oui')?></label>
                                    <input name="PRO_ROOT" id="PRO_ROOT_0"  type="radio" value="0"<?php if (!$isRoot) echo ' checked'?><?php if ($selfRootEdit) echo ' disabled';?>>
                                    <label for="PRO_ROOT_0"><?php echo gettext('Non')?></label>
                                </td>
                            </tr>
                            <?php } ?>
                            <tr>
                                <th><label><?php echo gettext('Roles sur les pages')?></label></th>
                                <td>
                                    <?php if (!$oUtilisateur->exist()) {?>
                                    Vous devez enregistrer l'utilisateur avant de lui associer des profils "CMS"
                                    <?php } else { ?>
                                    <a href="adm_utilisateurProfilPopup.php?editShare=1&amp;idtf=<?php echo $oUtilisateur->getID() ?>" class="action popup">Ajouter un profil</a>
                                    <?php } ?>
                                </td>
                            </tr>
                            <?php
                            $sql = "select * from ROLE
                                inner join OFF_PAGE on ROLE.ID_PAGE = OFF_PAGE.ID_PAGE
                                inner join DD_PROFIL on ROLE.PRO_CODE = DD_PROFIL.PRO_CODE
                                where ID_UTILISATEUR=" . $oUtilisateur->getID() . " and ROLE.SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID()) . "
                                order by PRO_LIBELLE";
                            $aROLE = $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
                            if (count($aROLE) > 0) { ?>
                            <tr>
                                <td colspan="2">
                                    <table class="liste">
                                        <thead>
                                            <tr>
                                                <th><?php echo gettext('Point de depart')?></th>
                                                <th><?php echo gettext('Role')?></th>
                                                <th><?php echo gettext('Action')?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($aROLE as $rowListe) { ?>
                                            <tr>
                                                <td><?php echo secureInput($rowListe['PAG_TITRE_MENU'] . ' (' . $rowListe['ID_PAGE'] . ')')?></td>
                                                <td class="aligncenter"><?php echo secureInput(extraireLibelle($rowListe['PRO_LIBELLE']))?></td>
                                                <td class="aligncenter">
                                                    <a href="<?php echo SERVER_ROOT?>cms/cms_page.php?idtf=<?php echo $rowListe['ID_PAGE']?>" class="actionProprietes">Propriétés</a>
                                                    <a href="<?php echo SERVER_ROOT?>cms/cms_pseudo.php?idtf=<?php echo $rowListe['ID_PAGE']?>&amp;PFM=1" class="actionEditer">Editer</a>
                                                    <a href="adm_utilisateurProfilPopupSubmit.php?Delete=<?php echo $rowListe['ID_ROLE']?>&amp;idtf=<?php echo $oUtilisateur->getID() ?>" onClick="return confirm('<?php echo gettext('Etes-vous sur ?')?>')" class="actionSupprimer"><?php echo gettext('Supprimer')?></a>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            <?php } ?>
                            <tr>
                                <th><label><?php echo gettext('Autres roles')?></label></th>
                                <td>
                                    <table class="selection">
                                        <tr>
                                            <th><?php echo gettext('Affecte(s)')?></th>
                                            <th>&nbsp;</th>
                                            <th><?php echo gettext('Disponible(s)')?></th>
                                        </tr>
                                        <tr>
                                            <td>
                                                <select name="PRO_CODE[]" id="PRO_CODE" size="10" multiple ondblclick="DeplaceCritere(document.getElementById('PRO_CODE'), document.getElementById('PRO_CODE_ALL'));">
                                                <?php
                                                $MOD_GROUPE = '';
                                                $sql = 'select distinct MOD_GROUPE, PRO_LIBELLE, DD_PROFIL.PRO_CODE from ROLE
                                                    inner join DD_PROFIL on ROLE.PRO_CODE = DD_PROFIL.PRO_CODE
                                                    inner join MODULE_PROFIL on DD_PROFIL.PRO_CODE = MODULE_PROFIL.PRO_CODE
                                                    inner join DD_MODULE on MODULE_PROFIL.MOD_CODE = DD_MODULE.MOD_CODE
                                                    where ID_UTILISATEUR = ' . $oUtilisateur->getID() . ' and PRO_PAGE <> 1 and ROLE.SIT_CODE=' . $dbh->quote(CMS::getCurrentSite()->getID()) . '
                                                    order by MOD_GROUPE, PRO_LIBELLE';
                                                $aROLE = $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
                                                if (count($aROLE) > 0) {
                                                    foreach ($aROLE as $rowTemp) {
                                                        if ($MOD_GROUPE != $rowTemp['MOD_GROUPE']) {
                                                            if ($MOD_GROUPE != '') {
                                                                echo '</optgroup>';
                                                            }
                                                            $MOD_GROUPE = $rowTemp['MOD_GROUPE'];?>
                                                            <optgroup label="<?php echo secureInput(extraireLibelle($MOD_GROUPE))?>">
                                                        <?php } ?>
                                                        <option value="<?php echo $rowTemp['PRO_CODE']?>"><?php echo secureInput(extraireLibelle($rowTemp['PRO_LIBELLE']))?></option>
                                                    <?php } ?>
                                                    </optgroup>
                                                <?php } ?>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="button" name="Button" value="&lt;&lt;" onclick="DeplaceCritere(document.getElementById('PRO_CODE_ALL'), document.getElementById('PRO_CODE'));">
                                                <input type="button" name="Button2" value="&gt;&gt;" onclick="DeplaceCritere(document.getElementById('PRO_CODE'), document.getElementById('PRO_CODE_ALL'));">
                                            </td>
                                            <td>
                                                <select name="PRO_CODE_ALL[]" id="PRO_CODE_ALL" size="10" multiple ondblclick="DeplaceCritere(document.getElementById('PRO_CODE_ALL'), document.getElementById('PRO_CODE'));">
                                                <?php
                                                $MOD_GROUPE = '';
                                                $sql = 'select * from DD_PROFIL
                                                    inner join MODULE_PROFIL on DD_PROFIL.PRO_CODE = MODULE_PROFIL.PRO_CODE
                                                    inner join DD_MODULE on MODULE_PROFIL.MOD_CODE = DD_MODULE.MOD_CODE
                                                    inner join SITE_MODULE on DD_MODULE.MOD_CODE = SITE_MODULE.MOD_CODE
                                                    where PRO_PAGE <> 1
                                                    and DD_PROFIL.PRO_CODE not in (
                                                        select PRO_CODE
                                                        from ROLE
                                                        where ID_UTILISATEUR=' . $oUtilisateur->getID() . '
                                                        and SIT_CODE=' . $dbh->quote(CMS::getCurrentSite()->getID()) . '
                                                    )
                                                    and DD_PROFIL.PRO_CODE <> \'PRO_ROOT\'
                                                    and SITE_MODULE.SIT_CODE =  ' . $dbh->quote(CMS::getCurrentSite()->getID()) . '
                                                    group by DD_PROFIL.PRO_CODE
                                                    order by MOD_GROUPE, PRO_LIBELLE';
                                                foreach ($dbh->query($sql) as $rowTemp) {
                                                    if ($MOD_GROUPE != $rowTemp['MOD_GROUPE']) {
                                                        if ($MOD_GROUPE != '') {
                                                            echo '</optgroup>';
                                                        }
                                                        $MOD_GROUPE = $rowTemp['MOD_GROUPE']; ?>
                                                        <optgroup label="<?php echo secureInput(extraireLibelle($MOD_GROUPE))?>">
                                                    <?php } ?>
                                                    <option value="<?php echo $rowTemp['PRO_CODE']?>"><?php echo secureInput(extraireLibelle($rowTemp['PRO_LIBELLE']))?></option>
                                                <?php } ?>
                                                    </optgroup>
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
    <?php include('../../include/inc.bo_bandeau_bas.php')?>
</div>
</body>
</html>
