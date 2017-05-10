<?php
require '../include/inc.bo_init.php';
CMS::checkAccess(new Module('MOD_CORE'), array('PRO_ROOT_SITE'));
require CLASS_DIR . 'class.db_styleDynamique.php';
require_once CLASS_DIR . 'class.db_page.php';

if (isset($_POST['Insert'])) {
    $stmt = $dbh->prepare("insert into STYLEDYNAMIQUE (
        SIT_CODE,
        STY_LIBELLE,
        STY_CSS
        ) values (
        :SIT_CODE,
        :STY_LIBELLE,
        :STY_CSS)");
    $stmt->bindValue(':SIT_CODE', CMS::getCurrentSite()->getID(), PDO::PARAM_STR);
    $stmt->bindValue(':STY_LIBELLE', $_POST['STY_LIBELLE'], PDO::PARAM_STR);
    $stmt->bindValue(':STY_CSS', $_POST['STY_CSS'], PDO::PARAM_STR);
    $stmt->execute();
    $idtf = $dbh->lastInsertId();
?>
<script>
    window.opener.addStylePerso('<?php echo $idtf ?>', '<?php echo escapeJS($_POST['STY_LIBELLE'])?>');
    window.close();
</script>
<?php
    exit();
} elseif (isset($_POST['Update'])) {
    $stmt = $dbh->prepare("update STYLEDYNAMIQUE set
        STY_LIBELLE=:STY_LIBELLE,
        STY_CSS=:STY_CSS
        where ID_STYLEDYNAMIQUE=:idtf and SIT_CODE=:SIT_CODE");
    $stmt->bindValue(':STY_LIBELLE', $_POST['STY_LIBELLE'], PDO::PARAM_STR);
    $stmt->bindValue(':STY_CSS', $_POST['STY_CSS'], PDO::PARAM_STR);
    $stmt->bindValue(':idtf', $_POST['idtf'], PDO::PARAM_STR);
    $stmt->bindValue(':SIT_CODE', CMS::getCurrentSite()->getID(), PDO::PARAM_STR);
    $stmt->execute();

    // Purge du cache de l'ensemble du site
    Page::clearCache();
?>
<script>
    window.close();
</script>
<?php
    exit();
} elseif (isset($_GET['Delete'])) {
    $oStyleDynamique = new StyleDynamique($_GET['Delete']);
    $oStyleDynamique->delete($_GET['idtf']);
?>
<script>
    var select = window.opener.document.getElementById('ID_STYLEDYNAMIQUE');
    for (var i = 0; select.options[i]; i++) {
        if (select.options[i].value == '<?php echo $_GET['Delete']?>') {
            select.remove(i);
            break;
        }
    }
    window.opener.updateModify();
    window.close();
</script>
<?php
    exit();
}
