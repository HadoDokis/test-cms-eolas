<?php
require_once CLASS_DIR . 'class.db_generic.php';

class WebothequeCategorie extends Generic
{

    private $_code;

    protected static $_tree_wbtCode = null;

    protected static $_tree_sit_code = null;

    public function __construct($idtf, $wbt_code = null)
    {
        parent::__construct($idtf);
        $this->_code = $wbt_code;
    }

    public static function getModuleCode()
    {
        return str_replace('WBT_', 'MOD_WEBOTHEQUE_', $this->getField('WBT_CODE'));
    }

    public function load()
    {
        $sql = "select * from WEBOTHEQUECATEGORIE where ID_WEBOTHEQUECATEGORIE=" . $this->getID();
        if ($this->_code != null) {
            $sql .= " and WBT_CODE=" . $this->dbh->quote($this->_code);
        }
        if ($row = $this->dbh->query($sql)->fetch(PDO::FETCH_ASSOC)) {
            $this->setFields($row);
        } else {
            $this->_idtf = - 1;
            $this->setFields(array());
        }
    }

    public function isDeletable()
    {
        $sql = "select count(ID_WEBOTHEQUECATEGORIE) from WEBOTHEQUECATEGORIE where CAT_IDPARENT=" . $this->getID();
        if ($this->dbh->query($sql)->fetchColumn() != 0) {
            return false;
        }

        $sql = "select count(ID_WEBOTHEQUE) from WEBOTHEQUE where ID_WEBOTHEQUECATEGORIE=" . $this->getID();
        if ($this->dbh->query($sql)->fetchColumn() != 0) {
            return false;
        }

        return true;
    }

    public function delete()
    {
        if (! $this->isDeletable()) {
            return false;
        }

        $sql = "delete from WEBOTHEQUECATEGORIE where ID_WEBOTHEQUECATEGORIE=" . $this->getID();
        $this->dbh->exec($sql);

        return true;
    }

