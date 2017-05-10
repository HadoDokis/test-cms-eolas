<?php
require_once CLASS_DIR . 'class.db_ajax.php';

class CommentaireParametrage extends Ajax
{
    public function __construct($idtf = -1)
    {
        parent::__construct('COMMENTAIRE_PARAM', 'ID_COMMENTAIRE_PARAM', $idtf);
    }

    /**
     * @return array la liste des champs (redéfinition pour retourner les valeur par défaut lorsque l'element n'existe pas pour un site donné
     */
    public function getFields()
    {
        return $this->_fields;
    }

    public static function getModuleCode()
    {
        return 'MOD_COMMENTAIRE';
    }

    public function load()
    {
        $sql = "select * from COMMENTAIRE_PARAM where ID_COMMENTAIRE_PARAM=" . $this->getID();
        if ($row = $this->dbh->query($sql)->fetch(PDO::FETCH_ASSOC)) {
            $this->setFields($row);
        } else {
            $this->_idtf = -1;
            $defaultoptions = array(
                'CPA_TYPEMODERATION' => '1',
                'CPA_AFFICHAGE_DEFAUT' => '1',
                'CPA_MES_DEPOT' => gettext('msgDefautDepot'),
                'CPA_MES_REMERCIEMENT' => gettext('msgRemerciementCommentaire'),
                'CPA_LIBELLELIEN' => gettext('msgLibLien'),
                'CPA_EMAILNOTIFICATION' => 0,
                'CPA_SIGNATUREMAIL' => gettext('msgSignature'),
                'CPA_COMMENTAIREVALIDE' => gettext('msgCommentaireValide'),
                'CPA_COMMENTAIREREFUS' => gettext('msgCommentaireRefuse')
            );
            $this->setFields($defaultoptions);
        }
    }

    public function isDeletable()
    {
        return false;
    }

    public function delete($forceDelete = false)
    {

        if (!$this->isDeletable() && !$forceDelete) {
            return false;
        }

        require_once CLASS_DIR . 'class.Link.php';
        Link::delete('COMMENTAIRE_PARAM', $this->getID(), 'ALL');

        $sql = "delete from COMMENTAIRE_PARAM where ID_COMMENTAIRE_PARAM=" . $this->getID();
        $this->dbh->exec($sql);

        return true;
    }

    public static function getParametrageForSite($SIT_CODE = null)
    {
        if (is_null($SIT_CODE)) {$SIT_CODE = CMS::getCurrentSite()->getID();}
        $ret = false;
        $dbh = DB::getInstance();
        $sql = "select ID_COMMENTAIRE_PARAM from COMMENTAIRE_PARAM where SIT_CODE = ".$dbh->quote($SIT_CODE);
        $idtf = $dbh->query($sql)->fetch(PDO::FETCH_COLUMN);
        $oParams = new self($idtf?$idtf:-1);
        $oParams->load();
        return $oParams;
    }

}
