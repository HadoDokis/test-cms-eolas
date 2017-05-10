<?php
require '../../include/inc.bo_init.php';
CMS::checkAccess(new Module('MOD_CORE'), array(
    'PRO_ROOT_SITE'
));
require CLASS_DIR . 'class.Arbo.php';

$oArbo = new Arbo('PERIMETRE');
$oUtilisateur = new Utilisateur($_GET['idtf']);
if (! $oUtilisateur->checkAuthorized(false)) {
    // peut-etre un utilisateur partagé ?
    $oUtilisateur->checkShareAuthorized();
}
?>
<!DOCTYPE html>
<html>
<head>
    <?php include('../../include/inc.bo_enTete.php')?>
    <script>
        $(document).ready(cmsBO.initArbo);
        function choixPerimetre(id)
        {
            document.getElementById('ID_PAGE').value= id;
            if (document.getElementById('PRO_CODE').value == '') {
                alert('Le champ "Profil" doit être renseigné.');
            } else {
                document.getElementById('formCreation').submit();
            }
        }
    </script>
</head>
<body id="popup">
    <?php include('../../include/inc.bo_bandeau_hautPopup.php')?>
    <div id="bo_contenuPopup">
        <h2>Rôles sur les pages</h2>
        <form method="post" action="adm_utilisateurProfilPopupSubmit.php" class="creation" id="formCreation">
            <table>
                <tbody>
                    <tr>
                        <td colspan="2"><?php echo Arbo::action()?></td>
                    </tr>
                    <tr>
                        <th><label for="PRO_CODE">Profil</label></th>
                        <td>
                            <input type="hidden" name="idtf" value="<?php echo $oUtilisateur->getID()?>">
                            <input type="hidden" name="editShare" value="<?php echo secureInput($_REQUEST['editShare'])?>">
                            <input type="hidden" name="ID_PAGE" id="ID_PAGE">
                            <input type="hidden" name="Insert" value="1">
                            <select name="PRO_CODE" id="PRO_CODE" required>
                                <option value="">&nbsp;</option>
                                <?php
                                $MOD_GROUPE = '';
                                $sql = "select * from DD_PROFIL
                                    inner join MODULE_PROFIL on DD_PROFIL.PRO_CODE = MODULE_PROFIL.PRO_CODE
                                    inner join DD_MODULE on MODULE_PROFIL.MOD_CODE = DD_MODULE.MOD_CODE
                                    inner join SITE_MODULE on DD_MODULE.MOD_CODE = SITE_MODULE.MOD_CODE
                                    where PRO_PAGE=1 and SITE_MODULE.SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID()) . "
                                    group by DD_PROFIL.PRO_CODE
                                    order by MOD_GROUPE, PRO_LIBELLE";
                                foreach ($dbh->query($sql) as $rowTemp) {
                                    if ($MOD_GROUPE != $rowTemp['MOD_GROUPE']) {
                                        if ($MOD_GROUPE != '') {
                                            echo '</optgroup>';
                                        }
                                        $MOD_GROUPE = $rowTemp['MOD_GROUPE'];
                                        ?>
                                    <optgroup label="<?php echo secureInput(extraireLibelle($MOD_GROUPE))?>">
                                        <?php } ?>
                                    <option value="<?php echo secureInput($rowTemp['PRO_CODE'])?>"><?php echo secureInput(extraireLibelle($rowTemp['PRO_LIBELLE']))?> </option>
                                <?php } ?>
                                </optgroup>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Point de départ</label></th>
                        <td><?php echo $oArbo->draw()?></td>
                    </tr>
                </tbody>
            </table>
        </form>
    </div>
    <?php include('../../include/inc.bo_bandeau_basPopup.php')?>
</body>
</html>
