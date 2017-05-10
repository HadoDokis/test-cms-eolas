<?php
require_once CLASS_DIR . 'class.db_generic.php';

abstract class Ajax extends Generic
{

    private $_LIA_CODE;
    private $_TABLE_NAME;
    private $_ID_NAME;

    public function __construct($LIA_CODE, $ID_NAME, $idtf, $isInt = true)
    {
        $this->_LIA_CODE    = $LIA_CODE;
        $this->_TABLE_NAME  = $LIA_CODE;
        $this->_ID_NAME     = $ID_NAME;
        parent::__construct($idtf, $isInt);
    }

    private function getTempID()
    {
        return session_id();;
    }

    private function _getID()
    {
        return $this->exist() ? $this->getID() : $this->getTempID();
    }

    public function attachLiaison()
    {
        /* LIAISON_PAGE */
        //on va chercher les onlyOne pour synchro champ
        $sql = "select * from LIAISON_PAGE where LIA_TEMP like '%@%' and ID_LIAISON=" . $this->dbh->quote($this->getTempID());
        foreach ($this->dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $sql = "update " . $this->_TABLE_NAME . " set " .
                end(explode('@', $row['LIA_TEMP'])) . " = " . $row['ID_PAGE'] . "
                where " . $this->_ID_NAME . "=" . $this->dbh->quote($this->getID());
            $this->dbh->exec($sql);
        }
        //on met à jour l'identifiant définitif
        $sql = "update LIAISON_PAGE set ID_LIAISON=" . $this->dbh->quote($this->getID()) . ", LIA_TEMP='' where ID_LIAISON=" . $this->dbh->quote($this->getTempID());
        $this->dbh->exec($sql);
        //on purge les autres temporaires anciennes
        $sql = "delete from LIAISON_PAGE where LIA_TEMP<>'' and LIA_TEMP <" . (time() - 86400);
        $this->dbh->exec($sql);

        /* LIAISON_WEBOTHEQUE */
        //on va chercher les onlyOne pour synchro champ
        $sql = "select * from LIAISON_WEBOTHEQUE where LIA_TEMP like '%@%' and ID_LIAISON=" . $this->dbh->quote($this->getTempID());
        foreach ($this->dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $sql = "update " . $this->_TABLE_NAME . " set " .
                end(explode('@', $row['LIA_TEMP'])) . " = " . $row['ID_WEBOTHEQUE'] . "
                where " . $this->_ID_NAME . "=" . $this->dbh->quote($this->getID());
            $this->dbh->exec($sql);
        }
        //on met à jour l'identifiant définitif
        $sql = "update LIAISON_WEBOTHEQUE set ID_LIAISON=" . $this->dbh->quote($this->getID()) . ", LIA_TEMP='' where ID_LIAISON=" . $this->dbh->quote($this->getTempID());
        $this->dbh->exec($sql);
        //on purge les autres temporaires anciennes
        $sql = "delete from LIAISON_WEBOTHEQUE where LIA_TEMP<>'' and LIA_TEMP <" . (time() - 86400);
        $this->dbh->exec($sql);

        /* LIAISON_EXTERNE */
        //on va chercher les onlyOne pour synchro champ
        $sql = "select * from LIAISON_EXTERNE where LIA_TEMP like '%@%' and LIA_CODE_FROM='" . $this->_LIA_CODE . "' and ID_LIAISON_FROM=" . $this->dbh->quote($this->getTempID());
        foreach ($this->dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $sql = "update " . $this->_TABLE_NAME . "
                    set " . end(explode('@', $row['LIA_TEMP'])) . " = " . $this->dbh->quote($row['ID_LIAISON_TO']) . "
                    where " . $this->_ID_NAME . "=" . $this->dbh->quote($this->getID());
            $this->dbh->exec($sql);
        }
        //on met à jour l'identifiant définitif
        $sql = "update LIAISON_EXTERNE
                set
                    ID_LIAISON_FROM = " . $this->dbh->quote($this->getID()) . ",
                    LIA_TEMP = ''
                where LIA_CODE_FROM = '" . $this->_LIA_CODE . "'
                and ID_LIAISON_FROM=" . $this->dbh->quote($this->getTempID());
        $this->dbh->exec($sql);
        //on purge les autres temporaires anciennes
        $sql = "delete from LIAISON_EXTERNE where LIA_TEMP<>'' and LIA_TEMP <" . (time() - 86400);
        $this->dbh->exec($sql);

        /* LIAISON_UTILISATEUR */
        //on va chercher les onlyOne pour synchro champ
        $sql = "select * from LIAISON_UTILISATEUR where LIA_TEMP like '%@%' and ID_LIAISON=" . $this->dbh->quote($this->getTempID());
        foreach ($this->dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $sql = "update " . $this->_TABLE_NAME . " set " .
                end(explode('@', $row['LIA_TEMP'])) . " = " . $row['ID_UTILISATEUR'] . "
                where " . $this->_ID_NAME . "=" . $this->dbh->quote($this->getID());
            $this->dbh->exec($sql);
        }
        //on met à jour l'identifiant définitif
        $sql = "update LIAISON_UTILISATEUR set ID_LIAISON=" . $this->dbh->quote($this->getID()) . ", LIA_TEMP='' where ID_LIAISON=" . $this->dbh->quote($this->getTempID());
        $this->dbh->exec($sql);
        //on purge les autres temporaires anciennes
        $sql = "delete from LIAISON_UTILISATEUR where LIA_TEMP<>'' and LIA_TEMP <" . (time() - 86400);
        $this->dbh->exec($sql);
    }

    /////////////////////
    // Liaison Webotheque
    /////////////////////
    /**
     * A surcharger le cas échéant :
     * Méthode exécutée avant l'enregistrement de chaque liaison
     *
     * @return Ajax
     */
    protected function _onPreSaveLiaisonWebotheque()
    {
        return $this;
    }

