<?php
require '../include/inc.bo_init.php';
require_once '../include/config_module_admin.php';
require CLASS_DIR . 'class.db_alerte.php';
require ADMIN_PATH . 'include/class/class.cms.application.php';

//* Install nécessaire ?
$app = application::getInstance();
$app->checkInitialInstall();

if (!$app->isUptodate() && !preg_match('/^'.preg_quote(ADMIN_ROOT, '/').'/', $_REQUEST['redirect'])) {
    $needInstall = true;
    $msgInstall = "<p><strong>L'application est en cours de mise à jour par un \"Super Administrateur\"</strong>.
                     <br>(<em>Elle ne peut pas être utilisée avant que la montée de version ne soit finalisée</em>)</p>";
}
//*/

/*
 * Ordre de traitement des processus d'authentification :
 *  * Affichage du formulaire d'une demande de changement de mot de passe ou d'activation de compte ==> Depuis le lien "Mot de passe perdu ?"
 *  * Demande d'envoie d'un mail de changement de mot de passe ou d'activation de compte ==> Depuis le lien "Mot de passe perdu ?" et après avoir renseigné un login
 *  * Demande d'envoie d'un mail de changement de mot de passe ou d'activation de compte ==> Depuis une précédante demande expirée
 *  * Vérification de la validité d'une demande de changement de mot de passe ou d'activation de compte permettant d'afficher le formulaire de modification/création du mot de passe ==> Lien depuis email
 *  * Activation du compte ou changement effectif du mot de passe
 *  * Tentative d'authentification
 */


