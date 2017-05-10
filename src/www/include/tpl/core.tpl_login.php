<?php
$dbh   = DB::getInstance();
$oPage = CMS::getCurrentSite()->getCurrentPage();

if (Utilisateur::isConnected()) {
    Page::setNoCache();
}

// Affichage du formulaire de génération d'un mail d'une demande de changement de mot de passe ou d'activation de compte ==> Depuis le lien "Mot de passe perdu ?"
if ($_GET['generateRecovery']) {
    $bRecoveryProcess = true;
// Demande d'envoie d'un mail de changement de mot de passe ou d'activation de compte ==> Depuis le lien "Mot de passe perdu ?" et après avoir renseigné un login
} else if ($_POST['generateRecovery'] && $_POST['recoveryLOGIN']) {
    $bRecoveryProcess = true;
    try {
        $stmt = $dbh->prepare("select * from UTILISATEUR where UTI_LOGIN=:UTI_LOGIN");
        $stmt->bindValue(':UTI_LOGIN', $_POST['recoveryLOGIN'], PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        if (!empty($row)) {
            $oUtilisateur = new Utilisateur($row['ID_UTILISATEUR']);
            $oUtilisateur->setFields($row);
            $recoveryType = $oUtilisateur->getField('UTI_LASTCONNEXION')?'forgotten':'activate';
            $oUtilisateur->generateRecoveryPwdNotification();
            $bGenerateRecovery = true;
        } else {
            throw new Exception("L'identifiant ne correspond à aucun compte utilisateur existant.", E_USER_WARNING);
        }
    } catch (Exception $e) {
        $sErrLogin = '<p>' . secureInput($e->getMessage()) . '</p>';
    }
// Demande d'envoie d'un mail de changement de mot de passe ou d'activation de compte ==> Depuis une précédante demande expirée
} else if ($_POST['generateRecovery'] && $_POST['recoveryKey']) {
    $bRecoveryProcess = true;
    try {
        $oUtilisateur = Utilisateur::getUtilisateurByRecoveryKey($_POST['recoveryKey']);
        $recoveryType = $oUtilisateur->getField('UTI_LASTCONNEXION')?'forgotten':'activate';
        $oUtilisateur->generateRecoveryPwdNotification();
        $bGenerateRecovery = true;
    } catch (Exception $e) {
        $bRecoveryProcess = false;
        $sErrLogin = '<p>' . secureInput($e->getMessage()) . '</p>';
    }
// Vérification de la validité d'une demande de changement de mot de passe ou d'activation de compte permettant d'afficher le formulaire de modification/création du mot de passe ==> Lien depuis email
} else if ($_GET['recoveryKey']) {
    $bRecoveryProcess = true;
    try {
        $bcheckRecoveryKey = Utilisateur::checkRecoveryKey($_GET['recoveryKey']);
        $recoveryType = in_array($_GET['recoveryType'], array('activate', 'forgotten','pwdmustbechanged'))?$_GET['recoveryType']:'forgotten';
        if ($recoveryType == 'pwdmustbechanged') {
            $sErrLogin = '<p>L\'administrateur du site demande la modification des mots de passe. Merci de bien vouloir actualiser votre mot de passe pour poursuivre votre navigation.</p>';
        }
    } catch (Exception $e) {
        $bRecoveryProcess = false;
        $sErrLogin = '<p>' . secureInput($e->getMessage()) . '</p>';
    }
// Activation du compte ou changement effectif du mot de passe
} else if (isset($_POST['recoveryKey']) && isset($_POST['UTI_LOGIN']) && isset($_POST['newPASSWORD']) && isset($_POST['newPASSWORD_CONFIRM'])) {
    $bRecoveryProcess = true;
    try {
        try {
            $oUtilisateur = Utilisateur::getUtilisateurByRecoveryKey($_POST['recoveryKey']);
        } catch (Exception $e) {
            $bRecoveryProcess = false;
            throw new Exception($e->getMessage(), $e->getCode());
        }
        $recoveryType = $oUtilisateur->getField('UTI_LASTCONNEXION')?'forgotten':'activate';
        // Mise à jour ou création du mot de passe
        Utilisateur::setRecoveryPwd($_POST['recoveryKey'], $_POST['UTI_LOGIN'], $_POST['newPASSWORD'], $_POST['newPASSWORD_CONFIRM']);
        // On n'est plus dans la section de traitement d'une demande de changement de mot de passe,
        // on réalise à présent l'authentification à proprement parlée
        $_POST['UTI_PASSWORD'] = $_POST['newPASSWORD'];
        $bRecoveryProcess = false;
    } catch (Exception $e) {
        $sErrLogin = '<p>' . secureInput($e->getMessage()) . '</p>';
    }

}



if (is_numeric($_GET['Deconnection']) && Utilisateur::isConnected()) {
    Utilisateur::getConnected()->logout();
    $oPageDeconnection = new Page($_GET['Deconnection'], CMS::$mode);
    if ($oPageDeconnection->exist()) {
        $oPageDeconnection->redirect();
    }
} elseif (!empty($_POST['UTI_LOGIN']) && !empty($_POST['UTI_PASSWORD'])) {
    $aLogInfo = Utilisateur::login($_POST['UTI_LOGIN'], $_POST['UTI_PASSWORD']);
    if ($aLogInfo['statut'] === true) {
        Utilisateur::getConnected()->initSession(CMS::getCurrentSite()->getID());
        http_response_code(303);
        if (is_numeric($_POST['idtfSecure'])) {
            $oPageSecure = new Page($_POST['idtfSecure'], CMS::$mode);
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
        $sErrLogin = '<p>' . nl2br(encode($aLogInfo['message']), false) . '</p>';
    } else if ($aLogInfo['statut'] == 'password_mustbechanged') {
        $bRecoveryProcess = true;
        $recoveryType = 'forgotten';
        $sErrLogin = '<p>' . secureInput($aLogInfo['message']) . '</p>';
        $_GET['recoveryKey'] = $_POST['recoveryKey'] = $_REQUEST['recoveryKey'] = $aLogInfo['hash'];
    } else {
        CMS::getCurrentSite()->setSecuredID($_POST['idtfSecure']);
        $sErrLogin = '<p>L\'identifiant ou le mot de passe est incorrect.</p>';
    }
}
?>
<div class="tpl_login">
<?php if ($bRecoveryProcess) { ?>
    <?php
    // Notification mail envoyée pour une nouvelle demande de changement de mot de passe ou d'activation de compte
    if ($bGenerateRecovery === true) {
    ?>
        <form method="get" action="<?php echo $oPage->getURLESCAPE()?>">
            <?php
            switch ($recoveryType) {
                case 'activate':
                    $legend = 'Récupération de ma demande d\'activation de compte';
                    break;
                case 'forgotten':
                    $legend = 'Mot de passe perdu';
                    break;
            }
            ?>
            <fieldset class="groupeQuestion">
                <legend><span><?php echo encode($legend);?></span></legend>
                <p>Un lien vient d'être envoyé à l'adresse mail "<?php echo encode($oUtilisateur->getField('UTI_EMAIL'));?>".</p>
                <p class="action">
                    <span class="submit">
                        <input type="submit" value="S'identifier" class="submit">
                        <input type="hidden" name="idtf" value="<?php echo $oPage->getID()?>">
                    </span>
                </p>
            </fieldset>
        </form>
    <?php
    // Affichage du formulaire d'une demande de changement de mot de passe ou d'activation de compte ==> Depuis le lien "Mot de passe perdu ?"
    } else if ($_GET['generateRecovery'] || isset($_POST['recoveryLOGIN'])) {
    ?>
        <form method="post" action="<?php echo $oPage->getURLESCAPE()?>">
            <fieldset class="groupeQuestion">
                <legend><span>Mot de passe perdu</span></legend>
                <div class="innerGroupeQuestion">
                    <?php if (isset($sErrLogin)) {?>
                    <div class="message_error"><?php echo $sErrLogin?></div>
                    <?php } ?>
                    <p>
                        <label for="recoveryLOGIN"><?php echo $oTemplate->i18n('Login')?></label>
                        <input required type="text" name="recoveryLOGIN" id="recoveryLOGIN" size="30" value="<?php echo encode($_POST['recoveryLOGIN'], false)?>">
                    </p>
                </div>
            </fieldset>
            <p class="action">
                <span class="submit">
                    <input type="submit" name="generateRecovery" value="Modifier mon mot de passe" class="submit"  tabindex="2">
                    <input type="hidden" name="idtf" value="<?php echo $oPage->getID()?>">
                </span>
            </p>
        </form>
    <?php
    // Clé de contrôle expirée
    } else if ($bcheckRecoveryKey === false) {
    ?>
        <form method="post" action="<?php echo $oPage->getURLESCAPE()?>">
            <?php
            switch ($recoveryType) {
                case 'activate':
                    $help = 'Veuillez soumettre une nouvelle demande d\'activation de compte pour générer un nouveau lien.';
                    $submit = 'Demande d\'activation de compte';
                    break;
                case 'forgotten':
                    $help = 'Veuillez soumettre une nouvelle demande de changement de mot de passe pour générer un nouveau lien.';
                    $submit = 'Demande de changement de mot de passe';
                    break;
            }
            ?>
            <fieldset class="groupeQuestion">
                <legend><span>Délais de validité dépassée</span></legend>
                <div class="innerGroupeQuestion">
                    <p>Le délais de validité du mail est dépassé.</p>
                    <p><?php echo encode($help)?></p>
                </div>
                <p class="action">
                    <span class="submit">
                        <input type="submit" value="<?php echo encode($submit, false)?>" name="generateRecovery" class="submit">
                        <input type="hidden" name="recoveryKey" value="<?php echo encode($_GET['recoveryKey'], false)?>">
                        <input type="hidden" name="idtf" value="<?php echo $oPage->getID()?>">
                    </span>
                </p>
            </fieldset>
        </form>

    <?php
    // Formulaire de saisie des informations d'authentification lors d'une demande de changement de mot de passe ou d'activation de compte
    } else {
    ?>
        <form method="post" action="<?php echo $oPage->getURLESCAPE()?>">
            <fieldset class="groupeQuestion">
                <?php
                switch ($recoveryType) {
                    case 'activate':
                        $legend = 'Activer mon compte utilisateur';
                        break;
                    case 'forgotten':
                    case 'pwdmustbechanged':
                        $legend = 'Modifier votre mot de passe';
                        break;
                }
                ?>
                <legend><span><?php echo encode($legend);?></span></legend>
                <div class="innerGroupeQuestion">
                    <?php if (isset($sErrLogin)) {?>
                    <div class="message_error"><?php echo $sErrLogin?></div>
                    <?php } ?>
                    <?php
                    if ($recoveryType == 'activate') {
                        echo '<p>' . secureInput(Utilisateur::getValidatePwdHelper()) . '</p>';
                    }
                    ?>
                    <p>
                        <label for="UTI_LOGIN"><?php echo $oTemplate->i18n('Login')?></label>
                        <input required type="text" name="UTI_LOGIN" id="UTI_LOGIN" size="30" value="<?php echo encode($_POST['UTI_LOGIN'], false)?>">
                    </p>
                    <p>
                        <label for="newPASSWORD">Mot de passe</label>
                        <input size="30" type="password" required
                            name="newPASSWORD" id="newPASSWORD" class="pwdcheck"
                            pattern="<?php echo encode(Utilisateur::getValidateRegPatternPwd(), false);?>"
                            title="<?php echo encode(Utilisateur::getValidatePwdHelper(), false);?>"
                            onchange="if(this.checkValidity && this.checkValidity()) document.getElementById('newPASSWORD_CONFIRM').pattern = preg_quote(this.value);">
                    </p>
                    <p>
                        <label for="newPASSWORD_CONFIRM">Confirmer le mot de passe</label>
                        <input size="30" type="password" required
                            name="newPASSWORD_CONFIRM" id="newPASSWORD_CONFIRM" class="pwdcheck"
                            pattern="<?php echo secureInput(Utilisateur::getValidateRegPatternPwd());?>"
                            title="la confirmation du mot de passe doit être identique au mot de passe">
                    </p>
                </div>
                <p class="action">
                    <span class="submit">
                        <input type="submit" name="Find" id="submit" value="Enregistrer et se connecter" class="submit" tabindex="4">
                        <input type="hidden" name="recoveryKey" value="<?php echo secureInput($_REQUEST['recoveryKey'])?>">
                        <input type="hidden" name="idtf" value="<?php echo $oPage->getID()?>">
                    </span>
                </p>
            </fieldset>
        </form>
    <?php
    }
    ?>
<?php } else if ($oUtilisateur = Utilisateur::getConnected()) { ?>
    <form action="<?php echo $oPage->getURLESCAPE()?>" method="post">
        <h2><?php echo encode($oUtilisateur->getField('UTI_NOM') . ' ' . $oUtilisateur->getField('UTI_PRENOM'), false)?></h2>
        <?php if (sizeof($_SESSION['Sa_ID_GROUPE']) > 0) { ?>
        <ul>
            <?php
            $sql = "select * from GROUPE where ID_GROUPE in (" . implode(',', $_SESSION['Sa_ID_GROUPE']) . ") order by GRP_LIBELLE";
            foreach ($dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) { ?>
            <li><?php echo encode($row['GRP_LIBELLE'], false)?></li>
            <?php } ?>
        </ul>
        <?php } ?>
        <?php if ($oUtilisateur->isPageContributor() || $oUtilisateur->isModuleContributor) { ?>
        <p class="alignright"><a href="<?php echo SERVER_ROOT ?>cms/"><?php echo $oTemplate->i18n('Contribuer')?></a></p>
        <?php } ?>
        <p class="alignright"><a <?php echo $oPage->getAnchor(array('Deconnection'=> $oPage->getID()))?>><?php echo $oTemplate->i18n('Deconnexion')?></a></p>
    </form>
<?php } else { ?>
    <form action="<?php echo $oPage->getURLESCAPE()?>" method="post">
        <fieldset class="groupeQuestion">
            <legend><span><?php echo $oTemplate->i18n('Titre')?></span></legend>
            <div class="innerGroupeQuestion">
                <?php if (isset($sErrLogin)) {?>
                <p><strong><?php echo $oTemplate->i18n('Erreur_authentification')?></strong></p>
                <div class="message_error"><?php echo $sErrLogin?></div>
                <?php } ?>
                <p>
                    <label for="UTI_LOGIN_TPL"><?php echo $oTemplate->i18n('Login')?></label>
                    <input required type="text" name="UTI_LOGIN" id="UTI_LOGIN_TPL" size="30" value="<?php echo secureInput($_POST['UTI_LOGIN'])?>" title="<?php echo $oTemplate->i18n('Login')?>">
                </p>
                <p>
                    <label for="UTI_PASSWORD_TPL"> <?php echo $oTemplate->i18n('Password')?></label>
                    <input required type="password" name="UTI_PASSWORD" id="UTI_PASSWORD_TPL" size="30" title="<?php echo $oTemplate->i18n('Password')?>">
                </p>
            </div>
        </fieldset>
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
                } ?>
                <input type="hidden" name="requestKey" value="<?php echo secureInput($requestKey)?>">
                <input type="submit" name="login" value="<?php echo $oTemplate->i18n('Action')?>" class="submit">
            </span>
            <a <?php echo $oPage->getAnchor(array('generateRecovery' => 1)) ?>><?php echo $oTemplate->i18n('login_mdp_oublie')?></a>
        </p>
    </form>
<?php } ?>
</div>
