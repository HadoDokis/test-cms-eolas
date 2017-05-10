<?php
require_once CLASS_DIR . 'class.db_generic.php';

/**
 * Classe parent des éléments de la Wébothèque
 *
 * @package CMS\DB\Webotheque
 */
class Webotheque extends Generic
{

    protected static $_aExtension = array(
        'WBT_IMAGE' => 'SIT_EXT_IMAGE',
        'WBT_FLASH' => 'SIT_EXT_FLASH',
        'WBT_DOCUMENT' => 'SIT_EXT_DOC',
        'WBT_VIDEO' => 'SIT_EXT_VIDEO',
        'WBT_MUSIC' => 'SIT_EXT_MUSIC'
    );

    public static $_aTraduction = array(
        'WBT_IMAGE' => 'Images',
        'WBT_DOCUMENT' => 'Documents',
        'WBT_LIENEXTERNE' => 'Liens externes',
        'WBT_FLASH' => 'Flashs',
        'WBT_VIDEO' => 'Videos',
        'WBT_VIDEOEXTERNE' => 'Videos externes',
        'WBT_WIDGET' => 'Widgets',
        'WBT_MUSIC' => 'Audios'
    );

    protected static $_aAccessExtension = array(
        'WBT_DOCUMENT' => array(
            '.txt',
            '.rtf'
        )
    );

    public static $aExtensionSousTitre = array(
        '.srt'
    );

    public static $aExtensionAudioDescription = array(
        '.mp3'
    );

    /**
     * Méthode statique d'insertion d'un élément en webothèque
     *
     * @param string $wbt_type
     * @param string $webLibelle
     * @param integer $idWebothequeCategorie
     * @param string $nomFichierFTP
     * @param boolean $deleteOnFail
     * @param string $errorMsg
     * @return boolean
     */
    public static function insertWebotheque($wbt_type, $webLibelle, $idWebothequeCategorie, $nomFichierFTP, $deleteOnFail = false, &$errorMsg = '', $md5 = '', $detail = '')
    {
        $dbh = DB::getInstance();
        $stmt = $dbh->prepare("insert into WEBOTHEQUE (
            ID_WEBOTHEQUECATEGORIE,
            SIT_CODE,
            ID_UTILISATEUR,
            WBT_CODE,
            WEB_LIBELLE,
            WEB_DATECREATION,
            WEB_DATEMODIFICATION,
            WEB_MD5
            ) values (
            :ID_WEBOTHEQUECATEGORIE,
            :SIT_CODE,
            :ID_UTILISATEUR,
            :WBT_CODE,
            :WEB_LIBELLE,
            :WEB_DATECREATION,
            :WEB_DATEMODIFICATION,
            :WEB_MD5
            )");
        $stmt->bindValue(':ID_WEBOTHEQUECATEGORIE', (is_numeric($idWebothequeCategorie)) ? $idWebothequeCategorie : null, PDO::PARAM_INT);
        $stmt->bindValue(':SIT_CODE', CMS::getCurrentSite()->getID(), PDO::PARAM_STR);
        $stmt->bindValue(':ID_UTILISATEUR', Utilisateur::getConnected()->getID(), PDO::PARAM_INT);
        $stmt->bindValue(':WBT_CODE', 'WBT_' . strtoupper($wbt_type), PDO::PARAM_STR);
        $stmt->bindValue(':WEB_LIBELLE', $webLibelle, PDO::PARAM_STR);
        $stmt->bindValue(':WEB_DATECREATION', time(), PDO::PARAM_INT);
        $stmt->bindValue(':WEB_DATEMODIFICATION', time(), PDO::PARAM_INT);
        $stmt->bindValue(':WEB_MD5', $md5, PDO::PARAM_STR);
        $stmt->execute();
        $idtf = $dbh->lastInsertID();

        // Initialisation de l'élément typé nouvellement initialisé
        $Webo_class = 'Webo_' . strtoupper($wbt_type);
        $oWebotheque = new $Webo_class($idtf);

        $oWebotheque->historize('CREATION', $detail);
        // Mise à jour de la catégorie si une nouvelle catégorie a été saisie
        $oWebotheque->updateCategorie('', '', $idWebothequeCategorie);

        // Traitements spécifiques au type de l'élément
        if (! $oWebotheque->updateSpecifique($nomFichierFTP, $errorMsg)) {
            if ($deleteOnFail) {
                $stmt = $dbh->prepare("delete from WEBOTHEQUE where ID_WEBOTHEQUE=:ID_WEBOTHEQUE");
                $stmt->bindValue(':ID_WEBOTHEQUE', $idtf, PDO::PARAM_INT);
                $stmt->execute();

                // On netoie la catégorie si besoin
                $oWebotheque->updateCategorie('', '', $idWebothequeCategorie);
            }

            return false;
        }

        return true;
    }

    public $code;

    protected $_newMD5 = null;

    private $_deletable = null;

    public function __construct($idtf = -1, $wbt_code = null)
    {
        parent::__construct($idtf);
        $this->code = $wbt_code;
    }

    public static function getModuleCode()
    {
        return 'MOD_CORE'; // pas forcément pertinent, mais il n'existe pas de module wébothèque générique
    }

    public function load()
    {
        $sql = "select * from WEBOTHEQUE where ID_WEBOTHEQUE=" . $this->getID();
        if ($this->code != null) {
            $sql .= " and WBT_CODE=" . $this->dbh->quote($this->code);
        }
        if ($row = $this->dbh->query($sql)->fetch(PDO::FETCH_ASSOC)) {
            $this->setFields($row);
        } else {
            $this->_idtf = - 1;
            $this->setFields(array());
        }
    }

    /**
     * A redéfinir si une vérification md5 est nécessaire
     *
     * @return l'identifiant d'un enregistrement identique ou faux si unique
     */
    public function checkMD5()
    {
        return false;
    }

    public function getMD5()
    {
        if (! is_null($this->_newMD5)) {
            return $this->_newMD5;
        }

        return $this->exist() ? $this->getField('WEB_MD5') : '';
    }

    /**
     * Vérifie s'il existe un autre enregistrement du même type avec la même signature
     *
     * @return l'identifiant d'un enregistrement identique ou faux si unique
     */
    protected function _checkMD5($md5)
    {
        if (CMS::getCurrentSite()->getField('SIT_CHECKMD5')) {
            $sql = "select ID_WEBOTHEQUE from WEBOTHEQUE where WBT_CODE=" . $this->dbh->quote($this->code) . " and WEB_MD5=" . $this->dbh->quote($md5) . " and SIT_CODE=" . $this->dbh->quote(CMS::getCurrentSite()->getID()) . " and ID_WEBOTHEQUE<>" . $this->getID();
            if ($idtf = $this->dbh->query($sql)->fetchColumn()) {
                return $idtf;
            }
        }
        $this->_newMD5 = $md5;

        return false;
    }

    /**
     * Génère le checksum md5
     *
     * @return String Checksum MD5
     */
    public function generateMD5()
    {
        $md5 = '';
        switch ($this->getField('WBT_CODE')) {
            case 'WBT_IMAGE':
            case 'WBT_DOCUMENT':
            case 'WBT_FLASH':
            case 'WBT_MUSIC':
            case 'WBT_VIDEO':
                $md5 = @md5_file(Webotheque::getUploadPhysicalDir($this->getField('WBT_CODE')) . $this->getField('WEB_CHEMIN'));
                break;
            default:
                $md5 = md5($this->getField('WEB_CHEMIN'));
        }

        return $md5;
    }

    /**
     * A redéfinir si des traitements spécifiques sont nécessaires avant l'enregistrement
     */
    public function preTraitement()
    {
        return false;
    }

    /**
     * A redéfinir si des traitements spécifiques sont nécessaires après l'enregistrement
     */
    public function postTraitement()
    {
        return false;
    }

    /**
     * TODO le force doit remettre à parser !!
     */
    public function delete($force = false, $deleteMultiple = false)
    {
        if (! $force && ! $this->isDeletable()) {
            return false;
        } elseif ($force) {
            $this->updateReferants();
            $this->dbh->exec('delete from LIAISON_WEBOTHEQUE where ID_WEBOTHEQUE=' . $this->getID());
        }
        require_once (CLASS_DIR . 'class.Link.php');
        Link::delete('WEBOTHEQUE', $this->getID(), 'ALL');
        $this->historize('SUPPRESSION', $deleteMultiple ? 'Suppression multiple' : '');
        $this->dbh->exec('delete from WEBOTHEQUE where ID_WEBOTHEQUE=' . $this->getID());

        return true;
    }

    public function isDeletable()
    {
        if ($this->_deletable === null) {
            if ((count($this->getReferants()) > 0) || (count($this->getReferants(false)) > 0)) {
                $this->_deletable = false;
            } else {
                $this->_deletable = true;
            }
        }

        return $this->_deletable;
    }

