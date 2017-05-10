<?php

/**
 * Ajoute une chaine de type notification, erreur (alertbox), etc... dans la table des messages.
 * Cette liste est restituée en BO pour informer l'utilisateur des différents états sur les traitements des données (erreur de traitement, mise à jour réalisée avec succès, etc...)
 * @param string $str Message à ajouter à la liste
 * @param string $msgType Type de message (NOTIFICATION | ERROR)
 */
function setMsg($str, $msgType = 'NOTIFICATION')
{
    $_SESSION['S_msg'][$msgType][] = $str;
}

function extraireLibelle($str, $locale = '')
{
    if (empty($locale)) {
        $locale = $_SESSION['S_LNG_CODE'];
    }
    $debut = strpos($str, '@' . $locale . ':');
    if ($debut === false) {
        return $str;
    }
    $debut += strlen('@' . $locale . ':');
    $fin = strpos($str, '@', $debut);

    return substr($str, $debut, $fin - $debut);
}

function secureInput($str)
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Echappe les caractères > < " ' & et fait un nl2br
 *
 * @param $str chaine
 *            à encoder
 * @param $addExtra ajoute
 *            les formes abrégées et les languismes
 */
function encode($str, $addExtra = true)
{
    $str = htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    $str = str_replace("\n", '<br>', $str);
    if ($addExtra) {
        $dbh = DB::getInstance();
        if (! isset($GLOBALS['_aLanguisme'])) {
            $GLOBALS['_aLanguisme'] = array();
            $sql = "select * from LANGUISME where LNG_LIBELLE<>'' and SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID());
            foreach ($dbh->query($sql) as $row) {
                $row['LNG_LIBELLE'] = preg_quote($row['LNG_LIBELLE'], '/');
                $GLOBALS['_aLanguisme'][$row['LNG_LIBELLE']] = $row['LNG_LANGUE'];
            }
        }
        foreach ($GLOBALS['_aLanguisme'] as $key => $val) {
            $str = preg_replace('/(?!(<[^>]+))(\\b' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '\\b)(?!([^<]*>))/ums', '\\1<span lang="' . $val . '">\\2</span>\\3', $str);
        }

        if (! isset($GLOBALS['_aAbreviation'])) {
            $GLOBALS['_aAbreviation'] = array();
            $sql = "select * from ABREVIATION where ABR_ABREVIATION<>'' and SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID());
            foreach ($dbh->query($sql) as $row) {
                $row['ABR_ABREVIATION'] = preg_quote($row['ABR_ABREVIATION'], '/');
                $GLOBALS['_aAbreviation'][$row['ABR_ABREVIATION']]['TITLE'] = $row['ABR_LIBELLE'];
                $GLOBALS['_aAbreviation'][$row['ABR_ABREVIATION']]['LANG'] = $row['ABR_LANGUE'];
                $GLOBALS['_aAbreviation'][$row['ABR_ABREVIATION']]['TAGNAME'] = $row['ABR_TAGNAME'];
            }
        }
        foreach ($GLOBALS['_aAbreviation'] as $key => $value) {
            switch ($key) {
                case '%':
                    $str = preg_replace('/(?!(<[^>]+))(%)(?!([^<]*>))/ums', '$1<' . $value['TAGNAME'] . ' lang="' . $value['LANG'] . '" title="' . htmlspecialchars($value['TITLE'], ENT_QUOTES, 'UTF-8') . '">$2</' . $value['TAGNAME'] . '>$3', $str);
                    break;
                case '@':
                    $str = preg_replace('/(?!(<[^>]+))(@)(?!([^<]*>))/ums', '$1<' . $value['TAGNAME'] . ' lang="' . $value['LANG'] . '" title="' . htmlspecialchars($value['TITLE'], ENT_QUOTES, 'UTF-8') . '">$2</' . $value['TAGNAME'] . '>$3', $str);
                    break;
                default:
                    $str = preg_replace('/(?!(<[^>]+))(\b' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '\b)(?!([^<]*>))/ums', '$1<' . $value['TAGNAME'] . ' lang="' . $value['LANG'] . '" title="' . htmlspecialchars($value['TITLE'], ENT_QUOTES, 'UTF-8') . '">$2</' . $value['TAGNAME'] . '>$3', $str);
                    break;
            }
        }
    }

    return $str;
}

/**
 * Dé-Echappe les caractères > < " ' & et fait un nl2br
 *
 * @param $str chaine
 *            à encoder
 * @param $removeExtra rupprime
 *            balises html
 */
