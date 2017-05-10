<?php

class Pagination
{

    protected $_idtf;

    protected $_orderBy;

    protected $_index;

    protected $_filter;

    protected $_maxResult;

    protected $_mpp;

    protected $_tabMPP;

    protected $_nbPage;

    protected $_nbResult;

    protected $_onSearch;

    protected $_params;

    protected $_extendedParams;

    /**
     * Constructeur
     *
     * @param
     *            identifiant un nom unique par objet
     * @return void
     */
    public function __construct($idtf = '')
    {
        $idtf = ($idtf == '') ? 'p' : 'p' . $idtf;
        $this->_idtf = $idtf;
        $this->_orderBy = '';
        $this->_index = 1;
        $this->_filter = '';
        $this->_mpp = 20;
        $this->_tabMPP = array(
            20,
            50,
            100
        );
        $this->_nbPage = 0;
        $this->_nbResult = 0;
        $this->_onSearch = ($_GET[$this->getSubmitName()]) || isset($_GET['Find']);
        $this->_params = array();
        $this->_extendedParams = array();

        unset($_GET[$this->getSubmitName()]);
        if (isset($_GET['o' . $this->_idtf])) {
            if ($this->_mkChecksum($_GET['o' . $this->_idtf]) == $_GET['c' . $this->_idtf]) {
                $this->setOrderBy($_GET['o' . $this->_idtf]);
                $this->_onSearch = true;
            }
            unset($_GET['o' . $this->_idtf]);
            unset($_GET['c' . $this->_idtf]);
        }
        if (is_numeric($_GET['i' . $this->_idtf])) {
            $this->_index = $_GET['i' . $this->_idtf];
            unset($_GET['i' . $this->_idtf]);
        }
        if (isset($_GET['m' . $this->_idtf])) {
            $this->setMPP($_GET['m' . $this->_idtf]);
            unset($_GET['m' . $this->_idtf]);
        }
        foreach ($_GET as $key => $val) {
            if (! is_array($val)) {
                $val = trim($val);
            }
            $this->_params[$key] = $val;
        }
    }