$oAlerte = Alerte::getCurrent();
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
        $recoveryType = $_GET['recoveryType']=='activate'?'activate':'forgotten';
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
// Tentative d'authentification
if (isset($_POST['UTI_LOGIN']) && isset ($_POST['UTI_PASSWORD'])) {
    $aLogInfo = Utilisateur::login($_POST['UTI_LOGIN'], $_POST['UTI_PASSWORD']);
    if ($aLogInfo['statut'] === true) {
        if (empty($_POST['redirect'])) {
            $sql = "select ID_PAGE from UTILISATEUR where ID_UTILISATEUR=" . Utilisateur::getConnected()->getID();
            $ID_PAGE = $dbh->query($sql)->fetchColumn();
            if (is_numeric($ID_PAGE)) {
                $_POST['redirect'] = SERVER_ROOT . 'cms/cms_pseudo.php?idtf=' . $ID_PAGE;
            }
        }
        $aSite = Utilisateur::getConnected()->getSites(true);
        if (sizeof($aSite) == 1) {
            $oAlerte = CMS::redirect(key($aSite), $_POST['redirect']);
        } else if (empty($aSite)) {
            Utilisateur::getConnected()->logout();
            $sErrLogin = '<p>Vous ne disposer pas de doit suffisant pour vous connecter à l\'espace d\'administration.</p>';
        }
    } elseif (in_array($aLogInfo['statut'], array('last_attempt', 'blocked', 'locked'))) {
        $sErrLogin = '<p>' . nl2br(secureInput($aLogInfo['message']), false) . '</p>';
    } else if ($aLogInfo['statut'] == 'password_mustbechanged') {
        $bRecoveryProcess = true;
        $recoveryType = 'forgotten';
        $sErrLogin = '<p>' . secureInput($aLogInfo['message']) . '</p>';
        $_GET['recoveryKey'] = $_POST['recoveryKey'] = $_REQUEST['recoveryKey'] = $aLogInfo['hash'];
    } else {
        $sErrLogin = '<p>L\'identifiant ou le mot de passe est incorrect.</p>';
    }
// Première phase d'authentificatino réussie
} elseif (Utilisateur::isConnected()) {
    if (isset($_POST['SITE'])) {
        $oAlerte = CMS::redirect($_POST['SITE'], $_POST['redirect']);
    } elseif (isset($_GET['logout'])) {
        Utilisateur::getConnected()->logout();
    } elseif ($oSite = CMS::getCurrentSite()) {
        $oAlerte = CMS::redirect($oSite->getID());
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/HTML; charset=utf-8">
<title>cms.Eolas - Authentification</title>
<meta http-equiv="Content-script-type" content="text/javascript">
<meta http-equiv="Content-style-type" content="text/css">
<meta http-equiv="Content-language" content="<?php echo substr($_SESSION['S_LNG_CODE'], 0, 2)?>">
<meta http-equiv="expires" content="0">
<meta name="Robots" content="noindex, nofollow">
<meta name="Author" content="EOLAS">
<link rel="shortcut icon" href="<?php echo SERVER_ROOT?>images/favicon.ico" type="image/x-icon">
<link rel="stylesheet" href="<?php echo SERVER_ROOT?>include/css/bo.css">
<link rel="stylesheet" href="<?php echo SERVER_ROOT?>include/css/bo_pseudo.css">
<script>var SERVER_ROOT = '<?php echo SERVER_ROOT?>';</script>
<script>var cms_lang = '<?php echo substr($_SESSION['S_LNG_CODE'], 0, 2)?>';</script>
<script src="<?php echo SERVER_ROOT?>include/js/formCtrl.js"></script>
<script src="<?php echo SERVER_ROOT?>include/js/formCtrl-<?php echo substr($_SESSION['S_LNG_CODE'], 0, 2)?>.js"></script>
<script src="<?php echo SERVER_ROOT?>include/js/jquery/jquery.min.js"></script>
<script src="<?php echo SERVER_ROOT?>include/js/jquery/ui/jquery-ui.min.js"></script>
<script src="<?php echo SERVER_ROOT?>include/js/jquery/ui/i18n/datepicker-fr.js"></script>
<script src="<?php echo SERVER_ROOT?>include/js/jquery/colorbox/jquery.colorbox-min.js"></script>
<script src="<?php echo SERVER_ROOT?>include/js/common.js"></script>
<script src="<?php echo SERVER_ROOT?>include/js/coreBo.js"></script>
<style>
    #install {
        margin: auto;
        padding: 10px;
        border: 2px solid red;
        color: red;
        text-align: center;
        background-color: #fff;
    }
    var { font-weight: bold; }
</style>
<script>
function preg_quote(str, delimiter) {
    return String(str)
    .replace(new RegExp('[.\\\\+*?\\[\\^\\]$(){}=!<>|:\\' + (delimiter || '') + '-]', 'g'), '\\$&');
}
</script>
</head>
<body>
<div id="document">
    <div id="corps">
        <p>&nbsp;</p>
        <p>&nbsp;</p>
<?php if ($needInstall) { ?>
        <div id="install"><?php echo $msgInstall;?></div>
<?php } else if ($bRecoveryProcess) { ?>
    <?php
    // Notification mail envoyée pour une nouvelle demande de changement de mot de passe ou d'activation de compte
    if ($bGenerateRecovery === true) {
    ?>
        <form method="get" action="<?php echo PHP_SELF?>" class="filtre" style="width:50%; margin:auto;">
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
            <fieldset>
                <legend><?php echo secureInput($legend);?></legend>
                <p>Un lien vient d'être envoyé à l'adresse mail "<?php echo secureInput($oUtilisateur->getField('UTI_EMAIL'));?>".</p>
                <p>
                    <input type="submit" value="S'identifier" class="submit">
                </p>
            </fieldset>
        </form>
    <?php
    // Affichage du formulaire d'une demande de changement de mot de passe ou d'activation de compte ==> Depuis le lien "Mot de passe perdu ?"
    } else if ($_GET['generateRecovery'] || isset($_POST['recoveryLOGIN'])) {
    ?>
        <form method="post" action="<?php echo PHP_SELF?>" class="filtre" style="width:50%; margin:auto;">
            <fieldset>
                <legend>Mot de passe perdu</legend>
                <?php if (isset($sErrLogin)) {?>
                <div class="alert"><?php echo $sErrLogin?></div>
                <?php } ?>
                <table>
                    <tfoot>
                        <tr>
                            <td colspan="2" class="">
                                <input type="submit" name="generateRecovery" value="Modifier mon mot de passe" class="submit"  tabindex="2">
                            </td>
                        </tr>
                    </tfoot>
                    <tbody>
                        <tr>
                            <th><label for="recoveryLOGIN">Identifiant</label></th>
                            <td>
                                <input size="30" required type="text" name="recoveryLOGIN" id="recoveryLOGIN" value="<?php echo secureInput($_POST['recoveryLOGIN'])?>" tabindex="1">
                                <script>document.getElementById('recoveryLOGIN').focus()</script>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </fieldset>
        </form>
    <?php
    // Clé de contrôle expirée
    } else if ($bcheckRecoveryKey === false) {
    ?>
        <form method="post" action="<?php echo PHP_SELF?>" class="filtre" style="width:50%; margin:auto;">
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
            <fieldset>
                <legend>Délai de validité dépassé</legend>
                <p>Le délai de validité du mail est dépassé.</p>
                <p><?php echo secureInput($help)?></p>
                <p>
                    <input type="submit" value="<?php echo secureInput($submit)?>" name="generateRecovery" class="submit">
                    <input type="hidden" name="recoveryKey" value="<?php echo secureInput($_GET['recoveryKey'])?>">
                </p>
            </fieldset>
        </form>
    <?php
    // Formulaire de saisie des informations d'authentification lors d'une demande de changement de mot de passe ou d'activation de compte
    } else {
    ?>
        <form method="post" action="<?php echo PHP_SELF?>" class="filtre" style="width:50%; margin:auto;">
            <fieldset>
                <?php
                switch ($recoveryType) {
                    case 'activate':
                        $legend = 'Activer mon compte utilisateur';
                        break;
                    case 'forgotten':
                        $legend = 'Modifier votre mot de passe';
                        break;
                }
                ?>
                <legend><?php echo secureInput($legend);?></legend>
                <?php
                if ($recoveryType == 'activate') {
                    echo '<p>' . secureInput(Utilisateur::getValidatePwdHelper()) . '</p>';
                }
                ?>
                <?php if (isset($sErrLogin)) {?>
                <div class="alert"><?php echo $sErrLogin?></div>
                <?php } ?>
                <table>
                    <tfoot>
                        <tr>
                            <td colspan="2">
                                <input type="submit" name="Find" id="submit" value="Enregistrer et accéder au Back Office" class="submit" tabindex="4">
                                <input type="hidden" name="recoveryKey" value="<?php echo secureInput($_REQUEST['recoveryKey'])?>">
                            </td>
                        </tr>
                    </tfoot>
                    <tbody>
                        <tr>
                            <th><label for="UTI_LOGIN">Identifiant</label></th>
                            <td>
                                <input size="30" type="text" required name="UTI_LOGIN" id="UTI_LOGIN" value="<?php echo secureInput($_POST['UTI_LOGIN'])?>"  tabindex="1">
                                <script>document.getElementById('UTI_LOGIN').focus()</script>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="newPASSWORD">Mot de passe</label></th>
                            <td>
                                <input size="30" type="password" required tabindex="2"
                                    name="newPASSWORD" id="newPASSWORD" class="pwdcheck"
                                    pattern="<?php echo secureInput(Utilisateur::getValidateRegPatternPwd());?>"
                                    title="<?php echo secureInput(Utilisateur::getValidatePwdHelper());?>"
                                    onchange="if(this.checkValidity && this.checkValidity()) document.getElementById('newPASSWORD_CONFIRM').pattern = preg_quote(this.value);">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="newPASSWORD_CONFIRM">Confirmer le mot de passe</label></th>
                            <td>
                                <input size="30" type="password" required tabindex="3"
                                    name="newPASSWORD_CONFIRM" id="newPASSWORD_CONFIRM" class="pwdcheck"
                                    pattern="<?php echo secureInput(Utilisateur::getValidateRegPatternPwd());?>"
                                    title="la confirmation du mot de passe doit être identique au mot de passe">
                            </td>
                        </tr>
                    </tbody>
                </table>
            </fieldset>
        </form>
    <?php
    }
    ?>
<?php } elseif (Utilisateur::isConnected()) { ?>
    <?php if ($oAlerte) { ?>
        <div class="bo_alerte">
            <div class="left"><img src="../images/pictoBoAlerte.png" alt="Alerte"></div>
            <div class="right"><?php echo nl2br(secureInput($oAlerte->getField('ALT_MESSAGE')))?></div>
        </div>
    <?php } ?>
    <?php $showSelect = count(Utilisateur::getConnected()->getSites(true)) > 0;?>
        <form  method="post" action="<?php echo PHP_SELF?>" class="filtre" style="width:50%; margin:auto;">
            <fieldset>
                <legend>Merci de choisir votre espace de travail</legend>
                <table>
                    <tfoot>
                        <tr>
                            <td colspan="2">
                                <?php if ($showSelect) { ?>
                                <input type="submit" name="Find" id="submit" value="Entrer dans le site" class="submit" tabindex="2">
                                <?php } ?>
                                <input type="hidden" name="redirect" value="<?php echo secureInput($_POST['redirect'])?>">
                                <a href="<?php echo SERVER_ROOT ?>cms/index.php?logout=1" title="Déconnexion" tabindex="3" class="btnAction">Déconnexion</a>
                            </td>
                        </tr>
                    </tfoot>
                    <tbody>
                        <tr>
                            <th><label><?php echo gettext('Utilisateur')?></label></th>
                            <td><?php echo secureInput(Utilisateur::getConnected()->getField('UTI_PRENOM') . ' ' . Utilisateur::getConnected()->getField('UTI_NOM'))?></td>
                        </tr>
                        <?php if ($showSelect) { ?>
                        <tr>
                            <th><label for="SITE"><?php echo gettext('Site')?></label></th>
                            <td>
                                <select name="SITE" id="SITE" tabindex="1" required>
                                    <option value="">&nbsp;</option>
                                    <?php foreach (Utilisateur::getConnected()->getSites(true) as $_SIT_CODE => $_SIT_LIBELLE) { ?>
                                        <option value="<?php echo $_SIT_CODE?>"><?php echo secureInput($_SIT_LIBELLE)?></option>
                                    <?php } ?>
                                </select>
                                <script>document.getElementById('SITE').focus()</script>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </fieldset>
        </form>
<?php } else { ?>
        <form method="post" action="<?php echo PHP_SELF?>" class="filtre" style="width:50%; margin:auto;">
            <fieldset>
                <legend>Merci de vous authentifier</legend>
                <?php if (isset($sErrLogin)) {?>
                <div class="alert"><?php echo $sErrLogin?></div>
                <?php } ?>
                <table>
                    <tfoot>
                        <tr>
                            <td colspan="2">
                                <input type="submit" name="Find" value="Se connecter" class="submit">
                                <input type="hidden" name="redirect" value="<?php echo secureInput($_REQUEST['redirect'])?>">
                                <a class="btnAction" href="<?php echo PHP_SELF?>?generateRecovery=1">Mot de passe perdu ?</a>
                            </td>
                        </tr>
                    </tfoot>
                    <tbody>
                        <tr>
                            <th><label for="UTI_LOGIN">Identifiant</label></th>
                            <td>
                                <input size="30" type="text" required name="UTI_LOGIN" id="UTI_LOGIN" value="<?php echo secureInput($_POST['UTI_LOGIN'])?>" tabindex="1">
                                <script>document.getElementById('UTI_LOGIN').focus()</script>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="UTI_PASSWORD">Mot de passe</label></th>
                            <td>
                                <input type="password" required tabindex="2" name="UTI_PASSWORD" id="UTI_PASSWORD" size="30">
                            </td>
                        </tr>
                    </tbody>
                </table>
            </fieldset>
        </form>
<?php } ?>
    </div>
    <?php include('../include/inc.bo_bandeau_bas.php')?>
</div>
</body>
</html>
