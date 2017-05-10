<?php
require '../include/inc.bo_init.php';
CMS::checkAccess(new Module('MOD_WEBOTHEQUE_IMAGE'));
require CLASS_DIR . 'class.Pagination.php';
require CLASS_DIR . 'class.db_webotheque.php';
require CLASS_DIR . 'class.db_webothequeCategorie.php';
require CLASS_DIR . 'class.File_management.php';

$WBT_CODE = 'WBT_IMAGE';
$p = new Pagination();
$aRevertSharedSite = CMS::getCurrentSite()->getRevertSharedSites();
$filtre = "WBT_CODE=" . $dbh->quote($WBT_CODE);
if (count($aRevertSharedSite) == 0) {
    $filtre .= " and SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID());
} else {
    $filtre .= " and SIT_CODE in (" . $dbh->quote(CMS::getCurrentSite()->getID());
    foreach ($aRevertSharedSite as $SIT_CODE => $null) {
        $filtre .= ", " .  $dbh->quote($SIT_CODE);
    }
    $filtre .= ")";
}
if ($p->onSearch()) {
    if (!empty ($_GET['WEB_LIBELLE'])) {
        $filtre .= " and (WEB_LIBELLE" . $p->makeLike('WEB_LIBELLE') . " or WEB_DESCRIPTION" . $p->makeLike('WEB_LIBELLE') . ")";
    }
    if (is_numeric($_GET['ID_WEBOTHEQUE'])) {
        $filtre .= " and WEBOTHEQUE.ID_WEBOTHEQUE=" . intval($_GET['ID_WEBOTHEQUE']);
    }
    if (!empty ($_GET['ID_WEBOTHEQUECATEGORIE'])) {
        $filtre .= (is_numeric($_GET['ID_WEBOTHEQUECATEGORIE']))
          ? " and WEBOTHEQUE.ID_WEBOTHEQUECATEGORIE=" . intval($_GET['ID_WEBOTHEQUECATEGORIE'])
          : " and WEBOTHEQUE.SIT_CODE=" . $dbh->quote($_GET['ID_WEBOTHEQUECATEGORIE']);
    }
} else {
    $p->setOrderBy('WEB_LIBELLE');
    $p->setParam('ID_WEBOTHEQUECATEGORIE', CMS::getCurrentSite()->getID(), false);
}
$p->setFilter($filtre);
$p->setCount("select count(ID_WEBOTHEQUE) from WEBOTHEQUE");
?>
<!DOCTYPE html>
<html>
<head>
<?php include('../include/inc.bo_enTete.php') ?>
<script src="<?php echo SERVER_ROOT ?>include/js/onglet.js"></script>
</head>
<body id="popup">
    <?php include('../include/inc.bo_bandeau_hautPopup.php') ?>
    <div id="bo_contenuPopup" class="creation">
        <h2>Choisir une image</h2>
        <fieldset class="tab">
            <legend><?php echo gettext('Choisir')?></legend>
            <form method="get" action="<?php echo PHP_SELF?>" class="filtre">
                <table>
                    <tfoot>
                        <tr>
                            <td colspan="4"><?php echo $p->actionRecherche()?></td>
                        </tr>
                    </tfoot>
                    <tbody>
                        <tr>
                            <th><label for="WEB_LIBELLE_choisir"><?php echo gettext('Libelle')?> / <?php echo gettext('Description')?> </label></th>
                            <td><input type="text" name="WEB_LIBELLE" id="WEB_LIBELLE_choisir" value="<?php echo $p->getParam('WEB_LIBELLE')?>" size="25"></td>
                            <th><label for="ID_WEBOTHEQUE_choisir"><?php echo gettext('Numero')?></label></th>
                            <td><input type="text" name="ID_WEBOTHEQUE" id="ID_WEBOTHEQUE_choisir" value="<?php echo $p->getParam('ID_WEBOTHEQUE')?>" size="10" data-type="integer"></td>
                        </tr>
                        <tr>
                            <th><label for="ID_WEBOTHEQUECATEGORIE_choisir"><?php echo gettext('Categorie')?></label></th>
                            <td>
                                <select name="ID_WEBOTHEQUECATEGORIE" id="ID_WEBOTHEQUECATEGORIE_choisir">
                                    <optgroup label="<?php echo secureInput(CMS::getCurrentSite()->getField('SIT_LIBELLE'))?>">
                                        <option value="<?php echo CMS::getCurrentSite()->getID()?>"><?php echo gettext('Toutes les categories')?></option>
                                        <?php echo WebothequeCategorie::getSelectOptions($WBT_CODE, $p->getParam('ID_WEBOTHEQUECATEGORIE'));?>
                                    </optgroup>
                                    <?php foreach ($aRevertSharedSite as $SIT_CODE=>$_oSite) {?>
                                    <optgroup label="<?php echo secureInput($_oSite->getField('SIT_LIBELLE'))?>">
                                        <option value="<?php echo $SIT_CODE?>"<?php if ($p->getParam('ID_WEBOTHEQUECATEGORIE') == $SIT_CODE) echo ' selected'?>><?php echo gettext('Toutes les categories')?></option>
                                        <?php echo WebothequeCategorie::getSelectOptions($WBT_CODE, $p->getParam('ID_WEBOTHEQUECATEGORIE'), null, $SIT_CODE);?>
                                    </optgroup>
                                    <?php } ?>
                                </select>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </form>
<?php
if ($p->onSearch()) {
    echo $p->reglette();
    if ($p->getNb() > 0) { ?>
            <table class="liste">
                <thead>
                    <tr>
                        <th><?php echo $p->tri(gettext('Numero'), 'ID_WEBOTHEQUE')?></th>
                        <th><?php echo $p->tri(gettext('Titre'), 'WEB_LIBELLE')?></th>
                        <th><?php echo gettext('Resolution')?></th>
                        <th><?php echo gettext('Apercu')?></th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $sql = "select * from WEBOTHEQUE";
                foreach ($p->fetch($sql) as $rowListe) {
                    $oWebo = new Webo_IMAGE($rowListe['ID_WEBOTHEQUE']);
                    $oWebo->setFields($rowListe);?>
                    <tr>
                        <td class="alignright"><?php echo $rowListe['ID_WEBOTHEQUE']?></td>
                        <td><a href="editor_insert_image.php?idtf=<?php echo $rowListe['ID_WEBOTHEQUE'] ?>"><?php echo secureInput($rowListe['WEB_LIBELLE'])?></a></td>
                        <td class="aligncenter"><?php echo $rowListe['WEB_LARGEUR'] . '*' . $rowListe['WEB_HAUTEUR'] ?></td>
                        <td class="aligncenter"><img src="<?php echo $oWebo->getThumbSRC()?>" alt=""></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
<?php } } ?>
        </fieldset>
    <?php
    if (Utilisateur::getConnected()->checkProfil(array('PRO_WEBIMAGE', 'PRO_WEBROOT'))) {
        $fromPopup = 'tinymce/editor_insert_image.php?dummy=1';
        include '../webotheque/web_imagePopup.php';
    } ?>
    </div>
    <?php include('../include/inc.bo_bandeau_basPopup.php')?>
</body>
</html>
