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

header('Content-type: text/html; charset=utf-8');
header('Content-script-type: text/javascript');
header('Content-style-type: text/css');

require 'lib.common.php';

require CLASS_DIR . 'class.DB.php';
require CLASS_DIR . 'class.CMS.php';
require CLASS_DIR . 'class.db_site.php';
require CLASS_DIR . 'class.db_page.php';
require CLASS_DIR . 'class.db_template.php';
require CLASS_DIR . 'class.db_paragraphe.php';
require CLASS_DIR . 'class.db_module.php';
require CLASS_DIR . 'class.db_utilisateur.php';

$dbh = DB::getInstance();

CMS::$mode = 'ON_';

CMS::init();

setlocale(LC_ALL, CMS::getCurrentSite()->getField('SIT_LANGUE') . '.UTF-8');
setlocale(LC_NUMERIC, 'en_US.UTF-8');
header('Content-language: ' . CMS::getCurrentSite()->getField('SIT_SHORT_LANGUE'));

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
