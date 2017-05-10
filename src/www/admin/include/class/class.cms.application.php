<?php
/*
 * Classe gérant la partie administration de l'application
 * @authos Harmen <harmen@endogene.net>
 */
require (dirname(__FILE__) . '/class.cms.modules.php');
require (dirname(__FILE__) . '/../clearbricks/_common.php');
// NON disponible pour le moment: require dirname(__FILE__).'/../check.php';

class application
{
    private static $app = null;
    private $fpLog = null; //< pointeur sur le fichier de log s'il existe et est définie et ouvert au par aileurs
    public $dbh = null; //< Connection
    public $availableMdl = null; //< Modules présents sur le système de fichier
    public $registeredMdl = null; //< Tableau contenant les info sur les différents modules installés

    public static function getInstance()
    {
        if (self :: $app == null) {
            self :: $app = new application();
        }

        return self :: $app;
    }

    private function __construct()
    {
        $this->dbh = DB_logger :: getInstance();
        $this->availableMdl = new CMS_availableModules();
        $this->registeredMdl = new CMS_registeredModules();
    }
    /**
     * Génère un éventuel log des actions réalisés
     * @param string $msg message à inscrire dans le log
     */
    public function log($msg)
    {
        if ($this->fpLog === null) {
            if (defined('ADMIN_LOG_FILE') && ADMIN_LOG_FILE) {
                $this->fpLog = @fopen(ADMIN_LOG_FILE,"a");
            } else {
                $this->fpLog = false;
            }
        }
        if ($this->fpLog) {
            fwrite($this->fpLog,$msg."\n");
        }
    }
    /**
     * Ferme le fichier journal
     */
    public function logclose()
    {
        if ($this->fpLog) {
            @fclose($this->fpLog);
        }
    }
    /**
     * Réalise un export SQL de la DB
     * @param string  $filName  Nom du fichier dans lequel suavegarder l'export
     * @param boolean $compress Le fichier doit-il être compressé
     */
    public function dumpDB($fileName, $compress = true)
    {
        $f = explode('.',basename($fileName));
        if (count($f) <= 1) {$format= ''; }
        $format = strtolower($f[count($f)-1]);
        $format = in_array($format, array('sql','xml'))? $format : 'sql';
        $f = ($format == 'xml')?' -X ':'';
        if ($compress) {
            $cmdDump = 'mysqldump --password='.escapeshellarg(DB_PASSWORD).' -u '.escapeshellarg(DB_USER).'  --skip-lock-tables -q ' . $f . escapeshellarg(DB_NAME) . ' | gzip > '.ADMIN_LOG_DIR.$fileName . '.gz';
        } else {
            $cmdDump = 'mysqldump --password='.escapeshellarg(DB_PASSWORD).' -u '.escapeshellarg(DB_USER).'  --skip-lock-tables -q ' . $f . escapeshellarg(DB_NAME) . ' > '.ADMIN_LOG_DIR.$fileName;
        }
        $output = array();
        $return_var = null;
        $res = exec($cmdDump, $output, $return_var);
        if ($return_var != 0) {
            throw new Exception("La sauvegarde de la base de données \"<var>".$fileName."</var>\" n'a pas pu être créée.<br>Veuillez, par exemple, vérifier les droits d'écriture au sein du répertoire <code>&quot;".ADMIN_LOG_DIR."&quot;</code>.", E_WARNING);
        }
    }

