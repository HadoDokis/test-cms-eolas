<?php
require_once CLASS_DIR . 'class.db_generic.php';

class Thematique extends Generic
{

    public static function getModuleCode()
    {
        return 'MOD_THEMATIQUE';
    }

    public function load()
    {
        $sql = 'select * from THEMATIQUE where ID_THEMATIQUE = ' . $this->getID();
        if (($this->getID()>0) && ($row = $this->dbh->query($sql)->fetch(PDO::FETCH_ASSOC))) {
            $this->setFields($row);
        } else {
            $this->_idtf = -1;
            $this->setFields(array ());
        }
    }

    public function delete()
    {
        if (!$this->isDeletable()) {
            return false;
        }

        $sql = 'delete from LIAISON_THEMATIQUE where ID_THEMATIQUE = ' . $this->getID();
        $this->dbh->exec($sql);
        $sql = 'delete from THEMATIQUE where ID_THEMATIQUE = ' . $this->getID();
        $this->dbh->exec($sql);

        return true;
    }

    public function isDeletable()
    {
        return true;
    }

    /**
     * Renvoi un tableau contenant les objets thématiques
     *
     * @param  String $orderBy trier les résultats, contient le nom du champs de la table THEMATIQUE
     * @return array  $a_oThematique Tableau contenant les objets de la classe Thematique
     */
    public static function getListeThematiques($orderBy = 'THE_LIBELLE')
    {
        $dbh = DB::getInstance();
        $sql = 'select ID_THEMATIQUE
                from THEMATIQUE
                where ' . self::_getSiteSharedSQLFilter();
        if ($orderBy != '') {
            $sql .= ' order by ' . $orderBy;
        }
        $a_oThematique = array();
        foreach ($dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN) as $ID_THEMATIQUE) {
            $a_oThematique[] = new Thematique($ID_THEMATIQUE);
        }

