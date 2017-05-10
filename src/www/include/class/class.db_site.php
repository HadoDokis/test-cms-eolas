<?php
require_once CLASS_DIR . 'class.db_generic.php';

class Site extends Generic
{

    /**
     * Valeur par défault du SIT_CONNECTION_MAX
     * @var int
     */
    public static $default_connexion_max = 5;

    /**
     * Valeur par défault du SIT_CONNECTION_TTL
     * @var int
     */
    public static $default_connexion_ttl = 10;

    /**
     * @var Page Page d'accueil du site
     */
    private $_homePage = null;

    /**
     * @var Page Page courante
     */
    private $_currentPage = null;

    /**
     * @var array Tableau des pages spéciales du site
     */
    private $_aSpecialePage  = null;

    /**
     * @var int Identifiant de la page sécurisée intialement appelée
     */
    private $_securedID = null;

    /**
     * @var array Extensions autorisées pour les différents fichiers
     */
    private static $_aFMExtension = array(
        'SIT_LOGO'		=> array('.jpeg', '.png', '.gif', '.jpg'),
        'SIT_FAVICON'	=> array('.ico'));


    /**
     * @var bool    Des données du site sont liées à d'autre site ou non
     */
    private $_isLinked = null;

    /**
     * Modules disponibles pour le site
     *
     * @var array
     */
    private $_loadedModules = null;

    /**
     * Tous les modules, activés ou pas
     *
     * @var array
     */
    private static $_allModules = null;

    /**
     * Ensemble des sites partagés (from & to)
     *
     * @var array Site avec SIT_CODE en clé
     */
    private $_sharedSites = null;

    private $_revertSharedSites = null;

    public function __construct($idtf)
    {
        parent::__construct($idtf, false);
    }

    /**
     * Retourne le module de référence de la classe
     *
     * @since  5.6
     * @return string
     */
    public static function getModuleCode()
    {
        return 'MOD_CORE';
    }

    public function load()
    {
        $sql = "select * from DD_SITE
            inner join DD_GABARIT using(GAB_CODE)
            inner join DD_GABARITSTYLE on (DD_SITE.GBS_CODE=DD_GABARITSTYLE.GBS_CODE)
            inner join DD_GABARITIMAGE on (DD_SITE.GBI_CODE=DD_GABARITIMAGE.GBI_CODE)
            where DD_SITE.SIT_CODE=" . $this->dbh->quote($this->getID());
        if ($row = $this->dbh->query($sql)->fetch(PDO :: FETCH_ASSOC)) {
            $this->setFields($row);
            $aShortcutInfo = array(
                'SIT_INCLUDE'      => PHYSICAL_PATH . $this->getField('GAB_PATH'),
                'SIT_IMAGE'        => SERVER_ROOT . $this->getField('GBI_PATH'),
                'SIT_LANGUE'       => $this->getField('LNG_CODE'),
                'SIT_SHORT_LANGUE' => substr($this->getField('LNG_CODE'), 0, 2)
            );
            $this->setFields($aShortcutInfo, true);
        } else {
            $this->_idtf = -1;
            $this->setFields(array ());
        }
    }

    /**
     * TODO : gérer la suppression des DE liées (via le module handler de Sylvain)
     * TODO : voir si les données du noyau ne peuvent pas aussi être gérés via le module handler car tout est module ;-)
     */
    public function delete()
    {
        if (!$this->isDeletable()) {
            return false;
        }

        /**
         * Implémenter la suppression de l'historique d'un site supprimé
         */

        require_once CLASS_DIR . 'class.File_management.php';
        $fm = new File_management('DD_SITE', 'SIT_CODE', $this->getID(), UPLOAD_IMAGE_PHYSIQUE);
        $fm->delete('SIT_LOGO');
        $fm->delete('SIT_FAVICON');

        $fmGA = new File_management('DD_SITE', 'SIT_CODE', $this->getID(), UPLOAD_EXTERNE_PHYSIQUE);
        $fmGA->delete('SIT_GA_KEYFILE');

        $this->deleteFichiersGenerique();

        require_once CLASS_DIR . 'class.ModuleHandler.php';

        $moduleHandler = new ModuleHandler($this);

        $this->_loadAllModules();

        foreach (array_reverse(self::$_allModules, true) as $modCode => $module) {
            if ($modCode) {
                $moduleHandler->delete($module);
            }
        }

        // Suppression de l'historique après avoir supprimé l'ensemble des éléments
        $this->deleteHistorique();

        $quote = $this->dbh->quote($this->getID());
        $this->dbh->exec("delete from ONLINEUSER where SIT_CODE=" . $quote);
        $this->dbh->exec("delete from SITE_MODULE where SIT_CODE=" . $quote);
        $this->dbh->exec("delete from SITE_PARTAGE where SIT_CODE_FROM=" . $quote . " or SIT_CODE_TO=" . $quote);
        $this->dbh->exec("delete from GROUPE where SIT_CODE=" . $quote);
        $this->dbh->exec("delete from DD_SITE where SIT_CODE=" . $quote);

        unset($_SESSION['Sa_SIT_CODE'][$this->getID()]);
        unset($_SESSION['Sa_SIT_CODE_all'][$this->getID()]);

        return true;
    }

