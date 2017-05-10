<?php
require '../../include/inc.bo_init.php';
CMS::checkAccess(new Module('MOD_CORE'), array('PRO_ROOT'));
require CLASS_DIR . 'class.db_ldap.php';

if (isset($_POST['Insert'])) {

    $stmt = $dbh->prepare("insert into LDAP (
        LDA_LIBELLE,
        LDA_HOST,
        LDA_PORT,
        LDA_LDAPS,
        LDA_ACCOUNT,
        LDA_PASSWORD,
        LDA_BASEDN,
        LDA_FILTER,
        LDA_ATTRLOGIN
        ) values (
        :LDA_LIBELLE,
        :LDA_HOST,
        :LDA_PORT,
        :LDA_LDAPS,
        :LDA_ACCOUNT,
        :LDA_PASSWORD,
        :LDA_BASEDN,
        :LDA_FILTER,
        :LDA_ATTRLOGIN)");
    $stmt->bindValue(':LDA_LIBELLE', $_POST['LDA_LIBELLE'], PDO::PARAM_STR);
    $stmt->bindValue(':LDA_HOST', $_POST['LDA_HOST'], PDO::PARAM_STR);
    $stmt->bindValue(':LDA_PORT', $_POST['LDA_PORT'], PDO::PARAM_INT);
    $stmt->bindValue(':LDA_LDAPS', !empty($_POST['LDA_LDAPS']) ? 1 : 0, PDO::PARAM_INT);
    $stmt->bindValue(':LDA_ACCOUNT', !empty($_POST['LDA_ACCOUNT']) ? $_POST['LDA_ACCOUNT'] : null, PDO::PARAM_STR);
    $stmt->bindValue(':LDA_PASSWORD', !empty($_POST['LDA_PASSWORD']) ? $_POST['LDA_PASSWORD'] : null, PDO::PARAM_STR);
    $stmt->bindValue(':LDA_BASEDN', !empty($_POST['LDA_BASEDN']) ? $_POST['LDA_BASEDN'] : null, PDO::PARAM_STR);
    $stmt->bindValue(':LDA_FILTER', !empty($_POST['LDA_FILTER']) ? $_POST['LDA_FILTER'] : null, PDO::PARAM_STR);
    $stmt->bindValue(':LDA_ATTRLOGIN', !empty($_POST['LDA_ATTRLOGIN']) ? $_POST['LDA_ATTRLOGIN'] : null, PDO::PARAM_STR);
    $stmt->execute();
    $idtf = $dbh->lastInsertID();
    $oLdap = new Ldap($idtf);

    header('Location:' . SERVER_ROOT . 'cms/administration/adm_ldap.php?idtf=' . $oLdap->getID());
    exit();

} elseif (isset($_POST['Update'])) {

    if ($_POST['LDA_PASSWORD'] == '********') {
        $oLdap = new Ldap($_POST['idtf']);
        $_POST['LDA_PASSWORD'] = $oLdap->getField('LDA_PASSWORD');
    }

    $stmt = $dbh->prepare("update LDAP set
        LDA_LIBELLE=:LDA_LIBELLE,
        LDA_HOST=:LDA_HOST,
        LDA_PORT=:LDA_PORT,
        LDA_LDAPS=:LDA_LDAPS,
        LDA_ACCOUNT=:LDA_ACCOUNT,
        LDA_PASSWORD=:LDA_PASSWORD,
        LDA_BASEDN=:LDA_BASEDN,
        LDA_FILTER=:LDA_FILTER,
        LDA_ATTRLOGIN=:LDA_ATTRLOGIN
        where ID_LDAP=:ID_LDAP");
    $stmt->bindValue(':LDA_LIBELLE', $_POST['LDA_LIBELLE'], PDO::PARAM_STR);
    $stmt->bindValue(':LDA_HOST', $_POST['LDA_HOST'], PDO::PARAM_STR);
    $stmt->bindValue(':LDA_PORT', $_POST['LDA_PORT'], PDO::PARAM_INT);
    $stmt->bindValue(':LDA_LDAPS', !empty($_POST['LDA_LDAPS']) ? 1 : 0, PDO::PARAM_INT);
    $stmt->bindValue(':LDA_ACCOUNT', !empty($_POST['LDA_ACCOUNT']) ? $_POST['LDA_ACCOUNT'] : null, PDO::PARAM_STR);
    $stmt->bindValue(':LDA_PASSWORD', !empty($_POST['LDA_PASSWORD']) ? $_POST['LDA_PASSWORD'] : null, PDO::PARAM_STR);
    $stmt->bindValue(':LDA_BASEDN', !empty($_POST['LDA_BASEDN']) ? $_POST['LDA_BASEDN'] : null, PDO::PARAM_STR);
    $stmt->bindValue(':LDA_FILTER', !empty($_POST['LDA_FILTER']) ? $_POST['LDA_FILTER'] : null, PDO::PARAM_STR);
    $stmt->bindValue(':LDA_ATTRLOGIN', !empty($_POST['LDA_ATTRLOGIN']) ? $_POST['LDA_ATTRLOGIN'] : null, PDO::PARAM_STR);
    $stmt->bindValue(':ID_LDAP', $_POST['idtf'], PDO::PARAM_INT);
    $stmt->execute();

    header('Location:' . SERVER_ROOT . 'cms/administration/adm_ldap.php?idtf=' . $_POST['idtf']);
    exit();

} elseif (!empty($_GET['Delete'])) {
    $oLdap = new Ldap($_GET['Delete']);
    if ($oLdap->delete()) {
        setMsg(gettext('DELETE_OK'));
    }
    header('Location:' . SERVER_ROOT . 'cms/administration/adm_ldapListe.php');
    exit();
}