function decode($str, $removeExtra = true)
{
    $str = htmlspecialchars_decode($str, ENT_QUOTES);
    $str = str_replace('<br>', "\n", $str);
    if ($removeExtra) {
        $str = strip_tags($str);
    }

    return $str;
}

function unixtime($date, $inclus = false)
{
    $_a = explode('/', $date);
    $seconde = 0;
    if ($inclus) {
        $_a[0] ++;
        $seconde --;
    }

    return (sizeof($_a) == 3) ? mktime(0, 0, $seconde, $_a[1], $_a[0], $_a[2]) : null;
}

function date2str($str)
{
    $_a = explode('-', $str);
    $str = $_a[2] . '/' . $_a[1] . '/' . $_a[0];
    if (count($_a) != 3 || ! valideDate($str, CMS::getCurrentSite()->getField('LNG_CODE'))) {
        return null;
    }
    return $str;
}

function str2date($str)
{
    $_a = explode('/', $str);
    if (count($_a) != 3 || ! valideDate($str, CMS::getCurrentSite()->getField('LNG_CODE'))) {
        return null;
    }
    return ($_a[2] . '-' . $_a[1] . '-' . $_a[0]);
}

function valideMail($mail)
{
    return filter_var($mail, FILTER_VALIDATE_EMAIL);
}

function valideFile($file_name, $aExtensionsValides)
{
    if ($file_name == '') {
        return false;
    }
    $ext = mb_strtolower(mb_strrchr($file_name, "."));

    return in_array($ext, $aExtensionsValides);
}

function valideDate($date, $lang)
{
    $tab = explode('/', $date);
    if ($date == '' || count($tab) != 3) {
        return false;
    }
    switch ($lang) {
        case 'fr_FR':
            $day = intval($tab[0]);
            $month = intval($tab[1]);
            $year = intval($tab[2]);
            break;
        case 'en_US':
            $day = intval($tab[1]);
            $month = intval($tab[0]);
            $year = intval($tab[2]);
            break;
        default:
            $day = intval($tab[1]);
            $month = intval($tab[0]);
            $year = intval($tab[2]);
            break;
    }

    return checkdate($month, $day, $year);
}

/**
 * Compatibilité descendante de l'ancienne fonction de gestion du statut HTTP.
 *
 * @param string $msg
 *            message HTTP à envoyer
 * @param int $code
 *            code de la réponse HTTP
 * @deprecated Utilisez la fonction "http_response_code"
 * @see http_response_code
 */
function http_statut(/** @noinspection PhpUnusedParameterInspection */ $msg, $code)
{
    http_response_code($code);
}

function escapeJS($str)
{
    return str_replace(array('\\', '"', "'", "\n", "\r"), array('\\\\', "''", "\'", '\n', ''), $str);
}

/**
 * Réduit une chaine aux caractères de l'iso646 (~US-ASCII)
 *
 * @param string $str
 *            chaine à réduire
 * @return string chaîne réduite
 */
function reduceToISO646($str)
{
    $escPattern['A'] = '\x{00C0}-\x{00C5}';
    $escPattern['AE'] = '\x{00C6}';
    $escPattern['C'] = '\x{00C7}';
    $escPattern['D'] = '\x{00D0}';
    $escPattern['E'] = '\x{00C8}-\x{00CB}';
    $escPattern['I'] = '\x{00CC}-\x{00CF}';
    $escPattern['N'] = '\x{00D1}';
    $escPattern['O'] = '\x{00D2}-\x{00D6}\x{00D8}';
    $escPattern['OE'] = '\x{0152}';
    $escPattern['S'] = '\x{0160}';
    $escPattern['U'] = '\x{00D9}-\x{00DC}';
    $escPattern['Y'] = '\x{00DD}';
    $escPattern['Z'] = '\x{017D}';

    $escPattern['a'] = '\x{00E0}-\x{00E5}';
    $escPattern['ae'] = '\x{00E6}';
    $escPattern['c'] = '\x{00E7}';
    $escPattern['d'] = '\x{00F0}';
    $escPattern['e'] = '\x{00E8}-\x{00EB}';
    $escPattern['i'] = '\x{00EC}-\x{00EF}';
    $escPattern['n'] = '\x{00F1}';
    $escPattern['o'] = '\x{00F2}-\x{00F6}\x{00F8}';
    $escPattern['oe'] = '\x{0153}';
    $escPattern['s'] = '\x{0161}';
    $escPattern['u'] = '\x{00F9}-\x{00FC}';
    $escPattern['y'] = '\x{00FD}\x{00FF}';
    $escPattern['z'] = '\x{017E}';

    $escPattern['ss'] = '\x{00DF}';

    foreach ($escPattern as $r => $p) {
        $str = preg_replace('/[' . $p . ']/u', $r, $str);
    }
    // Tout ce qui sort de l'ISO646 (ASCII sur 8bits) est purement et simplement recalé
    $str = preg_replace('/[^\x{0000}-\x{007F}]/u', '-', $str);
    $str = preg_replace('/-+/u', '-', $str);

    return $str;
}

