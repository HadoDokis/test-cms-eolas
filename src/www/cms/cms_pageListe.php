<?php
require '../include/inc.bo_init.php';
Utilisateur::checkConnected();
require CLASS_DIR . 'class.db_page.php';
require CLASS_DIR . 'class.Pagination.php';
require CLASS_DIR . 'class.db_thematique.php';

$p = new Pagination();
if ($p->onSearch()) {
    $filtre = "OFF_PAGE.SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID());
    if (!empty ($_GET['PAG_TITRE'])) {
        $filtre .= " and (PAG_TITRE" . $p->makeLike('PAG_TITRE') . " or PAG_TITRE_MENU" . $p->makeLike('PAG_TITRE') . ")";
    }
    if (!empty ($_GET['PAG_MOTCLE'])) {
        $filtre .= " and (PAG_MOTCLE1" . $p->makeLike('PAG_MOTCLE') . "
                    or PAG_MOTCLE2" . $p->makeLike('PAG_MOTCLE') . "
                    or PAG_MOTCLE3" . $p->makeLike('PAG_MOTCLE') . "
                    or PAG_MOTCLE4" . $p->makeLike('PAG_MOTCLE') . "
                    or PAG_MOTCLE5" . $p->makeLike('PAG_MOTCLE') . ")";
        }
    if (is_numeric($_GET['ID_PAGE'])) {
            $filtre .= " and ID_PAGE=" . intval($_GET['ID_PAGE']);
    }
    if (!empty ($_GET['PST_CODE'])) {
            $filtre .= " and OFF_PAGE.PST_CODE=" . $dbh->quote($_GET['PST_CODE']);
    }
    if (is_numeric($_GET['onLine'])) {
            $filtre .= ($_GET['onLine']) ? " and ID_PAGE in (select ID_PAGE from ON_PAGE)" : " and ID_PAGE not in (select ID_PAGE from ON_PAGE)";
    }
    if (is_numeric($_GET['https'])) {
            $filtre .= ($_GET['https']) ? " and PAG_HTTPS = 1" : " and PAG_HTTPS = 0";
    }
    if (!empty ($_GET['PGS_CODE'])) {
            $filtre .= ($_GET['PGS_CODE']) ? " and PGS_CODE <> ''" : " and PGS_CODE = ''";
    }
    if (is_numeric($_GET['ssp'])) {
            $filtre .= ($_GET['ssp']) ? " and ID_PAGE in (select distinct(ID_PAGE) from GROUPE_OFF_PAGE)" : " and ID_PAGE not in (select distinct(ID_PAGE) from GROUPE_OFF_PAGE)";
    }
    if (!empty ($_GET['PAR_BROUILLON'])) {
        $filtre .= " and ID_PAGE ".(($_GET['PAR_BROUILLON']) ? "" : "not") ." in (SELECT distinct OFF_PAGE.ID_PAGE FROM OFF_PARAGRAPHE left join OFF_PAGE on OFF_PARAGRAPHE.ID_PAGE=OFF_PAGE.ID_PAGE where SIT_CODE=".$dbh->quote(CMS::getCurrentSite()->getID())." and PAR_BROUILLON is not null)" ;
    }
    if (!empty ($_GET['ID_THEMATIQUE'])) {
        $filtre .= " and ID_THEMATIQUE" . $p->makeInt('ID_THEMATIQUE') ;
    }
} else {
    $filtre = "0=1";
    $p->setOrderBy('PAG_TITRE_MENU');
}
$p->setFilter($filtre);
$p->setCount("select count(distinct ID_PAGE) from OFF_PAGE
                left join LIAISON_THEMATIQUE on OFF_PAGE.ID_PAGE=LIAISON_THEMATIQUE.ID_LIAISON and LIA_CODE ='OFF_PAGE'");
?>
<!DOCTYPE html>
<html>
<head>
<?php include('../include/inc.bo_enTete.php') ?>
</head>
<body>
<div id="document">
    <?php $aMenuKey = array('CTN', 'PAGE'); if (!empty ($_GET['PST_CODE'])) $aMenuKey[] = $_GET['PST_CODE']; include('../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">
            <h2><?php echo gettext('Pages')?></h2>
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
                                <th><label for="PAG_TITRE"> <?php echo gettext('Titre')?> / <?php echo gettext('Titre menu')?></label></th>
                                <td><input type="text" name="PAG_TITRE" id="PAG_TITRE" value="<?php echo $p->getParam('PAG_TITRE')?>" size="30"></td>
                                <th><label for="onLine_1"><?php echo gettext('Existe sur le site')?></label></th>
                                <td>
                                    <input type="radio" name="onLine" id="onLine_1" value="1"<?php if ($p->getParam('onLine') == '1') echo ' checked'?>>
                                    <label for="onLine_1"><?php echo gettext('Oui')?></label>
                                    <input type="radio" name="onLine" id="onLine_0" value="0"<?php if ($p->getParam('onLine') == '0') echo ' checked'?>>
                                    <label for="onLine_0"><?php echo gettext('Non')?></label>
                                    <input type="radio" name="onLine" id="onLine" value=""<?php if ($p->getParam('onLine') == '') echo ' checked'?>>
                                    <label for="onLine">N/A</label>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="PAG_MOTCLE"> <?php echo gettext('Mot cle')?></label></th>
                                <td><input type="text" name="PAG_MOTCLE" id="PAG_MOTCLE" value="<?php echo $p->getParam('PAG_MOTCLE')?>" size="30"></td>
                                <th><label for="PGS_CODE_1"><?php echo gettext('Page speciale')?></label></th>
                                <td>
                                    <input type="radio" name="PGS_CODE" id="PGS_CODE_1" value="1"<?php if ($p->getParam('PGS_CODE') == '1') echo ' checked'?>>
                                    <label for="PGS_CODE_1"><?php echo gettext('Oui')?></label>
                                    <input type="radio" name="PGS_CODE" id="PGS_CODE_0" value="0"<?php if ($p->getParam('PGS_CODE') == '0') echo ' checked'?>>
                                    <label for="PGS_CODE_0"><?php echo gettext('Non')?></label>
                                    <input type="radio" name="PGS_CODE" id="PGS_CODE" value=""<?php if ($p->getParam('PGS_CODE') == '') echo ' checked'?>>
                                    <label for="PGS_CODE">N/A</label>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="ID_PAGE"> <?php echo gettext('Numero')?></label></th>
                                <td><input type="text" name="ID_PAGE" id="ID_PAGE" value="<?php echo $p->getParam('ID_PAGE')?>" size="10" data-type="integer"></td>
                                <th><label for="PAR_BROUILLON_1"><?php echo gettext('Sauvegarde')?></label></th>
                                <td>
                                    <input type="radio" name="PAR_BROUILLON" id="PAR_BROUILLON_1" value="1"<?php if ($p->getParam('PAR_BROUILLON') == '1') echo ' checked'?>>
                                    <label for="PAR_BROUILLON_1"><?php echo gettext('Oui')?></label>
                                    <input type="radio" name="PAR_BROUILLON" id="PAR_BROUILLON_0" value="0"<?php if ($p->getParam('PAR_BROUILLON') == '0') echo ' checked'?>>
                                    <label for="PAR_BROUILLON_0"><?php echo gettext('Non')?></label>
                                    <input type="radio" name="PAR_BROUILLON" id="PAR_BROUILLON" value=""<?php if ($p->getParam('PAR_BROUILLON') == '') echo ' checked'?>>
                                    <label for="PAR_BROUILLON">N/A</label>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="PST_CODE"><?php echo gettext('Etat')?></label></th>
                                <td>
                                    <select name="PST_CODE" id="PST_CODE">
                                        <option value="">&nbsp;</option>
                                        <?php
                                        $sql = "select * from DD_PAGESTATUT order by PST_POIDS";
                                        foreach ($dbh->query($sql) as $rowTemp) { ?>
                                        <option value="<?php echo $rowTemp['PST_CODE']?>"<?php if ($p->getParam('PST_CODE') == $rowTemp['PST_CODE']) echo ' selected'?>>
                                        <?php echo secureInput(extraireLibelle($rowTemp['PST_LIBELLE']))?></option>
                                        <?php } ?>
                                    </select>
                                </td>
                                <?php if (CMS::getCurrentSite()->hasModule(new Module('MOD_THEMATIQUE'))) {?>
                                <th><label><?php echo gettext('Thematiques')?></label></th>
                                <td><?php Thematique::genererSelectbox($_GET['ID_THEMATIQUE']) ?></td>
                                <?php } ?>
                            </tr>
                            <?php if (CMS::getCurrentSite()->getField('SIT_PAGE_HTTPS') || CMS::getCurrentSite()->hasModule(new Module('MOD_EXTRANET'))) { ?>
                            <tr>
                                <?php if (CMS::getCurrentSite()->getField('SIT_PAGE_HTTPS')) { ?>
                                <th><label for="https_1"><?php echo gettext('Acces securise (HTTPS)')?></label></th>
                                <td>
                                    <input type="radio" name="https" id="https_1" value="1"<?php if ($p->getParam('https') == '1') echo ' checked'?>>
                                    <label for="https_1"><?php echo gettext('Oui')?></label>
                                    <input type="radio" name="https" id="https_0" value="0"<?php if ($p->getParam('https') == '0') echo ' checked'?>>
                                    <label for="https_0"><?php echo gettext('Non')?></label>
                                    <input type="radio" name="https" id="https" value=""<?php if ($p->getParam('https') == '') echo ' checked'?>>
                                    <label for="https">N/A</label>
                                </td>
                                <?php } ?>
                                <?php if (CMS::getCurrentSite()->hasModule(new Module('MOD_EXTRANET'))) {?>
                                <th><label><?php echo gettext('Acces restreint')?></label></th>
                                <td>
                                    <input type="radio" name="ssp" id="ssp_1" value="1"<?php if ($p->getParam('ssp') == '1') echo ' checked'?>>
                                    <label for="ssp_1"><?php echo gettext('Oui')?></label>
                                    <input type="radio" name="ssp" id="ssp_0" value="0"<?php if ($p->getParam('ssp') == '0') echo ' checked'?>>
                                    <label for="ssp_0"><?php echo gettext('Non')?></label>
                                    <input type="radio" name="ssp" id="ssp" value=""<?php if ($p->getParam('ssp') == '') echo ' checked'?>>
                                    <label for="ssp">N/A</label>
                                </td>
                                <?php } ?>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </fieldset>
            </form>
<?php
if ($p->onSearch() || $p->getNb() > 0) {
    echo $p->reglette();
    if ($p->getNb() > 0) { ?>
            <table class="liste">
                <thead>
                    <tr>
                        <th><?php echo $p->tri(gettext('Numero'), 'ID_PAGE')?></th>
                        <th><?php echo $p->tri(gettext('Titre'), 'PAG_TITRE_MENU')?></th>
                        <th><?php echo gettext('Etat')?></th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $sql = "select distinct ID_PAGE, OFF_PAGE.*, DD_PAGESTATUT.PST_LIBELLE from OFF_PAGE
                        left join LIAISON_THEMATIQUE on OFF_PAGE.ID_PAGE=LIAISON_THEMATIQUE.ID_LIAISON and LIA_CODE ='OFF_PAGE'
                        inner join DD_PAGESTATUT on OFF_PAGE.PST_CODE=DD_PAGESTATUT.PST_CODE";
                foreach ($p->fetch($sql) as $rowListe) {
                    $oPage = new Page($rowListe['ID_PAGE']); ?>
                    <tr>
                        <td class="alignright"><?php echo $rowListe['ID_PAGE']?></td>
                        <td>
                            <?php if ($oPage->checkAuthorized(false) && (!$oPage->isLocked() || Utilisateur::getConnected()->isRoot())) { ?>
                            <a href="cms_page.php?idtf=<?php echo $rowListe['ID_PAGE']?>"><?php echo secureInput($rowListe['PAG_TITRE_MENU'])?></a>
                            <?php } else { ?>
                            <?php echo secureInput($rowListe['PAG_TITRE_MENU'])?>
                            <?php } ?>
                        </td>
                        <td class="aligncenter"><?php echo secureInput(extraireLibelle($rowListe['PST_LIBELLE']))?></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
            <?php } } ?>
        </div>
    </div>
    <?php include('../include/inc.bo_bandeau_bas.php')?>
</div>
</body>
</html>
