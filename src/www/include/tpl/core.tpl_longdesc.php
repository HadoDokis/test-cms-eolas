<?php
require_once CLASS_DIR . 'class.Editor.php';
require_once CLASS_DIR . 'class.db_webotheque.php';

$oWebotheque = new Webo_IMAGE(Paragraphe::getCurrentTemplateRestriction());
if (!$oWebotheque->checkAuthorized(false) && !$oWebotheque->checkShareAuthorized(false)) {
     Paragraphe::noRender();

     return;
}
$oPage = CMS::getCurrentSite()->getCurrentPage();?>
<?php echo Editor::displayContent($oWebotheque->getField('WEB_DESCRIPTIONACC'), $oPage); ?>
<p>
    <a <?php echo $oPage->getAnchor(); if ($oPage->getField('PAG_TITLE') != '') echo ' title="'.encode($oPage->getField('PAG_TITLE'), false).'"'?>><?php echo encode($oPage->getField('PAG_TITRE_MENU'))?></a>
</p>