    /**
     * Retourne un tableau associatif (à trois entrées : $array['LIA_CODE'][['ID_LIAISON']['LIA_LIBELLE']) contenant les éventuelles référants d'un élément de la webothèque
     *
     * @param boolean $inSIT_CODE
     *            La recherche des référants doit-elle être fait sur le SIT_CODE (=> true) ou sur les autres sites (=> false)
     * @return array tableau associatif contenant les liaisons de l'élément
     */
    public function getReferants($inSIT_CODE = true)
    {
        $sql = ($inSIT_CODE) ? "select l.LIA_CODE, ID_LIAISON, LIA_LIBELLE from LIAISON_WEBOTHEQUE l
                inner join WEBOTHEQUE w on l.ID_WEBOTHEQUE = w.ID_WEBOTHEQUE
                left join DD_LIAISON dd on l.LIA_CODE=dd.LIA_CODE
                where w.ID_WEBOTHEQUE=" . $this->getID() . " and SIT_CODE=" . $this->dbh->quote(CMS::getCurrentSite()->getID()) : "select l.LIA_CODE, ID_LIAISON, SIT_CODE, LIA_LIBELLE from LIAISON_WEBOTHEQUE l
                inner join WEBOTHEQUE w on l.ID_WEBOTHEQUE = w.ID_WEBOTHEQUE
                left join DD_LIAISON dd on l.LIA_CODE=dd.LIA_CODE
                where w.ID_WEBOTHEQUE=" . $this->getID() . " and SIT_CODE<>" . $this->dbh->quote(CMS::getCurrentSite()->getID());

        return $this->dbh->query($sql)->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);
    }

    /**
     * Mise à jours des référants qui intégrent un éditeur riche et qui font référence à cet élément de la webothèque
     */
    public function updateReferants()
    {
        $sql = "select * from LIAISON_WEBOTHEQUE
            inner join DD_LIAISON on LIAISON_WEBOTHEQUE.LIA_CODE=DD_LIAISON.LIA_CODE
            where LIA_TYPE='RTE' and ID_WEBOTHEQUE = " . $this->getID();
        foreach ($this->dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (($row['LIA_CODE'] == 'OFF_PARAGRAPHE') || ($row['LIA_CODE'] == 'ON_PARAGRAPHE')) {
                $sql = "update " . $row['LIA_CODE'] . " set PAR_APARSER = 1 where ID_PARAGRAPHE = " . $row['ID_LIAISON'];
                $this->dbh->exec($sql);
            } elseif ($row['LIA_NOM_CHAMP'] != '') {
                $sql = "select * from " . $row['LIA_CODE'] . " where " . $row['LIA_NOM_CHAMP_ID'] . "=" . $this->dbh->quote($row['ID_LIAISON']);
                $rowTemp = $this->dbh->query($sql)->fetch(PDO::FETCH_ASSOC);
                $aColonne = explode('@', substr($row['LIA_NOM_CHAMP'], 1, - 1));
                foreach ($aColonne as $colonne) {
                    require_once (CLASS_DIR . 'class.Editor.php');
                    Editor::updateContent($rowTemp[$colonne], $row['LIA_CODE'], $colonne, $row['LIA_NOM_CHAMP_ID'], $row['ID_LIAISON']);
                }
            }
        }

        // Purge du cache de l'ensemble des sites
        require_once CLASS_DIR . 'class.db_page.php';
        Page::clearAllCache();
    }

    /**
     * Génère la liste des référants (qui utilisent) d'un élément de la Webothèque
     *
     * @param boolean $inSIT_CODE
     *            La recherche des référants doit-elle être fait sur le SIT_CODE (=> true) ou sur les autres sites (=> false)
     */
    public function genereReferantsListe($inSIT_CODE = true)
    {
        require_once 'class.db_page.php';
        // TODO : faire pointer le liens du référant de type paragraphe sur cms_pseudo.php?idtf=XXX
        $aReferants = $this->getReferants($inSIT_CODE);
        // S'il existe des référants
        if (count($aReferants) > 0) {
            $legend = ($inSIT_CODE) ? gettext('Affectations sur le site courant') : gettext('Affectations sur les autres sites');
            echo '<fieldset><legend>' . $legend . '</legend>';
            echo '<ul>';
            // Site de contribution
            if (isset($aReferants['OFF_PARAGRAPHE']) || isset($aReferants['OFF_PAGE'])) {
                echo '<li>' . gettext('Espace de contribution') . '<ul>';
                $ID_PAGES = array();
                if (isset($aReferants['OFF_PARAGRAPHE'])) {
                    $referants = array_values($aReferants['OFF_PARAGRAPHE']);
                    $id_referants = array();
                    foreach ($referants as $ref) {
                        $id_referants[] = $ref['ID_LIAISON'];
                    }
                    $sql = 'select distinct ID_PAGE from OFF_PARAGRAPHE where ID_PARAGRAPHE in (' . implode(',', $id_referants) . ')';
                    $ID_PAGES = array_keys($this->dbh->query($sql)->fetchAll(PDO::FETCH_NUM | PDO::FETCH_UNIQUE));
                }
                if (isset($aReferants['OFF_PAGE'])) {
                    $referants = array_values($aReferants['OFF_PAGE']);
                    foreach ($referants as $ref) {
                        $ID_PAGES[] = $ref['ID_LIAISON'];
                    }
                }
                $sql = 'select * from OFF_PAGE where ID_PAGE in (' . implode(',', array_unique($ID_PAGES)) . ')';
                foreach ($this->dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $rowListe) {
                    echo '<li>';
                    $oPage = new Page($rowListe['ID_PAGE']);
                    if ($oPage->checkAuthorized(false) && ! $oPage->isLocked()) {
                        echo '<a href="../cms/cms_pseudo.php?idtf=' . $oPage->getID() . '&amp;PFM=1">' . secureInput($rowListe['PAG_TITRE_MENU']) . '</a>';
                    } else {
                        echo secureInput($rowListe['PAG_TITRE_MENU'] . ' (' . $rowListe['ID_PAGE'] . ')');
                    }
                    echo '</li>';
                }
                echo '</ul></li>';
            }
            // Site public
            if (isset($aReferants['ON_PARAGRAPHE']) || isset($aReferants['ON_PAGE'])) {
                echo '<li>' . gettext('Site') . '<ul>';
                $ID_PAGES = array();
                if (isset($aReferants['ON_PARAGRAPHE'])) {
                    $referants = array_values($aReferants['ON_PARAGRAPHE']);
                    $id_referants = array();
                    foreach ($referants as $ref) {
                        $id_referants[] = $ref['ID_LIAISON'];
                    }
                    $sql = 'select distinct ID_PAGE from ON_PARAGRAPHE where ID_PARAGRAPHE in (' . implode(',', $id_referants) . ')';
                    $ID_PAGES = array_keys($this->dbh->query($sql)->fetchAll(PDO::FETCH_NUM | PDO::FETCH_UNIQUE));
                }
                if (isset($aReferants['ON_PAGE'])) {
                    $referants = array_values($aReferants['ON_PAGE']);
                    foreach ($referants as $ref) {
                        $ID_PAGES[] = $ref['ID_LIAISON'];
                    }
                }
                $sql = 'select * from ON_PAGE where ID_PAGE in (' . implode(',', array_unique($ID_PAGES)) . ')';
                foreach ($this->dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $rowListe) {
                    echo '<li>';
                    $oPage = new Page($rowListe['ID_PAGE']);
                    if ($oPage->checkAuthorized(false) && $oPage->isLocked()) {
                        echo '<a href="../cms/cms_page.php?idtf=' . $oPage->getID() . '">' . secureInput($rowListe['PAG_TITRE_MENU']) . '</a>';
                    } else {
                        echo secureInput($rowListe['PAG_TITRE_MENU'] . ' (' . $rowListe['ID_PAGE'] . ')');
                    }
                    echo '</li>';
                }
                echo '</ul></li>';
            }
            // Révisions
            if (isset($aReferants['REVISION_PARAGRAPHE']) || isset($aReferants['REVISION_PAGE'])) {
                echo '<li>' . gettext('Revisions') . '<ul>';
                $ID_PAGES = array();
                if (isset($aReferants['REVISION_PARAGRAPHE'])) {
                    $referants = array_values($aReferants['REVISION_PARAGRAPHE']);
                    $id_referants = array();
                    foreach ($referants as $ref) {
                        $id_referants[] = $ref['ID_LIAISON'];
                    }
                    $sql = 'select distinct ID_PAGE from REVISION_PARAGRAPHE where ID_REVISIONPARAGRAPHE in (' . implode(',', $id_referants) . ')';
                    $ID_PAGES = array_keys($this->dbh->query($sql)->fetchAll(PDO::FETCH_NUM | PDO::FETCH_UNIQUE));
                }
                if (isset($aReferants['REVISION_PAGE'])) {
                    $referants = array_values($aReferants['REVISION_PAGE']);

                    $id_referants = array();
                    foreach ($referants as $ref) {
                        $id_referants[] = $ref['ID_LIAISON'];
                    }
                    $sql = 'select distinct ID_PAGE from REVISION_PAGE where ID_REVISIONPAGE in (' . implode(',', $id_referants) . ')';
                    foreach ($this->dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN) as $idPageTmp) {
                        $ID_PAGES[] = $idPageTmp;
                    }
                }

                $sql = 'select *, count(ID_REVISION) as NB_AFFECTATION from REVISION_PAGE inner join REVISION using(ID_REVISION) where REVISION_PAGE.ID_PAGE in (' . implode(',', array_unique($ID_PAGES)) . ') GROUP BY REVISION.ID_PAGE';
                foreach ($this->dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $rowListe) {
                    echo '<li><a href="/cms/cms_revisionListe.php?idtf=' . $rowListe['ID_PAGE'] . '">' . secureInput($rowListe['PAG_TITRE_MENU'] . ' (' . $rowListe['NB_AFFECTATION'] . ' affectation' . ($rowListe['NB_AFFECTATION'] > 1 ? 's' : '') . ')') . '</a></li>';
                }
                echo '</ul></li>';
            }
            // Données externes
            // On sup. les entrées relatives au paragraphe et aux pages pour ne garder que les autres référants
            unset($aReferants['OFF_PARAGRAPHE'], $aReferants['OFF_PAGE'], $aReferants['ON_PARAGRAPHE'], $aReferants['ON_PAGE'], $aReferants['REVISION_PARAGRAPHE'], $aReferants['REVISION_PAGE']);
            if (! empty($aReferants)) {
                echo '<li>' . gettext('Modules') . '<ul>';
                $sql = "select LIA_CODE FROM DD_LIAISON where LIA_NOM_FICHIER<>''";
                $aLIA_CODE = array_keys($this->dbh->query($sql)->fetchAll(PDO::FETCH_NUM | PDO::FETCH_UNIQUE));
                foreach ($aReferants as $code => $referants) {
                    echo '<li';
                    if (Utilisateur::getConnected()->isRoot() && in_array($code, $aLIA_CODE)) {
                        echo ' class="expand" data-liaison="' . $code . '"';
                    }
                    echo '>';
                    echo secureInput(extraireLibelle($referants[0]['LIA_LIBELLE']) . ' : ' . count($referants));
                    echo '</li>';
                }
                echo '</ul></li>';
            }
            echo '</ul></fieldset>';
        }
    }

    /**
     * Retourne les infos sur le utilisateur ayant réalisé les dernière modifs
     */
    public function getUtilisateurInfo()
    {
        if (is_numeric($this->getField('ID_UTILISATEUR'))) {
            $sql = "select UTI_NOM, UTI_PRENOM from UTILISATEUR where ID_UTILISATEUR=" . $this->getField('ID_UTILISATEUR');
            $rowTemp = $this->dbh->query($sql)->fetch(PDO::FETCH_ASSOC);

            return $rowTemp['UTI_NOM'] . ' ' . $rowTemp['UTI_PRENOM'];
        }

        return $this->getField('WEB_REDACTEUR');
    }

    /**
     * Met à jour la catégorie d'un élément
     *
     * @param string $cat_libelle
     *            libelle de la nouvelle catégorie
     * @param int $cat_idparent
     *            identifiant de la catégorie parente pour la nouvelle catégorie (peut etre vide)
     * @param int $cat_id
     *            identifiant de la catégorie à mettre à jour (peut etre vide)
     *            3 cas d'utilisation :
     *            - Aucun paramètre : la méthode supprime les catégories vides
     *            - 2 premiers paramètres : création et association de la nouvelle catégorie
     *            - Troisième paramètre : association directe avec la catégorie précisée
     */
    public function updateCategorie($cat_libelle = '', $cat_idparent = '', $cat_id = null)
    {
        require_once CLASS_DIR . 'class.db_webothequeCategorie.php';
        WebothequeCategorie::updateCategorie($cat_libelle, $cat_idparent, $cat_id, $this);
    }

    /**
     * Retourne le chemin physique correspondant au WBT_CODE
     *
     * @param String $WBT_CODE
     *            Code du type d'élément de la webotheque
     * @return String
     */
    public static function getUploadPhysicalDir($WBT_CODE)
    {
        switch ($WBT_CODE) {
            case 'WBT_VIDEO':
                $UPLOAD_PHYSIQUE = UPLOAD_VIDEO_PHYSIQUE;
                break;
            case 'WBT_MUSIC':
                $UPLOAD_PHYSIQUE = UPLOAD_MUSIC_PHYSIQUE;
                break;
            case 'WBT_FLASH':
                $UPLOAD_PHYSIQUE = UPLOAD_FLASH_PHYSIQUE;
                break;
            case 'WBT_IMAGE':
                $UPLOAD_PHYSIQUE = UPLOAD_IMAGE_PHYSIQUE;
                break;
            default:
                $UPLOAD_PHYSIQUE = UPLOAD_DOCUMENT_PHYSIQUE;
        }

        return $UPLOAD_PHYSIQUE;
    }

    /**
     * Retourne le chemin physique d'import FTP correspondant au WBT_CODE
     *
     * @param String $WBT_CODE
     *            Code du type d'élément de la webotheque
     * @return String
     */
    public static function getImportFtpPhysicalDir($WBT_CODE)
    {
        switch ($WBT_CODE) {
            case 'WBT_VIDEO':
                $UPLOAD_PHYSIQUE = IMPORT_VIDEO_FTP_PHYSIQUE;
                break;
            case 'WBT_MUSIC':
                $UPLOAD_PHYSIQUE = IMPORT_MUSIC_FTP_PHYSIQUE;
                break;
            case 'WBT_FLASH':
                $UPLOAD_PHYSIQUE = IMPORT_FLASH_FTP_PHYSIQUE;
                break;
            case 'WBT_IMAGE':
                $UPLOAD_PHYSIQUE = IMPORT_IMAGE_FTP_PHYSIQUE;
                break;
            default:
                $UPLOAD_PHYSIQUE = IMPORT_DOCUMENT_FTP_PHYSIQUE;
        }

        return $UPLOAD_PHYSIQUE;
    }

    /**
     * Retourne le tableau des extensions autorisées à être uploadées pour les objets webotheque de type $WBT_CODE
     *
     * @static
     *
     * @access public
     * @param string $WBT_CODE
     *            Code Webotheque. Si on est dans le contexte d'un objet, on récupére le code de l'objet en cours
     * @param
     *            optional boolean $accessible On souhaite obtenir la liste des extensions pour le média accessible ou pas
     * @return array
     */
    public static function getExtension($WBT_CODE, $accessible = false)
    {
        $aReturn = array();
        if ($accessible) {
            $aReturn = self::$_aAccessExtension[$WBT_CODE];
        } else {
            if (key_exists($WBT_CODE, self::$_aExtension)) {
                $aReturn = CMS::getCurrentSite()->getExtension(self::$_aExtension[$WBT_CODE]);
            } else {
                $aReturn = CMS::getCurrentSite()->getExtension('SIT_EXT_ALL'); // sinon renvoi tous les extensions permis
            }
        }

        return $aReturn;
    }

    /**
     *
     * Fonction permettant d'historiser les actions effectuées sur un élément de la webothèque
     *
     * @param string $HIS_ACTION
     *            : type d'action : CREATION / MODIFICATION / SUPPRESSION d'une fiche webothèque
     * @param string $HIS_DETAIL
     *            : Détail de l'action si besoin
     */
    public function historize($HIS_ACTION, $HIS_DETAIL = '')
    {
        if (! empty($HIS_DETAIL)) {
            if ($HIS_DETAIL == "MULTIPLE") {
                $HIS_DETAIL = "Import multiple";
            } else if ($HIS_DETAIL == "FTP") {
                $HIS_DETAIL = "Import FTP";
            }
        }

        $stmt = $this->dbh->prepare("insert into HISTORIQUE_WEBOTHEQUE (
            SIT_CODE,
            ID_WEBOTHEQUE,
            ID_HISTORIQUE_UTILISATEUR,
            HIS_ACTION,
            WBT_CODE,
            HIS_DETAIL,
            HIS_DATE
            ) values(
            :SIT_CODE,
            :ID_WEBOTHEQUE,
            :ID_HISTORIQUE_UTILISATEUR,
            :HIS_ACTION,
            :WBT_CODE,
            :HIS_DETAIL,
            :HIS_DATE
            )");

        $stmt->bindValue(':SIT_CODE', $this->getField('SIT_CODE'), PDO::PARAM_STR);
        $stmt->bindValue(':ID_WEBOTHEQUE', $this->getID(), PDO::PARAM_INT);
        // On peut historiser des modifications sans passer par un utilisateur (ex via un crontab).
        // Dans ce cas, nous n'avons pas les infos sur ledit utilisateur
        $S_ID_HISTORIQUE = '';
        if (isset($_SESSION['S_ID_HISTORIQUE']) && CMS::getCurrentSite() && is_numeric($_SESSION['S_ID_HISTORIQUE'][CMS::getCurrentSite()->getID()])) {
            $S_ID_HISTORIQUE = $_SESSION['S_ID_HISTORIQUE'][CMS::getCurrentSite()->getID()];
        }
        $stmt->bindValue(':ID_HISTORIQUE_UTILISATEUR', $_SESSION['S_ID_HISTORIQUE'][CMS::getCurrentSite()->getID()], PDO::PARAM_INT);
        $stmt->bindValue(':HIS_ACTION', $HIS_ACTION, PDO::PARAM_STR);
        $stmt->bindValue(':WBT_CODE', $this->code, PDO::PARAM_STR);
        $stmt->bindValue(':HIS_DETAIL', $HIS_DETAIL, PDO::PARAM_STR);
        $stmt->bindValue(':HIS_DATE', time(), PDO::PARAM_INT);
        $stmt->execute();

        if (isset($_SESSION['S_ID_HISTORIQUE'])) {
            Utilisateur::historizeAction();
        }

        if ($HIS_ACTION == 'SUPPRESSION') {
            $this->dbh->exec('update HISTORIQUE_WEBOTHEQUE
                               set HIS_INFO = ' . $this->dbh->quote($this->getField('WEB_LIBELLE')) . '
                               where ID_WEBOTHEQUE = ' . $this->getID());
        }
    }
}

