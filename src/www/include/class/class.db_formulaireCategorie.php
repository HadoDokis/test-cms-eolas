<?php
require_once CLASS_DIR . 'class.db_generic.php';

class FormulaireCategorie extends Generic
{
    public static function getModuleCode()
    {
        return 'MOD_FORMULAIRE';
    }

    public function load()
    {
        $sql = "select * from FORMULAIRECATEGORIE where ID_FORMULAIRECATEGORIE=" . $this->getID();
        if ($row = $this->dbh->query($sql)->fetch(PDO::FETCH_ASSOC)) {
            $this->setFields($row);
        } else {
            $this->_idtf = -1;
            $this->setFields(array ());
        }
    }

    public function isDeletable()
    {
        $sql = "select count(ID_FORMULAIRECATEGORIE) from FORMULAIRECATEGORIE where CAT_IDPARENT=" . $this->getID();
        if ($this->dbh->query($sql)->fetchColumn() != 0) {
            return false;
        }

        $sql = "select count(ID_FORMULAIRE) from FORMULAIRE where ID_FORMULAIRECATEGORIE=" . $this->getID();
        if ($this->dbh->query($sql)->fetchColumn() != 0) {
            return false;
        }

        return true;
    }

    public function delete()
    {
        if (!$this->isDeletable()) {
            return false;
        }

        $sql = "delete from FORMULAIRECATEGORIE where ID_FORMULAIRECATEGORIE=" . $this->getID();
        $this->dbh->exec($sql);

        return true;
    }

    public function getArbo($idParent = null)
    {
        $dbh = DB::getInstance();
        $str = '';
        $sql = 'select ID_FORMULAIRECATEGORIE, CAT_LIBELLE from FORMULAIRECATEGORIE
            where CAT_IDPARENT  ' . (is_null($idParent) ? 'is null' : ' = ' . intval($idParent)) . '
            and SIT_CODE=' . $dbh->quote(CMS::getCurrentSite()->getID()) . '
            order by CAT_LIBELLE';
        $aRow = $dbh->query($sql)->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_COLUMN);
        if (count($aRow) > 0) {
            $str .= '<ul>';
            foreach ($aRow as $key=>$value) {
                $str .= '<li>';
                $str .=  '<div class="fade">';
                $str .= '<a href="frm_formulaireListe.php?ID_FORMULAIRECATEGORIE=' . $key . '&amp;Find=1">' . secureInput($value) . '</a>';
                $str .= '<a href="frm_categorie.php?idtf=' . $key . '" class="actionEditer actionArbo">Modifier</a>';
                $str .= '<a href="frm_categorie.php?idtfParent=' . $key . '" class="actionAjouter actionArbo">Ajouter</a>';
                $str .= '</div>';
                $str .= self::getArbo($key);
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
        $dbh = DB :: getInstance();
        $sql = "select ID_FORMULAIRECATEGORIE from FORMULAIRECATEGORIE where CAT_IDPARENT in (" . implode(',' , array_map('intval', $aID)) . ")";
        return array_merge($aID, self::getChildrensID($dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN)));
    }

