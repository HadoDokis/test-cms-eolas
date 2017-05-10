<?php
$title = !empty($_GET['PAG_TITLE']) ? $_GET['PAG_TITLE'] : $oPage->getField('PAG_TITRE_REFERENCEMENT');
$title .= ' - ' . CMS::getCurrentSite()->getField('SIT_TITLE');

if ($oPage->getField('PAG_METADESCRIPTION') != '') {
    $metaDescription = $oPage->getField('PAG_METADESCRIPTION');
} else {
    $oPartagrapheTemp = reset($oPage->getParagraphes('PAR_CENTRAL'));
    $metaDescription = ($oPartagrapheTemp) ? $oPartagrapheTemp->getField('PAR_CONTENUTEXTE') : '';
}
$metaDescription = resume($metaDescription, 200);
$tabMC = array();
for ($i=1; $i<6; $i++) {
    if ($oPage->getField('PAG_MOTCLE' . $i) != '' ) {
        $tabMC[] = $oPage->getField('PAG_MOTCLE' . $i);
    }
}
?>
<meta charset="UTF-8">
<title><?php echo encode($title , false)?></title>
<meta name="Description" content="<?php echo encode($metaDescription, false)?>">
<?php if (count($tabMC)) { ?>
<meta name="Keywords" content="<?php echo encode(implode(',', $tabMC), false);?>">
<?php } ?>
<?php if ($oPage->getField('PAG_NOINDEX')) { ?>
<meta name="robots" content="noindex">
<?php } ?>
<?php if (CMS::getCurrentSite()->getField('SIT_AUTHOR') != '') { ?>
<meta name="author" content="<?php echo encode(CMS::getCurrentSite()->getField('SIT_AUTHOR'), false)?>">
<?php } ?>
<?php
if ($_oPage = $oPage->getCanonicalPage()) {
    CMS::addHEADER('<link rel="canonical" href="' . $_oPage->getURLEscape() . '">');
}
?>
<?php if ($src = CMS::getCurrentSite()->getFaviconSRC()) { ?>
<link rel="shortcut icon" href="<?php echo $src?>" type="image/x-icon">
<?php } ?>
<?php
if (CMS::getCurrentSite()->getField('GAB_CSS_PATH') != '') {
    CMS::addLESS(SERVER_ROOT . CMS::getCurrentSite()->getField('GAB_CSS_PATH'), array('media' => 'screen, print'));
}
if (CMS::getCurrentSite()->getField('GBS_PATH') != '') {
    CMS::addLESS(SERVER_ROOT . CMS::getCurrentSite()->getField('GBS_PATH'), array('media' => 'screen, print'));
}
// Style de page ?
$oPage->loadPageStyles();

// Style dynamique ?
$oPage->loadDynamicStyles();

CMS::addLESS(SERVER_ROOT . CMS::getCurrentSite()->getField('GAB_PRINT_CSS_PATH'), array('media' => 'print'));
?>
<script>var SERVER_ROOT = '<?php echo SERVER_ROOT?>'; var SIT_IMAGE = '<?php echo CMS::getCurrentSite()->getField('SIT_IMAGE')?>';</script>
<?php
CMS::addJS(SERVER_ROOT . 'include/js/jquery/jquery.min.js');
CMS::addJS(SERVER_ROOT . 'include/js/jquery/ui/jquery-ui.min.js');
CMS::addJS(SERVER_ROOT . 'include/js/jquery/ui/i18n/datepicker-' . CMS::getCurrentSite()->getField('SIT_SHORT_LANGUE') . '.js');
CMS::addCSS(SERVER_ROOT . 'include/js/jquery/ui/jquery-ui.min.css');
CMS::addJS(SERVER_ROOT . 'include/js/jquery/colorbox/jquery.colorbox-min.js');
if (file_exists(PHYSICAL_PATH . 'include/js/jquery/colorbox/i18n/jquery.colorbox-' . CMS::getCurrentSite()->getField('SIT_SHORT_LANGUE') . '.js')) {
    CMS::addJS(SERVER_ROOT . 'include/js/jquery/colorbox/i18n/jquery.colorbox-' . CMS::getCurrentSite()->getField('SIT_SHORT_LANGUE') . '.js');
}
CMS::addCSS(SERVER_ROOT . 'include/js/jquery/colorbox/colorbox.css');
CMS::addJS(SERVER_ROOT . 'include/js/core.js.php');
CMS::addJS(SERVER_ROOT . 'include/flashplayer/jwplayer/jwplayer.js');
CMS::addJS(SERVER_ROOT . 'include/js/audiodescription.js');

if ($oPage->getField('PAG_HEAD')) {
    CMS::addHEADER($oPage->getField('PAG_HEAD'));
}