/**
 * Classe représantant les images de la wébothèque
 *
 * @package CMS\DB\Webotheque
 */
class Webo_IMAGE extends Webotheque
{

    public function __construct($idtf = -1)
    {
        parent::__construct($idtf, 'WBT_IMAGE');
    }

    public static function getModuleCode()
    {
        return 'MOD_WEBOTHEQUE_IMAGE';
    }

    public function delete($force = false, $deleteMultiple = false)
    {
        if (! $force && ! $this->isDeletable()) {
            return false;
        }
        $fm = new File_management('WEBOTHEQUE', 'ID_WEBOTHEQUE', $this->getID(), UPLOAD_IMAGE_PHYSIQUE);
        $fm->delete('WEB_CHEMIN');

        return parent::delete($force, $deleteMultiple);
    }

    /**
     * Renvoi le chemin de l'image depuis la racine web au bon format
     *
     * @param string $format
     *            Format de l'image déclaré dans DD_IMAGEFORMAT
     * @return String Chemin vers l'image au format $format
     */
    public function getSRC($format = '')
    {
        require_once CLASS_DIR . 'class.File_management.php';
        $fm = new File_management('WEBOTHEQUE', 'ID_WEBOTHEQUE', $this->getID(), UPLOAD_IMAGE_PHYSIQUE);

        return $fm->getSRC($this->getField('WEB_CHEMIN'), $format);
    }

    /**
     * Renvoi le chemin de l'image depuis la racine web au format THUMB (50x50 par défaut)
     *
     * @return String Chemin vers l'image au format THUMB
     */
    public function getThumbSRC()
    {
        require_once CLASS_DIR . 'class.File_management.php';
        $fm = new File_management('WEBOTHEQUE', 'ID_WEBOTHEQUE', $this->getID(), UPLOAD_IMAGE_PHYSIQUE);

        return $fm->getThumbSRC($this->getField('WEB_CHEMIN'));
    }

    /**
     * Renvoie le code HTML d'une image avec les options choisies, popup, légende, crédits, ...
     *
     * @param string $format
     *            le format de l'image déclaré dans DD_IMAGEFORMAT (si vide alors taille d'origine)
     * @param string $alt
     *            l'alternative texte de l'image
     * @param string $class
     *            Classes HTML à rajouter à la balise img
     * @param boolean $formatPopup
     *            Affiche l'image en "livebox" si true
     * @param string $legende
     *            La légende de l'image
     * @param boolean $showCredit
     *            Les crédits de l'image
     * @param boolean $oPageLongDesc
     *            Ajoute la description longue si true
     * @param string $complement
     *            du texte ajouté dans la balise IMG (d'autres attributs par exemple)
     * @return String HTML de l'image en fonction des options
     */
    public function getHTML($format = '', $alt = '', $class = '', $formatPopup = false, $legende = '', $showCredit = true, $oPageLongDesc = false, $complement = '')
    {
        // la longdesc est un lien sur la même page vers un template spécifique
        if ($oPageLongDesc && ($this->getField('WEB_DESCRIPTIONACC') != '')) {
            $complement .= trim(' longdesc="' . $oPageLongDesc->getURLESCAPE(array(
                'TPL_CODE' => 'TPL_LONGDESC',
                'PAR_TPL_IDENTIFIANT' => $this->getID()
            )) . '"');
        }

        // largeur / hauteur
        if ($format == '') {
            $complement .= trim(' width="' . $this->getField('WEB_LARGEUR') . '" height="' . $this->getField('WEB_HAUTEUR') . '"');
        }

        $credit = $showCredit ? $this->getField('WEB_CREDIT') : '';

        $srcPopup = $formatPopup !== false ? $this->getSRC($formatPopup) : '';

        return self::getIMGHTML($this->getSRC($format), $format, $legende, $credit, $alt, $class, $srcPopup, $complement);
    }

    /**
     * Renvoie le code HTML d'une image avec les options choisies, popup, légende, crédits, ...
     *
     * @param string $src
     *            La source de l'image
     * @param string $format
     *            le format de l'image déclaré dans DD_IMAGEFORMAT (si vide alors taille d'origine)
     * @param string $legende
     *            La légende de l'image
     * @param boolean $credit
     *            Les crédits de l'image
     * @param string $alt
     *            l'alternative texte de l'image
     * @param string $class
     *            Classes HTML à rajouter à la balise img
     * @param boolean $srcPopup
     *            Affiche l'image en "livebox"
     * @param string $complement
     *            du texte ajouté dans la balise IMG (d'autres attributs par exemple)
     * @return String HTML de l'image en fonction des options
     */
    public static function getIMGHTML($src, $format = '', $legende = '', $credit = '', $alt = '', $class = '', $srcPopup = '', $complement = '')
    {

        // on traite spécifiquement les classes d'alignements qui doivent remonter si légende, crédit ou popup
        $aClass = explode(' ', $class);
        if ($legende || $credit || $srcPopup) {
            $conteneurClass = '';
            if (in_array('alignleft', $aClass)) {
                $aClass = array_diff($aClass, array(
                    'alignleft'
                ));
                $conteneurClass = 'alignleft';
            } elseif (in_array('alignright', $aClass)) {
                $aClass = array_diff($aClass, array(
                    'alignright'
                ));
                $conteneurClass = 'alignright';
            }
            if (! empty($format)) {
                $conteneurClass = trim($conteneurClass . ' ' . $format);
            }
        } elseif (! empty($format)) {
            $aClass = array_merge($aClass, array(
                $format
            ));
        }

        $html = '<img src="' . $src . '" alt="' . encode($alt, false) . '"';
        if (! empty($aClass)) {
            $html .= ' class="' . implode(' ', array_filter($aClass)) . '"';
        }
        if ($complement) {
            $html .= ' ' . $complement;
        }
        $html .= '>';

        if ($legende || $credit || $srcPopup) {
            if ($credit || $srcPopup) {
                if ($srcPopup) {
                    $oModule = new Module('MOD_WEBOTHEQUE_IMAGE');
                    $traduction = $oModule->i18n('web_voir_en_grand');
                    if ($legende) {
                        $title = encode($legende, false);
                    } elseif ($alt) {
                        $title = encode($alt, false);
                    } else {
                        $title = '';
                    }
                    $html = '<a href="' . $srcPopup . '" class="lightbox" title="' . $traduction . '" data-title="' . $title . '">' . $html . '<img alt="' . $traduction . '" src="' . CMS::getCurrentSite()->getField('SIT_IMAGE') . 'loupe.png" class="imgLoupe"></a>';
                }
                if ($credit) {
                    $html .= ' <span class="spanCredit">' . encode($credit, false) . '</span>';
                }
            }
            // permet de positionner la légende et de calculer la largeur dispo pour la légende
            $html = ' <span class="spanImgOuter">' . $html . '</span>';
            if ($legende) {
                $html .= ' <span class="spanLegende">' . $legende . '</span>';
            }
            // conteneur global
            $span = '<span class="spanImgContainer';
            if (! empty($conteneurClass)) {
                $span .= ' ' . $conteneurClass;
            }
            $span .= '">';
            $html = $span . $html . '</span>';
        }

        return $html;
    }

