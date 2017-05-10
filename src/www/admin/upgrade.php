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
set_time_limit(300); // 5 minutes
/**
 * Mise à jour de l'application
 * @author Harmen.Christophe<harmen.christophe@businessdecision.com>
 */

require dirname(__FILE__).'/../include/config.php';
require (dirname(__FILE__).'/../include/config_module_admin.php');
require CLASS_DIR . 'class.DB.php';
require CLASS_DIR . 'class.db_utilisateur.php';
require_once CLASS_DIR . 'class.db_page.php';
require dirname(__FILE__) . '/include/class/class.cms.application.php';
// ERROR AND SLOW HTTP REQUEST
require_once PHYSICAL_PATH . 'admin/include/inc.errorHandler.php';

$app = application :: getInstance();
$app->checkInitialInstall();

if (!Utilisateur::isConnected() || !Utilisateur::getConnected()->isRoot(true)) {
    header('Location:' . SERVER_ROOT . 'cms/index.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit ();
}
$step = 0; // n° de l'étape du procéssus de maj (0=> initial, 'final' => maj terminée)
$install = true; // Le processus de mise à jour se poursuit normallement
$fatalCondition = false; // Si l'erreur rencontrée est fatale. C'est à dire qu'il faut reprendre la maj du début (ex : on n'affiche pas le formulaire pour poursuivre immédiatement)
$msg = '';
$dbh = DB::getInstance();
$errorCode = null; // Code de l'éventuelle erreur



// Mise à jour des signatures
$upSignatures = !empty($_REQUEST['upSignatures']) ? $_REQUEST['upSignatures'] : null;

if (($step == 0) && !$fatalCondition) {
    try {
        $app = application :: getInstance();
        // Si l'appli est à jour
        if ($app->isUptodate()) {
            $signaturesAreUptodate = $app->signatureIsUptodate();

            if (!$upSignatures) {
                $install = false;
                $step = 'final';
                $errorCode = E_NOTICE;
                $msg = "<p>Selon l'information de version des signatures de chacun des modules, <strong>l'application est à jour</strong>. Si ce n'est pas le cas, mettez à jour ces signatures.</p>";
                if (!$signaturesAreUptodate) {
                    $msg .= '<p><a href="?upSignatures=1">Mettre à jour la signature des modules</a>.</p>';
                }
            }
            // Mais que l'on doit tout de même mettre à jour les signatures (si différentes)
            if ($upSignatures && $signaturesAreUptodate) {
                $install = false;
                $step = 'final';
                $errorCode = E_NOTICE;
                $msg = "<p>Les signatures sont à jour.</p>";
            }
        } elseif (!$app->isUptodate() && $upSignatures) {
            $install = false;
            $step = 'final';
            $msg = "<p>Les signatures ne peuvent pas être mise à jour seules car l'application doit l'être dans son ensemble.</p>";
        }
        # Assure les prérequis techniques
        /* NON disponible pour le moment
        if (!appSystemCheck($app->dbh,$_e)) {
            $fatalCondition = true;
            $install = false;
            $msg .= "<p>La configuration système n'est pas complète :</p><ul><li>".implode('</li><li>',$_e)."</li></ul>";
        }
        //*/
    } catch (Exception $e) {
        $install = false;
        $msg .= $e->getMessage();
        $errorCode = $e->getCode();
    }
}

// Tente la mise à jour
if ($install && $step === 0 && !empty($_POST)) {
    // Maj
    if ($install) {
        // Maj
        try {
            if ($upSignatures) {
                $msg .= $app->updateSignatures();
            } else {
                $msg .= $app->install();
                // Purge du cache de l'ensemble des sites
                Page::clearAllCache();
            }
            $step = 'final';

        } catch (Exception $e) {
            $fatalCondition = true;
            $install = false;
            $msg .= $e->getMessage();
            $errorCode = $e->getCode();
        }
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
    $b = str_ireplace('<div class="center">', '<div class="center"><p><a href="'.$_SERVER["PHP_SELF"].'"><strong>"Mise à jour de l\'application"</strong></a></p>', $b);
    echo $b;
    exit;
}
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/HTML; charset=utf-8">
        <meta http-equiv="Content-script-type" content="text/javascript">
        <meta http-equiv="Content-style-type" content="text/css">
        <meta http-equiv="Content-language" content="fr">
        <meta http-equiv="expires" content="0">
        <meta name="Robots" content="noindex, nofollow">
        <meta name="Author" content="EOLAS">
        <title>Mise à jour de l'application</title>
        <link rel="shortcut icon" href="<?php echo SERVER_ROOT?>images/favicon.ico" type="image/x-icon">
        <link rel="stylesheet" href="<?php echo SERVER_ROOT?>include/css/bo.css" type="text/css">
        <link rel="stylesheet" href="<?php echo ADMIN_ROOT?>include/css/admin.css" type="text/css">
        <link rel="stylesheet" href="<?php echo SERVER_ROOT?>include/css/bo_pseudo.css" type="text/css">
        <script type="text/javascript" src="<?php echo SERVER_ROOT?>include/js/jquery/jquery.min.js"></script>
        <script type="text/javascript" src="<?php echo SERVER_ROOT?>include/js/onglet.js"></script>
        <script type="text/javascript" src="<?php echo SERVER_ROOT?>include/js/common.js"></script>
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
    </head>
    <body>
        <div id="document">
            <div id="bandeau_haut">&nbsp;</div>
            <div id="corps" class="creation">
                <h1>Mise à jour de l'application</h1>

                <p class="alignright">
                    <a href="index.php">Informations et outils d'administration</a>
                </p>
                <fieldset class="tab">
                    <legend>Mise à jour de l'application</legend>
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
                        echo '<p class="msg"><a href="'.$_SERVER["PHP_SELF"].'">Recommencer la procédure</a>.</p>';
                    }
                    //if (($install||!$fatalCondition) && $step === 0) {
                    if (!$fatalCondition && $step === 0) {
                    ?>
                        <form method="post" action="<?php echo $_SERVER["PHP_SELF"]?>">
                            <?php
                            $aUpdMdl = array();
                            $aUpdSign = array();
                            $aMdl = $app->availableMdl->getModules();
                            foreach ($aMdl as $id => $m) {
                                if (version_compare($app->availableMdl->moduleInfo($id,'version'),
                                        $app->registeredMdl->moduleInfo($id,'version'),
                                        '>'))
                                {
                                    $aUpdMdl[$id] =  $app->availableMdl->moduleInfo($id,'version');

                                }
                                if ($m != $app->registeredMdl->getModules($id)) {
                                    $aUpdSign[$id] = $app->availableMdl->moduleInfo($id,'version');
                                }
                            }
                            if (empty($upSignatures) && !empty($aUpdMdl)) {
                                echo '<p>Vous êtes sur le point d\'installer ou de mettre à jour le ou les modules suivants :</p>';
                                echo '<ul>';
                                foreach ($aUpdMdl as $id => $v) {
                                    echo '<li><strong>'.htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '</strong> (' . htmlspecialchars($v, ENT_QUOTES, 'UTF-8').')</li>';
                                }
                                echo '</ul>';
                            } elseif (!empty($upSignatures) && !empty($aUpdSign)) {
                                echo '<p>Vous êtes sur le point de mettre à jour la signature du ou les modules suivants :</p>';
                                echo '<ul>';
                                foreach ($aUpdSign as $id => $v) {
                                    echo '<li><strong>'.htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '</strong> (' . htmlspecialchars($v, ENT_QUOTES, 'UTF-8').')</li>';
                                }
                                echo '</ul>';
                            }
                            ?>
                            <p>
                                <input type="submit" id="bsubmit" name="submit" value="Mettre à jour" class="submit">
                                <?php
                                if (isset($_REQUEST['upSignatures'])) {
                                    echo '<input type="hidden" name="upSignatures" value="'.htmlspecialchars($_REQUEST['upSignatures'], ENT_QUOTES, 'UTF-8').'">';
                                }
                                ?>
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
                            <input type="submit" name="submit" value="Retour à l'application" class="submit">
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
