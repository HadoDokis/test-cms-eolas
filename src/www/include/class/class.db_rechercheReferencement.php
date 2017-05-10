<?php
require_once CLASS_DIR . 'class.db_generic.php';

class RechercheReferencement extends Generic
{

    public static function getModuleCode()
    {
        return 'MOD_REFERENCEMENT';
    }

    public function load()
    {
        $sql = "select * from RECHERCHEREFERENCEMENT where ID_RECHERCHEREFERENCEMENT=" . $this->getID();
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

        require_once CLASS_DIR . 'class.Link.php';
        Link::delete('RECHERCHEREFERENCEMENT', $this->getID(), 'ALL');

        $sql = "delete from RECHERCHEREFERENCEMENT where ID_RECHERCHEREFERENCEMENT=" . $this->getID();
        $this->dbh->exec($sql);

        return true;
    }

    public function isDeletable()
    {
        return true;
    }

    public function getLibelle()
    {
        return $this->getField('REC_TITLE');
    }

    public function getResume()
    {
        return $this->getField('REC_RESUME');
    }

    public function getDescription($oPage = null)
    {
        require_once CLASS_DIR . 'class.Editor.php';
        if (is_null($oPage)) {
            $oPage = CMS::getCurrentSite()->getCurrentPage();
        }
        return Editor::displayContent($this->getField('REC_DESCRIPTION'), $oPage);
    }
}
