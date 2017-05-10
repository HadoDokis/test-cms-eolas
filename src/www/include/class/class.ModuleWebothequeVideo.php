<?php
require_once CLASS_DIR . 'class.ModuleWebotheque.php';
require_once CLASS_DIR . 'class.db_webothequeCategorie.php';

class ModuleWebothequeVideo extends ModuleWebotheque
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
        require_once CLASS_DIR . 'class.db_webotheque.php';

        $dbh = DB::getInstance();
        $sql = 'select * from WEBOTHEQUE
                where WBT_CODE = \'WBT_VIDEO\'
                and SIT_CODE = ' . $dbh->quote($oSite->getID());
        foreach ($dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $oDelete = new Webo_VIDEO ($row['ID_WEBOTHEQUE']);
            $oDelete->setFields($row);
            $oDelete->delete(true);
        }

        // Suppression des catégories
        require_once CLASS_DIR . 'class.db_webothequeCategorie.php';
        WebothequeCategorie::clearCategorie('WBT_VIDEO', $oSite->getField('SIT_CODE'));

        return true;
    }
}
