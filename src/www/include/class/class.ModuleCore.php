<?php
require_once CLASS_DIR . 'class.ModuleGeneric.php';

class ModuleCore extends ModuleGeneric
{
    /**
     * Méthode pour supprimer toutes les données d'un site
     * A redéfinir le cas échéant
     *
     * @see    Generic::deleteSite() de la version 5.5
     * @param  Site $oSite
     * @return bool
     */
    public function delete(Site $oSite)
    {
        if (!$this->isDeletable($oSite)) {
            return false;
        }

        // Suppression des elements suivants :
        $dbh = DB::getInstance();

        // Style dynamique
        require_once CLASS_DIR . 'class.db_styleDynamique.php';

        $sql = 'select *
                from STYLEDYNAMIQUE
                where SIT_CODE=' . $dbh->quote($oSite->getID());
        foreach ($dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $oDelete = new StyleDynamique($row['ID_STYLEDYNAMIQUE']);
            $oDelete->setFields($row);
            $oDelete->delete();
        }

        //@TODO : Page (la version 'on_' me semble inutile)
        $this->_deletePages($oSite->getID(), 'ON_');
        $this->_deletePages($oSite->getID(), 'OFF_');


        // Alerte
        require_once CLASS_DIR . 'class.db_alerte.php';
        $oDelete = new Alerte($oSite->getID());
        $oDelete->delete();

        // Utilisateur
        require_once CLASS_DIR . 'class.db_utilisateur.php';
        $sql = 'select *
                from UTILISATEUR
                where SIT_CODE = ' . $dbh->quote($oSite->getID());
        foreach ($dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $oDelete = new Utilisateur($row['ID_UTILISATEUR']);
            $oDelete->setFields($row);
            $oDelete->delete();
        }

        // Suppression des rôles restants (rôles des super admin ?!)
        $sql = 'delete from ROLE where SIT_CODE = ' . $dbh->quote($oSite->getID());
        $dbh->exec($sql);
    }




