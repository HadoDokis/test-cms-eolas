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

require 'config.php';

//LANG
if ($_SESSION['S_LNG_CODE'] == '') {
    $_SESSION['S_LNG_CODE'] = ($_COOKIE['C_LNG_CODE'] != '') ? $_COOKIE['C_LNG_CODE'] : 'fr_FR';
}
putenv('LANG=' . $_SESSION['S_LNG_CODE']);
putenv('LANGUAGE=' . $_SESSION['S_LNG_CODE']);
setlocale(LC_ALL, $_SESSION['S_LNG_CODE'] . '.UTF-8');
setlocale(LC_NUMERIC, 'en_US.UTF-8');
bindtextdomain('messages', PHYSICAL_PATH . 'include/lang');
bind_textdomain_codeset('messages', 'UTF-8');
textdomain('messages');

define('PHP_SELF', htmlentities($_SERVER['PHP_SELF']));

header('Content-type: text/html; charset=utf-8');
header('Content-script-type: text/javascript');
header('Content-style-type: text/css');

require 'lib.common.php';

require CLASS_DIR . 'class.DB.php';
require CLASS_DIR . 'class.CMS.php';
require CLASS_DIR . 'class.db_site.php';
require CLASS_DIR . 'class.db_module.php';
require CLASS_DIR . 'class.db_utilisateur.php';

$dbh = DB::getInstance();

CMS::init();

if (get_magic_quotes_gpc()) {
    function stripslashes_deep($value)
    {
        if (is_array($value)) {
            $value = array_map('stripslashes_deep', $value);
        } elseif (!empty ($value) && is_string($value)) {
            $value = stripslashes($value);
        }

        return $value;
    }

    $_POST = stripslashes_deep($_POST);
    $_GET = stripslashes_deep($_GET);
    $_REQUEST = stripslashes_deep($_REQUEST);
    $_COOKIE = stripslashes_deep($_COOKIE);
}
