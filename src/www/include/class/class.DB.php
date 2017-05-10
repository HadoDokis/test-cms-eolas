<?php
/**
 * Classe permettant de débugger les requêtes SQL
 *
 * @package CMS/DB
 *
 */
class PDO_TRACE extends PDO
{
    //Compteur du nombre de requête exécutées
    protected $queryCount = 0;

    public function __construct($db_dns, $db_user, $db_password)
    {
        parent :: __construct($db_dns, $db_user, $db_password);
        $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, array('PDO_TRACE_STATEMENT', array($this)));
    }

    public function logInfo2Session($query, $execTime) {
        //Récupération de la provenance d'exécution de la requête (ligne des fichiers appelant)
        $f = '';
        foreach (debug_backtrace() as $val) {
            $f .= basename($val['file']) . ' : ' . $val['line'] . '<br>' ;
        }

        //Ajout de l'info sur la requête et sa durée d'exécution
        $_SESSION['DEBUG_INFO']['DB'][$query][] = array(
            'DURATION'=>($execTime),
            'FILE'=>$f
        );

        if (defined('SLOWREQUEST_LOG') && SLOWREQUEST_LOG) {
            $_SESSION['SLOWREQUEST_LOG']['DB_PROFILING'][$query]['DURATION_CMS'] = ($execTime);
            $_SESSION['SLOWREQUEST_LOG']['DB_PROFILING'][$query]['FILE'] = $f;
            if (defined('DB_PROFILING_SUPPORT') && DB_PROFILING_SUPPORT) {
                $rowList = parent :: query('show profiles')->fetchAll(PDO::FETCH_ASSOC);
                foreach ($rowList as $row) {
                    if ($row['QUERY'] == $query) {
                        $_SESSION['SLOWREQUEST_LOG']['DB_PROFILING'][$query]['SQL'] = $row;
                        $rowsDetail = parent ::  query('show profile for query '.$row['QUERY_ID'])->fetchAll(PDO::FETCH_ASSOC);
                        if ($rowsDetail) {
                            $_SESSION['SLOWREQUEST_LOG']['DB_PROFILING'][$query]['SQL']['DETAILS'] = $rowsDetail;
                        }
                        break;
                    }
                }
            }
        }
    }

    public function exec($query)
    {
        //Temps de début de l'exécution
        $startTime = microtime(true);
        //Exécution de la requete
        $res = parent ::  exec($query);
        //Temps d'éxécution final par rapport au début
        $execTime = microtime(true) - $startTime;

        // Log des infos de la requette
        $this->logInfo2Session($query, $execTime);

        //Renvoi du résultat
        return $res;
    }

    public function query($query)
    {
        //Temps de début de l'exécution
        $startTime = microtime(true);
        //Exécution de la requete
        $res = parent ::  query($query);
        //Temps d'éxécution final par rapport au début
        $execTime = microtime(true) - $startTime;

        // Log des infos de la requette
        $this->logInfo2Session($query, $execTime);

        //Renvoi du résultat
        return $res;
    }

    //Méthode de récupération du nombre de requête
    public function getQueryCount()
    {
        $res = 0;
        foreach ($_SESSION['DEBUG_INFO']['DB'] as $query => $nombreAppels) {
            $res = $res + count($nombreAppels);
        }

        return $res;
    }
}

class PDO_TRACE_STATEMENT extends PDOStatement {
    //debugDumpParams
    private $blnIsTrace = false;
    private $dbh = false;
    private $aParms = array();
    protected function __construct($dbh) {
       $this->dbh = $dbh;
    }

    /**
     * Executes a prepared statement
     * @link http://www.php.net/manual/en/pdostatement.execute.php
     * @param input_parameters array[optional] <p>
     * An array of values with as many elements as there are bound
     * parameters in the SQL statement being executed.
     * All values are treated as PDO::PARAM_STR.
     * </p>
     * <p>
     * You cannot bind multiple values to a single parameter; for example,
     * you cannot bind two values to a single named parameter in an IN()
     * clause.
     * </p>
     * @return bool Returns true on success or false on failure.
     */

    public function execute(array $input_parameters = null) {
        $strQuery = $this->queryString;
        try {

            foreach ($this->aParms as $pattern => $aValueAndType) {
                if ($aValueAndType['type'] == PDO::PARAM_STR) {
                    $replacement = $this->dbh->quote($aValueAndType['value']);
                } else {
                    $replacement = $aValueAndType['value'];
                }
                $strQuery = str_replace($pattern, $replacement, $strQuery);
            }
        } catch (Exception $e) {
            //Do nothing as PDO will launch warning/error
            echo 'Exception execute : PDO_TRACE_STATEMENT';die();
        }

        //Temps de début de l'exécution
        $startTime = microtime(true);
        //Exécution de la requete
        $res = parent::execute($input_parameters);
        //Temps d'éxécution final par rapport au début
        $execTime = microtime(true) - $startTime;

        //remettre a zero la valeur param
        $this->aParms = array();

        // Log des infos de la requette
        $this->dbh->logInfo2Session($strQuery, $execTime);

        //Renvoi du résultat
        return $res;
    }


