<?php
require_once CLASS_DIR . 'class.ModuleGeneric.php';

class ModuleAccessibilite extends ModuleGeneric
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
        return true;
    }
}