    public function saveLiaisonWebotheque($ID_WEBOTHEQUE, $onlyOneOfWBT_CODE = '', $LIA_TYPE = '', $LIA_TEXT = null)
    {
        require_once CLASS_DIR . 'class.Link.php';

        $oWebotheque = new Webotheque($ID_WEBOTHEQUE);
        if ($oWebotheque->checkAuthorized(false) || $oWebotheque->checkShareAuthorized(false)) {

            $this->_onPreSaveLiaisonWebotheque();

            $LIA_TEMP = '';
            if ($onlyOneOfWBT_CODE != '') {
                $colonne = 'ID_WEBOTHEQUE' . substr($onlyOneOfWBT_CODE, 3) . (empty($LIA_TYPE) ? '' : '_' . $LIA_TYPE);
                if ($this->exist()) {
                    $sql = "update " . $this->_TABLE_NAME . " set " .
                        $colonne . "=" . intval($ID_WEBOTHEQUE) . "
                        where " . $this->_ID_NAME . "=" . $this->dbh->quote($this->getID());
                    $this->dbh->exec($sql);
                } else {
                    $LIA_TEMP = time() . '@' . $colonne;
                }

                //suppression ancienne liaison éventuelle
                $sql = "select ID_WEBOTHEQUE from WEBOTHEQUE
                        where WBT_CODE = " . $this->dbh->quote($onlyOneOfWBT_CODE) . "
                        and ID_WEBOTHEQUE in (
                            select ID_WEBOTHEQUE
                            from LIAISON_WEBOTHEQUE
                            where LIA_CODE = '" . $this->_TABLE_NAME . "'
                            and ID_LIAISON = " . $this->dbh->quote($this->_getID()) . "
                            and LIA_TYPE = " . $this->dbh->quote($LIA_TYPE) . "
                        )";
                $aID_WEBOTHEQUE = $this->dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN);
                if (count($aID_WEBOTHEQUE) > 0) {
                    Link::delete($this->_TABLE_NAME, $this->_getID(), "and ID_WEBOTHEQUE in (" . implode(',', $aID_WEBOTHEQUE) . ") and LIA_TYPE = " . $this->dbh->quote($LIA_TYPE));
                }
            } elseif (!$this->exist()) {
                $LIA_TEMP = time();
            }
            Link::insertWebotheque($this->_TABLE_NAME, $this->_getID(), $ID_WEBOTHEQUE, null, $LIA_TYPE, $LIA_TEMP, $LIA_TEXT);

            $this->_onPostSaveLiaisonWebotheque();
        } else {
            echo 'Ressource interdite : ' . $ID_WEBOTHEQUE;
        }
    }

    /**
     * A surcharger le cas échéant :
     * Méthode exécutée après l'enregistrement de chaque liaison
     *
     * @return Ajax
     */
    protected function _onPostSaveLiaisonWebotheque()
    {
        // Purge du cache de l'ensemble du site
        require_once CLASS_DIR . 'class.db_page.php';
        Page::clearCache();

        return $this;
    }

    public function upLiaisonWebotheque($ID_LIAISON_WEBOTHEQUE)
    {
        require_once CLASS_DIR . 'class.Link.php';
        //mon ordre
        $sql = "select LIA_ORDRE, WBT_CODE, LIA_TYPE from LIAISON_WEBOTHEQUE
            inner join WEBOTHEQUE using(ID_WEBOTHEQUE)
            where ID_LIAISON_WEBOTHEQUE=" . intval($ID_LIAISON_WEBOTHEQUE);
        $row = $this->dbh->query($sql)->fetch(PDO::FETCH_ASSOC);

        // inverser l'ordre juste avant
        $sql = "select ID_LIAISON_WEBOTHEQUE from LIAISON_WEBOTHEQUE
            inner join WEBOTHEQUE using(ID_WEBOTHEQUE)
            where LIA_TYPE = ". $this->dbh->quote($row['LIA_TYPE']) ."
            and LIA_CODE=" . $this->dbh->quote($this->_TABLE_NAME) . "
            and WBT_CODE=" . $this->dbh->quote($row['WBT_CODE']) . "
            and ID_LIAISON=" . $this->dbh->quote($this->_getID()) . "
            and LIA_ORDRE<" . intval($row['LIA_ORDRE']) . "
            order by LIA_ORDRE desc limit 0, 1";
        $sql = "update LIAISON_WEBOTHEQUE set LIA_ORDRE=" . intval($row['LIA_ORDRE']) . " where ID_LIAISON_WEBOTHEQUE=" . intval($this->dbh->query($sql)->fetchColumn());
        $this->dbh->exec($sql);

        // monter mon ordre
        $sql = "update LIAISON_WEBOTHEQUE set LIA_ORDRE=LIA_ORDRE-1 where ID_LIAISON_WEBOTHEQUE=" . intval($ID_LIAISON_WEBOTHEQUE);
        $this->dbh->exec($sql);

        // tout remettre dans l'ordre pour etre ok
        Link::reorderLiaisonWebotheque($this->_TABLE_NAME, $row['WBT_CODE'], $this->_getID(), $row['LIA_TYPE']);

        // Purge du cache de l'ensemble du site
        require_once CLASS_DIR . 'class.db_page.php';
        Page::clearCache();
    }

    public function downLiaisonWebotheque($ID_LIAISON_WEBOTHEQUE)
    {
        require_once CLASS_DIR . 'class.Link.php';
        //mon ordre
        $sql = "select LIA_ORDRE, WBT_CODE, LIA_TYPE from LIAISON_WEBOTHEQUE
            inner join WEBOTHEQUE using(ID_WEBOTHEQUE)
            where ID_LIAISON_WEBOTHEQUE=" . intval($ID_LIAISON_WEBOTHEQUE);
        $row = $this->dbh->query($sql)->fetch(PDO::FETCH_ASSOC);

        // inverser l'ordre juste après
        $sql = "select ID_LIAISON_WEBOTHEQUE from LIAISON_WEBOTHEQUE
            inner join WEBOTHEQUE using(ID_WEBOTHEQUE)
            where LIA_TYPE = ". $this->dbh->quote($row['LIA_TYPE']) ."
            and LIA_CODE=" . $this->dbh->quote($this->_TABLE_NAME) . "
            and WBT_CODE=" . $this->dbh->quote($row['WBT_CODE']) . "
            and ID_LIAISON=" . $this->dbh->quote($this->_getID()) . "
            and LIA_ORDRE>" . intval($row['LIA_ORDRE']) . "
            order by LIA_ORDRE limit 0, 1";
        $sql = "update LIAISON_WEBOTHEQUE set LIA_ORDRE=" . intval($row['LIA_ORDRE']) . " where ID_LIAISON_WEBOTHEQUE=" . intval($this->dbh->query($sql)->fetchColumn());
        $this->dbh->exec($sql);

        // descendre mon ordre
        $sql = "update LIAISON_WEBOTHEQUE set LIA_ORDRE=LIA_ORDRE+1 where ID_LIAISON_WEBOTHEQUE=" . intval($ID_LIAISON_WEBOTHEQUE);
        $this->dbh->exec($sql);

        // tout remettre dans l'ordre pour etre ok
        Link::reorderLiaisonWebotheque($this->_TABLE_NAME, $row['WBT_CODE'], $this->_getID(), $row['LIA_TYPE']);

        // Purge du cache de l'ensemble du site
        require_once CLASS_DIR . 'class.db_page.php';
        Page::clearCache();
    }

    /**
     * A surcharger le cas échéant :
     * Méthode exécutée avant la suppression de chaque liaison
     *
     * @return Ajax
     */
    protected function _onPreDeleteLiaisonWebotheque()
    {
        return $this;
    }

    public function deleteLiaisonWebotheque($ID_WEBOTHEQUE, $WBT_CODE, $onlyOne = false, $LIA_TYPE = '')
    {
        $this->_onPreDeleteLiaisonWebotheque();

        require_once CLASS_DIR . 'class.Link.php';
        if ($onlyOne) {
            $sql = "update " . $this->_TABLE_NAME . " set
                ID_WEBOTHEQUE" . substr($WBT_CODE, 3) . (empty($LIA_TYPE) ? '' : '_'.$LIA_TYPE) . "=null
                where " . $this->_ID_NAME . "=" . $this->dbh->quote($this->_getID());
            $this->dbh->exec($sql);
        }
        Link::delete($this->_TABLE_NAME, $this->_getID(), "and ID_WEBOTHEQUE=" . intval($ID_WEBOTHEQUE). " and LIA_TYPE=" . $this->dbh->quote($LIA_TYPE));
        Link::reorderLiaisonWebotheque($this->_TABLE_NAME, $WBT_CODE, $this->_getID(), $LIA_TYPE);

        $this->_onPostDeleteLiaisonWebotheque();
    }

    /**
     * A surcharger le cas échéant :
     * Méthode exécutée après la suppression de chaque liaison
     *
     * @return Ajax
     */
    protected function _onPostDeleteLiaisonWebotheque()
    {
        // Purge du cache de l'ensemble du site
        require_once CLASS_DIR . 'class.db_page.php';
        Page::clearCache();

        return $this;
    }

    public function getLiaisonWebotheque($WBT_CODE, $LIA_TYPE = '')
    {
        require_once CLASS_DIR . 'class.Link.php';

        return Link::getLiaisonWebotheque($this->_TABLE_NAME, $WBT_CODE, $this->_getID(), $LIA_TYPE);
    }

    public function getIdLiaisonWebotheque($WBT_CODE, $LIA_TYPE = '')
    {
        require_once CLASS_DIR . 'class.Link.php';
        $aRow = Link::getLiaisonWebotheque($this->_TABLE_NAME, $WBT_CODE, $this->getID(), $LIA_TYPE);
        $aID_LIAISON = array();
        foreach ($aRow as $row) {
            $aID_LIAISON[] = $row['ID_WEBOTHEQUE'];
        }

        return $aID_LIAISON;
    }

    //////////////
    //Liaison Page
    //////////////
    /**
     * A surcharger le cas échéant :
     * Méthode exécutée avant l'enregistrement de chaque liaison
     *
     * @return Ajax
     */
    protected function _onPreSaveLiaisonPage()
    {
        return $this;
    }

    public function saveLiaisonPage($ID_PAGE, $onlyOne, $LIA_TYPE = '', $LIA_TEXT = null)
    {
        require_once CLASS_DIR . 'class.Link.php';
        require_once CLASS_DIR . 'class.db_page.php';
        $oPage = new Page($ID_PAGE);
        if ($oPage->exist() && ($oPage->getField('SIT_CODE')==CMS::getCurrentSite()->getID() || array_key_exists($oPage->getField('SIT_CODE'), CMS::getCurrentSite()->getRevertSharedSites()))) {

            $this->_onPreSaveLiaisonPage();

            $LIA_TEMP = '';
            if ($onlyOne) {
                $colonne = 'ID_PAGE' . (empty($LIA_TYPE) ? '' : '_' . $LIA_TYPE);
                if ($this->exist()) {
                    $sql = "update " . $this->_TABLE_NAME . " set " .
                        $colonne . "=" . intval($ID_PAGE) . "
                        where " . $this->_ID_NAME . "=" . $this->dbh->quote($this->getID());
                    $this->dbh->exec($sql);
                } else {
                    $LIA_TEMP = time() . '@' . $colonne;
                }

                //suppression ancienne liaison éventuelle
                $sql = "select ID_PAGE
                        from OFF_PAGE
                        where ID_PAGE in (
                            select ID_PAGE
                            from LIAISON_PAGE
                            where LIA_CODE = '" . $this->_TABLE_NAME . "'
                            and ID_LIAISON = " . $this->dbh->quote($this->_getID()) . "
                            and LIA_TYPE = " . $this->dbh->quote($LIA_TYPE) .")";
                $aID_PAGE = $this->dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN);
                if (count($aID_PAGE) > 0) {
                    Link::delete($this->_TABLE_NAME, $this->_getID(), "and ID_PAGE in (" . implode(',', $aID_PAGE) . ") and LIA_TYPE=" . $this->dbh->quote($LIA_TYPE));
                }
            } elseif (!$this->exist()) {
                $LIA_TEMP = time();
            }
            Link::insertPage($this->_TABLE_NAME, $this->_getID(), $ID_PAGE, null, null, $LIA_TYPE, $LIA_TEMP, $LIA_TEXT);

            $this->_onPostSaveLiaisonPage();
        } else {
            echo 'Ressource interdite : ' . $ID_PAGE;
        }
    }

    /**
     * A surcharger le cas échéant :
     * Méthode exécutée après l'enregistrement de chaque liaison
     *
     * @return Ajax
     */
    protected function _onPostSaveLiaisonPage()
    {
        // Purge du cache de l'ensemble du site
        require_once CLASS_DIR . 'class.db_page.php';
        Page::clearCache();

        return $this;
    }

    public function upLiaisonPage($ID_LIAISON_PAGE)
    {
        require_once CLASS_DIR . 'class.Link.php';
        //mon ordre
        $sql = "select LIA_ORDRE, LIA_TYPE from LIAISON_PAGE where ID_LIAISON_PAGE=" . intval($ID_LIAISON_PAGE);
        $row = $this->dbh->query($sql)->fetch(PDO::FETCH_ASSOC);

        // inverser l'ordre juste avant
        $sql = "select ID_LIAISON_PAGE from LIAISON_PAGE
            where LIA_TYPE = " . $this->dbh->quote($row['LIA_TYPE']) . "
            and LIA_CODE=" . $this->dbh->quote($this->_TABLE_NAME) . "
            and ID_LIAISON=" . $this->dbh->quote($this->_getID()) . "
            and LIA_ORDRE<" . intval($row['LIA_ORDRE']) . "
            order by LIA_ORDRE desc limit 0, 1";
        $sql = "update LIAISON_PAGE set LIA_ORDRE=" . intval($row['LIA_ORDRE']) . " where ID_LIAISON_PAGE=" . intval($this->dbh->query($sql)->fetchColumn());
        $this->dbh->exec($sql);

        // monter mon ordre
        $sql = "update LIAISON_PAGE set LIA_ORDRE=LIA_ORDRE-1 where ID_LIAISON_PAGE=" . intval($ID_LIAISON_PAGE);
        $this->dbh->exec($sql);

        // tout remettre dans l'ordre pour etre ok
        Link::reorderLiaisonPage($this->_TABLE_NAME, $this->_getID(), $row['LIA_TYPE']);

        // Purge du cache de l'ensemble du site
        require_once CLASS_DIR . 'class.db_page.php';
        Page::clearCache();
    }

    public function downLiaisonPage($ID_LIAISON_PAGE)
    {
        require_once CLASS_DIR . 'class.Link.php';
        //mon ordre
        $sql = "select LIA_ORDRE, LIA_TYPE from LIAISON_PAGE where ID_LIAISON_PAGE=" . intval($ID_LIAISON_PAGE);
        $row = $this->dbh->query($sql)->fetch(PDO::FETCH_ASSOC);

        // inverser l'ordre juste après
        $sql = "select ID_LIAISON_PAGE from LIAISON_PAGE
            where LIA_TYPE = " . $this->dbh->quote($row['LIA_TYPE']) . "
            and LIA_CODE=" . $this->dbh->quote($this->_TABLE_NAME) . "
            and ID_LIAISON=" . $this->dbh->quote($this->_getID()) . "
            and LIA_ORDRE>" . intval($row['LIA_ORDRE']) . "
            order by LIA_ORDRE limit 0, 1";
        $sql = "update LIAISON_PAGE set LIA_ORDRE=" . intval($row['LIA_ORDRE']) . " where ID_LIAISON_PAGE=" . intval($this->dbh->query($sql)->fetchColumn());
        $this->dbh->exec($sql);

        // descendre mon ordre
        $sql = "update LIAISON_PAGE set LIA_ORDRE=LIA_ORDRE+1 where ID_LIAISON_PAGE=" . intval($ID_LIAISON_PAGE);
        $this->dbh->exec($sql);

        // tout remettre dans l'ordre pour etre ok
        Link::reorderLiaisonPage($this->_TABLE_NAME, $this->_getID(), $row['LIA_TYPE']);

        // Purge du cache de l'ensemble du site
        require_once CLASS_DIR . 'class.db_page.php';
        Page::clearCache();
    }

    /**
     * A surcharger le cas échéant :
     * Méthode exécutée après la suppression de chaque liaison
     *
     * @return Ajax
     */
    protected function _onPreDeleteLiaisonPage()
    {
        return $this;
    }

    public function deleteLiaisonPage($ID_PAGE, $onlyOne =  false, $LIA_TYPE = '')
    {

        $this->_onPreDeleteLiaisonPage();

        require_once CLASS_DIR . 'class.Link.php';
        if ($onlyOne) {
            $sql = "update " . $this->_TABLE_NAME . " set
                ID_PAGE" . (empty($LIA_TYPE) ? '' : '_' . $LIA_TYPE) . "=null
                where " . $this->_ID_NAME . "=" . $this->dbh->quote($this->_getID());
            $this->dbh->exec($sql);
        }
        Link::delete($this->_TABLE_NAME, $this->_getID(), "and ID_PAGE=" . intval($ID_PAGE) . " and LIA_TYPE=" . $this->dbh->quote($LIA_TYPE));
        Link::reorderLiaisonPage($this->_TABLE_NAME, $this->_getID(), $LIA_TYPE);

        $this->_onPostDeleteLiaisonPage();
    }

    /**
     * A surcharger le cas échéant :
     * Méthode exécutée après la suppression de chaque liaison
     *
     * @return Ajax
     */
    protected function _onPostDeleteLiaisonPage()
    {
        // Purge du cache de l'ensemble du site
        require_once CLASS_DIR . 'class.db_page.php';
        Page::clearCache();

        return $this;
    }

    public function getLiaisonPage($LIA_TYPE = '')
    {
        require_once CLASS_DIR . 'class.Link.php';

        return Link::getLiaisonPage($this->_TABLE_NAME, $this->_getID(), $LIA_TYPE);
    }

    public function getIdLiaisonPage($LIA_TYPE = '')
    {
        require_once CLASS_DIR . 'class.Link.php';
        $aRow = Link::getLiaisonPage($this->_TABLE_NAME, $this->getID(), $LIA_TYPE);
        $aID_LIAISON = array();
        foreach ($aRow as $row) {
            $aID_LIAISON[] = $row['ID_PAGE'];
        }

        return $aID_LIAISON;
    }

    ///////////////////////
    //Liaison Utilisateur//
    ///////////////////////
    /**
     * A surcharger le cas échéant :
     * Méthode exécutée avant l'enregistrement de chaque liaison
     *
     * @return Ajax
     */
    protected function _onPreSaveLiaisonUtilisateur()
    {
        return $this;
    }


    public function saveLiaisonUtilisateur($ID_UTILISATEUR, $onlyOne, $LIA_TYPE = '', $LIA_TEXT = null)
    {
        require_once CLASS_DIR . 'class.Link.php';
        require_once CLASS_DIR . 'class.db_utilisateur.php';

        $oUtilisateur = new Utilisateur($ID_UTILISATEUR);
        if ($oUtilisateur->exist() && ($oUtilisateur->getField('SIT_CODE') == CMS::getCurrentSite()->getID() || array_key_exists($oUtilisateur->getField('SIT_CODE'), CMS::getCurrentSite()->getRevertSharedSites()))) {

            $this->_onPreSaveLiaisonUtilisateur();//TODO verifier si pas de test prealable

            $LIA_TEMP = '';
            if ($onlyOne) {
                $colonne = 'ID_UTILISATEUR' . (empty($LIA_TYPE) ? '' : '_' . $LIA_TYPE);
                if ($this->exist()) {
                    $sql = "update " . $this->_TABLE_NAME . " set " .
                        $colonne . "=" . intval($ID_UTILISATEUR) . "
                        where " . $this->_ID_NAME . "=" . $this->dbh->quote($this->getID());
                    $this->dbh->exec($sql);
                } else {
                    $LIA_TEMP = time() . '@' . $colonne;
                }

                //suppression ancienne liaison éventuelle
                $sql = "select ID_UTILISATEUR
                        from LIAISON_UTILISATEUR
                        where LIA_CODE = '" . $this->_TABLE_NAME . "'
                        and ID_LIAISON = " . $this->dbh->quote($this->_getID()) . "
                        and LIA_TYPE = " . $this->dbh->quote($LIA_TYPE);
                $aID_UTILISATEUR = $this->dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN);
                if (count($aID_UTILISATEUR) > 0) {
                    Link::delete($this->_TABLE_NAME, $this->_getID(), "and ID_UTILISATEUR in (" . implode(',', $aID_UTILISATEUR) . ") and LIA_TYPE=" . $this->dbh->quote($LIA_TYPE));
                }
            } elseif (!$this->exist()) {
                $LIA_TEMP = time();
            }

            Link::insertUtilisateur($this->_TABLE_NAME, $this->getID(), $ID_UTILISATEUR, null, $LIA_TYPE, $LIA_TEMP, $LIA_TEXT);

            $this->_onPostSaveLiaisonUtilisateur();

        } else {
            echo 'Ressource interdite : ' . $ID_UTILISATEUR;
        }

    }

    protected function _onPostSaveLiaisonUtilisateur()
    {
        // Purge du cache de l'ensemble du site
        require_once CLASS_DIR . 'class.db_page.php';
        Page::clearCache();

        return $this;
    }

    public function upLiaisonUtilisateur($ID_LIAISON_UTILISATEUR)
    {
        require_once CLASS_DIR . 'class.Link.php';
        //mon ordre
        $sql = "select LIA_ORDRE, LIA_TYPE from LIAISON_UTILISATEUR
            inner join UTILISATEUR using(ID_UTILISATEUR)
            where ID_LIAISON_UTILISATEUR=" . intval($ID_LIAISON_UTILISATEUR);
        $row = $this->dbh->query($sql)->fetch(PDO::FETCH_ASSOC);

        // inverser l'ordre juste avant
        $sql = "select ID_LIAISON_UTILISATEUR from LIAISON_UTILISATEUR
            inner join UTILISATEUR using(ID_UTILISATEUR)
            where LIA_TYPE = ". $this->dbh->quote($row['LIA_TYPE']) ."
            and LIA_CODE=" . $this->dbh->quote($this->_TABLE_NAME) . "
            and ID_LIAISON=" . $this->dbh->quote($this->_getID()) . "
            and LIA_ORDRE<" . intval($row['LIA_ORDRE']) . "
            order by LIA_ORDRE desc limit 0, 1";
        $sql = "update LIAISON_UTILISATEUR set LIA_ORDRE=" . intval($row['LIA_ORDRE']) . " where ID_LIAISON_UTILISATEUR=" . intval($this->dbh->query($sql)->fetchColumn());
        $this->dbh->exec($sql);

        // monter mon ordre
        $sql = "update LIAISON_UTILISATEUR set LIA_ORDRE=LIA_ORDRE-1 where ID_LIAISON_UTILISATEUR=" . intval($ID_LIAISON_UTILISATEUR);
        $this->dbh->exec($sql);

        // tout remettre dans l'ordre pour etre ok
        Link::reorderLiaisonUtilisateur($this->_TABLE_NAME, $this->_getID(), $row['LIA_TYPE']);

        // Purge du cache de l'ensemble du site
        require_once CLASS_DIR . 'class.db_page.php';
        Page::clearCache();
    }

    public function downLiaisonUtilisateur($ID_LIAISON_UTILISATEUR)
    {
        require_once CLASS_DIR . 'class.Link.php';
        //mon ordre
        $sql = "select LIA_ORDRE, LIA_TYPE from LIAISON_UTILISATEUR
            inner join UTILISATEUR using(ID_UTILISATEUR)
            where ID_LIAISON_UTILISATEUR=" . intval($ID_LIAISON_UTILISATEUR);
        $row = $this->dbh->query($sql)->fetch(PDO::FETCH_ASSOC);

        // inverser l'ordre juste après
        $sql = "select ID_LIAISON_UTILISATEUR from LIAISON_UTILISATEUR
            inner join UTILISATEUR using(ID_UTILISATEUR)
            where LIA_TYPE = ". $this->dbh->quote($row['LIA_TYPE']) ."
            and LIA_CODE=" . $this->dbh->quote($this->_TABLE_NAME) . "
            and ID_LIAISON=" . $this->dbh->quote($this->_getID()) . "
            and LIA_ORDRE>" . intval($row['LIA_ORDRE']) . "
            order by LIA_ORDRE limit 0, 1";
        $sql = "update LIAISON_UTILISATEUR set LIA_ORDRE=" . intval($row['LIA_ORDRE']) . " where ID_LIAISON_UTILISATEUR=" . intval($this->dbh->query($sql)->fetchColumn());
        $this->dbh->exec($sql);

        // descendre mon ordre
        $sql = "update LIAISON_UTILISATEUR set LIA_ORDRE=LIA_ORDRE+1 where ID_LIAISON_UTILISATEUR=" . intval($ID_LIAISON_UTILISATEUR);
        $this->dbh->exec($sql);

        // tout remettre dans l'ordre pour etre ok
        Link::reorderLiaisonUtilisateur($this->_TABLE_NAME, $this->_getID(), $row['LIA_TYPE']);

        // Purge du cache de l'ensemble du site
        require_once CLASS_DIR . 'class.db_page.php';
        Page::clearCache();
    }

    /**
     * A surcharger le cas échéant :
     * Méthode exécutée avant la suppression de chaque liaison
     *
     * @return Ajax
     */
    protected function _onPreDeleteLiaisonUtilisateur()
    {
        return $this;
    }

    public function deleteLiaisonUtilisateur($ID_UTILISATEUR, $onlyOne =  false, $LIA_TYPE = '')
    {

        $this->_onPreDeleteLiaisonUtilisateur();

        require_once CLASS_DIR . 'class.Link.php';
        if ($onlyOne) {
            $sql = "update " . $this->_TABLE_NAME . " set
                ID_UTILISATEUR" . (empty($LIA_TYPE) ? '' : '_' . $LIA_TYPE) . "=null
                where " . $this->_ID_NAME . "=" . $this->dbh->quote($this->_getID());
            $this->dbh->exec($sql);
        }
        Link::delete($this->_TABLE_NAME, $this->_getID(), "and ID_UTILISATEUR=" . intval($ID_UTILISATEUR) . " and LIA_TYPE=" . $this->dbh->quote($LIA_TYPE));
        Link::reorderLiaisonUtilisateur($this->_TABLE_NAME, $this->_getID(), $LIA_TYPE);

        $this->_onPostDeleteLiaisonUtilisateur();
    }

    protected function _onPostDeleteLiaisonUtilisateur()
    {
        // Purge du cache de l'ensemble du site
        require_once CLASS_DIR . 'class.db_page.php';
        Page::clearCache();

        return $this;
    }

    public function getLiaisonUtilisateur($LIA_TYPE = '')
    {
        require_once CLASS_DIR . 'class.Link.php';

        return Link::getLiaisonUtilisateur($this->_TABLE_NAME, $this->getID(), $LIA_TYPE);
    }

    public function getIdLiaisonUtilisateur($LIA_TYPE = '')
    {
        require_once CLASS_DIR . 'class.Link.php';
        $aRow = Link::getLiaisonUtilisateur($this->_TABLE_NAME, $this->getID(), $LIA_TYPE);
        $aID_LIAISON = array();
        foreach ($aRow as $row) {
            $aID_LIAISON[] = $row['ID_UTILISATEUR'];
        }

        return $aID_LIAISON;
    }

    /////////////////
    //Liaison Externe
    /////////////////
    /**
     * A surcharger le cas échéant :
     * Méthode exécutée avant l'enregistrement de chaque liaison
     *
     * @return Ajax
     */
    protected function _onPreSaveLiaisonExterne()
    {
        return $this;
    }

    public function saveLiaisonExterne($ID_LIAISON_TO, $LIA_CODE_TO, $onlyOneOfLIA_CODE_TO='', $LIA_TYPE = '', $LIA_TEXT = null)
    {
        require_once CLASS_DIR . 'class.Link.php';

        $this->_onPreSaveLiaisonExterne();

        $LIA_TEMP = '';

        if ($onlyOneOfLIA_CODE_TO != '') {

            if ($LIA_CODE_TO == $this->_TABLE_NAME && empty($LIA_TYPE)) {
                echo "Vous devez modifier l'id de votre Div de chargement et le champ BD pour la contrainte d'intégrité, le nom est semblable à celui du module courant.";
                exit;
            }

            $colonne = str_replace('DE_', 'ID_', $onlyOneOfLIA_CODE_TO);

            if ($this->exist()) {
                $sql = "update " . $this->_TABLE_NAME . " set " . $colonne . (empty($LIA_TYPE) ? '' : '_' . $LIA_TYPE) . "=" . intval($ID_LIAISON_TO) . "  where " . $this->_ID_NAME . "=" . $this->dbh->quote($this->getID());
                $this->dbh->exec($sql);
            } else {
                $LIA_TEMP = time() . '@' . $colonne . (empty($LIA_TYPE) ? '' : '_' . $LIA_TYPE);
            }

            //suppression ancienne liaison éventuelle
            $sql = "select distinct " . $colonne . " from " . $onlyOneOfLIA_CODE_TO . "
                inner join LIAISON_EXTERNE on (ID_LIAISON_TO = " . $colonne . ")
                where
                    LIA_CODE_FROM = '" . $this->_TABLE_NAME . "'
                    and ID_LIAISON_FROM = " . $this->dbh->quote($this->_getID()) . "
                    and LIA_CODE_TO = " . $this->dbh->quote($onlyOneOfLIA_CODE_TO)."
                    and LIA_TYPE = " . $this->dbh->quote($LIA_TYPE);

            $aID_EXTERNE = $this->dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN);
            if (count($aID_EXTERNE) > 0) {
                Link::delete($this->_TABLE_NAME, $this->_getID(), " and LIA_CODE_TO=" . $this->dbh->quote($onlyOneOfLIA_CODE_TO) . " and ID_LIAISON_TO in (" . implode(',', array_map(array($this->dbh, 'quote'), $aID_EXTERNE)) . ") and LIA_TYPE = " . $this->dbh->quote($LIA_TYPE));
            }
            $sql = "update " . $this->_TABLE_NAME . "
                    set " . $colonne . (empty($LIA_TYPE) ? '' : '_' . $LIA_TYPE) . "=" . $this->dbh->quote($ID_LIAISON_TO) . "
                    where " . $this->_ID_NAME . "=" . $this->dbh->quote($this->_getID());
            $this->dbh->exec($sql);

        } elseif (!$this->exist()) {
            $LIA_TEMP = time();
        }
        Link::insertExterne($this->_TABLE_NAME, $this->_getID(), $LIA_CODE_TO, $ID_LIAISON_TO, $LIA_TYPE, $LIA_TEMP, $LIA_TEXT);

        $this->_onPostSaveLiaisonExterne();
    }

    /**
     * A surcharger le cas échéant :
     * Méthode exécutée après l'enregistrement de chaque liaison
     *
     * @return Ajax
     */
    protected function _onPostSaveLiaisonExterne()
    {
        // Purge du cache de l'ensemble du site
        require_once CLASS_DIR . 'class.db_page.php';
        Page::clearCache();

        return $this;
    }

    public function upLiaisonExterne($ID_LIAISON_EXTERNE)
    {
        require_once CLASS_DIR . 'class.Link.php';
        //mon ordre
        $sql = "select LIA_ORDRE, LIA_CODE_TO, LIA_TYPE from LIAISON_EXTERNE where ID_LIAISON_EXTERNE=" . intval($ID_LIAISON_EXTERNE);
        $row = $this->dbh->query($sql)->fetch(PDO::FETCH_ASSOC);

        // inverser l'ordre juste avant
        $sql = "select ID_LIAISON_EXTERNE from LIAISON_EXTERNE
            where LIA_CODE_FROM=" . $this->dbh->quote($this->_TABLE_NAME) . " and LIA_CODE_TO=" . $this->dbh->quote($row['LIA_CODE_TO']) . "
            and ID_LIAISON_FROM=" . $this->dbh->quote($this->_getID()) . " and LIA_ORDRE<" . intval($row['LIA_ORDRE']) . " and LIA_TYPE = " . $this->dbh->quote($row['LIA_TYPE']) . "
            order by LIA_ORDRE desc limit 0, 1";
        $sql = "update LIAISON_EXTERNE set LIA_ORDRE=" . intval($row['LIA_ORDRE']) . " where ID_LIAISON_EXTERNE=" . intval($this->dbh->query($sql)->fetchColumn());
        $this->dbh->exec($sql);

        // monter mon ordre
        $sql = "update LIAISON_EXTERNE set LIA_ORDRE=LIA_ORDRE-1 where ID_LIAISON_EXTERNE=" . intval($ID_LIAISON_EXTERNE);
        $this->dbh->exec($sql);

        // tout remettre dans l'ordre pour etre ok
        Link::reorderLiaisonExterne($this->_TABLE_NAME, $row['LIA_CODE_TO'], $this->_getID(), $row['LIA_TYPE']);

        // Purge du cache de l'ensemble du site
        require_once CLASS_DIR . 'class.db_page.php';
        Page::clearCache();
    }

    public function downLiaisonExterne($ID_LIAISON_EXTERNE)
    {
        require_once CLASS_DIR . 'class.Link.php';
        //mon ordre
        $sql = "select LIA_ORDRE, LIA_CODE_TO, LIA_TYPE  from LIAISON_EXTERNE where ID_LIAISON_EXTERNE=" . intval($ID_LIAISON_EXTERNE);
        $row = $this->dbh->query($sql)->fetch(PDO::FETCH_ASSOC);

        // inverser l'ordre juste après
        $sql = "select ID_LIAISON_EXTERNE from LIAISON_EXTERNE
            where LIA_CODE_FROM=" . $this->dbh->quote($this->_TABLE_NAME) . " and LIA_CODE_TO=" . $this->dbh->quote($row['LIA_CODE_TO']) . "
            and ID_LIAISON_FROM=" . $this->dbh->quote($this->_getID()) . " and LIA_ORDRE>" . intval($row['LIA_ORDRE']) . " and LIA_TYPE = " . $this->dbh->quote($row['LIA_TYPE']) . "
            order by LIA_ORDRE limit 0, 1";
        $sql = "update LIAISON_EXTERNE set LIA_ORDRE=" . intval($row['LIA_ORDRE']) . " where ID_LIAISON_EXTERNE=" . intval($this->dbh->query($sql)->fetchColumn());
        $this->dbh->exec($sql);

        // descendre mon ordre
        $sql = "update LIAISON_EXTERNE set LIA_ORDRE=LIA_ORDRE+1 where ID_LIAISON_EXTERNE=" . intval($ID_LIAISON_EXTERNE);
        $this->dbh->exec($sql);

        // tout remettre dans l'ordre pour etre ok
        Link::reorderLiaisonExterne($this->_TABLE_NAME, $row['LIA_CODE_TO'], $this->_getID(), $row['LIA_TYPE']);

        // Purge du cache de l'ensemble du site
        require_once CLASS_DIR . 'class.db_page.php';
        Page::clearCache();
    }

    /**
     * A surcharger le cas échéant :
     * Méthode exécutée après la suppression de chaque liaison
     *
     * @return Ajax
     */
    protected function _onPreDeleteLiaisonExterne()
    {
        return $this;
    }

    public function deleteLiaisonExterne($ID_LIAISON_TO, $LIA_CODE_TO, $onlyOne = false, $LIA_TYPE = '')
    {
        $this->_onPreDeleteLiaisonExterne();

        require_once CLASS_DIR . 'class.Link.php';
        if ($onlyOne) {
            $sql = "update ".$this->_TABLE_NAME." set " . str_replace('DE_', 'ID_', $LIA_CODE_TO) . (empty($LIA_TYPE)?'':'_'.$LIA_TYPE) . "=null";
            $sql .= " where " . $this->_ID_NAME . "=" . $this->dbh->quote($this->_getID());
            $this->dbh->exec($sql);
        }
        Link::delete($this->_TABLE_NAME, $this->_getID(), "and LIA_CODE_TO=" . $this->dbh->quote($LIA_CODE_TO) . " and ID_LIAISON_TO=" . $this->dbh->quote($ID_LIAISON_TO) . " and LIA_TYPE = " . $this->dbh->quote($LIA_TYPE));
        Link::reorderLiaisonExterne($this->_TABLE_NAME, $LIA_CODE_TO, $this->_getID(), $LIA_TYPE);

        $this->_onPostDeleteLiaisonExterne();
    }

    /**
     * A surcharger le cas échéant :
     * Méthode exécutée après la suppression de chaque liaison
     *
     * @return Ajax
     */
    protected function _onPostDeleteLiaisonExterne()
    {
        // Purge du cache de l'ensemble du site
        require_once CLASS_DIR . 'class.db_page.php';
        Page::clearCache();

        return $this;
    }

    public function getLiaisonExterne($LIA_CODE_TO, $LIA_TYPE = '')
    {
        require_once CLASS_DIR . 'class.Link.php';

        return Link::getLiaisonExterne($this->_TABLE_NAME, $LIA_CODE_TO, $this->_getID(), '*', $LIA_TYPE);
    }

    public function getLiaisonExterneReverse($LIA_CODE_FROM, $LIA_TYPE = '')
    {
        require_once CLASS_DIR . 'class.Link.php';
        return Link::getLiaisonExterneReverse($LIA_CODE_FROM, $this->_TABLE_NAME, $this->_getID(), '*', $LIA_TYPE);
    }

    public function getIdLiaisonExterne($LIA_CODE_TO, $LIA_TYPE = '')
    {
        require_once CLASS_DIR . 'class.Link.php';
        $IDENTIFIANT = str_replace('DE_', 'ID_', $LIA_CODE_TO);
        $aRow = Link::getLiaisonExterne($this->_TABLE_NAME, $LIA_CODE_TO, $this->_getID() , $IDENTIFIANT, $LIA_TYPE);
        $aID_LIAISON = array();
        foreach ($aRow as $row) {
            $aID_LIAISON[] = $row[$IDENTIFIANT];
        }

        return $aID_LIAISON;
    }

    public function getIdLiaisonExterneReverse($LIA_CODE_FROM, $LIA_TYPE = '')
    {
        require_once CLASS_DIR . 'class.Link.php';
        $IDENTIFIANT = str_replace('DE_', 'ID_', $LIA_CODE_FROM);
        $aRow = Link::getLiaisonExterneReverse($LIA_CODE_FROM, $this->_TABLE_NAME, $this->_getID() , $IDENTIFIANT, $LIA_TYPE);
        $aID_LIAISON = array();
        foreach ($aRow as $row) {
            $aID_LIAISON[] = $row[$IDENTIFIANT];
        }
        return $aID_LIAISON;
    }


    //////////////
    //Liaison Thematique
    //////////////
    /**
     * A surcharger le cas échéant :
     * Méthode exécutée avant l'enregistrement de chaque liaison
     *
     * @return Ajax
     */
    protected function _onPreSaveLiaisonThematique()
    {
        return $this;
    }

    public function saveLiaisonThematique($ID_THEMATIQUE)
    {
        require_once CLASS_DIR . 'class.Link.php';
        require_once CLASS_DIR . 'class.db_thematique.php';

        $oThematique = new Thematique($ID_THEMATIQUE);
        if ($oThematique->getField('SIT_CODE') == CMS::getCurrentSite()->getID() || array_key_exists($oThematique->getField('SIT_CODE'), CMS::getCurrentSite()->getRevertSharedSites())) {
            $this->_onPreSaveLiaisonThematique();

            Link::insertThematique($this->_TABLE_NAME, $this->getID(), $ID_THEMATIQUE);

            $this->_onPostSaveLiaisonThematique();

        } else {
            echo 'Ressource interdite : ' . $ID_THEMATIQUE;
        }
    }

    /**
     * A surcharger le cas échéant :
     * Méthode exécutée après l'enregistrement de chaque liaison
     *
     * @return Ajax
     */
    protected function _onPostSaveLiaisonThematique()
    {
        // Purge du cache de l'ensemble du site
        require_once CLASS_DIR . 'class.db_page.php';
        Page::clearCache();

        return $this;
    }

    /**
     * A surcharger le cas échéant :
     * Méthode exécutée après la suppression de chaque liaison
     *
     * @return Ajax
     */
    protected function _onPreDeleteLiaisonThematique()
    {
        return $this;
    }

    public function deleteLiaisonThematique($ID_THEMATIQUE)
    {
        $this->_onPreDeleteLiaisonThematique();

        require_once CLASS_DIR . 'class.Link.php';
        Link::delete($this->_TABLE_NAME, $this->getID(), ' and ID_THEMATIQUE=' . intval($ID_THEMATIQUE));

        $this->_onPostDeleteLiaisonThematique();
    }

    /**
     * A surcharger le cas échéant :
     * Méthode exécutée après la suppression de chaque liaison
     *
     * @return Ajax
     */
    protected function _onPostDeleteLiaisonThematique()
    {
        // Purge du cache de l'ensemble du site
        require_once CLASS_DIR . 'class.db_page.php';
        Page::clearCache();

        return $this;
    }

    public function getLiaisonThematique()
    {
        require_once CLASS_DIR . 'class.Link.php';

        return Link::getLiaisonThematique($this->_TABLE_NAME, $this->getID());
    }

    public function getIdLiaisonThematique()
    {
        require_once CLASS_DIR . 'class.Link.php';
        $aRow = Link::getLiaisonThematique($this->_TABLE_NAME, $this->getID());
        $aID_LIAISON = array();
        foreach ($aRow as $row) {
            $aID_LIAISON[] = $row['ID_THEMATIQUE'];
        }

        return $aID_LIAISON;
    }
}