    public function getArbo($WBT_CODE, $idParent = null, $SITE_CODE = null, $modePopup = false)
    {
        if ($WBT_CODE == 'WBT_DOCUMENT') {
            $url = 'web_documentListe.php';
        } elseif ($WBT_CODE == 'WBT_LIENEXTERNE') {
            $url = 'web_lienExterneListe.php';
        } elseif ($WBT_CODE == 'WBT_MUSIC') {
            $url = 'web_musicListe.php';
        } elseif ($WBT_CODE == 'WBT_FLASH') {
            $url = 'web_flashListe.php';
        } elseif ($WBT_CODE == 'WBT_VIDEO') {
            $url = 'web_videoListe.php';
        } elseif ($WBT_CODE == 'WBT_VIDEOEXTERNE') {
            $url = 'web_videoExterneListe.php';
        } elseif ($WBT_CODE == 'WBT_IMAGE') {
            $url = 'web_imageListe.php';
        } elseif ($WBT_CODE == 'WBT_WIDGET') {
            $url = 'web_widgetListe.php';
        } else {
            $url = '#';
        }

        if (is_null($SITE_CODE)) {
            $SITE_CODE = CMS::getCurrentSite()->getID();
        }

        $dbh = DB::getInstance();
        $str = '';
        $sql = "select ID_WEBOTHEQUECATEGORIE, CAT_LIBELLE from WEBOTHEQUECATEGORIE
            where CAT_IDPARENT " . (is_null($idParent) ? "is null" : "=" . intval($idParent)) . " and WBT_CODE=" . $dbh->quote($WBT_CODE) . "
            and SIT_CODE=" . $dbh->quote($SITE_CODE) . "
            order by CAT_LIBELLE";
        $aRow = $dbh->query($sql)->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_COLUMN);
        if (count($aRow) > 0) {
            $str .= '<ul>';
            foreach ($aRow as $key => $value) {
                $str .= '<li>';
                $str .= '<div class="fade">';
                if ($modePopup) {
                    $str .= '<a href="javascript:maj(' . $key . ',\'' . escapeJS($value) . '\');">' . secureInput($value) . '</a>';
                    $str .= '<a href="javascript:maj(' . $key . ',\'' . escapeJS($value) . '\');" class="actionChoisir actionArbo">Choisir</a>';
                } else {
                    $str .= '<a href="' . $url . '?ID_WEBOTHEQUECATEGORIE=' . $key . '&amp;Find=1">' . secureInput($value) . '</a>';
                    $str .= '<a href="web_categorie.php?idtf=' . $key . '" class="actionEditer actionArbo">Modifier</a>';
                    $str .= '<a href="web_categorie.php?idtfParent=' . $key . '" class="actionAjouter actionArbo">Ajouter</a>';
                }
                $str .= '</div>';
                $str .= self::getArbo($WBT_CODE, $key, $SITE_CODE, $modePopup);
                $str .= '</li>';
            }
            $str .= '</ul>';
        }
        return $str;
    }

    public static function getChildrensID($aID)
    {
        if (count($aID) == 0) {
            return array();
        }
        $dbh = DB::getInstance();
        $sql = "select ID_WEBOTHEQUECATEGORIE from WEBOTHEQUECATEGORIE where CAT_IDPARENT in (" . implode(',', array_map('intval', $aID)) . ")";
        return array_merge($aID, self::getChildrensID($dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN)));
    }

    /**
     * getSelectOptions (Génére un select avec l'indentation des catégories)
     *
     * @param string  $WBT_CODE
     *                                                  WBT_CODE du type de wébothèque
     * @param integer $ID_WEBOTHEQUECATEGORIE_SELECTED
     *                                                  Permet de passer en paramètre l'élément sélectionné
     * @param integer $CAT_IDPARENT
     *                                                  Identifiant de la catégorie père - permet de ramener tous ces fils
     * @param string  $SITE_CODE
     *                                                  Code du site pôur lequel il faut générer les options (default = null)
     * @param integer $ID_WEBOTHEQUECATEGORIE_SOUSTRAIT
     *                                                  Identifiant d'une catégorie que l'on ne souhaite pas voir dans le select
     * @param integer $niveau
     *                                                  permet à la fonction de calculer le niveau récursivement
     */
    public static function getSelectOptions($WBT_CODE, $ID_WEBOTHEQUECATEGORIE_SELECTED = '', $CAT_IDPARENT = '', $SITE_CODE = null, $ID_WEBOTHEQUECATEGORIE_SOUSTRAIT = null, $niveau = 0)
    {
        $dbh = DB::getInstance();
        if (is_null($SITE_CODE)) {
            $SITE_CODE = CMS::getCurrentSite()->getID();
        }
        $sql = "select * from WEBOTHEQUECATEGORIE where SIT_CODE=" . $dbh->quote($SITE_CODE) . " and WBT_CODE=" . $dbh->quote($WBT_CODE);
        $sql .= (is_numeric($CAT_IDPARENT)) ? " and CAT_IDPARENT=" . intval($CAT_IDPARENT) : " and CAT_IDPARENT is null";
        $sql .= ' order by CAT_LIBELLE';
        $return = '';
        foreach ($dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if ($ID_WEBOTHEQUECATEGORIE_SOUSTRAIT == $row['ID_WEBOTHEQUECATEGORIE']) {
                continue;
            }
            $return .= '<option value="' . $row['ID_WEBOTHEQUECATEGORIE'] . '"';
            if ($row['ID_WEBOTHEQUECATEGORIE'] == $ID_WEBOTHEQUECATEGORIE_SELECTED) {
                $return .= ' selected';
            }
            $return .= '>' . str_repeat('&nbsp;&nbsp;', $niveau) . secureInput($row['CAT_LIBELLE']) . '</option>';
            $return .= self::getSelectOptions($WBT_CODE, $ID_WEBOTHEQUECATEGORIE_SELECTED, $row['ID_WEBOTHEQUECATEGORIE'], $SITE_CODE, $ID_WEBOTHEQUECATEGORIE_SOUSTRAIT, $niveau + 1);
        }
        return $return;
    }

    /**
     * Retourne le nombre de catégorie d'un type de webothèque
     *
     * @param  string $WBT_CODE
     *                          code du type de la webothèque
     * @return int    Le nombre de catégorie
     */
    public static function getNb($WBT_CODE)
    {
        $dbh = DB::getInstance();
        $sql = "select count(ID_WEBOTHEQUECATEGORIE) from WEBOTHEQUECATEGORIE where WBT_CODE=" . $dbh->quote($WBT_CODE) . " and SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID());
        return $dbh->query($sql)->fetchColumn();
    }

    /**
     * Met à jour la catégorie d'un élément
     *
     * @param string $cat_libelle
     *                             libelle de la nouvelle catégorie
     * @param int    $cat_idparent
     *                             identifiant de la catégorie parente pour la nouvelle catégorie (peut etre vide)
     * @param int    $cat_id
     *                             identifiant de la catégorie à mettre à jour (peut etre vide)
     * @param string $oWeb
     *                             objet webotheque associé
     *                             3 cas d'utilisation :
     *                             - Aucun paramètre : la méthode supprime les catégories vides
     *                             - 2 premiers paramètres : création et association de la nouvelle catégorie
     *                             - Troisième paramètre : association directe avec la catégorie précisée
     */
    public static function updateCategorie($cat_libelle = '', $cat_idparent = '', $cat_id = null, $oWeb = null)
    {
        $ID_WEBOTHEQUECATEGORIE = $cat_id;
        $dbh = DB::getInstance();
        if (! is_null($oWeb)) {
            // * Insertion d'une éventuelle nouvelle catégorie
            if ($cat_libelle != '') {
                $stmt = $dbh->prepare("insert into WEBOTHEQUECATEGORIE (
                    SIT_CODE,
                    WBT_CODE,
                    CAT_IDPARENT,
                    CAT_LIBELLE
                    ) values (
                    :SIT_CODE,
                    :WBT_CODE,
                    :CAT_IDPARENT,
                    :CAT_LIBELLE
                    )");
                $stmt->bindValue(':SIT_CODE', CMS::getCurrentSite()->getID(), PDO::PARAM_STR);
                $stmt->bindValue(':WBT_CODE', $oWeb->code, PDO::PARAM_STR);
                $stmt->bindValue(':CAT_IDPARENT', (is_numeric($cat_idparent)) ? $cat_idparent : null, PDO::PARAM_INT);
                $stmt->bindValue(':CAT_LIBELLE', $cat_libelle, PDO::PARAM_STR);
                $stmt->execute();
                $ID_WEBOTHEQUECATEGORIE = $dbh->lastInsertID();
            }

            // Mise à jour de l'association de l'élément en cours avec la catégorie (nouvellement créée ou précisée)
            if ($ID_WEBOTHEQUECATEGORIE) {
                $stmt = $dbh->prepare("update WEBOTHEQUE set
                    ID_WEBOTHEQUECATEGORIE = :ID_WEBOTHEQUECATEGORIE
                    where ID_WEBOTHEQUE= :ID_WEBOTHEQUE and SIT_CODE=:SIT_CODE");
                $stmt->bindValue(':ID_WEBOTHEQUECATEGORIE', $ID_WEBOTHEQUECATEGORIE, PDO::PARAM_INT);
                $stmt->bindValue(':ID_WEBOTHEQUE', $oWeb->getID(), PDO::PARAM_INT);
                $stmt->bindValue(':SIT_CODE', CMS::getCurrentSite()->getID(), PDO::PARAM_STR);
                $stmt->execute();
            }
        }
    }

    /**
     * Fonction pour la suppression des catégories sans élément
     *
     * @param string  $WBT_CODE
     *                              requis
     * @param string  $SIT_CODE
     *                              optionnel (CMS::getCurrentSite()->getID())
     * @param integer $CAT_IDPARENT
     *                              optionnel (null)
     *
     * @return void
     */
    public static function clearCategorie($WBT_CODE, $SIT_CODE = '', $CAT_IDPARENT = null)
    {
        $dbh = DB::getInstance();
        $SIT_CODE = ! empty($SIT_CODE) ? $SIT_CODE : CMS::getCurrentSite()->getID();
        $sql = "select * from WEBOTHEQUECATEGORIE
            where WBT_CODE=" . $dbh->quote($WBT_CODE) . " and SIT_CODE=" . $dbh->quote($SIT_CODE);
        if (is_numeric($CAT_IDPARENT)) {
            $sql .= " and CAT_IDPARENT=" . intval($CAT_IDPARENT);
        } else {
            $sql .= " and CAT_IDPARENT is null";
        }
        foreach ($dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
            self::clearCategorie($WBT_CODE, $SIT_CODE, $row['ID_WEBOTHEQUECATEGORIE']);
            $oCategorie = new self($row['ID_WEBOTHEQUECATEGORIE']);
            $oCategorie->setFields($row);
            $oCategorie->delete();
        }
    }
}
