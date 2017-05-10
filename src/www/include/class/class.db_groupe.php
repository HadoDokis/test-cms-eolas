<?php
require_once (CLASS_DIR . 'class.db_generic.php');

class Groupe extends Generic
{
    public static function getModuleCode()
    {
        return 'MOD_EXTRANET';
    }

    public function load()
    {
        $sql = "select * from GROUPE where ID_GROUPE=" . $this->getID();
        if ($row = $this->dbh->query($sql)->fetch(PDO::FETCH_ASSOC)) {
            $this->setFields($row);
        } else {
            $this->_idtf = - 1;
            $this->setFields(array());
        }
    }

    public function delete()
    {
        if (! $this->isDeletable()) {
            return false;
        }
        $this->dbh->exec("delete from REVISION_GROUPE where ID_GROUPE=" . $this->getID());
        $this->dbh->exec("delete from GROUPE_UTILISATEUR where ID_GROUPE=" . $this->getID());
        $this->dbh->exec("delete from GROUPE_OFF_PAGE where ID_GROUPE=" . $this->getID());
        $this->dbh->exec("delete from GROUPE_ON_PAGE where ID_GROUPE=" . $this->getID());
        $this->dbh->exec("delete from GROUPE_SITE where ID_GROUPE=" . $this->getID());
        $this->dbh->exec("delete from GROUPE where ID_GROUPE=" . $this->getID());

        return true;
    }

    public function isDeletable()
    {
        return true;
    }

    /*
     * Partage le groupe avec d'autres sites
     * Ce partage "en ouverture" permet aux sites distants d'y insérer leurs utilisateurs
     */
    public function share($aSIT_CODE = null)
    {
        if (is_null($aSIT_CODE)) {
            $aSIT_CODE = array();
        }

        // on met les codes en clés
        $aSIT_CODE = array_flip($aSIT_CODE);

        // la liste des sites partageables
        $aSIT_CODE_CMS = CMS::getCurrentSite()->getSharedSites();

        // on va chercher tous les anciens sites "ouverts"
        $sql = "select SIT_CODE from GROUPE_SITE where ID_GROUPE=" . $this->getID();
        foreach ($this->dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN) as $SIT_CODE) {
            if (isset($aSIT_CODE_CMS[$SIT_CODE]) && isset($aSIT_CODE[$SIT_CODE])) {
                // ce site existe tjs, on le supprime du parametre car inutile de le réinsérer
                unset($aSIT_CODE[$SIT_CODE]);
            } else {
                // on supprime l'ouverture
                $sql = "delete from GROUPE_SITE where SIT_CODE=" . $this->dbh->quote($SIT_CODE) . " and ID_GROUPE=" . $this->getID();
                $this->dbh->exec($sql);

                // on supprime les utilisateurs externes qui auraient pu être dans ce groupe
                $sql = "delete GROUPE_UTILISATEUR from GROUPE_UTILISATEUR
                    inner join UTILISATEUR on GROUPE_UTILISATEUR.ID_UTILISATEUR=UTILISATEUR.ID_UTILISATEUR
                    where UTILISATEUR.SIT_CODE=" . $this->dbh->quote($SIT_CODE) . " and ID_GROUPE=" . $this->getID();
                $this->dbh->exec($sql);
            }
        }

        // on insère les nouveaux
        foreach ($aSIT_CODE as $SIT_CODE => $null) {
            // on insère le partage
            $sql = "insert into GROUPE_SITE (
                SIT_CODE,
                ID_GROUPE
                ) values (" .
                $this->dbh->quote($SIT_CODE) . "," .
                $this->getID() . "
                )";
            $this->dbh->exec($sql);
        }
        return true;
    }
}
