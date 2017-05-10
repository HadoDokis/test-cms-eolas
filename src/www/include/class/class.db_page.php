<?php
require_once CLASS_DIR . 'class.db_ajax.php';
require_once CLASS_DIR . 'class.i_commentaire.php';

class Page extends Ajax implements i_commentaire
{

    protected $mode;

    protected $_hasLeftColumn = true;

    protected $_hasRightColumn = true;

    protected $_locked = null;

    protected $_deletable = null;

    protected $_aChildren = null;

    protected $_aChildrenForMenu = null;

    protected $_aChildrenID = null;

    protected $_aParent = null;

    protected $_aParentID = null;

    protected $_aParagraphe = null;

    protected $_oPageRedirection = null;

    protected $_oPageCanonical = null;

    protected $_oWeboAccroche = null;

    protected $_oWeboRedirection = null;

    protected $_statut = null;

    protected $_aTag = null;

    protected $_isRevision = false;

    protected $_aRevision = null;

    protected static $_initialStatut = null;

    protected static $_aSecuredID = null;

    protected static $_aForbiddenID = null;

    protected static $_writeCache = true;

    public function __construct($idtf, $mode = 'OFF_')
    {
        parent::__construct($mode . 'PAGE', 'ID_PAGE', $idtf);
        $this->mode = $mode;
    }

    public static function getModuleCode()
    {
        return 'MOD_CORE';
    }

    public function load()
    {
        $sql = "select * from " . $this->mode . "PAGE where ID_PAGE=" . $this->getID();
        if ($row = $this->dbh->query($sql)->fetch(PDO::FETCH_ASSOC)) {
            $this->setFields($row);
        } else {
            $this->_idtf = - 1;
            $this->setFields(array());
        }
    }

    /**
     * Retourne le mode de la page [ON_ | OFF_]
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * Fixe le colonnage de la page courante
     *
     * @param  bool $left
     *                     possède une colonne à gauche
     * @param  bool $right
     *                     possède une colonne à droite
     * @return Page
     */
    public function setColumns($left, $right)
    {
        $this->_hasLeftColumn = $left;
        $this->_hasRightColumn = $right;

        return $this;
    }

    /**
     * Indique si la page possède une colonne à gauche
     */
    public function hasLeftColumn()
    {
        return ! $this->getField('PAG_MASQUERGAUCHE') && $this->_hasLeftColumn;
    }

    /**
     * Indique si la page possède une colonne à droite
     */
    public function hasRightColumn()
    {
        return ! $this->getField('PAG_MASQUERDROITE') && $this->_hasRightColumn;
    }

    /**
     * Ordonnance les parents du plus ancien au plus récent
     *
     * @return array un tableau d'objet Page
     */
    public function getParents()
    {
        if (is_null($this->_aParent)) {
            $_a = $_b = array();
            $_id = $this->getField('PAG_IDPERE');
            while (is_numeric($_id)) {
                $_oPage = new Page($_id, $this->mode);
                if (! $_oPage->exist()) {
                    break;
                }
                $_a[] = $_oPage;
                $_b[] = $_id;
                $_id = $_oPage->getField('PAG_IDPERE');
            }
            $this->_aParent = array_reverse($_a);
            $this->_aParentID = array_reverse($_b);
        }

        return $this->_aParent;
    }

    /**
     * Ordonnance les parents du plus ancien au plus récent
     *
     * @param  bool  $selfInclude
     *                            indique si oui ou non l'identifiant de la page courante doit etre inclus
     * @return array un tableau d'identifiant
     */
    public function getParentsID($selfInclude = false)
    {
        if (is_null($this->_aParentID)) {
            $_a = $_b = array();
            $_id = $this->getField('PAG_IDPERE');
            while (is_numeric($_id)) {
                $_oPage = new Page($_id, $this->mode);
                if (! $_oPage->exist()) {
                    break;
                }
                $_a[] = $_oPage;
                $_b[] = $_id;
                $_id = $_oPage->getField('PAG_IDPERE');
            }
            $this->_aParent = array_reverse($_a);
            $this->_aParentID = array_reverse($_b);
        }

        return ($selfInclude) ? array_merge($this->_aParentID, array(
            $this->getID()
        )) : $this->_aParentID;
    }

    /**
     * Retourne le niveau d'arborescence de la page (la page d'accueil retourne 0)
     *
     * @return int la position
     */
    public function getLevel()
    {
        return sizeof($this->getParentsID());
    }

    /**
     * Retourne tous les enfants triés par poids
     *
     * @param  string $filtre
     *                        une chaine sql commençant par ' and' et protégée des caractères à risque
     * @return array  un tableau d'objet Page
     */
    public function getChildren($filtre = '')
    {
        if (is_null($this->_aChildren) || $filtre != '') {
            $_a = array();
            $sql = "select * from " . $this->mode . "PAGE where PAG_IDPERE=" . $this->getID() . $filtre . " order by PAG_POIDS";
            foreach ($this->dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $rowTemp) {
                $_oPage = new Page($rowTemp['ID_PAGE'], $this->mode);
                $_oPage->setFields($rowTemp);
                $_a[] = $_oPage;
            }
            if ($filtre != '') {
                return $_a;
            }
            $this->_aChildren = $_a;
        }

        return $this->_aChildren;
    }

    /**
     * Retourne tous les enfants visibles dans le menu et non sécurisés, triés par poids
     *
     * @return array un tableau d'objet Page
     */
    public function getChildrenForMenu()
    {
        if (is_null($this->_aChildrenForMenu)) {
            $filtreEnfant = " and PAG_VISIBLE_MENU=1";
            if (count(self::getForbiddenID($this->mode)) > 0) {
                $filtreEnfant .= " and ID_PAGE not in (" . implode(',', self::getForbiddenID($this->mode)) . ")";
            }
            $this->_aChildrenForMenu = $this->getChildren($filtreEnfant);
        }

        return $this->_aChildrenForMenu;
    }

    /**
     * Retourne tous les identifants d'enfants dans un ordre non déterminé
     *
     * @param  string $filtre
     *                        une chaine sql commençant par ' and' et protégée des caractères à risque
     * @return array  un tableau d'identifiant
     */
    public function getChildrenID($filtre = '')
    {
        if (is_null($this->_aChildrenID)) {
            $this->_aChildrenID = self::_getChildrenID(array(
                $this->getID()
            ), $this->mode);
            array_shift($this->_aChildrenID);
        }
        if ($filtre != '' && sizeof($this->_aChildrenID) > 0) {
            $sql = "select ID_PAGE from " . $this->mode . "PAGE where ID_PAGE in (" . implode(',', $this->_aChildrenID) . ")" . $filtre;

            return $this->dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN);
        }

