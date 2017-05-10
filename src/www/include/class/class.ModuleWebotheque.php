<?php
require_once CLASS_DIR . 'class.ModuleGeneric.php';

abstract class ModuleWebotheque extends ModuleGeneric
{

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
        $dbh = DB::getInstance();

        // Tous les éléments webotheque de ce site
        $sql = 'select ID_WEBOTHEQUE
                from WEBOTHEQUE
                where SIT_CODE=' . $dbh->quote($oSite->getID());

        $aId = $dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN);

        if (is_array($aId) && !empty($aId)) {
            $filter = ' and ID_LIAISON not in (' . implode(',', $aId) . ') ';
        } else {
            $filter = null;
        }


        // On compte le nombre d'objet wébotheque des autres sites qui sont liés
        // à des pages du site
        $sql = 'select  count(*)
                from LIAISON_PAGE
                where LIA_CODE=\'WEBOTHEQUE\'
                and ID_PAGE in
                (
                    select ID_PAGE
                    from OFF_PAGE
                    where SIT_CODE=' . $dbh->quote($oSite->getID()) . '
                union
                    select ID_PAGE
                    from ON_PAGE
                    where SIT_CODE=' . $dbh->quote($oSite->getID()) . '
                ) ' . $filter;

        if ($dbh->query($sql)->fetchColumn() > 0) {
            return false;
        }

        // On compte le nombre d'objet wébotheque des autres sites qui sont liés
        // à des éléments webotheques du site
        $sql = 'select  count(*)
                from LIAISON_WEBOTHEQUE
                where LIA_CODE=\'WEBOTHEQUE\'
                and ID_WEBOTHEQUE  in
                (
                    select ID_WEBOTHEQUE
                    from WEBOTHEQUE
                    where SIT_CODE=' . $dbh->quote($oSite->getID()) . '
                ) ' . $filter;

        if ($dbh->query($sql)->fetchColumn() > 0) {
            return false;
        }

        return true;
    }
}
