<?php
require '../../include/inc.bo_init.php';
Utilisateur::checkConnected();
require CLASS_DIR . 'class.Editor.php';
require CLASS_DIR . 'class.db_page.php';
require CLASS_DIR . 'class.db_paragraphe.php';
$dbh = DB::getInstance();
$oParagraphe = new Paragraphe_TXT($_GET['idtf']);
$PRS_CODE = '';
if ($oParagraphe->exist()) {
    $row = $oParagraphe->getFields();
    $oPage = $oParagraphe->getPage();
    $PAR_COLONNE = $oParagraphe->getField('PAR_COLONNE');
    $PRS_CODE = $oParagraphe->getField('PRS_CODE');
} else {
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
    if (! $oPage->exist() || ! $PAR_COLONNE) {
        // si jamais les paramètres sont manipulés et erronés
        header('Location: ' . SERVER_ROOT . 'cms');
        exit();
    }
    $oParagraphe_tmp = Paragraphe_TXT::createTempParagraphe($oPage->getID(), $PAR_COLONNE, $PAR_POIDS);
}
$oPage->checkAuthorized();
$oPage->lock();
$tiny = ($PAR_COLONNE == 'PAR_CENTRAL') ? 'paragraphe' : 'module';
?>
<!DOCTYPE html>
<html>
<head>
    <?php include('../../include/inc.bo_enTete.php')?>
    <script src="<?php echo SERVER_ROOT?>include/js/autoSave.js"></script>
    <script src="<?php echo SERVER_ROOT?>include/js/autoSave-<?php echo substr($_SESSION['S_LNG_CODE'], 0, 2)?>.js"></script>
    <script src="<?php echo SERVER_ROOT ?>include/js/ajx_liaison.js"></script>
    <script src="<?php echo SERVER_ROOT ?>include/js/onglet.js"></script>
    <script>
    ajaxLiaison.init('paragraphe', <?php echo $oParagraphe->getID();?>);
    </script>
    <?php Editor :: header()?>
    <script>
    editorInit('<?php echo $tiny?>', new Array('PAR_CONTENU'),'<?php echo $oPage->getField('ID_STYLEDYNAMIQUE')?>', '<?php echo $PRS_CODE ?>');
    autoSave.outputDiv = 'autoSaveTxt';
    <?php if ($oParagraphe->exist() && $oParagraphe->getField('PAR_BROUILLON') != '') { ?>
    autoSave.element = 'PAR_BROUILLON';
    <?php } ?>
    autoSave.load('PAR_CONTENU',<?php echo isset($oParagraphe_tmp) ? $oParagraphe_tmp->getID() : $oParagraphe->getID() ?>, <?php echo $oPage->getID() ?>);
    </script>