    /**
     * Binds a parameter to the specified variable name
     * @link http://www.php.net/manual/en/pdostatement.bindparam.php
     * @param parameter mixed <p>
     * Parameter identifier. For a prepared statement using named
     * placeholders, this will be a parameter name of the form
     * :name. For a prepared statement using
     * question mark placeholders, this will be the 1-indexed position of
     * the parameter.
     * </p>
     * @param variable mixed <p>
     * Name of the PHP variable to bind to the SQL statement parameter.
     * </p>
     * @param data_type int[optional] <p>
     * Explicit data type for the parameter using the PDO::PARAM_*
     * constants.
     * To return an INOUT parameter from a stored procedure,
     * use the bitwise OR operator to set the PDO::PARAM_INPUT_OUTPUT bits
     * for the data_type parameter.
     * </p>
     * @param length int[optional] <p>
     * Length of the data type. To indicate that a parameter is an OUT
     * parameter from a stored procedure, you must explicitly set the
     * length.
     * </p>
     * @param driver_options mixed[optional] <p>
     * </p>
     * @return bool Returns true on success or false on failure.
     */
    public function bindParam($parameter, &$variable, $data_type = null, $length = null, $driver_options = null) {
        $varVal = $variable;
        $this->aParms[$parameter]['value'] = $varVal;
        $this->aParms[$parameter]['type'] = $data_type;
        return parent::bindParam($parameter, $variable, $data_type, $length, $driver_options);
    }

    /**
     * Bind a column to a PHP variable
     * @link http://www.php.net/manual/en/pdostatement.bindcolumn.php
     * @param column mixed <p>
     * Number of the column (1-indexed) or name of the column in the result set.
     * If using the column name, be aware that the name should match the
     * case of the column, as returned by the driver.
     * </p>
     * @param param mixed <p>
     * Name of the PHP variable to which the column will be bound.
     * </p>
     * @param type int[optional] <p>
     * Data type of the parameter, specified by the PDO::PARAM_* constants.
     * </p>
     * @param maxlen int[optional] <p>
     * A hint for pre-allocation.
     * </p>
     * @param driverdata mixed[optional] <p>
     * Optional parameter(s) for the driver.
     * </p>
     * @return bool Returns true on success or false on failure.
     */
    public function bindColumn($column, &$param, $type = null, $maxlen = null, $driverdata = null) {
        return parent::bindColumn($column, $param, $type, $maxlen, $driverdata);
    }

    /**
     * Binds a value to a parameter
     * @link http://www.php.net/manual/en/pdostatement.bindvalue.php
     * @param parameter mixed <p>
     * Parameter identifier. For a prepared statement using named
     * placeholders, this will be a parameter name of the form
     * :name. For a prepared statement using
     * question mark placeholders, this will be the 1-indexed position of
     * the parameter.
     * </p>
     * @param value mixed <p>
     * The value to bind to the parameter.
     * </p>
     * @param data_type int[optional] <p>
     * Explicit data type for the parameter using the PDO::PARAM_*
     * constants.
     * </p>
     * @return bool Returns true on success or false on failure.
     */
    public function bindValue($parameter, $value, $data_type = null) {
        $this->aParms[$parameter]['value'] = $value;
        $this->aParms[$parameter]['type'] = $data_type;
        return parent::bindValue($parameter, $value, $data_type);
    }

}

/**
 * Classe interface avec la base de données
 * @package CMS
 *
 */
class DB
{
    private static $a_dbh = array();

    /**
     * Instancie l'objet PDO
     * @param  String $dbdsn      Si aucun n'est fourni, prend la valeur DB_DSN dans config.php
     * @param  String $dbusuer    Si aucun utilisateur n'est fourni, prend la valeur DB_USER dans config.php
     * @param  String $dbpassword Si aucun utilisateur n'est fourni, prend la valeur DB_PASSWORD dans config.php
     * @return PDO    Objet PDO
     *
     * @todo   Supprimer l'appel à la session S_UTI_LOGIN
     */
    public static function getInstance($dbdsn = DB_DSN, $dbusuer = DB_USER, $dbpassword = DB_PASSWORD)
    {
        if (self :: $a_dbh[$dbdsn] == null) {
            try {
                //Si l'utilisateur connecté est lié au mode debug, on utilise la classe en charge de ce mode
                if (CMS_DEBUG == $_SESSION['S_UTI_LOGIN']) {
                    self :: $a_dbh[$dbdsn] = new PDO_TRACE($dbdsn, $dbusuer, $dbpassword);
                } else {
                    self :: $a_dbh[$dbdsn] = new PDO($dbdsn, $dbusuer, $dbpassword);
                }
                self :: $a_dbh[$dbdsn]->setAttribute(PDO :: ATTR_CASE, PDO :: CASE_UPPER);
                self :: $a_dbh[$dbdsn]->setAttribute(PDO :: ATTR_ERRMODE, PDO :: ERRMODE_EXCEPTION);
                self :: $a_dbh[$dbdsn]->setAttribute(PDO :: ATTR_ORACLE_NULLS, PDO :: NULL_TO_STRING);
                self :: $a_dbh[$dbdsn]->setAttribute(PDO :: MYSQL_ATTR_USE_BUFFERED_QUERY, true);
                self :: $a_dbh[$dbdsn]->setAttribute(PDO :: ATTR_EMULATE_PREPARES, true);
                self :: $a_dbh[$dbdsn]->exec('SET NAMES utf8');

            } catch (PDOException $e) {
                echo 'Echec de la connexion : ' . secureInput($e->getMessage());
            }
        }

        return self :: $a_dbh[$dbdsn];
    }
}
