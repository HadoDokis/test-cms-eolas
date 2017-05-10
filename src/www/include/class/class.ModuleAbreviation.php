<?php
require_once CLASS_DIR . 'class.ModuleGeneric.php';

class ModuleAbreviation extends ModuleGeneric
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

        require_once CLASS_DIR . 'class.db_abreviation.php';

        $dbh = DB::getInstance();
        $sql = 'select *
                from ABREVIATION
                where SIT_CODE=' . $dbh->quote($oSite->getID());
        foreach ($dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $oDelete = new Abreviation($row['ID_ABREVIATION']);
            $oDelete->setFields($row);
            $oDelete->delete();
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
        return true;
    }
}
