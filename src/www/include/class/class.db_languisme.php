<?php
require_once CLASS_DIR . 'class.db_generic.php';

class Languisme extends Generic
{

    public static function getModuleCode()
    {
        return 'MOD_LANGUISME';
    }

    public function load()
    {
        $sql = "select * from LANGUISME where ID_LANGUISME=" . $this->getID();
        if ($row = $this->dbh->query($sql)->fetch(PDO::FETCH_ASSOC)) {
            $this->setFields($row);
        } else {
            $this->_idtf = -1;
            $this->setFields(array ());
        }
    }

    public function delete()
    {
        if (!$this->isDeletable()) {
            return false;
        }
        $sql = "delete from LANGUISME where ID_LANGUISME=" . $this->getID();
        $this->dbh->exec($sql);

        return true;
    }

    public function isDeletable()
    {
        return true;
    }
}
