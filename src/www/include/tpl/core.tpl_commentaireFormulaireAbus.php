<?php
include_once CLASS_DIR.'class.db_commentaire.php';

$dbh   = DB::getInstance();
$oPage = CMS::getCurrentSite()->getCurrentPage();
$oModule = new Module('MOD_COMMENTAIRE');
$PAR_TPL_IDENTIFIANT = $_GET['PAR_TPL_IDENTIFIANT'];
if (isset($_POST['ID_COMMENTAIRE']) && intval($_POST['ID_COMMENTAIRE']) > 0) {
    $PAR_TPL_IDENTIFIANT = intval($_POST['ID_COMMENTAIRE']);
}
$oCommentaire = new Commentaire($PAR_TPL_IDENTIFIANT);

if (!CMS::getCurrentSite()->hasModule($oModule) || !$oCommentaire->exist()) {
    Paragraphe::noRender();

    return;
}

if (!$oCommentaire->checkAuthorized(false)) {
     Paragraphe::noRender();

     return;
}

$aInfosComm = $oCommentaire->getTypeInfo();
$retourLink = false;
if ($aInfosComm['MOD_CODE'] != 'MOD_CORE') {
    if (isset($_SESSION['COMMENTS'][$aInfosComm['MOD_CODE']][$oCommentaire->getField('COM_IDLIAISON')]['RETOUR'])) {
        $retourLink = $_SESSION['COMMENTS'][$aInfosComm['MOD_CODE']][$oCommentaire->getField('COM_IDLIAISON')]['RETOUR'];
    }
} else {
    if (isset($_SESSION['COMMENTS'][$PAR_TPL_IDENTIFIANT]['RETOUR'])) {
        $retourLink = $_SESSION['COMMENTS'][$PAR_TPL_IDENTIFIANT]['RETOUR'];
    }
}

$utiPseudo = '';
$utiPseudoReadOnly = false;
$utiMail = '';
$utiMailReadOnly = false;
if ($oUtilisateur = Utilisateur::getConnected()) {
    $utiPseudo = $oUtilisateur->getField('UTI_PRENOM');
    $utiMail = $oUtilisateur->getField('UTI_EMAIL');
    $utiPseudoReadOnly = $utiMailReadOnly = true;
}
if (!empty($_POST['CAB_PSEUDO'])) {
    $utiPseudo = $_POST['CAB_PSEUDO'];
}
if (!empty($_POST['CAB_MAIL'])) {
    $utiMail = $_POST['CAB_MAIL'];
}
if (isset($_POST['NEW_ABUS'])) {
    $utiPseudo = $_POST['CAB_PSEUDO'];
    $utiMail = $_POST['CAB_MAIL'];
    $message = false;
    $error = array();

    //validation
    if(intval($_POST['ID_COMMENTAIRE'] <= 0)) $error[] = $oModule->i18n('com_form_abuscom');
    if(empty($_POST['CAB_PSEUDO']) || trim($_POST['CAB_PSEUDO']) == '') $error[] = $oModule->i18n('com_afe_prenom');
    if (empty($_POST['CAB_MAIL']) || trim($_POST['CAB_MAIL']) == '') {
        $error[] = $oModule->i18n('com_afe_mail');
    } else {
        if(!valideMail($_POST['CAB_MAIL'])) $error[] = $oModule->i18n('com_afe_mail_invalid');
    }
    if(empty($_POST['CAB_MESSAGE']) || trim(strip_tags($_POST['CAB_MESSAGE'])) == '') $error[] = $oModule->i18n('com_afe_decrireabus');

    if (count($error) == 0) {
        if (Commentaire::saveAbus()) {
            $message = true;//message valide + lien vers le retour
        } else {
            $error[] = $oModule->i18n('com_form_submitError');
        }
    }

}

CMS::replaceTITLE($oModule->i18n('com_commentaire_abus_titre')); ?>
<div class="tpl_formulaireAbus">
    <?php if ($message) { ?>

        <p><?php echo $oModule->i18n('com_message_abus_recu');?></p>
        <?php if ($retourLink) { ?>
            <p><a class="bouton" href="<?php echo $retourLink?>"><?php echo $oModule->i18n('com_retour_page');?></a></p>
        <?php }
    } else { ?>
        <form id="form_abus_comment" action="<?php echo $oPage->getURLESCAPE(array('TPL_CODE' => 'TPL_COMMENTAIREFABUS','PAR_TPL_IDENTIFIANT'=>$PAR_TPL_IDENTIFIANT))?>#form_abus_comment" method="post" class="creation">
            <fieldset class="groupeQuestion">
                <legend><span><?php echo $oModule->i18n('com_commentaire_abus_titre');?></span></legend>
                <?php if (count($error) > 0) {
                    echo '<p class="message_error">';
                    foreach ($error as $uneErr) {
                        echo $uneErr . '<br>';
                    }
                    echo '</p>';
                } ?>
                <div class="innerGroupeQuestion">
                    <p>
                        <label for="CAB_PSEUDO"><?php echo $oModule->i18n('com_form_prenom')?></label>
                        <input type="text" value="<?php echo encode($utiPseudo, false);?>" name="CAB_PSEUDO" id="CAB_PSEUDO" required>
                    </p>
                    <p>
                        <label for="CAB_MAIL"><?php echo $oModule->i18n('com_monemail')?></label>
                        <input type="email" value="<?php echo encode($utiMail, false);?>" name="CAB_MAIL" id="CAB_MAIL" required>
                    </p>
                   <p>
                        <label for="ID_COMMENTAIRE"><?php echo $oModule->i18n('com_form_repere')?></label>
                        <input type="hidden" value="<?php echo $PAR_TPL_IDENTIFIANT?>" name="ID_COMMENTAIRE">
                        <?php echo encode($oCommentaire->getField('COM_MESSAGE'));?>
                    </p>
                    <p>
                        <label for="CAB_MESSAGE"><?php echo $oModule->i18n('com_form_decrireabus')?></label>
                        <textarea name="CAB_MESSAGE" id="CAB_MESSAGE" rows="10" cols="50" required><?php echo encode($_POST['CAB_MESSAGE'], false);?></textarea>
                    </p>
                </div>
            </fieldset>
            <p class="action">
                <input type="hidden" value="<?php echo $PAR_TPL_IDENTIFIANT?>" name="ID_COMMENTAIRE">
                <span class="submit"><input type="submit" value="<?php echo $oModule->i18n('com_btn_envoyer');?>" name="NEW_ABUS" class="submit"></span>
            </p>
        </form>
        <?php if ($retourLink) {?>
            <p><a class="bouton" href="<?php echo $retourLink?>"><?php echo $oModule->i18n('com_retour_page');?></a></p>
        <?php }
    } ?>
</div>
