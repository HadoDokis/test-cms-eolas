<?php
require '../../include/inc.bo_init.php';
Utilisateur::checkConnected();
require CLASS_DIR . 'class.db_page.php';
require CLASS_DIR . 'class.db_paragraphe.php';
require CLASS_DIR . 'class.db_webotheque.php';

$oParagraphe = new Paragraphe_APPLIEXTERNE($_GET['idtf']);
if ($oParagraphe->exist()) {
    $row = $oParagraphe->getFields();
    $oPage = $oParagraphe->getPage();
} else {
    $row = array();
    $oParagraphe_prec = new Paragraphe($_GET['idtf_prec']);
    if ($oParagraphe_prec->exist()) {
        $oPage = $oParagraphe_prec->getPage();
        $PAR_POIDS = $oParagraphe_prec->getField('PAR_POIDS') + 1;
    } else {
        $oPage = new Page($_GET['ID_PAGE']);
        $PAR_POIDS = 1;
    }
}
$oPage->checkAuthorized();
$oPage->lock();

$aPAR_TPL_IDENTIFIANT = explode('@', $row['PAR_TPL_IDENTIFIANT']);
$oWebotheque = new Webo_LIENEXTERNE($aPAR_TPL_IDENTIFIANT[0]);
?>
<!DOCTYPE html>
<html>
<head>
    <?php include('../../include/inc.bo_enTete.php')?>
