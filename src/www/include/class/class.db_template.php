<?php
require_once CLASS_DIR . 'class.db_generic.php';

class Template extends Generic
{

    /**
     * Clés par défaut.
     * Attention! il faut modifier la méthode replaceKey en conséquence
     */
    public static $aDefaultKeys = array(
        '[PAG_TITLE]' => 'Title de la page courante',
        '[PAG_DESCRIPTION]' => 'Description de la page courante',
        '[SIT_TITLE]' => 'Titre du site courant',
        '[VIDE]' => 'Force à vide'
    );

    /**
     * Pattern par défaut.
     * Attention! il faut modifier la méthode replaceProperties en conséquence
     */
    public static $aDefaultProperties = array(
        'Title',
        'Metadescription',
        'Titre de page',
        'Fil ariane',
        'og-title',
        'og-description',
        'og-type',
        'og-image'
    );

    protected $_oModule;

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
        $sql = "select * from DD_TEMPLATE where TPL_CODE=" . $this->dbh->quote($this->getID());
        if ($row = $this->dbh->query($sql)->fetch(PDO::FETCH_ASSOC)) {
            $this->setFields($row);
        } else {
            $this->_idtf = - 1;
            $this->setFields(array());
        }
    }

    public function delete()
    {
        return false;
    }

    public function isDeletable()
    {
        return false;
    }

    /**
     * Retourne un tableau contenant les propriétés du template
     *
     * @return Array
     */
    public function getProperties()
    {
        $aParam = array();
        foreach (self::$aDefaultProperties as $key) {
            $aParam[$key] = '';
        }
        foreach (array_filter(explode('@', $this->getField('TPL_PARAMETRAGE'))) as $p) {
            $aTmp = explode(':', $p);
            $aParam[$aTmp[0]] = $aTmp[1];
        }
        return $aParam;
    }

    /**
     * Retourne un tableau contenant les clés disponibles pour les paramètres du template
     *
     * @return Array
     */
    public function getKeys()
    {
        $aParam = array();
        foreach (array_filter(explode('@', $this->getField('TPL_PARAMETRAGE_CLES'))) as $p) {
            $aTmp = explode(':', $p);
            $aParam[$aTmp[0]] = $aTmp[1];
        }
        return $aParam;
    }

    /**
     * Execute les fonctions de traitement des paramètres dont les méthodes ont été implémentées
     *
     * @param Object $oExterne
     *                         Objet sur lequel appeler les méthodes de traitement
     */
    public function replaceProperties($oExterne = null)
    {
        foreach ($this->getProperties() as $key => $val) {
            if ($val != '') {
                $val = $this->replaceKey($val, $oExterne);
                if ($oExterne && method_exists($oExterne, 'replaceProperties')) {
                    $val = call_user_func_array(array(
                        $oExterne,
                        'replaceProperties'
                    ), array(
                        $key,
                        $val
                    ));
                }
                switch ($key) {
                    case 'Title':
                        CMS::replaceTITLE($val);
                        break;
                    case 'Titre de page':
                        CMS::replaceTITREPAGE($val);
                        break;
                    case 'Metadescription':
                        CMS::replaceMETADESCRIPTION($val);
                        break;
                    case 'Fil ariane':
                        CMS::addToARIANE($val);
                        break;
                    case 'og-title':
                    case 'og-description':
                    case 'og-type':
                    case 'og-image':
                        CMS::addMETAPROPERTY(str_replace('-', ':', $key),  $val);
                        break;
                }
            }
        }
    }

    public function replaceKey($val, $oExterne, $oPage = null)
    {
        if ($oExterne && method_exists($oExterne, 'replaceKey')) {
            $val = call_user_func_array(array(
                $oExterne,
                'replaceKey'
            ), array(
                $val
            ));
        }
        $oSite = CMS::getCurrentSite();
        if (! $oPage) {
            $oPage = $oSite->getCurrentPage();
        }
        return str_replace(array_keys(self::$aDefaultKeys), array(
            $oPage->getField('PAG_TITRE_REFERENCEMENT'),
            $oPage->getField('PAG_METADESCRIPTION'),
            $oSite->getField('SIT_TITLE'),
            ''
        ), $val);
    }

    /**
     * Vérifie l'activation du template via son module sur le site passé en paramètre
     *
     * @param  Site $oSite
     * @return bool
     */
    public function isEnabled(Site $oSite)
    {
        if ($this->exist() && $oSite->hasModule($this->getModule())) {
            $sql = "select count(DD_TEMPLATE.TPL_CODE) from DD_TEMPLATE
                        left join DD_TEMPLATE_GABARIT on (DD_TEMPLATE.TPL_CODE = DD_TEMPLATE_GABARIT.TPL_CODE)
                        where DD_TEMPLATE.TPL_CODE=" . $this->dbh->quote($this->getID()) . "
                        and (
                            ID_TEMPLATE_GABARIT IS NULL
                            or DD_TEMPLATE_GABARIT.GAB_CODE = " . $this->dbh->quote($oSite->getField('GAB_CODE')) . "
                        )";

            return $this->dbh->query($sql)->fetchColumn() > 0; // Le template est diposnible
        }

        return false;
    }

    /**
     * Voir methode class.db_module.php-->i18n()
     */
    public function i18n($key, $aSprintf = array(), $encode = true, $addExtra = false)
    {
        return $this->getModule()->i18n($key, $aSprintf, $encode, $addExtra);
    }

    /**
     *
     * @return Module
     */
    public function getModule()
    {
        if (is_null($this->_oModule)) {
            require_once CLASS_DIR . 'class.db_module.php';
            $this->_oModule = new Module($this->getField('MOD_CODE'));
        }
        return $this->_oModule;
    }
}
