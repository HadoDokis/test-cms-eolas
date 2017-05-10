<?php
require '../../include/inc.bo_init.php';
CMS::checkAccess(new Module('MOD_CORE'), array('PRO_ROOT'));
require CLASS_DIR . 'class.Pagination.php';
require CLASS_DIR . 'class.db_emailTemplate.php';

$dbh = DB::getInstance();
$p = new Pagination();
$filtre = "1=1";
if ($p->onSearch()) {
    if (!empty($_GET['MOD_CODE'])) {
        $filtre .= " and DD_EMAILTEMPLATE.MOD_CODE=" . $dbh->quote($_GET['MOD_CODE']) ;
    }
} else {
    $p->setOrderBy('MOD_LIBELLE');
}
$p->setFilter($filtre);
$p->setCount("select count(EMT_CODE) from DD_EMAILTEMPLATE");
?>
<!DOCTYPE html>
<html>
<head>
<?php include '../../include/inc.bo_enTete.php' ?>
</head>
<body>
<div id="document">
    <?php $aMenuKey = array('CFG', 'PTF', 'EMT'); include('../../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">
            <h2>Templates d'email</h2>
            <form method="get" action="<?php echo PHP_SELF?>" class="filtre">
                <fieldset>
                    <legend><?php echo gettext('MOTEUR_RECHERCHE')?></legend>
                    <table>
                        <tbody>
                            <tr>
                                <th><label for="MOD_CODE"><?php echo gettext('Module')?></label></th>
                                <td>
                                    <select name="MOD_CODE" id="MOD_CODE">
                                        <option value="">&nbsp;</option>
                                        <?php
                                        $sql = "select * from DD_MODULE
                                            where MOD_CODE in (select distinct MOD_CODE from DD_EMAILTEMPLATE)
                                            order by MOD_LIBELLE";
                                        foreach ($dbh->query($sql, PDO::FETCH_ASSOC) as $rowTemp) {?>
                                            <option value="<?php echo $rowTemp['MOD_CODE']?>"<?php if($p->getParam('MOD_CODE') == $rowTemp['MOD_CODE']) echo ' selected'?>><?php echo extraireLibelle($rowTemp['MOD_LIBELLE'])?></option>
                                        <?php } ?>
                                    </select>
                                </td>
                                <td><?php echo $p->actionRecherche()?></td>
                            </tr>
                        </tbody>
                    </table>
                </fieldset>
            </form>
<?php
echo $p->reglette();
if ($p->getNb() > 0) { ?>
            <table class="liste">
                <thead>
                    <tr>
                        <th>Module</th>
                        <th>Sujet</th>
                        <th>Description</th>
                        <th>Expéditeur</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $sql = "select * from DD_EMAILTEMPLATE
                    inner join DD_MODULE using(MOD_CODE)";
                foreach ($p->fetch($sql) as $rowListe) {?>
                    <tr>
                        <td><?php echo extraireLibelle($rowListe['MOD_LIBELLE'])?></td>
                        <td><?php echo secureInput($rowListe['EMT_SUJET'])?></td>
                        <td><a href="adm_emailTemplate.php?idtf=<?php echo $rowListe['EMT_CODE'] ?>"><?php echo secureInput($rowListe['EMT_DESCRIPTION'])?></a></td>
                        <td>
                            <?php if ($rowListe['EMT_EXPEDITEURFROM']) { ?>
                            <?php echo secureInput($rowListe['EMT_EXPEDITEURFROM'])?>
                                <?php if ($rowListe['EMT_EXPEDITEURFROMNAME']) { ?>
                                (<?php echo secureInput($rowListe['EMT_EXPEDITEURFROMNAME'])?>)
                                <?php } ?>
                            <?php } elseif ($rowListe['EMT_EXPEDITEUR'] == 'USER') { ?>
                            Contributeur connecté
                            <?php } elseif ($rowListe['EMT_EXPEDITEUR'] == 'SITE') { ?>
                            Site courant
                            <?php } else { ?>
                            Plateforme CMS
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
<?php } ?>
        </div>
    </div>
    <?php include '../../include/inc.bo_bandeau_bas.php' ?>
</div>
</body>
</html>
