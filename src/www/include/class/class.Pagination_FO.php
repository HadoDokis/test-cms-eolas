<?php
require_once CLASS_DIR . 'class.Pagination.php';

class Pagination_FO extends Pagination
{

    private $_oModule;

    private $_regletteCalled = false;

    public static function getModuleCode()
    {
        return 'MOD_RECHERCHE';
    }

    /**
     * Constructeur
     * @param identifiant un nom unique par objet
     * @return void
     */
    public function __construct($idtf = '')
    {
        unset ($_GET['idtf']);
        require_once CLASS_DIR . 'class.db_module.php';
        $this->_oModule = new Module($this->getModuleCode());
        parent::__construct($idtf);
    }

    /**
     * retourne la reglette de navigation
     * @param boolean voirResultat
     * @param boolean voirListePage
     * @param boolean addID (en cas d'usage de plusieurs reglettes pour une même pagination)
     * @return string
     */
    public function reglette($voirResultat = true, $voirListePage = true, $addID = true)
    {
        $_reglette = 5;

        if ($voirResultat || ($voirListePage && $this->_nbPage > 1)) {
            $retour = '<div class="blocNavigation"';
            if ($addID) {
                $retour .= ' id="' . $this->_idtf . '"';
            }
            $retour .= '>';
        }

        if ($voirResultat) {
            $retour .= '<div class="resultatNavigation">';
            if ($this->_nbResult == 0) {
                $retour .= $this->_oModule->i18n('Aucun_resultat');
            } else {
                $_nbResultAffiche = ($this->_mpp <= 0 || $this->_nbResult < $this->_mpp)
                    ? $this->_nbResult
                    : (($this->_index != $this->_nbPage)
                        ? $this->_mpp
                        : $this->_nbResult - ($this->_mpp * ($this->_nbPage-1)));
                $retour .= $_nbResultAffiche;
                $retour .= ($_nbResultAffiche > 1) ? $this->_oModule->i18n('resultats_sur') : $this->_oModule->i18n('resultat_sur');
                $retour .= $this->_nbResult;
                if ($this->_nbPage > 1) {
                    $retour .= ' - ' . $this->_oModule->i18n('Page') . ' ' . $this->_index . '/' . $this->_nbPage;
                }
            }
            $retour .= '</div>';
        }

        if ($voirListePage && $this->_nbPage > 1) {
            $checksum = $this->_mkChecksum($this->_orderBy);
            $retour .= '<div class="regletteNavigation">';

            //Première page
            if ($this->_index > $_reglette + 1) {
                $retour .= '<span>[<a href="' . $this->_URI(array ('i' . $this->_idtf => 1, 'o' . $this->_idtf => $this->_orderBy, 'c' . $this->_idtf => $checksum)) . '" title="' . $this->_oModule->i18n('Consulter_premiere_page') . '">' . $this->_oModule->i18n('Premiere') . '</a>]</span>';
            }

            //Si l'index courant est supérieur à la longueur de la réglette on affiche la réglette devant
            $debut = ($this->_index > $_reglette) ? $this->_index - $_reglette : 1;
            //Si l'index courant est inférieur à la longueur de la réglette on affiche la réglette après
            $fin = ($this->_index < $this->_nbPage - $_reglette) ? $this->_index + $_reglette : $this->_nbPage;
            for ($i = $debut; $i <= $fin; $i++) {
                if ($this->_index == $i) {
                    $retour .= '<span class="selected"><strong>' . $i . '</strong></span>';
                } else {
                    $retour .= '<span>[<a href="' . $this->_URI(array ('i' . $this->_idtf => $i, 'o' . $this->_idtf => $this->_orderBy, 'c' . $this->_idtf => $checksum)) . '" title="' . $this->_oModule->i18n('Consulter_page_X', array($i)) . '">' . $i . '</a>]</span>';
                }
            }

            //Dernière page
            if ($this->_index < ($this->_nbPage - $_reglette)) {
                $retour .= '<span>[<a href="' . $this->_URI(array ('i' . $this->_idtf => $this->_nbPage, 'o' . $this->_idtf => $this->_orderBy, 'c' . $this->_idtf => $checksum)) . '" title="' . $this->_oModule->i18n('Consulter_derniere_page') . '">' . $this->_oModule->i18n('Derniere') . '</a>]</span>';
            }

            $retour .= '</div>';

            //maj des balise suivant et precedent dans le header
            if ($this->_index > 1 && !$this->_regletteCalled) {
                $link = '<link rel="prev" href="' . $this->_URI(array ('i' . $this->_idtf => ($this->_index - 1), 'o' . $this->_idtf => $this->_orderBy, 'c' . $this->_idtf => $checksum)) . '" />';
                CMS::addHEADER($link);
            }
            if ($this->_index < $this->_nbPage && !$this->_regletteCalled) {
                $link = '<link rel="next" href="' . $this->_URI(array ('i' . $this->_idtf => ($this->_index + 1), 'o' . $this->_idtf => $this->_orderBy, 'c' . $this->_idtf => $checksum)) . '" />';
                CMS::addHEADER($link);
            }

            $this->_regletteCalled = true;

        }

        if ($voirResultat || ($voirListePage && $this->_nbPage > 1)) {
            $retour .= '</div>';
        }

        return $retour;
    }

