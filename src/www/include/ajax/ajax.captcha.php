<?php
include '../inc.fo_init.php';
require_once CLASS_DIR . 'class.CMSCaptcha.php';
$oCaptcha = new Graphical_CAPTCHA();
?>
<img src="<?php echo $oCaptcha->getImageSRC() ?>" alt="Captcha anti-spam" class="captchaImg">
<input type="hidden" name="<?php echo secureInput($_GET['name']) ?>" value="<?php echo $oCaptcha->getID() ?>">