    /**
     * Retourne true si on peut supprimer le site, false sinon
     *
     * @return bool
     */
    public function isDeletable()
    {
         //On ne peut pas supprimer le site courant
        if (CMS::getCurrentSite()->getID() == $this->getID()) {
            return false;
        }

        //On ne peut pas supprimer le site si c'est celui de l'utilisateur connecté (sinon ca revient à se supprimer)
        if (Utilisateur::getConnected()->getField('SIT_CODE') == $this->getID()) {
            return false;
        }

        if (count($this->getSharedSites()) >= 1) {
            return false;
        }

        /**
         * On ne peut supprimer le site s'il possede des pages,
         * autres que la page d'accueil avec un état "hors ligne"
         */
        $sql = 'select
                (
                    select count(ID_PAGE)
                    from ON_PAGE
                    where SIT_CODE=' . $this->dbh->quote($this->getID()) . '
                ) + (
                    select count(ID_PAGE)
                    from OFF_PAGE
                    where SIT_CODE = ' . $this->dbh->quote($this->getID()) . '
                    and PST_CODE <> \'PST_HORSLIGNE\'
                )';
        if ($this->dbh->query($sql)->fetchColumn() != 0) {
            return false;
        }

        if ($this->isLinked()) {
            return false;
        }

        return true;
    }

    /**
     * Vérifie pour chaque éléments des tables de gestion
     * des liaison [LIAISON_PAGE | LIAISON_THEMATIQUE | LIAISON_WEBOTHEQUE]
     * si le code du site est different du site en cours
     *
     * @since  5.5
     * @return bool
     */
    public function isLinked()
    {

        if ($this->_isLinked !== null && is_bool($this->_isLinked)) {
            return $this->_isLinked;
        }

        if (!$this->exist()) {
            $this->_isLinked = false;

            return $this->_isLinked;
        }

        // Vérification des liaisons entre les modules de ce site et des éléments d'autres sites
        require_once CLASS_DIR . 'class.ModuleHandler.php';

        $moduleHandler = new ModuleHandler($this);

        $this->_loadAllModules();

        foreach (self::$_allModules as $modCode => $module) {
            if ($moduleHandler->isDeletable($module) == false) {
                $this->_isLinked = true;
                return $this->_isLinked;
            }
        }

        $this->_isLinked = false;

        return $this->_isLinked;
    }

    /**
     *
     * @return bool
     */
    public function upload()
    {
        require_once CLASS_DIR . 'class.File_management.php';
        $ok = true;
        $fm = new File_management('DD_SITE', 'SIT_CODE', $this->getID(), UPLOAD_IMAGE_PHYSIQUE);
        foreach (self::$_aFMExtension as $field => $aFMExtension) {
            $fm->setExtensions($aFMExtension);
            if (!$fm->upload($field)) {
                if ($fm->error == 'FM_EXTENSION') {
                    $ok = false;
                    setMSG('Extension incorrecte (' . implode(', ', $fm->getExtensions()) . ')', 'ERROR');
                } elseif (!empty ($fm->error)) {
                    $ok = false;
                    setMSG('Upload : ' . $fm->error, 'ERROR');
                }
                $fm->checkDelete($field);
            }
        }

        return $ok;
    }

