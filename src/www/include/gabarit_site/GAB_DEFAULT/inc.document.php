<?php
$SIT_INCLUDE = CMS::getCurrentSite()->getField('SIT_INCLUDE');
?>
<div id="document"<?php echo $classColonnage?>>
  <?php include($SIT_INCLUDE . '/inc.navigation_haut.php') ?>
  <div id="corps" class="clearfix">
    <?php if ($oPage->hasLeftColumn()) include($SIT_INCLUDE . '/inc.navigation_gauche.php');?>
    <?php include($SIT_INCLUDE . '/inc.fo_contenu.php');?>
    <?php if ($oPage->hasRightColumn()) include($SIT_INCLUDE . '/inc.navigation_droite.php');?>
  </div>
  <?php include($SIT_INCLUDE . '/inc.navigation_bas.php') ?>
</div>