        return $a_oThematique;
    }

    /**
     * Renvoi un tableau contenant les Id des Thématiques associés
     *
     * @param  string $LIA_CODE   Type de liaison -> référence à DD_LIAISON
     * @param  int    $ID_LIAISON Id de l'élément à laquelle fait référence la liaison
     * @return array
     */
    public static function getThematiques($LIA_CODE, $ID_LIAISON)
    {
        $dbh = DB::getInstance();
        $sql = 'select LIAISON_THEMATIQUE.ID_THEMATIQUE
                from LIAISON_THEMATIQUE
                inner join THEMATIQUE on LIAISON_THEMATIQUE.ID_THEMATIQUE=THEMATIQUE.ID_THEMATIQUE
                where LIA_CODE = ' . $dbh->quote($LIA_CODE) . '
                and ' . self::_getSiteSharedSQLFilter() . '
                and ID_LIAISON=' . intval($ID_LIAISON) . ' order by THEMATIQUE.THE_LIBELLE';
        $_aIdThematique = array();
        foreach ($dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $_aIdThematique[] = $row['ID_THEMATIQUE'];
        }

        return $_aIdThematique;
    }

    /**
     * Renvoi le nombre d'affectations à $LIA_CODE
     *
     * @param  string $LIA_CODE Type de liaison -> référence à DD_LIAISON
     * @return array
     */
    public function getNbAffectation($LIA_CODE)
    {
        $sql = 'select count(ID_LIAISON_THEMATIQUE)
                inner join THEMATIQUE on LIAISON_THEMATIQUE.ID_THEMATIQUE=THEMATIQUE.ID_THEMATIQUE
                from LIAISON_THEMATIQUE
                where LIA_CODE = ' . $this->dbh->quote($LIA_CODE) . '
                and ' . self::_getSiteSharedSQLFilter() . '
                and ID_THEMATIQUE = ' . intval($this->getID());

        return $this->dbh->query($sql)->fetchColumn();
    }

    /**
     * Renvoi un tableau contenant les ID des éléments affectés à la thématique de type $LIA_CODE
     *
     * @param  String  $LIA_CODE Type de liaison -> référence à DD_LIAISON
     * @return tableau d'ID
     */
    public function getAffectations($LIA_CODE)
    {
        $sql = 'select ID_LIAISON
                inner join THEMATIQUE on LIAISON_THEMATIQUE.ID_THEMATIQUE=THEMATIQUE.ID_THEMATIQUE
                from LIAISON_THEMATIQUE
                where LIA_CODE = ' . $this->dbh->quote($LIA_CODE) . '
                and ' . self::_getSiteSharedSQLFilter() . '
                and ID_THEMATIQUE = ' . intval($this->getID());
        $aIdLiaison = array();
        foreach ($this->dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN) as $ID_LIAISON) {
            $aIdLiaison[] = $ID_LIAISON;
        }

        return $aIdLiaison;
    }

    /**
     * Renvoi un tableau contenant tous les élements affectés à la thématique
     *
     * @param  Bool  $includeOffPages Permet d'inclure les pages OFF. En FO, mettre cette option à false.
     * @return array tableau à 2 dimentions, la clé de la première contient le libellé du type de liaison (LIA_LIBELLE) et la deuxième contien les infos nécessaire pour générer une liste. $aAffectes['XXX']['LIBELLE_AFFECTE'] contient le libellé de l'élément.
     */
    public function getAffectes($includeOffPages = true)
    {
        if ($includeOffPages) {
            $filtre = ' 1=1 ';
        } else {
            $filtre = ' DD_LIAISON.LIA_CODE<>\'OFF_PAGE\' ';
        }
        $sql = 'select DD_LIAISON.LIA_LIBELLE, DD_LIAISON.*, LIAISON_THEMATIQUE.*
                from LIAISON_THEMATIQUE
                left join DD_LIAISON on DD_LIAISON.LIA_CODE=LIAISON_THEMATIQUE.LIA_CODE
                left join THEMATIQUE on LIAISON_THEMATIQUE.ID_THEMATIQUE=THEMATIQUE.ID_THEMATIQUE
                where ' . $filtre . '
                and LIAISON_THEMATIQUE.ID_THEMATIQUE=' . $this->getID() . '
                and ' .  self::_getSiteSharedSQLFilter() . '
                order by DD_LIAISON.LIA_LIBELLE';
        $aAffectes = array() ;
        foreach ($this->dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_GROUP) as $libelle => $row) {
            preg_match('/@'.$_SESSION['S_LNG_CODE'].':(.[^@]*)/',$libelle,$key);
            foreach ($row as &$r) {
                $sql = "select ".$r['LIA_LIBELLE_CHAMP']." from ".$r['LIA_CODE']. " where " . $r['LIA_NOM_CHAMP_ID'] . " = " . $r['ID_LIAISON'] ;
                $r = array_merge($r,array('LIBELLE_AFFECTE'=>$this->dbh->query($sql)->fetchColumn()));
            }
            $aAffectes[isset($key[1])?$key[1]:$libelle] = $row;
        }

        return $aAffectes;
    }

    /**
     * Renvoi True si il existe des thematiques, false sinon. Utile pour savois si on génére ou non la liste des thématiques
     *
     * @return Bool
     */
    public static function thematiquesExist()
    {
        $dbh = DB::getInstance();
        $sql = 'select count(ID_THEMATIQUE)
                from THEMATIQUE
                where ' . self::_getSiteSharedSQLFilter();

        return ($dbh->query($sql)->fetchColumn()>0);
    }

    /**
     * Génere la liste des Thématiques avec des checkbox et labels associés. les input ont pour nom ID_THEMATIQUE[]
     *
     * @param array  $aID_THEMATIQUE Tableau des Thématiques à cocher
     * @param String $fieldName      Nom a donner au champs "name" de l'input.
     *                               Le champs est un tableau de input (le champs name est enfaite '$fieldName[]')
     *                               mais les '[]' sont ajoutés automatiquement.
     */
    public static function genererCheckbox($aID_THEMATIQUE = array(), $fieldName = 'ID_THEMATIQUE')
    {

        $sfInput  = '<input type="checkbox" name="%s[]" id="ID_THEMATIQUE_%d" value="%d"%s>';
        $sfInput .= '<label for="ID_THEMATIQUE_%d">%s</label>';

        $dbh = DB::getInstance();
        $sql = 'select *
                from THEMATIQUE
                where ' . self::_getSiteSharedSQLFilter() . ' order by THE_LIBELLE';

        foreach ($dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $checked = null;
            if (in_array($row['ID_THEMATIQUE'], $aID_THEMATIQUE)) {
                $checked = ' checked="checked"';
            }
            printf($sfInput, $fieldName, $row['ID_THEMATIQUE'], $row['ID_THEMATIQUE'],
                    $checked, $row['ID_THEMATIQUE'], encode($row['THE_LIBELLE']));
        }
    }

    /**
     * Génere la liste des Thématiques avec une selectbox. Le select a pour nom ID_THEMATIQUE
     *
     * @param array  $ID_THEMATIQUE : Thématique(s) à sélectionner
     * @param bool   $bWithAll=1    => avec une option "Toutes"
     * @param String $fieldName     Nom a donner au champs "name" du select.
     */
    public static function genererSelectbox($ID_THEMATIQUE = '', $fieldName = 'ID_THEMATIQUE')
    {
        $dbh = DB::getInstance();
        $sql = 'select *
                from THEMATIQUE
                where ' . self::_getSiteSharedSQLFilter() . ' order by THE_LIBELLE';
        echo '<select name="' .$fieldName. '" id="' .$fieldName. '">';
        echo '<option value=""></option>';
        foreach ($dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $selected = null;
            if ($row['ID_THEMATIQUE'] == $ID_THEMATIQUE) {
                $selected = ' selected';
            }
            echo '<option value="' . $row['ID_THEMATIQUE'] . '"' . $selected . '>' . secureInput($row['THE_LIBELLE']) . '</option>';
        }
        echo '</select>';
    }


    /**
     * Génere une liste de sélection Thématiques avec les ids et labels associés. l'input a pour nom ID_THEMATIQUE_MODULE
     *
     * @param Integer $aID_THEMATIQUE thématique preselectionné
     * @param String  $fieldName      Nom a donner au champs "name" de l'input. Le champs est un tableau de input (le champs name est enfaite '$fieldName[]') mais les '[]' sont ajoutés automatiquement.
     * @deprecated
     */
    public static function genererListeSelection($aID_THEMATIQUE = 0, $fieldName = 'ID_THEMATIQUE_MODULE')
    {

        throw new BadMethodCallException('Deprecated method');

        $dbh = DB::getInstance();
        $sql = 'select *
                from THEMATIQUE
                where ' . self::_getSiteSharedSQLFilter() . '
                order by THE_LIBELLE';

        $resultatsListeDesThematiques = array();
        $resultatsListeDesThematiques = $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($resultatsListeDesThematiques)) {
            if ($aID_THEMATIQUE == 0) {
                $aSelected = 'selected="selected"';
            } else {
                $aSelected = '';
            }
            $codeHtmlSelect = '<select name="ID_THEMATIQUE_MODULE" id="ID_THEMATIQUE_MODULE"><option value="0" ' . $aSelected . '>Toutes</option>';

            foreach ($resultatsListeDesThematiques as $row) {
                if ($aID_THEMATIQUE == $row['ID_THEMATIQUE']) {
                    $aSelected = 'selected';
                } else {
                    $aSelected = '';
                }
                $codeHtmlSelect = $codeHtmlSelect . '<option value="' . $row['ID_THEMATIQUE'] . '"' . $aSelected . '>' . $row['THE_LIBELLE'] . '</option>';
            }
            $codeHtmlSelect = $codeHtmlSelect . '</select>';
        } else {
            $codeHtmlSelect = '<select name="ID_THEMATIQUE_MODULE" id="ID_THEMATIQUE_MODULE" style="border : 1 solid #000000;"><option value="0" selected="selected">Toutes</option></select>';
        }
        echo $codeHtmlSelect;
    }


    public static function getSharedSQLFilter()
    {
        return self::_getSiteSharedSQLFilter();
    }

    /**
     * Retourne la chaine de caractèrtes du filtre SQL correspondant
     * à tout les codes des sites partagés avec le site courant
     *
     * @return string
     */
    private static function _getSiteSharedSQLFilter()
    {
        $dbh = DB::getInstance();
        $aRevertSharedSite = CMS::getCurrentSite()->getRevertSharedSites();
        if (count($aRevertSharedSite) == 0) {
            $filter = ' SIT_CODE = ' . $dbh->quote(CMS::getCurrentSite()->getID());
        } else {
            $filter = ' SIT_CODE in (' . $dbh->quote(CMS::getCurrentSite()->getID());
            foreach ($aRevertSharedSite as $SIT_CODE => $null) {
                $filter .= ', ' .  $dbh->quote($SIT_CODE);
            }
            $filter .= ')';
        }

        return $filter;
    }
}