    /**
     * Renvoi un tableau contenant les Id des pages appartenant au site.
     *
     * @return array
     */
    public function getIdPages($mode = 'OFF_')
    {
        $sql = 'select ID_PAGE
                from '.$mode.'PAGE
                where SIT_CODE='.$this->dbh->quote($this->getID()) ;

        return $this->dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Renvoi la page d'accueil du site
     *
     * @since  5.6
     * @return Page | bool
     */
    public function getHomePage($mode = null)
    {
        if ($this->_homePage === null || $mode !== null) {
            if ($mode===null) {
                $mode = CMS::$mode;
            }
            require_once CLASS_DIR . 'class.db_page.php';
            $sql = 'select *
                    from '. $mode . 'PAGE
                    where SIT_CODE=' . $this->dbh->quote($this->getID()) . '
                    and PAG_IDPERE is null';
            if ($row = $this->dbh->query($sql)->fetch(PDO::FETCH_ASSOC)) {
                $oPage = new Page($row['ID_PAGE'], $mode);
                $oPage->setFields($row);
                $this->_homePage = $oPage;
            } else {
                $this->_homePage = false;
            }
        }

        return $this->_homePage;
    }

    /**
     * Sauvegarde la page courante
     *
     * @since  5.6
     * @param  Page $oPage un objet page chargée (load)
     * @return Site
     */
    public function setCurrentPage(Page $oPage)
    {
        $this->_currentPage = $oPage;

        return $this;
    }

    /**
     * Retourne la page courante (utilisation dans les TPL)
     *
     * @since  5.6
     * @return Page la page chargée (load)
     */
    public function getCurrentPage()
    {
         return $this->_currentPage;
    }

    /**
     * Retourne la page spéciale chargée (load) si elle existe ou faux sinon
     *
     * @since  5.6
     * @param  string $PGS_CODE le code de la page
     * @param  string $mode     le mode de la page (si renseigné la page n'est pas mise en cache, si non on utilise le mode courant)
     * @return Page   | bool
     */
    public function getSpecialePage($PGS_CODE, $mode = null)
    {
        require_once CLASS_DIR . 'class.db_page.php';
        require_once CLASS_DIR . 'class.db_module.php';
        if ($mode != null) {
            $sql = 'select p.*, s.MOD_CODE
                        from ' . $mode . 'PAGE p
                        inner join DD_PAGESPECIALE s on p.PGS_CODE=s.PGS_CODE
                        where p.SIT_CODE=' . $this->dbh->quote($this->getID()) . '
                        and p.PGS_CODE=' . $this->dbh->quote($PGS_CODE);
            $row = $this->dbh->query($sql)->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $module = new Module($row['MOD_CODE']);
                if ($this->hasModule($module)) {
                    unset($row['MOD_CODE']);
                    $oPage = new Page($row['ID_PAGE'], $mode);
                    $oPage->setFields($row);

                    return $oPage;
                }

                return false;
            }

            return false;
        } elseif (is_null($this->_aSpecialePage)) {
            $sql = 'select p.*, s.MOD_CODE
                    from ' . CMS::$mode . 'PAGE p
                    inner join DD_PAGESPECIALE s on p.PGS_CODE=s.PGS_CODE
                    where p.SIT_CODE=' . $this->dbh->quote($this->getID());
            $this->_aSpecialePage = array();
            foreach ($this->dbh->query($sql) as $row) {
                $module = new Module($row['MOD_CODE']);
                if ($this->hasModule($module)) {
                    unset($row['MOD_CODE']);
                    $oPage = new Page($row['ID_PAGE'], CMS::$mode);
                    $oPage->setFields($row);
                    $this->_aSpecialePage[$row['PGS_CODE']] = $oPage;
                }
            }
        }
        if (isset ($this->_aSpecialePage[$PGS_CODE])) {
            return $this->_aSpecialePage[$PGS_CODE];
        } else {
            return false;
        }
    }

    /**
     * Retourne la page spéciale chargée (load) correspondante au template en paramètre si elle existe ou faux sinon
     * @param $TPL_CODE le code du template
     * @param $mode le mode de la page (si renseigné la page n'est pas mise en cache, si non on utilise le mode courant)
     * @return mixed un objet Page ou false
     */
    public function getSpecialePageByTemplate($TPL_CODE, $mode = null)
    {
        $sql = "select PGS_CODE from DD_TEMPLATE where TPL_CODE=" . $this->dbh->quote($TPL_CODE);
        $PGS_CODE = $this->dbh->query($sql)->fetchColumn();
        if (!empty($PGS_CODE)) {
            return $this->getSpecialePage($PGS_CODE, $mode);
        } else {
            return false;
        }
    }

    /**
     * Sauvegarde l'identifiant de la page sécurisée intialement appelée
     *
     * @since  5.6
     * @param  int  $idft Identifiant de la page sécurisée
     * @return Site
     */
    public function setSecuredID($idft)
    {
        $this->_securedID = intval($idft);

        return $this;
    }