    /**
     * Méthode pour supprimer toutes les pages d'un site
     *
     * @param  string $idtf     Code du site
     * @param  string $mode     CMS Mode
     * @param  int    $parentId Id de la page parent
     * @return bool
     */
    private function _deletePages($idtf, $mode = 'OFF_', $parentId = null)
    {
        require_once CLASS_DIR . 'class.db_page.php';

        $dbh = DB::getInstance();

        $filter = ' SIT_CODE = '.$dbh->quote($idtf) . '
                    and PAG_IDPERE ';
        if (!is_numeric($parentId)) {
            $filter .= 'is null';
        } else {
            $filter .= '= ' . intval($parentId);
        }
        $sql = 'select ID_PAGE
                from ' . $mode . 'PAGE
                where ' . $filter;
        foreach ($dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN) as $pageId) {
            $this->_deletePages($idtf, $mode, $pageId);
            $oDelete = new Page($pageId, $mode);
            $oDelete->delete(false,true);
        }
        if (is_numeric($parentId)) {
            $oDelete = new Page($parentId, $mode);
            $oDelete->delete(false,true);
        }
        return true;
    }

    /**
     * Vérifie que les données de ce module ne sont pas utilisées par d'autres sites
     * A redéfinir le cas échéant
     *
     * @see    Generic::hasMultiSiteConstraints() de la version 5.5
     * @param  Site $oSite
     * @return bool
     */
    public function isDeletable(Site $oSite)
    {
        $dbh   = DB::getInstance();
        $idtf  = $oSite->getID();
        $aMode = array('OFF_', 'ON_');

        /**
         * Paragraphe
         */
        // Tous les paragraphes de ce site
        foreach ($aMode as $mode) {

            $sql = 'select ID_PARAGRAPHE
                    from ' . $mode . 'PARAGRAPHE
                    left join ' . $mode . 'PAGE on (' . $mode . 'PARAGRAPHE.ID_PAGE = ' . $mode . 'PAGE.ID_PAGE)
                    where ' . $mode . 'PAGE.SIT_CODE=' . $dbh->quote($idtf);

            $aId = $dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN);

            if (is_array($aId) && !empty($aId)) {
                $filter = ' and ID_LIAISON not in (' . implode(',', $aId) . ') ';
            } else {
                $filter = null;
            }

            // On compte le nombre de paragraphes des autres sites qui sont liés
            // à des pages du site
            $sql = 'select  count(*)
                    from LIAISON_PAGE
                    where LIA_CODE=\'' . $mode . 'PARAGRAPHE\'
                    and ID_PAGE in
                    (
                        select ID_PAGE
                        from ' . $mode . 'PAGE
                        where SIT_CODE=' . $dbh->quote($idtf) . '
                    )' .
                    $filter;

            if ($dbh->query($sql)->fetchColumn() > 0) {
                return false;
            }

            // Vérification des Liaisons avec des éléments webotheque
            $sql = 'select  count(*)
                    from LIAISON_WEBOTHEQUE
                    where LIA_CODE=\'' . $mode . 'PARAGRAPHE\'
                    and ID_WEBOTHEQUE  in
                    (
                        select ID_WEBOTHEQUE
                        from WEBOTHEQUE
                        where SIT_CODE=' . $dbh->quote($idtf) . '
                    )' .
                    $filter;
            if ($dbh->query($sql)->fetchColumn() > 0) {
                return false;
            }
        }

        /**
         * Paragraphe_Revision
         */
        // Toutes les révisions de paragraphes de ce site
        $sql = 'select distinct(ID_PARAGRAPHE)
                from REVISION_PARAGRAPHE
                left join REVISION_PAGE on
                (
                    REVISION_PARAGRAPHE.ID_PAGE = REVISION_PAGE.ID_PAGE
                    and
                    REVISION_PARAGRAPHE.ID_REVISION = REVISION_PAGE.ID_REVISION
                )
                where REVISION_PAGE.SIT_CODE=' . $dbh->quote($idtf);

        $aId = $dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN);

        if (is_array($aId) && !empty($aId)) {
            $filter = ' and ID_LIAISON not in (' . implode(',', $aId) . ')';
        } else {
            $filter = null;
        }

        // On compte le nombre de Révisions de Paragraphes des autres sites qui sont liés
        // à des pages du site
        $sql = 'select  count(*)
                from LIAISON_PAGE
                where LIA_CODE=\'REVISION_PARAGRAPHE\'
                and ID_PAGE in
                (
                    select ID_PAGE
                    from OFF_PAGE
                    where SIT_CODE=' . $dbh->quote($idtf) . '
                union
                    select ID_PAGE
                    from ON_PAGE
                    where SIT_CODE=' . $dbh->quote($idtf) . '
                )
                ' . $filter;

        if ($dbh->query($sql)->fetchColumn() > 0) {
            return false;
        }

        // On compte le nombre de Révisions de Paragraphes des autres sites qui sont liés
        // à des éléments wébothèques du site
        $sql = 'select  count(*)
                from LIAISON_WEBOTHEQUE
                where LIA_CODE=\'REVISION_PARAGRAPHE\'
                and ID_WEBOTHEQUE  in
                (
                    select ID_WEBOTHEQUE
                    from WEBOTHEQUE
                    where SIT_CODE=' . $dbh->quote($idtf) . '
                )
                ' . $filter;

        if ($dbh->query($sql)->fetchColumn() > 0) {
            return false;
        }

        /**
         * Page
         */
        // Toutes les pages de ce site
        foreach ($aMode as $mode) {

            $sql = 'select ID_PAGE
                    from ' . $mode . 'PAGE
                    where SIT_CODE=' . $dbh->quote($idtf);

            $aId = $dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN);

            if (is_array($aId) && !empty($aId)) {
                $filter = ' and ID_LIAISON not in (' . implode(',', $aId) . ')';
            } else {
                $filter = null;
            }
            // On compte le nombre de Page des autres sites qui sont liés
            // à des pages du site
            $sql = 'select  count(*)
                    from LIAISON_PAGE
                    where LIA_CODE=\'' . $mode . 'PAGE\'
                    and ID_PAGE in
                    (
                        select ID_PAGE
                        from ' . $mode . 'PAGE
                        where SIT_CODE=' . $dbh->quote($idtf) . '
                    )' . $filter;

            if ($dbh->query($sql)->fetchColumn() > 0) {
                return false;
            }

            // On compte le nombre de Page des autres sites qui sont liés
            // à des thematiques du site
            $sql = 'select count(*)
                    from LIAISON_THEMATIQUE
                    where LIA_CODE=\'' . $mode . 'PAGE\'
                    and ID_THEMATIQUE in
                    (
                        select ID_THEMATIQUE
                        from THEMATIQUE
                        where SIT_CODE=' . $dbh->quote($idtf) . '
                    )' . $filter;

            if ($dbh->query($sql)->fetchColumn() > 0) {
                return false;
            }

            // On compte le nombre de Page des autres sites qui sont liés
            // à des éléments wébothèques du site
            $sql = 'select  count(*)
                    from LIAISON_WEBOTHEQUE
                    where LIA_CODE=\'' . $mode . 'PAGE\'
                    and ID_WEBOTHEQUE  in
                    (
                        select ID_WEBOTHEQUE
                        from WEBOTHEQUE
                        where SIT_CODE=' . $dbh->quote($idtf) . '
                    )' . $filter;

            if ($dbh->query($sql)->fetchColumn() > 0) {
                return false;
            }
        }

        /**
         * Page_Revision
         */
        // Toutes les révisions de pages de ce site
        $sql = 'select distinct(ID_PAGE)
                from REVISION_PAGE
                where SIT_CODE=' . $dbh->quote($idtf);

        $aId = $dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN);

        if (is_array($aId) && !empty($aId)) {
            $filter = ' and ID_LIAISON not in (' . implode(',', $aId) . ')';
        } else {
            $filter = null;
        }

        // On compte le nombre de Révisions de Page des autres sites qui sont liés
        // à des pages du site
        $sql = 'select  count(*)
                from LIAISON_PAGE
                where LIA_CODE=\'REVISION_PAGE\'
                and ID_PAGE in
                (
                    select ID_PAGE
                    from OFF_PAGE
                    where SIT_CODE=' . $dbh->quote($idtf) . '
                union
                    select ID_PAGE
                    from ON_PAGE
                    where SIT_CODE=' . $dbh->quote($idtf) . '
                )
                ' . $filter;

        if ($dbh->query($sql)->fetchColumn() > 0) {
            return false;
        }

        // On compte le nombre de Révisions de Page des autres sites qui sont liés
        // à des éléments wébothèques du site
        $sql = 'select  count(*)
                from LIAISON_WEBOTHEQUE
                where LIA_CODE=\'REVISION_PAGE\'
                and ID_WEBOTHEQUE  in
                (
                    select ID_WEBOTHEQUE
                    from WEBOTHEQUE
                    where SIT_CODE=' . $dbh->quote($idtf) . '
                )
                ' . $filter;

        if ($dbh->query($sql)->fetchColumn() > 0) {
            return false;
        }

        return true;
    }
}
