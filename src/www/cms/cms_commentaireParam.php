<?php
require '../include/inc.bo_init.php';
require CLASS_DIR . 'class.Editor.php';
require CLASS_DIR . 'class.db_commentaire.php';

CMS::checkAccess(new Module('MOD_COMMENTAIRE'), array('PRO_ROOT_SITE'));

$dhb = DB :: getInstance() ;

$oExterne = CommentaireParametrage::getParametrageForSite();
if ($oExterne->exist()) {
    $oExterne->checkAuthorized();
}
// Chargement des valeurs (valeurs par défauts si non existant)
$row = $oExterne->getFields();
?>
<!DOCTYPE html>
<html>
<head>
    <?php include('../include/inc.bo_enTete.php') ?>
    <?php Editor :: header()?>
    <script src="../include/js/ajx_liaison.js"></script>
    <script>
        editorInit('externe', new Array('CPA_MES_DEPOT'));
        editorInit('presque-riche', new Array('CPA_SIGNATUREMAIL'));
        ajaxLiaison.init('commentaireParametrage', <?php echo $oExterne->getID();?>);
    </script>
</head>
<body>
<div id="document">
    <?php $aMenuKey = array('CFG', 'SITE', 'MOD_COMMENTAIRE', 'PARAM'); include('../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">
            <h2><?php echo gettext('com_parametrage_commentaire')?></h2>
            <form method="post" action="cms_commentaireSubmit.php" id="formCreation" class="creation">

            <fieldset>
                <legend><?php echo gettext('com_type_moderation')?></legend>
                <table>
                    <tbody>
                        <tr>
                            <th>
                                <label for="CPA_TYPEMODERATION_AVANT">Type de modération</label>
                                <div class="helper">
                                    --------------------------------------------------------------
                                    <br>
                                    Si l’option est activée les rédacteurs pourront décider d’autoriser, ou non, la possibilité de commenter leurs pages.
                                    <br>
                                    Si la modération «&nbsp;Avant&nbsp;diffusion» est choisie, les commentaires écrits par les internautes ne seront publiés qu’après la validation de l’un des modérateurs.
                                    <br>
                                    Si la modération «&nbsp;Après diffusion&nbsp;» est choisie, les commentaires des internautes seront automatiquement publiés sur le site.
                                    <br><br>
                                    Dans les deux cas, les contributeurs associés au profil ‘Modérateur Pages’ reçoivent un email de notification à chaque fois qu’un internaute dépose un commentaire.
                                    <br><br>
                                    N’oubliez donc pas de déclarer des modérateurs dans le back-office du site (Onglet Administration &gt; Contributeurs &gt;  Profil CMS)&nbsp;!
                                    <br>
                                    --------------------------------------------------------------
                                </div>
                            </th>
                            <td>
                                <input name="CPA_TYPEMODERATION" type="radio" id="CPA_TYPEMODERATION_AVANT" value="1"<?php if ($row['CPA_TYPEMODERATION'] == 1) echo ' checked'?>>
                                <label for="CPA_TYPEMODERATION_AVANT"><?php echo gettext('com_type_moderation_avant')?></label>
                                <input name="CPA_TYPEMODERATION" type="radio" id="CPA_TYPEMODERATION_APRES" value="0"<?php if ($row['CPA_TYPEMODERATION'] == 0) echo ' checked'?>>
                                <label for="CPA_TYPEMODERATION_APRES"><?php echo gettext('com_type_moderation_apres')?></label>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="CPA_AFFICHAGE_DEFAUT_OUI"><?php echo gettext('com_afficher_commentaire_defaut')?></label></th>
                            <td>
                                <input name="CPA_AFFICHAGE_DEFAUT" type="radio" id="CPA_AFFICHAGE_DEFAUT_OUI" value="1"<?php if ($row['CPA_AFFICHAGE_DEFAUT'] == 1) echo ' checked'?>>
                                <label for="CPA_AFFICHAGE_DEFAUT_OUI"><?php echo gettext('Oui')?></label>
                                <input name="CPA_AFFICHAGE_DEFAUT" type="radio" id="CPA_AFFICHAGE_DEFAUT_NON" value="0"<?php if ($row['CPA_AFFICHAGE_DEFAUT'] == 0) echo ' checked'?>>
                                <label for="CPA_AFFICHAGE_DEFAUT_NON"><?php echo gettext('Non')?></label>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </fieldset>
             
            <fieldset>
                <legend>Textes internautes (Front-Office)</legend>
                <table>
                    <tbody>
                        <tr>
                            <th>
                                <label for="CPA_MES_DEPOT">Texte restitué sur formulaire de dépôt </label>
                                <div class="helper">Texte restitué sur le formulaire de dépôt de commentaire sur les pages où le dépôt de commentaire est autorisé</div></th>
                            <td><textarea rows="10" cols="60" name="CPA_MES_DEPOT" id="CPA_MES_DEPOT" required><?php echo secureInput($row['CPA_MES_DEPOT'])?></textarea></td>
                        </tr>
                        <tr>
                            <th><label for="CPA_MES_REMERCIEMENT">Message de retour formulaire de dépôt</label></th>
                            <td><textarea rows="6" cols="60" name="CPA_MES_REMERCIEMENT" id="CPA_MES_REMERCIEMENT" required><?php echo secureInput($row['CPA_MES_REMERCIEMENT']);?></textarea></td>
                        </tr>
                        <tr>
                            <th><label>Associer une page charte de modération des commentaires</label><div class="helper">Libellé du lien vers page de charte de modération</div></th>
                            <td>
                                <input name="CPA_LIBELLELIEN" type="text" id="CPA_LIBELLELIEN" value="<?php echo secureInput($row['CPA_LIBELLELIEN'])?>" size="50">
                                &nbsp;
                            </td>
                        </tr>
                        <tr>
                            <th><label><?php echo gettext('com_lien_interne')?></label></th>
                            <td>
                                <a href="../cms/cms_choisirLienInternePopup.php?AJAX=ID_PAGE" class="action popup"><?php echo gettext('com_ajouter_remplacer')?></a>
                                <div id="ID_PAGE" class="ajax liaison_page onlyOne"></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </fieldset>

            <fieldset>
                <legend><?php echo gettext('com_mail_notification_internautes')?></legend>
                <table>
                    <tbody>
                        <tr>
                            <th><label for="CPA_EMAILNOTIFICATION_OUI">Activer les e-mails de notifications</label></th>
                            <td>
                                <input name="CPA_EMAILNOTIFICATION" type="radio" id="CPA_EMAILNOTIFICATION_OUI" value="1"<?php if ($row['CPA_EMAILNOTIFICATION'] == 1) echo ' checked'?>><label for="CPA_EMAILNOTIFICATION_OUI"><?php echo gettext('Oui')?></label>
                                <input name="CPA_EMAILNOTIFICATION" type="radio" id="CPA_EMAILNOTIFICATION_NON" value="0"<?php if ($row['CPA_EMAILNOTIFICATION'] == 0) echo ' checked'?>><label for="CPA_EMAILNOTIFICATION_NON"><?php echo gettext('Non')?></label>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="CPA_SIGNATUREMAIL"><?php echo gettext('com_mail_signature')?></label></th>
                            <td><textarea rows="10" cols="60" name="CPA_SIGNATUREMAIL" id="CPA_SIGNATUREMAIL" required><?php echo secureInput($row['CPA_SIGNATUREMAIL']);?></textarea></td>
                        </tr>
                        <tr>
                            <th><label for="CPA_COMMENTAIREVALIDE"><?php echo gettext('com_commentaire_valide')?></label></th>
                            <td><textarea rows="6" cols="60" name="CPA_COMMENTAIREVALIDE" id="CPA_COMMENTAIREVALIDE" required><?php echo secureInput($row['CPA_COMMENTAIREVALIDE']);?></textarea></td>
                        </tr>
                        <tr>
                            <th><label for="CPA_COMMENTAIREREFUS"><?php echo gettext('com_commentaire_refuse')?></label></th>
                            <td><textarea rows="6" cols="60" name="CPA_COMMENTAIREREFUS" id="CPA_COMMENTAIREREFUS" required><?php echo secureInput($row['CPA_COMMENTAIREREFUS']);?></textarea></td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2">
                                <input type="hidden" name="idtf" id="idtf" value="<?php echo $oExterne->getID()?>">
                                <input type="submit" name="param" value="<?php echo gettext('com_enregistrer')?>" class="modifier">
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </fieldset>
            </form>
        </div>
        <?php include('../include/inc.bo_bandeau_bas.php')?>
    </div>
</div>
</body>
</html>