    /**
     * Vérifie si l'image n'existe pas déjà
     *
     * @param string $tmp_name
     *            nom du fichier, prend $_FILES['WEB_CHEMIN']['tmp_name'] si vide
     * @return int | boolean l'identifiant de l'image correspondante si elle existe, false sinon
     */
    public function checkMD5($tmp_name = '')
    {
        if (empty($tmp_name) && ! empty($_FILES['WEB_CHEMIN']['tmp_name'])) {
            $tmp_name = $_FILES['WEB_CHEMIN']['tmp_name'];
        }
        if (empty($tmp_name)) {
            return false;
        }
        $md5 = md5_file($tmp_name);

        return parent::_checkMD5($md5);
    }

    /**
     * Méthode appelé à l'insertion et à la modificarion de l'image
     *
     * @return Boolean Toujours true
     */
    public function postTraitement()
    {
        // Mise à jour de l'éventuel contenu alternatif
        Editor::updateContent(isset($_POST['WEB_DESCRIPTIONACC']) ? $_POST['WEB_DESCRIPTIONACC'] : '', 'WEBOTHEQUE', 'WEB_DESCRIPTIONACC', 'ID_WEBOTHEQUE', $this->getID());

        // upload
        $fm = new File_management('WEBOTHEQUE', 'ID_WEBOTHEQUE', $this->getID(), UPLOAD_IMAGE_PHYSIQUE);
        $fm->setExtensions(Webotheque::getExtension($this->code));
        if (! $fm->upload('WEB_CHEMIN', 'WEB_TAILLE', $this->getField('WEB_LIBELLE'))) {
            if ($fm->error == 'FM_EXTENSION') {
                setMsg(gettext('Fichier') . ' : ' . gettext('Extension incorrecte') . ' (' . implode(', ', $fm->getExtensions()) . ')', 'ERROR');
            } elseif (! empty($fm->error)) {
                setMsg(gettext('Fichier') . ' : ' . gettext('erreur') . ' ' . $fm->error, 'ERROR');
            }
        } else {
            // Redimensionnement fullHD maxi seulement si plus grand
            $info = getimagesize($fm->getPhysicalName());
            if ($info[1] > 1920 || $info[0] > 1920) {
                $fm->resize(1920, 1920);
                // Mise à jour des infos
                $info = getimagesize($fm->getPhysicalName());
                $sql = "update WEBOTHEQUE set
                    WEB_TAILLE=" . intval(filesize($fm->getPhysicalName())) . "
                    where ID_WEBOTHEQUE=" . $this->getID();
                $this->dbh->exec($sql);
            }
            // Conversion RGB
            $fm->convertCMYKtoRGB();
            // Mise à jour des dimensions
            $stmt = $this->dbh->prepare('update WEBOTHEQUE set WEB_HAUTEUR=:WEB_HAUTEUR, WEB_LARGEUR=:WEB_LARGEUR where ID_WEBOTHEQUE=:ID_WEBOTHEQUE');
            $stmt->bindValue(':WEB_HAUTEUR', $info[1], PDO::PARAM_INT);
            $stmt->bindValue(':WEB_LARGEUR', $info[0], PDO::PARAM_INT);
            $stmt->bindValue(':ID_WEBOTHEQUE', $this->getID(), PDO::PARAM_INT);
            $stmt->execute();
        }
        $sql = "update WEBOTHEQUE set WEB_CREDIT=" . $this->dbh->quote($_POST['WEB_CREDIT']) . " where ID_WEBOTHEQUE=" . $this->getID();
        $this->dbh->query($sql);

        // pixlr
        if (isset($_POST['pixlr'])) {
            $file = UPLOAD_IMAGE_PHYSIQUE . dirname($this->getField('WEB_CHEMIN')) . '/pixlr_' . $this->getID() . substr($this->getField('WEB_CHEMIN'), strrpos($this->getField('WEB_CHEMIN'), '.'));
            $thumb = UPLOAD_IMAGE_PHYSIQUE . dirname($this->getField('WEB_CHEMIN')) . '/THUMB/pixlr_' . $this->getID() . substr($this->getField('WEB_CHEMIN'), strrpos($this->getField('WEB_CHEMIN'), '.'));

            if ($_POST['pixlr'] == 'UPDATE') {

                // on supprime tous les formats
                $fm = new File_management('WEBOTHEQUE', 'ID_WEBOTHEQUE', $this->getID(), UPLOAD_IMAGE_PHYSIQUE);
                $fm->delete('WEB_CHEMIN');

                // on met à jour la version pixlr
                $newName = dirname($this->getField('WEB_CHEMIN')) . '/' . $this->getID() . '_' . substr(time(), - 3) . substr($this->getField('WEB_CHEMIN'), strrpos($this->getField('WEB_CHEMIN'), '_'));
                rename($file, UPLOAD_IMAGE_PHYSIQUE . $newName);
                $info = getimagesize(UPLOAD_IMAGE_PHYSIQUE . $newName);
                $sql = "update WEBOTHEQUE set
                    WEB_HAUTEUR=" . intval($info[1]) . ",
                    WEB_LARGEUR=" . intval($info[0]) . ",
                    WEB_TAILLE=" . intval(filesize(UPLOAD_IMAGE_PHYSIQUE . $newName)) . ",
                    WEB_CHEMIN=" . $this->dbh->quote($newName) . ",
                    WEB_MD5=" . $this->dbh->quote(md5_file(UPLOAD_IMAGE_PHYSIQUE . $newName)) . "
                    where ID_WEBOTHEQUE=" . $this->getID();
                $this->dbh->exec($sql);

                // on supprime la vignette pixlr
                File_management::deleteFromName($thumb);
            } elseif ($_POST['pixlr'] == 'DELETE') {

                File_management::deleteFromName($file);
                File_management::deleteFromName($thumb);
            }
        }

        return true;
    }

    /**
     * Méthode des traitements spécifiques lors de l'insertion d'une image de la webothèque
     *
     * @param String $nomFichier
     *            nom du fichier image
     * @param string $error
     *            référence vers le message d'erreur
     * @return boolean true si tout va bien, false sinon
     */
    public function updateSpecifique($nomFichier, &$error = '')
    {
        require_once (CLASS_DIR . 'class.Link.php');
        Link::delete('WEBOTHEQUE', $this->getID());
        require_once (CLASS_DIR . 'class.Editor.php');
        Editor::updateContent($this->getField('WEB_DESCRIPTION'), 'WEBOTHEQUE', 'WEB_DESCRIPTION', 'ID_WEBOTHEQUE', $this->getID());
        // upload
        $fm = new File_management('WEBOTHEQUE', 'ID_WEBOTHEQUE', $this->getID(), UPLOAD_IMAGE_PHYSIQUE);
        $fm->setExtensions(Webotheque::getExtension($this->code));
        if (file_exists($nomFichier)) { // Gestion import depuis le rep temp ou autre
            $physicalDir = dirname($nomFichier) . '/';
            $nomFichier = basename($nomFichier);
        } else {
            $physicalDir = Webotheque::getImportFtpPhysicalDir($this->code);
        }
        if (! $fm->upload('WEB_CHEMIN', 'WEB_TAILLE', $this->getField('WEB_LIBELLE'), $physicalDir, $nomFichier)) {
            $error = gettext('Fichier') . ' : ';
            if ($fm->error == 'FM_EXTENSION') {
                $error .= gettext('Extension incorrecte') . ' (' . implode(', ', $fm->getExtensions()) . ')';
            } elseif (! empty($fm->error)) {
                $error .= gettext('erreur') . ' ';
                if ($fm->error == 'FM_UPLOADSIZE') {
                    $error .= gettext('poids trop important');
                } else {
                    $error .= $fm->error;
                }
            } else {
                $error .= gettext('erreur');
            }
            setMsg($error, 'ERROR');

            return false;
        } else {
            $info = getimagesize($fm->getPhysicalName());
            // Mise à jour des dimensions
            $stmt = $this->dbh->prepare('update WEBOTHEQUE set WEB_HAUTEUR = :WEB_HAUTEUR, WEB_LARGEUR = :WEB_LARGEUR where ID_WEBOTHEQUE = :ID_WEBOTHEQUE');
            $stmt->bindValue(':WEB_HAUTEUR', $info[1], PDO::PARAM_INT);
            $stmt->bindValue(':WEB_LARGEUR', $info[0], PDO::PARAM_INT);
            $stmt->bindValue(':ID_WEBOTHEQUE', $this->getID(), PDO::PARAM_INT);
            $stmt->execute();

            return true;
        }
    }
}

/**
 * Classe représantant les documents de la wébothèque
 *
 * @package CMS\DB\Webotheque
 */
class Webo_DOCUMENT extends Webotheque
{

    public function __construct($idtf = -1)
    {
        parent::__construct($idtf, 'WBT_DOCUMENT');
    }

    public static function getModuleCode()
    {
        return 'MOD_WEBOTHEQUE_DOCUMENT';
    }

    public function delete($force = false, $deleteMultiple = false)
    {
        if (! $force && ! $this->isDeletable()) {
            return false;
        }
        $fm = new File_management('WEBOTHEQUE', 'ID_WEBOTHEQUE', $this->getID(), UPLOAD_DOCUMENT_PHYSIQUE);
        $fm->delete('WEB_CHEMIN');
        $fm->delete('WEB_CHEMINACC');

        $sql = "delete from WEBOTHEQUEINDEXER where ID_WEBOTHEQUE=" . $this->getID();
        $this->dbh->exec($sql);

        return parent::delete($force, $deleteMultiple);
    }

