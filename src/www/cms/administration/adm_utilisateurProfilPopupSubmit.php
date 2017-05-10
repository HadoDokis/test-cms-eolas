<?php
require '../../include/inc.bo_init.php';
CMS::checkAccess(new Module('MOD_CORE'), array('PRO_ROOT_SITE'));
// Redirection vers la fiche utilisateur classique ou vers la version partagée ?
$share = !empty($_REQUEST['editShare'])?'Share':'';

if (isset($_POST['Insert'])) {
    $oUtilisateur = new Utilisateur($_POST['idtf']);
    if (!$oUtilisateur->checkAuthorized(false)) {
        //peut-etre un utilisateur partagé ?
        $oUtilisateur->checkShareAuthorized();
    }

    $stmt = $dbh->prepare("insert into ROLE (
        SIT_CODE,
        ID_UTILISATEUR,
        PRO_CODE,
        ID_PAGE
        ) values (
        :SIT_CODE,
        :ID_UTILISATEUR,
        :PRO_CODE,
        :ID_PAGE
        )");
    $stmt->bindValue(':SIT_CODE', CMS::getCurrentSite()->getID(), PDO::PARAM_STR);
    $stmt->bindValue(':ID_UTILISATEUR', $_POST['idtf'], PDO::PARAM_INT);
    $stmt->bindValue(':PRO_CODE', $_POST['PRO_CODE'], PDO::PARAM_STR);
    $stmt->bindValue(':ID_PAGE', $_POST['ID_PAGE'], PDO::PARAM_INT);
    $stmt->execute();

    ?>
<script>
window.opener.location.href = 'adm_utilisateur<?php echo $share?>.php?idtf=<?php echo $_POST['idtf'] ?>';
window.close();
</script>
    <?php
    exit();
} elseif (is_numeric($_GET['Delete'])) {
    $oUtilisateur = new Utilisateur($_GET['idtf']);
    if (!$oUtilisateur->checkAuthorized(false)) {
        //peut-etre un utilisateur partagé ?
        $oUtilisateur->checkShareAuthorized();
    }
    $sql = "delete from ROLE where ID_ROLE=" . intval($_GET['Delete']);
    $dbh->exec($sql);

    header('Location:' . SERVER_ROOT . 'cms/administration/adm_utilisateur' . $share . '.php?idtf=' . $_GET['idtf']);
    exit ();
}
