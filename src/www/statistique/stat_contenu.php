<?php
require '../include/inc.bo_init.php';
require CLASS_DIR . 'class.Pagination.php';
require CLASS_DIR . 'class.db_page.php';
require CLASS_DIR . 'class.db_historique.php';
require CLASS_DIR . 'class.db_webotheque.php';
Utilisateur::checkConnected();
include_once 'stat_searchDateTraitement.inc.php';

if (isset($_GET['type']) && $_GET['type'] == "webo") {
    $include_stats = 'stat_contenuWebotheque.inc.php';
    $titre = CMS::getCurrentSite()->getField('SIT_LIBELLE') . ' : activité sur la webothèque';
} elseif (isset($_GET['type']) && $_GET['type'] == "form") {
    $include_stats = 'stat_contenuFormulaire.inc.php';
    $titre = CMS::getCurrentSite()->getField('SIT_LIBELLE') . ' : activité sur les formulaires';
} elseif (isset($_GET['type']) && $_GET['type'] == "admin") {
    $include_stats = 'stat_contenuAdministration.inc.php';
    $titre = CMS::getCurrentSite()->getField('SIT_LIBELLE') . ' : activité sur la configuration';
} elseif (isset($_GET['type']) && $_GET['type'] == "module") {
    $include_stats = 'stat_contenuModule.inc.php';
    $titre = CMS::getCurrentSite()->getField('SIT_LIBELLE') . ' : activité sur les modules';
} else {
    $include_stats = 'stat_contenuPage.inc.php';
    $titre = CMS::getCurrentSite()->getField('SIT_LIBELLE') . ' : activité sur les pages';
}?>
<!DOCTYPE html>
<html>
<head>
    <?php include('../include/inc.bo_enTete.php');?>
</head>
<body>
<div id="document">
    <?php $aMenuKey = array('TDB', 'SSC', 'CTN'); include('../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu" class="stats">
            <h2><?php echo secureInput($titre)?></h2>
            <div id="tabScrollerContainer">
                <div id="tabScroller">
                    <ul id="bo_onglet">
                        <li <?php echo !isset($_GET['type']) || $_GET['type'] == "" || $_GET['type'] == "page" ? 'class="selected"' : '';?> > <a href="<?php echo PHP_SELF . '?type=page' . $param;?>" ><span>Pages</span></a></li>
                        <li <?php echo isset($_GET['type']) && $_GET['type'] == "webo" ? 'class="selected"' : '';?> > <a href="<?php echo PHP_SELF . '?type=webo' . $param;?>" ><span>Webothèque</span></a></li>
                        <li <?php echo isset($_GET['type']) && $_GET['type'] == "form" ? 'class="selected"' : '';?> > <a href="<?php echo PHP_SELF . '?type=form' . $param;?>" ><span>Formulaires</span></a></li>
                        <?php if (Historique::isModuleActif()) {?>
                            <li <?php echo isset($_GET['type']) && $_GET['type'] == "module" ? 'class="selected"' : '';?> > <a href="<?php echo PHP_SELF . '?type=module' . $param;?>" ><span>Modules</span></a><li>
                        <?php }?>
                        <li <?php echo isset($_GET['type']) && $_GET['type'] == "admin" ? 'class="selected"' : '';?> > <a href="<?php echo PHP_SELF . '?type=admin' . $param;?>" ><span>Configuration</span></a></li>
                    </ul>
                  </div>
            </div>
            <?php include 'stat_searchDateForm.inc.php';?>
            <div class="stat_container">
                <div id="placeholder" class="stat_placeholder"></div>
            </div>
            <div class="stat_container_default">
                <img class="graph_default invisble" src="<?php echo SERVER_ROOT . 'images/graph_default.jpg'?>" alt="">
            </div>
            <h4 class="aligncenter"><?php echo $byMonth ? ucfirst(strftime('%B %Y', intval($dateDebut))) : strftime('%Y', intval($dateDebut))?></h4>
            <?php include_once $include_stats?>
        </div>
    </div>
    <?php include('../include/inc.bo_bandeau_bas.php')?>
</div>
</body>
</html>