    public function index()
    {
        $sql = "delete from WEBOTHEQUEINDEXER where ID_WEBOTHEQUE=" . $this->getID();
        $this->dbh->exec($sql);

        // on force le load pour etre sur d'utiliser les dernières données (cas d'un update sql)
        $this->load();
        $ext = strtolower(strrchr($this->getField('WEB_CHEMIN'), '.'));
        $retour = array();
        if ($ext == '.pdf') {
            exec('pdftotext -enc UTF-8 ' . UPLOAD_DOCUMENT_PHYSIQUE . $this->getField('WEB_CHEMIN') . ' -', $retour);
        } elseif ($ext == '.doc' || $ext == '.rtf' || $ext == '.docx') {
            exec('catdoc -a -dutf-8 ' . UPLOAD_DOCUMENT_PHYSIQUE . $this->getField('WEB_CHEMIN'), $retour);
        } elseif ($ext == '.xls' || $ext == 'xlsx') {
            exec('xls2csv -dutf-8 ' . UPLOAD_DOCUMENT_PHYSIQUE . $this->getField('WEB_CHEMIN'), $retour);
        } elseif ($ext == '.ppt') {
            exec('catppt -dutf-8 ' . UPLOAD_DOCUMENT_PHYSIQUE . $this->getField('WEB_CHEMIN'), $retour);
        } elseif ($ext == '.txt') {
            $str = file_get_contents(UPLOAD_DOCUMENT_PHYSIQUE . $this->getField('WEB_CHEMIN'));
            $retour[] = (seems_utf8($str)) ? $str : utf8_encode($str);
        }

        if (sizeof($retour) > 0) {
            $retour = trim(preg_replace('/\s+/u', ' ', preg_replace('/\s.\s/u', ' ', strtr(implode(' ', $retour), '.,', '  '))));
            if ($retour != '') {
                $sql = "insert into WEBOTHEQUEINDEXER (
                    ID_WEBOTHEQUE,
                    IND_TEXTE
                    ) values (
                    " . $this->getID() . ",
                    " . $this->dbh->quote($retour) . "
                    )";
                $this->dbh->exec($sql);
            }
        }
    }

    public function checkMD5()
    {
        if (empty($_FILES['WEB_CHEMIN']['tmp_name'])) {
            return false;
        }
        $md5 = md5_file($_FILES['WEB_CHEMIN']['tmp_name']);

        return parent::_checkMD5($md5);
    }

    /**
     * Cette méthode peut renvoyer faux si l'url est vide
     */
    public function getDownloadURL($alternativeACC = false)
    {
        $field = $alternativeACC ? 'WEB_CHEMINACC' : 'WEB_CHEMIN';
        if ($this->getField($field) == '' || ! file_exists(UPLOAD_DOCUMENT_PHYSIQUE . $this->getField($field))) {
            return false;
        }
        // On tente d'utiliser que la partie pertinante au sein du nom fichier (basé sur le libellé de la webothèque)
        // pour construire l'URL de téléchargement afin que l'upload d'un nouveau fichier ne change pas son URL
        // si le libellé ne change pas entre les différents uploads
        // ==> cf. {File_management}->genererNomFichier()
        if (preg_match('#^.{2}\/[0-9]+.*_[0-9]{3}_(.*)$#', $this->getField($field), $matches)) {
            $path = $matches[1];
        } else {
            $path = $this->getField($field);
        }
        $chemin = SERVER_ROOT . 'include/viewfilesecure.php?idtf=' . $this->getID() . '&amp;path=' . urlencode($path);
        if (CMS::$mode == 'ON_') { // si on est en FO, on verifie l'acces à la page
            if (($oPage = CMS::getCurrentSite()->getCurrentPage()) && ! $oPage->isSecured()) {
                $chemin = SERVER_ROOT . 'cms_viewFile.php?idtf=' . $this->getID() . '&amp;path=' . urlencode($path);
            }
        }
        return $chemin;
    }

    /**
     * Cette méthode peut renvoyer faux si l'url est vide
     */
    public function getAnchor($aClass = array(), $alternativeACC = false)
    {
        if (! $url = $this->getDownloadURL($alternativeACC)) {
            return false;
        }
        $str = 'href="' . $url . '"';
        $dataVar = array();
        if (CMS::$mode == 'ON_') {
            $field = $alternativeACC ? 'WEB_CHEMINACC' : 'WEB_CHEMIN';
            $str .= ' data-file_name="' . filenameToRfc1738($this->getField('WEB_LIBELLE')) . '"';
            $str .= ' data-file_ext="' . strtoupper(end(explode('.', $this->getField($field)))) . '"';
        }
        $aClass[] = 'document';
        $str .= ' class="' . implode(' ', $aClass) . '"';
        return $str;
    }

    public function postTraitement()
    {
        // upload
        $fm = new File_management('WEBOTHEQUE', 'ID_WEBOTHEQUE', $this->getID(), UPLOAD_DOCUMENT_PHYSIQUE);
        $fm->setExtensions(Webotheque::getExtension($this->code));
        if (! $fm->upload('WEB_CHEMIN', 'WEB_TAILLE', $this->getField('WEB_LIBELLE'))) {
            if ($fm->error == 'FM_EXTENSION') {
                setMsg(gettext('Fichier') . ' : ' . gettext('Extension incorrecte') . ' (' . implode(', ', $fm->getExtensions()) . ')', 'ERROR');
            } elseif (! empty($fm->error)) {
                setMsg(gettext('Fichier') . ' : ' . gettext('erreur') . ' ' . $fm->error, 'ERROR');
            }
        } else {
            $this->index();
        }
        $fm->setExtensions(Webotheque::getExtension($this->code, true));
        if (! $fm->upload('WEB_CHEMINACC', 'WEB_TAILLEACC', $this->getField('WEB_LIBELLE'))) {
            if ($fm->error == 'FM_EXTENSION') {
                setMsg(gettext('Alternative') . ' : ' . gettext('Extension incorrecte') . ' (' . implode(', ', $fm->getExtensions()) . ')', 'ERROR');
            } elseif (! empty($fm->error)) {
                setMsg(gettext('Alternative') . ' : ' . gettext('erreur') . ' ' . $fm->error, 'ERROR');
            }
            $fm->checkDelete('WEB_CHEMINACC', 'WEB_TAILLEACC');
        }

        return true;
    }

    /**
     * Méthode des traitements spécifiques lors de l'insertion d'un élément en webothèque
     */
    public function updateSpecifique($nomFichier, &$error = '')
    {
        // upload
        $fm = new File_management('WEBOTHEQUE', 'ID_WEBOTHEQUE', $this->getID(), UPLOAD_DOCUMENT_PHYSIQUE);
        $fm->setExtensions(Webotheque::getExtension($this->code));
        if (file_exists($nomFichier)) { // Gestion import depuis le rep temp ou autre
            $physicalDir = dirname($nomFichier) . '/';
            $nomFichier = basename($nomFichier);
        } else {
            $physicalDir = Webotheque::getImportFtpPhysicalDir($this->code);
        }
        if (! $fm->upload('WEB_CHEMIN', 'WEB_TAILLE', $this->getField('WEB_LIBELLE'), $physicalDir, $nomFichier)) {
            $error = gettext('Fichier') . ' : ';
            if ($fm->error == 'FM_EXTENSION') {
                $error .= gettext('Extension incorrecte') . ' (' . implode(', ', $fm->getExtensions()) . ')';
            } elseif (! empty($fm->error)) {
                $error .= gettext('erreur') . ' ';
                if ($fm->error == 'FM_UPLOADSIZE') {
                    $error .= gettext('poids trop important');
                } else {
                    $error .= $fm->error;
                }
            } else {
                $error .= gettext('erreur');
            }
            setMsg($error, 'ERROR');

            return false;
        }
        $this->index();

        return true;
    }
}

/**
 * Classe représantant les liens externes de la wébothèque
 *
 * @package CMS\DB\Webotheque
 */
class Webo_LIENEXTERNE extends Webotheque
{

    public function __construct($idtf = -1)
    {
        parent::__construct($idtf, 'WBT_LIENEXTERNE');
    }

    public static function getModuleCode()
    {
        return 'MOD_WEBOTHEQUE_LIENEXTERNE';
    }

    public function checkMD5()
    {
        $md5 = md5($_POST['WEB_CHEMIN']);

        return parent::_checkMD5($md5);
    }

    public function getAnchor($aClass = array())
    {
        // On transforme les & non utilisés pour des entités en &amp;
        $url = preg_replace('/\&(?![#0-9a-z]+;)/ui', "&amp;", $this->getField('WEB_CHEMIN'));
        if (strtolower(substr($url, 0, 6)) != 'mailto') {
            $aClass[] = 'external';
        }
        $str = 'href="' . $url . '"';
        if (! empty($aClass)) {
            $str .= ' class="' . implode(' ', $aClass) . '"';
        }
        return $str;
    }

    public function preTraitement()
    {
        $_POST['WEB_CHEMIN'] = trim($_POST['WEB_CHEMIN']);

        // on cherche un protocole
        $aProtocol = array(
            'http:/',
            'mailto',
            'https:',
            'ftp://',
            'file:/'
        );
        if (! in_array(substr($_POST['WEB_CHEMIN'], 0, 6), $aProtocol)) {
            $_POST['WEB_CHEMIN'] = (valideMail($_POST['WEB_CHEMIN'])) ? 'mailto:' . $_POST['WEB_CHEMIN'] : 'http://' . $_POST['WEB_CHEMIN'];
        }

        return true;
    }

    public function postTraitement()
    {
        $sql = "update WEBOTHEQUE set WEB_CHEMIN=" . $this->dbh->quote($_POST['WEB_CHEMIN']) . " where ID_WEBOTHEQUE=" . $this->getID();
        $this->dbh->query($sql);

        return true;
    }
}

/**
 * Classe représantant les animations flash de la wébothèque
 *
 * @package CMS\DB\Webotheque
 */
class Webo_FLASH extends Webotheque
{

    public function __construct($idtf = -1)
    {
        parent::__construct($idtf, 'WBT_FLASH');
    }

    public static function getModuleCode()
    {
        return 'MOD_WEBOTHEQUE_FLASH';
    }

    public function delete($force = false, $deleteMultiple = false)
    {
        if (! $force && ! $this->isDeletable()) {
            return false;
        }
        $fm = new File_management('WEBOTHEQUE', 'ID_WEBOTHEQUE', $this->getID(), UPLOAD_FLASH_PHYSIQUE);
        $fm->delete('WEB_CHEMIN');

        return parent::delete($force, $deleteMultiple);
    }

    /**
     * Retourne le code HTML de l'objet Flash
     *
     * @param String $align
     * @param Int $width
     * @param Int $height
     * @return String
     */
    public function getHTML($align = '', $width = '', $height = '')
    {
        require_once CLASS_DIR . 'class.Editor.php';
        $htmlObject = '';
        $htmlObject .= '<object name="flash" classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,40,0" width="' . $this->getField('WEB_LARGEUR') . '" height="' . $this->getField('WEB_HAUTEUR') . '"';
        if ($align != '') {
            $htmlObject .= ' class="align' . $align . '"';
        }
        $htmlObject .= '>';
        $htmlObject .= '<param name="movie" value="' . UPLOAD_FLASH . $this->getField('WEB_CHEMIN') . '">';
        $htmlObject .= '<param name="quality" value="best">';
        $htmlObject .= '<param name="wmode" value="Transparent">';
        $htmlObject .= '<param name="width" value="' . $this->getField('WEB_LARGEUR') . '">';
        $htmlObject .= '<param name="height" value="' . $this->getField('WEB_HAUTEUR') . '">';
        $htmlObject .= '<!--[if !IE]><-->';
        $htmlObject .= '<object data="' . UPLOAD_FLASH . $this->getField('WEB_CHEMIN') . '" width="' . $this->getField('WEB_LARGEUR') . '" height="' . $this->getField('WEB_HAUTEUR') . '" type="application/x-shockwave-flash"';
        if ($align != '') {
            $htmlObject .= ' class="align' . $align . '"';
        }
        $htmlObject .= '>';
        $htmlObject .= '<param name="quality" value="best">';
        $htmlObject .= '<param name="wmode" value="Transparent">';
        $htmlObject .= '<param name="pluginurl" value="http://www.macromedia.com/go/getflashplayer">';
        $htmlObject .= '<!--> <![endif]-->';
        $htmlObject .= Editor::displayContent($this->getField('WEB_DESCRIPTIONACC'), CMS::getCurrentSite()->getHomePage());
        $htmlObject .= '<!--[if !IE]> <-->';
        $htmlObject .= '</object>';
        $htmlObject .= '<!--><![endif]-->';
        $htmlObject .= '</object>';

        return $htmlObject;
    }

    public function checkMD5()
    {
        if (empty($_FILES['WEB_CHEMIN']['tmp_name'])) {
            return false;
        }
        $md5 = md5_file($_FILES['WEB_CHEMIN']['tmp_name']);

        return parent::_checkMD5($md5);
    }

