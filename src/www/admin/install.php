<?php
ob_start();
$currentCookieParams = session_get_cookie_params();
session_set_cookie_params(
    $currentCookieParams["lifetime"],
    $currentCookieParams["path"],
    $currentCookieParams["domain"],
    $currentCookieParams["secure"],
    true
);
session_start();
/**
 * Installation de l'application
 * @author Harmen.Christophe<harmen.christophe@businessdecision.com>
 */
require dirname(__FILE__) . '/../include/config.php';
require (dirname(__FILE__).'/../include/config_module_admin.php');
require CLASS_DIR . 'class.DB.php';
require dirname(__FILE__) . '/include/class/class.cms.application.php';
// ERROR AND SLOW HTTP REQUEST
require_once PHYSICAL_PATH . 'admin/include/inc.errorHandler.php';


$app = application :: getInstance();
$app->availableMdl->loadModules();
$app->registeredMdl->loadModules();
$aRegisteredMdl = $app->registeredMdl->getModules();
$aAvailableMdl = $app->availableMdl->getModules();

// On valide qu'il s'agit bien d'une première installation
if (!empty($aRegisteredMdl)) {
    // Dans le cas contraire, on redirige vers l'écran de mise à jour
    header('Location:' . ADMIN_ROOT . 'upgrade.php');
    exit();
}

$step = 0; // n° de l'étape du procéssus de maj (0=> initial, 'final' => maj terminée)
$install = true; // Le processus de mise à jour se poursuit normallement
$fatalCondition = false; // L'erreur rencontrée est fatale. C'est à dire qu'il faut reprendre l'install du début (ex : on n'affiche pas le formulaire pour poursuivre immédiatement)
$msg = '';
$errorCode = null; // Code de l'éventuelle erreur

if ($install && $step === 0 && !empty($_POST)) {
    $SIT_CODE           = !empty($_POST['SIT_CODE']) ? $_POST['SIT_CODE'] : null;
    $SIT_LIBELLE        = !empty($_POST['SIT_LIBELLE']) ? $_POST['SIT_LIBELLE'] : null;
    $UTI_CIVILITE       = !empty($_POST['UTI_CIVILITE']) ? $_POST['UTI_CIVILITE'] : null;
    $UTI_NOM            = !empty($_POST['UTI_NOM']) ? $_POST['UTI_NOM'] : null;
    $UTI_PRENOM         = !empty($_POST['UTI_PRENOM']) ? $_POST['UTI_PRENOM'] : null;
    $UTI_EMAIL          = !empty($_POST['UTI_EMAIL']) ? $_POST['UTI_EMAIL'] : null;
    $UTI_EMAIL_CONFIRM  = !empty($_POST['UTI_EMAIL_CONFIRM']) ? $_POST['UTI_EMAIL_CONFIRM'] : null;
    $UTI_LOGIN          = !empty($_POST['UTI_LOGIN']) ? $_POST['UTI_LOGIN'] : null;
    // Validation du SIT_CODE
    if (is_null($SIT_CODE)) {
        $fatalCondition = false;
        $install = false;
        $msg .= '<p>Le code du site doit être renseigné.</p>';
        $errorCode = E_USER_ERROR;
    } else if (strlen($SIT_CODE) > 27) {
        $fatalCondition = false;
        $install = false;
        $msg .= '<p>Le code du site doit être inférieur à 27 caractères.</p>';
        $errorCode = E_USER_ERROR;
    }
    if (is_null($SIT_LIBELLE)) {
            $fatalCondition = false;
            $install = false;
            $msg .= '<p>Le libellé du site doit être renseigné.</p>';
            $errorCode = E_USER_ERROR;
    }
    // Validation des données sur les info compte
    if (is_null($UTI_CIVILITE)
        || is_null($UTI_NOM)
        || is_null($UTI_PRENOM)
        || is_null($UTI_EMAIL)
        || is_null($UTI_EMAIL_CONFIRM)
        || is_null($UTI_LOGIN)
    )
    {
        $fatalCondition = false;
        $install = false;
        $msg .= '<p>Les informations nécessaires à la création du compte utilisateur ne sont pas toutes renseignées.</p>';
        $errorCode = E_USER_ERROR;
    // Validation du mail
    } elseif (!filter_var($UTI_EMAIL, FILTER_VALIDATE_EMAIL)) {
        $fatalCondition = false;
        $install = false;
        $msg .= '<p>Veuillez saisir un email valide.</p>';
        $errorCode = E_USER_ERROR;
    // Validation mail email de confirmation
    } elseif ($UTI_EMAIL != $UTI_EMAIL_CONFIRM) {
        $fatalCondition = false;
        $install = false;
        $msg .= '<p>Les valeurs des champs "<code>Email</code>" et "<code>Confirmation Email</code>" ne sont pas identiques.</p>';
        $errorCode = E_USER_ERROR;
    }
}
if ($install && $step === 0 && !empty($_POST)) {
    // Install
    try {
        $msg .= $app->install();
        $step = 'final';
        $aRegisteredMdl = $app->registeredMdl->getModules();
        // Envoie du mail d'activation de compte
        require_once CLASS_DIR . 'class.CMS.php';
        require_once CLASS_DIR . 'class.db_site.php';
        require_once CLASS_DIR . 'class.db_utilisateur.php';
        $oUtilisateur = new Utilisateur(1);
        $oUtilisateur->generateRecoveryPwdNotification(true);
        $msg .= '<p>Un email vous a été envoyé à l\'adresse "'.htmlspecialchars($UTI_EMAIL, ENT_QUOTES, 'UTF-8').'". Il contient un lien permettant d\'activer votre compte.';
    } catch (Exception $e) {
        $fatalCondition = true;
        $install = false;
        $msg .= $e->getMessage();
        $errorCode = $e->getCode();
    }
}

