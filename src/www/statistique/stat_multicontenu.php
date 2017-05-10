<?php
require '../include/inc.bo_init.php';
require CLASS_DIR . 'class.Pagination.php';
require CLASS_DIR . 'class.db_page.php';
require CLASS_DIR . 'class.db_historique.php';
require CLASS_DIR . 'class.db_webotheque.php';
include_once 'stat_searchDateTraitement.inc.php';

Utilisateur::checkConnected();
if (!Utilisateur::getConnected()->isRoot(true)) {
    header('Location:' . SERVER_ROOT . 'statistique/stat_index.php');
    exit ();
}

if (isset($_GET['type']) && $_GET['type'] == "webo") {
    $stats = 'stat_multicontenuWebotheque.inc.php';
    $titre = ucfirst(gettext('tous_mes_sites')) . ' : ' . gettext('activite_sur') . ' la webothèque';
} else if (isset($_GET['type']) && $_GET['type'] == "form") {
    $stats = 'stat_multicontenuFormulaire.inc.php';
    $titre = ucfirst(gettext('tous_mes_sites')) . ' : ' . gettext('activite_sur') . ' les formulaires';
} else if (isset($_GET['type']) && $_GET['type'] == "admin") {
    $stats = 'stat_multicontenuAdministration.inc.php';
    $titre = ucfirst(gettext('tous_mes_sites')) . ' : ' . gettext('activite_sur') . ' la configuration';
} else if (isset($_GET['type']) && $_GET['type'] == "module") {
    $stats = 'stat_multicontenuModule.inc.php';
    $titre = ucfirst(gettext('tous_mes_sites')) . ' : ' . gettext('activite_sur') . ' les modules';
} else {
    $stats = 'stat_multicontenuPage.inc.php';
    $titre = ucfirst(gettext('tous_mes_sites')) . ' : ' . gettext('activite_sur') . ' les pages';
}?>
<!DOCTYPE html>
<html>
<head>
    <?php include('../include/inc.bo_enTete.php');?>
</head>
<body>
<div id="document">
    <?php $aMenuKey = array('TDB', 'SMS', 'CTN'); include('../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu" class="stats">
            <h2><?php echo secureInput($titre)?></h2>
            <div id="tabScrollerContainer">
                <div id="tabScroller">
                    <ul id="bo_onglet">
                        <li <?php echo !isset($_GET['type']) || $_GET['type'] == "" || $_GET['type'] == "page" ? 'class="selected"' : '';?> ><a href="<?php echo PHP_SELF . '?type=page' . $param;?>" ><span><?php echo gettext('Pages')?></span></a></li>
                        <li <?php echo isset($_GET['type']) && $_GET['type'] == "webo" ? 'class="selected"' : '';?> > <a href="<?php echo PHP_SELF . '?type=webo' . $param;?>" <?php echo isset($_GET['type']) && $_GET['type'] == "webo" ? 'class="selected"' : '';?>><span><?php echo gettext('Webotheque')?></span></a></li>
                        <li <?php echo isset($_GET['type']) && $_GET['type'] == "form" ? 'class="selected"' : '';?> > <a href="<?php echo PHP_SELF . '?type=form' . $param;?>" ><span><?php echo gettext('Formulaire')?></span></a></li>
                        <?php if (Historique::isModuleActif()) {?>
                            <li <?php echo isset($_GET['type']) && $_GET['type'] == "module" ? 'class="selected"' : '';?> > <a href="<?php echo PHP_SELF . '?type=module' . $param;?>" ><span><?php echo gettext('Modules')?></span></a></li>
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
            <h4 class="aligncenter"><?php echo $byMonth ? strftime('%B %Y', intval($dateDebut)) : strftime('%Y', intval($dateDebut))?></h4>
            <?php include_once $stats ?>
        </div>
    </div>
    <?php include('../include/inc.bo_bandeau_bas.php') ?>
</div>
</body>
</html>