    /**
     * Retourne l'identifiant de la page sécurisée (utilisation dans les TPL LOGIN)
     *
     * @since  5.6
     * @return int identifiant de la page sécurisée
     */
    public function getSecuredID()
    {
        return $this->_securedID;
    }

    /**
     * Retourne le path du logo, a partir de la racine du serveur web, false si aucun fichier n'est défini ou existant
     *
     * @return string Une chaine de caractère si le fichier existe, sinon false
     */
    public function getLogoSRC()
    {
        if (!$this->exist() || $this->getField('SIT_LOGO') == '') {
            return false;
        }

        return UPLOAD_IMAGE . $this->getField('SIT_LOGO');
    }


    /**
     * Retourne le path du favicon, a partir de la racine du serveur web,
     * false si aucun fichier n'est défini ou existant
     *
     * @return string Une chaine de caractère si le fichier existe, sinon false
     */
    public function getFaviconSRC()
    {
        if ($this->exist() && $this->getField('SIT_FAVICON') != '' && is_file(UPLOAD_IMAGE_PHYSIQUE . $this->getField('SIT_FAVICON'))) {
            return UPLOAD_IMAGE . $this->getField('SIT_FAVICON');
        }

        return false;
    }

    /**
     * Créé un fichier générique
     *
     * @todo   Trouver un meilleur endroit pour stocker les fchiers génériques. (Actuellement uploads/Documents)
     * @param  string $nomFic     Nom du fichier à créer
     * @param  string $contenuFic Contenu du fichier
     * @return Site
     */
    public function creerFichierGenerique($nomFic, $contenuFic)
    {
        $fd = fopen(UPLOAD_DOCUMENT_PHYSIQUE . $this->getID() . '_' . $nomFic, 'wb');
        fwrite($fd, $contenuFic);
        fclose($fd);

        return $this;
    }

    /**
     * Récupère les fichiers génériques du site courrant
     *
     * @param  bool  $forceRobots Si $forceRobots = false alors on n'inclut le robots.txt
     *                            que s'il existe (permet de cacher car contient des élément
     *                            du CMS et pas interressant pour le contributeur)
     * @return array Tableau avec le nom du fichier en clé et son contenu en valeur
     */
    public function getFichiersGeneriques($forceRobots = true)
    {
        $aFicGen = array();
        $aGlob = glob(UPLOAD_DOCUMENT_PHYSIQUE . $this->getID() . '_*');
        if (is_array($aGlob)) {
            foreach ($aGlob as $filename) {
                $fd = fopen($filename,'rb');
                $fileNameFG = preg_replace('/^.*' . $this->getID() . '_(.*)$/', '$1', $filename);
                if (filesize($filename) > 0) {
                    $aFicGen[$fileNameFG] = fread($fd, filesize($filename));
                } else {
                    $aFicGen[$fileNameFG] = '';
                }
            }
        }
        //on inclu le robots.txt si $forceRobots et pas déja inclu
        if ($forceRobots && !in_array('robots.txt',array_keys($aFicGen))) {
            $aFicGen['robots.txt'] = '';
        }

        return $aFicGen;
    }

    /**
     * Supprime tous les fichiers generique du site
     *
     * @return void
     */
    public function deleteFichiersGenerique()
    {
        foreach (glob(UPLOAD_DOCUMENT_PHYSIQUE.$this->getID().'_*') as $filename) {
            unlink($filename);
        }
    }

    /**
     * Retourne un tableau d'objet Site ordonnancé sur le libellé avec le SIT_CODE en clé
     * Il s'agit des sites vers lesquels le partage est activé pour le site courant
     *
     * @since  5.6
     * @return array
     */
    public function getSharedSites()
    {
        if (is_null($this->_sharedSites)) {
            $this->_sharedSites = array();
            $sql = "select DD_SITE.SIT_CODE, DD_SITE.* from SITE_PARTAGE
                inner join DD_SITE on SITE_PARTAGE.SIT_CODE_TO=DD_SITE.SIT_CODE
                where SIT_CODE_FROM=" . $this->dbh->quote($this->getID()) . "
                order by SIT_LIBELLE";
            foreach ($this->dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $_oSite = new Site($row['SIT_CODE']);
                $_oSite->setFields($row);
                $this->_sharedSites[$row['SIT_CODE']] = $_oSite;
            }
        }

        return $this->_sharedSites;
    }

