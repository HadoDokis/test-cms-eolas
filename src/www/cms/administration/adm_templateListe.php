<?php
require '../../include/inc.bo_init.php';
CMS::checkAccess(new Module('MOD_CORE'), array('PRO_ROOT'));
require CLASS_DIR . 'class.Pagination.php';
require CLASS_DIR . 'class.db_template.php';
$dbh = DB::getInstance();
$p = new Pagination();
$filtre = "1=1";
if (Utilisateur::getConnected()->isSEO() || !Utilisateur::getConnected()->isRoot(true)) {
    $aMDL = array_keys(CMS::getCurrentSite()->getModules());
    if (!empty($aMDL)) {
        //recupere les TPL_CODE [template] appartenant aux modules actives et avec gabarit correspondant a celui du site actuel
        $sql = "select DD_TEMPLATE.TPL_CODE from DD_TEMPLATE
        left join DD_TEMPLATE_GABARIT on (DD_TEMPLATE.TPL_CODE = DD_TEMPLATE_GABARIT.TPL_CODE)
        where DD_TEMPLATE.MOD_CODE in (" . implode(',', array_map(array($dbh, 'quote'), $aMDL)) . ")
        and (ID_TEMPLATE_GABARIT IS NULL or DD_TEMPLATE_GABARIT.GAB_CODE = " . $dbh->quote(CMS::getCurrentSite()->getField('GAB_CODE')).")";
        $aLstTplCodes = $dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN);
        if (sizeof($aLstTplCodes) > 0) {
            $filtre .= " and DD_TEMPLATE.TPL_CODE in (" . implode(',', array_map(array($dbh, 'quote'), $aLstTplCodes)) . ")";
        }else $filtre .= " and DD_TEMPLATE.TPL_CODE = ''"; //pas de template dispo

    } else {
        //pas de module active, donc recuperation des templates appartenant au module noyau et correspondant au gabarit du site actuel
        $sql = "select DD_TEMPLATE.TPL_CODE from DD_TEMPLATE
        left join DD_TEMPLATE_GABARIT on (DD_TEMPLATE.TPL_CODE = DD_TEMPLATE_GABARIT.TPL_CODE)
        where DD_TEMPLATE.MOD_CODE in ('MOD_CORE')
        and (ID_TEMPLATE_GABARIT IS NULL or DD_TEMPLATE_GABARIT.GAB_CODE = " . $dbh->quote(CMS::getCurrentSite()->getField('GAB_CODE')).")";
        $aLstTplCodes = $dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN);
        if (sizeof($aLstTplCodes) > 0) {
            $filtre .= " and DD_TEMPLATE.TPL_CODE in (" . implode(',', array_map(array($dbh, 'quote'), $aLstTplCodes)) . ")";
        }else $filtre .= " and DD_TEMPLATE.TPL_CODE = ''"; //pas de template dispo

    }

    $filtre .= " AND OFF_PAGE.SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID());
}

if ($p->onSearch()) {
    if (!empty($_GET['MOD_CODE'])) {
        $filtre .= " and DD_TEMPLATE.MOD_CODE=" . $dbh->quote($_GET['MOD_CODE']) ;
    }
    if (!empty($_GET['TPL_LIBELLE'])) {
        $filtre .= " and TPL_LIBELLE" . $p->makeLike('TPL_LIBELLE');
    }
}
$p->setFilter($filtre);
$p->setOrderBy('MOD_TPL_LIBELLE');

