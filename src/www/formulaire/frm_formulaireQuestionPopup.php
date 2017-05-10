<?php
require '../include/inc.bo_init.php';
CMS::checkAccess(new Module('MOD_FORMULAIRE'), array(
    'PRO_FORMGEST'
));
require CLASS_DIR . 'class.db_formulaire.php';

$sql = "select * from FORMULAIREQUESTION
    inner join FORMULAIREGROUPE using (ID_FORMULAIREGROUPE)
    where ID_FORMULAIREQUESTION = " . intval($_GET['idtf']);
if (! $row = $dbh->query($sql)->fetch(PDO::FETCH_ASSOC)) {
    $_GET['idtf'] = - 1;
    $row['QST_VISIBLE'] = 1;
    $row['QST_LIBELLEVISIBLE'] = 1;

    // Vérification des paramètres ID_FORMULAIREGROUPE
    $sql = "select ID_FORMULAIRE from FORMULAIREGROUPE where ID_FORMULAIREGROUPE = " . intval($_GET['ID_FORMULAIREGROUPE']);
    if ($rowTmp = $dbh->query($sql)->fetch(PDO::FETCH_ASSOC)) {
        $row['ID_FORMULAIRE'] = $rowTmp['ID_FORMULAIRE'];
        $row['ID_FORMULAIREGROUPE'] = $_GET['ID_FORMULAIREGROUPE'];
    } else {
        // Groupe inexistant
        exit();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <?php include('../include/inc.bo_enTete.php')?>
    <script>
        $(document).ready(function () {
            $('#QTY_CODE').change(function () {
                val = $(this).val();
                $('#tr_QST_OBLIGATOIRE').hide();
                $('#tr_QST_MULTIPLE').hide();
                $('#tr_QST_WIDTH').hide();
                $('#tr_QST_HEIGHT').hide();
                $('#tr_QST_MAXLENGTH').hide();
                $('#tr_QST_VALEUR').hide();
                $('#tr_QST_COMMENTAIRE').hide();
                $('#tr_QST_MESSAGEAIDE').hide();
                $('#tr_QST_PLACEHOLDER').hide();
                if (val == 'QTY_TEXTAREA') {
                    $('#tr_QST_OBLIGATOIRE').show();
                    $('#tr_QST_WIDTH').show();
                    $('#tr_QST_HEIGHT').show();
                    $('#tr_QST_MESSAGEAIDE').show();
                    $('#tr_QST_PLACEHOLDER').show();
                } else if (val == 'QTY_TEXT' || val == 'QTY_URL' || val == 'QTY_EMAIL' || val == 'QTY_EMAIL_NOTIF') {
                    $('#tr_QST_OBLIGATOIRE').show();
                    $('#tr_QST_WIDTH').show();
                    $('#tr_QST_MAXLENGTH').show();
                    $('#tr_QST_MESSAGEAIDE').show();
                    $('#tr_QST_PLACEHOLDER').show();
                } else if (val == 'QTY_SELECT') {
                    $('#tr_QST_OBLIGATOIRE').show();
                    $('#tr_QST_VALEUR').show();
                    $('#tr_QST_MESSAGEAIDE').show();
                } else if (val == 'QTY_SELECTEMAIL') {
                    $('#tr_QST_OBLIGATOIRE').show();
                    $('#tr_QST_VALEUR').show();
                    $('#tr_QST_MESSAGEAIDE').show();
                } else if (val == 'QTY_RADIO' || val == 'QTY_CHECKBOX') {
                    $('#tr_QST_OBLIGATOIRE').show();
                    $('#tr_QST_VALEUR').show();
                    $('#tr_QST_MESSAGEAIDE').show();
                } else if (val == 'QTY_LIST') {
                    $('#tr_QST_OBLIGATOIRE').show();
                    $('#tr_QST_MULTIPLE').show();
                    $('#tr_QST_HEIGHT').show();
                    $('#tr_QST_VALEUR').show();
                    $('#tr_QST_MESSAGEAIDE').show();
                } else if (val == 'QTY_FILE') {
                    $('#tr_QST_OBLIGATOIRE').show();
                    $('#tr_QST_MESSAGEAIDE').show();
                } else if (val == 'QTY_DATE') {
                    $('#tr_QST_OBLIGATOIRE').show();
                    $('#tr_QST_MESSAGEAIDE').show();
                } else if (val == 'QTY_TEL') {
                    $('#tr_QST_OBLIGATOIRE').show();
                    $('#tr_QST_MESSAGEAIDE').show();
                    $('#tr_QST_PLACEHOLDER').show();
                } else if (val == 'QTY_INFORMATION') {
                    $('#tr_QST_COMMENTAIRE').show();
                } else if (val == 'QTY_CAPTCHAGRAPHIC' || val == 'QTY_CAPTCHANUMERIC' || val == 'QTY_CAPTCHANUMERICMINUS') {
                    $('#tr_QST_MESSAGEAIDE').show();
                }
                if (val == 'QTY_INFORMATION') {
                    $('#QST_LIBELLEVISIBLE_1').prop('disabled', true);
                    $('#QST_LIBELLEVISIBLE_0').prop('disabled', true);
                    $('#QST_LIBELLEVISIBLE_0').prop('checked', true);
                } else {
                    $('#QST_LIBELLEVISIBLE_1').prop('disabled', false);
                    $('#QST_LIBELLEVISIBLE_0').prop('disabled', false);
                }
                cmsBO.resizeWindow();
            });
            $('#QTY_CODE').change();
        });
    </script>
</head>
<body id="popup">
    <?php include('../include/inc.bo_bandeau_hautPopup.php')?>
    <div id="bo_contenuPopup">
        <h2><?php echo gettext('Question')?></h2>
        <form method="post" action="frm_formulaireQuestionPopupSubmit.php" class="creation">
        <table>
            <tfoot>
                <tr>
                    <td colspan="2">
                        <?php if ($_GET['idtf'] != -1) { ?>
                        <input type="hidden" name="idtf" id="idtf" value="<?php echo secureInput($_GET['idtf'])?>">
                        <input type="submit" name="Update" value="<?php echo gettext('UPDATE')?>" class="modifier">
                        <input type="hidden" name="Update" value="1">
                        <input type="button" name="Delete" value="<?php echo gettext('DELETE')?>" class="supprimer"<?php if ($nbID_FORMULAIREQUESTION != 0) echo ' disabled' ?> onclick="if (confirm('<?php echo gettext('Etes-vous sur ?')?>')) window.location.href='frm_formulaireQuestionPopupSubmit.php?Delete=<?php echo $_GET['idtf'] ?>&amp;ID_FORMULAIRE=<?php echo $row['ID_FORMULAIRE']?>'">
                        <?php } else { ?>
                        <input type="submit" name="Insert" value="<?php echo gettext('INSERT')?>" class="ajouter">
                        <input type="hidden" name="Insert" value="1">
                        <input type="hidden" name="QST_POIDS" id="QST_POIDS" value="<?php echo secureInput($_GET['QST_POIDS']) ?>">
                        <?php } ?>
                    </td>
                </tr>
            </tfoot>
            <tr>
            <?php if ($_GET['idtf'] != -1) { ?>
                <th><label for="ID_FORMULAIREGROUPE"><?php echo gettext('Groupe') ?></label></th>
                <td>
                    <select name="ID_FORMULAIREGROUPE" id="ID_FORMULAIREGROUPE" required>
                        <option value="">&nbsp;</option>
                        <?php
                        $sql = "select ID_FORMULAIREGROUPE, FMG_LIBELLE from FORMULAIREGROUPE where ID_FORMULAIRE= " .$row['ID_FORMULAIRE']." order by FMG_POIDS";
                        foreach ($dbh->query($sql) as $rowTemp) { ?>
                        <option value="<?php echo $rowTemp['ID_FORMULAIREGROUPE']?>"<?php if($rowTemp['ID_FORMULAIREGROUPE'] == $row['ID_FORMULAIREGROUPE']) echo ' selected' ?>><?php echo secureInput($rowTemp['FMG_LIBELLE'])?></option>
                        <?php } ?>
                    </select>
                </td>
            <?php } else { ?>
                <th><label for="ID_FORMULAIREGROUPE"><?php echo gettext('Groupe') ?></label></th>
                <td>
                    <input type="hidden" name="ID_FORMULAIREGROUPE" id="ID_FORMULAIREGROUPE" value="<?php echo $row['ID_FORMULAIREGROUPE'] ?>">
                    <?php
                    $sql = "select FMG_LIBELLE from FORMULAIREGROUPE where ID_FORMULAIREGROUPE= " .intval($row['ID_FORMULAIREGROUPE']);
                    echo $dbh->query($sql)->fetchColumn(); ?>
                </td>
            <?php } ?>
            </tr>
            <tr>
                <th><label for="QST_LIBELLE"><?php echo gettext('Libelle') ?></label></th>
                <td><input name="QST_LIBELLE" type="text" id="QST_LIBELLE" value="<?php echo secureInput($row['QST_LIBELLE'])?>" size="60" required></td>
            </tr>
            <tr>
                <th><label><?php echo gettext('Libelle visible') ?></label></th>
                <td>
                    <input name="QST_LIBELLEVISIBLE" id="QST_LIBELLEVISIBLE_1" type="radio" value="1"<?php if ($row['QST_LIBELLEVISIBLE']) echo ' checked'?> required>
                    <label for="QST_LIBELLEVISIBLE_1" class="enLigne"><?php echo gettext('Oui')?></label>
                    <input name="QST_LIBELLEVISIBLE" id="QST_LIBELLEVISIBLE_0" type="radio" value="0"<?php if (!$row['QST_LIBELLEVISIBLE']) echo ' checked'?> required>
                    <label for="QST_LIBELLEVISIBLE_0" class="enLigne"><?php echo gettext('Non')?></label>
                </td>
            </tr>
            <tr>
                <th><label><?php echo gettext('En-tete de colonne')?></label></th>
                <td>
                    <input name="QST_ENTETE" id="QST_ENTETE_1" type="radio" value="1"<?php if ($row['QST_ENTETE']) echo ' checked'?> required>
                    <label for="QST_ENTETE_1" class="enLigne"><?php echo gettext('Oui')?></label>
                    <input name="QST_ENTETE" id="QST_ENTETE_0" type="radio" value="0"<?php if (!$row['QST_ENTETE']) echo ' checked'?> required>
                    <label for="QST_ENTETE_0" class="enLigne"><?php echo gettext('Non')?></label>
                </td>
            </tr>
            <tr>
                <th><label><?php echo gettext('Visible') ?></label></th>
                <td>
                    <input name="QST_VISIBLE" id="QST_VISIBLE_1" type="radio" value="1"<?php if ($row['QST_VISIBLE']) echo ' checked'?> required>
                    <label for="QST_VISIBLE_1" class="enLigne"><?php echo gettext('Oui')?></label>
                    <input name="QST_VISIBLE" id="QST_VISIBLE_0" type="radio" value="0"<?php if (!$row['QST_VISIBLE']) echo ' checked'?> required>
                    <label for="QST_VISIBLE_0" class="enLigne"><?php echo gettext('Non')?></label>
                </td>
            </tr>
            <tr id="tr_QST_OBLIGATOIRE">
                <th><label><?php echo gettext('Obligatoire') ?></label></th>
                <td>
                    <input name="QST_OBLIGATOIRE" id="QST_OBLIGATOIRE_1" type="radio" value="1"<?php if ($row['QST_OBLIGATOIRE']) echo ' checked'?> required>
                    <label for="QST_OBLIGATOIRE_1" class="enLigne"><?php echo gettext('Oui')?></label>
                    <input name="QST_OBLIGATOIRE" id="QST_OBLIGATOIRE_0" type="radio" value="0"<?php if (!$row['QST_OBLIGATOIRE']) echo ' checked'?> required>
                    <label for="QST_OBLIGATOIRE_0" class="enLigne"><?php echo gettext('Non')?></label>
                </td>
            </tr>
            <tr>
                <th><label for="QTY_CODE">Type</label></th>
                <td>
                    <select name="QTY_CODE" id="QTY_CODE"  required>
                        <option value="">&nbsp;</option>
                        <?php
                        $sql = "select * from DD_FORMULAIREQUESTIONTYPE order by QTY_GROUPE, QTY_LIBELLE";
                        $groupe = '';
                        foreach ($dbh->query($sql) as $rowTemp) {
                            if ($groupe != $rowTemp['QTY_GROUPE']) {
                                if ($groupe != '') echo '</optgroup>';
                                $groupe = $rowTemp['QTY_GROUPE'];?>
                        <optgroup label="<?php echo $groupe?>">
                        <?php }?>
                        <option value="<?php echo $rowTemp['QTY_CODE']?>"<?php if($row['QTY_CODE'] == $rowTemp['QTY_CODE']) echo ' selected' ?>>
                        <?php echo secureInput(extraireLibelle($rowTemp['QTY_LIBELLE']))?> </option>
                        <?php } ?>
                        </optgroup>
                    </select>
                </td>
            </tr>
            <tr id="tr_QST_VALEUR">
                <th>
                    <label for="QST_VALEUR">Valeurs possibles</label>
                    <div class="helper">
                        <ul>
                            <li>Une valeur par ligne</li>
                            <li>Ajouter <strong>[X]</strong> pour les valeurs par défaut</li>
                            <li>Ajouter <strong>[R]</strong> pour forcer un retour à la ligne (type <em>bouton radio</em> et <em>case à cocher</em> seulement)</li>
                            <li>Ajouter l'email sous cette forme <strong>[destinataire@email.com]</strong> à la suite de chaque valeur. Le formulaire sera envoyé à l’email correspondant à la valeur sélectionnée par l'utilisateur.  (type <em>liste destinataires</em> seulement)</li>
                        </ul>
                    </div>
                </th>
                <td><textarea name="QST_VALEUR" cols="60" rows="8" id="QST_VALEUR"><?php echo secureInput($row['QST_VALEUR'])?></textarea></td>
            </tr>
            <tr id="tr_QST_MULTIPLE">
                <th><label for="QST_MULTIPLE_1"><?php echo gettext('Choix multiple')?></label></th>
                <td>
                    <input name="QST_MULTIPLE" id="QST_MULTIPLE_1" type="radio" value="1"<?php if ($row['QST_MULTIPLE']) echo ' checked'?> required>
                    <label for="QST_MULTIPLE_1" class="enLigne"><?php echo gettext('Oui')?></label>
                    <input name="QST_MULTIPLE" id="QST_MULTIPLE_0" type="radio" value="0"<?php if (!$row['QST_MULTIPLE']) echo ' checked'?> required>
                    <label for="QST_MULTIPLE_0" class="enLigne"><?php echo gettext('Non')?></label>
                </td>
            </tr>
            <tr id="tr_QST_WIDTH">
                <th><label for="QST_WIDTH"><?php echo gettext('Largeur') ?></label></th>
                <td><input name="QST_WIDTH" type="text" id="QST_WIDTH" value="<?php echo secureInput($row['QST_WIDTH'])?>" size="3" maxlength="3" data-type="integer"></td>
            </tr>
            <tr id="tr_QST_HEIGHT">
                <th><label for="QST_HEIGHT"><?php echo gettext('Hauteur') ?></label></th>
                <td><input name="QST_HEIGHT" type="text" id="QST_HEIGHT" value="<?php echo secureInput($row['QST_HEIGHT'])?>" size="3" maxlength="3" data-type="integer"></td>
            </tr>
            <tr id="tr_QST_MAXLENGTH">
                <th><label for="QST_MAXLENGTH"><?php echo gettext('Nb maximum de caracteres') ?></label></th>
                <td><input name="QST_MAXLENGTH" type="text" id="QST_MAXLENGTH" value="<?php echo secureInput($row['QST_MAXLENGTH'])?>" size="3" maxlength="3" data-type="integer"></td>
            </tr>
            <tr id="tr_QST_COMMENTAIRE">
                <th><label for="QST_COMMENTAIRE"><?php echo gettext('Commentaire') ?></label></th>
                <td><textarea name="QST_COMMENTAIRE" cols="60" rows="5" id="QST_COMMENTAIRE"><?php echo secureInput($row['QST_COMMENTAIRE'])?></textarea></td>
            </tr>
            <tr id="tr_QST_MESSAGEAIDE">
                <th><label for="QST_MESSAGEAIDE"><?php echo gettext('Texte_du_message_d_aide'); /* Texte du message d'aide */ ?></label></th>
                <td><input type="text" name="QST_MESSAGEAIDE" id="QST_MESSAGEAIDE" value="<?php echo secureInput($row['QST_MESSAGEAIDE'])?>" size="60" maxlength="255"></td>
            </tr>
            <tr id="tr_QST_PLACEHOLDER">
                <th><label for="QST_PLACEHOLDER"><?php echo gettext('Placeholder'); ?></label></th>
                <td><input type="text" name="QST_PLACEHOLDER" id="QST_PLACEHOLDER" value="<?php echo secureInput($row['QST_PLACEHOLDER'])?>" size="60" maxlength="255"></td>
            </tr>
        </table>
    </form>
</div>
<?php include('../include/inc.bo_bandeau_basPopup.php')?>
</body>
</html>
