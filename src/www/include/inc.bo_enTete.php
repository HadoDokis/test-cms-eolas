<meta http-equiv="Content-Type" content="text/HTML; charset=utf-8">
<title>cms.Eolas - <?php echo secureInput(CMS::getCurrentSite()->getField('SIT_LIBELLE'))?></title>
<meta http-equiv="Content-script-type" content="text/javascript">
<meta http-equiv="Content-style-type" content="text/css">
<meta http-equiv="Content-language" content="<?php echo substr($_SESSION['S_LNG_CODE'], 0, 2)?>">
<meta name="robots" content="noindex, nofollow">
<meta name="Author" content="EOLAS">
<link rel="shortcut icon" href="<?php echo SERVER_ROOT?>images/favicon.ico" type="image/x-icon">
<link rel="stylesheet" href="<?php echo SERVER_ROOT?>include/css/bo.css">
<link rel="stylesheet" href="<?php echo SERVER_ROOT?>include/css/bo_pseudo.css">
<link rel="stylesheet" href="<?php echo SERVER_ROOT?>include/css/print.css" media="print">
<link rel="stylesheet" href="<?php echo SERVER_ROOT?>include/js/jquery/ui/jquery-ui.min.css">
<link rel="stylesheet" href="<?php echo SERVER_ROOT ?>include/js/jquery/colorbox/colorbox.css">
<script>var SERVER_ROOT = '<?php echo SERVER_ROOT?>';</script>
<script>var cms_lang = '<?php echo substr($_SESSION['S_LNG_CODE'], 0, 2)?>';</script>
<script src="<?php echo SERVER_ROOT?>include/js/formCtrl.js"></script>
<script src="<?php echo SERVER_ROOT?>include/js/formCtrl-<?php echo substr($_SESSION['S_LNG_CODE'], 0, 2)?>.js"></script>
<script src="<?php echo SERVER_ROOT?>include/js/jquery/jquery.min.js"></script>
<script src="<?php echo SERVER_ROOT?>include/js/jquery/ui/jquery-ui.min.js"></script>
<script src="<?php echo SERVER_ROOT?>include/js/jquery/ui/i18n/datepicker-<?php echo substr($_SESSION['S_LNG_CODE'], 0, 2)?>.js"></script>
<script src="<?php echo SERVER_ROOT?>include/js/jquery/colorbox/jquery.colorbox-min.js"></script>
<?php if (file_exists(PHYSICAL_PATH . 'include/js/jquery/colorbox/i18n/jquery.colorbox-'.substr($_SESSION['S_LNG_CODE'], 0, 2).'.js')) {?>
<script src="<?php echo SERVER_ROOT?>include/js/jquery/colorbox/i18n/jquery.colorbox-<?php echo substr($_SESSION['S_LNG_CODE'], 0, 2);?>.js"></script>
<?php } ?>
<script src="<?php echo SERVER_ROOT?>include/js/common.js"></script>
<script src="<?php echo SERVER_ROOT?>include/js/coreBo.js"></script>
<script>$(cmsBO.init);</script>
