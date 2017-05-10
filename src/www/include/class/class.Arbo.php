<?php
require_once CLASS_DIR . 'class.db_page.php';
require_once CLASS_DIR . 'class.db_webotheque.php';

class Arbo
{

    public $mode;

    private $type_arbo;

    private $xtParam;

    public function __construct($type, $aParam = array(), $mode = 'OFF_')
    {
        $this->type_arbo = $type;
        $this->mode = $mode;
        $this->xtParam = '';
        foreach ($aParam as $key => $val) {
            $this->xtParam .= '&amp;' . $key . '=' . urlencode($val);
        }
    }

    public static function action()
    {
        $str = '<div class="bo_arbo_action">';
        $str .= '<a href="#" id="closeArbo" class="btnAction">' . gettext('Fermer arborescence') . '</a>';
        $str .= '<a href="#" id="expandArbo" class="btnAction">' . gettext('Deployer arborescence') . '</a>';
        $str .= '</div>';
        return $str;
    }

    public function draw($ID_PAGE_DEPART = null)
    {
        if ($ID_PAGE_DEPART === null) {
            $ID_PAGE_DEPART = CMS::getCurrentSite()->getHomePage()->getID();
        }
        // les infos de la page de départ
        $oPageAccueil = new Page($ID_PAGE_DEPART, $this->mode);
        if (! $oPageAccueil->exist()) {
            return '!! Page inexistante : ' . $ID_PAGE_DEPART . '!!';
        }

        $accesPage = $oPageAccueil->checkAuthorized(false) || in_array($this->type_arbo, array(
            'COPIEPARTAGE',
            'TINY',
            'LIENINTERNE'
        ));
        $retour .= '<div class="bo_arbo">';
        $retour .= '<ul><li id="p' . $oPageAccueil->getID() . '"' . (! $accesPage ? ' class="denied"' : '') . '>';
        $retour .= '<div class="fade">';
        $retour .= $this->txt($oPageAccueil, $accesPage);
        $retour .= $this->otherTreeElements($oPageAccueil, $accesPage);
        $retour .= '</div>';
        $retour .= $this->explore($oPageAccueil, 1);
        $retour .= '</li></ul>';
        $retour .= '</div>';
        return $retour;
    }

    protected function explore($oPage)
    {
        $retour = '';
        $aChildren = $oPage->getChildrenForArbo();
        if (sizeof($aChildren) > 0) {
            $retour .= '<ul>';
            foreach ($aChildren as $oPageChild) {
                $accesPage = $oPageChild->checkAuthorized(false) || in_array($this->type_arbo, array(
                    'COPIEPARTAGE',
                    'TINY',
                    'LIENINTERNE'
                ));
                $retour .= '<li id="p' . $oPageChild->getID() . '"' . (! $accesPage ? ' class="denied"' : '') . '>';
                $retour .= '<div class="fade">';
                $retour .= $this->txt($oPageChild, $accesPage);
                $retour .= $this->otherTreeElements($oPageChild, $accesPage);
                $retour .= '</div>';
                $retour .= $this->explore($oPageChild);
                $retour .= '</li>';
            }
            $retour .= '</ul>';
        }
        return $retour;
    }

