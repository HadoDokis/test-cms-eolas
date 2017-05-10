<?php
require_once (dirname(__FILE__).'/../../include/config_module_admin.php');

class errorHandler
{
    private static $_errorsType = array(1,		'E_ERROR',
                                        2,		'E_WARNING',
                                        4,		'E_PARSE',
                                        8,		'E_NOTICE',
                                        16,		'E_CORE_ERROR',
                                        32,		'E_CORE_WARNING',
                                        64,		'E_COMPILE_ERROR',
                                        128,	'E_COMPILE_WARNING',
                                        256,	'E_USER_ERROR',
                                        512,	'E_USER_WARNING',
                                        1024,	'E_USER_NOTICE',
                                        2048,	'E_STRICT',
                                        4096,	'E_RECOVERABLE_ERROR',
                                        8192,	'E_DEPRECATED',
                                        16384,	'E_USER_DEPRECATED',
                                        32767,	'E_ALL');

    private static $_mailSlowSentTime = '_mailSlowSentTime';
    private static $_mailErrorSentTime = '_mailErrorSentTime';

    public static function shutdownTime($startTime)
    {
        /* On ne reporte que les erreurs sur l'index
        if($_SERVER['PHP_SELF'] != SERVER_ROOT . 'index.php' &&
            !preg_match('#/(externe|include/ajax)/#', $_SERVER['PHP_SELF'])){
            return false;
        }
        //*/
        //Gestion des erreurs
        if (defined('ERROR_LOG') && ERROR_LOG) {
            $aLastError=error_get_last();
            $aErrorsT_discard = array(E_NOTICE, E_STRICT, E_WARNING);
            if ($aLastError && !in_array($aLastError['type'], $aErrorsT_discard)  && self::checkTTL(ADMIN_LOG_DIR.self::$_mailErrorSentTime, MAIL_REPORT_TTL)) {
                $str = '';
                $str .= '<h1>Génération d\'une erreur</h1>';
                $str .= '<ul>';
                $str .= '<li>Machine : ' . $_SERVER['SERVER_NAME'] . ' (' . $_SERVER['SERVER_ADDR'] . ')</li>';
                $str .= '<li>Request URI : http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . '</li>';
                $str .= '<li>Error type : ' . self::$_errorsType[$aLastError['type']] . '</li>';
                $str .= '<li>Message : ' . $aLastError['message'] . '<li>';
                $str .= '<li>Dans le fichier ' . $aLastError['file'] . ' à la ligne ' . $aLastError['line'].'<li>';

                if ($_SERVER['HTTP_REFERER']!='') {
                    $str .= '<li>Referer : ' . $_SERVER['HTTP_REFERER'] . '<li>';
                }
                $str .= '</ul>';
                if (!empty($_GET)) {
                    $str .= '<h2>Variables d\'URL (get)</h2>';
                    $str .= '<dl>';
                    foreach ($_GET as $k=> $v) {
                        if (is_array($v)) $v = print_r ($v, true);
                        $str .= '<dt>'.htmlspecialchars($k, ENT_QUOTES, 'UTF-8').'</dt><dd><code>'.htmlspecialchars($v, ENT_QUOTES, 'UTF-8').'</code></dd>';
                    }
                    $str .= '</dl>';
                }
                if (!empty($_POST)) {
                    $str .= '<h2>Variables postées</h2>';
                    $str .= '<dl>';
                    foreach ($_POST as $k=> $v) {
                        if (is_array($v)) $v = print_r ($v, true);
                        $str .= '<dt>'.htmlspecialchars($k, ENT_QUOTES, 'UTF-8').'</dt><dd><code>'.htmlspecialchars($v, ENT_QUOTES, 'UTF-8').'</code></dd>';
                    }
                    $str .= '</dl>';
                }
                if (!empty($_SESSION)) {
                    $str .= '<h2>Variables de session</h2>';
                    $str .= '<dl>';
                    foreach ($_SESSION as $k=> $v) {
                        if (is_array($v)) $v = print_r ($v, true);
                        $str .= '<dt>'.htmlspecialchars($k, ENT_QUOTES, 'UTF-8').'</dt><dd><code>'.htmlspecialchars($v, ENT_QUOTES, 'UTF-8').'</code></dd>';
                    }
                    $str .= '</dl>';
                }
                if (!empty($_COOKIE)) {
                    $str .= '<h2>Cookies</h2>';
                    $str .= '<dl>';
                    foreach ($_COOKIE as $k=> $v) {
                        if (is_array($v)) $v = print_r ($v, true);
                        $str .= '<dt>'.htmlspecialchars($k, ENT_QUOTES, 'UTF-8').'</dt><dd><code>'.htmlspecialchars($v, ENT_QUOTES, 'UTF-8').'</code></dd>';
                    }
                    $str .= '</dl>';
                }
                $SIT_LIBELLE = '[CMS.Eolas] ';
                if (class_exists('CMS') && class_exists('Site') && CMS::getCurrentSite()) {
                    $SIT_LIBELLE = '[' . CMS::getCurrentSite()->getField('SIT_LIBELLE') . '] ';
                }
                self::mailReport($str, $SIT_LIBELLE .  $_SERVER['SERVER_NAME'] . ' - Erreur d\'execution!', self::$_mailErrorSentTime);
            }
        }

        //Gestion des délais d'executions
        if (defined('SLOWREQUEST_LOG') && SLOWREQUEST_LOG) {
            $endTime = microtime(true);
            $delta = $endTime-$startTime;
            $dbh = DB::getInstance();
            if ($delta > SLOWREQUEST_DURATION && self::checkTTL(SLOWREQUEST_LOG_FILE, SLOWREQUEST_LOG_TTL)) {
                $slowRepport = true;
                // Vérification si l'URL fait partie des URL connues comme lente (à ne pas loger)
                if (defined('SLOWREQUEST_IGNORED_REGEXP') && is_array($aIgnoredPattern = unserialize(SLOWREQUEST_IGNORED_REGEXP))) {
                    $count = 0;
                    @preg_replace($aIgnoredPattern, '', $_SERVER['REQUEST_URI'], 1,$count);
                    if ($count > 0) {$slowRepport = false;}
                }
                if ($slowRepport) {
                    //Génération d'un log sur les temps moyens...
                    @file_put_contents(SLOWREQUEST_LOG_FILE,  '[' . date('r') . '] ' . "\t" . $delta . "\t" . $_SERVER['REQUEST_URI']  . "\n", FILE_APPEND);
                    if (self::checkTTL(ADMIN_LOG_DIR.self::$_mailSlowSentTime, MAIL_REPORT_TTL)) {
                        $str = '';
                        $str .= '<h1>Génération d\'une requête HTTP lente</h1>';
                        $str .= '<p>La page ' . $_SERVER['REQUEST_URI'] . ' à eu un temps d\'éxécution de ' . $delta . ' secondes!</p>';
                        $str .= '<ul>';
                        $str .= '<li>Machine : ' . $_SERVER['SERVER_NAME'] . ' (' . $_SERVER['SERVER_ADDR'] . ')</li>';
                        $str .= '<li>Le ' . date('d/m/Y H:i:s') . '</li>';
                        $str .= '<li>Request URI : http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . '</li>';
                        $str .= '</ul>';

                        if (!empty($_SESSION['SLOWREQUEST_LOG']['DB_PROFILING'])) {
                                $str .= '<h2>Requêtes SQL</h2>';
                                $str .= '<p>Affichage des requêtes jouées au sein de la base de données par des appels aux méthodes "<code>PDO::query()</code>", "<code>PDO::exec()</code>" et "<code>PDOStatement::execute()</code>".</p>';
                                $str .= '<table border="1" cellpadding="10" cellspacing="0">';
                                $str .= '<tr>
                                    <th>N°</th>
                                    <th>Duration</th>
                                    <th>Query</th>
                                    <th>Details</th>
                                    '.((CMS_DEBUG == $_SESSION['S_UTI_LOGIN'])?'<th>Durée CMS</th>':'').'
                                    '.((CMS_DEBUG == $_SESSION['S_UTI_LOGIN'])?'<th>Fichiers</th>':'').'
                                </tr>';
                                $i = 0;
                                foreach ($_SESSION['SLOWREQUEST_LOG']['DB_PROFILING'] as $k => $row) {
                                    $strDetail = '';
                                    if (!empty($row['SQL']['DETAILS'])) {
                                        $strDetail .= '<table border="1" cellpadding="5" cellspacing="0">
                                            <tr>
                                                <th>Etape</th>
                                                <th>Durée</th>
                                            </tr>';
                                        foreach ($row['SQL']['DETAILS'] as $rowDetail) {
                                            $strDetail .= '<tr>
                                                <td>'.htmlspecialchars($rowDetail['STATUS'], ENT_QUOTES, 'UTF-8').'</td>
                                                <td>'.htmlspecialchars($rowDetail['DURATION'], ENT_QUOTES, 'UTF-8').'</td>
                                            </tr>';
                                        }
                                        $strDetail .= '</table>';
                                    }
                                    $str .= '<tr>
                                        <td valign="top">'.htmlspecialchars(++$i, ENT_QUOTES, 'UTF-8').'</td>
                                        <td valign="top">'.htmlspecialchars($row['SQL']['DURATION']?$row['SQL']['DURATION']:'-', ENT_QUOTES, 'UTF-8').'</td>
                                        <td valign="top">'.htmlspecialchars($row['SQL']['QUERY']?$row['SQL']['QUERY']:$k, ENT_QUOTES, 'UTF-8').'</td>
                                        <td valign="top">'.($strDetail?$strDetail:'-').'</td>
                                        '.((CMS_DEBUG == $_SESSION['S_UTI_LOGIN'])?'<td valign="top">'.htmlspecialchars($row['DURATION_CMS'], ENT_QUOTES, 'UTF-8').'</td>':'').'
                                        '.((CMS_DEBUG == $_SESSION['S_UTI_LOGIN'])?'<td valign="top">'.htmlspecialchars($row['FILE'], ENT_QUOTES, 'UTF-8').'</td>':'').'
                                    </tr>';
                                }
                                $str .= '</table>';
                                $str .= '<hr>';
                        } elseif (defined('DB_PROFILING_SUPPORT') && DB_PROFILING_SUPPORT) {
                            $rowList = $dbh->query('show profiles')->fetchAll(PDO::FETCH_ASSOC);
                            if (!empty($rowList)) {
                                $profiling_history_size = $dbh->query("SELECT @@profiling_history_size")->fetchColumn();
                                $str .= '<h2>Requêtes SQL</h2>';
                                $str .= '<p>Historique des ' . $profiling_history_size . ' dernière(s) requête(s) jouées au sein de la base de données.</p>';
                                $str .= '<table border="1" cellpadding="10" cellspacing="0">';
                                $str .= '<tr>
                                    <th>N°</th>
                                    <th>Duration</th>
                                    <th>Query</th>
                                    <th>Details</th>
                                </tr>';
                                $i = 0;
                                foreach ($rowList as $row) {
                                    $rowsDetail = $dbh->query('show profile for query '.$row['QUERY_ID'])->fetchAll(PDO::FETCH_ASSOC);
                                    $strDetail = '';
                                    if (!empty($rowsDetail)) {
                                        $strDetail .= '<table border="1" cellpadding="5" cellspacing="0">
                                            <tr>
                                                <th>Etape</th>
                                                <th>Durée</th>
                                            </tr>';
                                        foreach ($rowsDetail as $rowDetail) {
                                            $strDetail .= '<tr>
                                                <td>'.htmlspecialchars($rowDetail['STATUS'], ENT_QUOTES, 'UTF-8').'</td>
                                                <td>'.htmlspecialchars($rowDetail['DURATION'], ENT_QUOTES, 'UTF-8').'</td>
                                            </tr>';
                                        }
                                        $strDetail .= '</table>';
                                    }
                                    $str .= '<tr>
                                        <td valign="top">'.htmlspecialchars(++$i, ENT_QUOTES, 'UTF-8').'</td>
                                        <td valign="top">'.htmlspecialchars($row['DURATION'], ENT_QUOTES, 'UTF-8').'</td>
                                        <td valign="top">'.htmlspecialchars($row['QUERY'], ENT_QUOTES, 'UTF-8').'</td>
                                        <td valign="top">'.$strDetail.'</td>
                                    </tr>';
                                }
                                $str .= '</table>';
                                $str .= '<hr>';
                            }
                        }

                        if (!empty($_GET)) {
                            $str .= '<h2>Variables d\'URL (get)</h2>';
                            $str .= '<dl>';
                            foreach ($_GET as $k=> $v) {
                                if (is_array($v)) $v = print_r ($v, true);
                                $str .= '<dt>'.htmlspecialchars($k, ENT_QUOTES, 'UTF-8').'</dt><dd><code>'.htmlspecialchars($v, ENT_QUOTES, 'UTF-8').'</code></dd>';
                            }
                            $str .= '</dl>';
                        }
                        if (!empty($_POST)) {
                            $str .= '<h2>Variables postées</h2>';
                            $str .= '<dl>';
                            foreach ($_POST as $k=> $v) {
                                if (is_array($v)) $v = print_r ($v, true);
                                $str .= '<dt>'.htmlspecialchars($k, ENT_QUOTES, 'UTF-8').'</dt><dd><code>'.htmlspecialchars($v, ENT_QUOTES, 'UTF-8').'</code></dd>';
                            }
                            $str .= '</dl>';
                        }
                        if (!empty($_SESSION)) {
                            $str .= '<h2>Variables de session</h2>';
                            $str .= '<dl>';
                            foreach ($_SESSION as $k=> $v) {
                                if (is_array($v)) $v = print_r ($v, true);
                                $str .= '<dt>'.htmlspecialchars($k, ENT_QUOTES, 'UTF-8').'</dt><dd><code>'.htmlspecialchars($v, ENT_QUOTES, 'UTF-8').'</code></dd>';
                            }
                            $str .= '</dl>';
                        }
                        if (!empty($_COOKIE)) {
                            $str .= '<h2>Cookies</h2>';
                            $str .= '<dl>';
                            foreach ($_COOKIE as $k=> $v) {
                                if (is_array($v)) $v = print_r ($v, true);
                                $str .= '<dt>'.htmlspecialchars($k, ENT_QUOTES, 'UTF-8').'</dt><dd><code>'.htmlspecialchars($v, ENT_QUOTES, 'UTF-8').'</code></dd>';
                            }
                            $str .= '</dl>';
                        }
                        $SIT_LIBELLE = '[CMS.Eolas] ';
                        if (class_exists('CMS') && class_exists('Site') && CMS::getCurrentSite()) {
                            $SIT_LIBELLE = '[' . CMS::getCurrentSite()->getField('SIT_LIBELLE') . '] ';
                        }
                        self::mailReport($str, $SIT_LIBELLE . 'Temps d\'éxécution critique!', self::$_mailSlowSentTime);
                    }
                }
            }
            if (defined('DB_PROFILING_SUPPORT') && DB_PROFILING_SUPPORT) {
                $dbh->exec("set profiling = 0");
            }
            unset($_SESSION['SLOWREQUEST_LOG']['DB_PROFILING']);
        }
    }

