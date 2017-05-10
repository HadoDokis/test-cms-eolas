<fieldset class="tab">
    <legend><?php echo gettext('Ajouter')?></legend>
    <form method="post" action="<?php echo SERVER_ROOT?>webotheque/web_webothequeSubmit.php" class="creation" enctype="multipart/form-data">
        <table>
            <tfoot>
                <tr>
                    <td colspan="2">
                        <input type="hidden" name="WBT_CODE" value="<?php echo $WBT_CODE?>">
                        <input type="submit" name="Insert" value="<?php echo gettext('INSERT')?>" class="ajouter">
                        <input type="hidden" name="fromPopup" value="<?php echo $fromPopup?>">
                    </td>
                </tr>
            </tfoot>
            <tbody>
                <tr>
                    <th><label for="WEB_LIBELLE_ajout"><?php echo gettext('Libelle')?></label></th>
                    <td><input name="WEB_LIBELLE" type="text" id="WEB_LIBELLE_ajout" size="40" required></td>
                </tr>
                <tr>
                    <th><label for="<?php echo (WebothequeCategorie::getNb($WBT_CODE) == 0) ? 'CAT_LIBELLE_ajout' : 'ID_WEBOTHEQUECATEGORIE_ajout'?>" class="isNotNull"><?php echo gettext('Categorie')?>*</label></th>
                    <td>
                        <select name="ID_WEBOTHEQUECATEGORIE" id="ID_WEBOTHEQUECATEGORIE_ajout">
                        <?php echo WebothequeCategorie::getSelectOptions($WBT_CODE) ?>
                        </select>
                        <input type="text" name="CAT_LIBELLE" id="CAT_LIBELLE_ajout" value="" size="30" placeholder="<?php echo gettext('Ajouter_dossier')?>">
                    </td>
                </tr>
                <?php if ($WBT_CODE == 'WBT_VIDEOEXTERNE' || $WBT_CODE == 'WBT_WIDGET') { ?>
                <tr>
                    <th><label for="WEB_DESCRIPTIONACC_ajout"><?php echo gettext('Code')?></label></th>
                    <td><textarea name="WEB_DESCRIPTIONACC" id="WEB_DESCRIPTIONACC_ajout" rows="12" cols="60" required></textarea></td>
                </tr>
                <?php } else { ?>
                <tr>
                    <th><label for="WEB_CHEMIN_ajout"><?php echo gettext('Fichier')?> <span class="tailleMax">( <?php echo gettext('taille maximum') . ' : ' . File_management::getMaxUpload()?> )</span></label></th>
                    <td><input type="file" name="WEB_CHEMIN" id="WEB_CHEMIN_ajout" size="20" required></td>
                </tr>
                    <?php if ($WBT_CODE != 'WBT_MUSIC') { ?>
                <tr>
                    <th><label for="WEB_LARGEUR_ajout"><?php echo gettext('Largeur')?></label></th>
                    <td><input name="WEB_LARGEUR" type="text" id="WEB_LARGEUR_ajout" size="4" maxlength="6" data-type="integer" required> px</td>
                </tr>
                <tr>
                    <th><label for="WEB_HAUTEUR_ajout"><?php echo gettext('Hauteur')?></label></th>
                    <td><input name="WEB_HAUTEUR" type="text" id="WEB_HAUTEUR_ajout" size="4" maxlength="6" data-type="integer" required> px</td>
                </tr>
                    <?php } ?>
                <?php } ?>
            </tbody>
        </table>
    </form>
</fieldset>