    public function postTraitement()
    {
        // mise à jour de l'éventuel contenu alternatif
        Editor::updateContent(isset($_POST['WEB_DESCRIPTIONACC']) ? $_POST['WEB_DESCRIPTIONACC'] : '', 'WEBOTHEQUE', 'WEB_DESCRIPTIONACC', 'ID_WEBOTHEQUE', $this->getID());

        // mise à jour des dimensions
        $stmt = $this->dbh->prepare('update WEBOTHEQUE set WEB_HAUTEUR=:WEB_HAUTEUR, WEB_LARGEUR=:WEB_LARGEUR where ID_WEBOTHEQUE=:ID_WEBOTHEQUE');
        $stmt->bindValue(':WEB_HAUTEUR', $_POST['WEB_HAUTEUR'], PDO::PARAM_INT);
        $stmt->bindValue(':WEB_LARGEUR', $_POST['WEB_LARGEUR'], PDO::PARAM_INT);
        $stmt->bindValue(':ID_WEBOTHEQUE', $this->getID(), PDO::PARAM_INT);
        $stmt->execute();

        // upload
        $fm = new File_management('WEBOTHEQUE', 'ID_WEBOTHEQUE', $this->getID(), UPLOAD_FLASH_PHYSIQUE);
        $fm->setExtensions(Webotheque::getExtension($this->code));
        if (! $fm->upload('WEB_CHEMIN', 'WEB_TAILLE', $this->getField('WEB_LIBELLE'))) {
            if ($fm->error == 'FM_EXTENSION') {
                setMsg(gettext('Fichier') . ' : ' . gettext('Extension incorrecte') . ' (' . implode(', ', $fm->getExtensions()) . ')', 'ERROR');
            } elseif (! empty($fm->error)) {
                setMsg(gettext('Fichier') . ' : ' . gettext('erreur') . ' ' . $fm->error, 'ERROR');
            }
        }

        return true;
    }

    /**
     * Méthode des traitements spécifiques lors de l'insertion d'un élément en webothèque
     */
    public function updateSpecifique($nomFichier, &$error = '')
    {
        // Mise à jour de l'éventuel contenu alternatif
        require_once CLASS_DIR . 'class.Link.php';
        Link::delete('WEBOTHEQUE', $this->getID());
        require_once CLASS_DIR . 'class.Editor.php';
        Editor::updateContent($this->getField('WEB_DESCRIPTION'), 'WEBOTHEQUE', 'WEB_DESCRIPTION', 'ID_WEBOTHEQUE', $this->getID());
        // Mise à jour des dimensions
        $stmt = $this->dbh->prepare('update WEBOTHEQUE set WEB_HAUTEUR = :WEB_HAUTEUR, WEB_LARGEUR = :WEB_LARGEUR where ID_WEBOTHEQUE = :ID_WEBOTHEQUE');
        $stmt->bindValue(':WEB_HAUTEUR', $_POST['WEB_HAUTEUR'], PDO::PARAM_INT);
        $stmt->bindValue(':WEB_LARGEUR', $_POST['WEB_LARGEUR'], PDO::PARAM_INT);
        $stmt->bindValue(':ID_WEBOTHEQUE', $this->getID(), PDO::PARAM_INT);
        $stmt->execute();

        // upload
        $fm = new File_management('WEBOTHEQUE', 'ID_WEBOTHEQUE', $this->getID(), UPLOAD_FLASH_PHYSIQUE);
        $fm->setExtensions(Webotheque::getExtension($this->code));
        if (file_exists($nomFichier)) { // Gestion import depuis le rep temp ou autre
            $physicalDir = dirname($nomFichier) . '/';
            $nomFichier = basename($nomFichier);
        } else {
            $physicalDir = Webotheque::getImportFtpPhysicalDir($this->code);
        }
        if (! $fm->upload('WEB_CHEMIN', 'WEB_TAILLE', $this->getField('WEB_LIBELLE'), $physicalDir, $nomFichier)) {
            $error = gettext('Fichier') . ' : ';
            if ($fm->error == 'FM_EXTENSION') {
                $error .= gettext('extention incorrect') . ' (' . implode(', ', $fm->getExtensions()) . ')';
            } elseif (! empty($fm->error)) {
                $error .= gettext('erreur') . ' ';
                if ($fm->error == 'FM_UPLOADSIZE') {
                    $error .= gettext('poids trop important');
                } else {
                    $error .= $fm->error;
                }
            } else {
                $error .= gettext('erreur');
            }
            setMsg($error, 'ERROR');

            return false;
        }

        return true;
    }
}

/**
 * Classe représantant les vidéos internes de la wébothèque
 *
 * @package CMS\DB\Webotheque
 */
class Webo_VIDEO extends Webotheque
{

    public function __construct($idtf = -1)
    {
        parent::__construct($idtf, 'WBT_VIDEO');
    }

    public static function getModuleCode()
    {
        return 'MOD_WEBOTHEQUE_VIDEO';
    }

    public function delete($force = false, $deleteMultiple = false)
    {
        if (! $force && ! $this->isDeletable()) {
            return false;
        }
        $fm = new File_management('WEBOTHEQUE', 'ID_WEBOTHEQUE', $this->getID(), UPLOAD_VIDEO_PHYSIQUE);
        $fm->delete('WEB_CHEMIN');
        $fm->delete('WEB_VIGNETTE');
        $fm->delete('WEB_SOUSTITRE');

        return parent::delete($force, $deleteMultiple);
    }

    /**
     * Retourne le code HTML de l'objet Vidéo
     *
     * @param String $align
     * @param Int $width
     * @param Int $height
     * @return String
     */
    public function getHTML($align = '', $width = '', $height = '')
    {
        require_once CLASS_DIR . 'class.Editor.php';
        if (! is_numeric($width)) {
            $width = $this->getField('WEB_LARGEUR');
        }
        if (! is_numeric($height)) {
            $height = $this->getField('WEB_HAUTEUR');
        }

        // Lanceur JS
        $htmlObject = '<div class="videocontainer';
        if ($align != '') {
            $htmlObject .= ' align' . $align;
        }
        $htmlObject .= '">';
        $htmlObject .= '<div id="weboVideo_' . $this->getID() . '"';
        $htmlObject .= 'style="height:' . $height . 'px; width:' . $width . 'px;"';
        $htmlObject .= ' class="cms_videoplayer';

        $htmlObject .= '">Chargement ...</div>';
        $htmlObject .= '<script type="text/javascript">';
        if ($this->getField('WEB_AUDIODESCRIPTION') != '') {
            $htmlObject .= '
            audiodescription.addAudioDescription("weboVideo_' . $this->getID() . '", "' . UPLOAD_VIDEO . $this->getField('WEB_AUDIODESCRIPTION') . '");';
        }
        $htmlObject .= '
            jwplayer("weboVideo_' . $this->getID() . '").setup({';
        if ($src = $this->getVignetteSRC()) {
            $htmlObject .= '
                image: "' . $src . '",';
        }
        $aPlugin = array(
            'backstroke-1'
        );
        $htmlObject .= '
                plugins: {
                    "backstroke-1": {}';
        if ($this->getField('WEB_SOUSTITRE') != '') {
            $aPlugin[] = 'captions-2';
            $htmlObject .= ',
                    "captions": {
                        file: "' . UPLOAD_VIDEO . $this->getField('WEB_SOUSTITRE') . '",
                        state: true,
                        back: true
                    }';
        }
        if ($this->getField('WEB_AUDIODESCRIPTION') != '') {
            $aPlugin[] = 'audiodescription';
            $htmlObject .= ',
                    "audiodescription": {
                        file: "' . UPLOAD_VIDEO . $this->getField('WEB_AUDIODESCRIPTION') . '"
                    }';
        }
        $htmlObject .= '
                },';
        $htmlObject .= '
                skin: "' . SERVER_ROOT . 'include/flashplayer/jwplayer/skins/snel/snel.zip",
                height: ' . $height . ',
                width: ' . $width . ',
                modes: [';
        $extensionWEB_CHEMIN = pathinfo($this->getField('WEB_CHEMIN'), PATHINFO_EXTENSION);
        if ($this->getField('WEB_CHEMINWEBM') != '' || $this->getField('WEB_CHEMINMP4') != '' || $extensionWEB_CHEMIN == 'webm' || $extensionWEB_CHEMIN == 'mp4') {
            $htmlObject .= '
                    {
                        type: "html5",
                        config: {
                            levels: [';
            if ($this->getField('WEB_CHEMINMP4') != '' || $extensionWEB_CHEMIN == 'mp4') {
                $htmlObject .= '
                                { file: "' . UPLOAD_VIDEO . (($this->getField('WEB_CHEMINMP4') != '') ? $this->getField('WEB_CHEMINMP4') : $this->getField('WEB_CHEMIN')) . '" }';
                if ($this->getField('WEB_CHEMINWEBM') != '' || $extensionWEB_CHEMIN == 'webm') {
                    $htmlObject .= ',';
                }
            }
            if ($this->getField('WEB_CHEMINWEBM') != '' || $extensionWEB_CHEMIN == 'webm') {
                $htmlObject .= '
                                { file: "' . UPLOAD_VIDEO . (($this->getField('WEB_CHEMINWEBM') != '') ? $this->getField('WEB_CHEMINWEBM') : $this->getField('WEB_CHEMIN')) . '" }';
            }
            $htmlObject .= '
                            ]
                        }
                    },';
        }
        $htmlObject .= '
                    {
                        type: "flash",
                        src: "' . SERVER_ROOT . 'include/flashplayer/jwplayer/player.swf",
                        config: { file: "' . UPLOAD_VIDEO . $this->getField('WEB_CHEMIN') . '" }
                    },
                    { type: "download" }
                ]
            });
        </script>';

        // objet HTML pour noscript
        $htmlObject .= '<noscript>
        <style>#weboVideo_' . $this->getID() . ' { display: none; }</style>
        <object type="application/x-shockwave-flash" data="' . SERVER_ROOT . 'include/flashplayer/jwplayer/player.swf" width="' . $width . '" height="' . $height . '"';
        if ($align != '') {
            $htmlObject .= ' class="align' . $align . '"';
        }
        $htmlObject .= '>';
        $htmlObject .= '<param name="movie" value="' . SERVER_ROOT . 'include/flashplayer/jwplayer/player.swf">';
        $htmlObject .= '<param name="quality" value="best">';
        $htmlObject .= '<param name="width" value="' . $width . '">';
        $flashVars = 'file=' . UPLOAD_VIDEO . $this->getField('WEB_CHEMIN');
        $flashVars .= '&amp;title=' . encode($this->getField('WEB_LIBELLE'), false);
        if ($this->getField('WEB_SOUSTITRE') != '') {
            $flashVars .= '&amp;captions.file=' . UPLOAD_VIDEO . $this->getField('WEB_SOUSTITRE') . '&amp;captions.state=true&amp;captions.back=true';
        }
        if ($this->getField('WEB_AUDIODESCRIPTION') != '') {
            $flashVars .= '&amp;audiodescription.file=' . UPLOAD_VIDEO . $this->getField('WEB_AUDIODESCRIPTION');
        }
        $flashVars .= '&amp;plugins=' . implode(',', $aPlugin);
        if ($src) {
            $flashVars .= '&amp;image=' . $src;
        }
        $flashVars .= '&amp;skin=' . SERVER_ROOT . 'include/flashplayer/jwplayer/snel.zip';
        $htmlObject .= '<param name="FlashVars" value="' . $flashVars . '">';
        $htmlObject .= '<param name="allowFullScreen" value="true">';
        if ($src) {
            $htmlObject .= '<img src="' . $src . '" alt="' . encode($this->getField('WEB_LIBELLE'), false) . '">';
        }
        if ($this->getField('WEB_DESCRIPTIONACC') != '') {
            $htmlObject .= Editor::displayContent($this->getField('WEB_DESCRIPTIONACC'), CMS::getCurrentSite()->getHomePage());
        }
        $htmlObject .= '</object></noscript>';
        if ($this->getField('WEB_AUDIODESCRIPTION') != '') {
            $htmlObject .= '<a href="' . UPLOAD_VIDEO . $this->getField('WEB_AUDIODESCRIPTION') . '">Audio description</a>';
        }

        $htmlObject .= '</div>';

        return $htmlObject;
    }

