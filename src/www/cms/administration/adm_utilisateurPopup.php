<?php
require '../../include/inc.bo_init.php';
Utilisateur::checkConnected();
require CLASS_DIR . 'class.db_page.php';
require CLASS_DIR . 'class.Pagination.php';

$oPage = new Page($_GET['idtf']);
$oPage->checkAuthorized();
$p = new Pagination();
if (! $p->onSearch()) {
    $p->setOrderBy('UTI_NOM');
    $p->setFilter("ROLE.ID_PAGE in (" . implode(',', $oPage->getParentsID(true)) . ")
        or ROLE.PRO_CODE='PRO_ROOT'
        or (ROLE.PRO_CODE='PRO_ROOT_SITE' and ROLE.SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID()) . ")");
    $p->setParam('idtf', $oPage->getID());
    $p->setCount("select count(UTILISATEUR.ID_UTILISATEUR) from UTILISATEUR
        inner join ROLE using (ID_UTILISATEUR)");
}
?>
<!DOCTYPE html>
<html>
<head>
    <?php include('../../include/inc.bo_enTete.php')?>
</head>
<body id="popup">
    <?php include('../../include/inc.bo_bandeau_hautPopup.php')?>
    <div id="bo_contenuPopup">
        <h2>Utilisateurs ayant un accès à cette page</h2>
        <br>
        <?php echo $p->reglette();?>
        <table class="liste">
            <thead>
                <tr>
                    <th>Utilisateur</th>
                    <th>Rôle</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "select * from UTILISATEUR
                    inner join ROLE using (ID_UTILISATEUR)
                    inner join DD_PROFIL using(PRO_CODE)";
                foreach ($p->fetch($sql) as $rowListe) {
                    ?>
                <tr>
                    <td>
                        <?php if (Utilisateur::getConnected()->isRoot()) { ?>
                        <a href="javascript:window.opener.location.href='adm_utilisateur.php?idtf=<?php echo $rowListe['ID_UTILISATEUR']?>'; window.close()">
                            <?php echo secureInput($rowListe['UTI_NOM'] . ' ' . $rowListe['UTI_PRENOM'])?>
                        </a>
                        <?php } else { ?>
                        <?php echo secureInput($rowListe['UTI_NOM'] . ' ' . $rowListe['UTI_PRENOM'])?>
                        <?php } ?>
                    </td>
                    <td><?php echo secureInput(extraireLibelle($rowListe['PRO_LIBELLE']))?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <?php include('../../include/inc.bo_bandeau_basPopup.php')?>
</body>
</html>
