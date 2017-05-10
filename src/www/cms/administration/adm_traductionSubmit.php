<?php
require '../../include/inc.bo_init.php';
require_once CLASS_DIR . 'class.db_page.php';
CMS::checkAccess(new Module('MOD_CORE'), array('PRO_ROOT'));
//* On récupère le module demandé en vérifiant que le module est activé et qu'il y a des traductions associées
$availableModules = array_map(array($dbh, 'quote'), array_keys(CMS::getCurrentSite()->getModules()));
$sql = "select * from DD_TRADUCTION
    inner join DD_MODULE using (MOD_CODE)
    where MOD_CODE in (".implode(',', $availableModules).")
        and MOD_CODE=" . $dbh->quote($_POST['MOD_CODE']) . " order by TRA_DESCRIPTION, TRA_CODE";
$aTraduction = $dbh->query($sql)->fetchAll(PDO :: FETCH_ASSOC);
if (count($aTraduction) == 0) {
    header('location:' . SERVER_ROOT . 'cms/administration/adm_traductionListe.php');
    exit();
}
//*/

if (isset($_POST['Update'])) {
    //On supprime toutes les traductions du site pour le module en cours
    $sql = "delete from TRADUCTION_LANGUE
        where TRA_CODE in (select TRA_CODE from DD_TRADUCTION where MOD_CODE=". $dbh->quote($_POST['MOD_CODE']) . ")
        and LNG_CODE=" . $dbh->quote($_POST['LNG_CODE']);
    $dbh->exec($sql);

    //On prepare la requete d'insertion
    $stmt = $dbh->prepare("insert into TRADUCTION_LANGUE (
        TRA_CODE,
        LNG_CODE,
        TRA_LIBELLE
        ) values (
        :TRA_CODE,
        :LNG_CODE,
        :TRA_LIBELLE
        )");
    $stmt->bindValue(':LNG_CODE', $_POST['LNG_CODE'], PDO :: PARAM_STR);

    //On insère toutes les nouvelles traductions du site pour le module en cours
    foreach ($_POST as $k=>$v) {
        if (!in_array($k, array('Update', 'MOD_CODE', 'LNG_CODE')) && trim($v) != '') {
            $stmt->bindValue(':TRA_CODE', $k, PDO :: PARAM_STR);
            $stmt->bindValue(':TRA_LIBELLE', $v, PDO :: PARAM_STR);
            $stmt->execute();
        }
    }

    //On invalide le cache
    unset($_SESSION['_tabI18N'][$_POST['MOD_CODE']]);

    setMSG(gettext('UPDATE_OK'));

    // Purge du cache de l'ensemble des sites
    Page::clearAllCache();

    header('location:' . SERVER_ROOT . 'cms/administration/adm_traduction.php?idtf='. $_POST['MOD_CODE'] . '&LNG_CODE=' . $_POST['LNG_CODE']);
    exit();
}
