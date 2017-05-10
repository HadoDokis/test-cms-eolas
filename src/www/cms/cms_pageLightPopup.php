<?php
require '../include/inc.bo_init.php';
Utilisateur::checkConnected();
require CLASS_DIR . 'class.db_page.php';

$oPage = new Page($_GET['idtf']);
$oPage->checkAuthorized();
$oPage->lock();
$row = $oPage->getFields();
?>
<!DOCTYPE html>
<html>
<head>
    <?php include('../include/inc.bo_enTete.php')?>
    <script src="<?php echo SERVER_ROOT ?>include/js/onglet.js"></script>
</head>
<body id="popup">
    <?php include('../include/inc.bo_bandeau_hautPopup.php')?>
    <div id="bo_contenuPopup">
        <h2><?php echo secureInput($row['PAG_TITRE_MENU'])?></h2>
        <form method="post" action="cms_pageSubmit.php" class="creation">
            <fieldset class="tab">
                <legend>Propriétés</legend>
                <table>
                    <tbody>
                        <tr>
                            <th><label for="PAG_TITRE">Titre de la page (long) </label></th>
                            <td>
                                <input name="PAG_TITRE" type="text" id="PAG_TITRE" value="<?php echo secureInput($row['PAG_TITRE'])?>" size="70" required>
                                <script>document.getElementById('PAG_TITRE').focus();</script>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="PAG_TITRE_MENU">Titre dans le menu (court)</label></th>
                            <td>
                                <input name="PAG_TITRE_MENU" type="text" id="PAG_TITRE_MENU" value="<?php echo secureInput($row['PAG_TITRE_MENU'])?>" size="40" required>
                                <a href="#" onclick="document.getElementById('PAG_TITRE_MENU').value=document.getElementById('PAG_TITRE').value; return false;" class="btnAction"><?php echo gettext('Copier le titre')?></a>
                            </td>
                        </tr>
                        <tr>
                            <th>&nbsp;</th>
                            <td>
                                <input type="checkbox" name="PAG_VISIBLE_MENU" id="PAG_VISIBLE_MENU" value="1"<?php if ($row['PAG_VISIBLE_MENU'] || $row['PAG_VISIBLE_MENU'] == '') echo ' checked'?>>
                                <label for="PAG_VISIBLE_MENU">Faire apparaître cette page dans le menu</label>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="PAG_TITLE">Info bulle (survol sur titre)</label></th>
                            <td><input name="PAG_TITLE" type="text" id="PAG_TITLE" value="<?php echo secureInput($row['PAG_TITLE'])?>" size="50"></td>
                        </tr>
                    </tbody>
                </table>
            </fieldset>
            <fieldset class="tab">
                <legend>Contenu et mise en page</legend>
                <table>
                    <tbody>
                        <?php if (CMS::getCurrentSite()->getField('SIT_PAGE_TXTACCROCHE')) {?>
                        <tr>
                            <th><label for="PAG_ACCROCHE">Texte d'accroche</label></th>
                            <td><textarea name="PAG_ACCROCHE" cols="60" rows="8" id="PAG_ACCROCHE"><?php echo secureInput($row['PAG_ACCROCHE'])?></textarea></td>
                        </tr>
                        <?php } ?>
                        <tr>
                            <th><label>Colonne</label></th>
                            <td>
                                <input type="checkbox" name="PAG_MASQUERGAUCHE" id="PAG_MASQUERGAUCHE" value="1"<?php if ($row['PAG_MASQUERGAUCHE']) echo ' checked'?>>
                                <label for="PAG_MASQUERGAUCHE">Masquer la colonne de gauche</label>
                                <br>
                                <input type="checkbox" name="PAG_MASQUERDROITE" id="PAG_MASQUERDROITE" value="1"<?php if ($row['PAG_MASQUERDROITE']) echo ' checked'?>>
                                <label for="PAG_MASQUERDROITE">Masquer la colonne de droite</label>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </fieldset>
            <fieldset class="tab">
                <legend>Référencement</legend>
                <table>
                    <tbody>
                        <tr>
                            <th><label for="PAG_TITRE_REFERENCEMENT">Title</label></th>
                            <td><input name="PAG_TITRE_REFERENCEMENT" type="text" id="PAG_TITRE_REFERENCEMENT" value="<?php echo secureInput($row['PAG_TITRE_REFERENCEMENT'])?>" size="45" data-maxchar="70"></td>
                        </tr>
                        <tr>
                            <th><label for="PAG_METADESCRIPTION">Metadescription</label></th>
                            <td><textarea name="PAG_METADESCRIPTION" cols="50" rows="9" id="PAG_METADESCRIPTION" data-maxchar="200"><?php echo secureInput($row['PAG_METADESCRIPTION'])?></textarea></td>
                        </tr>
                    </tbody>
                </table>
            </fieldset>
            <table>
                <tfoot>
                    <tr>
                        <td>
                            <input type="hidden" name="idtf" value="<?php echo $oPage->getID()?>">
                            <input type="hidden" name="PFM" value="<?php echo secureInput($_GET['PFM'])?>">
                            <input type="submit" name="UpdateLight" value="<?php echo gettext('UPDATE')?>" class="modifier">
                        </td>
                    </tr>
                </tfoot>
            </table>
        </form>
    </div>
    <?php include('../include/inc.bo_bandeau_basPopup.php')?>
</body>
</html>
