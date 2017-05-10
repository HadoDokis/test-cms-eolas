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
<meta name="robots" content="noindex, nofollow">
<meta name="Author" content="EOLAS">
<link rel="shortcut icon" href="<?php echo SERVER_ROOT?>images/favicon.ico" type="image/x-icon">
<?php
CMS::addCSS(SERVER_ROOT . 'include/css/bo_pseudo.css');
CMS::addCSS(SERVER_ROOT . 'include/css/pseudo.css');
if (CMS::getCurrentSite()->getField('GAB_CSS_PATH') != '') {
    CMS::addLESS(SERVER_ROOT . CMS::getCurrentSite()->getField('GAB_CSS_PATH'), array('media' => 'screen, print'));
}
if (CMS::getCurrentSite()->getField('GBS_PATH') != '') {
    CMS::addLESS(SERVER_ROOT . CMS::getCurrentSite()->getField('GBS_PATH'), array('media' => 'screen, print'));
}
$oPage->loadPageStyles();
$oPage->loadDynamicStyles();

CMS::addLESS(SERVER_ROOT . CMS::getCurrentSite()->getField('GAB_PRINT_CSS_PATH'), array('media' => 'print'));
?>
<script>
    var SERVER_ROOT = '<?php echo SERVER_ROOT?>';
    var SIT_IMAGE = '<?php echo CMS::getCurrentSite()->getField('SIT_IMAGE')?>';
    var cms_lang = '<?php echo substr($_SESSION['S_LNG_CODE'], 0, 2)?>';
</script>
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
if (CMS::$edition) {
    CMS::addJS(SERVER_ROOT . 'include/js/dragDrop.js');
}
CMS::addJS(SERVER_ROOT . 'include/js/common.js');
CMS::addJS(SERVER_ROOT . 'include/flashplayer/jwplayer/jwplayer.js');
CMS::addJS(SERVER_ROOT . 'include/js/audiodescription.js');
if (isset($_SESSION['S_msg']['NOTIFICATION_PSEUDO']) && !empty($_SESSION['S_msg']['NOTIFICATION_PSEUDO'][0])) {
    CMS::addDOMREADY("alert('".escapeJS($_SESSION['S_msg']['NOTIFICATION_PSEUDO'][0])."');");
    unset($_SESSION['S_msg']['NOTIFICATION_PSEUDO']);
}
CMS::addJS(SERVER_ROOT . 'include/js/coreBo.js');
CMS::addDOMREADY("cmsBO.initMenu();");