    /**
     * Retourne un tableau d'objet Site ordonnancé sur le libellé avec le SIT_CODE en clé
     * Il s'agit des sites qui initie le partage vers le site courant
     *
     * @since  5.6
     * @return array
     */
    public function getRevertSharedSites()
    {
        if (is_null($this->_revertSharedSites)) {
            $this->_revertSharedSites = array();
            $sql = "select DD_SITE.SIT_CODE, DD_SITE.* from SITE_PARTAGE
                inner join DD_SITE on SITE_PARTAGE.SIT_CODE_FROM=DD_SITE.SIT_CODE
                where SIT_CODE_TO=" . $this->dbh->quote($this->getID()) . "
                order by SIT_LIBELLE";
            foreach ($this->dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $_oSite = new Site($row['SIT_CODE']);
                $_oSite->setFields($row);
                $this->_revertSharedSites[$row['SIT_CODE']] = $_oSite;
            }
        }

        return $this->_revertSharedSites;
    }

    /**
     * Charge tous modules chargés ou pas
     *
     * @since  5.6.1
     * @return Site
     */
    private function _loadAllModules()
    {
        if (self::$_allModules === null) {

            $sql = 'select *
                    from DD_MODULE
                    order by MOD_POIDS';

            self::$_allModules = array();

            foreach ($this->dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $module = new Module($row['MOD_CODE']);
                $module->setFields($row);
                self::$_allModules[$module->getID()] = $module;
            }
        }

        return $this;
    }

    /**
     * Retourne tous les modules chargés sur le site
     *
     * @return array
     */
    public function getModules()
    {
        $this->loadModules();

        return $this->_loadedModules;
    }

    /**
     * Charge les modules activés sur pour ce site
     *
     * @since  5.6
     * @return Site
     */
    public function loadModules()
    {
        if (is_null($this->_loadedModules)) {

            require_once CLASS_DIR . 'class.ModuleHandler.php';

            $this->_loadedModules = array();

            $moduleHandler = new ModuleHandler($this);

            //Récupération des modules affectables [par gabarit du site]
            $sql = "select distinct DD_MODULE.MOD_CODE, 1 from DD_MODULE
                left join DD_MODULE_GABARIT on (DD_MODULE.MOD_CODE = DD_MODULE_GABARIT.MOD_CODE)
                where (ID_MODULE_GABARIT IS NULL or GAB_CODE = " . $this->dbh->quote($this->getField('GAB_CODE')) . ")";
            $aModule = $this->dbh->query($sql)->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_COLUMN|PDO::FETCH_UNIQUE);

            $sql = 'select DD_MODULE.*
                    from SITE_MODULE
                    inner join DD_MODULE using(MOD_CODE)
                    where SIT_CODE=' . $this->dbh->quote($this->getID()) . '
                    order by MOD_POIDS';
            foreach ($this->dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
                if (!isset($aModule[$row['MOD_CODE']])) {
                    //ce module n'est pas globale ni affecte a ce gabarit
                    //ce module ne peut etre associe a ce site/gabarit, on enleve l'association et desactive le module
                    $moduleDesactive = new Module($row['MOD_CODE']);
                    if ($moduleHandler->disable($moduleDesactive)) {
                        $this->detachModule($moduleDesactive);
                    }
                } else {
                    $module = new Module($row['MOD_CODE']);
                    $module->setFields($row);
                    $this->attachModule($module);
                }
            }
        }

