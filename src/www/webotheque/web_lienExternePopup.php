<fieldset class="tab">
    <legend><?php echo gettext('Ajouter')?></legend>
    <form method="post" action="<?php echo SERVER_ROOT?>webotheque/web_webothequeSubmit.php" class="creation">
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
                    <td><input name="WEB_LIBELLE" type="text" id="WEB_LIBELLE_ajout" value="" size="40" required></td>
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
                <tr>
                    <th><label for="WEB_CHEMIN_ajout"><?php echo gettext('URL')?></label></th>
                    <td><input type="text" name="WEB_CHEMIN" id="WEB_CHEMIN_ajout" size="40" required></td>
                </tr>
            </tbody>
        </table>
    </form>
</fieldset>
