<?php
require '../inc.bo_init.php';
Utilisateur::checkConnected();
$idTDAMODIFIER = 'MODULE_BLOC';
$dbh = DB::getInstance();
$gab_CODE = $_POST['gabid'];
$siteCode = $_POST['sitecode'];

if (!empty($gab_CODE) && !empty($siteCode)) {

?>
<div>
    <div id="MODULE_BLOC_DIV">
        <fieldset>
        <?php
        $sqlGabLib = "select GAB_LIBELLE from DD_GABARIT where GAB_CODE=" . $dbh->quote($gab_CODE);
        $gabLib = secureInput(extraireLibelle($dbh->query($sqlGabLib)->fetchColumn()));

        $sql = "select MOD_CODE, 1 from SITE_MODULE where SIT_CODE=" . $dbh->quote($siteCode);
        $aMOD_CODE = $dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN|PDO::FETCH_UNIQUE);

        $aMOD_CODE_erreur = array();
        $aMOD_CODE_GAB_erreur = array();
        $aMOD_LIB_ALERT = array();
        $MOD_GROUPE = '';

        $sql = "select * from DD_MODULE order by MOD_GROUPE, MOD_LIBELLE";
        foreach ($dbh->query($sql, PDO::FETCH_ASSOC) as $rowTemp) {
            //verif acces de ce module pour ce gabarit

            $sqlCountEntreesPourModule = "select count(ID_MODULE_GABARIT) from DD_MODULE_GABARIT where MOD_CODE = " . $dbh->quote($rowTemp['MOD_CODE']);
            $countEntreesPourModule = $dbh->query($sqlCountEntreesPourModule)->fetchColumn();

            $sqlCountModuleSpecifiqueGabarit = "select count(ID_MODULE_GABARIT) from DD_MODULE_GABARIT where GAB_CODE=" . $dbh->quote($gab_CODE) . " and MOD_CODE = " . $dbh->quote($rowTemp['MOD_CODE']);
            $countModuleSpecifiqueGabarit = $dbh->query($sqlCountModuleSpecifiqueGabarit)->fetchColumn();

            if ($countModuleSpecifiqueGabarit < 1) {
                if ($countEntreesPourModule > 0) {
                    $aMOD_CODE_GAB_erreur[] = $rowTemp['MOD_CODE'];
                    if(isset($aMOD_CODE[$rowTemp['MOD_CODE']])) $aMOD_LIB_ALERT[] = extraireLibelle(secureInput($rowTemp['MOD_LIBELLE']));
                }
            }
            if ($MOD_GROUPE != $rowTemp['MOD_GROUPE']) {
                if ($MOD_GROUPE != '') {
                    echo '</ul></td></tr></table></fieldset><fieldset>';
                }
                $MOD_GROUPE = $rowTemp['MOD_GROUPE'];
           ?>
            <legend><?php echo extraireLibelle(secureInput($rowTemp['MOD_GROUPE']))?></legend>
            <table>
                <tr>
                    <th></th>
                    <td><ul>
                    <?php } ?>
                            <li>
                                <input type="checkbox" value="<?php echo $rowTemp['MOD_CODE']?>" id="<?php echo $rowTemp['MOD_CODE']?>" name="MOD_CODE[]"<?php if ($rowTemp['MOD_OBLIGATOIRE'] || isset($aMOD_CODE[$rowTemp['MOD_CODE']])) echo ' checked';?><?php if ($rowTemp['MOD_OBLIGATOIRE']) echo ' disabled'?>>
                                <label for="<?php echo $rowTemp['MOD_CODE']?>"<?php if (in_array($rowTemp['MOD_CODE'], $aMOD_CODE_erreur) || in_array($rowTemp['MOD_CODE'], $aMOD_CODE_GAB_erreur)) echo ' class="alert"'?>><?php echo extraireLibelle(secureInput($rowTemp['MOD_LIBELLE']))?></label>
                                <span class="alert" id="gab_error_<?php echo $rowTemp['MOD_CODE']?>" style="padding: 0 0 0 40px;">
                                <?php
                                if (in_array($rowTemp['MOD_CODE'], $aMOD_CODE_GAB_erreur)) {
                                  printf(gettext('ce_module_nest_pas_disponible_pour_le_gabarit_X'), secureInput($gabLib));
                                  if (isset($aMOD_CODE[$rowTemp['MOD_CODE']])) {
                                      echo '&nbsp;'.gettext('veuillez_la_desactiver');
                                  }
                                }?>
                                </span>
                            </li>
            <?php } ?>
                        </ul>
                    </td>
                </tr>
            </table>
        </fieldset>
    </div>
<?php
    if (sizeof($aMOD_LIB_ALERT) > 0) {
?>
    <div id="ALERTE_GAB">
        <span class="alert">
        <?php echo gettext('ce_changement_necessite_la_desactivation_des_modules');?>&nbsp;:&nbsp;
        <?php
        $strAlerte = "";
        foreach ($aMOD_LIB_ALERT as $libModCode) {
            if(!empty($strAlerte)) $strAlerte .= ", ";
            $strAlerte .= $libModCode;
        }
        echo $strAlerte;
        ?>
        </span>
    </div>
<?php
    }
?>
</div>
<?php
}
