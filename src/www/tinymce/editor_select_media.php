<?php
require '../include/inc.bo_init.php';
$WBT_CODE = $_GET['WBT_CODE'];
$MOD_CODE = str_replace('WBT_', 'MOD_WEBOTHEQUE_', $WBT_CODE);
CMS::checkAccess(new Module($MOD_CODE));
require CLASS_DIR . 'class.Pagination.php';
require CLASS_DIR . 'class.db_webotheque.php';
require CLASS_DIR . 'class.db_webothequeCategorie.php';
require CLASS_DIR . 'class.File_management.php';

$isMULTI = in_array($WBT_CODE, array('WBT_MUSIC'));

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
$p->setParam('WBT_CODE', $WBT_CODE);
$p->setFilter($filtre);
$p->setCount("select count(ID_WEBOTHEQUE) from WEBOTHEQUE");

if ($WBT_CODE == 'WBT_FLASH') {
    $titre = "Choisir un élément flash";
} elseif ($WBT_CODE == 'WBT_MUSIC') {
    $titre = "Choisir un élément audio";
} elseif ($WBT_CODE == 'WBT_VIDEO') {
    $titre = "Choisir une vidéo";
} elseif ($WBT_CODE == 'WBT_VIDEOEXTERNE') {
    $titre = "Choisir une vidéo externe";
} else {
    $titre = "Choisir un élément de webothèque";
}
?>
<!DOCTYPE html>
<html>
<head>
    <?php include('../include/inc.bo_enTete.php') ?>
    <script src="<?php echo SERVER_ROOT ?>include/js/onglet.js"></script>
</head>
<body id="popup">
    <?php include('../include/inc.bo_bandeau_hautPopup.php')?>
    <div id="bo_contenuPopup" class="creation">
        <h2><?php echo $titre?></h2>
        <fieldset class="tab">
            <legend><?php echo gettext('Choisir')?></legend>
            <form method="get" action="<?php echo PHP_SELF?>" class="filtre">
                <table>
                    <tfoot>
                        <tr>
                            <td colspan="2"><?php echo $p->actionRecherche()?></td>
                        </tr>
                    </tfoot>
                    <tbody>
                        <tr>
                            <th><label for="WEB_LIBELLE_choisir"><?php echo gettext('Libelle')?> / <?php echo gettext('Description')?></label></th>
                            <td><input type="text" name="WEB_LIBELLE" id="WEB_LIBELLE_choisir" value="<?php echo $p->getParam('WEB_LIBELLE')?>" size="25"></td>
                        </tr>
                        <tr>
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
            <?php if ($isMULTI) { ?>
            <form action="editor_insert_media.php" method="post">
            <?php } ?>
                <table class="liste">
                    <thead>
                        <tr>
                            <th><?php echo $isMULTI ? '' : $p->tri(gettext('Numero'), 'ID_WEBOTHEQUE')?></th>
                            <th><?php echo $p->tri(gettext('Titre'), 'WEB_LIBELLE')?></th>
                            <th><?php echo gettext('Resolution')?></th>
                        </tr>
                    </thead>
            <?php if ($isMULTI) { ?>
                    <tfoot>
                        <tr>
                            <td colspan="3">
                            <input type="submit" value="Ajouter" class="ajouter" >
                            <input type="hidden" value="1" name="isMULTI">
                            <input type="hidden" value="<?php echo $WBT_CODE?>" name="WBT_CODE">
                            </td>
                        </tr>
                    </tfoot>
            <?php } ?>
                    <tbody>
                    <?php
                    $sql = "select * from WEBOTHEQUE";
                    foreach ($p->fetch($sql) as $rowListe) { ?>
                        <tr>
                            <?php if ($isMULTI) { ?>
                            <td class="aligncenter"><input type="checkbox" name="ID_WEBOTHEQUE[]" value="<?php echo $rowListe['ID_WEBOTHEQUE']?>"></td>
                            <?php } else { ?>
                            <td class="alignright"><?php echo $rowListe['ID_WEBOTHEQUE']?></td>
                            <?php } ?>
                            <td class="aligncenter"><a href="editor_insert_media.php?idtf=<?php echo $rowListe['ID_WEBOTHEQUE']?>"><?php echo secureInput($rowListe['WEB_LIBELLE'])?></a></td>
                            <td class="aligncenter"><?php echo $rowListe['WEB_LARGEUR'] . '*' . $rowListe['WEB_HAUTEUR'] ?></td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            <?php if ($isMULTI) { ?>
            </form>
            <?php } ?>
<?php } } ?>
        </fieldset>
    <?php
    if (Utilisateur::getConnected()->checkProfil(array('PRO_WEB' . strtoupper(substr($WBT_CODE, 4)), 'PRO_WEBROOT'))) {
        $fromPopup = 'tinymce/editor_insert_media.php?WBT_CODE=' . $WBT_CODE;
        include '../webotheque/web_mediaPopup.php';
    } ?>
    </div>
    <?php include('../include/inc.bo_bandeau_basPopup.php')?>
</body>
</html>
