<?php
require_once CLASS_DIR . 'class.db_ajax.php';
require_once PHYSICAL_PATH . 'include/password_compat/password.php';

class Utilisateur extends Ajax
{

    private static $_connected = null;

    private static $_validateRegPatternPwd = '(?=.*\d)(?=.*[!@#$%^&*()_+\-=\[\]{};\':"\\\\|,.<>\/?]).{8,}';

    private static $_validatePwdHelper = "Votre mot de passe doit être composé, au minimum, de 8 caractères dont 1 chiffre et 1 caractère spécial.";

    protected $_aGroupe = array();

    protected $_aPage = array();

    protected $_aProfil = array();

    protected $_aSiteAll = array();

    protected $_aSite = array();

    public function __construct($idtf)
    {
        parent::__construct('UTILISATEUR', 'ID_UTILISATEUR', $idtf);
    }

    public static function getModuleCode()
    {
        return 'MOD_CORE';
    }

    public function load()
    {
        $sql = "select * from UTILISATEUR where ID_UTILISATEUR=" . $this->getID();
        if ($row = $this->dbh->query($sql)->fetch(PDO::FETCH_ASSOC)) {
            $this->setFields($row);
        } else {
            $this->_idtf = - 1;
            $this->setFields(array());
        }
    }

    public function delete()
    {
        if (! $this->isDeletable()) {
            return false;
        }
        // Suppresion des réponses de l'utilisateur aux éventuels formulaires
        require_once CLASS_DIR . 'class.db_formulaire.php';
        $sql = "select ID_FORMULAIRE, ID_FORMULAIREREPONSE from FORMULAIREREPONSE where ID_UTILISATEUR=" . $this->getID();
        foreach ($this->dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $oForm = new Formulaire($row['ID_FORMULAIRE']);
            $oForm->deleteReponse($row['ID_FORMULAIREREPONSE']);
        }

        require_once CLASS_DIR . 'class.db_historique.php';
        Historique::historizeAdmin('SUPPRESSION', 'UTILISATEUR', $this->getID());

        $aSQL = array();
        // mis à jour des éléments webotheque + historique
        $aSQL[] = "update WEBOTHEQUE set ID_UTILISATEUR=null, WEB_REDACTEUR=" . $this->dbh->quote($this->getField('UTI_PRENOM') . ' ' . $this->getField('UTI_NOM')) . " where ID_UTILISATEUR=" . $this->getID();

        // Suppression des verroux de l'utilisateur qui ne se serait pas déconnecté explicitement
        $aSQL[] = "update OFF_PAGE set ID_UTILISATEURVERROU=null, PAG_DATEVERROU=null where ID_UTILISATEURVERROU=" . $this->getID();

        // autre suppression
        $aSQL[] = "delete from GROUPE_UTILISATEUR where ID_UTILISATEUR=" . $this->getID();
        $aSQL[] = "delete from ROLE where ID_UTILISATEUR=" . $this->getID();
        $aSQL[] = "delete from FORMULAIRE_UTILISATEUR where ID_UTILISATEUR=" . $this->getID();
        $aSQL[] = "delete from UTILISATEUR where ID_UTILISATEUR=" . $this->getID();
        foreach ($aSQL as $sql) {
            $this->dbh->exec($sql);
        }

        return true;
    }

    public function isDeletable()
    {
        if ((count($this->getReferants()) > 0) || (count($this->getReferants(false)) > 0)) {
            return false;
        }
        return ($this->getID() != Utilisateur::getConnected()->getID());
    }

    public function getNom()
    {
        return $this->getField('UTI_PRENOM') . ' ' . $this->getField('UTI_NOM');
    }