        return $this;
    }

    /**
     * Ajoute un module à la liste des modules disponibles dans le site
     *
     * @since  5.6
     * @param  Module $module
     * @return Site
     */
    public function attachModule(Module $module)
    {
        if (!$this->hasModule($module)) {
            $this->_loadedModules[$module->getID()] = $module;
        }

        return $this;
    }

    /**
     * Supprime un module de la liste des modules disponible pour un site
     *
     * @since  5.6
     * @param  Module $module
     * @return Site
     */
    public function detachModule(Module $module)
    {
        if ($this->hasModule($module)) {
            unset($this->_loadedModules[$module->getID()]);
        }

        return $this;
    }

    /**
     * Verifie l'existance d'un module dans la liste des modules disponibles pour le site
     *
     * @since  5.6
     * @param  Module $module
     * @return bool
     */
    public function hasModule(Module $module)
    {
        $this->loadModules();

        return (bool) array_key_exists($module->getID(), $this->_loadedModules);
    }

    /**
     * Active un ensemble de module
     *
     * @since  5.6
     * @param  array $aMOD_CODE
     * @return bool  True si ok, un tableau de code des modules qui n'ont pas pu être desactivés
     */
    public function activeModules($aMOD_CODE = null)
    {

        require_once CLASS_DIR . 'class.ModuleHandler.php';

        $moduleHandler = new ModuleHandler($this);
        $this->loadModules();

        if (is_null($aMOD_CODE)) {
            $aMOD_CODE = array();
        }

        //on ajoute les modules obligatoires
        $sql = "select MOD_CODE from DD_MODULE where MOD_OBLIGATOIRE=1";
        $aMOD_CODE = array_merge($aMOD_CODE, $this->dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN));

        //on met les codes en clés
        $aMOD_CODE = array_flip($aMOD_CODE);

        //pour stocker les modules non desactivables
        $aMOD_CODE_KO = array();

        //on va chercher tous les anciens modules
        foreach ($this->_loadedModules as $MOD_CODE => $oModule) {
            if (isset($aMOD_CODE[$MOD_CODE])) {
                $sql = "select count(DD_MODULE.MOD_CODE) from DD_MODULE
                left join DD_MODULE_GABARIT on (DD_MODULE.MOD_CODE = DD_MODULE_GABARIT.MOD_CODE)
                where (ID_MODULE_GABARIT IS NULL or GAB_CODE = " . $this->dbh->quote($this->getField('GAB_CODE')) . ")
                and DD_MODULE.MOD_CODE = " . $this->dbh->quote($MOD_CODE);
                $nb = $this->dbh->query($sql)->fetchColumn();
                //ce module n'existe pas pour le gabarit actuel
                if ($nb < 1) {
                    if ($moduleHandler->disable($oModule)) {
                        $this->detachModule($oModule);
                    } else {
                        $aMOD_CODE_KO[] = $MOD_CODE;
                    }
                } else {
                    //ce module existe tjs, on le supprime du parametre car inutile de le réinsérer
                    unset($aMOD_CODE[$MOD_CODE]);
                }

            } else {
                //peut-on desactiver le module
                if ($moduleHandler->disable($oModule)) {
                    $this->detachModule($oModule);
                } else {
                    $aMOD_CODE_KO[] = $MOD_CODE;
                }

            }
        }

        // on insère les nouveaux
        foreach ($aMOD_CODE as $MOD_CODE => $null) {
            /* Maj #9650 */
            //verfication si ce module est affectable [par gabarit du site]
            $sql = "select count(DD_MODULE.MOD_CODE) from DD_MODULE
            left join DD_MODULE_GABARIT on (DD_MODULE.MOD_CODE = DD_MODULE_GABARIT.MOD_CODE)
            where (ID_MODULE_GABARIT IS NULL or GAB_CODE = " . $this->dbh->quote($this->getField('GAB_CODE')) . ")
            and DD_MODULE.MOD_CODE = " . $this->dbh->quote($MOD_CODE);
            $nb = $this->dbh->query($sql)->fetchColumn();
            if ($nb < 1) {
                //ce module n'existe pas pour le gabarit actuel
                //$aMOD_CODE_KO[] = $MOD_CODE;
            } else {
                $oModule = new Module($MOD_CODE);
                $moduleHandler->enable($oModule);
                $this->attachModule($oModule);
            }
        }

        return (count($aMOD_CODE_KO) == 0) ? true : $aMOD_CODE_KO;
    }

    /**
     * Partage un site avec d'autres
     *
     * @since  5.6
     * @todo   Enrichir le fonctionnement d'un non-partage (ie : supprimer les accès, vérifier la sécurité, etc ...)
     * @return bool
     */
    public function partageSites($aSIT_CODE = null)
    {
        if (is_null($aSIT_CODE)) {
            $aSIT_CODE = array();
        }

        //on met les codes en clés
        $aSIT_CODE = array_flip($aSIT_CODE);

        //on va chercher tous les anciens sites partagés
        foreach ($this->getSharedSites() as $SIT_CODE_TO=>$null) {
            if (isset($aSIT_CODE[$SIT_CODE_TO])) {
                //ce site existe tjs, on le supprime du parametre car inutile de le réinsérer
                unset($aSIT_CODE[$SIT_CODE_TO]);
            } else {
                //on supprime le partage
                $sql = "delete from SITE_PARTAGE where SIT_CODE_TO=" . $this->dbh->quote($SIT_CODE_TO) . " and SIT_CODE_FROM=" . $this->dbh->quote($this->getID());
                $this->dbh->exec($sql);
            }
        }

        // on insère les nouveaux
        foreach ($aSIT_CODE as $SIT_CODE_TO => $null) {
            //on insère le partage
            $sql = "insert into SITE_PARTAGE (
                SIT_CODE_TO,
                SIT_CODE_FROM
                ) values (" .
            $this->dbh->quote($SIT_CODE_TO) . "," .
            $this->dbh->quote($this->getID()) . "
                )";
            $this->dbh->exec($sql);
        }

        return true;
    }

