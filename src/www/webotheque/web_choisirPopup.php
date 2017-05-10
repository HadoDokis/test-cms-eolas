<?php
require '../include/inc.bo_init.php';
Utilisateur::checkConnected();
require CLASS_DIR . 'class.Pagination.php';
require CLASS_DIR . 'class.db_webotheque.php';
require CLASS_DIR . 'class.db_webothequeCategorie.php';
require CLASS_DIR . 'class.File_management.php';

//On traite les cas où, soit le type n'est pas fourni, soit le module n'est pas activé
$WBT_CODE = $_GET['WBT_CODE'];
if (!isset($WBT_CODE) || !CMS::getCurrentSite()->hasModule(new Module(str_replace('WBT_', 'MOD_WEBOTHEQUE_', $WBT_CODE)))) {
    die(gettext('Ressource_non_disponible'));
}

//Préparation de la recherche
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
        $filtre .= " and (WEB_LIBELLE" . $p->makeLike('WEB_LIBELLE') . " or WEB_DESCRIPTION" . $p->makeLike('WEB_LIBELLE');
        if ($WBT_CODE == 'WBT_LIENEXTERNE') {
            $filtre .= " or WEB_CHEMIN" . $p->makeLike('WEB_LIBELLE');
        }
        $filtre .= ")";
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
//Passage des paramètres à conserver pour la soumission du formulaire ou la pagination du moteur
$p->setParam('WBT_CODE', $WBT_CODE);
if (!empty($_GET['IDENTIFIANT'])) {
    $p->setParam('IDENTIFIANT', $_GET['IDENTIFIANT']); //identifiant du champ caché cible contenant l'identifiant
}
if (!empty($_GET['TEXTE'])) {
    $p->setParam('TEXTE', $_GET['TEXTE']); //libellé du champ d'affichage cible
}
if (!empty($_GET['CONCATENATION'])) {
    $p->setParam('CONCATENATION', 1); //permet de concaténer plusieurs valeurs dans les champs cibles
}
if (!empty($_GET['AJAX'])) {
    $p->setParam('AJAX', $_GET['AJAX']); //mode ajax
}
if (!empty($_GET['NOCLOSE'])) {
    $p->setParam('NOCLOSE', 1); //laisse la fenetre ouverte
}
$p->setFilter($filtre);
$p->setCount("select count(ID_WEBOTHEQUE) from WEBOTHEQUE");

