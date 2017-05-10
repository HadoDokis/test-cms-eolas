<?php
require '../include/inc.bo_init.php';
CMS::checkAccess(new Module('MOD_WEBOTHEQUE_FLASH'), array('PRO_WEBFLASH', 'PRO_WEBROOT'));
require CLASS_DIR . 'class.db_webotheque.php';
require CLASS_DIR . 'class.db_webothequeCategorie.php';
require CLASS_DIR . 'class.Pagination.php';
require CLASS_DIR . 'class.File_management.php';

$WBT_CODE = 'WBT_FLASH';
$p = new Pagination();
$filtre = "WBT_CODE=" . $dbh->quote($WBT_CODE) . " and SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID());
if ($p->onSearch()) {
    if (!empty ($_GET['WEB_LIBELLE'])) {
        $filtre .= " and (WEB_LIBELLE" . $p->makeLike('WEB_LIBELLE') . " or WEB_DESCRIPTION" . $p->makeLike('WEB_LIBELLE') . ")";
    }
    if (is_numeric($_GET['ID_WEBOTHEQUE'])) {
        $filtre .= " and WEBOTHEQUE.ID_WEBOTHEQUE=" . intval($_GET['ID_WEBOTHEQUE']);
    }
    if (is_numeric ($_GET['ID_WEBOTHEQUECATEGORIE'])) {
        $filtre .= " and WEBOTHEQUE.ID_WEBOTHEQUECATEGORIE=" . intval($_GET['ID_WEBOTHEQUECATEGORIE']);
    }
} else {
    $p->setOrderBy('WEB_LIBELLE');
}
$p->setFilter($filtre);
$p->setCount("select count(ID_WEBOTHEQUE) from WEBOTHEQUE");
?>
<!DOCTYPE html>
<html>
<head>
    <?php include('../include/inc.bo_enTete.php') ?>
</head>
<body>
<div id="document">
    <?php $aMenuKey = array('WEB', 'MOD_WEBOTHEQUE_FLASH', 'FLASH', 'LISTE'); include('../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">
            <h2><?php echo gettext('Flashs')?></h2>
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
                                <th><label for="WEB_LIBELLE"><?php echo gettext('Libelle') . ' / ' . gettext('Description')?></label></th>
                                <td><input type="text" name="WEB_LIBELLE" id="WEB_LIBELLE" value="<?php echo $p->getParam('WEB_LIBELLE')?>" size="30"></td>
                                <th><label for="ID_WEBOTHEQUE"><?php echo gettext('Numero')?></label></th>
                                <td><input type="text" name="ID_WEBOTHEQUE" id="ID_WEBOTHEQUE" value="<?php echo $p->getParam('ID_WEBOTHEQUE')?>" size="10" data-type="integer"></td>
                            </tr>
                            <tr>
                                <th><label for="ID_WEBOTHEQUECATEGORIE"><?php echo gettext('Categorie')?></label></th>
                                <td colspan="3">
                                    <select name="ID_WEBOTHEQUECATEGORIE" id="ID_WEBOTHEQUECATEGORIE">
                                        <option value=""><?php echo gettext('Toutes les categories')?></option>
                                        <?php echo WebothequeCategorie::getSelectOptions($WBT_CODE, $p->getParam('ID_WEBOTHEQUECATEGORIE'));?>
                                    </select>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </fieldset>
            </form>
            <?php
if ($p->onSearch()) {
    echo $p->reglette();
    if ($p->getNb() > 0) {
    ?>
            <form method="post" action="web_webothequeSubmit.php">
                <table class="liste">
                    <thead>
                        <tr>
                            <?php
                            //Partie suppression massive visible uniquement par un gestionnaire webotheque
                            if (Utilisateur::getConnected()->checkProfil(array('PRO_WEBROOT'))) {
                                //Initialisation du compteur "poids total"
                                $PoidsTotalNonAffectes = 0;?>
                            <th><input type="checkbox" onclick="$('.checkbox').not(':disabled').each(function () {$(this).prop('checked', !$(this).prop('checked'))})"></th>
                            <?php } ?>
                            <th><?php echo $p->tri(gettext('Numero'), 'ID_WEBOTHEQUE')?></th>
                            <th><?php echo $p->tri(gettext('Libelle'), 'WEB_LIBELLE')?></th>
                            <th><?php echo $p->tri(gettext('Poids'), 'WEB_TAILLE')?></th>
                            <th><?php echo gettext('Apercu')?></th>
                            <th><?php echo $p->tri("Nombre d’utilisations", 'NB')?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $sql = "select distinct(WEBOTHEQUE.ID_WEBOTHEQUE), WEBOTHEQUE.*, count(distinct(concat(LIA_CODE, ID_LIAISON))) as NB from WEBOTHEQUE
                        left join LIAISON_WEBOTHEQUE on WEBOTHEQUE.ID_WEBOTHEQUE=LIAISON_WEBOTHEQUE.ID_WEBOTHEQUE";
                    foreach ($p->fetch($sql, 'WEBOTHEQUE.ID_WEBOTHEQUE') as $rowListe) {
                        $oWebo = new Webo_FLASH($rowListe['ID_WEBOTHEQUE']);
                        $oWebo->setFields($rowListe);?>
                        <tr>
                            <?php
                            //Partie suppression massive visible uniquement par un gestionnaire webotheque
                            if (Utilisateur::getConnected()->checkProfil(array('PRO_WEBROOT'))) {
                                if ($rowListe['NB']==0) {
                                    $PoidsTotalNonAffectes += $rowListe['WEB_TAILLE'];
                                } ?>
                            <td class="aligncenter"><input type="checkbox" class="checkbox"<?php if ($rowListe['NB']>0) echo '  disabled'?> name="del_web[]" value="<?php echo $rowListe['ID_WEBOTHEQUE']?>"></td>
                            <?php } ?>
                            <td class="alignright"><?php echo $rowListe['ID_WEBOTHEQUE']?></td>
                            <td><a href="web_flash.php?idtf=<?php echo $rowListe['ID_WEBOTHEQUE']?>"><?php echo secureInput($rowListe['WEB_LIBELLE'])?></a></td>
                            <td class="alignright"><?php echo File_management::displayFileSize($rowListe['WEB_TAILLE']) ?></td>
                            <td class="aligncenter"><a href="<?php echo SERVER_ROOT ?>webotheque/web_mediaViewPopup.php?idtf=<?php echo $rowListe['ID_WEBOTHEQUE'] ?>" class="action popup"><?php echo gettext('Voir')?></a></td>
                            <td class="alignright"><?php echo $rowListe['NB']?></td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>

                <?php if (Utilisateur::getConnected()->checkProfil(array('PRO_WEBROOT'))) { ?>
                <p class="action">
                    <input type="hidden" name="WBT_CODE" id="WBT_CODE" value="<?php echo $WBT_CODE ?>">
                    <input type="hidden" name="ID_WEBOTHEQUECATEGORIE" value="<?php echo secureInput($_GET['ID_WEBOTHEQUECATEGORIE']) ?>">
                    <input type="submit" name="massDelete" value="<?php echo gettext('suppression_massive')?>" class="supprimer" onclick="return confirm('<?php echo gettext('Etes-vous sur de vouloir supprimer la selection')?> ?');">
                </p>
                <p>
                    <?php echo gettext('poids_total_inutilises') . ' ' . File_management::displayFileSize($PoidsTotalNonAffectes);?>
                </p>
                <?php } ?>
            </form>
<?php } } ?>
        </div>
    </div>
    <?php include('../include/inc.bo_bandeau_bas.php')?>
</div>
</body>
</html>