/**
 * Sécurise une chaine selon la rfc 1783 (maj par la 3986)
 *
 * @param string $str
 *            chaine à sécuriser
 * @return string chaine sécurisé
 */
function safeFromRfc1738($str)
{
    $gendelims  = array(":", "/", "?", "#", "[", "]", "@");
    $subdelims  = array("!", "$", "&", "'", "(", ")", "*", "+", ",", ";", "=");
    $reserved = array_merge($gendelims, $subdelims);
    $str = str_replace($reserved, '-', $str);

    $str = preg_replace('/[\s]+/', ' ', trim($str));
    $unsafe = array(" ", "<", ">", "\"", "#", "%", "{", "}", "|", "\\", "^", "~", "[", "]", "`", "'");
    $str = str_replace($unsafe, '-', $str);

    $str = preg_replace('/-+/u', '-', $str);

    return $str;
}

/**
 * Encode une composante d'URI selon les RFC 1783 et maj.
 */
function filenameToRfc1738($str)
{
    $str = reduceToISO646($str);
    $str = safeFromRfc1738($str);

    return $str;
}

function resume($resume, $len, $needle = ' ', $end = ' [...]')
{
    $resume = html2Text($resume);
    if (mb_strlen($resume) > $len) {
        if ($l = mb_strrpos(mb_substr($resume, 0, $len), $needle)) {
            return mb_substr($resume, 0, $l) . $end;
        }

        return mb_substr($resume, 0, $len) . $end;
    }

    return $resume;
}

function html2Text($str)
{
    return strip_tags(str_replace(array ('</p>', '</pre>', '</dd>', '</dt>', '</div>', '</blockquote>', '</form>', '<hr>', '</fieldset>','</legend>', '<br>', '</td>', '</h1>', '</h2>', '</h3>', '</h4>', '</h5>', '</h6>', '</li>'), ' ', $str));
}

/**
 * Retourn l'url formaté
 *
 * @param String $url
 *            Url à formater
 * @return String L'url formaté
 */
function formatUrl($url)
{
    $str = mb_convert_case(trim($url), MB_CASE_LOWER);
    $str = filenameToRfc1738($str);

    return $str;
}

/**
 * Test si $url est valide ou non => ne contient pas de répertoire en début de chaine
 *
 * @param String $chaine
 *            Chaine contenant l'URL à tester
 * @return Boolean Retourn true si l'url est respecté
 */
function testURLFormat($url)
{
    // certains mots sont interdits car utilisés par l'appli
    $tabInterdits = unserialize(TAB_REP_VIRTUEL_INTERDITS);

    return (preg_match('/^(' . implode('|', $tabInterdits) . ')/i', $url) == 0);
}

function Sec2Time($time)
{
    if (is_numeric($time)) {
        $value = array("years" => '0', "days" => '0', "hours" => '0', "minutes" => '0', "seconds" => '0');

        if ($time >= 31556926) {
            if (strlen(strval(floor($time / 31556926))) < 2) {
                $value["years"] = '0' . strval(floor($time / 31556926));
            } else {
                $value["years"] = strval(floor($time / 31556926));
            }
            $time = ($time % 31556926);
        }

        if ($time >= 86400) {
            if (strlen(strval(floor($time / 86400))) < 2) {
                $value["days"] = '0' . strval(floor($time / 86400));
            } else {
                $value["days"] = strval(floor($time / 86400));
            }
            $time = ($time % 86400);
        }

        if ($time >= 3600) {
            if (strlen(strval(floor($time / 3600))) < 2) {
                $value["hours"] = '0' . strval(floor($time / 3600));
            } else {
                $value["hours"] = strval(floor($time / 3600));
            }
            $time = ($time % 3600);
        }

        if ($time >= 60) {
            if (strlen(strval(floor($time / 60))) < 2) {
                $value["minutes"] = '0' . strval(floor($time / 60));
            } else {
                $value["minutes"] = strval(floor($time / 60));
            }
            $time = ($time % 60);
        }

        if (strlen(strval(floor($time))) < 2) {
            $value["seconds"] = '0' . strval(floor($time));
        } else {
            $value["seconds"] = strval(floor($time));
        }

        return (array) $value;
    } else {
        return (bool) FALSE;
    }
}

