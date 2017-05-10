<?php
require_once CLASS_DIR . 'class.db_generic.php';

class StyleDynamique extends Generic
{

    public static function getModuleCode()
    {
        return 'MOD_CORE';
    }

    public function load()
    {
        $sql = "select * from STYLEDYNAMIQUE where ID_STYLEDYNAMIQUE=" . $this->getID();
        if ($row = $this->dbh->query($sql)->fetch(PDO :: FETCH_ASSOC)) {
            $this->setFields($row);
        } else {
            $this->_idtf = -1;
            $this->setFields(array ());
        }
    }

    public function isDeletable()
    {
        $sql = "select count(ID_PAGE) from OFF_PAGE where ID_STYLEDYNAMIQUE=" . $this->getID();

        return ($this->dbh->query($sql)->fetchColumn() == 0);
    }

    public function delete($idPageExclu = null)
    {
        if (!$this->isDeletable($idPageExclu)) {
            return false;
        }
        $sql = "update OFF_PAGE set ID_STYLEDYNAMIQUE = null where ID_STYLEDYNAMIQUE=" . $this->getID();
        $this->dbh->exec($sql);
        $sql = "update ON_PAGE set ID_STYLEDYNAMIQUE = null where ID_STYLEDYNAMIQUE=" . $this->getID();
        $this->dbh->exec($sql);
        $sql = "update REVISION_PAGE set ID_STYLEDYNAMIQUE = null where ID_STYLEDYNAMIQUE=" . $this->getID();
        $this->dbh->exec($sql);
        $sql = "delete from STYLEDYNAMIQUE where ID_STYLEDYNAMIQUE=" . $this->getID();
        $this->dbh->exec($sql);

        return true;
    }
}