    /***************************************************************************
     * GESTION DES VERSIONS DES MODULES
     */
    /**
     * Valide que l'application est à jour en se basant sur l'information de version de chacun des modules et sur un contrôle de leurs dépendances
     * @param  string $id identifiant d'un module
     * @return bool   application à jour (true) ou non (false)
     */
    public function isUptodate($id = null)
    {
        if ($id &&
            $this->availableMdl->getModules($id) &&
            version_compare($this->availableMdl->moduleInfo($id,'version'),
                    $this->registeredMdl->moduleInfo($id,'version'),
                    '>')) {
            return false; // Module non à jour
        } elseif ($id && !$this->availableMdl->getModules($id)) {
            return null; // Module supprimé
        } elseif ($id) {
            return true; // Module à jour
        }
        $this->availableMdl->loadModules();
        $this->registeredMdl->loadModules();
        $aMdl = $this->availableMdl->getModules();
        foreach ($aMdl as $id => $m) {
            if ($this->isUptodate($id) === false) {
                return false;
                break;
            }
        }

        return true;
    }
    /**
     * Valide que les signatures sont identiques entre les "registeredMdl" et les "availableMdl"
     * @param  string $id Identifiant éventuel du module à valider la signature
     * @return bool   les signatures sont identiques ou pas
     */
    public function signatureIsUptodate($id = null)
    {
        if ($id &&
            $this->availableMdl->getModules($id) && $this->registeredMdl->getModules($id))
        {
            return $this->registeredMdl->getModules($id) == $this->availableMdl->getModules($id);
        } elseif ($id) {
            return null;
        }
        $this->availableMdl->loadModules();
        $this->registeredMdl->loadModules();
        $aMdl = $this->availableMdl->getModules();
        foreach ($aMdl as $id => $m) {
            if ($this->signatureIsUptodate($id) === false) {
                return false;
                break;
            }
        }

        return true;
    }
    /**
     * Met à jour les signatures des modules enregistrés à partir des module disponibles sur le systme de fichier
     */
    public function updateSignatures()
    {
        try {
            $this->log("\n==================================================================================================================\n".
                        "= MISE A JOUR DES SIGNATURES".
                        " (".strftime('%Y/%m/%d %H:%M:%S').") =\n");
            // Si les signatures ne sont pas différentes, on ne les met donc pas à jour
            if ($this->signatureIsUptodate()) {
                throw new Exception("<p>Les signatures sont à jour.</p>", E_WARNING);
            }
            // Si l'appli n'est pas à jour, on ne met pas à jour la signature des modules
            if (!$this->isUptodate()) {
                throw new Exception('<p>Les signatures ne peuvent pas être mise à jour seules car l\'application doit l\'être dans son ensemble.</p>');
            }
            // Pour chacun des modules
            $sign_ok = $sign_error = array();
            foreach ($this->registeredMdl->getModules() as $id => $m) {
                if ( !is_null($this->availableMdl->getModules($id)) && ($this->registeredMdl->getModules($id) != $this->availableMdl->getModules($id))) {
                    // On contrôle les dépendances
                    $bDepCheck = $this->availableMdl->dependencyIsChecked($id);
                    if ($bDepCheck || is_null($bDepCheck)) {
                        // On met à jour la signature des modules enregistrés dont la signature diffère avec celui disponible sur le système de fichier
                        $this->registeredMdl->registerModule($id,$this->availableMdl->getModules($id));
                        $sign_ok[] = $id;
                    } elseif (!is_null($bDepCheck)) {
                        $sign_error[] = $id;
                    }
                }
            }
            $msg = '';
            if (!empty($sign_ok)) {
                $s = implode(", ", $sign_ok);
                if (count($sign_ok) == 1) {
                    $msg .= "\n<p>La signature du module <strong>".$s."</strong> a été mise à jour.</p>";
                } else {
                    $msg .= "\n<p>La signature des modules <strong>".substr_replace($s, ' et', strrpos($s, ','), 1)."</strong> ont été mises à jour.</p>";
                }
            }
            if (!empty($sign_error)) {
                $s = implode(", ", $sign_error);
                if (count($sign_error) == 1) {
                    $msg .= "\n<p>La signature du module <strong>".$s."</strong> n'a pas été mise à jour car elle fait référence à des dépendances non valides.</p>";
                } else {
                    $msg .= "\n<p>La signature des modules <strong>".substr_replace($s, ' et', strrpos($s, ','), 1)."</strong> n'ont pas été mises à jour car elles font référence à des dépendances non valides.</p>";
                }

            }
            $this->log("\n".strip_tags($msg).
                    "\n==================================================================================================================");
        } catch (Exception $e) {
            $this->log("\n".strip_tags($msg)."\n".
                        "\n-----\n".strip_tags($e->getMessage())."\n\n".$e->getTraceAsString()."\n-----\n".
                    '==================================================================================================================');
            $msg .= $e->getMessage();
            if (defined('ADMIN_LOG_FILE') && file_exists(ADMIN_LOG_FILE)) {
                $this->logclose();
            }
            throw new Exception($msg, $e->getCode());
        }
        $this->logclose();

        return $msg;
    }
    /**
     * Lance la mise à jour automatique de l'application et de ces modules si necessaire
     */
    public function install()
    {
        if ($this->isUptodate()) {return;}
        set_time_limit(0);
        $msg = '';

        $this->log("\n==================================================================================================================\n".
                    "= INSTALLATION DE L'APPLICATION ".
                    " (".strftime('%Y/%m/%d %H:%M:%S').") =\n");
        try {
            // Si on doit sauvegarder la DB et qu'il y a déjà des modules (c'est à dire qu'il y a quelques chose à sauvegarder)
            $mdls_init = $this->registeredMdl->getModules();
            if (ADMIN_DB_SAVE && !empty($mdls_init)) {
                $fileName = DB_NAME.'_'.strftime('%Y-%m-%d_%H-%M-%S').'.sql';
                $this->dumpDB($fileName);
                $fileName .= ".gz";
            }
            $res  = $this->availableMdl->installModules();
            if (!empty($res['success'])) {
                // S'il y a des ereurs, on rajoute une note pour les modules dont la mise à jour a réussie
                $note = '';
                if (!empty($res['failure'])) {$note = ' : Mise à jour réussie';}
                $msg .= '<ul>';
                foreach ($res['success'] as $id => $result) {
                    $msg .= "\n<li class=\"txtValide\"><strong>".$id."</strong> (v".$this->registeredMdl->moduleInfo($id,'version').")".($result === true?$note:' : ' . $result)."</li>";
                }
                $msg .= '</ul>';
            }
            if (!empty($res['failure'])) {
                $msg .= '<ul>';
                foreach ($res['failure'] as $id => $s) {
                    $msg .= "\n<li class=\"txtError\"><strong>".$id."</strong> (v".$this->availableMdl->moduleInfo($id,'version').") : ".$s."</li>";
                }
                $msg .= '</ul>';
                throw new Exception($msg);
            }

            if (!empty($mdls_init)) {
                $msg = "<p>L'application a été mise à jour avec succès.</p>\n".$msg;
            } else {
                $msg = "<p>L'application a été installée avec succès.</p>\n".$msg;
            }
            if (!empty($fileName) && file_exists(ADMIN_LOG_DIR.$fileName)) {
                $msg .= "\n\n<p>Une sauvegarde de la base avant mise à jour est disponible sous \"<strong>".ADMIN_LOG_DIR.$fileName."</strong>\".</p>\n";
            }
            $this->log("\n".strip_tags($msg).
                    "\n==================================================================================================================");

        } catch (Exception $e) {
            if ($e->getCode() != E_NOTICE) {
                $msg = "<p>La mise à jour de l'application a rencontré au moins une erreur </p>";
                if ($e->getCode()>0) {
                    $msg .= "<var>".$e->getFile()."</var>(".$e->getLine()."):<br>";
                }
                $this->log("\n".strip_tags($msg)."\n".
                            "\n-----\n".strip_tags($e->getMessage())."\n\n".$e->getTraceAsString()."\n-----\n");
            } else {
                $this->log("\n".strip_tags($e->getMessage())."\n");
            }
            $msg .= $e->getMessage();

            if (!empty($fileName) && file_exists(ADMIN_LOG_DIR.$fileName)) {
                $saveInfo = "\n<p>Une sauvegarde de la base avant mise à jour est disponible sous \"<strong>".ADMIN_LOG_DIR.$fileName."</strong>\".</p>\n";
                $this->log(strip_tags($saveInfo));
                $msg .= $saveInfo;
            }
            $this->log('==================================================================================================================');
            if (defined('ADMIN_LOG_FILE') && file_exists(ADMIN_LOG_FILE)) {
                $this->logclose();
            }
            throw new Exception($msg, $e->getCode());
        }
        $this->logclose();

        return $msg;
    }
    /**
     * Vérifie si une install initiale est necessaire,
     * si c'est pas le cas, réalise une redirection vers celle-çi
     */
    public function checkInitialInstall()
    {
        $sql = 'show tables like \'DD_MODULES\'';
        if (!$this->dbh->query($sql)->fetchColumn()) {
            //* Dans ce cas, on redirige vers l'écran d'installation
            header('Location:' . ADMIN_ROOT . 'install.php');
            exit();
        }

        return true;
    }
    /**
     * FIN GESTION DES MODULES
     **************************************************************************/
}
class DB_logger extends DB
{
    private static $dbh = null;
    /**
     * @return PDO_logger
     */
    public static function getInstance()
    {
        if (self :: $dbh == null) {
            try {
                self :: $dbh = new PDO_logger(DB_DSN, DB_USER, DB_PASSWORD);
                self :: $dbh->setAttribute(PDO :: ATTR_CASE, PDO :: CASE_UPPER);
                self :: $dbh->setAttribute(PDO :: ATTR_ERRMODE, PDO :: ERRMODE_EXCEPTION);
                self :: $dbh->setAttribute(PDO :: ATTR_ORACLE_NULLS, PDO :: NULL_TO_STRING);
                self :: $dbh->query('SET NAMES utf8');
            } catch (PDOException $e) {
                throw new Exception('Echec de la connexion : ' . $e->getMessage(), $e->getCode());
            }
        }

        return self :: $dbh;
    }
}
class PDO_logger extends PDO
{
    private $rq = 0; //< ReQuest counter
    /**
     * retourne le driver utilisé pour la connexion
     */
    public function driver()
    {
        return $this->getAttribute(PDO :: ATTR_DRIVER_NAME);
    }
    /**
     * Protège les nom de champs et tables
     */
    public function escapeSystem($str)
    {
       return '`'.$str.'`';
    }
    /**
     * Retourne la version du serveur de DB
     * @return string version du serveur de DB
     */
    public function version()
    {
        return $this->getAttribute(PDO :: ATTR_SERVER_VERSION);
    }
    /**
     * Retourne le nb des requêtes loggées
     * @return int nb des requêtes logées
     */
    public function count()
    {
        return $this->rq;
    }
    /**
     * Exécute une requête SQL via la commande "exec" de PDO
     * @param  string $sql Requête sql à exécuter
     * @return int    nb de ligne affectées par la requête
     */
    public function exec($sql)
    {
        $this->rq++;
        $app = application :: getInstance();
        $app->log("\n".$sql.";\n");
        try {
          return parent :: exec($sql);
        } catch (Exception $e) {
            throw new Exception('Erreur SQL : ' . $e->getMessage());
        }
    }
}