    /**
     * Utilisée en BO pour ajouter des groupes à l'utilisateur
     */
    public function manageGroupes($aID_GROUPE = null)
    {
        $sql = "delete from GROUPE_UTILISATEUR where ID_UTILISATEUR=" . $this->getID();
        $this->dbh->exec($sql);

        if (is_array($aID_GROUPE)) {
            // tous les groupes du site d'origine de l'utilisateur + ceux "ouverts"
            $sql = "select GROUPE.ID_GROUPE from GROUPE
                left join GROUPE_SITE on GROUPE.ID_GROUPE=GROUPE_SITE.ID_GROUPE
                where GROUPE.SIT_CODE=" . $this->dbh->quote($this->getField('SIT_CODE')) . " or GROUPE_SITE.SIT_CODE=" . $this->dbh->quote($this->getField('SIT_CODE'));
            $aID_GROUPE_OK = $this->dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN);
            $stmt = $this->dbh->prepare("insert into GROUPE_UTILISATEUR (ID_GROUPE, ID_UTILISATEUR) values (:ID_GROUPE, :ID_UTILISATEUR)");
            $stmt->bindValue(':ID_UTILISATEUR', $this->getID(), PDO::PARAM_INT);
            foreach ($aID_GROUPE as $ID_GROUPE) {
                if (! in_array($ID_GROUPE, $aID_GROUPE_OK)) {
                    continue;
                }
                $stmt->bindValue(':ID_GROUPE', $ID_GROUPE, PDO::PARAM_INT);
                $stmt->execute();
            }
        }
    }

    /**
     * Utilisée en BO pour ajouter des profils à l'utilisateur
     */
    public function manageProfils($PRO_ROOT, $aPRO_CODE = null)
    {

        // On supprime tjs le profil PRO_ROOT si l'utilisateur connecté est Super-Admin et qu'il n'édite pas sa propre fiche
        if (Utilisateur::getConnected()->isRoot(true) && (Utilisateur::getConnected()->getId() != $this->getID())) {
            $sql = "delete from ROLE where SIT_CODE is null and ID_UTILISATEUR=" . $this->getID();
            $this->dbh->exec($sql);
            // SUPER ADMIN
            if ($PRO_ROOT == 1) {
                $sql = "insert into ROLE (SIT_CODE, PRO_CODE, ID_UTILISATEUR) values (NULL, 'PRO_ROOT', " . $this->getID() . ")";
                $this->dbh->exec($sql);
            }
        }

        // les autres profils (hors PRO_PAGE)
        $sql = "delete ROLE from ROLE
            inner join DD_PROFIL on ROLE.PRO_CODE = DD_PROFIL.PRO_CODE
            where PRO_PAGE=0 and ID_UTILISATEUR=" . $this->getID() . " and SIT_CODE=" . $this->dbh->quote(CMS::getCurrentSite()->getID());
        $this->dbh->exec($sql);
        if (is_array($aPRO_CODE)) {
            foreach ($aPRO_CODE as $PRO_CODE) {
                $sql = "insert into ROLE (SIT_CODE, PRO_CODE, ID_UTILISATEUR) values (" . $this->dbh->quote(CMS::getCurrentSite()->getID()) . ", " . $this->dbh->quote($PRO_CODE) . ", " . $this->getID() . ")";
                $this->dbh->exec($sql);
            }
        }
    }

    /**
     * Vérifie les profils de l'utilisateurs
     *
     * @since 5.6
     *
     * @param  array $aPRO_CODE
     * @return bool
     */
    public function checkProfil(Array $aPRO_CODE)
    {
        if ($this->isRoot(true)) {
            // super admin : tous les droits
            return true;
        }
        if ($this->isRoot()) {
            // admin de site : on vérifie qu'on ne veut pas précisément PRO_ROOT
            return ! in_array('PRO_ROOT', $aPRO_CODE);
        }
        foreach ($aPRO_CODE as $PRO_CODE) {
            if (array_key_exists($PRO_CODE, $this->getProfils())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Administrateur ou pas ?
     *
     * @since 5.6
     *
     * @param  bool $onlySuperRoot
     * @return bool
     */
    public function isRoot($onlySuperRoot = false)
    {
        if ($onlySuperRoot) {
            if (is_array($this->getProfils())) {
                return array_key_exists('PRO_ROOT', $this->getProfils());
            }
            $sql = "select count(ID_UTILISATEUR) from ROLE where PRO_CODE='PRO_ROOT' and ID_UTILISATEUR=" . $this->getID();

            return $this->dbh->query($sql)->fetchColumn();
        } elseif (is_array($this->getProfils())) {
            return (array_key_exists('PRO_ROOT_SITE', $this->getProfils()) || array_key_exists('PRO_ROOT', $this->getProfils()));
        } else {
            return false;
        }
    }

    /**
     * Utilisateur référenceur ?
     *
     * @since 5.6
     *
     * @param  bool $onlyEolas
     * @return bool
     */
    public function isSEO($onlyEolas = false)
    {
        $_isSEO = (! $this->isRoot() && $this->checkProfil(array(
            'PRO_REFERENCEUR'
        )));
        if ($onlyEolas) {
            $_isSEO = $_isSEO && ($this->getField('UTI_LOGIN') == LOGIN_REFERENCEUR);
        }

        return $_isSEO;
    }

    /**
     * Contributeur ?
     *
     * @since 5.6
     *
     * @return bool
     */
    public function isPageContributor()
    {
        if ($this->isRoot()) {
            return true;
        }
        if (is_array($this->getProfils())) {
            foreach ($this->getProfils() as $PRO_CODE => $val) {
                if (is_array($val)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Contributeur de module ?
     *
     * @since 5.6
     *
     * @return bool
     */
    public function isModuleContributor()
    {
        if ($this->isRoot()) {
            return true;
        }
        if (is_array($this->getProfils())) {
            foreach ($this->getProfils() as $PRO_CODE => $val) {
                if (is_bool($val) && ! in_array($PRO_CODE, array(
                    'PRO_ABREVIATION',
                    'PRO_COMMENTAIRE',
                    'PRO_RECHERCHE',
                    'PRO_REDACTEUR',
                    'PRO_REFERENCEUR',
                    'PRO_TEMPLATE',
                    'PRO_THEMATIQUE',
                    'PRO_TRADUCTION',
                    'PRO_FORMGEST',
                    'PRO_FORMLECT',
                    'PRO_LANGUISME',
                    'PRO_ABREVIATION',
                    'PRO_WEBROOT',
                    'PRO_WEBFLASH',
                    'PRO_WEBIMAGE',
                    'PRO_WEBVIDEO',
                    'PRO_WEBDOCUMENT',
                    'PRO_WEBVIDEOEXTERNE',
                    'PRO_WEBLIENEXTERNE',
                    'PRO_WEBWIDGET',
                    'PRO_WEBMUSIC',
                    'PRO_VALIDATEUR'
                ))) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Charge les sites disponibles pour l'utilisateur
     *
     * @since 5.6
     *
     * @return Utilisateur
     */
    public function initSites()
    {
        $sql = "select ID_ROLE from ROLE where PRO_CODE='PRO_ROOT' and ID_UTILISATEUR=" . $this->getID();
        if ($this->dbh->query($sql)->fetchColumn()) {
            foreach ($this->dbh->query('select SIT_CODE, SIT_LIBELLE from DD_SITE order by SIT_LIBELLE') as $rowSITE) {
                $this->_aSite[$rowSITE['SIT_CODE']] = $rowSITE['SIT_LIBELLE'];
            }
            $this->_aSiteAll = $this->_aSite;
        } else {
            require_once CLASS_DIR . 'class.db_alerte.php';
            require_once CLASS_DIR . 'class.db_site.php';
            $oSite = new Site($this->getField('SIT_CODE'));
            $availableSites = array_merge(array(
                $this->getField('SIT_CODE') => $oSite
            ), $oSite->getSharedSites());
            $availableSites = array_map(array(
                $this->dbh,
                'quote'
            ), array_keys($availableSites));
            $sql = 'select distinct(DD_SITE.SIT_CODE), SIT_LIBELLE, PRO_CODE from ROLE
                inner join DD_SITE on ROLE.SIT_CODE=DD_SITE.SIT_CODE
                where ID_UTILISATEUR=' . $this->getID() . ' and DD_SITE.SIT_CODE in (' . implode(',', $availableSites) . ')
                order by SIT_LIBELLE';
            foreach ($this->dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $rowSITE) {
                $oAlerte = new Alerte($rowSITE['SIT_CODE']);
                if (! $oAlerte->exist() || ! $oAlerte->isLocked() || $rowSITE['PRO_CODE'] == 'PRO_ROOT_SITE') {
                    $this->_aSite[$rowSITE['SIT_CODE']] = $rowSITE['SIT_LIBELLE'];
                }
                $this->_aSiteAll[$rowSITE['SIT_CODE']] = $rowSITE['SIT_LIBELLE'];
            }
        }

        return $this;
    }

    /**
     * Retourne tous les sites disponibles pour l'utilisateur
     *
     * @since 5.6
     *
     * @return array
     */
    public function getSites($all = false)
    {
        if ($all) {
            return $this->_aSiteAll;
        } else {
            return $this->_aSite;
        }
    }

    /**
     * Renvoie les profils de l'utilisateur
     * Si ID_PAGE vaut null :
     * la clé est le PRO_CODE
     * la valeur est tableau d'identifiant de page si il s'agit d'un profil 'PAGE', sinon la valeur vaut true
     * Si ID_PAGE est différent de null
     * la valeur est le PRO_CODE
     *
     * @since 5.6
     *
     * @param  int   $ID_PAGE
     *                        uniquement les profils concernés par cette page
     * @return array
     */
    public function getProfils($ID_PAGE = null)
    {
        if ($ID_PAGE == null) {
            return $this->_aProfil;
        }
        $_a = array();
        foreach ($this->_aProfil as $PRO_CODE => $aPage) {
            if (@in_array($ID_PAGE, $aPage)) {
                $_a[] = $PRO_CODE;
            }
        }

        return array_unique($_a);
    }

    /**
     * Chargement des profils utilisateurs
     *
     * @since 5.6
     *
     * @param  string      $SIT_CODE
     * @return Utilisateur
     */
    public function initProfils($SIT_CODE)
    {
        require_once CLASS_DIR . 'class.db_page.php';

        $tab = array();
        require_once CLASS_DIR . 'class.db_site.php';
        // Filtre sur les sites ou l'utilisateurs a accès
        $oSite = new Site($this->getField('SIT_CODE'));
        $availableSites = array_merge(array(
            $this->getField('SIT_CODE') => $oSite
        ), $oSite->getSharedSites());
        $availableSites = array_map(array(
            $this->dbh,
            'quote'
        ), array_keys($availableSites));
        $stmt = $this->dbh->prepare('select ROLE.*, PRO_PAGE from ROLE
            inner join DD_PROFIL on (ROLE.PRO_CODE=DD_PROFIL.PRO_CODE)
            where
                ROLE.ID_UTILISATEUR=:ID_UTILISATEUR
                and (
                    (ROLE.SIT_CODE=:SIT_CODE and ROLE.SIT_CODE in (' . implode(',', $availableSites) . '))
                    or
                    ROLE.SIT_CODE is null
                )
        ');
        $stmt->bindValue(':ID_UTILISATEUR', $this->getID(), PDO::PARAM_INT);
        $stmt->bindValue(':SIT_CODE', $SIT_CODE, PDO::PARAM_STR);
        $stmt->execute();

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if ($row['PRO_CODE'] == 'PRO_ROOT' || $row['PRO_CODE'] == 'PRO_ROOT_SITE') {
                $stmt = $this->dbh->prepare("select ID_PAGE from OFF_PAGE where SIT_CODE=:SIT_CODE");
                $stmt->bindValue(':SIT_CODE', $SIT_CODE, PDO::PARAM_STR);
                $stmt->execute();
                while ($ID_PAGE = $stmt->fetchColumn()) {
                    $tab[$row['PRO_CODE']][] = $ID_PAGE;
                }
            } elseif ($row['PRO_PAGE']) {
                $tab[$row['PRO_CODE']][] = $row['ID_PAGE'];
                $oPageTemp = new Page($row['ID_PAGE']);
                $tab[$row['PRO_CODE']] = array_merge($tab[$row['PRO_CODE']], $oPageTemp->getChildrenID());
            } else {
                $tab[$row['PRO_CODE']] = true;
            }
        }
        foreach ($tab as $key => $val) {
            if (is_array($val)) {
                $tab[$key] = array_unique($val);
            }
        }
        $this->_aProfil = $tab;

        return $this;
    }

    /**
     * Initialisation des Groupe utilisateur
     *
     * @since 5.6
     *
     * @return Utilisateur
     */
    public function initGroupes()
    {
        $filtreSharedSites = '';
        require_once CLASS_DIR . 'class.db_site.php';
        $oSite = new Site($this->getField('SIT_CODE'));
        $aSharedSites = $oSite->getSharedSites();
        if (! empty($aSharedSites)) {
            $aSharedSites = array_map(array(
                $this->dbh,
                'quote'
            ), array_keys($aSharedSites));
            $filtreSharedSites = ' or GROUPE_SITE.SIT_CODE in (' . implode(',', $aSharedSites) . ')';
        }
        // Filtre sur les groupes du site d'origine et ceux ouverts à l'instant T
        $sql = 'select ID_GROUPE from GROUPE_UTILISATEUR
                where ID_UTILISATEUR=' . $this->getID() . '
                    and ID_GROUPE in (
                        select GROUPE.ID_GROUPE from GROUPE
                        left join GROUPE_SITE on GROUPE.ID_GROUPE=GROUPE_SITE.ID_GROUPE
                        where GROUPE.SIT_CODE=' . $this->dbh->quote($this->getField('SIT_CODE')) . ' ' . $filtreSharedSites . '
                    )';
        $this->_aGroupe = $this->dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN);

        return $this;
    }

    /**
     * Retourne les groupes de l'utilisateur
     *
     * @since 5.6
     *
     * @return array
     */
    public function getGroupes()
    {
        return $this->_aGroupe;
    }

    /**
     * Initialisation des pages de l'utilisateur
     *
     * @since 5.6
     *
     * @param  string      $SIT_CODE
     * @return Utilisateur
     */
    public function initPages($SIT_CODE)
    {
        require_once CLASS_DIR . 'class.db_site.php';
        $oSite = new Site($this->getField('SIT_CODE'));
        $availableSites = array_merge(array(
            $this->getField('SIT_CODE') => $oSite
        ), $oSite->getSharedSites());
        $availableSites = array_map(array(
            $this->dbh,
            'quote'
        ), array_keys($availableSites));
        // Filtre sur l'ensemble des sites (source et partagés)
        $sql = 'select gp.ID_PAGE
                from GROUPE_UTILISATEUR u
                inner join GROUPE_' . CMS::$mode . 'PAGE gp on u.ID_GROUPE = gp.ID_GROUPE
                inner join ' . CMS::$mode . 'PAGE p on gp.ID_PAGE = p.ID_PAGE
                where ID_UTILISATEUR=' . $this->getID() . '
                    and p.SIT_CODE in (' . implode(',', $availableSites) . ')';
        $this->_aPage = $this->dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN);

        return $this;
    }

    /**
     * Retourne les pages de l'utilisateur
     *
     * @since 5.6
     *
     * @return array
     */
    public function getPages()
    {
        return $this->_aPage;
    }

    /**
     * Ferme la session utilisateur
     *
     * @since 5.6
     *
     * @return void
     */
    public function logout()
    {
        $stmt = $this->dbh->prepare("update OFF_PAGE set ID_UTILISATEURVERROU=null, PAG_DATEVERROU=null where ID_UTILISATEURVERROU=:ID_UTILISATEURVERROU");
        $stmt->bindValue(':ID_UTILISATEURVERROU', $this->getID(), PDO::PARAM_INT);
        $stmt->execute();
        $LNG_CODE = $_SESSION['S_LNG_CODE'];
        session_regenerate_id(true);
        $_SESSION = array();
        $_SESSION['S_LNG_CODE'] = $LNG_CODE;
    }

    /**
     * Retourne si une session Utilisateur est activée
     * Si oui, on renseigne la propriété self::$_connected avec cette valeur
     *
     * @since 5.6
     *
     * @return bool
     */
    public static function isConnected()
    {
        if (! isset($_SESSION['S_ID_UTILISATEUR'])) {
            return false;
        }
        if (self::$_connected === null) {
            $oUtilisateur = new Utilisateur($_SESSION['S_ID_UTILISATEUR']);
            if (! $oUtilisateur->exist()) {
                $_SESSION = array();
                self::$_connected = false;
                return false;
            }
            self::$_connected = $oUtilisateur;
            self::$_connected->_restore();
        }
        return true;
    }

    /**
     * Retourne un objet Utilisateur pour la personne qui est connectée
     *
     * @since 5.6
     *
     * @return Utilisateur
     */
    public static function getConnected()
    {
        if (! self::isConnected()) {
            return false;
        }
        return self::$_connected;
    }

    /**
     * Restaure les propriétés depuis la session
     *
     * @since 5.6
     *
     * @return Utilisateur
     */
    protected function _restore()
    {
        if (isset($_SESSION['Sa_ID_GROUPE'])) {
            $this->_aGroupe = $_SESSION['Sa_ID_GROUPE'];
        }
        if (isset($_SESSION['Sa_ID_PAGE'])) {
            $this->_aPage = $_SESSION['Sa_ID_PAGE'];
        }
        if (isset($_SESSION['Sa_PRO_CODE'])) {
            $this->_aProfil = $_SESSION['Sa_PRO_CODE'];
        }
        if (isset($_SESSION['Sa_SIT_CODE_all'])) {
            $this->_aSiteAll = $_SESSION['Sa_SIT_CODE_all'];
        }
        if (isset($_SESSION['Sa_SIT_CODE'])) {
            $this->_aSite = $_SESSION['Sa_SIT_CODE'];
        }

        return $this;
    }

    /**
     * Vérifie qu'une session Utilisateur BO existe et redirige vers le formulaire
     * d'authentification si ce n'est pas le cas
     *
     * @since 5.6
     *
     * @return void
     */
    public static function checkConnected()
    {
        if (! isset($_SESSION['S_ID_UTILISATEUR'])) {
            if (strpos($_SERVER['REQUEST_URI'], 'Submit') > 0) {
                header('Location:' . SERVER_ROOT . 'cms/index.php');
            } else {
                header('Location:' . SERVER_ROOT . 'cms/index.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            }
            exit();
        } else {
            $aSite = self::getConnected()->getSites(true);
            if (empty($aSite) || ! in_array(CMS::getCurrentSite()->getID(), array_keys($aSite))) {
                header('Location:' . SERVER_ROOT . 'cms/index.php?logout=1');
                exit();
            }
        }
    }

    /**
     * Loggue l'utilisateur
     *
     * @since 5.6
     *
     * @param string $UTI_LOGIN
     *                             Identifant
     * @param string $UTI_PASSWORD
     *                             Mot de passe
     *
     * @return array Tableau contenant les info relatives au statut de la connexion :
     *               <pre>array('statut' => true) // Connexion réussie
     *               array('statut' => false) // Echec de la connexion
     *               array('statut' => 'last_attempt', 'message'= {message}) // Echec de connexion avec dernière tentative avant blocage de {$SIT_CONNECTION_TTL} minutes
     *               array('statut' => 'blocked', 'message'= {message}} // Compte bloqué durant "{X}" minutes suite à {$SIT_CONNECTION_MAX} tentatives
     *               array('statut' => 'locked', 'message'= {message}) // Compte verrouillé par un administrateur
     *               array('statut' => 'password_mustbechanged', 'message'=> {message}, 'hash' => $hash) // L'administrateur a demander de changer les mots de passe des comptes. Le $hash de controle associé est généré</pre>
     *
     */
    public static function login($UTI_LOGIN, $UTI_PASSWORD)
    {
        $dbh = DB::getInstance();
        $stmt = $dbh->prepare("select * from UTILISATEUR where UTI_LOGIN=:UTI_LOGIN");
        $stmt->bindValue(':UTI_LOGIN', $UTI_LOGIN, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        // Si utilisateur non existant
        if (empty($row)) {
            return array(
                'statut' => false
            );
        }
        // * Récupération des paramétrages d'authentification associés au site d'appartenance de l'utilisateur
        $oUserSite = new Site($row['SIT_CODE']);
        $SIT_CONNECTION_MAX = $oUserSite->getField('SIT_CONNECTION_MAX');
        $SIT_CONNECTION_TTL = $oUserSite->getField('SIT_CONNECTION_TTL');
        // */
        $aAuth_info = ! empty($row['UTI_AUTH_INFO']) ? unserialize($row['UTI_AUTH_INFO']) : array();
        $login_ip = ! empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
        $login_process = 'passed';
        // Même ordre de traitement du statut que dans "adm_utilisateurListe.php"
        if ($row['UTI_STATUT_LOCKED'] == 1) {
            return array(
                'statut' => 'locked',
                'message' => 'L\'administrateur a verrouillé votre compte. Pour plus d\'information veuillez contacter l\'administrateur du site.'
            );
            // Si le compte est bloqué et que la précédante tentative a eu lieu au cours du $SIT_CONNECTION_TTL
        } elseif (($row['UTI_STATUT_BLOCKED'] == 1) && ($aAuth_info['datetime'] > strtotime('-' . $SIT_CONNECTION_TTL . ' mins'))) {
            $dt1 = date_create('@' . strtotime('-' . $SIT_CONNECTION_TTL . ' mins'));
            $dt2 = date_create('@' . $aAuth_info['datetime']);
            $interval = date_diff($dt1, $dt2);
            return array(
                'statut' => 'blocked',
                'message' => 'Vous venez de faire au moins ' . $SIT_CONNECTION_MAX . ' erreurs de connexion, votre compte est bloqué durant ' . $interval->format('%I minute(s) et %S seconde(s)') . '.
                    Vous pouvez également contacter l\'administrateur de la plateforme pour débloquer votre compte plus rapidement.'
            );
        }
        // Authentification LDAP
        if ($row['ID_LDAP']) {
            require_once CLASS_DIR . 'class.db_ldap.php';
            $oLdap = new LDAP($row['ID_LDAP']);
            if (extension_loaded('ldap') && $oLdap->exist()) {
                $ldapHostname = $oLdap->getField('LDA_HOST');
                if ($oLdap->getField('LDA_LDAPS') && (strpos($ldapHostname, 'ldaps://') === false)) {
                    $ldapHostname = 'ldaps://' . $ldapHostname;
                } elseif (strpos($ldapHostname, 'ldap://') === false) {
                    $ldapHostname = 'ldap://' . $ldapHostname;
                }
                $ldapConn = @ldap_connect($ldapHostname, $oLdap->getField('LDA_PORT')) or die("Connexion impossible à l\'annuaire LDAP");
                ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3);
                if ($oLdap->getField('LDA_LDAPS')) {
                    @ldap_start_tls($ldapConn);
                }
                // Construction des $ldapLogin et $ldapPwd pour la pré-authentification
                $ldapLogin = $ldapPwd = null;
                if ($oLdap->getField('LDA_ACCOUNT') && (strpos($oLdap->getField('LDA_ACCOUNT'), '$login') !== false)) {
                    $ldapLogin = str_replace('$login', ldap_escape($UTI_LOGIN), $oLdap->getField('LDA_ACCOUNT'));
                    $ldapPwd = $UTI_PASSWORD;
                } else {
                    $ldapLogin = $oLdap->getField('LDA_ACCOUNT');
                    $ldapPwd = $oLdap->getField('LDA_PASSWORD');
                }
                // Si pré-authentification réalisée avec succès
                if (@ldap_bind($ldapConn, $ldapLogin, $ldapPwd)) {
                    $ldapBaseDN = $oLdap->getField('LDA_BASEDN');
                    $ldapAttrLogin = $oLdap->getField('LDA_ATTRLOGIN');
                    // On considère que cette pré-authentification n'est pas suffisante si
                    // * LDA_BASEDN et LDA_ATTRLOGIN sont renseignés
                    // * ou que cette pré-authentification a été réalisée avec les infos "compte" définies pour l'annuaire (sans la délégation à l'utilisateur via le mot-clé "$login")
                    if ((! empty($ldapBaseDN) && ! empty($ldapAttrLogin)) || (($ldapLogin == $oLdap->getField('LDA_ACCOUNT')) && ($ldapPwd == $oLdap->getField('LDA_PASSWORD')))) {
                        // Si pas de DN de base ou d'attribut identifiant, echec de l'authenfication
                        if (empty($ldapBaseDN) || empty($ldapAttrLogin)) {
                            $login_process = 'aborted';
                        }
                        if ($oLdap->getField('LDA_FILTER')) {
                            $ldapFilter = '(&' . $oLdap->getField('LDA_FILTER') . ' (' . $ldapAttrLogin . '=' . ldap_escape($UTI_LOGIN) . '))';
                        } else {
                            $ldapFilter = $ldapAttrLogin . '=' . ldap_escape($UTI_LOGIN);
                        }
                        try {
                            $ldapSearch = ldap_search($ldapConn, $ldapBaseDN, $ldapFilter, array(
                                "dn"
                            ), 0, 1);
                        } catch (Exception $e) {
                            $ldapSearch = false;
                        }
                        if ($ldapSearch) {
                            $ldapResults = ldap_get_entries($ldapConn, $ldapSearch);
                            $ldapUserDN = $ldapResults[0]['dn'];
                            if (! @ldap_bind($ldapConn, $ldapUserDN, $UTI_PASSWORD)) {
                                $login_process = 'failled';
                            }
                        } else {
                            $login_process = 'aborted';
                        }
                    }
                    // Echec de la pré-authentification
                } else {
                    $login_process = 'failled';
                }
                // Impossibilité de tenter l'authentification
            } else {
                $login_process = 'aborted';
            }
        } elseif (! password_verify($UTI_PASSWORD, $row['UTI_PASSWORD'])) {
            // Si migration de l'algo de hachage
            if (defined('AUTHHASH_MIGRATED') && AUTHHASH_MIGRATED) {
                // Et que le MD5 correspond
                if (md5($UTI_PASSWORD) == $row['UTI_PASSWORD']) {
                    // On génère un nouveau hash et on le met jour
                    $hash = password_hash($UTI_PASSWORD, PASSWORD_BCRYPT);
                    $stmt = $dbh->prepare("update UTILISATEUR set UTI_PASSWORD=:UTI_PASSWORD where ID_UTILISATEUR=:ID_UTILISATEUR");
                    $stmt->bindValue(':UTI_PASSWORD', $hash, PDO::PARAM_INT);
                    $stmt->bindValue(':ID_UTILISATEUR', $row['ID_UTILISATEUR'], PDO::PARAM_INT);
                    $stmt->execute();
                } else {
                    $login_process = 'failled';
                }
            } else {
                $login_process = 'failled';
            }
        }
        // Traitement du statut de la tentative de connexion
        switch ($login_process) {
            // Avortée
            case 'aborted':
                return array(
                    'statut' => false
                );
                break;
            // Echouée
            case 'failled':
                // Incrémentation du nombre de tentative de connexion et récupération de l'IP
                $aAuth_info['attempt_count'] = is_numeric($aAuth_info['attempt_count']) ? $aAuth_info['attempt_count'] + 1 : 1;
                $aAuth_info['attempt_ip'] = $login_ip;
                // Si le nombre de tentative est strictement supérieur au $SIT_CONNECTION_MAX,
                // c'est qu'il s'agit d'une nouvelle tentative de connexion après un blocage de $SIT_CONNECTION_TTL minutes
                // Dans ce cas, on ré-initialise le "attempt_count" à 1
                if ($aAuth_info['attempt_count'] > $SIT_CONNECTION_MAX) {
                    $aAuth_info['attempt_count'] = 1;
                }
                $statut_blocked = 0;
                // Si l'utilisateur atteint le nb de tentative max, on bloc de l'utilisateur
                if ($aAuth_info['attempt_count'] == $SIT_CONNECTION_MAX) {
                    $statut_blocked = 1;
                    $aAuth_info['datetime'] = time();
                    require_once CLASS_DIR . 'class.db_historique.php';
                    CMS::setCurrentSite($oUserSite);
                    Historique::historizeAdmin('MODIFICATION', 'UTILISATEUR', $row['ID_UTILISATEUR'], 'Blocage du compte depuis l\'IP ' . $login_ip);
                }
                $stmt = $dbh->prepare("update UTILISATEUR set UTI_STATUT_BLOCKED=:UTI_STATUT_BLOCKED,
                        UTI_AUTH_INFO=:UTI_AUTH_INFO
                        where ID_UTILISATEUR=:idtf");
                $stmt->bindValue(':UTI_STATUT_BLOCKED', $statut_blocked, PDO::PARAM_INT);
                $stmt->bindValue(':UTI_AUTH_INFO', serialize($aAuth_info), PDO::PARAM_STR);
                $stmt->bindValue(':idtf', $row['ID_UTILISATEUR'], PDO::PARAM_INT);
                $stmt->execute();

                if ($aAuth_info['attempt_count'] == ($SIT_CONNECTION_MAX - 1)) {
                    return array(
                        'statut' => 'last_attempt',
                        'message' => 'A la prochaine erreur d\'identification, votre compte sera bloqué pendant ' . $SIT_CONNECTION_TTL . ' minutes.'
                    );
                } elseif ($statut_blocked == 1) {
                    return array(
                        'statut' => 'blocked',
                        'message' => 'Vous venez de faire au moins ' . $SIT_CONNECTION_MAX . ' erreurs de connexion, votre compte est bloqué durant ' . $SIT_CONNECTION_TTL . ' minutes.
                            Vous pouvez également contacter l\'administrateur de la plateforme pour débloquer votre compte plus rapidement.'
                    );
                } else {
                    return array(
                        'statut' => false
                    );
                }
                break;
            // Réussie
            case 'passed':
                // Si le mot de passe doit être changé
                if ($row['UTI_PWD_MUSTBECHANGED']) {
                    // Génération et enregistrement d'une clé de hachage unique valable 2h
                    $hash = hash('sha256', time() . $row['ID_UTILISATEUR'] . $row['UTI_LOGIN']);
                    $aAuth_info = array(
                        "recoveryHash" => $hash,
                        "recoveryExpire" => strtotime('+2 hours'),
                        "datetime" => time()
                    );
                    // Mise à jour de la clé de hachage et éventuel déblocage du compte
                    $sql = "UPDATE UTILISATEUR SET UTI_STATUT_BLOCKED=0, UTI_AUTH_INFO=" . $dbh->quote(serialize($aAuth_info)) . " WHERE ID_UTILISATEUR=" . $dbh->quote($row['ID_UTILISATEUR']);
                    $dbh->exec($sql);
                    return array(
                        'statut' => 'password_mustbechanged',
                        'message' => 'L\'administrateur du site demande la modification des mots de passe. Merci de bien vouloir actualiser votre mot de passe pour poursuivre votre navigation.',
                        'hash' => $hash
                    );
                }
                // Si présence de précédentes info d'authentification
                // ou si utilisateur précédement bloqué, on réinitilise l'ensemble
                if (! empty($aAuth_info)) {
                    $stmt = $dbh->prepare("update UTILISATEUR set UTI_STATUT_BLOCKED=:UTI_STATUT_BLOCKED,
                            UTI_AUTH_INFO=:UTI_AUTH_INFO
                        where ID_UTILISATEUR=:idtf");
                    $stmt->bindValue(':UTI_STATUT_BLOCKED', 0, PDO::PARAM_INT);
                    $stmt->bindValue(':UTI_AUTH_INFO', null, PDO::PARAM_STR);
                    $stmt->bindValue(':idtf', $row['ID_UTILISATEUR'], PDO::PARAM_INT);
                    $stmt->execute();
                    if ($row['UTI_STATUT_BLOCKED'] == 1) {
                        require_once CLASS_DIR . 'class.db_historique.php';
                        CMS::setCurrentSite($oUserSite);
                        Historique::historizeAdmin('MODIFICATION', 'UTILISATEUR', $row['ID_UTILISATEUR'], 'Déblocage du compte  depuis l\'IP ' . $login_ip);
                    }
                }
                break;
        }
        // Mise à jour de la date de dernière (et avant denrière) connexion
        if (version_compare(CMS::getVersion(), '7.0.2dev1', '>=')) {
            $stmt = $dbh->prepare("update UTILISATEUR set UTI_PRELASTCONNEXION=UTI_LASTCONNEXION, UTI_LASTCONNEXION=:UTI_LASTCONNEXION where ID_UTILISATEUR=:ID_UTILISATEUR");
            $stmt->bindValue(':UTI_LASTCONNEXION', time(), PDO::PARAM_INT);
            $stmt->bindValue(':ID_UTILISATEUR', $row['ID_UTILISATEUR'], PDO::PARAM_INT);
            $stmt->execute();
        }

        // Initialisation des différentes information de connexion
        setcookie('C_LNG_CODE', $row['LNG_CODE'], time() + 86400 * 365, '/');

        self::$_connected = new self($row['ID_UTILISATEUR']);
        self::$_connected->setFields($row);
        self::$_connected->initSites();

        session_regenerate_id(true);
        $_SESSION['S_ID_UTILISATEUR'] = self::$_connected->getID();
        $_SESSION['S_LNG_CODE'] = self::$_connected->getField('LNG_CODE');
        $_SESSION['Sa_SIT_CODE_all'] = self::$_connected->getSites(true);
        $_SESSION['Sa_SIT_CODE'] = self::$_connected->getSites(false);

        return array(
            'statut' => true
        );
    }

    /**
     * Initialise la session de l'utilisateur avec ses propriétés
     *
     * @since 5.6
     *
     * @param  string      $SIT_CODE
     * @return Utilisateur
     */
    public function initSession($SIT_CODE, $resetAll = true)
    {
        $this->initSites();
        $this->initProfils($SIT_CODE);
        $this->initGroupes();
        $this->initPages($SIT_CODE);

        $idHistorize = '';
        if (isset($_SESSION['S_ID_HISTORIQUE'][$SIT_CODE]) && is_numeric($_SESSION['S_ID_HISTORIQUE'][$SIT_CODE])) {
            $idHistorize = $_SESSION['S_ID_HISTORIQUE'][$SIT_CODE];
        } elseif (version_compare(CMS::getVersion(), '6.1.0dev1', '>=')) {
            $idHistorize = $this->hasHistorique($SIT_CODE);
        }

        if ($resetAll) {
            $_SESSION = array();
        }
        $_SESSION['S_ID_UTILISATEUR'] = $this->getID();
        $_SESSION['S_UTI_LOGIN'] = $this->getField('UTI_LOGIN');
        $_SESSION['S_LNG_CODE'] = $this->getField('LNG_CODE');
        $_SESSION['Sa_SIT_CODE'] = $this->getSites(false);
        $_SESSION['Sa_SIT_CODE_all'] = $this->getSites(true);
        $_SESSION['Sa_ID_GROUPE'] = $this->getGroupes();
        $_SESSION['Sa_ID_PAGE'] = $this->getPages();
        $_SESSION['Sa_PRO_CODE'] = $this->getProfils();
        $_SESSION['S_CONST']['SIT_CODE'] = $SIT_CODE;

        if (empty($idHistorize) && version_compare(CMS::getVersion(), '6.1.0dev1', '>=')) {
            $idHistorize = $this->historize($SIT_CODE);
        }
        $_SESSION['S_ID_HISTORIQUE'][$SIT_CODE] = $idHistorize;
        return $this;
    }

    /**
     * Vérifie si la clé générée par une demande d'activation de compte est valide et non expiré
     *
     * @param string $hash
     *                     Hash de type sha256
     *
     * @return boolean true si le hash est valide et non expiré false si non
     *
     */
    public static function checkRecoveryKey($hash)
    {
        $oUtilisateur = self::getUtilisateurByRecoveryKey($hash);
        $aAuth_info = unserialize($oUtilisateur->getField('UTI_AUTH_INFO'));
        return (time() <= $aAuth_info['recoveryExpire']);
    }

    /**
     * Retourne l'objet $Utilisateur correspondant à une clé de hachage générée précédement
     *
     * @param string $hash
     *                     Hash de type sha256
     *
     * @throws E_USER_WARNING La clé de sécurité "recoveryKey" n'est pas valide.
     * @throws E_USER_WARNING Le lien fourni dans le mail n’est pas ou plus valide. Cliquez sur « mot de passe perdu » pour générer un nouveau lien.
     *
     * @return Utilisateur Utilisateur corespondant au hash
     *
     */
    public static function getUtilisateurByRecoveryKey($hash)
    {
        if (strlen($hash) != 64) {
            throw new Exception("Le lien fourni dans le mail n’est pas valide. Cliquez sur « mot de passe perdu » pour générer un nouveau lien.", E_USER_WARNING);
        }
        $dbh = DB::getInstance();
        $stmt = $dbh->prepare("select * from UTILISATEUR where UTI_AUTH_INFO like :UTI_AUTH_INFO");
        $stmt->bindValue(':UTI_AUTH_INFO', 'a:3:{s:12:"recoveryHash";s:64:"' . $hash . '"%', PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        if (! empty($row)) {
            $oUtilisateur = new self($row['ID_UTILISATEUR']);
            $oUtilisateur->setFields($row);
            return $oUtilisateur;
        } else {
            throw new Exception("Le lien fourni dans le mail n’est plus valide. Cliquez sur « mot de passe perdu » pour générer un nouveau lien.", E_USER_WARNING);
        }
    }

    /**
     * Génère une demande de notification de changement de mot de passe
     *
     * @param boolean $activateOnlyNotification
     *                                          [optional] Ne doit correspondre qu'a une demande d'activation
     *
     * @throws E_USER_WARNING La demande de notification [de changement de mot de passe|d'activation de compte] ne peut être traitée car le compte est associé à un annuaire LDAP. Veuillez contacter l’administrateur du site.
     * @throws E_USER_WARNING La demande de notification [de changement de mot de passe|d'activation de compte] ne peut être traitée car le compte est verrouillé. Veuillez contacter l’administrateur du site.
     * @throws E_USER_WARNING L'activation du compte ne peut pas être réalisée car le compte a déjà été activé.
     * @throws E_USER_WARNING Le compte ne peut pas être activé car aucun rôle lié à la contribution ne lui est attribué.
     * @throws E_USER_WARNING Le compte ne peut pas être activé pour l'une des deux raisons suivantes.Il s'agit d'un compte back-office, aucun rôle lié à la contribution ne lui être attribué. Il s'agit d'un compte front-office, la page spéciale "Authentification" n'est pas en ligne.
     *
     * @return void
     *
     */
    public function generateRecoveryPwdNotification($activateOnlyNotification = false)
    {
        if ($this->getField('UTI_LASTCONNEXION')) {
            $recoveryType = 'forgotten';
            $typeDemande = 'de changement de mot de passe';
        } else {
            $recoveryType = 'activate';
            $typeDemande = 'd\'activation de compte';
        }

        if ($this->getField('UTI_STATUT_LOCKED')) {
            throw new Exception("La demande de notification " . $typeDemande . " ne peut être traitée car le compte est verrouillé. Veuillez contacter l’administrateur du site.", E_USER_WARNING);
        } elseif ($this->getField('ID_LDAP')) {
            throw new Exception("La demande de notification " . $typeDemande . " ne peut être traitée car le compte est associé à un annuaire LDAP. Veuillez contacter l’administrateur du site.", E_USER_WARNING);
        } elseif ($activateOnlyNotification && $this->getField('UTI_PASSWORD')) {
            throw new Exception("L'activation du compte ne peut pas être réalisée car le compte a déjà été activé.", E_USER_WARNING);
        }
        // Récupération du site courant ou du site d'origine si site courant non existant
        if (! $oSite = CMS::getCurrentSite()) {
            $oSite = new Site($this->getField('SIT_CODE'));
            // Pour éviter une erreur sur la récupération du lien FO depuis une demande réalisée depuis le BO (par un utilisateur FO)
            CMS::setCurrentSite($oSite);
        }
        // Contrôle de la possibilité d'activer ou de générer un nouveaux accès pour le compte "Interne"
        $bRecoveryToBO = $bRecoveryToFO = false;
        // Récupération des infos du compte (contributeur ou utilisateur FO uniquement ?)
        $this->initSites();
        $this->initProfils($oSite->getID());
        $this->initGroupes();
        $this->initPages($oSite->getID());
        $aProfils = $this->getProfils();
        // Si s'agit d'un contributeur, la génération du mot de passe est réalisée via le BO uniquement
        if ($this->isRoot() || $this->isPageContributor() || ! empty($aProfils)) {
            $bRecoveryToBO = true;
        } else {
            $sCompteInfo = 'Le compte ne peut pas être activé car aucun rôle lié à la contribution ne lui est attribué.';
            $ModuleExtranet = new module("MOD_EXTRANET");
            // S'il s'agit uniquement d'un contributeur FO, la procédure passe par la mise en place de la page spéciale "Authentification" de son site d'appartenance
            if ($oSite->hasModule($ModuleExtranet) && ! $oSite->getSpecialePage('PGS_AUTHENTIFICATION', 'ON_')) {
                $sCompteInfo = 'Le compte ne peut pas être activé pour l\'une des deux raisons suivantes.Il s\'agit d\'un compte back-office, aucun rôle lié à la contribution ne lui être attribué. Il s\'agit d\'un compte front-office, la page spéciale "Authentification" n\'est pas en ligne.';
            } elseif ($AuthPage = $oSite->getSpecialePage('PGS_AUTHENTIFICATION', 'ON_')) {
                $bRecoveryToFO = true;
            }
        }
        if (! $bRecoveryToBO && ! $bRecoveryToFO) {
            throw new Exception($sCompteInfo, E_USER_WARNING);
        }
        // Génération et enregistrement d'une clé de hachage unique valable 2h
        $hash = hash('sha256', time() . $this->getID() . $this->getField('UTI_LOGIN'));
        $aAuth_info = array(
            "recoveryHash" => $hash,
            "recoveryExpire" => strtotime('+2 hours'),
            "datetime" => time()
        );
        // Mise à jour de la clé de hachage et éventuel déblocage du compte (après blocage, l'utilisateur peut demander à changer son pwd)
        $sql = "UPDATE UTILISATEUR SET UTI_STATUT_BLOCKED=0, UTI_AUTH_INFO=" . $this->dbh->quote(serialize($aAuth_info)) . " WHERE ID_UTILISATEUR=" . $this->dbh->quote($this->getID());
        $this->dbh->exec($sql);
        // * Génération du lien d'activation
        if ($bRecoveryToBO) {
            $sLienActivation = 'http://' . CMS::getCurrentSite()->getField('SIT_HOST') . SERVER_ROOT . 'cms/index.php?recoveryKey=' . urlencode($hash) . '&recoveryType=' . urlencode($recoveryType);
        } else {
            // :-( Pas d'appel à "$AuthPage->getURLESCAPE()" afin d'avoir un lien valide au sien du mail au format TXT
            $sLienActivation = $AuthPage->getURL(array(
                'recoveryKey' => $hash,
                'recoveryType' => $recoveryType
            ));
            if (strpos($sLienActivation, '://') === false) {
                $sLienActivation = 'http://' . CMS::getCurrentSite()->getField('SIT_HOST') . $sLienActivation;
            }
        }
        require_once CLASS_DIR . 'class.CMSMailer.php';
        $EMT_CODE = $this->getField('UTI_LASTCONNEXION') ? 'EMT_EXTRANET_ACCOUNT_FORGOTTEN' : 'EMT_EXTRANET_ACCOUNT_ACTIVATE';
        $oMail = new CMSMailer($EMT_CODE);
        $oMail->replace('[UTI_CIVILITE]', $this->getField('UTI_CIVILITE'));
        $oMail->replace('[UTI_NOM]', $this->getField('UTI_NOM'));
        $oMail->replace('[UTI_PRENOM]', $this->getField('UTI_PRENOM'));
        $oMail->replace('[UTI_LOGIN]', $this->getField('UTI_LOGIN'));
        $oMail->replace('[LIEN_ACTIVATION]', '<a href="' . $sLienActivation . '">cliquer sur ce lien</a>');
        $oMail->AddAddress($this->getField('UTI_EMAIL'));
        $oMail->send();
    }

    /**
     * Après vérification, met à jour le mot de passe d'un utilisateur à partir de la clé de hachage, du login et du mot de passe souhaité
     *
     * @param string $recoveryKey
     *                              Clé de hachage correspondant au compte à mettre à jour
     * @param string $login
     *                              Indentifant du compte à mettre à jour
     * @param string $newPwd
     *                              Nouveau mot de passe
     * @param string $newPwdConfirm
     *                              Confirmation du mot nouveau mot de passe
     *
     * @throws E_USER_WARNING Le délais de validité de la demande de changement de mot de passe est dépassé. Veuillez renouveler votre demande.
     * @throws E_USER_WARNING L’identifiant que vous avez saisi ne correspond pas à celui fourni dans le mail d’activation ou de changement de mot de passe. Veuillez saisir le bon identifiant attendu.
     * @throws E_USER_WARNING La demande [de changement de mot de passe |d'activation de compte] ne peut être réalisée car le compte est associé à un annuaire LDAP. Veuillez contacter l’administrateur du site.
     * @throws E_USER_WARNING La demande [de changement de mot de passe |d'activation de compte] ne peut être réalisée car le compte est verrouillé. Veuillez contacter l’administrateur du site.
     * @throws E_USER_WARNING La confirmation du mot de passe doit être identique au mot de passe.
     * @throws E_USER_WARNING Le nouveau mot de passe doit être différent du précédent.
     *
     * @return void
     */
    public static function setRecoveryPwd($recoveryKey, $login, $newPwd, $newPwdConfirm)
    {
        if (! self::checkRecoveryKey($recoveryKey)) {
            throw new Exception('Le délais de validité de la demande de changement de mot de passe est dépassé. Veuillez renouveler votre demande.', E_USER_WARNING);
        }
        // Récupération de l'utilisateur ayant réaliser une demande
        $oUtilisateur = self::getUtilisateurByRecoveryKey($recoveryKey);
        // Controle que le login corresponde au login associé au hash
        if ($login != $oUtilisateur->getField('UTI_LOGIN')) {
            throw new Exception("L’identifiant que vous avez saisi ne correspond pas à celui fourni dans le mail d’activation ou de changement de mot de passe. Veuillez saisir le bon identifiant attendu.", E_USER_WARNING);
        }
        if ($oUtilisateur->getField('UTI_LASTCONNEXION')) {
            $recoveryType = 'forgotten';
            $typeDemande = 'de changement de mot de passe';
        } else {
            $recoveryType = 'activate';
            $typeDemande = 'd\'activation de compte';
        }
        if ($oUtilisateur->getField('UTI_STATUT_LOCKED')) {
            throw new Exception("La demande " . $typeDemande . " ne peut être réalisée car le compte est verrouillé. Veuillez contacter l’administrateur du site.", E_USER_WARNING);
        } elseif ($oUtilisateur->getField('ID_LDAP')) {
            throw new Exception("La demande " . $typeDemande . " ne peut être réalisée car le compte est associé à un annuaire LDAP. Veuillez contacter l’administrateur du site.", E_USER_WARNING);
        }
        if ($newPwd != $newPwdConfirm) {
            throw new Exception("La confirmation du mot de passe doit être identique au mot de passe.", E_USER_WARNING);
        }
        // Si l'utilisateur à déjà un mot de passe, le nouveau doit être différent
        if ($oUtilisateur->getField('UTI_PASSWORD')) {
            if (crypt($newPwd, $oUtilisateur->getField('UTI_PASSWORD')) == $oUtilisateur->getField('UTI_PASSWORD')) {
                throw new Exception("Le nouveau mot de passe doit être différent du précédent.", E_USER_WARNING);
            }
        }
        if (! preg_match('/^' . self::getValidateRegPatternPwd() . '$/', $newPwd)) {
            throw new Exception(self::getValidatePwdHelper(), E_USER_WARNING);
        }
        $dbh = DB::getInstance();
        // On génère un nouveau hash et on le met jour
        $hash = password_hash($newPwd, PASSWORD_BCRYPT);
        $stmt = $dbh->prepare("update UTILISATEUR set UTI_PASSWORD=:UTI_PASSWORD, UTI_PWD_MUSTBECHANGED=0 where ID_UTILISATEUR=:ID_UTILISATEUR");
        $stmt->bindValue(':UTI_PASSWORD', $hash, PDO::PARAM_INT);
        $stmt->bindValue(':ID_UTILISATEUR', $oUtilisateur->getID(), PDO::PARAM_INT);
        $stmt->execute();
        // On ajoute une entrée dans l'historique
        require_once CLASS_DIR . 'class.db_historique.php';
        if (! CMS::getCurrentSite()) {
            $oUserSite = new Site($oUtilisateur->getField('SIT_CODE'));
            CMS::setCurrentSite($oUserSite);
        }
        Historique::historizeAdmin('MODIFICATION', 'UTILISATEUR', $oUtilisateur->getID(), ($recoveryType == 'forgotten' ? 'Changement de mot de passe' : 'Activation de compte'));
    }

    /**
     * Retourne le parttern de l'expression régulière associée au contrôle des mots de passe
     *
     * @return string
     */
    public static function getValidateRegPatternPwd()
    {
        return self::$_validateRegPatternPwd;
    }

    /**
     * Retourn le message décrivant le parttern de l'expression régulière associée au contrôle des mots de passe
     *
     * @return string
     */
    public static function getValidatePwdHelper()
    {
        return self::$_validatePwdHelper;
    }

    /**
     * Définition du parttern de l'expression régulière associée au contrôle des mots de passe
     *
     * @param string $s
     *
     * @return void
     */
    public static function setValidateRegPatternPwd($s)
    {
        self::$_validateRegPatternPwd = $s;
    }

    /**
     * Définition du message décrivant l'expression régulière associée au contrôle des mots de passe
     *
     * @param string $s
     *
     * @return void
     */
    public static function setValidatePwdHelper($s)
    {
        self::$_validatePwdHelper = $s;
    }

    /**
     * Retourne un tableau associatif (à trois entrées : $array['LIA_CODE'][['ID_LIAISON']['LIA_LIBELLE']) contenant les éventuelles référants d'un élément utilisateur
     *
     * @param  boolean $inSIT_CODE
     *                             La recherche des référants doit-elle être fait sur le SIT_CODE (=> true) ou sur les autres sites (=> false)
     * @return array   tableau associatif contenant les liaisons de l'élément
     */
    public function getReferants($inSIT_CODE = true)
    {
        $sql = ($inSIT_CODE)
        ? "select l.LIA_CODE, ID_LIAISON, LIA_LIBELLE from LIAISON_UTILISATEUR l
                inner join UTILISATEUR u on l.ID_UTILISATEUR = u.ID_UTILISATEUR
                left join DD_LIAISON dd on l.LIA_CODE=dd.LIA_CODE
                where u.ID_UTILISATEUR=" . $this->getID() . " and SIT_CODE=" . $this->dbh->quote(CMS::getCurrentSite()->getID())
        : "select l.LIA_CODE, ID_LIAISON, SIT_CODE, LIA_LIBELLE from LIAISON_UTILISATEUR l
                inner join UTILISATEUR u on l.ID_UTILISATEUR = u.ID_UTILISATEUR
                left join DD_LIAISON dd on l.LIA_CODE=dd.LIA_CODE
                where u.ID_UTILISATEUR=" . $this->getID() . " and SIT_CODE<>" . $this->dbh->quote(CMS::getCurrentSite()->getID());

        return $this->dbh->query($sql)->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);
    }

    /**
     * Retourne un identifiant correspondant à une session de connexion
     *
     * @param string $SIT_CODE,
     *                            identifiant du site
     * @param string $HIS_DETAIL,
     *                            précision sur l'action effectuée
     */
    public function historize($SIT_CODE, $HIS_DETAIL = '')
    {
        $stmt = $this->dbh->prepare("insert into HISTORIQUE_UTILISATEUR (
            SIT_CODE,
            ID_UTILISATEUR,
            HIS_DETAIL,
            HIS_DATE
            ) values(
            :SIT_CODE,
            :ID_UTILISATEUR,
            :HIS_DETAIL,
            :HIS_DATE
            )");

        $stmt->bindValue(':SIT_CODE', $SIT_CODE, PDO::PARAM_STR);
        $stmt->bindValue(':ID_UTILISATEUR', $this->getID(), PDO::PARAM_INT);
        $stmt->bindValue(':HIS_DETAIL', $HIS_DETAIL, PDO::PARAM_STR);
        $stmt->bindValue(':HIS_DATE', time(), PDO::PARAM_INT);
        $stmt->execute();
        return $this->dbh->lastInsertID();
    }

    /**
     *
     * Fonction permettant de retourner l'identifiant de session de connexion d'utilisateur si celui-ci s'est connecté moins d'une heure avant
     *
     * @param string $SIT_CODE
     *                         : Identifiant du site sur lequel l'utilisateur se connecte
     */
    public function hasHistorique($SIT_CODE)
    {
        $sql = "select ID_HISTORIQUE_UTILISATEUR
                from HISTORIQUE_UTILISATEUR
                where ID_UTILISATEUR = " . $this->getID() . "
                and SIT_CODE = " . $this->dbh->quote($SIT_CODE) . "
                and HIS_DATE >= " . (time() - 3600);

        if ($idHistorique = $this->dbh->query($sql)->fetch(PDO::FETCH_COLUMN)) {
            return $idHistorique;
        }
        return '';
    }

    /**
     * Fonction qui met à jour le compteur d'action d'une session de connexion d'un utilisateur
     */
    static public function historizeAction()
    {
        $dbh = DB::getInstance();
        if (isset($_SESSION['S_ID_HISTORIQUE']) && CMS::getCurrentSite() && is_numeric($_SESSION['S_ID_HISTORIQUE'][CMS::getCurrentSite()->getID()])) {
            $sql = "update HISTORIQUE_UTILISATEUR set
                    HIS_NBACTION = (HIS_NBACTION+1)
                    where ID_HISTORIQUE_UTILISATEUR = " . $_SESSION['S_ID_HISTORIQUE'][CMS::getCurrentSite()->getID()];
            $dbh->exec($sql);
        }
    }
}