    private function txt($oPage, $accesPage)
    {
        $retour = '';
        $aClass = array(
            'txt'
        );
        if (! $accesPage) {
            $aClass[] = 'denied';
        }
        if ($this->mode == 'OFF_' && $accesPage) {
            $aClass[] = $oPage->getField('PST_CODE');
        }
        $retour .= '<span data-id_page="' . $oPage->getID() . '" class="' . implode(' ', $aClass) . '">' . secureInput($oPage->getField('PAG_TITRE_MENU'));
        $retour .= '<em>n°' . $oPage->getID() . '</em>';
        if (is_numeric($oPage->getField('ID_WEBOTHEQUE_LIENEXTERNE'))) {
            $oWebo = new Webo_LIENEXTERNE($oPage->getField('ID_WEBOTHEQUE_LIENEXTERNE'));
            $retour .= '<img src="' . SERVER_ROOT . 'images/page_go.png" alt="Redirection" title="' . secureInput($oWebo->getField('WEB_LIBELLE') . ' (idtf: ' . $oWebo->getID() . ')') . '">';
        } elseif (is_numeric($oPage->getField('ID_PAGE_REDIRECT'))) {
            $oRedir = new Page($oPage->getField('ID_PAGE_REDIRECT'));
            $retour .= '<img src="' . SERVER_ROOT . 'images/page_go.png" alt="Redirection" title="' . secureInput($oRedir->getField('PAG_TITRE_MENU') . ' (idtf: ' . $oRedir->getID() . ')') . '">';
        }
        if (! $oPage->getField('PAG_VISIBLE_MENU')) {
            $retour .= '<img src="' . SERVER_ROOT . 'images/page_invisible.png" alt="Non visible dans les menus" title="Non visible dans les menus">';
        }
        if (CMS::getCurrentSite()->hasModule(new Module('MOD_EXTRANET'))) {
            $sql = "select count(GROUPE.ID_GROUPE) from GROUPE
                inner join GROUPE_OFF_PAGE using (ID_GROUPE)
                where ID_PAGE=" . $oPage->getID() . " order by GRP_LIBELLE";
            $dbh = DB::getInstance();
            if ($dbh->query($sql)->fetch(PDO::FETCH_COLUMN) > 0) {
                $retour .= '<img src="' . SERVER_ROOT . 'images/page_white_key.png" alt="Accès restreint" title="Accès restreint">';
            }
        }
        $retour .= ' </span>';
        return $retour;
    }

    private function otherTreeElements($oPage, $accesPage)
    {
        if (! $accesPage) {
            return '';
        }
        $retour = '';
        switch ($this->type_arbo) {
            case 'REDACTIONNEL':
                if (! $oPage->isLocked() || Utilisateur::getConnected()->isRoot()) {
                    // bouton propriete
                    $retour .= '<a href="cms_page.php?idtf=' . $oPage->getID() . '" class="actionProprietes actionArbo">Propriétés</a>';
                    // bouton editer
                    $retour .= '<a href="cms_pseudo.php?idtf=' . $oPage->getID() . '&amp;PFM=1" class="actionEditer actionArbo">Editer</a>';
                }
                // bouton visualiser
                $retour .= '<a href="cms_pseudo.php?idtf=' . $oPage->getID() . '" class="actionVoir actionArbo">Visualiser</a>';
                // bouton ajout
                $retour .= '<a href="cms_pageAjout.php?idtf=' . $oPage->getID() . '" class="actionAjouter actionArbo">Ajouter une page</a>';
                break;
            case 'REFERENCEMENT':
                if (! $oPage->isLocked()) {
                    // bouton propriete
                    $retour .= '<a href="cms_page.php?idtf=' . $oPage->getID() . '" class="actionProprietes actionArbo">Propriétés</a>';
                }
                // bouton visualiser
                $retour .= '<a href="cms_pseudo.php?idtf=' . $oPage->getID() . '" class="actionVoir actionArbo">Visualiser</a>';
                break;
            case 'PERIMETRE':
                $retour .= '<a href="javascript:choixPerimetre(' . $oPage->getID() . ')" class="actionChoisir actionArbo">Choisir</a>';
                break;
            case 'COPIEPARTAGE':
                $retour .= '<a href="?ID_PAGE_DEST=' . $oPage->getID() . $this->xtParam . '" class="actionChoisir actionArbo">Choisir</a>';
                break;
            case 'LIENINTERNE':
                $retour .= '<a href="javascript:choixLien(' . $oPage->getID() . ',\'' . escapeJS($oPage->getField('PAG_TITRE_MENU')) . '\');" class="actionChoisir actionArbo">Choisir</a>';
                break;
            case 'TINY':
                $retour .= '<a href="?idtf=' . $oPage->getID() . $this->xtParam . '" class="actionChoisir actionArbo">Choisir</a>';
                break;
        }
        return $retour;
    }
}
