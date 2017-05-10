<?php
require '../../include/inc.bo_init.php';
require_once CLASS_DIR . 'class.db_page.php';
CMS::checkAccess(new Module('MOD_CORE'), array('PRO_ROOT'));

if (! empty($_POST['Update'])) {
    $sql = "select ID_RECHERCHE from DD_RECHERCHE where REC_SITEMAP=1";
    foreach ($dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $stmt = $dbh->prepare("update DD_RECHERCHE set
            REC_FILTRESITE=:REC_FILTRESITE,
            REC_GOOGLEFREQUENCE=:REC_GOOGLEFREQUENCE,
            REC_GOOGLEPRIORITE=:REC_GOOGLEPRIORITE
            where ID_RECHERCHE = :ID_RECHERCHE");
        $stmt->bindValue(':REC_FILTRESITE', isset($_POST['REC_FILTRESITE_' . $row['ID_RECHERCHE']]), PDO::PARAM_INT);
        $stmt->bindValue(':REC_GOOGLEFREQUENCE', $_POST['REC_GOOGLEFREQUENCE_' . $row['ID_RECHERCHE']], PDO::PARAM_STR);
        $stmt->bindValue(':REC_GOOGLEPRIORITE', $_POST['REC_GOOGLEPRIORITE_' . $row['ID_RECHERCHE']], PDO::PARAM_STR);
        $stmt->bindValue(':ID_RECHERCHE', $row['ID_RECHERCHE'], PDO::PARAM_STR);
        $stmt->execute();

        if (isset($_POST['REC_GOOGLELASTMOD_' . $row['ID_RECHERCHE']])) {
            $stmt = $dbh->prepare("update DD_RECHERCHE set
                REC_GOOGLELASTMOD=:REC_GOOGLELASTMOD
                where ID_RECHERCHE = :ID_RECHERCHE");
            $stmt->bindValue(':REC_GOOGLELASTMOD', unixtime($_POST['REC_GOOGLELASTMOD_' . $row['ID_RECHERCHE']]), PDO::PARAM_INT);
            $stmt->bindValue(':ID_RECHERCHE', $row['ID_RECHERCHE'], PDO::PARAM_STR);
            $stmt->execute();
        }
    }

    setMsg(gettext('UPDATE_OK'));
    header('Location:' . SERVER_ROOT . 'cms/administration/adm_sitemap.php');
    exit();
}