    /**
     * fixe le nombre de résultat (si présent, hérite du filtre)
     *
     * @param $sql une
     *            chaine du type 'select count(id) from table'
     * @return void
     */
    public function setCount($sql)
    {
        try {
            $dbh = DB::getInstance();
            if (is_array($sql)) {
                if ($this->_filter) {
                    foreach ($sql as $k => $req) {
                        if (is_array($this->_filter) && ! empty($this->_filter[$k])) {
                            $sql[$k] .= ' where ' . $this->_filter[$k];
                        } else
                            if ($this->_filter != '') {
                                $sql[$k] .= ' where ' . $this->_filter;
                            }
                    }
                }
                $sql = '(' . implode(')union(', $sql) . ')';
                $rows = $dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN);
                $nb = count($rows);
            } else {
                if ($this->_filter) {
                    $sql .= ' where ' . $this->_filter;
                }
                $nb = $dbh->query($sql)->fetchColumn();
            }

            $this->_nbResult = $nb;
            if (is_int($this->_maxResult) && $this->_nbResult > $this->_maxResult) {
                $this->_nbResult = $this->_maxResult;
            }
            $this->_nbPage = ($this->_mpp > 0) ? ceil($this->_nbResult / $this->_mpp) : 1;
        } catch (PDOException $e) {
            echo 'Erreur pagination (setCount) avec la requete : ' . $sql;
        }
    }

    /**
     * retourne le nombre de résultat
     *
     * @return int
     */
    public function getNb()
    {
        return $this->_nbResult;
    }

    /**
     * fixe le nombre d'affichage Maximum Par Page
     *
     * @param
     *            mpp (<=0 pas de limite)
     * @return void
     */
    public function setMPP($mpp)
    {
        $this->_mpp = intval($mpp);
        $this->_tabMPP = array_unique(array_merge($this->_tabMPP, array(
            $this->_mpp
        )));
        sort($this->_tabMPP);
        $this->_nbPage = ($this->_mpp > 0) ? ceil($this->_nbResult / $this->_mpp) : 1;
    }

    /**
     * fixe le critère d'ordonnancement
     *
     * @param
     *            orderBy une chaine ou un tableau de type array('COL1' => 'asc', 'COL2' => 'desc')
     * @return void
     */
    public function setOrderBy($orderBy)
    {
        $this->_orderBy = $this->_mkOrderBy($orderBy);
    }

    /**
     * retourne le nom à donner au bouton lançant la recherche
     *
     * @return string
     */
    public function getSubmitName()
    {
        return 'F' . $this->_idtf;
    }

    /**
     * indique si une recherche est en cours
     *
     * @return boolean
     */
    public function onSearch()
    {
        return $this->_onSearch;
    }

    /**
     * retourne la valeur htmlspecialcharisée (sauf si tableau) d'un parametre donné ou vide si inexistant
     *
     * @param
     *            key le nom du critère
     * @return string
     */
    public function getParam($key)
    {
        if (isset($this->_params[$key])) {
            if (is_array($this->_params[$key])) {
                return $this->_params[$key];
            }
            return htmlspecialchars($this->_params[$key], ENT_QUOTES, 'UTF-8');
        }
        return '';
    }

    /**
     * ajoute un parametre
     *
     * @param
     *            $key
     * @param
     *            $val
     * @param $extended false
     *            si le parametre ne doit pas etre ajouté aux champs hidden (si déjà présent dans un input par exemple)
     * @return void
     */
    public function setParam($key, $val, $extended = true)
    {
        $this->_params = array_merge($this->_params, array(
            $key => $val
        ));
        if ($extended) {
            $this->_extendedParams = array_merge($this->_extendedParams, array(
                $key => $val
            ));
        }
    }

    /**
     * ajoute un filtre au resultat de recherche
     *
     * @param
     *            filtre (attention cette chaine doit être protégée SQL)
     * @return void
     */
    public function setFilter($filter)
    {
        $this->_filter = $filter;
    }

    public function getFilter()
    {
        return $this->_filter;
    }

    public function setMaxResult($maxResult)
    {
        $this->_maxResult = intval($maxResult);
    }

    /**
     * @WARNING OPTIMISE POUR MYSQL ACTUELLEMENT
     *
     * @return array
     */
    public function fetch($sql, $groupBy = '', $useLimit = true)
    {
        try {
            $dbh = DB::getInstance();
            if (is_array($sql)) {
                foreach ($sql as $k => $req) {
                    if (is_array($this->_filter) && ! empty($this->_filter[$k])) {
                        $sql[$k] .= ' where ' . $this->_filter[$k];
                    } elseif ($this->_filter != '') {
                        $sql[$k] .= ' where ' . $this->_filter;
                    }
                    if (is_array($groupBy) && ! empty($groupBy[$k])) {
                        $sql[$k] .= ' group by ' . $groupBy[$k];
                    } elseif (! empty($groupBy)) {
                        $sql[$k] .= ' group by ' . $groupBy;
                    }
                }
                $sql = '(' . implode(')union(', $sql) . ')';

                if (! empty($this->_orderBy)) {
                    $sql .= ' order by ' . $this->_orderBy;
                }

                if (! empty($this->_mpp) && $useLimit) {
                    $sql .= ' limit ' . ($this->_mpp * ($this->_index - 1)) . ', ' . $this->_mpp;
                }
            } else {
                if ($this->_filter != '') {
                    $sql .= ' where ' . $this->_filter;
                }
                if ($groupBy != '') {
                    $sql .= ' group by ' . $groupBy;
                }
                if ($this->_orderBy != '') {
                    $sql .= ' order by ' . $this->_orderBy;
                }
                if ($this->_mpp > 0 && $useLimit) {
                    $sql .= ' limit ' . ($this->_mpp * ($this->_index - 1)) . ', ' . $this->_mpp;
                }
            }
            return $useLimit ? $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) : $dbh->query($sql, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo 'Erreur pagination (fetch) avec la requete : ' . $sql;
        }
    }

    /**
     * retourne la reglette de navigation
     *
     * @param
     *            boolean voirResultat
     * @param
     *            boolean voirListePage
     * @param
     *            boolean addID (en cas d'usage de plusieurs reglettes pour une même pagination)
     * @return string
     */
    public function reglette($voirResultat = true, $voirListePage = true, $addID = true)
    {
        $_reglette = 5;
        $retour = '<div class="blocNavigation"';
        if ($addID) {
            $retour .= ' id="' . $this->_idtf . '"';
        }
        $retour .= '>';

        if ($voirResultat) {
            $retour .= '<div class="resultatNavigation">';
            if ($this->_nbResult == 0) {
                $retour .= gettext('Aucun resultat');
            } else {
                $retour .= gettext('Il y a') . ' ' . number_format($this->_nbResult, 0, ',', ' ') . ' ';
                $retour .= ($this->_nbResult > 1) ? gettext('resultats') : gettext('resultat');
                if ($this->_nbPage > 1) {
                    $retour .= ' - ' . number_format($this->_nbPage, 0, ',', ' ') . ' ' . gettext('pages');
                }
            }
            $retour .= '</div>';
        }

        if ($voirListePage && $this->_nbPage > 1) {
            $checksum = $this->_mkChecksum($this->_orderBy);
            $retour .= '<div class="regletteNavigation">';

            // Première page
            if ($this->_index > $_reglette + 1) {
                $retour .= '<span><a href="' . $this->_URI(array(
                    'i' . $this->_idtf => 1,
                    'o' . $this->_idtf => $this->_orderBy,
                    'c' . $this->_idtf => $checksum
                )) . '">' . gettext('Premiere') . '</a></span>';
            }

            // Si l'index courant est supérieur à la longueur de la réglette on affiche la réglette devant
            $debut = ($this->_index > $_reglette) ? $this->_index - $_reglette : 1;
            // Si l'index courant est inférieur à la longueur de la réglette on affiche la réglette après
            $fin = ($this->_index < $this->_nbPage - $_reglette) ? $this->_index + $_reglette : $this->_nbPage;
            for ($i = $debut; $i <= $fin; $i ++) {
                if ($this->_index == $i) {
                    $retour .= '<span><strong>' . $i . '</strong></span>';
                } else {
                    $retour .= '<span><a href="' . $this->_URI(array(
                        'i' . $this->_idtf => $i,
                        'o' . $this->_idtf => $this->_orderBy,
                        'c' . $this->_idtf => $checksum
                    )) . '">' . $i . '</a></span>';
                }
            }

            // Dernière page
            if ($this->_index < ($this->_nbPage - $_reglette)) {
                $retour .= '<span><a href="' . $this->_URI(array(
                    'i' . $this->_idtf => $this->_nbPage,
                    'o' . $this->_idtf => $this->_orderBy,
                    'c' . $this->_idtf => $checksum
                )) . '">' . gettext('Derniere') . '</a></span>';
            }

            $retour .= '</div>';
        }
        $retour .= '</div>';

        return $retour;
    }

    /**
     * affiche le contenu d'un en-tête de colonne ordonnançable et renvoie le code HTML correspondant
     *
     * @param
     *            orderBy une chaine ou un tableau de type array('COL1' => 'asc', 'COL2' => 'desc', 'COL3' => '')
     * @return string
     */
    public function tri($libelle, $orderBy)
    {
        $orderByAsc = $this->_mkOrderBy($orderBy, 'asc');
        $orderByDesc = $this->_mkOrderBy($orderBy, 'desc');
        $retour = '';
        if ($this->_orderBy == $orderByAsc) {
            $retour .= '<img src="' . SERVER_ROOT . 'images/pagination/triAscSelected.gif" alt="">';
        } else {
            $retour = '<a href="' . $this->_URI(array(
                'o' . $this->_idtf => $orderByAsc,
                'c' . $this->_idtf => $this->_mkChecksum($orderByAsc)
            )) . '">';
            $retour .= '<img src="' . SERVER_ROOT . 'images/pagination/triAsc.gif" alt=""></a>';
        }
        $retour .= ' ' . $libelle . ' ';
        if ($this->_orderBy == $orderByDesc) {
            $retour .= '<img src="' . SERVER_ROOT . 'images/pagination/triDescSelected.gif" alt="">';
        } else {
            $retour .= '<a href="' . $this->_URI(array(
                'o' . $this->_idtf => $orderByDesc,
                'c' . $this->_idtf => $this->_mkChecksum($orderByDesc)
            )) . '">';
            $retour .= '<img src="' . SERVER_ROOT . 'images/pagination/triDesc.gif" alt=""></a>';
        }

        return $retour;
    }

    /**
     * génère les boutons d'action du formulaire de recherche et renvoie le code HTML correspondant
     *
     * @return string
     */
    public function actionRecherche()
    {
        // bug IE (si 1 seul INPUT)
        $retour = '<input type="hidden" name="' . $this->getSubmitName() . '" value="1">';

        // param
        foreach ($this->_extendedParams as $key => $val) {
            if (is_array($val)) {
                foreach ($val as $valBis) {
                    $retour .= '<input type="hidden" name="' . $key . '[]" value="' . htmlspecialchars($valBis, ENT_QUOTES, 'UTF-8') . '">';
                }
            } else {
                $retour .= '<input type="hidden" name="' . $key . '" value="' . htmlspecialchars($val, ENT_QUOTES, 'UTF-8') . '">';
            }
        }

        // orderBy + checksum
        $retour .= '<input type="hidden" name="o' . $this->_idtf . '" value="' . htmlspecialchars($this->_orderBy, ENT_QUOTES, 'UTF-8') . '">';
        $retour .= '<input type="hidden" name="c' . $this->_idtf . '" value="' . htmlspecialchars($this->_mkChecksum($this->_orderBy), ENT_QUOTES, 'UTF-8') . '">';

        // mpp
        if ($this->_mpp > 0) {
            $retour .= '<select name="m' . $this->_idtf . '">';
            foreach ($this->_tabMPP as $_valMPP) {
                $retour .= '<option value="' . $_valMPP . '"';
                if ($this->_mpp == $_valMPP)
                    $retour .= ' selected';
                $retour .= '>' . $_valMPP . '</option>';
            }
            $retour .= '</select> ';
        }

        // bouton
        $retour .= '<input type="submit" name="' . $this->getSubmitName() . '" value="Filtrer les résultats" class="submit">';

        return $retour;
    }

    /**
     *
     * @return string
     */
    public function makeLike($param)
    {
        $dbh = DB::getInstance();
        if (! isset($this->_params[$param])) {
            return '';
        }
        $param = $this->_params[$param];
        if (substr($param, 0, 1) == '[') {
            $param = substr($param, 1);
        } else {
            $param = '%' . $param;
        }
        if (substr($param, - 1) == ']') {
            $param = substr($param, 0, - 1);
        } else {
            $param .= '%';
        }

        return " like " . $dbh->quote($param);
    }

    /**
     *
     * @return string
     */
    public function makeQuote($param)
    {
        $dbh = DB::getInstance();
        if (! isset($this->_params[$param])) {
            return '';
        }
        return "=" . $dbh->quote($this->_params[$param]);
    }

    /**
     *
     * @return string
     */
    public function makeInt($param)
    {
        if (! isset($this->_params[$param])) {
            return '';
        }
        return "=" . intval($this->_params[$param]);
    }

    private function _URI($tabParam = array ())
    {
        $tabParam = array_merge($this->_params, $tabParam, array(
            'm' . $this->_idtf => $this->_mpp
        ));
        $param = '';
        foreach ($tabParam as $key => $val) {
            if (is_array($val)) {
                foreach ($val as $valBis) {
                    $param .= '&amp;' . urlencode($key . '[]') . '=' . urlencode($valBis);
                }
            } else {
                $param .= '&amp;' . urlencode($key) . '=' . urlencode($val);
            }
        }
        if ($param != '') {
            $param = substr($param, 5);
        }

        return PHP_SELF . '?' . $param . '#' . $this->_idtf;
    }

    protected function _mkOrderBy($orderBy, $tri = '')
    {
        if (is_array($orderBy)) {
            $_tmp = '';
            foreach ($orderBy as $key => $val) {
                $_tmp .= ',' . $key . ' ' . ((empty($val)) ? $tri : strtolower($val));
            }

            return trim(substr($_tmp, 1));
        } elseif ($tri != '') {
            return trim($orderBy . ' ' . $tri);
        } elseif ($orderBy != '') {
            $fin = strtolower(end(explode(' ', $orderBy)));
            if ($fin != 'asc' && $fin != 'desc') {
                $orderBy .= ' asc';
            }

            return $orderBy;
        }

        return '';
    }

    protected function _mkChecksum($orderBy)
    {
        return substr(md5($orderBy), 0, 20);
    }
}