function formatTime($time)
{
    $strTimeFormat = "";
    $aTime = Sec2Time($time);

    if ($aTime['years'] != '0' || $aTime['years'] != '00') {
        $strTimeFormat .= $aTime['years'] + ":";
    }

    if ($aTime['days'] != '0' || $aTime['days'] != '00') {
        $strTimeFormat .= $aTime['days'] + ":";
    }

    $strTimeFormat .= $aTime['hours'] . ":" . $aTime['minutes'] . ":" . $aTime['seconds'];

    return $strTimeFormat;
}

function formatNum($checkValue, $calculatedValue)
{
    if ($checkValue != 0) {
        return number_format($calculatedValue, 2, '.', '');
    } else {
        return number_format(0, 2, '.', '');
    }
}

/**
 *
 * Permet de convertir une chaine de caractère en code hexadécimal
 *
 * @return string code hexadecimal (Ex : #00BB77)
 * @param string $str
 *            chaine de caractère à transformer en code couleur hexadécimal
 */
function genereColor($str)
{
    $code = dechex(crc32($str));
    $code = substr($code, 0, 6);
    return '#' . $code;
}

if (! function_exists('http_response_code')) {

    /*
     * Fallback de la fonction de même nom disponible sous PHP >= 5.4
     * @param $code int Getter ou setter du code HTTP de la réponse [optionnal]
     * @return int Code de la réponse en cours, par défault la valeur de retour est "200"
     * @link http://php.net/manual/fr/function.http-response-code.php#116539
     */
    function http_response_code($code = NULL)
    {
        $prev_code = (isset($GLOBALS['http_response_code']) ? $GLOBALS['http_response_code'] : 200);

        if ($code === NULL) {
            return $prev_code;
        }

        switch ($code) {
            case 100: $text = 'Continue'; break;
            case 101: $text = 'Switching Protocols'; break;
            case 200: $text = 'OK'; break;
            case 201: $text = 'Created'; break;
            case 202: $text = 'Accepted'; break;
            case 203: $text = 'Non-Authoritative Information'; break;
            case 204: $text = 'No Content'; break;
            case 205: $text = 'Reset Content'; break;
            case 206: $text = 'Partial Content'; break;
            case 300: $text = 'Multiple Choices'; break;
            case 301: $text = 'Moved Permanently'; break;
            case 302: $text = 'Moved Temporarily'; break;
            case 303: $text = 'See Other'; break;
            case 304: $text = 'Not Modified'; break;
            case 305: $text = 'Use Proxy'; break;
            case 400: $text = 'Bad Request'; break;
            case 401: $text = 'Unauthorized'; break;
            case 402: $text = 'Payment Required'; break;
            case 403: $text = 'Forbidden'; break;
            case 404: $text = 'Not Found'; break;
            case 405: $text = 'Method Not Allowed'; break;
            case 406: $text = 'Not Acceptable'; break;
            case 407: $text = 'Proxy Authentication Required'; break;
            case 408: $text = 'Request Time-out'; break;
            case 409: $text = 'Conflict'; break;
            case 410: $text = 'Gone'; break;
            case 411: $text = 'Length Required'; break;
            case 412: $text = 'Precondition Failed'; break;
            case 413: $text = 'Request Entity Too Large'; break;
            case 414: $text = 'Request-URI Too Large'; break;
            case 415: $text = 'Unsupported Media Type'; break;
            case 500: $text = 'Internal Server Error'; break;
            case 501: $text = 'Not Implemented'; break;
            case 502: $text = 'Bad Gateway'; break;
            case 503: $text = 'Service Unavailable'; break;
            case 504: $text = 'Gateway Time-out'; break;
            case 505: $text = 'HTTP Version not supported'; break;
            default:
                trigger_error('Unknown http status code ' . $code, E_USER_ERROR); // exit('Unknown http status code "' . htmlentities($code) . '"');
                return $prev_code;
        }

        $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
        header($protocol . ' ' . $code . ' ' . $text);
        $GLOBALS['http_response_code'] = $code;

        // original function always returns the previous or current code
        return $prev_code;
    }
}