</head>
<body>
<div id="document">
    <?php include('../../include/inc.bo_bandeau_haut.php')?>
    <div id="corps">
        <div id="bo_contenu" class="creation">
            <form method="post" action="PRT_Submit.php" id="formCreation" class="creation">
                <fieldset>
                    <legend><?php echo gettext('Application Externe')?></legend>
                    <table>
                        <tfoot>
                            <tr>
                                <td colspan="2">
                                    <?php if ($oParagraphe->exist()) { ?>
                                    <input type="hidden" name="idtf" value="<?php echo $oParagraphe->getID()?>">
                                    <input type="submit" name="Update" value="<?php echo gettext('UPDATE')?>" class="modifier">
                                    <input type="hidden" name="Update" value="Update">
                                    <?php } else { ?>
                                    <input type="hidden" name="ID_PAGE" value="<?php echo $oPage->getID()?>">
                                    <input type="hidden" name="PAR_POIDS" value="<?php echo $PAR_POIDS?>">
                                    <input type="submit" name="Insert" value="<?php echo gettext('INSERT')?>" class="ajouter">
                                    <input type="hidden" name="Insert" value="Insert">
                                    <?php } ?>
                                    <input type="hidden" name="PRT_CODE" value="PRT_APPLIEXTERNE">
                                    <input type="button" name="retour" onclick="window.location.href='../cms_pseudo.php?idtf=<?php echo $oPage->getID()?>&amp;PFM=1'" value="<?php echo gettext('Retour')?>" class="retour">
                                </td>
                            </tr>
                        </tfoot>
                        <tbody>
                            <tr>
                                <th><label for="PAR_TITRE"><?php echo gettext('Titre')?></label></th>
                                <td><input name="PAR_TITRE" type="text" id="PAR_TITRE" value="<?php echo secureInput($row['PAR_TITRE'])?>" size="80"></td>
                            </tr>
                            <tr>
                                <th><label for="ID_WEBOTHEQUE" class="isNotNull">URL</label></th>
                                <td>
                                    <input type="hidden" name="ID_WEBOTHEQUE" id="ID_WEBOTHEQUE" value="<?php echo $aPAR_TPL_IDENTIFIANT[0] ?>" required>
                                    <input type="text" name="LIEN_LIBELLE" id="LIEN_LIBELLE" value="<?php if ($oWebotheque->exist()) echo secureInput($oWebotheque->getField('WEB_LIBELLE')) ?>" size="50" disabled>
                                    <a href="<?php echo SERVER_ROOT?>webotheque/web_choisirPopup.php?WBT_CODE=WBT_LIENEXTERNE&amp;IDENTIFIANT=ID_WEBOTHEQUE&amp;TEXTE=LIEN_LIBELLE" class="action popup">Choisir l'application</a>
                                </td>
                            </tr>
                            <tr>
                                <th><label>Affichage</label></th>
                                <td>
                                    <input type="checkbox" name="PAR_MOBILEHIDDEN" id="PAR_MOBILEHIDDEN" value="1"<?php if ($row['PAR_MOBILEHIDDEN']) echo ' checked'?>>
                                    <label for="PAR_MOBILEHIDDEN">Masquer sur mobile</label>
                                </td>
                            </tr>
                            <?php if (CMS::getCurrentSite()->hasModule(new Module('MOD_ACCESSIBILITE'))) { ?>
                            <tr>
                                <th><label for="APP_TITLE">Title</label></th>
                                <td><input type="text" name="APP_TITLE" id="APP_TITLE" size="40" value="<?php echo secureInput($aPAR_TPL_IDENTIFIANT[8]) ?>" required></td>
                            </tr>
                            <?php } ?>
                            <tr>
                                <th><label for="APP_LARGEUR"><?php echo gettext('Largeur') ?></label></th>
                                <td>
                                    <input type="text" name="APP_LARGEUR" id="APP_LARGEUR" size="5" value="<?php echo secureInput($aPAR_TPL_IDENTIFIANT[1]) ?>" data-type="integer" required>
                                    <select name="APP_LARGEUR_FIXE" id="APP_LARGEUR_FIXE">
                                        <option value="1"<?php echo ($aPAR_TPL_IDENTIFIANT[2]!=='0' || $aPAR_TPL_IDENTIFIANT[2]=='1') ? ' selected' : ''?>>px</option>
                                        <option value="0"<?php echo ($aPAR_TPL_IDENTIFIANT[2]==='0') ? ' selected' : ''?>>%</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="APP_HAUTEUR"><?php echo gettext('Hauteur') ?></label></th>
                                <td><input type="text" name="APP_HAUTEUR" id="APP_HAUTEUR" size="5" value="<?php echo secureInput($aPAR_TPL_IDENTIFIANT[3]) ?>" data-type="integer" required> px</td>
                            </tr>
                            <tr>
                                <th><label for="APP_FRAMEBORDER">Frameborder</label></th>
                                <td><input type="text" name="APP_FRAMEBORDER" id="APP_FRAMEBORDER" size="5" value="<?php echo secureInput($aPAR_TPL_IDENTIFIANT[4]) ?>" data-type="integer"> px</td>
                            </tr>
                            <tr>
                                <th><label>Scrolling</label></th>
                                <td>
                                    <input type="radio" name="APP_SCROLLING" id="APP_SCROLLING_1" value="yes" <?php echo $aPAR_TPL_IDENTIFIANT[5]=="yes" ?' checked' : '' ?>>
                                    <label for="APP_SCROLLING_1"><?php echo gettext('Oui') ?></label>
                                    <input type="radio" name="APP_SCROLLING" id="APP_SCROLLING_0" value="no" <?php echo $aPAR_TPL_IDENTIFIANT[5]=="no" ? ' checked' : '' ?>>
                                    <label for="APP_SCROLLING_0"><?php echo gettext('Non') ?></label>
                                    <input type="radio" name="APP_SCROLLING" id="APP_SCROLLING_" value="auto" <?php echo ($aPAR_TPL_IDENTIFIANT[5]=="" || $aPAR_TPL_IDENTIFIANT[5]=="auto") ? ' checked' : '' ?>>
                                    <label for="APP_SCROLLING_"><?php echo gettext('Auto') ?></label>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="APP_MARGINHEIGHT">Marginheight</label></th>
                                <td><input type="text" name="APP_MARGINHEIGHT" id="APP_MARGINHEIGHT" size="5" value="<?php echo secureInput($aPAR_TPL_IDENTIFIANT[6]) ?>" data-type="integer"> px</td>
                            </tr>
                            <tr>
                                <th><label for="APP_MARGINWIDTH">Marginwidth</label></th>
                                <td><input type="text" name="APP_MARGINWIDTH" id="APP_MARGINWIDTH" size="5" value="<?php echo secureInput($aPAR_TPL_IDENTIFIANT[7]) ?>" data-type="integer"> px</td>
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