if ($WBT_CODE == 'WBT_IMAGE') {
    $titre = "Choisir une image";
} elseif ($WBT_CODE == 'WBT_DOCUMENT') {
    $titre = "Choisir un document";
} elseif ($WBT_CODE == 'WBT_LIENEXTERNE') {
    $titre = "Choisir un lien externe";
} elseif ($WBT_CODE == 'WBT_FLASH') {
    $titre = "Choisir un élément flash";
} elseif ($WBT_CODE == 'WBT_MUSIC') {
    $titre = "Choisir un élément audio";
} elseif ($WBT_CODE == 'WBT_WIDGET') {
    $titre = "Choisir un widget";
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
    <script>
        function maj(id, libelle)
        {
            <?php if ($p->getParam('IDENTIFIANT') != '') { ?>
                <?php if ($p->getParam('CONCATENATION') != '') {?>
            if (window.opener.document.getElementById('<?php echo $p->getParam('IDENTIFIANT')?>').value == '') {
                window.opener.document.getElementById('<?php echo $p->getParam('IDENTIFIANT')?>').value = id;
            } else {
                window.opener.document.getElementById('<?php echo $p->getParam('IDENTIFIANT')?>').value = window.opener.document.getElementById('<?php echo $p->getParam('IDENTIFIANT')?>').value + '@' + id;
            }
               <?php } else { ?>
            window.opener.document.getElementById('<?php echo $p->getParam('IDENTIFIANT')?>').value = id;
               <?php } ?>
               <?php  if ($p->getParam('TEXTE') != '') {?>
            window.opener.document.getElementById('<?php echo $p->getParam('TEXTE')?>').value = libelle + ' (' + id + ')';
                <?php } ?>
            <?php } ?>

            <?php if ($p->getParam('AJAX') != '') {?>
            window.opener.ajaxLiaison.getAjax('<?php echo $p->getParam('AJAX') ?>', 'webotheque', 'insert', id);
            <?php } ?>

            <?php if ($p->getParam('NOCLOSE') == '') {?>
            window.close();
            <?php } ?>

            return false;
        }
    </script>
    <?php
    //Cas du retour suite à l'ajout d'un nouvel élément, on fait alors la maj de l'appelant directement
    if (isset($_GET['idtf'])) {
        $oWebotheque = new Webotheque($_GET['idtf']);
        if ($oWebotheque->checkAuthorized(false) || $oWebotheque->checkRevertAuthorized()) {?>
        <script>
            maj(<?php echo $oWebotheque->getID()?>, '<?php echo escapeJS($oWebotheque->getField(''.($WBT_CODE=='WBT_LIENEXTERNE'?'WEB_CHEMIN':'WEB_LIBELLE').''))?>');
        </script>
        <?php
        }
    } ?>
</head>
<body id="popup">
<?php include('../include/inc.bo_bandeau_hautPopup.php') ?>
<div id="bo_contenuPopup" class="creation">
    <h2><?php echo $titre?></h2>
    <fieldset class="tab">
        <legend><?php echo gettext('MOTEUR_RECHERCHE')?></legend>
        <form method="get" action="<?php echo PHP_SELF?>" class="filtre">
            <table>
                <tfoot>
                    <tr>
                        <td colspan="4"><?php echo $p->actionRecherche()?></td>
                    </tr>
                </tfoot>
                <tbody>
                    <tr>
                        <th><label for="WEB_LIBELLE"><?php echo gettext('Libelle')?> / <?php echo gettext('Description')?></label></th>
                        <td><input type="text" name="WEB_LIBELLE" id="WEB_LIBELLE" value="<?php echo $p->getParam('WEB_LIBELLE')?>" size="25"></td>
                        <th><label for="ID_WEBOTHEQUE"><?php echo gettext('Numero')?></label></th>
                        <td><input type="text" name="ID_WEBOTHEQUE" id="ID_WEBOTHEQUE" value="<?php echo $p->getParam('ID_WEBOTHEQUE')?>" size="10" data-type="integer"></td>
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
        <?php
if ($p->onSearch()) {
    echo $p->reglette();
    if ($p->getNb() > 0) { ?>
        <table class="liste">
            <thead>
                <tr>
                    <th><?php echo $p->tri(gettext('Numero'), 'ID_WEBOTHEQUE')?></th>
                    <th><?php echo $p->tri(gettext('Libelle'), 'WEB_LIBELLE')?></th>
                    <?php if ($WBT_CODE == 'WBT_IMAGE') {?>
                    <th><?php echo gettext('Resolution')?></th>
                    <th><?php echo gettext('Apercu')?></th>
                    <?php } elseif ($WBT_CODE == 'WBT_LIENEXTERNE') {?>
                    <th><?php echo gettext('URL')?></th>
                    <?php } elseif ($WBT_CODE == 'WBT_DOCUMENT') {?>
                    <th><?php echo $p->tri(gettext('Poids'), 'WEB_TAILLE')?></th>
                    <th><?php echo gettext('Apercu')?></th>
                    <?php } elseif ($WBT_CODE == 'WBT_FLASH' || $WBT_CODE == 'WBT_MUSIC' || $WBT_CODE == 'WBT_VIDEO' || $WBT_CODE == 'WBT_VIDEOEXTERNE') { ?>
                    <th><?php echo gettext('Resolution')?></th>
                    <?php }?>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "select WEBOTHEQUE.* from WEBOTHEQUE";
                foreach ($p->fetch($sql) as $rowListe) {
                   $Webotheque_class = 'Webo' . substr($rowListe['WBT_CODE'], 3);
                   $oWebo = new $Webotheque_class ($rowListe['ID_WEBOTHEQUE']);
                   $oWebo->setFields($rowListe);?>
                <tr>
                    <td class="alignright"><?php echo $rowListe['ID_WEBOTHEQUE']?></td>
                    <td><a href="#" onclick="return maj(<?php echo $rowListe['ID_WEBOTHEQUE']?>, '<?php echo escapeJS($rowListe['WEB_LIBELLE'])?>')"><?php echo secureInput($rowListe['WEB_LIBELLE'])?></a></td>
                    <?php if ($WBT_CODE == 'WBT_IMAGE') { ?>
                    <td class="aligncenter"><?php echo $rowListe['WEB_LARGEUR'].'*'.$rowListe['WEB_HAUTEUR'] ?></td>
                    <td class="aligncenter"><img src="<?php echo $oWebo->getThumbSRC()?>" alt=""></td>
                    <?php } elseif ($WBT_CODE == 'WBT_LIENEXTERNE') { ?>
                    <td><?php echo secureInput($rowListe['WEB_CHEMIN'])?></td>
                    <?php } elseif ($WBT_CODE == 'WBT_DOCUMENT') { ?>
                    <td class="alignright"><?php echo File_management::displayFileSize($rowListe['WEB_TAILLE'])?></td>
                    <td><a href="<?php echo UPLOAD_DOCUMENT.$rowListe['WEB_CHEMIN']?>" target="_blank"><?php echo secureInput($rowListe['WEB_LIBELLE'])?></a></td>
                    <?php } elseif ($WBT_CODE == 'WBT_FLASH' || $WBT_CODE == 'WBT_MUSIC' || $WBT_CODE == 'WBT_VIDEO' || $WBT_CODE == 'WBT_VIDEOEXTERNE') { ?>
                    <td class="aligncenter"><?php echo $rowListe['WEB_LARGEUR'] . '*' . $rowListe['WEB_HAUTEUR']?></td>
                    <?php } ?>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
<?php } } ?>
    </fieldset>
    <?php
    //Inclusion du formulaire d'ajout selon le type de média
    $fromPopup = 'webotheque/web_choisirPopup.php?WBT_CODE=' . $WBT_CODE .  '&amp;IDENTIFIANT=' . $_GET['IDENTIFIANT'] . '&amp;TEXTE=' . $_GET['TEXTE'] . '&amp;CONCATENATION=' . $_GET['CONCATENATION'] . '&amp;AJAX=' . $_GET['AJAX'] . '&amp;NOCLOSE=' . $_GET['NOCLOSE'];
    if ($WBT_CODE == 'WBT_IMAGE' && Utilisateur::getConnected()->checkProfil(array('PRO_WEBIMAGE', 'PRO_WEBROOT'))) {
        include 'web_imagePopup.php';
    } elseif ($WBT_CODE == 'WBT_LIENEXTERNE' && Utilisateur::getConnected()->checkProfil(array('PRO_WEBLIENEXTERNE', 'PRO_WEBROOT'))) {
        include 'web_lienExternePopup.php';
    } elseif ($WBT_CODE == 'WBT_DOCUMENT' && Utilisateur::getConnected()->checkProfil(array('PRO_WEBDOCUMENT', 'PRO_WEBROOT'))) {
        include 'web_documentPopup.php';
    } elseif (
        $WBT_CODE == 'WBT_FLASH' && Utilisateur::getConnected()->checkProfil(array('PRO_WEBFLASH', 'PRO_WEBROOT')) ||
        $WBT_CODE == 'WBT_MUSIC' && Utilisateur::getConnected()->checkProfil(array('PRO_WEBMUSIC', 'PRO_WEBROOT')) ||
        $WBT_CODE == 'WBT_VIDEO' && Utilisateur::getConnected()->checkProfil(array('PRO_WEBVIDEO', 'PRO_WEBROOT')) ||
        $WBT_CODE == 'WBT_VIDEOEXTERNE' && Utilisateur::getConnected()->checkProfil(array('PRO_WEBVIDEOEXTERNE', 'PRO_WEBROOT')) ||
        $WBT_CODE == 'WBT_WIDGET' && Utilisateur::getConnected()->checkProfil(array('PRO_WEBWIDGET', 'PRO_WEBROOT'))) {
        include 'web_mediaPopup.php';
    }?>
</div>
<?php include('../include/inc.bo_bandeau_basPopup.php')?>
</body>
</html>