/******************************************************************************
 * Phpinfo
 */
$phpinfo = !empty($_GET['phpinfo']) ? $_GET['phpinfo'] : null;
if ($phpinfo) {
    header('Content-type: text/html; charset=utf-8');
    phpinfo();
    $b = ob_get_contents();
    ob_clean();
    $b = str_ireplace('<div class="center">', '<div class="center"><p><a href="'.htmlentities($_SERVER['PHP_SELF']).'"><strong>"Installation de l\'application"</strong></a></p>', $b);
    echo $b;
    exit;
}
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/HTML; charset=utf-8">
        <meta http-equiv="Content-script-type" content="text/javascript">
        <meta http-equiv="Content-style-type" content="text/css">
        <meta http-equiv="Content-language" content="fr">
        <meta http-equiv="expires" content="0">
        <meta name="Robots" content="noindex, nofollow">
        <meta name="Author" content="EOLAS">
        <title>Installation de l'application</title>
        <link rel="shortcut icon" href="<?php echo SERVER_ROOT?>images/favicon.ico" type="image/x-icon">
        <link rel="stylesheet" href="<?php echo SERVER_ROOT?>include/css/bo.css">
        <link rel="stylesheet" href="<?php echo SERVER_ROOT?>include/css/bo_pseudo.css">
        <link rel="stylesheet" href="<?php echo SERVER_ROOT?>include/css/print.css" media="print">
        <link rel="stylesheet" href="<?php echo SERVER_ROOT?>include/js/jquery/ui/jquery-ui.min.css">
        <link rel="stylesheet" href="<?php echo SERVER_ROOT ?>include/js/jquery/colorbox/colorbox.css">
        <script>var SERVER_ROOT = '<?php echo SERVER_ROOT?>';</script>
        <script>var cms_lang = 'fr';</script>
        <script src="<?php echo SERVER_ROOT?>include/js/formCtrl.js"></script>
        <script src="<?php echo SERVER_ROOT?>include/js/formCtrl-fr.js"></script>
        <script src="<?php echo SERVER_ROOT?>include/js/jquery/jquery.min.js"></script>
        <script src="<?php echo SERVER_ROOT?>include/js/jquery/ui/jquery-ui.min.js"></script>
        <script src="<?php echo SERVER_ROOT?>include/js/jquery/ui/i18n/datepicker-fr.js"></script>
        <script src="<?php echo SERVER_ROOT?>include/js/jquery/colorbox/jquery.colorbox-min.js"></script>
        <script src="<?php echo SERVER_ROOT?>include/js/common.js"></script>
        <script src="<?php echo SERVER_ROOT?>include/js/coreBo.js"></script>
        <script>$(cmsBO.init);</script>
        <script type="text/javascript" src="<?php echo SERVER_ROOT?>include/js/onglet.js"></script>
        <link rel="stylesheet" href="<?php echo ADMIN_ROOT?>include/css/admin.css" type="text/css">
        <script type="text/javascript">
                $(document).ready(function () {
                    //external
                    $(document).on('click', 'a.external', function (e) {
                        e.preventDefault();
                        window.open(this.href);

                        return false;
                    });
                }
                );
        </script>