    public function checkMD5()
    {
        if (empty($_FILES['WEB_CHEMIN']['tmp_name'])) {
            return false;
        }
        $md5 = md5_file($_FILES['WEB_CHEMIN']['tmp_name']);

        return parent::_checkMD5($md5);
    }

    public function postTraitement()
    {
        // mise à jour de l'éventuel contenu alternatif
        Editor::updateContent(isset($_POST['WEB_DESCRIPTIONACC']) ? $_POST['WEB_DESCRIPTIONACC'] : '', 'WEBOTHEQUE', 'WEB_DESCRIPTIONACC', 'ID_WEBOTHEQUE', $this->getID());

        // mise à jour des dimensions
        $stmt = $this->dbh->prepare('update WEBOTHEQUE set WEB_HAUTEUR=:WEB_HAUTEUR, WEB_LARGEUR=:WEB_LARGEUR where ID_WEBOTHEQUE=:ID_WEBOTHEQUE');
        $stmt->bindValue(':WEB_HAUTEUR', $_POST['WEB_HAUTEUR'], PDO::PARAM_INT);
        $stmt->bindValue(':WEB_LARGEUR', $_POST['WEB_LARGEUR'], PDO::PARAM_INT);
        $stmt->bindValue(':ID_WEBOTHEQUE', $this->getID(), PDO::PARAM_INT);
        $stmt->execute();

        // upload
        $fm = new File_management('WEBOTHEQUE', 'ID_WEBOTHEQUE', $this->getID(), UPLOAD_VIDEO_PHYSIQUE);
        $fm->setExtensions('.flv');
        if (! $fm->upload('WEB_CHEMIN', 'WEB_TAILLE', $this->getField('WEB_LIBELLE'))) {
            if ($fm->error == 'FM_EXTENSION') {
                setMsg(gettext('Alternative') . ' : ' . gettext('Extension incorrecte') . ' (' . implode(', ', $fm->getExtensions()) . ')', 'ERROR');
            } elseif (! empty($fm->error)) {
                setMsg(gettext('Alternative') . ' : ' . gettext('erreur') . ' ' . $fm->error, 'ERROR');
            }
        }
        $fm->setExtensions(array(
            '.webm'
        ));
        if (! $fm->upload('WEB_CHEMINWEBM', '', $this->getField('WEB_LIBELLE'))) {
            if ($fm->error == 'FM_EXTENSION') {
                setMsg(gettext('Alternative') . ' : ' . gettext('Extension incorrecte') . ' (' . implode(', ', $fm->getExtensions()) . ')', 'ERROR');
            } elseif (! empty($fm->error)) {
                setMsg(gettext('Alternative') . ' : ' . gettext('erreur') . ' ' . $fm->error, 'ERROR');
            }
            $fm->checkDelete('WEB_CHEMINWEBM');
        }
        $fm->setExtensions(array(
            '.mp4'
        ));
        if (! $fm->upload('WEB_CHEMINMP4', '', $this->getField('WEB_LIBELLE'))) {
            if ($fm->error == 'FM_EXTENSION') {
                setMsg(gettext('Alternative') . ' : ' . gettext('Extension incorrecte') . ' (' . implode(', ', $fm->getExtensions()) . ')', 'ERROR');
            } elseif (! empty($fm->error)) {
                setMsg(gettext('Alternative') . ' : ' . gettext('erreur') . ' ' . $fm->error, 'ERROR');
            }
            $fm->checkDelete('WEB_CHEMINMP4');
        }
        $fm->setExtensions(Webotheque::getExtension('WBT_IMAGE'));
        if (! $fm->upload('WEB_VIGNETTE', '', $this->getField('WEB_LIBELLE'))) {
            if ($fm->error == 'FM_EXTENSION') {
                setMsg(gettext('Vignette') . ' : ' . gettext('Extension incorrecte') . ' (' . implode(', ', $fm->getExtensions()) . ')', 'ERROR');
            } elseif (! empty($fm->error)) {
                setMsg(gettext('Vignette') . ' : ' . gettext('erreur') . ' ' . $fm->error, 'ERROR');
            }
            $fm->checkDelete('WEB_VIGNETTE');
        } else {
            $fm->resize($_POST['WEB_LARGEUR'], $_POST['WEB_HAUTEUR'], 60, '', 'crop');
        }
        $fm->setExtensions(Webotheque::$aExtensionSousTitre);
        if (! $fm->upload('WEB_SOUSTITRE', '', $this->getField('WEB_LIBELLE'))) {
            if ($fm->error == 'FM_EXTENSION') {
                setMsg(gettext('Sous titres') . ' : ' . gettext('Extension incorrecte') . ' (' . implode(', ', $fm->getExtensions()) . ')', 'ERROR');
            } elseif (! empty($fm->error)) {
                setMsg(gettext('Sous titres') . ' : ' . gettext('erreur') . ' ' . $fm->error, 'ERROR');
            }
            $fm->checkDelete('WEB_SOUSTITRE');
        }
        $fm->setExtensions(Webotheque::$aExtensionAudioDescription);
        if (! $fm->upload('WEB_AUDIODESCRIPTION', '', $this->getField('WEB_LIBELLE'))) {
            if ($fm->error == 'FM_EXTENSION') {
                setMsg(gettext('Audiodescription') . ' : ' . gettext('Extension incorrecte') . ' (' . implode(', ', $fm->getExtensions()) . ')', 'ERROR');
            } elseif (! empty($fm->error)) {
                setMsg(gettext('Audiodescription') . ' : ' . gettext('erreur') . ' ' . $fm->error, 'ERROR');
            }
            $fm->checkDelete('WEB_AUDIODESCRIPTION');
        }
        $sql = "update WEBOTHEQUE set WEB_CREDIT=" . $this->dbh->quote($_POST['WEB_CREDIT']) . " where ID_WEBOTHEQUE=" . $this->getID();
        $this->dbh->query($sql);

        return true;
    }

    public function getVignetteSRC($format = '')
    {
        if ($this->exist() && $this->getField('WEB_VIGNETTE') != '') {
            require_once CLASS_DIR . 'class.File_management.php';
            $fm = new File_management('DE_WEBOTHEQUE', 'ID_WEBOTHEQUE', $this->getID(), UPLOAD_VIDEO_PHYSIQUE);

            return $fm->getSRC($this->getField('WEB_VIGNETTE'), $format);
        }

        return false;
    }

    /**
     * Méthode des traitements spécifiques lors de l'insertion d'un élément en webothèque
     */
    public function updateSpecifique($nomFichier, &$error = '')
    {
        // Mise à jour de l'éventuel contenu alternatif
        require_once (CLASS_DIR . 'class.Link.php');
        Link::delete('WEBOTHEQUE', $this->getID());
        require_once (CLASS_DIR . 'class.Editor.php');
        Editor::updateContent($this->getField('WEB_DESCRIPTION'), 'WEBOTHEQUE', 'WEB_DESCRIPTION', 'ID_WEBOTHEQUE', $this->getID());
        // Mise à jour des dimensions
        $stmt = $this->dbh->prepare('update WEBOTHEQUE set WEB_HAUTEUR = :WEB_HAUTEUR, WEB_LARGEUR = :WEB_LARGEUR where ID_WEBOTHEQUE = :ID_WEBOTHEQUE');
        $stmt->bindValue(':WEB_HAUTEUR', $_POST['WEB_HAUTEUR'], PDO::PARAM_INT);
        $stmt->bindValue(':WEB_LARGEUR', $_POST['WEB_LARGEUR'], PDO::PARAM_INT);
        $stmt->bindValue(':ID_WEBOTHEQUE', $this->getID(), PDO::PARAM_INT);
        $stmt->execute();

        // upload
        $fm = new File_management('WEBOTHEQUE', 'ID_WEBOTHEQUE', $this->getID(), UPLOAD_VIDEO_PHYSIQUE);
        $fm->setExtensions(Webotheque::getExtension($this->code));
        if (file_exists($nomFichier)) { // Gestion import depuis le rep temp ou autre
            $physicalDir = dirname($nomFichier) . '/';
            $nomFichier = basename($nomFichier);
        } else {
            $physicalDir = Webotheque::getImportFtpPhysicalDir($this->code);
        }
        if (! $fm->upload('WEB_CHEMIN', 'WEB_TAILLE', $this->getField('WEB_LIBELLE'), $physicalDir, $nomFichier)) {
            $error = gettext('Fichier') . ' : ';
            if ($fm->error == 'FM_EXTENSION') {
                $error .= gettext('extention incorrect') . ' (' . implode(', ', $fm->getExtensions()) . ')';
            } elseif (! empty($fm->error)) {
                $error .= gettext('erreur') . ' ';
                if ($fm->error == 'FM_UPLOADSIZE') {
                    $error .= gettext('poids trop important');
                } else {
                    $error .= $fm->error;
                }
            } else {
                $error .= gettext('erreur');
            }
            setMsg($error, 'ERROR');

            return false;
        }

        return true;
    }
}

/**
 * Classe représantant les vidéos externes de la wébothèque
 *
 * @package CMS\DB\Webotheque
 */
class Webo_VIDEOEXTERNE extends Webotheque
{

    public function __construct($idtf = -1)
    {
        parent::__construct($idtf, 'WBT_VIDEOEXTERNE');
    }

    public static function getModuleCode()
    {
        return 'MOD_WEBOTHEQUE_VIDEOEXTERNE';
    }

    /**
     * Retourn le code HTML de l'objet Vidéo
     *
     * @param String $align
     * @param Int $width
     * @param Int $height
     * @return String
     */
    public function getHTML($align = '', $width = '', $height = '')
    {
        $html = $this->getField('WEB_DESCRIPTIONACC');
        $html = preg_replace('|\&(?![#0-9a-z]+;)|i', "&amp;", $html);
        if ($align == '') {
            $html = '<div class="iframe-ratio">' . $html . '</div>';
        } else {
            $html = '<div class="iframe-ratio width_' . $align . '">' . $html . '</div>';
        }

        return $html;
    }

    public function postTraitement()
    {
        $htmlObject = isset($_POST['WEB_DESCRIPTIONACC']) ? $_POST['WEB_DESCRIPTIONACC'] : '';
        // On tente de récupérer la largeur et le hauteur de l'élément
        if (preg_match('/height=[\'"]?([0-9]+)[\'" >]?/i', $htmlObject, $height)) {
            $height = $height[1];
        } else {
            $height = 200;
        }
        if (preg_match('/width=[\'"]?([0-9]+)[\'"\s>]?/i', $htmlObject, $width)) {
            $width = $width[1];
        } else {
            $width = 200;
        }
        // Mise à jour des dimensions
        $stmt = $this->dbh->prepare('update WEBOTHEQUE set WEB_HAUTEUR=:WEB_HAUTEUR, WEB_LARGEUR=:WEB_LARGEUR where ID_WEBOTHEQUE=:ID_WEBOTHEQUE');
        $stmt->bindValue(':WEB_HAUTEUR', $height, PDO::PARAM_INT);
        $stmt->bindValue(':WEB_LARGEUR', $width, PDO::PARAM_INT);
        $stmt->bindValue(':ID_WEBOTHEQUE', $this->getID(), PDO::PARAM_INT);
        $stmt->execute();

        return true;
    }
}