</head>
<body>
<div id="document">
    <?php include('../../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">
            <form method="post" action="PRT_Submit.php" class="creation">
                <table>
                    <tfoot>
                        <tr>
                            <td colspan="2">
                                <?php if ($oParagraphe->exist()) { ?>
                                <input type="hidden" name="idtf" id="idtf" value="<?php echo $oParagraphe->getID()?>">
                                <input type="submit" name="Update" value="<?php echo gettext('UPDATE')?>" class="modifier">
                                <input type="hidden" name="Update" value="Update">
                                <input type="button" name="retour" onclick="autoSave.undoSave();" value="<?php echo gettext('Retour')?>" class="retour">
                                <?php } else { ?>
                                <input type="hidden" name="ID_PARAGRAPHE_TEMP" id="ID_PARAGRAPHE_TEMP" value="<?php echo $oParagraphe_tmp->getID()?>">
                                <input type="hidden" name="PAR_POIDS" value="<?php echo $PAR_POIDS?>">
                                <input type="hidden" name="PAR_COLONNE" value="<?php echo $PAR_COLONNE?>">
                                <input type="submit" name="Insert" value="<?php echo gettext('INSERT')?>" class="ajouter">
                                <input type="hidden" name="Insert" value="Insert">
                                <input type="button" name="retour" onclick="autoSave.deleteTempParagraphe();" value="<?php echo gettext('Retour')?>" class="retour">
                                <?php } ?>
                                <input type="hidden" name="ID_PAGE" value="<?php echo $oPage->getID()?>">
                                <input type="hidden" name="PRT_CODE" value="PRT_TXT">
                            </td>
                        </tr>
                    </tfoot>
                    <tbody>
                        <tr>
                            <td colspan="2">
                                <fieldset<?php if (CMS::getCurrentSite()->hasModule(new Module('MOD_ENSAVOIRPLUS'))) {?> class="tab"<?php }?>>
                                    <legend>Paragraphe rédactionnel</legend>
                                    <table>
                                        <tbody>
                                            <tr>
                                                <th><label for="PAR_TITRE">Titre</label></th>
                                                <td><input name="PAR_TITRE" type="text" id="PAR_TITRE" value="<?php echo secureInput($row['PAR_TITRE'])?>" size="80" maxlength="200"><script>document.getElementById('PAR_TITRE').focus()</script></td>
                                            </tr>
                                            <tr>
                                                <th><label>Affichage</label></th>
                                                <td>
                                                    <input type="checkbox" name="PAR_MOBILEHIDDEN" id="PAR_MOBILEHIDDEN" value="1"<?php if ($row['PAR_MOBILEHIDDEN']) echo ' checked'?>>
                                                    <label for="PAR_MOBILEHIDDEN">Masquer sur mobile</label>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th><label>Héritable</label></th>
                                                <td>
                                                    <input type="radio" name="PAR_HERITABLE" id="PAR_HERITABLE_2" value="2"<?php if ($row['PAR_HERITABLE'] == '2') echo ' checked'?>>
                                                    <label for="PAR_HERITABLE_2"><?php echo gettext('Oui')?> <?php echo gettext('avec_styles')?></label>
                                                    <input type="radio" name="PAR_HERITABLE" id="PAR_HERITABLE_1" value="1"<?php if ($row['PAR_HERITABLE'] == '1') echo ' checked'?>>
                                                    <label for="PAR_HERITABLE_1"><?php echo gettext('Oui')?> <?php echo gettext('sans_styles')?></label>
                                                    <input type="radio" name="PAR_HERITABLE" id="PAR_HERITABLE_0" value="0"<?php if (!$row['PAR_HERITABLE']) echo ' checked'?>>
                                                    <label for="PAR_HERITABLE_0"><?php echo gettext('Non')?></label>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="2">
                                                    <div id="autoSaveTxt"></div>
                                                    <textarea id="PAR_CONTENU" name="PAR_CONTENU" style="width:100%" rows="30" cols="80"><?php echo secureInput($row['PAR_CONTENU'])?></textarea>
                                                    <input type="hidden" value="<?php echo secureInput($row['PAR_BROUILLON']) ?>" id="PAR_BROUILLON">
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </fieldset>

                                <?php if (CMS::getCurrentSite()->hasModule(new Module('MOD_ENSAVOIRPLUS'))) {?>
                                <fieldset class="tab">
                                    <legend>En savoir plus</legend>
                                    <table>
                                        <tbody>
                                            <?php if (CMS::getCurrentSite()->hasModule(new Module('MOD_WEBOTHEQUE_DOCUMENT'))) { ?>
                                            <tr>
                                                <th><label>Document(s)</label></th>
                                                <td>
                                                    <a href="<?php echo SERVER_ROOT?>webotheque/web_choisirPopup.php?AJAX=WBT_DOCUMENT&amp;WBT_CODE=WBT_DOCUMENT" class="action popup">Ajouter un document</a>
                                                    <div id="WBT_DOCUMENT" class="ajax liaison_webotheque hasText:Libellé_du_lien"></div>
                                                </td>
                                            </tr>
                                            <?php } ?>
                                            <tr>
                                                <th><label>Lien(s) interne(s)</label></th>
                                                <td>
                                                    <a href="<?php echo SERVER_ROOT?>cms/cms_choisirLienInternePopup.php?AJAX=ID_PAGE" class="action popup">Ajouter un lien interne</a>
                                                    <div id="ID_PAGE" class="ajax liaison_page hasText:Libellé_du_lien"></div>
                                                </td>
                                            </tr>
                                            <?php if (CMS::getCurrentSite()->hasModule(new Module('MOD_WEBOTHEQUE_LIENEXTERNE'))) { ?>
                                            <tr>
                                                <th><label>Lien(s) externe(s)</label></th>
                                                <td>
                                                    <a href="<?php echo SERVER_ROOT?>webotheque/web_choisirPopup.php?AJAX=WBT_LIENEXTERNE&amp;WBT_CODE=WBT_LIENEXTERNE" class="action popup">Ajouter un lien externe</a>
                                                    <div id="WBT_LIENEXTERNE" class="ajax liaison_webotheque hasText:Libellé_du_lien"></div>
                                                </td>
                                            </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </fieldset>
                                <?php }?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </form>
        </div>
    </div>
    <?php include('../../include/inc.bo_bandeau_bas.php')?>
</div>
</body>
</html>
