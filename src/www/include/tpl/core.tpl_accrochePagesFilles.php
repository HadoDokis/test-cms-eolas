<?php
$oPage = CMS::getCurrentSite()->getCurrentPage();
$aPagesChilds = $oPage->getChildrenForMenu();
if (empty($aPagesChilds)) {
    Paragraphe::noRender();
    return;
}
$aID_PAGE = array();
foreach ($aPagesChilds as $oPageChild) {
    $aID_PAGE[] = $oPageChild->getID();
}
Paragraphe::setCurrentTemplateRestriction(implode('@', $aID_PAGE));
include 'core.tpl_accrochePages.php';
