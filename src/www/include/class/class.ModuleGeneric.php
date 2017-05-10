<?php
class ModuleGeneric
{
    protected $_oModule;

    public function __construct(Module $oModule)
    {
        $this->_oModule = $oModule;
    }

    /**
     * Actions par défaut, communes à tout les modules lors de sa désactivation
     * A surcharger le cas échéant
     *
     * @param  Site $oSite
     * @return bool
     */
    public function disable(Site $oSite)
    {
        $dbh = DB::getInstance();
        /*
        $sql = 'select count(ID_PARAGRAPHE)
                from OFF_PARAGRAPHE
                inner join OFF_PAGE using(ID_PAGE)
                where TPL_CODE in (
                    select TPL_CODE
                    from DD_TEMPLATE
                    where MOD_CODE=' . $dbh->quote($this->_oModule->getID()) . "
                )
                and SIT_CODE=" . $dbh->quote($oSite->getID());

        $nb = $dbh->query($sql)->fetchColumn();
        if ($nb == 0) {
            $sql = 'select count(ID_PARAGRAPHE)
                    from ON_PARAGRAPHE
                    inner join ON_PAGE using(ID_PAGE)
                    where TPL_CODE in (
                        select TPL_CODE
                        from DD_TEMPLATE
                        where MOD_CODE=' . $dbh->quote($this->_oModule->getID()) . '
                    )
                    and SIT_CODE=' . $dbh->quote($oSite->getID());
            $nb = $dbh->query($sql)->fetchColumn();
        }
        //*/
        $nb = 0;
        if ($nb == 0) {
            //ce module n'est pas utilisé : on peut supprimer la liaison
            $sql = 'delete from SITE_MODULE
                    where SIT_CODE=' . $dbh->quote($oSite->getID()) . '
                    and MOD_CODE=' . $dbh->quote($this->_oModule->getID());
            $dbh->exec($sql);

            //on supprime les traductions
            $sql = 'delete from TRADUCTION_SITE where SIT_CODE=' . $dbh->quote($oSite->getID()) . '
                    and TRA_CODE in (
                        select TRA_CODE from DD_TRADUCTION where MOD_CODE=' . $dbh->quote($this->_oModule->getID()) . ')' ;
            $dbh->exec($sql);

            return true;
        }

        return false;
    }

    /**
     * Actions par défaut, communes à tout les modules lors de son activation
     * A surcharger le cas échéant
     *
     * @param  Site $oSite
     * @return bool
     */
    public function enable(Site $oSite)
    {
        $dbh  = DB::getInstance();
        $stmt = $dbh->prepare('insert into SITE_MODULE (SIT_CODE, MOD_CODE) values (:SIT_CODE, :MOD_CODE)');
        $stmt->bindParam(':SIT_CODE', $oSite->getID(), PDO::PARAM_STR);
        $stmt->bindValue(':MOD_CODE', $this->_oModule->getID(), PDO::PARAM_STR);
        $stmt->execute();

        // Module traduction activé :
        if ($oSite->hasModule(new Module('MOD_TRADUCTION'))) {
            // On charge les traductions du module
            $sql = 'insert into TRADUCTION_SITE
                    (
                        TRA_CODE,
                        SIT_CODE,
                        TRA_LIBELLE
                    )
                    select TRA_CODE, ' . $dbh->quote($oSite->getID()) .', TRA_LIBELLE
                    from TRADUCTION_LANGUE
                    where LNG_CODE =  ' . $dbh->quote($oSite->getField('LNG_CODE')) . '
                    and TRA_CODE in
                    (
                        select TRA_CODE
                        from DD_TRADUCTION
                        where MOD_CODE=' . $dbh->quote($this->_oModule->getID()) .'
                    )';
            $dbh->exec($sql);
        }

        return true;
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
        return false;
    }
}
