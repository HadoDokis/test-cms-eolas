<?php
require_once (CLASS_DIR . 'class.db_generic.php');

class Abreviation extends Generic
{

    public function __construct($idtf)
    {
        parent :: __construct($idtf);
    }

    public static function getModuleCode()
    {
        return 'MOD_ABREVIATION';
    }

    public function load()
    {
        $sql = "select * from ABREVIATION where ID_ABREVIATION=" . $this->getID();
        if ($row = $this->dbh->query($sql)->fetch(PDO :: FETCH_ASSOC)) {
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
        $sql = "delete from ABREVIATION where ID_ABREVIATION=" . $this->getID();
        $this->dbh->exec($sql);

        return true;
    }

    public function isDeletable()
    {
        return true;
    }

    /**
     * Retourne la liste des tags existants ou le libellé d'un tag
     * @param $key le nom du tag
     */
    public static function getTagnameArray($key = '')
    {
        $tab = array (
            'abbr' => gettext('Abreviation'),
            'acronym' => gettext('Acronyme'));
        if ($key == '') {
            return $tab;
        }
        $key = mb_strtolower($key);
        if (isset ($tab[$key])) {
            return $tab[$key];
        }

        return '!!' . $key . '!!';
    }
}
