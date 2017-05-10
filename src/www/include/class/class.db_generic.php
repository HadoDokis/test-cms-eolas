<?php
/**
 * Classe de base, toutes les classes "Model" etendent cette classe
 * @package CMS/DB
 */
abstract class Generic
{
    /**
     * Id de l'objet en base
     * @var Int | String
     */
    protected $_idtf;
    /**
     * Tableau contenant les champs de la base pour cet objet
     * @var array
     */
    protected $_fields;
    /**
     * Objet d'interface avec la base de données
     * @var PDO
     */
    protected $dbh;

    /**
     * Contructeur générique
     * @param Mixed   $idtf  La plus part du temps un Integer ou un String correspondant à la clé primaire en base de données
     * @param boolean $isInt protège l'idtf si true, laisse tel quel sinon
     */
    public function __construct($idtf, $isInt = true)
    {
        $this->_idtf   = ($isInt) ? intval($idtf) : $idtf;
        $this->_fields = array ();
        $this->dbh     = DB::getInstance();
    }

    /**
     * Méthode abstraite permettant de charger une instance de classe : initialiser les valeurs du mapping de la base
     */
    abstract public function load();

    /**
     * Méthode abstraite permettant de supprimer une instance de classe
     */
    abstract public function delete();


    /**
     * Méthode abstraite permettant de vérifier si la suppression est possible
     *
     * @return bool
     */
    abstract public function isDeletable();

    /**
     * Méthode abstraite permettant d'associer la classe à un module
     *
     * @since  5.6
     * @return string
     */
    abstract public static function getModuleCode();

    /**
     * @return int l'identifiant de l'instance
     */
    public function getID()
    {
        return $this->_idtf;
    }

    /**
     * @return array la liste des champs
     */
    public function getFields()
    {
        if (!$this->exist()) {
            return array();
        }
        return $this->_fields;
    }

    /**
     * @param  string $field le champ souhaité
     * @return string la valeur d'un champ
     */
    public function getField($field)
    {
        if ((!$this->exist()) || (!isset ($this->_fields[$field]))) {
            throw new Exception(__METHOD__ . " (Champ non défini pour l'objet '" . get_class($this) . "') : " . $field);
        }

        return $this->_fields[$field];
    }

    /**
     * @param array $row    une liste de champ
     * @param bool  $append si la liste doit s'ajouter à l'existant ou le remplacer
     */
    public function setFields($row, $append = false)
    {
        if ($append) {
            $this->_fields = array_merge($this->_fields, $row);
        } else {
            $this->_fields = $row;
        }
    }

    /**
     * L'objet existe en base de données
     *
     * @return bool
     */
    public function exist()
    {
        if ($this->getID() != -1 && count($this->_fields) == 0) {
            $this->load();
        }
        return ($this->getID() != -1);
    }

    /**
     * Vérifie que la ressource est bien autorisée
     * Doit être redéfinie si le test ne porte pas sur le SIT_CODE (exemple avec db_page)
     *
     * @since  5.6
     * @return bool
     */
    public function checkAuthorized($strict = true)
    {
        if ($this->exist() && $this->getField('SIT_CODE') == CMS::getCurrentSite()->getID()) {
            return true;
        } elseif ($strict && Utilisateur::getConnected() && Utilisateur::getConnected()->isRoot()
            && ($this->getField('SIT_CODE') != CMS::getCurrentSite()->getID())
        ) {
            Utilisateur::getConnected()->initSession($this->getField('SIT_CODE'));
            //on redirige pour rafraichir les initialisations dans les includes
            header('Location:' . $_SERVER['REQUEST_URI']);
            exit();
        }
        if ($strict) {
            header('Location:' . SERVER_ROOT . 'cms/index.php?logout=1');
            exit ();
        }

        return false;
    }

    /**
     * Vérifie qu'il s'agit bien d'une ressource 'partagée'
     *
     * @since  5.6
     * @return bool
     */
    public function checkShareAuthorized($strict = true)
    {
        $aRevertSharedSite = CMS::getCurrentSite()->getRevertSharedSites();
        if ($this->exist() && isset($aRevertSharedSite[$this->getField('SIT_CODE')])) {
            return true;
        }
        if ($strict) {
            header('Location:' . SERVER_ROOT . 'cms/index.php?logout=1');
            exit ();
        }

        return false;
    }
}
