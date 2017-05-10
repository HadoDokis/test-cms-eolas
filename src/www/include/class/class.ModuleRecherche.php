<?php
require_once CLASS_DIR . 'class.ModuleGeneric.php';

class ModuleRecherche extends ModuleGeneric
{

    /**
     * Traitement effectué lors de la désactivation du module Tradutction
     *
     * @see trunk/src/www/include/class/ModuleGeneric::disable()
     *
     * @param  Site $oSite
     * @return bool
     */
    public function disable(Site $oSite)
    {
        if (parent::disable($oSite)) {
            $dbh = DB::getInstance();
            $dbh->exec('delete from STOPWORD_SITE where SIT_CODE = ' . $dbh->quote($oSite->getID()));

            return true;
        }

        return false;
    }

    /**
     * Traitement effectué lors de l'activation du module Tradutction
     *
     * @see trunk/src/www/include/class/ModuleGeneric::enable()
     *
     * @param  Site $oSite
     * @return bool
     */
    public function enable(Site $oSite)
    {
        if (parent::enable($oSite)) {
            $dbh = DB::getInstance();
            $sql = 'insert into STOPWORD_SITE (STP_LIBELLE, SIT_CODE)
                        select STP_LIBELLE, ' . $dbh->quote($oSite->getID()) .'
                            from STOPWORD_LANGUE where LNG_CODE =  ' . $dbh->quote($oSite->getField('LNG_CODE'));
            $dbh->exec($sql);

            return true;
        }

        return false;
    }


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
        $dbh = DB::getInstance();
        $dbh->exec('delete from STOPWORD_SITE where SIT_CODE = ' . $dbh->quote($oSite->getID()));

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