        return $this->_aChildrenID;
    }

    private static function _getChildrenID($aID, $mode)
    {
        if (sizeof($aID) == 0) {
            return array();
        }
        $dbh = DB::getInstance();
        $sql = "select ID_PAGE from " . $mode . "PAGE where PAG_IDPERE in (" . implode(',', $aID) . ")";

        return array_merge($aID, self::_getChildrenID($dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN), $mode));
    }

    /**
     * Retourne la page de redirection déjà chargée (load) si elle existe ou faux sinon
     *
     * @return mixed un objet Page ou false
     */
    public function getInternalRedirection()
    {
        if (is_null($this->_oPageRedirection)) {
            if ($this->getField('ID_PAGE_REDIRECT') == '' || $this->getField('ID_PAGE_REDIRECT') == $this->getID()) {
                $this->_oPageRedirection = false;
            } else {
                $oPageTemp = new Page($this->getField('ID_PAGE_REDIRECT'), $this->mode);
                $this->_oPageRedirection = $oPageTemp->exist() ? $oPageTemp : false;
            }
        }

        return $this->_oPageRedirection;
    }

    /**
     * Retourne le lien externe si il existe ou faux sinon
     *
     * @return mixed un objet Webo_LIENEXTERNE ou false
     */
    public function getExternalRedirection()
    {
        if (is_null($this->_oWeboRedirection)) {
            if ($this->getField('ID_WEBOTHEQUE_LIENEXTERNE') == '') {
                $this->_oWeboRedirection = false;
            } else {
                require_once CLASS_DIR . 'class.db_webotheque.php';
                $oWeboTemp = new Webo_LIENEXTERNE($this->getField('ID_WEBOTHEQUE_LIENEXTERNE'));
                $this->_oWeboRedirection = $oWeboTemp->exist() ? $oWeboTemp : false;
            }
        }

        return $this->_oWeboRedirection;
    }

    /**
     * Retourne la page canonique si elle existe ou faux sinon
     *
     * @return mixed un objet Page ou false
     */
    public function getCanonicalPage()
    {
        if (is_null($this->_oPageCanonical)) {
            if ($this->getField('ID_PAGE_CANONICAL') == '') {
                $this->_oPageCanonical = false;
            } else {
                $oPage = new Page($this->getField('ID_PAGE_CANONICAL'), CMS::$mode);
                $this->_oPageCanonical = $oPage->exist() ? $oPage : false;
            }
        }
        return $this->_oPageCanonical;
    }

    /**
     * Retourne l'image d'accroche déjà chargée (load) si elle existe ou faux sinon
     *
     * @return mixed un objet Webo_IMAGE ou false
     */
    public function getAccroche($recursif = false)
    {
        if (is_null($this->_oWeboAccroche)) {
            $this->_oWeboAccroche = false;
            if ($this->getField('ID_WEBOTHEQUE_IMAGE') != '') {
                require_once CLASS_DIR . 'class.db_webotheque.php';
                // pour avoir LIA_TEXT
                $row = reset($this->getLiaisonWebotheque('WBT_IMAGE', $ext));
                $oWebo = new Webo_IMAGE($row['ID_WEBOTHEQUE']);
                $oWebo->setFields($row);
                $this->_oWeboAccroche = $oWebo;
            }
            if (! $this->_oWeboAccroche && $recursif) {
                $aParent = $this->getParents();
                if ($oPageParent = array_pop($aParent)) {
                    $this->_oWeboAccroche = $oPageParent->getAccroche(true);
                }
            }
        }
        return $this->_oWeboAccroche;
    }

    public function checkCache()
    {
        if (CMS::getCurrentSite()->getField('SIT_PAGE_CACHE') && $this->getField('PAG_CACHE') && empty($_POST) && (empty($_GET) || (sizeof($_GET) == 1 && is_numeric($_GET['idtf'])))) {
            $cacheFile = UPLOAD_CACHE_PHYSIQUE . $this->getField('SIT_CODE') . '-' . $this->getID() . '-' . intval(CMS::isMobile()) . '.htm';

            // Si on a un fichier cache pour cette page on va vérifier qu'il est valable
            if (file_exists($cacheFile)) {
                // Si le cache est trop vieux (3h) on charge la page normalement
                $bLoadFromCache = (filemtime($cacheFile) > time() - (3600 * 3));
            } else {
                $bLoadFromCache = false;
            }

            if ($bLoadFromCache) {
                ob_clean();
                readfile($cacheFile);
                exit();
            }
            register_shutdown_function(array(
                'CMS',
                'loadHeader'
            )); // à faire avec le write cache pour mettre à jour le "content"
            register_shutdown_function(array(
                $this,
                'writeCache'
            ), $cacheFile);
        } else {
            register_shutdown_function(array(
                'CMS',
                'loadHeader'
            ));
        }
    }

    /**
     * Ecris dans le fichier cache de la page
     *
     * @param string $cacheFile
     */
    public function writeCache($cacheFile)
    {
        if (self::$_writeCache) {
            file_put_contents($cacheFile, ob_get_contents());
        }
    }

    public static function setNoCache()
    {
        self::$_writeCache = false;
    }

    public static function clearCache($SIT_CODE = false)
    {
        if ($SIT_CODE) {
            $_SIT_CODE = $SIT_CODE;
        } elseif (CMS::getCurrentSite()) {
            $_SIT_CODE = CMS::getCurrentSite()->getID();
        } else {
            return false;
        }
        foreach (glob(UPLOAD_CACHE_PHYSIQUE . $_SIT_CODE . "-*.htm") as $filename) {
            unlink($filename);
        }
        return true;
    }

    public static function clearAllCache()
    {
        foreach (glob(UPLOAD_CACHE_PHYSIQUE . "*.htm") as $filename) {
            unlink($filename);
        }
        return true;
    }

    /**
     * Teste si l'utilisateur accède à cette page
     *
     * @param  bool $strict
     *                      précise si un non-accès renvoi faux ou bien deconnecte l'utilisateur
     * @return bool
     */
    public function checkAuthorized($strict = true)
    {
        $oUtilisateur = Utilisateur::getConnected();
        if ($oUtilisateur) {
            foreach ($oUtilisateur->getProfils() as $PRO_CODE => $tabPage) {
                if (is_array($tabPage) && in_array($this->getID(), $tabPage)) {
                    return true;
                }
            }
        }
        if ($strict) {
            header('Location:' . SERVER_ROOT . 'cms/cms_pageArbo.php');
            exit();
        }

        return false;
    }

    /**
     * Retourne tous les enfants pour l'affichage de l'arbo en fonction des droits de l'utilisateur
     *
     * @access public
     *
     * @return array un tableau d'objet Page
     */
    public function getChildrenForArbo()
    {
        $filtreEnfant = ' and 1=1 ';
        $oUtilisateur = Utilisateur::getConnected();
        if ($oUtilisateur && ! $oUtilisateur->isRoot()) {
            if ((sizeof(self::getForbiddenID($this->mode)) > 0)) {
                $filtreEnfant .= " and ID_PAGE not in (" . implode(',', self::getForbiddenID($this->mode)) . ")";
            }
        }

        return $this->getChildren($filtreEnfant);
    }

    /**
     * Teste si la page est une page spéciale
     *
     * @param  string $PGS_CODE
     *                          le code recherché (vide si page spéciale quelconque)
     * @return bool
     */
    public function isSpecialePage($PGS_CODE = '')
    {
        if ($this->exist()) {
            if ($PGS_CODE == '') {
                return $this->getField('PGS_CODE') != '';
            } else {
                return $this->getField('PGS_CODE') == $PGS_CODE;
            }
        }

        return false;
    }

    public function isLocked()
    {
        if (is_null($this->_locked)) {
            $sql = "select ID_UTILISATEURVERROU, PAG_DATEVERROU from OFF_PAGE where ID_PAGE=" . $this->getID();
            $row = $this->dbh->query($sql)->fetch(PDO::FETCH_ASSOC);
            if (($row['ID_UTILISATEURVERROU'] == '') || ($row['ID_UTILISATEURVERROU'] == Utilisateur::getConnected()->getID()) || (($row['PAG_DATEVERROU'] + ini_get('session.gc_maxlifetime')) < time())) {
                $this->_locked = false;
            } else {
                $this->_locked = true;
            }
        }

        return $this->_locked;
    }

    /**
     * Teste si la page est dans la liste des pages sécurisée
     *
     * @param $force boolean
     *            force la vérification même si on est en pseudo
     * @return bool
     */
    public function isSecured($force = false)
    {
        return in_array($this->getID(), Page::getSecuredID($this->mode, $force));
    }

    public function isForbidden()
    {
        return in_array($this->getID(), Page::getForbiddenID($this->mode));
    }

    public function isHome()
    {
        return (bool) ($this->exist() && $this->getField('PAG_IDPERE') == null);
    }

    /**
     *
     * @param $copyMode vrai
     *            si la suppression sert pour une copie (OFF -> ON)
     */
    public function delete($copyMode = false, $forseDelHome = false)
    {
        if (($this->mode == 'OFF_' && ! $this->isDeletable($forseDelHome)) || ! $this->exist()) {
            return false;
        }
        if ($this->mode == 'OFF_') {
            $this->historize('SUPPRESSION', 'PAGE');
        }

        if (! $copyMode && $this->mode == 'OFF_') {
            $this->deleteRevision();
        }
        foreach ($this->getParagraphes() as $oParagraphe) {
            $oParagraphe->delete($copyMode);
        }

        $sql = "delete from GROUPE_" . $this->mode . "PAGE where ID_PAGE=" . $this->getID();
        $this->dbh->exec($sql);

        $sql = "update " . $this->mode . "PARAGRAPHE set PAR_APARSER=1 where ID_PARAGRAPHE in
             (select ID_LIAISON from LIAISON_PAGE where LIA_CODE='" . $this->mode . "PARAGRAPHE' and ID_PAGE=" . $this->getID() . ")";
        $this->dbh->exec($sql);

        // Ajout pour mettre a parser tous les paragraphes revision avec des liaison vers cette page
        $sql = "update REVISION_PARAGRAPHE set PAR_APARSER=1 where ID_PARAGRAPHE in
             (
             select ID_PARAGRAPHE from (
             select REVISION_PARAGRAPHE.ID_PARAGRAPHE from LIAISON_PAGE
             inner join REVISION_PARAGRAPHE on
                (LIAISON_PAGE.ID_REVISION = REVISION_PARAGRAPHE.ID_REVISION
                and LIAISON_PAGE.ID_LIAISON = REVISION_PARAGRAPHE.ID_REVISIONPARAGRAPHE)
             where LIAISON_PAGE.LIA_CODE='REVISION_PARAGRAPHE'
             and LIAISON_PAGE.ID_PAGE=" . $this->getID() . "
             ) AS tmptable
             )";
        $this->dbh->exec($sql);

        if ($this->mode == 'OFF_') {
            // après le traitement des paragraphes
            $sql = "update UTILISATEUR set ID_PAGE=null where ID_PAGE=" . $this->getID();
            $this->dbh->exec($sql);
            $sql = "delete from URLALTERNATIVE where ID_PAGE=" . $this->getID();
            $this->dbh->exec($sql);
            $sql = "delete from ROLE where ID_PAGE=" . $this->getID();
            $this->dbh->exec($sql);
            $sql = "delete from LIAISON_PAGE where ID_PAGE=" . $this->getID();
            $this->dbh->exec($sql);
        }
        $sql = "delete from LIAISON_THEMATIQUE where LIA_CODE='" . $this->mode . "PAGE' and ID_LIAISON=" . $this->getID();
        $this->dbh->exec($sql);
        $sql = "delete from LIAISON_WEBOTHEQUE where LIA_CODE='" . $this->mode . "PAGE' and ID_LIAISON=" . $this->getID();
        $this->dbh->exec($sql);
        $sql = "delete from LIAISON_PAGE where LIA_CODE='" . $this->mode . "PAGE' and ID_LIAISON=" . $this->getID();
        $this->dbh->exec($sql);
        // Ajout pour supprimer les liaisons vers cette page des instances de revision des pages X
        $sql = "delete from LIAISON_PAGE where LIA_CODE='REVISION_PAGE' and ID_PAGE=" . $this->getID();
        $this->dbh->exec($sql);
        $sql = "delete from LIAISON_PAGE where LIA_CODE='REVISION_PARAGRAPHE' and ID_PAGE=" . $this->getID();
        $this->dbh->exec($sql);

        // Ajout pour bloc en savoir plus
        if (!$copyMode) {
            $sql = "delete from LIAISON_PAGE where LIA_CODE='" . $this->mode . "PARAGRAPHE' and ID_PAGE=" . $this->getID();
            $this->dbh->exec($sql);
        }

        // suppression de la page
        $sql = "delete from " . $this->mode . "PAGE where ID_PAGE=" . $this->getID();
        $this->dbh->exec($sql);

        // reordonnancement des pages adjacentes
        if ($this->mode == 'OFF_' && ! $this->isHome()) {
            $oPageParent = end($this->getParents());
            if (get_class($oPageParent) == 'Page') {
                $oPageParent->reorderChildren();
            }
        }

        return true;
    }

    public function isDeletable($forseDelHome = false)
    {
        if (is_null($this->_deletable)) {
            $this->_deletable = (! $this->isHome() || $forseDelHome);
            if ($this->_deletable && $this->mode == 'OFF_' && ! $forseDelHome) {
                $sql = "select count(ID_PAGE) from LIAISON_PAGE where LIA_TYPE!='RTE' and ID_PAGE=" . $this->getID();
                $this->_deletable = ($this->dbh->query($sql)->fetchColumn() == 0);
            }
            if ($this->_deletable) {
                $sql = "select count(ID_PAGE) from ON_PAGE where ID_PAGE=" . $this->getID();
                $this->_deletable = ($this->dbh->query($sql)->fetchColumn() == 0);
            }
            if ($this->_deletable) {
                $sql = "select count(ID_PAGE) from ON_PAGE where PAG_IDPERE=" . $this->getID();
                $this->_deletable = ($this->dbh->query($sql)->fetchColumn() == 0);
            }
            if ($this->_deletable) {
                $sql = "select count(ID_PAGE) from OFF_PAGE where PAG_IDPERE=" . $this->getID();
                $this->_deletable = ($this->dbh->query($sql)->fetchColumn() == 0);
            }
        }

        return $this->_deletable;
    }

    public function lock()
    {
        $oUtilisateur = Utilisateur::getConnected();
        if ($this->isLocked()) {
            if ($oUtilisateur->isRoot()) {
                self::unlockAll();
                $stmt = $this->dbh->prepare("update OFF_PAGE set ID_UTILISATEURVERROU=:ID_UTILISATEURVERROU, PAG_DATEVERROU=:PAG_DATEVERROU where ID_PAGE=:ID_PAGE");
                $stmt->bindValue(':ID_UTILISATEURVERROU', $oUtilisateur->getID(), PDO::PARAM_INT);
                $stmt->bindValue(':PAG_DATEVERROU', time(), PDO::PARAM_INT);
                $stmt->bindValue(':ID_PAGE', $this->getID(), PDO::PARAM_INT);
                $stmt->execute();
                $this->_locked = false;
            } else {
                header('Location:' . SERVER_ROOT . 'cms/cms_pageArbo.php');
                exit();
            }
        } else {
            self::unlockAll();
            $stmt = $this->dbh->prepare("update OFF_PAGE set ID_UTILISATEURVERROU=:ID_UTILISATEURVERROU, PAG_DATEVERROU=:PAG_DATEVERROU where ID_PAGE=:ID_PAGE");
            $stmt->bindValue(':ID_UTILISATEURVERROU', $oUtilisateur->getID(), PDO::PARAM_INT);
            $stmt->bindValue(':PAG_DATEVERROU', time(), PDO::PARAM_INT);
            $stmt->bindValue(':ID_PAGE', $this->getID(), PDO::PARAM_INT);
            $stmt->execute();
        }
    }

    /**
     * Met la page dans son statut intial ("A rédiger")
     */
    public function resetStatut()
    {
        $stmt = $this->dbh->prepare("update OFF_PAGE set
            PST_CODE=:PST_CODE
            where ID_PAGE=:ID_PAGE");
        $stmt->bindValue(':PST_CODE', self::getInitialStatut(), PDO::PARAM_STR);
        $stmt->bindValue(':ID_PAGE', $this->getID(), PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Retourne le libellé du statut courant de la page
     */
    public function getStatut()
    {
        if (is_null($this->_statut)) {
            $sql = "select PST_LIBELLE from DD_PAGESTATUT where PST_CODE=" . $this->dbh->quote($this->getField('PST_CODE'));
            $this->_statut = $this->dbh->query($sql)->fetchColumn();
        }

        return $this->_statut;
    }

    /**
     * Retourne le code du statut intial d'une page ("A rédiger")
     */
    public static function getInitialStatut()
    {
        if (is_null(self::$_initialStatut)) {
            $dbh = DB::getInstance();
            $sql = "select PST_CODE from DD_PAGESTATUT order by PST_POIDS";
            self::$_initialStatut = $dbh->query($sql)->fetchColumn();
        }

        return self::$_initialStatut;
    }

    /**
     * Efectué lors de la suppression d'une liaison webothèque
     *
     * @since 5.6
     * @see trunk/src/www/include/class/Ajax::_onPostDeleteLiaisonWebotheque()
     * @return Page
     * @throws BadMethodCallException
     */
    protected function _onPostDeleteLiaisonWebotheque()
    {
        $this->resetStatut();
    }

    /**
     * Efectué lors de la suppression d'une liaison webothèque
     *
     * @since 5.6
     * @see trunk/src/www/include/class/Ajax::_onPostSaveLiaisonWebotheque()
     * @return Page
     * @throws BadMethodCallException
     */
    protected function _onPostSaveLiaisonWebotheque()
    {
        $this->resetStatut();
    }

    /**
     * Efectué lors de la suppression d'une liaison webothèque
     *
     * @since 5.6
     * @see trunk/src/www/include/class/Ajax::_onPostDeleteLiaisonPage()
     * @return Page
     * @throws BadMethodCallException
     */
    protected function _onPostDeleteLiaisonPage()
    {
        $this->resetStatut();
    }

    /**
     * Efectué lors de l'enregistrement d'une liaison webothèque
     *
     * @since 5.6
     * @see trunk/src/www/include/class/Ajax::_onPostSaveLiaisonPage()
     * @return Page
     * @throws BadMethodCallException
     */
    protected function _onPostSaveLiaisonPage()
    {
        $this->resetStatut();
    }

    /**
     *
     * @param $oParagraphe l'objet
     *            paragraphe qui précéde l'ajout ou une valeur de la liste suivante ['PAR_LEFT' | 'PAR_RIGHT' | 'PAR_CENTRAL']
     */
    public function getParagrapheButtons($oParagraphe)
    {
        if (! CMS::$edition || $this->mode != 'OFF_') {
            return '';
        }
        $retour = '<div class="pseudo_boutons_insertion">' . gettext('Ajouter') . ' : ';

        if ($oParagraphe instanceof Paragraphe) {
            $PAR_COLONNE_SQL = $oParagraphe->getField('PAR_COLONNE');
            $param = 'idtf_prec=' . $oParagraphe->getID();
        } else {
            $PAR_COLONNE_SQL = $oParagraphe;
            $param = 'ID_PAGE=' . $this->getID() . '&amp;PAR_COLONNE=' . $oParagraphe;
        }

        $sql = "select * from DD_PARAGRAPHETYPE where PRT_COLONNE like '%@" . $PAR_COLONNE_SQL . "@%'";
        if (! CMS::getCurrentSite()->hasModule(new Module('MOD_FORMULAIRE'))) {
            $sql .= " and PRT_CODE<>'PRT_FORMULAIRE'";
        }
        if (! CMS::getCurrentSite()->hasModule(new Module('MOD_WEBOTHEQUE_WIDGET'))) {
            $sql .= " and PRT_CODE<>'PRT_WIDGET'";
        }
        $sql .= " order by PRT_POIDS";
        foreach ($this->dbh->query($sql) as $row) {
            $retour .= '<a href="PRT/' . $row['PRT_CODE'] . '.php?' . $param . '" title="' . extraireLibelle($row['PRT_LIBELLE']) . '"><img src="../images/pseudo_' . $row['PRT_CODE'] . '_add.png" alt="' . extraireLibelle($row['PRT_LIBELLE']) . '"></a>';
        }
        $retour .= '</div>';

        return $retour;
    }

    /**
     *
     * @param $PAR_COLONNE ['PAR_LEFT'
     *            | 'PAR_RIGHT' | 'PAR_CENTRAL' | '']
     * @return array un tableau d'objet Paragraphe
     */
    public function getParagraphes($PAR_COLONNE = '')
    {
        if (is_null($this->_aParagraphe)) {
            require_once CLASS_DIR . 'class.db_paragraphe.php';
            $this->_aParagraphe = array(
                'PAR_LEFT' => array(),
                'PAR_CENTRAL' => array(),
                'PAR_RIGHT' => array()
            );
            $sql = "select * from " . $this->mode . "PARAGRAPHE where ID_PAGE=" . $this->getID() . " order by PAR_COLONNE, PAR_POIDS";
            foreach ($this->dbh->query($sql, PDO::FETCH_ASSOC) as $row) {
                $Paragraphe_class = 'Paragraphe' . substr($row['PRT_CODE'], 3);
                $oParagraphe = new $Paragraphe_class($row['ID_PARAGRAPHE'], $this->mode, $this);
                $oParagraphe->setFields($row);
                $this->_aParagraphe[$row['PAR_COLONNE']][] = $oParagraphe;
            }
        }

        return ($PAR_COLONNE == '') ? array_merge($this->_aParagraphe['PAR_LEFT'], $this->_aParagraphe['PAR_CENTRAL'], $this->_aParagraphe['PAR_RIGHT']) : $this->_aParagraphe[$PAR_COLONNE];
    }

    /**
     *
     * @param $PAR_POIDS emplacement
     *            du paragraphe à ajouter
     * @param $PAR_COLONNE ['PAR_LEFT'
     *            | 'PAR_RIGHT' | 'PAR_CENTRAL']
     */
    public function insertParagraphe($PAR_POIDS, $PAR_COLONNE)
    {
        $stmt = $this->dbh->prepare("update OFF_PARAGRAPHE set PAR_POIDS=PAR_POIDS+1 where PAR_POIDS>=:PAR_POIDS and PAR_COLONNE=:PAR_COLONNE and ID_PAGE=:ID_PAGE");
        $stmt->bindValue(':PAR_POIDS', $PAR_POIDS, PDO::PARAM_INT);
        $stmt->bindValue(':PAR_COLONNE', $PAR_COLONNE, PDO::PARAM_STR);
        $stmt->bindValue(':ID_PAGE', $this->getID(), PDO::PARAM_STR);
        $stmt->execute();
    }

    /**
     * Ordonne tous les paragraphes d'une colonne donnée d'une page
     *
     * @param $PAR_COLONNE la
     *            colonne à ordonner ['PAR_LEFT' | 'PAR_RIGHT' | 'PAR_CENTRAL']
     * @return void
     */
    public function reorderParagraphes($PAR_COLONNE)
    {
        $sql = "select ID_PARAGRAPHE from " . $this->mode . "PARAGRAPHE where ID_PAGE=" . $this->getID() . " and PAR_COLONNE=" . $this->dbh->quote($PAR_COLONNE) . " order by PAR_POIDS";
        $_aParagraphe = $this->dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN);
        $stmt = $this->dbh->prepare("update " . $this->mode . "PARAGRAPHE set PAR_POIDS=:PAR_POIDS where ID_PARAGRAPHE=:ID_PARAGRAPHE");
        $PAR_POIDS = 1;
        $stmt->bindParam(':PAR_POIDS', $PAR_POIDS, PDO::PARAM_INT);
        foreach ($_aParagraphe as $ID_PARAGRAPHE) {
            $stmt->bindValue(':ID_PARAGRAPHE', $ID_PARAGRAPHE, PDO::PARAM_INT);
            $stmt->execute();
            $PAR_POIDS ++;
        }
    }

    /**
     * Ordonne tous les enfants d'une page
     *
     * @return void
     */
    public function reorderChildren()
    {
        $sql = "select ID_PAGE from " . $this->mode . "PAGE where PAG_IDPERE=" . $this->getID() . " order by PAG_POIDS";
        $stmt = $this->dbh->prepare("update " . $this->mode . "PAGE set PAG_POIDS=:PAG_POIDS where ID_PAGE=:ID_PAGE");
        $PAG_POIDS = 1;
        $stmt->bindParam(':PAG_POIDS', $PAG_POIDS, PDO::PARAM_INT);
        foreach ($this->dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN) as $ID_PAGE) {
            $stmt->bindValue(':ID_PAGE', $ID_PAGE, PDO::PARAM_INT);
            $stmt->execute();
            $PAG_POIDS ++;
        }
    }

    /**
     * Retourne un sous-ensemble de la balise <a >
     *
     * @param $aParam les
     *            parametres de l'url du href avec key=nom_du_param et val=valeur_du_param
     * @param  string $ancre
     *                       l'ancre du href (ne contient pas #)
     * @param $aClass les
     *                       classes à ajouter
     * @return string les attributs href et class d'une balise <a>
     */
    public function getAnchor($aParam = array (), $ancre = '', $aClass = array ())
    {
        $url = $this->getURLESCAPE($aParam, $ancre);

        if (strpos($url, '://') > 0 && strpos($url, '://' . $_SERVER['HTTP_HOST']) === false) {
            $aClass[] = 'external';
        }

        $str = 'href="' . $url . '"';
        if (sizeof($aClass) > 0) {
            $str .= ' class="' . implode(' ', $aClass) . '"';
        }

        return $str;
    }

    /**
     * Retourne l'url de la page
     * Attention cette chaine utilise le caractère &amp; comme séparateur d'argument (à utiliser dans du rendu HTML)
     *
     * @param  array  $aParam
     *                        les parametres de l'url avec key=nom_du_param et val=valeur_du_param. val peut également etre un tableau
     * @param  string $ancre
     *                        l'ancre du href (ne contient pas #)
     * @return string l'url pour <form action=""> | <img longdesc="">
     */
    public function getURLESCAPE($aParam = array (), $ancre = '')
    {
        return str_replace('&', '&amp;', $this->getURL($aParam, $ancre));
    }

    /**
     * Retourne l'url de la page
     * Attention cette chaine utilise le caractère & comme séparateur d'argument (il faut donc les remplacer par des &amp; pour un usage HTML)
     *
     * @param  array  $aParam
     *                        les parametres de l'url avec key=nom_du_param et val=valeur_du_param. val peut également etre un tableau
     * @param  string $ancre
     *                        l'ancre du href (ne contient pas #)
     * @return string l'url pour <form action=""> | <img longdesc="">
     */
    public function getURL($aParam = array (), $ancre = '')
    {
        if ($oWebo = $this->getExternalRedirection()) {
            return $oWebo->getField('WEB_CHEMIN');
        }
        if ($oRedir = $this->getInternalRedirection()) {
            return $oRedir->getURL();
        }
        if ($this->mode == 'OFF_') {
            $url = '';
            foreach ($aParam as $key => $val) {
                if (is_array($val)) {
                    foreach ($val as $valBis) {
                        $url .= '&' . urlencode($key . '[]') . '=' . urlencode($valBis);
                    }
                } else {
                    $url .= '&' . urlencode($key) . '=' . urlencode($val);
                }
            }
            if ($ancre != '') {
                $url .= '#' . $ancre;
            }

            return SERVER_ROOT . 'cms/cms_pseudo.php?idtf=' . $this->getID() . $url;
        }
        $url = '';
        // * Detection d'une page HTTPS
        $bHTTPS = false;
        if ($this->exist() && $this->getField('PAG_HTTPS') == 1) {
            if (CMS::getCurrentSite()->getID() == $this->getField('SIT_CODE')) {
                $bHTTPS = CMS::getCurrentSite()->getField('SIT_PAGE_HTTPS');
            } else {
                $oSite = new Site($this->getField('SIT_CODE'));
                $bHTTPS = $oSite->getField('SIT_PAGE_HTTPS');
            }
        }
        // */
        if ($this->isHome() && (sizeof($aParam) == 0)) {
            // rien
        } elseif (URL_REWRITING) {

            require_once CLASS_DIR . 'class.UrlBuilder.php';

            $urlBuilder = new UrlBuilder($this);
            $url = $urlBuilder->setParam($aParam)->getUri();
        } else {
            foreach ($aParam as $key => $val) {
                if (is_array($val)) {
                    foreach ($val as $valBis) {
                        $url .= '&' . urlencode($key . '[]') . '=' . urlencode($valBis);
                    }
                } else {
                    $url .= '&' . urlencode($key) . '=' . urlencode($val);
                }
            }
            $url = 'index.php?idtf=' . $this->getID() . $url;
        }
        if ($ancre != '') {
            $url .= '#' . $ancre;
        }
        if (CMS::getCurrentSite()->getID() != $this->getField('SIT_CODE')) {
            $sql = "select SIT_HOST from DD_SITE where SIT_CODE=" . $this->dbh->quote($this->getField('SIT_CODE'));
            $url = ($bHTTPS ? 'https://' : 'http://') . $this->dbh->query($sql)->fetchColumn() . SERVER_ROOT . $url;
        } else {
            // Si on est en HTTPS et que le lien n'est pas en HTTPS, on place une URL absolue
            if (($_SERVER['HTTPS'] == 'on') && ! $bHTTPS) {
                $url = 'http://' . CMS::getCurrentSite()->getField('SIT_HOST') . SERVER_ROOT . $url;
                // Si on pointe sur une page HTTPS, on place une URL absolue
            } elseif ($bHTTPS) {
                $url = 'https://' . CMS::getCurrentSite()->getField('SIT_HOST') . SERVER_ROOT . $url;
                // Si non, on conserve le chemin relatif
            } else {
                $url = SERVER_ROOT . $url;
            }
        }

        return $url;
    }

    public function redirect($aParam = array (), $ancre = '')
    {
        $url = $this->getURL($aParam, $ancre);
        if (strpos($url, '://') === false) {
            $url = 'http://' . $_SERVER['HTTP_HOST'] . $url;
        }
        header('Location:' . $url);
        exit();
    }

    public static function unlockAll()
    {
        $dbh = DB::getInstance();
        $sql = "update OFF_PAGE set ID_UTILISATEURVERROU=null, PAG_DATEVERROU=null where ID_UTILISATEURVERROU=" . Utilisateur::getConnected()->getID();
        $dbh->exec($sql);
    }

    /**
     * Retourne les identifiants de toute les pages interdites
     *
     * @return array un tableau d'identifiant
     */
    public static function getForbiddenID($mode)
    {
        if (is_null(self::$_aForbiddenID)) {
            require_once CLASS_DIR . 'class.db_utilisateur.php';
            $aPage = array();
            if (Utilisateur::isConnected()) {
                $aPage += Utilisateur::getConnected()->getPages();
            }
            if ($mode == 'ON_') {
                $tab = self::getSecuredID($mode);
            } else {
                $tab = array();
            }
            $tab = array_diff($tab, $aPage);
            self::$_aForbiddenID = $tab;
        }

        return self::$_aForbiddenID;
    }

    /**
     * Retourne les identifiants de toutes les pages sécurisées
     *
     * @return array un tableau d'identifiant
     */
    public static function getSecuredID($mode)
    {
        if (is_null(self::$_aSecuredID)) {
            $dbh = DB::getInstance();
            $sql = "select distinct(ID_PAGE) from GROUPE_" . $mode . "PAGE";
            self::$_aSecuredID = $dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN);
        }

        return self::$_aSecuredID;
    }

    /**
     * retourne un tableau de l'ensemble des tags de la page et sa descendance
     * ce tableau est trié sur le nombre d'occurence (les plus nombreux au début)
     *
     * @return array un tableau tag=>nbOccurence
     */
    public function getTag($limit)
    {
        if (is_null($this->_aTag)) {
            $_aTag = array();
            $aID = array_merge(array(
                $this->getID()
            ), $this->getChildrenID());
            $sql = "select PAG_MOTCLE1, PAG_MOTCLE2, PAG_MOTCLE3, PAG_MOTCLE4, PAG_MOTCLE5 from " . $this->mode . "PAGE where ID_PAGE in (" . implode(', ', $aID) . ")";
            foreach ($this->dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
                for ($i = 1; $i <= 5; $i ++) {
                    if ($row['PAG_MOTCLE' . $i] != '') {
                        $_aTag[] = $row['PAG_MOTCLE' . $i];
                    }
                }
            }
            $_aTag = array_count_values($_aTag);
            arsort($_aTag);
            $this->_aTag = array_slice($_aTag, 0, $limit, true);
        }

        return $this->_aTag;
    }

    public function isRevision()
    {
        return $this->_isRevision;
    }

    /**
     * Retourne le tableau de toutes les révisions de la page
     *
     * @access public
     *
     * @return array Tableau d'instances de révision
     */
    public function getAllRevision()
    {
        if (is_null($this->_aRevision) && $this->exist()) {
            require_once CLASS_DIR . 'class.db_revision.php';
            $this->_aRevision = array();
            $sql = "select ID_REVISION from REVISION where ID_PAGE = " . $this->getID() . " order by REV_DATECREATION";
            foreach ($this->dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $this->_aRevision[] = new Revision($row['ID_REVISION']);
            }
        }

        return $this->_aRevision;
    }

    /**
     * Enregistrement d'une nouvelle révision
     *
     * @throws Exception
     */
    public function createRevision()
    {
        if (! ($this->exist() && $this instanceof Page)) {
            throw new Exception('Impossible de créer la révision pour la page ' . $this->getID());

            return false;
        }

        require_once CLASS_DIR . 'class.Link.php';
        require_once CLASS_DIR . 'class.db_revision.php';

        // Nouvelle révision
        $this->dbh->exec("insert into REVISION (ID_PAGE, REV_DATECREATION) values (" . $this->getID() . ", " . time() . ")");
        $_oRevision = new Revision($this->dbh->lastInsertID());

        // Enregistrement des données de la page
        $this->dbh->exec("insert into REVISION_PAGE (
        ID_REVISION,
        ID_PAGE,
        ID_PAGE_REDIRECT,
        SIT_CODE,
        ID_WEBOTHEQUE_IMAGE,
        ID_WEBOTHEQUE_LIENEXTERNE,
        PGS_CODE,
        PSS_CODE,
        ID_STYLEDYNAMIQUE,
        PAG_TITRE,
        PAG_TITRE_MENU,
        PAG_TITRE_REFERENCEMENT,
        PAG_TITLE, PAG_ACCROCHE,
        PAG_METADESCRIPTION,
        PAG_HEAD,
        PAG_VISIBLE_MENU,
        PAG_MOTCLE1,
        PAG_MOTCLE2,
        PAG_MOTCLE3,
        PAG_MOTCLE4,
        PAG_MOTCLE5,
        PAG_DATEMISEAJOUR,
        PAG_URLREWRITING,
        PAG_GOOGLEFREQUENCE,
        PAG_GOOGLEPRIORITE,
        PAG_EXCLURECHERCHE,
        PAG_MASQUERGAUCHE,
        PAG_MASQUERDROITE,
        PAG_COMMENTAIREACTIF,
        PAG_HTTPS)
        select
        " . $_oRevision->getID() . ",
        ID_PAGE,
        ID_PAGE_REDIRECT,
        SIT_CODE,
        ID_WEBOTHEQUE_IMAGE,
        ID_WEBOTHEQUE_LIENEXTERNE,
        PGS_CODE,
        PSS_CODE,
        ID_STYLEDYNAMIQUE,
        PAG_TITRE,
        PAG_TITRE_MENU,
        PAG_TITRE_REFERENCEMENT,
        PAG_TITLE, PAG_ACCROCHE,
        PAG_METADESCRIPTION,
        PAG_HEAD,
        PAG_VISIBLE_MENU,
        PAG_MOTCLE1,
        PAG_MOTCLE2,
        PAG_MOTCLE3,
        PAG_MOTCLE4,
        PAG_MOTCLE5,
        PAG_DATEMISEAJOUR,
        PAG_URLREWRITING,
        PAG_GOOGLEFREQUENCE,
        PAG_GOOGLEPRIORITE,
        PAG_EXCLURECHERCHE,
        PAG_MASQUERGAUCHE,
        PAG_MASQUERDROITE,
        PAG_COMMENTAIREACTIF,
        PAG_HTTPS
        from OFF_PAGE where ID_PAGE=" . $this->getID());

        // Champs pas pris en compte : PAG_DATEONLINE, PAG_DATEONLINE_ON, PAG_DATEOFFLINE, PAG_DATEOFFLINE_ON,
        // PAG_DATEVERROU, PAG_DATEMODIFICATION, [PAG_STYLEDEFAUTHERITABLE, PAG_STYLEPERSOHERITABLE]

        $idRevisionPage = $this->dbh->lastInsertID();

        // Reprise des liaisons de la page
        // Liaisons webs
        $sqlLiaisonsPage = "select * from LIAISON_WEBOTHEQUE where LIA_CODE = 'OFF_PAGE' and ID_LIAISON = " . $this->getID() . " order by LIA_ORDRE asc";
        foreach ($this->dbh->query($sqlLiaisonsPage)->fetchAll(PDO::FETCH_ASSOC) as $aLiaison) {
            Link::insertWebotheque('REVISION_PAGE', $idRevisionPage, $aLiaison['ID_WEBOTHEQUE'], $_oRevision->getID(), $aLiaison['LIA_TYPE'], $aLiaison['LIA_TEMP'], $aLiaison['LIA_TEXT']);
        }

        // Liaisons page
        $sqlLiaisonsWeb = "select * from LIAISON_PAGE where LIA_CODE = 'OFF_PAGE' and ID_LIAISON = " . $this->getID() . " order by LIA_ORDRE asc";
        foreach ($this->dbh->query($sqlLiaisonsWeb)->fetchAll(PDO::FETCH_ASSOC) as $aLiaison) {
            Link::insertPage('REVISION_PAGE', $idRevisionPage, $aLiaison['ID_PAGE'], null, $_oRevision->getID(), $aLiaison['LIA_TYPE'], $aLiaison['LIA_TEMP'], $aLiaison['LIA_TEXT']);
        }

        // Liaisons externes ? Pas de liaison externe - Données hautement modifiable

        // copie des paragraphes OFF => REVISION
        $sql = "insert into REVISION_PARAGRAPHE (ID_REVISION,            ID_PARAGRAPHE, ID_PAGE, PRT_CODE, PRS_CODE, PRS_WIDTH, TPL_CODE, PAR_TPL_IDENTIFIANT, PAR_POIDS, PAR_TITRE, PAR_CONTENU, PAR_CONTENUPARSE, PAR_CONTENUTEXTE, PAR_APARSER, PAR_COLONNE, PAR_HERITABLE, PAR_BROUILLON)
                                        select " . $_oRevision->getID() . ", ID_PARAGRAPHE, ID_PAGE, PRT_CODE, PRS_CODE, PRS_WIDTH, TPL_CODE, PAR_TPL_IDENTIFIANT, PAR_POIDS, PAR_TITRE, PAR_CONTENU, PAR_CONTENUPARSE, PAR_CONTENUTEXTE,  1         , PAR_COLONNE, PAR_HERITABLE, PAR_BROUILLON from OFF_PARAGRAPHE where ID_PAGE=" . intval($this->getID());
        $this->dbh->exec($sql);

        // copie des liaisons (obligatoire à cause des liens hors éditeur qui ne seront pas reparsés)
        // LIAISONS PARAGRAPHE SEULEMENT
        // Liaisons page
        $sqlLiaisonsPage = "select LIAISON_PAGE.*, REVISION_PARAGRAPHE.ID_REVISIONPARAGRAPHE from LIAISON_PAGE
        inner join REVISION_PARAGRAPHE on (REVISION_PARAGRAPHE.ID_PARAGRAPHE = LIAISON_PAGE.ID_LIAISON and REVISION_PARAGRAPHE.ID_REVISION = " . $_oRevision->getID() . ")
        where LIA_CODE = 'OFF_PARAGRAPHE'
        and LIAISON_PAGE.ID_LIAISON in (select ID_PARAGRAPHE from OFF_PARAGRAPHE where ID_PAGE=" . $this->getID() . ") order by LIA_ORDRE asc";
        foreach ($this->dbh->query($sqlLiaisonsPage)->fetchAll(PDO::FETCH_ASSOC) as $aLiaison) {
            Link::insertPage('REVISION_PARAGRAPHE', $aLiaison['ID_REVISIONPARAGRAPHE'], $aLiaison['ID_PAGE'], $aLiaison['ID_PARAGRAPHE'], $_oRevision->getID(), $aLiaison['LIA_TYPE'], $aLiaison['LIA_TEMP'], $aLiaison['LIA_TEXT']);
        }

        // Liaisons webs
        $sqlLiaisonsWeb = "select LIAISON_WEBOTHEQUE.*, REVISION_PARAGRAPHE.ID_REVISIONPARAGRAPHE from LIAISON_WEBOTHEQUE
        inner join REVISION_PARAGRAPHE on (REVISION_PARAGRAPHE.ID_PARAGRAPHE = LIAISON_WEBOTHEQUE.ID_LIAISON and REVISION_PARAGRAPHE.ID_REVISION = " . $_oRevision->getID() . ")
        where LIA_CODE = 'OFF_PARAGRAPHE'
        and LIAISON_WEBOTHEQUE.ID_LIAISON in (select ID_PARAGRAPHE from OFF_PARAGRAPHE where ID_PAGE=" . $this->getID() . ") order by LIA_ORDRE asc";
        foreach ($this->dbh->query($sqlLiaisonsWeb)->fetchAll(PDO::FETCH_ASSOC) as $aLiaison) {
            Link::insertWebotheque('REVISION_PARAGRAPHE', $aLiaison['ID_REVISIONPARAGRAPHE'], $aLiaison['ID_WEBOTHEQUE'], $_oRevision->getID(), $aLiaison['LIA_TYPE'], $aLiaison['LIA_TEMP'], $aLiaison['LIA_TEXT']);
        }

        if (CMS::getCurrentSite()->hasModule(new Module('MOD_EXTRANET'))) {
            // copie des groupes OFF => REVISION
            $sql = "insert into REVISION_GROUPE (ID_GROUPE, ID_REVISION) select ID_GROUPE, " . $_oRevision->getID() . " from GROUPE_OFF_PAGE where ID_PAGE = " . $this->getID();
            $this->dbh->exec($sql);
        }
        // Roles
        $sql = "insert into REVISION_ROLE (SIT_CODE, ID_UTILISATEUR, PRO_CODE, ID_REVISION) select SIT_CODE, ID_UTILISATEUR, PRO_CODE, " . $_oRevision->getID() . " from ROLE where ID_PAGE = " . $this->getID();
        $this->dbh->exec($sql);

        // Historisation de la création de la révision
        $this->historize('CREATION', 'REVISION', gettext('Revision_du') . ' ' . date('d/m/Y H:i', $_oRevision->getField('REV_DATECREATION')), null, $_oRevision->getID());

        return true;
    }

    public function deleteRevision()
    {
        if (! ($this instanceof Page))
            return;

        require_once CLASS_DIR . 'class.db_revision.php';
        foreach ($this->getAllRevision() as $oRevision) {
            $oRevision->delete();
        }
    }

    /**
     *
     * @param  int       $idtf
     * @param  int       $revID
     * @return Page
     * @throws Exception
     */
    public static function getPseudoInstance($idtf, $revID = null)
    {
        if (CMS::$mode != 'OFF_') {
            throw new Exception('Erreur de contexte. La méthode ' . __METHOD__ . ' ne peut être appelée depuis le front-office');
        }

        if (is_numeric($revID) && ! empty($revID)) {
            require_once CLASS_DIR . 'class.db_revision.php';
            $oRevision = new Revision($revID);
            if ($oRevision->exist()) {
                $oPage = $oRevision->getPage();
                if ($oPage->checkAuthorized(false) && ! Utilisateur::getConnected()->isSEO()) {
                    return $oPage;
                }
            }
        }

        return new Page($idtf, CMS::$mode);
    }

    /**
     * Retourn si la page a une verion enligne ou non.
     *
     * @access Public
     * @return Bool true si un verion enligne de cette page existe sinon false.
     */
    public function hasOnlineVersion()
    {
        $stmt = $this->dbh->prepare("select ID_PAGE from ON_PAGE where ID_PAGE=:ID_PAGE");
        $stmt->bindValue(':ID_PAGE', $this->getID(), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ? true : false;
    }

    /**
     * Renvoi un tableau contenant les ID des thématiques associés à la page
     *
     * @return array Tableau d'Id tématique
     */
    public function getIdThematiques()
    {
        $sql = "select ID_THEMATIQUE from LIAISON_THEMATIQUE where LIA_CODE='" . CMS::$mode . "PAGE' and ID_LIAISON=" . $this->getID();
        $aIdThematique = array();
        foreach ($this->dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN) as $ID_THEMATIQUE) {
            $aIdThematique[] = $ID_THEMATIQUE;
        }

        return $aIdThematique;
    }

    /**
     * Verifie si la page contient un paragraphe
     *
     * @return bool true si il y a une sauvegarde, false sinon
     */
    public function hasBackup()
    {
        $sql = "select count(ID_PARAGRAPHE) from OFF_PARAGRAPHE where ID_PAGE=" . $this->getID() . " and PAR_BROUILLON is not null";
        return $this->dbh->query($sql)->fetchColumn() > 0;
    }

    /**
     *
     * @deprecated
     *
     */
    public static function getTitleHTML()
    {
        die('Utiliser CMS::replaceTITLE() avec l\'option concat');
    }

    /**
     *
     * @deprecated
     *
     */
    public static function setTitleHTML($title)
    {
        die('Utiliser CMS::replaceTITLE()');
    }

    /**
     *
     * @deprecated
     *
     */
    public static function setDescriptionHTML($desc)
    {
        die('Utiliser CMS::replaceMETADESCRIPTION()');
    }

    /**
     *
     * @deprecated
     *
     */
    public function getStatsTag()
    {
        die('Ne fait plus partie du core du CMS');
    }

    public function setURLAlternative($aURL)
    {
        unset($_SESSION['URL_ALTERNATIVE'][CMS::getCurrentSite()->getID()]);
        $stmt = $this->dbh->exec("delete from URLALTERNATIVE where ID_PAGE=" . $this->getID());
        $retour = true;
        if (is_array($aURL)) {
            $stmtINS = $this->dbh->prepare("insert into URLALTERNATIVE (ID_PAGE, URA_LIBELLE) values (:idtf, :URA_LIBELLE)");
            $stmtDEL = $this->dbh->prepare("delete URLALTERNATIVE from URLALTERNATIVE
                inner join OFF_PAGE using (ID_PAGE)
                where URA_LIBELLE = :URA_LIBELLE and SIT_CODE = :SIT_CODE");
            $stmtINS->bindValue(':idtf', $this->getID(), PDO::PARAM_INT);
            $stmtDEL->bindValue(':SIT_CODE', CMS::getCurrentSite()->getID(), PDO::PARAM_STR);
            foreach ($aURL as $URA_LIBELLE) {
                $URA_LIBELLE = trim($URA_LIBELLE);
                if ($URA_LIBELLE != '') {
                    if (testURLFormat($URA_LIBELLE)) {
                        // on conserve les / par rapport à filenameToRfc1738
                        $URA_LIBELLE = str_replace('/', 'unechainequijespereneserajamaissaisie', $URA_LIBELLE);
                        $URA_LIBELLE = filenameToRfc1738($URA_LIBELLE);
                        $URA_LIBELLE = str_replace('unechainequijespereneserajamaissaisie', '/', $URA_LIBELLE);
                        $stmtDEL->bindValue(':URA_LIBELLE', $URA_LIBELLE, PDO::PARAM_STR);
                        $stmtDEL->execute();
                        $stmtINS->bindValue(':URA_LIBELLE', strtolower($URA_LIBELLE), PDO::PARAM_STR);
                        $stmtINS->execute();
                    } else {
                        $retour = false;
                    }
                }
            }
        }

        return $retour;
    }

    public static function getAllURLAlternative()
    {
        if (! isset($_SESSION['URL_ALTERNATIVE'][CMS::getCurrentSite()->getID()]) || $_SESSION['URL_ALTERNATIVE']['dlc'] < time()) {
            $_SESSION['URL_ALTERNATIVE']['dlc'] = time() + 300;
            $dbh = DB::getInstance();
            $sql = "select url.URA_LIBELLE, url.ID_PAGE from URLALTERNATIVE as url
                inner join OFF_PAGE using (ID_PAGE)
                where SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID());
            $_SESSION['URL_ALTERNATIVE'][CMS::getCurrentSite()->getID()] = $dbh->query($sql)->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE | PDO::FETCH_COLUMN);
        }

        return $_SESSION['URL_ALTERNATIVE'][CMS::getCurrentSite()->getID()];
    }

    // implementation pour la classe commentaire
    /**
     * renvoi le libéllé à afficher en BO pour le type de cible
     *
     * @return string [ + mesage si cible a été supprimé]
     */
    public function getLibelleTypeCommentaire()
    {
        $ret = 'Page : la page n’existe plus';
        if ($this->exist()) {
            $ret = 'Page';
        }

        return $ret;
    }

    /**
     * Renvoi le libellé de la page
     *
     * @return string ou false
     */
    public function getLibelleCommentaire()
    {
        $ret = false;
        if ($this->exist()) {
            $ret = $this->getField('PAG_TITRE_MENU');
        }

        return $ret;
    }

    /**
     * renvoi un lien pour afficher la page en FO
     *
     * @return string [url] ou false
     */
    public function getURLCommentaire()
    {
        $ret = false;
        if ($this->exist()) {
            $oPage = new Page($this->getID(), 'ON_');
            if ($oPage && $oPage->exist()) {
                $ret = $oPage->getURL();
            }
        }

        return $ret;
    }

    /**
     * renvoi un lien pour afficher la page en Pseudo FO
     *
     * @return string [url] ou false
     */
    public function getURLBO()
    {
        $ret = false;
        if ($this->exist()) {
            $oPage = new Page($this->getID(), 'OFF_');
            $ret = $oPage->getURL(array(
                'PFM' => 1
            ));
        }

        return $ret;
    }

    /**
     * renvoi 'true' si les commentaires pour cet instance du module doit être affiché, sinon 'false'
     */
    public function showCommentaire()
    {
        // si l'option commentaire est activé
        if ($this->exist() && $this->getField('PAG_COMMENTAIREACTIF'))
            return true;

        return false;
    }

    public function insertInto(Page $oPageParent, $aID)
    {
        $sql = "select ID_PAGE from OFF_PAGE where PAG_IDPERE=" . $oPageParent->getID() . " order by PAG_POIDS";
        $aOldID = $this->dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN);

        $stmt = $this->dbh->prepare("update OFF_PAGE set PAG_POIDS=:PAG_POIDS, PAG_IDPERE=:PAG_IDPERE where ID_PAGE=:ID_PAGE");
        $PAG_POIDS = 1;
        $stmt->bindParam(':PAG_POIDS', $PAG_POIDS, PDO::PARAM_INT);
        $stmt->bindValue(':PAG_IDPERE', $oPageParent->getID(), PDO::PARAM_INT);
        foreach ($aID as $idtf) {
            if ($idtf != $this->getID() && ! in_array($idtf, $aOldID)) {
                return false;
            }
            $stmt->bindValue(':ID_PAGE', $idtf, PDO::PARAM_INT);
            $stmt->execute();
            $PAG_POIDS ++;
        }

        $this->cleanUpAfterMove($oPageParent);

        return true;
    }

    /**
     * Gestion des paragraphes hérités +
     * Réordonne les enfant lorsque necessaire +
     * Modifie le statut de la page +
     * Met à jour les dates des enfants
     *
     * @param Page $oPageDest
     *                        La page de destination dont il faut réordonner
     *
     */
    private function cleanUpAfterMove(Page $oPageParent)
    {
        // à ce moment on n'a pas encore rechargé les champs
        // donc pour récupérer l'ancien parent on peut encore se baser dessus
        // Si la page a changée de père
        $oPageParentOld = end($this->getParents());
        if ($oPageParentOld->getID() != $oPageParent->getID()) {
            // On supprime les paragraphes qui ne sont plus hérités
            $_a = array_diff($oPageParentOld->getParentsID(true), $oPageParent->getParentsID(true));
            if (sizeof($_a) > 0) {
                $sql = "select ID_PARAGRAPHE from OFF_PARAGRAPHE where PAR_HERITABLE!=0 and ID_PAGE in (" . implode(',', $_a) . ")";
                foreach ($this->dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN) as $idtf) {
                    $oPara = new Paragraphe($idtf, 'OFF_');
                    $oPara->deleteInherited($this);
                }
            }

            // On ajoute les paragraphes qui sont nouvellement hérités (après le déplacement)
            $_a = array_diff($oPageParent->getParentsID(true), $oPageParentOld->getParentsID(true));
            if (sizeof($_a) > 0) {
                $sql = "select * from OFF_PARAGRAPHE where PAR_HERITABLE!=0 and ID_PAGE in (" . implode(',', $_a) . ")";
                foreach ($this->dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $oParagraphe = new Paragraphe($row['ID_PARAGRAPHE'], 'OFF_');
                    $oParagraphe->setFields($row);
                    $oParagraphe->inherit();
                }
            }

            // Réordonancement des pages enfants de la destination
            $oPageParent->reorderChildren();
        }

        // Réordonancement des pages enfants de l'ancien parent
        $oPageParentOld->reorderChildren();

        $this->resetStatut();

        // On va chercher les dates du nouveau pere
        $PAG_DATEONLINE = ($oPageParentOld->getField('PAG_DATEONLINE_ON') != '' && $oPageParentOld->getField('PAG_DATEONLINE_ON') > $oPageParentOld->getField('PAG_DATEONLINE')) ? $oPageParentOld->getField('PAG_DATEONLINE_ON') : $oPageParentOld->getField('PAG_DATEONLINE');

        $PAG_DATEOFFLINE = ($oPageParentOld->getField('PAG_DATEOFFLINE_ON') != '' && $oPageParentOld->getField('PAG_DATEOFFLINE_ON') < $oPageParentOld->getField('PAG_DATEOFFLINE')) ? $oPageParentOld->getField('PAG_DATEOFFLINE_ON') : $oPageParentOld->getField('PAG_DATEOFFLINE');

        // On va chercher tous les fils pour la cohérence des dates
        $aChildren = $this->getChildrenID();
        $aChildren[] = $this->getID();
        $sql = "update OFF_PAGE set PST_CODE=" . $this->dbh->quote(Page::getInitialStatut()) . ",
                PAG_DATEONLINE=" . $PAG_DATEONLINE . "
                where ID_PAGE in (" . implode(',', $aChildren) . ") and PAG_DATEONLINE<" . $PAG_DATEONLINE;
        $this->dbh->exec($sql);

        $sql = "update OFF_PAGE set PST_CODE=" . $this->dbh->quote(Page::getInitialStatut()) . ",
                PAG_DATEOFFLINE=" . $PAG_DATEOFFLINE . "
                where ID_PAGE in (" . implode(',', $aChildren) . ") and PAG_DATEOFFLINE>" . $PAG_DATEOFFLINE;
        $this->dbh->exec($sql);

        return true;
    }

    /**
     * Charge les styles de page s'il y en a
     *
     * @return Bool True si des styles ont été chargés, False sinon
     *
     */
    public function loadPageStyles()
    {
        if ($this->getField('PSS_CODE') == '') {
            return false;
        }
        $sql = 'select PSS_PATH from DD_PAGESTYLE where
                    PSS_CODE=' . $this->dbh->quote($this->getField('PSS_CODE')) . '
                    and GBS_CODE=' . $this->dbh->quote(CMS::getCurrentSite()->getField('GBS_CODE'));
        if ($PSS_PATH = $this->dbh->query($sql)->fetch(PDO::FETCH_COLUMN)) {
            CMS::addLESS(SERVER_ROOT . $PSS_PATH, array(
                'media' => 'screen, print'
            ));

            return true;
        }

        return false;
    }

    /**
     * Charge les styles dynamiques de page s'il y en a
     *
     * @return Bool True si des styles ont été chargés, False sinon
     *
     */
    public function loadDynamicStyles()
    {
        if ($this->getField('ID_STYLEDYNAMIQUE') == '') {
            return false;
        }
        CMS::addCSS(SERVER_ROOT . 'include/css/css.php?idtf=' . $this->getField('ID_STYLEDYNAMIQUE'), array(
            'media' => 'screen, print'
        ));

        return true;
    }

    /**
     * Fonction déterminant si la page a des filles en ligne
     */
    public function hasOnlineChildren()
    {
        $aChildrenId = self::getChildrenID();
        if (count($aChildrenId) > 0) {
            $sql = "select count(ID_PAGE) from ON_PAGE where ID_PAGE in (" . implode(',', $aChildrenId) . ")";

            return $this->dbh->query($sql)->fetchColumn() > 0;
        }

        return false;
    }

    public function getChildrenByLevel(&$aChildren = array(), $level = 1)
    {
        // if(!is_array($aChildren)) $aChildren = array();
        $sql = "select * from OFF_PAGE where PAG_IDPERE = " . intval($this->getID());
        $datas = $this->dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        if (count($datas) > 0) {
            foreach ($datas as $data) {

                $oPageTmp = new self($data['ID_PAGE'], 'OFF_');
                $oPageTmp->setFields($data);
                $aChildren[$level][] = $oPageTmp;
                $oPageTmp->getChildrenByLevel($aChildren, ($level + 1));
            }
        } else {
            return false;
        }
    }

    public function setDateModification()
    {
        $stmt = $this->dbh->prepare("update OFF_PAGE set
            PAG_DATEMODIFICATION=:PAG_DATEMODIFICATION
            where ID_PAGE=:ID_PAGE");

        $stmt->bindValue(':PAG_DATEMODIFICATION', time(), PDO::PARAM_INT);
        $stmt->bindValue(':ID_PAGE', $this->getID(), PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     *
     * Fonction permettant d'historiser les actions effectuées sur une page, un paragraphe, une révision ...
     *
     * @param string $HIS_ACTION
     *                              : type d'action : CREATION / MODIFICATION / SUPPRESSION
     * @param string $HIS_TYPE
     *                              : type d'élément historisé : PAGE / PARAGRAPHE / REVISION / WORKFLOW / REFERENCEMENT
     * @param string $HIS_DETAIL
     *                              : Détail de l'action si besoin
     * @param int    $ID_PARAGRAPHE
     *                              : Identifiant du paragraphe modifié ($HIS_TYPE = PARAGRAPHE)
     * @param int    $ID_REVISION
     *                              : Identifiant de la révision modifiée ($HIS_TYPE = REVISION)
     */
    public function historize($HIS_ACTION, $HIS_TYPE, $HIS_DETAIL = '', $ID_PARAGRAPHE = null, $ID_REVISION = null)
    {
        $stmt = $this->dbh->prepare("insert into HISTORIQUE_PAGE (
            SIT_CODE,
            ID_PAGE,
            ID_HISTORIQUE_UTILISATEUR,
            ID_PARAGRAPHE,
            ID_REVISION,
            HIS_ACTION,
            HIS_TYPE,
            HIS_DETAIL,
            HIS_DATE
            ) values(
            :SIT_CODE,
            :ID_PAGE,
            :ID_HISTORIQUE_UTILISATEUR,
            :ID_PARAGRAPHE,
            :ID_REVISION,
            :HIS_ACTION,
            :HIS_TYPE,
            :HIS_DETAIL,
            :HIS_DATE
            )");

        $stmt->bindValue(':SIT_CODE', $this->getField('SIT_CODE'), PDO::PARAM_STR);
        $stmt->bindValue(':ID_PAGE', $this->getID(), PDO::PARAM_INT);
        // On peut historiser des modifications sans passer par un utilisateur (ex via le crontab de "mise en ligne" et "hors ligne").
        // Dans ce cas, nous n'avons pas les infos sur ledit utilisateur
        $S_ID_HISTORIQUE = '';
        if (isset($_SESSION['S_ID_HISTORIQUE']) && CMS::getCurrentSite() && is_numeric($_SESSION['S_ID_HISTORIQUE'][CMS::getCurrentSite()->getID()])) {
            $S_ID_HISTORIQUE = $_SESSION['S_ID_HISTORIQUE'][CMS::getCurrentSite()->getID()];
        }
        $stmt->bindValue(':ID_HISTORIQUE_UTILISATEUR', (! empty($S_ID_HISTORIQUE)) ? $S_ID_HISTORIQUE : null, PDO::PARAM_INT);
        $stmt->bindValue(':ID_PARAGRAPHE', $ID_PARAGRAPHE, PDO::PARAM_INT);
        $stmt->bindValue(':ID_REVISION', $ID_REVISION, PDO::PARAM_INT);
        $stmt->bindValue(':HIS_ACTION', $HIS_ACTION, PDO::PARAM_STR);
        $stmt->bindValue(':HIS_TYPE', $HIS_TYPE, PDO::PARAM_STR);
        $stmt->bindValue(':HIS_DETAIL', $HIS_DETAIL, PDO::PARAM_STR);
        $stmt->bindValue(':HIS_DATE', time(), PDO::PARAM_INT);
        $stmt->execute();

        if (isset($_SESSION['S_ID_HISTORIQUE'])) {
            Utilisateur::historizeAction();
        }

        if (($HIS_TYPE == 'PAGE') && ($HIS_ACTION == 'SUPPRESSION')) {
            $this->dbh->exec('update HISTORIQUE_PAGE
                              set HIS_INFO = ' . $this->dbh->quote($this->getField('PAG_TITRE')) . '
                              where ID_PAGE = ' . $this->getID());
        }
    }
}

// {{{ Specificité révision
class Page_Revision extends Page
{

    /**
     *
     * @var Revision Objet Revision
     */
    private $_oRevision = null;

    /**
     *
     * @var Page Objet Page, qui est la référence de l'objet page en cours
     */
    private $_oPageRef = null;

    /**
     *
     * @param Revison $oRevision
     */
    public function __construct(Revision $oRevision)
    {
        parent::__construct($oRevision->getField('ID_PAGE'), 'OFF_');
        $this->_oRevision = $oRevision;
        $this->_oPageRef = new Page($this->_oRevision->getField('ID_PAGE'), 'OFF_');
        $this->_isRevision = true;
    }

    public function load()
    {
        $sql = "select r.*, p.PAG_IDPERE, p.PAG_POIDS, p.PAG_DATEMODIFICATION from REVISION_PAGE r inner join OFF_PAGE p using(ID_PAGE) where ID_REVISION=" . $this->_oRevision->getID() . " and ID_PAGE=" . $this->getID();
        if ($row = $this->dbh->query($sql)->fetch(PDO::FETCH_ASSOC)) {
            $this->setFields($row);
        } else {
            $this->_idtf = - 1;
            $this->setFields(array());
        }
    }

    /**
     *
     * @return bool
     */
    public function delete()
    {
        if (! $this->isDeletable()) {
            return false;
        }

        foreach ($this->getParagraphes() as $oParagraphe) {
            $oParagraphe->delete();
        }
        $aSql = array(
            "delete from LIAISON_WEBOTHEQUE where LIA_CODE='REVISION_PAGE' and ID_REVISION=" . $this->getField('ID_REVISION'),
            "delete from LIAISON_PAGE       where LIA_CODE='REVISION_PAGE' and ID_REVISION=" . $this->getField('ID_REVISION'),
            "delete from REVISION_PAGE      where                              ID_REVISION=" . $this->getField('ID_REVISION')
        );
        foreach ($aSql as $sql) {
            $this->dbh->exec($sql);
        }

        return true;
    }

    public function isDeletable()
    {
        return $this->exist();
    }

    /**
     *
     * @param $oParagraphe l'objet
     *            paragraphe qui précéde l'ajout ou une valeur de la liste suivante ['PAR_LEFT' | 'PAR_RIGHT' | 'PAR_CENTRAL']
     * @param $ID_PARAGRAPHE l'identifiant
     *            du paragraphe (laisser vide si rien avant)
     */
    public function getParagrapheButtons($oParagraphe)
    {
        return '';
    }

    /**
     *
     * @param $PAR_COLONNE ['PAR_LEFT'
     *            | 'PAR_RIGHT' | 'PAR_CENTRAL' | '']
     * @return array un tableau d'objet Paragraphe
     */
    public function getParagraphes($PAR_COLONNE = '')
    {
        // Display mode
        Revision::$display = true;

        if ($this->_aParagraphe == null) {
            require_once CLASS_DIR . 'class.db_paragraphe.php';
            $this->_aParagraphe = array(
                'PAR_LEFT' => array(),
                'PAR_CENTRAL' => array(),
                'PAR_RIGHT' => array()
            );
            $aID = array();
            $sql = "select * from REVISION_PARAGRAPHE where ID_REVISION=" . $this->_oRevision->getID() . " order by PAR_COLONNE, PAR_POIDS";
            $rowLt = $this->dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);

            $aIDParHerites = array();
            foreach ($rowLt as $row) {
                if ($row['TPL_CODE'] == 'TPL_HERITAGE')
                    $aIDParHerites[] = $row['ID_PARAGRAPHE'];
            }

            // Récupération des paragraphes hérités qui on étés ajoutés après la création de la révision
            if (Revision::$display) {
                if (empty($aIDParHerites)) {
                    $aIDParHerites[] = - 1;
                }
                $sql = "select * from " . $this->mode . "PARAGRAPHE where ID_PAGE=" . $this->getID() . " and TPL_CODE='TPL_HERITAGE' and ID_PARAGRAPHE not in (" . implode(',', $aIDParHerites) . ") order by PAR_COLONNE, PAR_POIDS";
                foreach ($this->dbh->query($sql, PDO::FETCH_ASSOC) as $row) {
                    $Paragraphe_class = 'Paragraphe' . substr($row['PRT_CODE'], 3);
                    $oParagraphe = new $Paragraphe_class($row['ID_PARAGRAPHE'], $this->mode);
                    $oParagraphe->setFields($row);
                    $this->_aParagraphe[$row['PAR_COLONNE']][] = $oParagraphe;
                }
            }

            foreach ($rowLt as $row) {
                $aID[] = $row['ID_PARAGRAPHE'];
                if (Revision::$display) {
                    if ($row['TPL_CODE'] == 'TPL_HERITAGE') {
                        // Le paragraphe était hérité lors de la création de la révision, mais le parent n'est plus héritable
                        if ($this->dbh->query("select count(*) from " . $this->mode . "PARAGRAPHE where ID_PARAGRAPHE=" . intval($row['PAR_TPL_IDENTIFIANT']) . " AND PAR_HERITABLE!=0")->fetchColumn() == 0) {
                            // On passe
                            continue;
                        }
                    } // On verifie que chaque paragraphe partagés existent encore en mode ON_
elseif ($row['TPL_CODE'] == 'TPL_PARTAGE') {
                        if ($this->dbh->query("select count(*) from ON_PARAGRAPHE where ID_PARAGRAPHE=" . intval($row['PAR_TPL_IDENTIFIANT']))->fetchColumn() == 0) {
                            // On supprime le paragraphe de la revision et on passe au suivant
                            require_once CLASS_DIR . 'class.db_revision.php';
                            $oParaManquant = new Paragraphe_Revision($row['ID_PARAGRAPHE'], new Revision($row['ID_REVISION']));
                            $oParaManquant->delete();
                            continue;
                        }
                    }
                }
                $Paragraphe_class = 'Paragraphe_Revision' . substr($row['PRT_CODE'], 3);
                $oParagraphe = new $Paragraphe_class($row['ID_PARAGRAPHE'], $this->_oRevision, $this->mode);
                $oParagraphe->setFields($row);
                $this->_aParagraphe[$row['PAR_COLONNE']][] = $oParagraphe;
            }
        }

        return ($PAR_COLONNE == '') ? array_merge($this->_aParagraphe['PAR_LEFT'], $this->_aParagraphe['PAR_CENTRAL'], $this->_aParagraphe['PAR_RIGHT']) : $this->_aParagraphe[$PAR_COLONNE];
    }

    /**
     *
     * @return Revision
     */
    public function getRevision()
    {
        if ($this->_oRevision === null) {
            $this->_oRevision = new Revision($this->getField('ID_REVISION'));
        }

        return $this->_oRevision;
    }

    /**
     *
     * @return Page
     */
    public function getPageRef()
    {
        if ($this->_oPageRef === null) {
            $this->_oPageRef = new Page($this->getID(), 'OFF_');
        }

        return $this->_oPageRef;
    }

    /**
     * Retourne le niveau d'alerte pour le champs $field
     *
     * @access public
     * @param string $field
     *                      Nom du champ pour lequel on shouaite verifier la
     *
     * @return integer [0 => Alerte nulle, 1 => Alerte
     */
    public function getLevelNotice($field)
    {
        if ($this->fieldIsEqual($field)) {
            return 0;
        }

        switch ($field) {
            case 'ID_WEBOTHEQUE_IMAGE':
                if (is_numeric($this->getField('ID_WEBOTHEQUE_IMAGE')) && $this->getField('ID_WEBOTHEQUE_IMAGE') > 0) {
                    $sql = "select count(*) from WEBOTHEQUE where ID_WEBOTHEQUE=" . intval($this->getField('ID_WEBOTHEQUE_IMAGE'));
                    if ($this->dbh->query($sql)->fetchColumn() == 0) {
                        return 1;
                    }
                }
                break;

            case 'PGS_CODE':
                $sql = "select ID_PAGE from OFF_PAGE where PGS_CODE=" . $this->dbh->quote($this->getField('PGS_CODE'));
                $row = $this->dbh->query($sql)->fetchColumn();
                if ($this->getField('PGS_CODE') != '') {
                    if ($row && $row != $this->getID()) {
                        return 2;
                    }
                } else {
                    if ($row && $row == $this->getID()) {
                        return 1;
                    }
                }
                break;
        }

        return 0;
    }

    /**
     * Permet de tester si la valeur d'un champ d'une révision de page est égale à la valeur de la page en cours
     *
     * @param  string $field
     * @return bool
     */
    public function fieldIsEqual($field)
    {
        $val = $this->getField($field);
        $bFieldIsNull = false;
        // Il faut déterminer sur le champ est "nul" ou simplement vide
        if (empty($val)) {
            $sql = "select " . $field . " from  REVISION_PAGE where ID_REVISION=" . $this->_oRevision->getID() . " and " . $field . " is not null";
            $bFieldIsNull = is_bool($this->dbh->query($sql)->fetchColumn()); // Si pas de résultat (fetchColumn() === false), c'est que le champ est nul
        }
        if (! $bFieldIsNull) {
            $sql = "select count(r." . $field . ") as IS_EQUAL from OFF_PAGE p inner join REVISION_PAGE r using (ID_PAGE) where ID_REVISION=" . $this->_oRevision->getID() . " and p." . $field . "=r." . $field;
            return $this->dbh->query($sql)->fetchColumn() == 1;
        } else {
            $sql = "select count(r.ID_REVISION) from OFF_PAGE p inner join REVISION_PAGE r using (ID_PAGE) where ID_REVISION=" . $this->_oRevision->getID() . " and p." . $field . " is null and r." . $field . " is null";
            return $this->dbh->query($sql)->fetchColumn() == 1;
        }
    }

    /**
     * Retourne la valeur à insérer dans la table OFF_PAGE pour un champ donné dans le cas d'un retour à une version
     *
     * @access public
     * @param string $field
     *                      Nom du champpour lequel on souhaite obtenir la valeur
     *
     * @return mixed
     */
    public function getInsertField($field)
    {
        if ($this->fieldIsEqual($field)) {
            return $this->getField($field);
        }

        switch ($field) {
            case 'ID_WEBOTHEQUE_IMAGE':
                if (is_numeric($this->getField('ID_WEBOTHEQUE_IMAGE')) && $this->getField('ID_WEBOTHEQUE_IMAGE') > 0) {
                    // Facultatif car normalement la liaison
                    $sql = "select count(*) from WEBOTHEQUE where ID_WEBOTHEQUE=" . intval($this->getField('ID_WEBOTHEQUE_IMAGE'));

                    return ($this->dbh->query($sql)->fetchColumn() == 0) ? null : $this->getField('ID_WEBOTHEQUE_IMAGE');
                }

                return null;
                break;

            case 'PGS_CODE':
                $tmpVr = $this->getField('PGS_CODE');
                if (! empty($tmpVr)) {
                    $sql = "select count(*) from OFF_PAGE where PGS_CODE=" . $this->dbh->quote($this->getField('PGS_CODE')) . " and ID_PAGE != " . intval($this->getID());

                    return $this->dbh->query($sql)->fetchColumn() == 1 ? null : $this->getField('PGS_CODE');
                }

                return null;
                break;

            case 'PSS_CODE':
                $tmpVr = $this->getField('PSS_CODE');
                if (! empty($tmpVr)) {
                    $sql = "select count(*) from DD_PAGESTYLE where PSS_CODE=" . $this->dbh->quote($this->getField('PSS_CODE'));

                    return $this->dbh->query($sql)->fetchColumn() > 0 ? $this->getField('PSS_CODE') : null;
                }

                return null;
                break;

            case 'ID_STYLEDYNAMIQUE':
                $tmpVr = $this->getField('ID_STYLEDYNAMIQUE');
                if (! is_null($tmpVr)) {
                    $sql = "select count(*) from STYLEDYNAMIQUE where ID_STYLEDYNAMIQUE=" . intval($this->getField('ID_STYLEDYNAMIQUE'));

                    return $this->dbh->query($sql)->fetchColumn() > 0 ? $this->getField('ID_STYLEDYNAMIQUE') : null;
                }

                return null;
                break;

            case 'ID_WEBOTHEQUE_LIENEXTERNE':
                if (is_numeric($this->getField('ID_WEBOTHEQUE_LIENEXTERNE')) && $this->getField('ID_WEBOTHEQUE_LIENEXTERNE') > 0) {
                    // Facultatif car normalement la liaison
                    $sql = "select count(*) from WEBOTHEQUE where ID_WEBOTHEQUE=" . intval($this->getField('ID_WEBOTHEQUE_LIENEXTERNE'));

                    return ($this->dbh->query($sql)->fetchColumn() == 0) ? null : $this->getField('ID_WEBOTHEQUE_LIENEXTERNE');
                }

                return null;
                break;

            case 'ID_PAGE_REDIRECT':
                if (is_numeric($this->getField('ID_PAGE_REDIRECT')) && $this->getField('ID_PAGE_REDIRECT') > 0) {
                    // Facultatif car normalement la liaison
                    $sql = "select count(*) from OFF_PAGE where ID_PAGE=" . intval($this->getField('ID_PAGE_REDIRECT'));

                    return ($this->dbh->query($sql)->fetchColumn() == 0) ? null : $this->getField('ID_PAGE_REDIRECT');
                }

                return null;
                break;
        }

        return $this->getField($field);
    }
}
