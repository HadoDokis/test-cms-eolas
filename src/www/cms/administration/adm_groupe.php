<?php
require '../../include/inc.bo_init.php';
CMS::checkAccess(new Module('MOD_EXTRANET'), array('PRO_ROOT_SITE'));
require CLASS_DIR . 'class.db_groupe.php';

$oGroupe = new Groupe($_GET['idtf']);
if ($oGroupe->exist()) {
    $oGroupe->checkAuthorized();
}
$row = $oGroupe->getFields();
$aSharedSite = CMS::getCurrentSite()->getSharedSites();
?>
<!DOCTYPE html>
<html>
<head>
<?php include('../../include/inc.bo_enTete.php') ?>
<script>
function postControl_formCreation(oForm)
{
    selectAll('SIT_CODE');
    selectAll('PRO_CODE');

    return true;
}
</script>
</head>
<body>
<div id="document">
    <?php $aMenuKey = array('CFG', 'USER', 'GROUPE'); if (!$oGroupe->exist()) $aMenuKey[] = 'ADD'; include('../../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">
            <h2><?php echo gettext('Groupe')?></h2>
            <form method="post" action="adm_groupeSubmit.php" id="formCreation" class="creation">
                <fieldset>
                    <legend><?php echo gettext('Informations')?></legend>
                    <table>
                        <tfoot>
                            <tr>
                                <td colspan="2">
                                    <?php if ($oGroupe->exist()) { ?>
                                    <input type="hidden" name="idtf" value="<?php echo $oGroupe->getID()?>">
                                    <input type="submit" name="Update" value="<?php echo gettext('UPDATE')?>" class="modifier">
                                    <input type="button" name="Delete" value="<?php echo gettext('DELETE')?>" class="supprimer<?php if (!$oGroupe->isDeletable()) echo ' disabled'?>" onclick="if (confirm('<?php echo gettext('Etes-vous sur ?')?>')) window.location.href='adm_groupeSubmit.php?Delete=<?php echo $oGroupe->getID()?>'"<?php if (!$oGroupe->isDeletable()) echo ' disabled'?>>
                                    <?php } else { ?>
                                    <input type="submit" name="Insert" value="<?php echo gettext('INSERT')?>" class="ajouter">

                                    <?php } ?>
                                </td>
                            </tr>
                        </tfoot>
                        <tbody>
                            <tr>
                                <th><label for="GRP_LIBELLE"><?php echo gettext('Libelle')?></label></th>
                                <td><input name="GRP_LIBELLE" type="text" id="GRP_LIBELLE" value="<?php echo secureInput($row['GRP_LIBELLE'])?>" size="30" required></td>
                            </tr>
                            <?php if (count($aSharedSite) > 0) {?>
                            <tr>
                                <th><label><?php echo gettext('Ouverture')?></label></th>
                                <td>
                                    <table class='selection'>
                                    <tr>
                                        <th><?php echo gettext('Affecte(s)')?></th>
                                        <th>&nbsp;</th>
                                        <th><?php echo gettext('Disponible(s)')?></th>
                                    </tr>
                                    <tr>
                                        <td>
                                            <select name="SIT_CODE[]" id="SIT_CODE" size="<?php echo count($aSharedSite)?>" multiple ondblclick="DeplaceCritere(document.getElementById('SIT_CODE'), document.getElementById('SIT_CODE_ALL'));">
                                            <?php
                                            $sql = "select DD_SITE.* from DD_SITE
                                                inner join GROUPE_SITE on (DD_SITE.SIT_CODE = GROUPE_SITE.SIT_CODE)
                                                where GROUPE_SITE.ID_GROUPE=" . $oGroupe->getID() . "
                                                order by SIT_LIBELLE";
                                            $aSharedSiteOk = array();;
                                            foreach ($dbh->query($sql, PDO::FETCH_ASSOC) as $rowTemp) {
                                                $aSharedSiteOk[$rowTemp['SIT_CODE']] = true;?>
                                                <option value="<?php echo secureInput($rowTemp['SIT_CODE'])?>"><?php echo secureInput($rowTemp['SIT_LIBELLE'])?></option>
                                            <?php } ?>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="button" name="Button" value="&lt;&lt;" onclick="DeplaceCritere(document.getElementById('SIT_CODE_ALL'), document.getElementById('SIT_CODE'));">
                                            <input type="button" name="Button2" value="&gt;&gt;" onclick="DeplaceCritere(document.getElementById('SIT_CODE'), document.getElementById('SIT_CODE_ALL'));">
                                        </td>
                                        <td>
                                            <select name="SIT_CODE_ALL[]" id="SIT_CODE_ALL" size="<?php echo count($aSharedSite)?>" multiple ondblclick="DeplaceCritere(document.getElementById('SIT_CODE_ALL'), document.getElementById('SIT_CODE'));">
                                            <?php
                                            foreach ($aSharedSite as $key=>$oSharedSite) {
                                                if (isset($aSharedSiteOk[$key])) {
                                                    continue;
                                                }?>
                                                <option value="<?php echo secureInput($key)?>"><?php echo secureInput($oSharedSite->getField('SIT_LIBELLE'))?></option>
                                            <?php } ?>
                                            </select>
                                        </td>
                                    </tr>
                                    </table>
                                </td>
                            </tr>
                            <?php } ?>
                            <tr>
                                <th><label><?php echo gettext('Affecte utilisateurs')?></label></th>
                                <td>
                                    <input type="radio" name="GRP_DEFAUT_UTILISATEUR" id="GRP_DEFAUT_UTILISATEUR_1" value="1"<?php if ($row['GRP_DEFAUT_UTILISATEUR']) echo ' checked';?>> <label for="GRP_DEFAUT_UTILISATEUR_1"><?php echo gettext('Oui')?></label>
                                    <input type="radio" name="GRP_DEFAUT_UTILISATEUR" id="GRP_DEFAUT_UTILISATEUR_0" value="0"<?php if (!$row['GRP_DEFAUT_UTILISATEUR']) echo ' checked';?>> <label for="GRP_DEFAUT_UTILISATEUR_0"><?php echo gettext('Non')?></label>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </fieldset>

                <?php
                $sql = "select UTILISATEUR.*, SIT_LIBELLE from GROUPE_UTILISATEUR
                    inner join UTILISATEUR using(ID_UTILISATEUR)
                    inner join DD_SITE using (SIT_CODE)
                    where ID_GROUPE=" . $oGroupe->getID() . "
                    order by UTI_NOM";
                $aUTILISATEUR = $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
                if (count($aUTILISATEUR) > 0) {
                ?>
                <fieldset>
                    <legend><?php echo gettext('Membres')?></legend>
                    <table class="liste">
                        <thead>
                            <tr>
                                <th><?php echo gettext('Nom')?></th>
                                <?php if (count($aSharedSite) > 0) {?>
                                <th><?php echo gettext('Origine')?></th>
                                <?php } ?>
                                <th><?php echo gettext('Fonction')?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        foreach ($aUTILISATEUR as $UTILISATEUR) {
                            ?>
                            <tr>
                                <?php if ($UTILISATEUR['SIT_CODE'] == $oGroupe->getField('SIT_CODE')) {?>
                                <td><a href="adm_utilisateur.php?idtf=<?php echo $UTILISATEUR['ID_UTILISATEUR']?>"><?php echo secureInput($UTILISATEUR['UTI_NOM'] . ' ' . $UTILISATEUR['UTI_PRENOM'])?></a></td>
                                <?php } else { ?>
                                <td><?php echo secureInput($UTILISATEUR['UTI_NOM'] . ' ' . $UTILISATEUR['UTI_PRENOM'])?></td>
                                <?php } ?>
                                <?php if (count($aSharedSite) > 0) {?>
                                <td><?php echo secureInput($UTILISATEUR['SIT_LIBELLE'])?></td>
                                <?php } ?>
                                <td><?php echo secureInput($UTILISATEUR['UTI_FONCTION'])?></td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </fieldset>
                <?php } ?>

       <?php
$sql = 'select *
        from OFF_PAGE
        inner join GROUPE_OFF_PAGE using(ID_PAGE)
        where ID_GROUPE='.$oGroupe->getID().'
        order by PAG_TITRE_MENU';
$aGROUPE_OFF_PAGE = $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
if (count($aGROUPE_OFF_PAGE) > 0) {
?>
        <fieldset>
        <legend><?php echo gettext('Page')?></legend>
        <table class="liste">
          <tr>
            <th><?php echo gettext('Numero')?></th>
            <th><?php echo gettext('Page')?></th>
          </tr>
          <?php foreach ($aGROUPE_OFF_PAGE as $GROUPE_OFF_PAGE) {
                ?>
          <tr>
            <td class="alignright"><?php echo $GROUPE_OFF_PAGE['ID_PAGE'] ?></td>
            <td><a href="../cms_page.php?idtf=<?php echo $GROUPE_OFF_PAGE['ID_PAGE']?>"><?php echo secureInput($GROUPE_OFF_PAGE['PAG_TITRE_MENU'])?></a></td>
          </tr>
          <?php } ?>
        </table>
        </fieldset>
        <?php } ?>
      </form>
    </div>
  </div>
  <?php include('../../include/inc.bo_bandeau_bas.php')?>
</div>
</body>
</html>
