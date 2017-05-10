<?php
require '../../include/inc.bo_init.php';
Utilisateur::checkConnected();
require CLASS_DIR . 'class.db_page.php';
require CLASS_DIR . 'class.db_paragraphe.php';
require CLASS_DIR . 'class.db_webotheque.php';

$oParagraphe = new Paragraphe_WIDGET($_GET['idtf']);
if ($oParagraphe->exist()) {
    $row = $oParagraphe->getFields();
    $oPage = $oParagraphe->getPage();
} else {
    $row = array();
    $oParagraphe_prec = new Paragraphe($_GET['idtf_prec']);
    if ($oParagraphe_prec->exist()) {
        $oPage = $oParagraphe_prec->getPage();
        $PAR_COLONNE = $oParagraphe_prec->getField('PAR_COLONNE');
        $PAR_POIDS = $oParagraphe_prec->getField('PAR_POIDS') + 1;
    } else {
        $oPage = new Page($_GET['ID_PAGE']);
        $PAR_COLONNE = $_GET['PAR_COLONNE'];
        $PAR_POIDS = 1;
    }
}
$oPage->checkAuthorized();
$oPage->lock();
$oWebotheque = new Webo_WIDGET($row['PAR_CONTENU']);
?>
<!DOCTYPE html>
<html>
<head>
<?php include('../../include/inc.bo_enTete.php') ?>
</head>
<body>
<div id="document">
    <?php include('../../include/inc.bo_bandeau_haut.php')?>
    <div id="corps">
        <div id="bo_contenu" class="creation">
            <form method="post" action="PRT_Submit.php" class="creation">
                <fieldset>
                    <legend><?php echo gettext('Widget')?></legend>
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
                                    <input type="hidden" name="PAR_COLONNE" value="<?php echo $PAR_COLONNE?>">
                                    <input type="submit" name="Insert" value="<?php echo gettext('INSERT')?>" class="ajouter">
                                    <input type="hidden" name="Insert" value="Insert">
                                    <?php } ?>
                                    <input type="hidden" name="PRT_CODE" value="PRT_WIDGET">
                                    <input type="button" name="retour" onclick="window.location.href='../cms_pseudo.php?idtf=<?php echo $oPage->getID()?>&amp;PFM=1'" value="<?php echo gettext('Retour')?>" class="retour">
                                </td>
                            </tr>
                        </tfoot>
                        <tbody>
                            <tr>
                                <th><label for="PAR_TITRE"><?php echo gettext('Titre')?></label></th>
                                <td>
                                    <input name="PAR_TITRE" type="text" id="PAR_TITRE" value="<?php echo secureInput($row['PAR_TITRE'])?>" size="80" maxlength="200">
                                    <script>document.getElementById('PAR_TITRE').focus()</script>
                                </td>
                            </tr>
                            <tr>
                                <th><label>Affichage</label></th>
                                <td>
                                    <input type="checkbox" name="PAR_MOBILEHIDDEN" id="PAR_MOBILEHIDDEN" value="1"<?php if ($row['PAR_MOBILEHIDDEN']) echo ' checked'?>>
                                    <label for="PAR_MOBILEHIDDEN">Masquer sur mobile</label>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="ID_WEBOTHEQUE" class="isNotNull"><?php echo gettext('Widget')?></label></th>
                                <td>
                                    <input type="hidden" name="ID_WEBOTHEQUE" id="ID_WEBOTHEQUE" value="<?php echo secureInput($row['PAR_CONTENU'])?>" required>
                                    <input type="text" name="LIEN_LIBELLE" id="LIEN_LIBELLE" disabled value="<?php if ($oWebotheque->exist()) echo secureInput($oWebotheque->getField('WEB_LIBELLE') . ' (' . $oWebotheque->getID() . ')')?>" size="50">
                                    <a href="<?php echo SERVER_ROOT?>webotheque/web_choisirPopup.php?WBT_CODE=WBT_WIDGET&amp;IDENTIFIANT=ID_WEBOTHEQUE&amp;TEXTE=LIEN_LIBELLE" class="action popup"><?php echo gettext('Choisir')?></a>
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