/**
 * Classe représantant les widgets de la wébothèque
 *
 * @package CMS\DB\Webotheque
 */
class Webo_WIDGET extends Webotheque
{

    public function __construct($idtf = -1)
    {
        parent::__construct($idtf, 'WBT_WIDGET');
    }

    public static function getModuleCode()
    {
        return 'MOD_WEBOTHEQUE_WIDGET';
    }
}

/**
 * Classe représantant les éléments audios de la wébothèque
 *
 * @package CMS\DB\Webotheque
 */
class Webo_MUSIC extends Webotheque
{

    public function __construct($idtf = -1)
    {
        parent::__construct($idtf, 'WBT_MUSIC');
    }

    public static function getModuleCode()
    {
        return 'MOD_WEBOTHEQUE_MUSIC';
    }

    public function delete($force = false, $deleteMultiple = false)
    {
        if (! $force && ! $this->isDeletable()) {
            return false;
        }
        $fm = new File_management('WEBOTHEQUE', 'ID_WEBOTHEQUE', $this->getID(), UPLOAD_VIDEO_PHYSIQUE);
        $fm->delete('WEB_CHEMIN');
        $fm->delete('WEB_CHEMINWEBM');

        return parent::delete($force, $deleteMultiple);
    }

    /**
     * Retourne le code HTML de l'objet Music pour un fichier
     *
     * @param String $align
     * @param Int $width
     *            A laisser vide, juste pour être compatible avec les autres classes
     * @param Int $height
     *            A laisser vide, juste pour être compatible avec les autres classes
     * @return String
     */
    public function getHTML($align = '', $width = '', $height = '')
    {
        require_once CLASS_DIR . 'class.Editor.php';
        $htmlObject = '<div class="audioPlayer">';

        $htmlObject .= '<audio controls ';
        if ($align != '') {
            $htmlObject .= ' class="align' . $align . '"';
        }
        $htmlObject .= 'width="320" height="30">';
        $htmlObject .= '<source type="audio/mp3" src="' . UPLOAD_MUSIC . $this->getField('WEB_CHEMIN') . '">';
        if ($this->getField('WEB_CHEMINWEBM') != '') {
            $htmlObject .= '<source type="audio/ogg" src="' . UPLOAD_MUSIC . $this->getField('WEB_CHEMINWEBM') . '">';
        }
        $htmlObject .= '<div class="htmlAlternative">' . Editor::displayContent($this->getField('WEB_DESCRIPTIONACC'), CMS::getCurrentSite()->getHomePage()) . '</div>';
        $htmlObject .= '</audio>';

        $htmlObject .= '</div>';

        return $htmlObject;
    }

    /**
     * Retourne le code HTML de l'objet Music pour plusieurs fichiers
     *
     * @param array $aID_WEBOTHEQUE
     *            tableau d'ID des fichiers audios à lire
     * @param String $align
     *            l'alignement du lecteur
     * @return String
     */
    public static function getHTMLMulti($aID_WEBOTHEQUE, $align = '')
    {
        $oWebo_MUSIC = new Webo_MUSIC(reset($aID_WEBOTHEQUE));

        $htmlObject = '<div class="audioPlayerMulti">';

        $htmlObject .= '<audio controls ';
        if ($align != '') {
            $htmlObject .= ' class="align' . $align . '"';
        }
        $htmlObject .= 'width="320" height="30">';
        $htmlObject .= '<source type="audio/mp3" src="' . UPLOAD_MUSIC . $oWebo_MUSIC->getField('WEB_CHEMIN') . '">';
        if ($oWebo_MUSIC->getField('WEB_CHEMINWEBM') != '') {
            $htmlObject .= '<source type="audio/ogg" src="' . UPLOAD_MUSIC . $oWebo_MUSIC->getField('WEB_CHEMINWEBM') . '">';
        }
        $htmlObject .= '<div class="htmlAlternative">' . Editor::displayContent($oWebo_MUSIC->getField('WEB_DESCRIPTIONACC'), CMS::getCurrentSite()->getHomePage()) . '</div>';
        $htmlObject .= '</audio>';

        $htmlObject .= '<ul class="playlist">';
        foreach ($aID_WEBOTHEQUE as $ID_WEBOTEHQUE) {
            $oWebo_MUSIC = new Webo_MUSIC($ID_WEBOTEHQUE);
            $audioProperties = array();
            $htmlAlternative = '';
            if ($oWebo_MUSIC->getField('WEB_DESCRIPTIONACC') != '') {
                $htmlAlternative .= Editor::displayContent($oWebo_MUSIC->getField('WEB_DESCRIPTIONACC'), CMS::getCurrentSite()->getHomePage());
            }
            $audioProperties['htmlAlternative'] = $htmlAlternative;
            $oggSrc = '';
            if ($oWebo_MUSIC->getField('WEB_CHEMINWEBM') != '') {
                $srcOgg = UPLOAD_MUSIC . $oWebo_MUSIC->getField('WEB_CHEMINWEBM');
            }
            $audioProperties['srcOgg'] = $srcOgg;
            $htmlObject .= '<li><a data-audio-properties="[' . encode(json_encode($audioProperties), false) . ']" href="' . UPLOAD_MUSIC . $oWebo_MUSIC->getField('WEB_CHEMIN') . '">' . encode($oWebo_MUSIC->getField('WEB_LIBELLE')) . '</a></li>';
        }
        $htmlObject .= '</ul>';

        $htmlObject .= '</div>';

        return $htmlObject;
    }

    public function checkMD5()
    {
        if (empty($_FILES['WEB_CHEMIN']['tmp_name'])) {
            return false;
        }
        $md5 = md5_file($_FILES['WEB_CHEMIN']['tmp_name']);

        return parent::_checkMD5($md5);
    }

    public function postTraitement()
    {
        // mise à jour de l'éventuel contenu alternatif
        Editor::updateContent(isset($_POST['WEB_DESCRIPTIONACC']) ? $_POST['WEB_DESCRIPTIONACC'] : '', 'WEBOTHEQUE', 'WEB_DESCRIPTIONACC', 'ID_WEBOTHEQUE', $this->getID());

        // mise à jour des dimensions pour l'affichage dans tinymce'
        $stmt = $this->dbh->prepare('update WEBOTHEQUE set WEB_HAUTEUR=:WEB_HAUTEUR, WEB_LARGEUR=:WEB_LARGEUR where ID_WEBOTHEQUE=:ID_WEBOTHEQUE');
        $stmt->bindValue(':WEB_HAUTEUR', 20, PDO::PARAM_INT);
        $stmt->bindValue(':WEB_LARGEUR', 160, PDO::PARAM_INT);
        $stmt->bindValue(':ID_WEBOTHEQUE', $this->getID(), PDO::PARAM_INT);
        $stmt->execute();

        // upload
        $fm = new File_management('WEBOTHEQUE', 'ID_WEBOTHEQUE', $this->getID(), UPLOAD_MUSIC_PHYSIQUE);
        $fm->setExtensions('.mp3');
        if (! $fm->upload('WEB_CHEMIN', 'WEB_TAILLE', $this->getField('WEB_LIBELLE'))) {
            if ($fm->error == 'FM_EXTENSION') {
                setMsg(gettext('Fichier') . ' : ' . gettext('Extension incorrecte') . ' (' . implode(', ', $fm->getExtensions()) . ')', 'ERROR');
            } elseif (! empty($fm->error)) {
                setMsg(gettext('Fichier') . ' : ' . gettext('erreur') . ' ' . $fm->error, 'ERROR');
            }
        }
        $fm->setExtensions(array(
            '.ogg'
        ));
        if (! $fm->upload('WEB_CHEMINWEBM', '', $this->getField('WEB_LIBELLE'))) {
            if ($fm->error == 'FM_EXTENSION') {
                setMsg(gettext('Alternative') . ' : ' . gettext('Extension incorrecte') . ' (' . implode(', ', $fm->getExtensions()) . ')', 'ERROR');
            } elseif (! empty($fm->error)) {
                setMsg(gettext('Alternative') . ' : ' . gettext('erreur') . ' ' . $fm->error, 'ERROR');
            }
            $fm->checkDelete('WEB_CHEMINWEBM');
        }

        return true;
    }

    /**
     * Méthode des traitements spécifiques lors de l'insertion d'un élément en webothèque
     */
    public function updateSpecifique($nomFichier, &$error = '')
    {
        // Mise à jour de l'éventuel contenu alternatif
        require_once (CLASS_DIR . 'class.Link.php');
        Link::delete('WEBOTHEQUE', $this->getID());
        require_once (CLASS_DIR . 'class.Editor.php');
        Editor::updateContent($this->getField('WEB_DESCRIPTION'), 'WEBOTHEQUE', 'WEB_DESCRIPTION', 'ID_WEBOTHEQUE', $this->getID());

        // Mise à jour des dimensions pour l'affichage dans tinymce'
        $stmt = $this->dbh->prepare('update WEBOTHEQUE set WEB_HAUTEUR = :WEB_HAUTEUR, WEB_LARGEUR = :WEB_LARGEUR where ID_WEBOTHEQUE = :ID_WEBOTHEQUE');
        $stmt->bindValue(':WEB_HAUTEUR', '20', PDO::PARAM_INT);
        $stmt->bindValue(':WEB_LARGEUR', '160', PDO::PARAM_INT);
        $stmt->bindValue(':ID_WEBOTHEQUE', $this->getID(), PDO::PARAM_INT);
        $stmt->execute();

        // upload
        $fm = new File_management('WEBOTHEQUE', 'ID_WEBOTHEQUE', $this->getID(), UPLOAD_MUSIC_PHYSIQUE);
        $fm->setExtensions(Webotheque::getExtension($this->code));
        if (file_exists($nomFichier)) { // Gestion import depuis le rep temp ou autre
            $physicalDir = dirname($nomFichier) . '/';
            $nomFichier = basename($nomFichier);
        } else {
            $physicalDir = Webotheque::getImportFtpPhysicalDir($this->code);
        }
        if (! $fm->upload('WEB_CHEMIN', 'WEB_TAILLE', $this->getField('WEB_LIBELLE'), $physicalDir, $nomFichier)) {
            $error = gettext('Fichier') . ' : ';
            if ($fm->error == 'FM_EXTENSION') {
                $error .= gettext('extention incorrect') . ' (' . implode(', ', $fm->getExtensions()) . ')';
            } elseif (! empty($fm->error)) {
                $error .= gettext('erreur') . ' ';
                if ($fm->error == 'FM_UPLOADSIZE') {
                    $error .= gettext('poids trop important');
                } else {
                    $error .= $fm->error;
                }
            } else {
                $error .= gettext('erreur');
            }
            setMsg($error, 'ERROR');

            return false;
        }

        return true;
    }
}

function seems_utf8($str)
{
    for ($i = 0; $i < strlen($str); $i ++) {
        if (ord($str[$i]) < 0x80)
            $n = 0; // 0bbbbbbb
        elseif ((ord($str[$i]) & 0xE0) == 0xC0)
            $n = 1; // 110bbbbb
        elseif ((ord($str[$i]) & 0xF0) == 0xE0)
            $n = 2; // 1110bbbb
        elseif ((ord($str[$i]) & 0xF0) == 0xF0)
            $n = 3; // 1111bbbb
        else
            return false; // Does not match any model
        for ($j = 0; $j < $n; $j ++) { // n octets that match 10bbbbbb follow ?
            if ((++ $i == strlen($str)) || ((ord($str[$i]) & 0xC0) != 0x80))
                return false;
        }
    }

    return true;
}