$p->setCount("select count(distinct TPL_CODE) from DD_TEMPLATE
    inner join DD_MODULE using (MOD_CODE)
    left join OFF_PARAGRAPHE using (TPL_CODE)
    left join OFF_PAGE on (OFF_PARAGRAPHE.ID_PAGE = OFF_PAGE.ID_PAGE)");
?>
<!DOCTYPE html>
<html>
<head>
<?php include('../../include/inc.bo_enTete.php')?>
</head>
<body>
<div id="document">
    <?php $aMenuKey = array('CFG', 'PTF', 'TPL'); include('../../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">
            <h2><?php echo gettext('Templates')?></h2>
            <form method="get" action="<?php echo PHP_SELF?>" class="filtre">
                <fieldset>
                    <legend><?php echo gettext('MOTEUR_RECHERCHE')?></legend>
                    <table>
                        <tfoot>
                            <tr>
                                <td colspan="4"><?php echo $p->actionRecherche()?></td>
                            </tr>
                        </tfoot>
                        <tbody>
                            <tr>
                                <th><label for="MOD_CODE"><?php echo gettext('Module')?></label></th>
                                <td>
                                    <select name="MOD_CODE" id="MOD_CODE">
                                        <option value="">&nbsp;</option>
                                        <?php
                                        if (Utilisateur::getConnected()->isRoot(true)) {
                                        $sql = "select * from DD_MODULE
                                            where MOD_CODE in (select distinct MOD_CODE from DD_TEMPLATE)
                                            order by MOD_LIBELLE";
                                        } else {
                                        $sql = "select * from DD_MODULE
                                            where DD_MODULE.MOD_CODE in (
                                                select distinct DD_TEMPLATE.MOD_CODE from DD_TEMPLATE
                                                inner join SITE_MODULE on (DD_TEMPLATE.MOD_CODE = SITE_MODULE.MOD_CODE)
                                                where SITE_MODULE.SIT_CODE like ". $dbh->quote(CMS::getCurrentSite()->getID()) ."
                                                )
                                            order by MOD_LIBELLE";
                                        }
                                        foreach ($dbh->query($sql, PDO::FETCH_ASSOC) as $rowTemp) {?>
                                            <option value="<?php echo secureInput($rowTemp['MOD_CODE'])?>" <?php if($p->getParam('MOD_CODE') == $rowTemp['MOD_CODE']) echo 'selected'?>><?php echo secureInput(extraireLibelle($rowTemp['MOD_LIBELLE']))?></option>
                                        <?php } ?>
                                    </select>
                                </td>
                                <th><label for="TPL_LIBELLE"> <?php echo gettext('Libelle')?></label></th>
                                <td><input type="text" name="TPL_LIBELLE" id="TPL_LIBELLE" value="<?php echo $p->getParam('TPL_LIBELLE')?>" size="30"></td>
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
                        <th><?php echo $p->tri('Libellé', 'TPL_LIBELLE')?></th>
                        <th>Nom du template dans URL</th>
                        <th><?php echo $p->tri('Utilisation sur le site', 'NB')?></th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $sql = "select distinct(DD_TEMPLATE.TPL_CODE), concat(MOD_LIBELLE, TPL_LIBELLE) MOD_TPL_LIBELLE, MOD_LIBELLE, TPL_LIBELLE, TPL_URLCODE, TPL_AFFECTABLE,
                    SUM(CASE
                       WHEN OFF_PAGE.ID_PAGE IS NULL THEN 0
                       ELSE 1
                       END) AS NB
                    from DD_TEMPLATE
                    inner join DD_MODULE using (MOD_CODE)
                    left join OFF_PARAGRAPHE using (TPL_CODE)
                    left join OFF_PAGE on (OFF_PARAGRAPHE.ID_PAGE = OFF_PAGE.ID_PAGE)";
                foreach ($p->fetch($sql, 'DD_TEMPLATE.TPL_CODE') as $rowListe) {?>
                    <tr>
                        <td><?php echo secureInput(extraireLibelle($rowListe['MOD_LIBELLE']))?></td>
                        <td><a href="adm_template.php?idtf=<?php echo $rowListe['TPL_CODE'] ?>"><?php echo secureInput(extraireLibelle($rowListe['TPL_LIBELLE']))?></a></td>
                        <td><?php echo secureInput($rowListe['TPL_URLCODE'])?></td>
                        <td class="alignright"><?php echo $rowListe['TPL_AFFECTABLE'] ? $rowListe['NB'] : 'N/A'?></td>
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