    private static function mailReport($message, $subject, $touchFile)
    {
        if (!defined('MAIL_REPORT') || !MAIL_REPORT) {return;}
        include_once PHYSICAL_PATH . 'include/phpmailer/class.phpmailer.php';
        $MonMail = new Phpmailer();
        $MonMail->IsHTML(true);
        $MonMail->IsMail();
        $MonMail->Host = EMAIL_SMTPHOST;
        $MonMail->SetLanguage('fr', CLASS_DIR . 'phpmailer/');
        $MonMail->From = EMAIL_FROM;
        $MonMail->FromName = EMAIL_FROMNAME;
        $MonMail->Sender  = EMAIL_FROM;
        $_aSendTo = unserialize(MAIL_REPORT_TO);
        foreach ($_aSendTo as $email) {
            $MonMail->AddAddress($email);
        }
        $MonMail->Subject = $subject;
        $MonMail->Body = $message;
        $MonMail->Send();
        @touch(ADMIN_LOG_DIR.$touchFile);

    }

    private static function checkTTL($pathFile, $ttl)
    {
        $TTLexpired = false;
        if (!@strtotime($ttl)) {$ttl = '-5 seconds';}
        if (@file_exists($pathFile)) {
            $ts = @filemtime($pathFile);
            if ($ts < strtotime($ttl)) {
                $TTLexpired = true;
            }
        } else {
            $TTLexpired = true;
        }

        return $TTLexpired;
    }

}

if (defined('SLOWREQUEST_LOG') && SLOWREQUEST_LOG) {
    $dbh = DB::getInstance();
    if (version_compare($dbh->query("SELECT version()")->fetchColumn(), '5.0.37', '>=')) {
        define('DB_PROFILING_SUPPORT', true);
        $dbh->exec("set profiling = 1");
    } else {
        define('DB_PROFILING_SUPPORT', false);
    }
}
register_shutdown_function("ErrorHandler::shutdownTime", microtime(true));