/**
     * Retourne le tableau des extensions autorisées à être uploadées pour les objets webotheque de type $WBT_CODE
     *
     * @static
     * @access    public
     * @param  string $WBT_CODE Code Webotheque. Si on est dans le contexte d'un objet, on récupére le code de l'objet en cours
     * @return array
     */
    public function getExtension($TYPE)
    {
        $aReturn = array();
        $aFields = array('SIT_EXT_IMAGE','SIT_EXT_FLASH','SIT_EXT_DOC','SIT_EXT_VIDEO','SIT_EXT_MUSIC');
        if ($TYPE == 'SIT_EXT_ALL') {
            foreach ($aFields as $field) {
                $txtExtension = $this->getField($field);
                $aTmp =  explode("\n", $txtExtension);
                foreach ($aTmp as $anExt) {
                    if (trim($anExt) != '') {
                        $aReturn[] = trim($anExt);
                    }
                }
            }
            $aReturn = array_unique($aReturn);
        } elseif (in_array($TYPE, $aFields)) {
            $txtExtension = $this->getField($TYPE);
            $aTmp =  explode("\n", $txtExtension);
            foreach ($aTmp as $anExt) {
                if (trim($anExt) != '') {
                    $aReturn[] = trim($anExt);
                }
            }
        }

        return $aReturn;
    }


    public function deleteHistorique() {

        $sql = "select * from HISTORIQUE_UTILISATEUR
                 left join UTILISATEUR using(ID_UTILISATEUR)
                 where HISTORIQUE_UTILISATEUR.SIT_CODE = " . $this->dbh->quote($this->getID());

        // Suppression des références aux ID_HISTORIQUE_UTILISATEUR du site à supprimer
        // (normalement seule la création d'un site enregistre un ID_HISTORIQUE_UTILISATEUR pour un site différent du site lui-même)
        // @TODO : ==> A voir si cette section est à conserver (hors la suppression de HISTORIQUE_ADMIN qui doit l'être)
        if ($rows = $this->dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC)) {
            foreach ($rows as $row) {echo $row['ID_HISTORIQUE_UTILISATEUR'] . '<br>';
                $this->dbh->exec("delete from HISTORIQUE_EXTERNE where ID_HISTORIQUE_UTILISATEUR = " .$row['ID_HISTORIQUE_UTILISATEUR']);
                $this->dbh->exec("delete from HISTORIQUE_WEBOTHEQUE where ID_HISTORIQUE_UTILISATEUR = " .$row['ID_HISTORIQUE_UTILISATEUR']);
                $this->dbh->exec("delete from HISTORIQUE_PAGE where ID_HISTORIQUE_UTILISATEUR = " .$row['ID_HISTORIQUE_UTILISATEUR']);
                $this->dbh->exec("delete from HISTORIQUE_FORMULAIRE where ID_HISTORIQUE_UTILISATEUR = " .$row['ID_HISTORIQUE_UTILISATEUR']);
                $this->dbh->exec('delete from HISTORIQUE_ADMIN where ID_HISTORIQUE_UTILISATEUR = ' . $row['ID_HISTORIQUE_UTILISATEUR']);
                $this->dbh->exec("delete from HISTORIQUE_UTILISATEUR where ID_HISTORIQUE_UTILISATEUR = " . $row['ID_HISTORIQUE_UTILISATEUR']);
            }
        }
        $this->dbh->exec("delete from HISTORIQUE_EXTERNE where SIT_CODE = " . $this->dbh->quote($this->getID()));
        $this->dbh->exec("delete from HISTORIQUE_WEBOTHEQUE where SIT_CODE = " . $this->dbh->quote($this->getID()));
        $this->dbh->exec("delete from HISTORIQUE_PAGE where SIT_CODE = " . $this->dbh->quote($this->getID()));
        $this->dbh->exec("delete from HISTORIQUE_FORMULAIRE where SIT_CODE = " . $this->dbh->quote($this->getID()));
        $this->dbh->exec("delete from HISTORIQUE_ADMIN where SIT_CODE = " . $this->dbh->quote($this->getID()));
        $this->dbh->exec("delete from HISTORIQUE_PERIODE where SIT_CODE = " . $this->dbh->quote($this->getID()));
    }

      /**
     *
     * @return bool
     */
    public function uploadGAKeyFile()
    {
        require_once CLASS_DIR . 'class.File_management.php';
        $ok = true;
        $fm = new File_management('DD_SITE', 'SIT_CODE', $this->getID(), UPLOAD_EXTERNE_PHYSIQUE);
        $fm->setExtensions('.p12');
        if (!$fm->upload('SIT_GA_KEYFILE')) {
            if ($fm->error == 'FM_EXTENSION') {
                $ok = false;
                setMSG('Extension incorrecte (' . implode(', ', $fm->getExtensions()) . ')', 'ERROR');
            } elseif (!empty ($fm->error)) {
                $ok = false;
                setMSG('Upload : ' . $fm->error, 'ERROR');
            }
            $fm->checkDelete('SIT_GA_KEYFILE');
        }
        return $ok;
    }

    /**
     * Retourne le path de la clé GA, a partir de la racine du serveur web,
     * false si aucun fichier n'est défini ou existant
     *
     * @return string Une chaine de caractère si le fichier existe, sinon false
     */
    public function getGAKeyFile()
    {
        if (!$this->exist() || $this->getField('SIT_GA_KEYFILE') == '') {
            return false;
        }
        return UPLOAD_EXTERNE . $this->getField('SIT_GA_KEYFILE');
    }

    public function updateTraficUser()
    {
        $time = time();
        // Insertion ou remplacement de la connexion de l'utilisateur
        $stmt = $this->dbh->prepare("replace into ONLINEUSER (
            OLU_IP,
            SIT_CODE,
            OLU_DATELASTCONNEXION
            ) values (
           :OLU_IP,
           :SIT_CODE,
           :OLU_DATELASTCONNEXION
           );");
        $stmt->bindValue(':OLU_IP', strval($_SERVER['HTTP_X_FORWARDED_FOR']), PDO::PARAM_STR);//strval astuce pour éviter null
        $stmt->bindValue(':SIT_CODE', $this->getID(), PDO::PARAM_STR);
        $stmt->bindValue(':OLU_DATELASTCONNEXION', $time, PDO::PARAM_INT);
        $stmt->execute();

        if ($time%5 == 0) {
            // On supprime les connexions datant de plus d'une minute
            $stmt = $this->dbh->prepare('delete from ONLINEUSER where
                OLU_DATELASTCONNEXION <= :OLU_DATELASTCONNEXION
                and SIT_CODE = :SIT_CODE;');
            $stmt->bindValue(':SIT_CODE', $this->getID(), PDO::PARAM_STR);
            $stmt->bindValue(':OLU_DATELASTCONNEXION', strtotime("-1 minutes"), PDO::PARAM_INT);
            $stmt->execute();

            // Si le nombre de connexion en cours est le max on insère
            $nbConnecte = self::getNbUserOnline();
            if ($nbConnecte > $this->getField('SIT_MAXONLINEUSER')) {
                $stmt = $this->dbh->prepare('update DD_SITE set
                    SIT_MAXONLINEUSER = :SIT_MAXONLINEUSER,
                    SIT_DATEMAXONLINEUSER = :SIT_DATEMAXONLINEUSER
                    where SIT_CODE = :SIT_CODE;');
                $stmt->bindValue(':SIT_MAXONLINEUSER', $nbConnecte, PDO::PARAM_INT);
                $stmt->bindValue(':SIT_DATEMAXONLINEUSER', $time, PDO::PARAM_INT);
                $stmt->bindValue(':SIT_CODE', $this->getID(), PDO::PARAM_STR);
                $stmt->execute();
            }
        }
    }

    /**
     * Retourne le nombre de personne sur le site actuellement
     *
     * @return number
     */
    public function getNbUserOnline()
    {
        $sql = "select count(*) from ONLINEUSER
            where SIT_CODE=" . $this->dbh->quote($this->getID());
        return intval($this->dbh->query($sql)->fetch(PDO::FETCH_COLUMN));
    }

    /**
     * Remet à zéro le maximum de visite sur le site
     */
    public function resetMaxVist()
    {
        $sql = 'update DD_SITE
                set SIT_MAXONLINEUSER = 0,
                SIT_DATEMAXONLINEUSER = 0
                where SIT_CODE = ' . $this->dbh->quote($this->getID());
        $this->dbh->exec($sql);
    }
}
