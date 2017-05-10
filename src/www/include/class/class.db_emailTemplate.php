<?php
require_once CLASS_DIR . 'class.db_generic.php';

class EmailTemplate extends Generic
{

    public function __construct($idtf)
    {
        parent::__construct($idtf,false);
    }

    public static function getModuleCode()
    {
        return 'MOD_CORE';
    }

    public function load()
    {
        $sql = "select * from DD_EMAILTEMPLATE where EMT_CODE=" . $this->dbh->quote($this->getID());
        if ($row = $this->dbh->query($sql)->fetch(PDO::FETCH_ASSOC)) {
            $this->setFields($row);
        }
        else {
            $this->_idtf = -1;
            $this->setFields(array ());
        }
    }

    public function delete()
    {
        return false;
    }

    public function isDeletable()
    {
        return false;
    }

    public function getKeys()
    {
        $sql = "select * from DD_EMAILTEMPLATEKEY where EMT_CODE=" . $this->dbh->quote($this->getID()) . "order by EMK_LIBELLE";
        return $this->dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     *
     * @return Module
     */
    public function getModule()
    {
        require_once CLASS_DIR . 'class.db_module.php';
        return new Module($this->getField('MOD_CODE'));
    }
}
