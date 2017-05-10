<?php
require '../include/inc.pseudo_init.php';
require '../include/inc.debug.php';
Utilisateur::checkConnected();

CMS::$edition = ($_GET['PFM'] == 1);
register_shutdown_function(array('CMS','loadHeader'));

// Récupération de la page à afficher
try {
    $oPage = Page::getPseudoInstance((is_numeric($_REQUEST['idtf'])) ? $_REQUEST['idtf'] : CMS::getCurrentSite()->getHomePage()->getID(), (is_numeric($_GET['rev'])) ? $_GET['rev'] : null);
} catch (Exception $e) {
    die($e->getMessage());
}

if (!$oPage->exist()) {
    $oPage = CMS::getCurrentSite()->getHomePage();
} elseif ($oPage->getField('SIT_CODE') != CMS::getCurrentSite()->getID()) {
    Utilisateur::getConnected()->initSession($oPage->getField('SIT_CODE'));
    //on redirige pour rafraichir les initialisations dans les includes
    header('Location:' . PHP_SELF . '?idtf=' . $oPage->getID());
    exit();
}
if (CMS::$edition) {
    $oPage->lock();
} else {
    Page::unlockAll();
}
CMS::getCurrentSite()->setCurrentPage($oPage);
include(CMS::getCurrentSite()->getField('SIT_INCLUDE') . '/inc.colonnage.php');
if ($oPage->hasLeftColumn() && $oPage->hasRightColumn()) {
    $classColonnage = ' class="avecDeuxColonnes"';
} elseif ($oPage->hasRightColumn()) {
    $classColonnage = ' class="avecColonneDroite"';
} elseif ($oPage->hasLeftColumn()) {
    $classColonnage = ' class="avecColonneGauche"';
} else {
    $classColonnage = '';
}
?>
<!DOCTYPE html>
<html lang="<?php echo CMS::getCurrentSite()->getField('SIT_SHORT_LANGUE')?>">
<head>
    <?php include('../include/inc.pseudo_enTete.php') ?>
</head>
<body<?php if ($oPage->isHome()) echo ' id="Accueil"'?> class="pseudo">
    <script>document.body.className="withJS pseudo"</script>
    <?php $aMenuKey = array('HOME'); include('../include/inc.bo_bandeau_haut.php') ?>
    <?php include CMS::getCurrentSite()->getField('SIT_INCLUDE') . '/inc.document.php' ?>
</body>
</html>