    /**
     * getSelectOptions (Génére un select avec l'indentation des catégories)
     *
     * @param integer $ID_FORMULAIRECATEGORIE_SELECTED  Permet de passer en paramètre l'élément sélectionné
     * @param integer $CAT_IDPARENT                     Identifiant de la catégorie père - permet de ramener tous ces fils
     * @param integer $ID_FORMULAIRECATEGORIE_SOUSTRAIT Identifiant d'une catégorie que l'on ne souhaite pas voir dans le select
     * @param integer $niveau                           permet à la fonction de calculer le niveau récursivement
     */
    public static function getSelectOptions($ID_FORMULAIRECATEGORIE_SELECTED, $CAT_IDPARENT = '', $ID_FORMULAIRECATEGORIE_SOUSTRAIT = null, $niveau = 0)
    {
        $dbh = DB::getInstance();
        $sql = "select * from FORMULAIRECATEGORIE where SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID());
        if (!Utilisateur::getConnected()->checkProfil(array('PRO_FORMGEST'), false)) {
            $sql .= " and FORMULAIRECATEGORIE.ID_FORMULAIRECATEGORIE in (
                select distinct(ID_FORMULAIRECATEGORIE) from FORMULAIRE_UTILISATEUR
                inner join FORMULAIRE on FORMULAIRE_UTILISATEUR.ID_FORMULAIRE = FORMULAIRE.ID_FORMULAIRE
                where ID_UTILISATEUR=" . Utilisateur::getConnected()->getID() . ")";
        }
        $sql .= (is_numeric($CAT_IDPARENT)) ? " and CAT_IDPARENT=" . intval($CAT_IDPARENT) : " and CAT_IDPARENT is null";
        $sql .= ' order by CAT_LIBELLE';
        $return = '';
        foreach ($dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if ($ID_FORMULAIRECATEGORIE_SOUSTRAIT == $row['ID_FORMULAIRECATEGORIE']) {
                continue;
            }
            $return .= '<option value="' . $row['ID_FORMULAIRECATEGORIE'] . '"';
            if ($row['ID_FORMULAIRECATEGORIE'] == $ID_FORMULAIRECATEGORIE_SELECTED) {
                $return .= ' selected';
            }
            $return .= '>' . str_repeat('&nbsp;&nbsp;', $niveau) . secureInput($row['CAT_LIBELLE']) . '</option>';
            $return .= self::getSelectOptions($ID_FORMULAIRECATEGORIE_SELECTED, $row['ID_FORMULAIRECATEGORIE'], $ID_FORMULAIRECATEGORIE_SOUSTRAIT, $niveau + 1);
        }
        return $return;
    }

    /**
     * Retourne le nombre de catégorie
     *
     * @return int Le nombre de catégorie
     */
    public static function getNb()
    {
        $dbh = DB::getInstance();
        $sql = "select count(ID_FORMULAIRECATEGORIE) from FORMULAIRECATEGORIE where SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID());
        if (!Utilisateur::getConnected()->checkProfil(array('PRO_FORMGEST'), false)) {
            $sql .= " and ID_FORMULAIRECATEGORIE in (
                select distinct(ID_FORMULAIRECATEGORIE) from FORMULAIRE_UTILISATEUR
                inner join FORMULAIRE on FORMULAIRE_UTILISATEUR.ID_FORMULAIRE = FORMULAIRE.ID_FORMULAIRE
                where ID_UTILISATEUR=" . Utilisateur::getConnected()->getID() . ")";
        }

        return intval($dbh->query($sql)->fetchColumn());
    }

    /**
     * Fonction pour la suppression des catégories sans élément
     *
     * @param string  $SIT_CODE
     *                              optionnel (CMS::getCurrentSite()->getID())
     * @param integer $CAT_IDPARENT
     *                              optionnel (null)
     *
     * @return void
     */
    public static function clearCategorie($SIT_CODE = '', $CAT_IDPARENT = null)
    {
        $dbh = DB::getInstance();
        $SIT_CODE = ! empty($SIT_CODE) ? $SIT_CODE : CMS::getCurrentSite()->getID();
        $sql = "select * from FORMULAIRECATEGORIE
            where SIT_CODE=" . $dbh->quote($SIT_CODE);
        if (is_numeric($CAT_IDPARENT)) {
            $sql .= " and CAT_IDPARENT=" . intval($CAT_IDPARENT);
        } else {
            $sql .= " and CAT_IDPARENT is null";
        }
        foreach ($dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
            self::clearCategorie($SIT_CODE, $row['ID_FORMULAIRECATEGORIE']);
            $oCategorie = new self($row['ID_FORMULAIRECATEGORIE']);
            $oCategorie->setFields($row);
            $oCategorie->delete();
        }
    }
}
