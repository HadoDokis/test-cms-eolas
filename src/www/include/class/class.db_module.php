<?php
/**
 * @author sylvainb@eolas.fr
 */
require_once CLASS_DIR . 'class.db_generic.php';

class module extends Generic
{
    /**
     * Profils disponibles pour le module
     *
     * @var array
     */
    protected $_aProfil = null;

    /**
     * Constructeur
     *
     * @param string $idtf Code du module
     */
    public function __construct($idtf)
    {
        parent::__construct($idtf, false);
    }

    public static function getModuleCode()
    {
        return 'MOD_CORE';
    }

    public function load()
    {
        $sql = 'select * from DD_MODULE where MOD_CODE=' . $this->dbh->quote($this->_idtf);
        if ($row = $this->dbh->query($sql)->fetch(PDO::FETCH_ASSOC)) {
            $this->setFields($row);
        } else {
            $this->_idtf = -1;
            $this->setFields(array ());
        }
    }

    public function delete()
    {

    }

    public function isDeletable()
    {

    }

    /**
     * Fonction permettant la récupération du libellé traduit associé au code demandé
     * @param  String       $key      La clé du libellé traduit (peut contenir un formatage particulier appliqué via sprintf)
     * @param  unknown_type $aSprintf Optionnel : les paramètres optionnels pour le formatage du résultat (sprintf)
     * @param  unknown_type $encode   Optionnel : true/false pour encoder le résultat (true par défaut)
     * @param  unknown_type $addExtra Optionnel : true/false pour forcer le second paramètre de l'encodage (false par défaut)
     * @return Ambigous     Le libellé traduit final
     */
    public function i18n($key, $aSprintf = array(), $encode = true, $addExtra = false)
    {
        //Si la session ne contient pas la date limite de consommation (DLC ou date de chargement des traductions) du module en cours
        // ou si cette DLC de chargement est trop ancienne
        //On (re)charge les traductions
        $time = time();
        if (empty($_SESSION['_tabI18N'][$this->getID()]) || $_SESSION['_tabI18N'][$this->getID()] < $time) {
            //Récupération des traductions du module en cours dans la base
            $dbh = DB :: getInstance();
            //On génère la requete en fonction de l'activation du module "traduction ou non"
            $sql = "select TRA_CODE, TRA_LIBELLE";
            if (CMS::getCurrentSite()->hasModule(new Module('MOD_TRADUCTION'))) {
                $sql = $sql . " from TRADUCTION_SITE
                    inner join DD_TRADUCTION using(TRA_CODE)
                    where DD_TRADUCTION.MOD_CODE=" . $dbh->quote($this->getID()) . " and SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID());
            } else {
                $sql = $sql . " from TRADUCTION_LANGUE
                    inner join DD_TRADUCTION using(TRA_CODE)
                    where DD_TRADUCTION.MOD_CODE=" . $dbh->quote($this->getID()) . " and LNG_CODE=" . $dbh->quote(CMS::getCurrentSite()->getField('LNG_CODE'));
            }
            //On stocke la DLC et on concatène les traductions du module en cours avec ceux précédemment chargés
            $_SESSION['_tabI18N'][$this->getID()] = $time + 300; //Date Limite de Consommation = 5 minutes
            if (!isset($_SESSION['_tabI18N']['data'])) {
                $_SESSION['_tabI18N']['data'] = array();
            }
            $_SESSION['_tabI18N']['data'] = array_merge($_SESSION['_tabI18N']['data'], $dbh->query($sql)->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_COLUMN|PDO::FETCH_UNIQUE));
        }
        //Dans tous les cas, les traductions sont chargées et on retourne la traduction du code demandé
        $valeurI18n = '!!' . $key . '!!';
        if (isset ($_SESSION['_tabI18N']['data'][$key])) {
            if (count($aSprintf)>0) {
                $valeurI18n = call_user_func_array('sprintf', array_merge(array($_SESSION['_tabI18N']['data'][$key]), $aSprintf));
            } else {
                $valeurI18n = $_SESSION['_tabI18N']['data'][$key];
            }
            if ($encode) {
                $valeurI18n = encode($valeurI18n, $addExtra);
            }
        }

        return $valeurI18n;
    }

    public function getProfils()
    {
        if ($this->_aProfil === null) {
            $sql = "select DD_PROFIL.PRO_CODE, DD_PROFIL.*
                    from DD_PROFIL
                    inner join MODULE_PROFIL using (PRO_CODE)
                    where MOD_CODE = " . $this->dbh->quote($this->getID());
            $this->_aProfil = $this->dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC|PDO::FETCH_GROUP|PDO::FETCH_UNIQUE);
        }

        return $this->_aProfil;
    }
}
