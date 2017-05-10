<?php
if (!$isActualData) {
    //$oRev = new Revision($revIdselected);
    //$oRev->loadPage();
    //$oPage = $oRev->getPage();
}
$SIT_INCLUDE = CMS::getCurrentSite()->getField('SIT_INCLUDE');
?>

<?php include($SIT_INCLUDE . '/inc.navigation_haut.php') ?>
<div id="corps" class="clearfix">
    <?php if ($oPage->hasLeftColumn()) include($SIT_INCLUDE . '/inc.navigation_gauche.php');?>
    <?php include($SIT_INCLUDE . '/inc.fo_contenu.php');?>
    <?php if ($oPage->hasRightColumn()) include($SIT_INCLUDE . '/inc.navigation_droite.php');?>
</div>
<?php include($SIT_INCLUDE . '/inc.navigation_bas.php') ?>
