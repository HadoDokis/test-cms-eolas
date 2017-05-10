<?php
$dbh   = DB::getInstance();
$oPage = CMS::getCurrentSite()->getCurrentPage();

if (Utilisateur::isConnected()) {
    Page::setNoCache();
}

if (!$oPageSpe = CMS::getCurrentSite()->getSpecialePage('PGS_AUTHENTIFICATION') ) {
    $oPageSpe = $oPage;
}
if (is_numeric($_GET['Deconnection']) && Utilisateur::isConnected()) {
    Utilisateur::getConnected()->logout();
    $oPageDeconnection = new Page($_GET['Deconnection'], CMS :: $mode);
    if ($oPageDeconnection->exist()) {
        $oPageDeconnection->redirect();
    }
} elseif (!empty($_POST['UTI_LOGIN_MDL']) && !empty($_POST['UTI_PASSWORD_MDL'])) {
    $aLogInfo = Utilisateur::login($_POST['UTI_LOGIN_MDL'], $_POST['UTI_PASSWORD_MDL']);
    if ($aLogInfo['statut'] === true) {
        Utilisateur::getConnected()->initSession(CMS::getCurrentSite()->getID());
        http_response_code(303);
        if (is_numeric($_POST['idtfSecure'])) {
            $oPageSecure = new Page($_POST['idtfSecure'], CMS :: $mode);
            if ($oPageSecure->exist()) {
                $param = array();
                if (!empty($_POST['requestKey'])) {
                    $requestKey  = base64_decode($_POST['requestKey']);
                    $param = unserialize($requestKey);
                }
                $oPageSecure->redirect($param);
            }
        }
        $oPage->redirect();
    } else if (in_array($aLogInfo['statut'], array('last_attempt', 'blocked', 'locked'))) {
        CMS::getCurrentSite()->setSecuredID($_POST['idtfSecure']);
        $sErrLogin = $aLogInfo['message'];
   } else if ($aLogInfo['statut'] == 'password_mustbechanged') {
       $oPageSpe->redirect(array('recoveryKey' => $aLogInfo['hash'], 'recoveryType' => 'pwdmustbechanged'));
    } else {
        CMS::getCurrentSite()->setSecuredID($_POST['idtfSecure']);
    }
}
?>
<div class="tpl_loginMDL">
    <form action="<?php echo $oPage->getURLESCAPE()?>" method="post">
<?php if ($oUtilisateur = Utilisateur::getConnected()) { ?>
        <h2><?php echo encode($oUtilisateur->getField('UTI_NOM') . ' ' . $oUtilisateur->getField('UTI_PRENOM'), false)?></h2>
        <?php if ($oUtilisateur->isPageContributor() || $oUtilisateur->isModuleContributor) { ?>
        <p class="alignright"><a href="<?php echo SERVER_ROOT ?>cms/"><?php echo $oTemplate->i18n('Contribuer')?></a></p>
        <?php } ?>
        <p class="alignright"><a <?php echo $oPage->getAnchor(array('Deconnection'=> $oPage->getID()))?>><?php echo $oTemplate->i18n('Deconnexion')?></a></p>
<?php } else { ?>
        <h2><?php echo $oTemplate->i18n('Titre')?></h2>
        <div class="groupeQuestion">
            <div class="innerGroupeQuestion">
                <?php if (!empty($_POST['UTI_LOGIN_MDL'])) { ?>
                    <p><strong><?php echo $oTemplate->i18n('Erreur_authentification')?></strong></p>
                    <?php
                    if (!empty($sErrLogin)) {
                        echo '<p class="message_error">'. encode($sErrLogin).'</p>';
                    }
                    ?>
                <?php } ?>
                <p>
                    <label for="UTI_LOGIN_MDL" class="enLigne"><?php echo $oTemplate->i18n('Login')?></label>
                    <input required type="text" name="UTI_LOGIN_MDL" id="UTI_LOGIN_MDL" size="15" value="<?php echo secureInput($_POST['UTI_LOGIN_MDL'])?>" title="<?php echo $oTemplate->i18n('Login')?>">
                </p>
                <p>
                    <label for="UTI_PASSWORD_MDL" class="enLigne"><?php echo $oTemplate->i18n('Password')?></label>
                    <input required type="password" name="UTI_PASSWORD_MDL" id="UTI_PASSWORD_MDL" size="15" title="<?php echo $oTemplate->i18n('Password')?>">
                </p>
                <p class="action">
                    <span class="submit">
                        <input type="hidden" name="idtf" value="<?php echo $oPage->getID()?>">
                        <input type="hidden" name="idtfSecure" value="<?php echo CMS::getCurrentSite()->getSecuredID()?>">
                        <?php
                        // On récupère l'ensemble des parametres en GET (sauf l'idtf) pour les replacer dans la redirection si l'authentification est correcte
                        $requestKey = "";
                        if (!empty($_POST['requestKey'])) {
                            $requestKey = $_POST['requestKey'];
                        } elseif (!empty($_GET)) {
                            unset($_GET['idtf']);
                            $requestKey = base64_encode(serialize($_GET));
                        }
                        ?>
                       <input type="hidden" name="requestKey" value="<?php echo secureInput($requestKey)?>">
                       <input type="submit" name="login" value="<?php echo $oTemplate->i18n('Action')?>" class="submit">
                    </span>
                </p>
                <p class="alignright">
                    <a <?php echo $oPageSpe->getAnchor(array('generateRecovery' => 1)) ?>><?php echo $oTemplate->i18n('login_mdp_oublie')?></a>
                </p>
            </div>
        </div>
<?php } ?>
    </form>
</div>
