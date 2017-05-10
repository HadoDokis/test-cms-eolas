<?php
require '../include/inc.bo_init.php';
if ($_GET['typelien'] == 'LienExterne') {
    $WBT_CODE = 'WBT_LIENEXTERNE';
    $MOD_CODE = 'MOD_WEBOTHEQUE_LIENEXTERNE';
    //Pour un lien externe, on va chercher dans l'url plutôt que la description
    $CHAMP_DESCRIPTION= 'WEB_CHEMIN';
    $titre = "Choisir un lien externe";
} elseif ($_GET['typelien'] == 'LienDocument') {
    $WBT_CODE = 'WBT_DOCUMENT';
    $MOD_CODE = 'MOD_WEBOTHEQUE_DOCUMENT';
    $CHAMP_DESCRIPTION= 'WEB_DESCRIPTION';
    $titre = "Choisir un document";
} elseif ($_GET['typelien'] == 'LienImage') {
    $WBT_CODE = 'WBT_IMAGE';
    $MOD_CODE = 'MOD_WEBOTHEQUE_IMAGE';
    $CHAMP_DESCRIPTION= 'WEB_DESCRIPTION';
    $titre = "Choisir une image";
}
CMS::checkAccess(new Module($MOD_CODE));
require CLASS_DIR . 'class.Pagination.php';
require CLASS_DIR . 'class.db_webotheque.php';
require CLASS_DIR . 'class.db_webothequeCategorie.php';
require CLASS_DIR . 'class.File_management.php';

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
        $filtre .= " and (WEB_LIBELLE" . $p->makeLike('WEB_LIBELLE') . " or " . $CHAMP_DESCRIPTION . $p->makeLike('WEB_LIBELLE') . ")";
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
$p->setParam('typelien', $_GET['typelien']);
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
    <?php include('../include/inc.bo_bandeau_hautPopup.php')?>
    <div id="bo_contenuPopup" class="creation">
        <h2><?php echo $titre?></h2>
        <fieldset class="tab">
            <legend>Rechercher</legend>
            <form method="get" action="<?php echo PHP_SELF?>" class="filtre">
                <table>
                    <tfoot>
                        <tr>
                            <td colspan="4"><?php echo $p->actionRecherche()?></td>
                        </tr>
                    </tfoot>
                    <tbody>
                        <tr>
                            <th><label for="WEB_LIBELLE_choisir"><?php echo gettext('Libelle')?> / <?php echo gettext('Description')?></label></th>
                            <td><input type="text" name="WEB_LIBELLE" id="WEB_LIBELLE_choisir" value="<?php echo $p->getParam('WEB_LIBELLE')?>" size="25"></td>
                            <th><label for="ID_WEBOTHEQUE_choisir"><?php echo gettext('Numero')?></label></th>
                            <td><input type="text" name="ID_WEBOTHEQUE" id="ID_WEBOTHEQUE_choisir" value="<?php echo $p->getParam('ID_WEBOTHEQUE')?>" size="10" data-type="integer"></td>
                        </tr>
                        <tr>
                            <th><label for="ID_WEBOTHEQUECATEGORIE_choisir"><?php echo gettext('Categorie')?></label></th>
                            <td colspan="3">
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
        </fieldset>
        <?php
    if ($p->onSearch()) {
        echo $p->reglette();
        if ($p->getNb() > 0) { ?>
        <table class="liste">
            <thead>
                <tr>
                    <th><?php echo $p->tri(gettext('Numero'), 'ID_WEBOTHEQUE')?></th>
                    <th><?php echo $p->tri(gettext('Libelle'), 'WEB_LIBELLE')?></th>
                    <th><?php echo ($_GET['typelien'] == 'LienExterne') ? gettext('URL') : $p->tri(gettext('Poids'), 'WEB_TAILLE')?></th>
                </tr>
            </thead>
            <tbody>
            <?php
            $sql = "select * from WEBOTHEQUE";
            foreach ($p->fetch($sql) as $rowListe) {?>
                <tr>
                    <td class="alignright"><?php echo $rowListe['ID_WEBOTHEQUE']?></td>
                    <td><a href="editor_insert_webotheque.php?idtf=<?php echo $rowListe['ID_WEBOTHEQUE']?>&amp;typelien=<?php echo $_GET['typelien']?>"><?php echo secureInput($rowListe['WEB_LIBELLE'])?></a></td>
                    <td><?php echo ($_GET['typelien'] == 'LienExterne') ? $rowListe['WEB_CHEMIN'] : File_management::displayFileSize($rowListe['WEB_TAILLE'])?></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    <?php } } ?>

    <?php
    if ($_GET['typelien'] == 'LienExterne' && Utilisateur::getConnected()->checkProfil(array('PRO_WEBLIENEXTERNE', 'PRO_WEBROOT'))) {
        $fromPopup = 'tinymce/editor_insert_webotheque.php?typelien=LienExterne';
        include '../webotheque/web_lienExternePopup.php';
    } elseif ($_GET['typelien'] == 'LienDocument' && Utilisateur::getConnected()->checkProfil(array('PRO_WEBDOCUMENT', 'PRO_WEBROOT'))) {
        $fromPopup = 'tinymce/editor_insert_webotheque.php?typelien=LienDocument';
        include '../webotheque/web_documentPopup.php';
    } elseif ($_GET['typelien'] == 'LienImage' && Utilisateur::getConnected()->checkProfil(array('PRO_WEBIMAGE', 'PRO_WEBROOT'))) {
        $fromPopup = 'tinymce/editor_insert_webotheque.php?typelien=LienImage';
        include '../webotheque/web_imagePopup.php';
    } ?>
    </div>
    <?php include('../include/inc.bo_bandeau_basPopup.php')?>
</body>
</html>
