<?php
require dirname(__FILE__) . '/../inc.bo_init.php';
header("Cache-Control: no-cache");
header("Content-type: text/plain; charset=utf-8");
if (isset($_GET['CHECK'], $_GET['VALUE']) && !empty($_GET['VALUE']) && in_array($_GET['CHECK'], array('UTI_EMAIL','UTI_LOGIN'))) {

    $sql = "select count(ID_UTILISATEUR) from UTILISATEUR where ". $_GET['CHECK'] ."=" . $dbh->quote($_GET['VALUE']);
    if (isset($_GET['idtf']) && is_numeric($_GET['idtf'])) {
        $sql .= " and ID_UTILISATEUR<>" . intval($_GET['idtf']);
    }
    if ($dbh->query($sql)->fetchColumn() > 0) {
        $return = 'Cet ' . ($_GET['CHECK'] == 'UTI_EMAIL' ? 'email' : 'identifiant') . ' est deja attribue a un autre utilisateur';
        echo gettext($return);
    }
}