    /**
     * affiche le contenu d'un en-tête de colonne ordonnançable et renvoie le code HTML correspondant
     * @param orderBy une chaine ou un tableau de type array('COL1' => 'asc', 'COL2' => 'desc', 'COL3' => '')
     * @return string
     */
    public function tri($libelle, $orderBy)
    {
        $orderByAsc = $this->_mkOrderBy($orderBy, 'asc');
        $orderByDesc = $this->_mkOrderBy($orderBy, 'desc');
        $retour = '';
        if ($this->_orderBy == $orderByAsc) {
            $retour .= '<img src="' . CMS::getCurrentSite()->getField('SIT_IMAGE') . 'triAscSelected.gif" alt="' . $this->_oModule->i18n('Tri_ascendant_sur_X', array($libelle)) . '" title="' . $this->_oModule->i18n('Tri_ascendant_sur_X', array($libelle)) . '">';
        } else {
            $retour = '<a href="' . $this->_URI(array ('o' . $this->_idtf => $orderByAsc, 'c' . $this->_idtf => $this->_mkChecksum($orderByAsc))) . '">';
            $retour .= '<img src="' . CMS::getCurrentSite()->getField('SIT_IMAGE') . 'triAsc.gif" alt="' . $this->_oModule->i18n('Tri_ascendant_sur_X', array($libelle)) . '" title="' . $this->_oModule->i18n('Tri_ascendant_sur_X', array($libelle)) . '"></a>';
        }
        $retour .= ' ' . encode($libelle, false) . ' ';
        if ($this->_orderBy == $orderByDesc) {
            $retour .= '<img src="' . CMS::getCurrentSite()->getField('SIT_IMAGE') . 'triDescSelected.gif" alt="' . $this->_oModule->i18n('Tri_descendant_sur_X', array($libelle)) . '" title="' . $this->_oModule->i18n('Tri_descendant_sur_X', array($libelle)) . '">';
        } else {
            $retour .= '<a href="' . $this->_URI(array ('o' . $this->_idtf => $orderByDesc, 'c' . $this->_idtf => $this->_mkChecksum($orderByDesc))) . '">';
            $retour .= '<img src="' . CMS::getCurrentSite()->getField('SIT_IMAGE') . 'triDesc.gif" alt="' . $this->_oModule->i18n('Tri_descendant_sur_X', array($libelle)) . '" title="' . $this->_oModule->i18n('Tri_descendant_sur_X', array($libelle)) . '"></a>';
        }

        return $retour;
    }

    /**
     * génère les boutons d'action du formulaire de recherche et renvoie le code HTML correspondant
     * @return string
     */
    public function getHiddens()
    {
        // page
        $oPage = CMS::getCurrentSite()->getCurrentPage();
        $retour = '<input type="hidden" name="idtf" value="' . $oPage->getID() . '">';

        //param
        foreach ($this->_extendedParams as $key => $val) {
            if (is_array ($val)) {
                foreach ($val as $valBis) {
                    $retour .= '<input type="hidden" name="' . $key . '[]" value="' . htmlspecialchars($valBis, ENT_QUOTES, 'UTF-8') . '">';
                }
            } else {
                $retour .= '<input type="hidden" name="' . $key . '" value="' . htmlspecialchars($val, ENT_QUOTES, 'UTF-8') . '">';
            }
        }

        //orderBy + checksum
        $retour .= '<input type="hidden" name="o' . $this->_idtf . '" value="' . htmlspecialchars($this->_orderBy, ENT_QUOTES, 'UTF-8') . '">';
        $retour .= '<input type="hidden" name="c' . $this->_idtf . '" value="' . htmlspecialchars($this->_mkChecksum($this->_orderBy), ENT_QUOTES, 'UTF-8') . '">';

        //mpp
        $retour .= '<input type="hidden" name="m' . $this->_idtf . '" value="' . $this->_mpp . '">';

        return $retour;
    }

    private function _URI($tabParam = array ())
    {
        $oPage = CMS::getCurrentSite()->getCurrentPage();
        $tabParam = array_merge($this->_params, $tabParam, array ('m' . $this->_idtf => $this->_mpp));

        return $oPage->getURLESCAPE($tabParam, $this->_idtf);
    }
}
