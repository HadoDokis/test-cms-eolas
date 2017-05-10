<?php
require '../../include/inc.bo_init.php';
CMS::checkAccess(new Module('MOD_CORE'), array('PRO_ROOT'));
require CLASS_DIR . 'class.Pagination.php';

$p = new Pagination();
if ($p->onSearch()) {
    $filtre = "1=1";
    if (!empty($_GET['LDA_LIBELLE']))
        $filtre .= " and (LDA_LIBELLE " . $p->makeLike('LDA_LIBELLE') . ")";
    $p->setFilter($filtre);
} else {
    $p->setOrderBy('LDA_LIBELLE');
}
$sql = "select count(ID_LDAP) from LDAP";
$p->setCount($sql);
?>
<!DOCTYPE html>
<html>
<head>
<?php include('../../include/inc.bo_enTete.php') ?>
</head>
<body>
<div id="document">
    <?php $aMenuKey = array('CFG', 'PTF', 'LDAP', 'LISTE'); include('../../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">
            <h2>Annuaires LDAP</h2>
            <form method="get" action="<?php echo PHP_SELF?>" class="filtre">
                <fieldset>
                    <legend><?php echo gettext('MOTEUR_RECHERCHE')?></legend>
                    <table>
                        <tbody>
                            <tr>
                                <th><label for="LDA_LIBELLE"><?php echo gettext('Libelle')?></label></th>
                                <td><input name="LDA_LIBELLE" type="text" id="LDA_LIBELLE" value="<?php echo $p->getParam('LDA_LIBELLE')?>" size="30"></td>
                                <td><?php echo $p->actionRecherche()?></td>
                            </tr>
                        </tbody>
                    </table>
                </fieldset>
            </form>
<?php
echo $p->reglette();
if ($p->getNb() > 0) {?>
            <table class="liste">
                <thead>
                    <tr>
                        <th><?php echo $p->tri(gettext('Libelle'), 'LDA_LIBELLE')?></th>
                        <th><?php echo $p->tri('Nombre d\'utilisateurs', 'NB_UTI')?></th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $sql = "select LDAP.ID_LDAP, LDA_LIBELLE, count(ID_UTILISATEUR) as NB_UTI
                    from LDAP
                    left join UTILISATEUR ON LDAP.ID_LDAP= UTILISATEUR.ID_LDAP";
                foreach ($p->fetch($sql,'LDAP.ID_LDAP') as $rowListe) {?>
                    <tr>
                        <td class="aligncenter">
                            <a href="adm_ldap.php?idtf=<?php echo $rowListe['ID_LDAP']?>">
                                <?php echo secureInput($rowListe['LDA_LIBELLE'])?>
                            </a>
                        </td>
                        <td class="aligncenter"><?php echo secureInput($rowListe['NB_UTI'])?></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
<?php } ?>
        </div>
    </div>
    <?php include('../../include/inc.bo_bandeau_bas.php')?>
</div>
</body>
</html>
