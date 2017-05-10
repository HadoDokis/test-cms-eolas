<?php
require_once CLASS_DIR . 'class.db_generic.php';

class Ldap extends Generic
{

    public static function getModuleCode()
    {
        return 'MOD_CORE';
    }

    public function load()
    {
        $sql = "select * from LDAP where ID_LDAP=" . $this->getID();
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
        $lockinfo = serialize(array('datetime' => time()));
        // Lors de la suppression, on verrouille l'ensemble des utilisateurs associé
        $stmt = $this->dbh->prepare("update UTILISATEUR set ID_LDAP=:ID_LDAP,
                UTI_STATUT_LOCKED=:UTI_STATUT_LOCKED,
                UTI_STATUT_BLOCKED=:UTI_STATUT_BLOCKED,
                UTI_AUTH_INFO=:UTI_AUTH_INFO
            where ID_LDAP=:idtf");
        $stmt->bindValue(':ID_LDAP', null, PDO::PARAM_INT);
        $stmt->bindValue(':UTI_STATUT_LOCKED', 1, PDO::PARAM_INT);
        $stmt->bindValue(':UTI_STATUT_BLOCKED', 0, PDO::PARAM_INT); // L'action sur le verrou supprime les informations liées au blocage
        $stmt->bindValue(':UTI_AUTH_INFO', $lockinfo, PDO::PARAM_STMT);
        $stmt->bindValue(':idtf',$this->getID(), PDO::PARAM_INT);
        $stmt->execute();

        $sql = "delete from LDAP where ID_LDAP=" . $this->getID();
        $this->dbh->exec($sql);

        return true;
    }

    public function isDeletable()
    {
        return true;
    }
}
/**
 * Escapes an LDAP AttributeValue
 */
if (!function_exists('ldap_escape'))
{
    function ldap_escape($string)
    {
        return str_replace(array('*', '\\', '(', ')'), array('\\*', '\\\\', '\\(', '\\)'), $string);
    }
}