<script>
function preg_quote(str, delimiter) {
    return String(str)
    .replace(new RegExp('[.\\\\+*?\\[\\^\\]$(){}=!<>|:\\' + (delimiter || '') + '-]', 'g'), '\\$&');
}
</script>
    </head>
    <body>
        <div id="document">
            <div id="bandeau_haut">&nbsp;</div>
            <div id="corps" class="creation">
                <h1>Installation de l'application</h1>

                <fieldset class="tab">
                    <legend>Installation de l'application</legend>
                        <?php
                        if (!$install && !empty($msg)) {
                            echo '<div class="msg'.($errorCode == E_NOTICE?' valide':'').'">';
                            if ($errorCode != E_NOTICE) {
                                echo '<p><strong>Erreur</strong></p>';
                            }
                            echo $msg;
                            echo '</div>';
                        }
                        if ($fatalCondition && $step === 0) {
                            echo '<p class="msg"><a href="'.htmlentities($_SERVER['PHP_SELF']).'">Recommencer la procédure</a>.</p>';
                        }
                        //if (($install||!$fatalCondition) && $step === 0) {
                        if (!$fatalCondition && $step === 0) {
                        ?>
                            <form method="post" action="<?php echo htmlentities($_SERVER['PHP_SELF'])?>">
                                <p>
                                    Vous êtes sur le point d'installer le ou les modules suivants :
                                </p>
                                <ul>
                                <?php
                                foreach ($aAvailableMdl as $id => $m) {
                                    echo '<li><strong>'.htmlspecialchars($id) . '</strong> (' . htmlspecialchars($app->availableMdl->moduleInfo($id,'version')).')</li>';
                                }
                                ?>
                                </ul>
                                <p>
                                    Pour permettre cette installation, veuillez renseigner les informations du compte qui va vous être créé.
                                </p>
                                <fieldset>
                                    <legend>Informations sur le site</legend>
                                    <p>
                                        <label for="SIT_CODE">
                                            Code :
                                            <span class="helper">Identifiant unique décrivant le site (sous forme de chaine de caractères)</span>
                                        </label>
                                        SIT_<input type="text" name="SIT_CODE" id="SIT_CODE" value="<?php echo htmlspecialchars($SIT_CODE, ENT_QUOTES, 'UTF-8')?>" size="30" maxlength="27" required>
                                        <strong>Attention</strong>, ce dernier ne pourra plus être modifié par la suite.
                                    </p>
                                    <p>
                                        <label for="SIT_LIBELLE">Libellé du site :</label>
                                        <input type="text" name="SIT_LIBELLE" id="SIT_LIBELLE" value="<?php echo htmlspecialchars($SIT_LIBELLE, ENT_QUOTES, 'UTF-8')?>" size="50" required>
                                    </p>
                                </fieldset>
                                <fieldset>
                                    <legend>Informations du compte</legend>
                                    <p>
                                        <label for="UTI_CIVILITE">Civilité :</label>
                                        <select name="UTI_CIVILITE" id="UTI_CIVILITE" required>
                                            <option value="">&nbsp;</option>
                                            <option value="Mme"<?php if ($UTI_CIVILITE=='Mme') echo ' selected'?>>Madame</option>
                                            <option value="M"<?php if ($UTI_CIVILITE=='M') echo ' selected'?>>Monsieur</option>
                                        </select>
                                    </p>
                                    <p>
                                        <label for="UTI_NOM">Nom :</label>
                                        <input name="UTI_NOM" type="text" id="UTI_NOM" value="<?php echo htmlspecialchars($UTI_NOM, ENT_QUOTES, 'UTF-8');?>" size="30" maxlength="50" required>
                                    </p>
                                    <p>
                                        <label for="UTI_PRENOM">Prénom :</label>
                                        <input name="UTI_PRENOM" type="text" id="UTI_PRENOM" value="<?php echo htmlspecialchars($UTI_PRENOM, ENT_QUOTES, 'UTF-8');?>" size="30" maxlength="50" required>
                                    </p>
                                    <p>
                                        <label for="UTI_EMAIL">Email :</label>
                                        <input type="email" value="<?php echo htmlspecialchars($UTI_EMAIL, ENT_QUOTES, 'UTF-8');?>"
                                            name="UTI_EMAIL" id="UTI_EMAIL" size="40" maxlength="100"
                                            onchange="if(this.checkValidity && this.checkValidity()) document.getElementById('UTI_EMAIL_CONFIRM').pattern = preg_quote(this.value);"
                                            required>
                                        Adresse mail sous laquelle vous souhaitez recevoir les informations d'activation de compte.
                                    </p>
                                    <p>
                                        <label for="UTI_EMAIL_CONFIRM">Confirmation Email :</label>
                                        <input  type="email" value="<?php echo htmlspecialchars($UTI_EMAIL_CONFIRM, ENT_QUOTES, 'UTF-8');?>"
                                            name="UTI_EMAIL_CONFIRM" id="UTI_EMAIL_CONFIRM" size="40" maxlength="100"
                                            title="La confirmation Email doit être identique au premier email saisi"
                                            required>
                                    </p>
                                    <p>
                                        <label for="UTI_LOGIN">Identifiant :</label>
                                        <input name="UTI_LOGIN" type="text" id="UTI_LOGIN" value="<?php echo htmlspecialchars($UTI_LOGIN, ENT_QUOTES, 'UTF-8');?>" size="20" maxlength="50" required>
                                    </p>
                                </fieldset>
                                <p>
                                    <input type="submit" id="bsubmit" name="submit" value="Confirmer cette installation" class="ajouter">
                                </p>
                            </form>
                        <?php
                        } elseif ($install && $step == 'final') {
                        ?>
                        <form action="<?php echo SERVER_ROOT;?>cms/" method="get">
                            <?php
                            if (!empty($msg)) {
                                echo '<div class="msg">'.$msg.'</div>';
                            }
                            ?>
                            <p>
                                <input type="submit" name="submit" value="Se connecter" class="submit">
                            </p>
                        </form>
                        <?php
                        }
                        ?>
                </fieldset>
                <fieldset class="tab">
                    <legend>Système</legend>
                    <h2>Information sur la configuration PHP</h2>
                    <ul>
                        <li>
                            <p><a href="?phpinfo=1">PhpInfo</a></p>
                        </li>
                    </ul>
                </fieldset>
            </div><!-- DIN : #corps -->
        </div><!-- DIN : #document -->
    </body>
</html>
