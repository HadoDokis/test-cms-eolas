<?php
require_once CLASS_DIR . 'class.db_generic.php';

class Alerte extends Generic
{
    public function __construct($idtf)
    {
        parent::__construct($idtf, false);
    }

    public static function getModuleCode()
    {
        return 'MOD_CORE';
    }

    public function load()
    {
        // idtf == '' signifie que c'est pour tous
        if ($this->getID() == '') {
            $sql = "select * from ALERTE where SIT_CODE is null";
        } else {
            $sql = "select * from ALERTE where SIT_CODE=" . $this->dbh->quote($this->getID());
        }
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
        // idtf == '' signifie que c'est pour tous
        if ($this->getID() == '') {
            $sql = "delete from ALERTE where SIT_CODE is null";
        } else {
            $sql = "delete from ALERTE where SIT_CODE=" . $this->dbh->quote($this->getID());
        }
        $this->dbh->exec($sql);
    }

    public function isDeletable()
    {
        return true;
    }

    public function isLocked()
    {
        return ($this->getField('ALT_DATE') != '') && ($this->getField('ALT_DATE') < time());
    }

    public function isGenerale()
    {
        return $this->getID() == '';
    }

    public function getCurrent($SIT_CODE = '')
    {
        $dbh = DB::getInstance();
        $sql = "select * from ALERTE where SIT_CODE is null";
        if ($row = $dbh->query($sql)->fetch(PDO::FETCH_ASSOC)) {
            $oAlerte = new Alerte('');
            $oAlerte->setFields($row);
            //on teste s'il n'existe pas une alerte de site qui serait bloquante et donc prioritaire
            if (!$oAlerte->isLocked() && $SIT_CODE != '') {
                $sql = "select * from ALERTE where SIT_CODE=" . $dbh->quote($SIT_CODE);
                if ($row = $dbh->query($sql)->fetch(PDO::FETCH_ASSOC)) {
                    $oAlerteSite = new Alerte($SIT_CODE);
                    $oAlerteSite->setFields($row);
                    if ($oAlerteSite->isLocked()) {
                        return $oAlerteSite;
                    }
                }
            }

            return $oAlerte;
        }
        if ($SIT_CODE == '') {
            return false;
        }
        $sql = "select * from ALERTE where SIT_CODE=" . $dbh->quote($SIT_CODE);
        if ($row = $dbh->query($sql)->fetch(PDO::FETCH_ASSOC)) {
            $oAlerteSite = new Alerte($SIT_CODE);
            $oAlerteSite->setFields($row);

            return $oAlerteSite;
        }

        return false;
    }
}
