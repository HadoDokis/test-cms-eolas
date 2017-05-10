<?php
include_once CLASS_DIR.'class.db_webotheque.php';

$dbh   = DB::getInstance();
$oPage = CMS::getCurrentSite()->getCurrentPage();

$PAR_TPL_IDENTIFIANT = Paragraphe::getCurrentTemplateRestriction();

$aParams = explode('@',$PAR_TPL_IDENTIFIANT);

$ID_WEBOTHEQUE = $aParams[0];
$width        = $aParams[1].($aParams[2]?"":"%");
$height       = $aParams[3];
$frameborder  = $aParams[4];
$scrolling    = $aParams[5];
$marginheight = $aParams[6];
$marginwidth  = $aParams[7];
if (CMS::getCurrentSite()->hasModule(new Module('MOD_ACCESSIBILITE'))) {
    $title  = $aParams[8];
}
$oWeboExt = new Webo_LIENEXTERNE($ID_WEBOTHEQUE);
if (!$oWeboExt->checkAuthorized(false) && !$oWeboExt->checkShareAuthorized(false)) {
     Paragraphe::noRender();

     return;
}
?>
<div class="tpl_appliExterne">
    <iframe
        src="<?php echo $oWeboExt->getField('WEB_CHEMIN') ?>"
        width="<?php echo $width ?>"
        height="<?php echo $height ?>"
        frameborder="<?php echo $frameborder ?>"
        scrolling="<?php echo $scrolling ?>"
        marginheight="<?php echo $marginheight ?>"
        marginwidth="<?php echo $marginwidth ?>"
        <?php
        if (isset($title)) {
            echo ' title="' . encode($title, false) . '"';
        } ?>></iframe>
</div>
