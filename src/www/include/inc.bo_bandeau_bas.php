<div id="bo_bandeau_bas">
    <a href="http://www.eolas.fr" onclick="window.open(this.href); return false;"><?php echo secureInput(gettext('Une solution EOLAS'))?></a>
    <div class="version">
        <a href="http://cms.eolas.fr" onclick="window.open(this.href); return false;">CMS.Eolas v<?php echo CMS::getVersion()?></a>
        <span>- <?php echo gettext('CMS_BASELINE')?></span>
        <img src="<?php echo SERVER_ROOT.'images/logo_basPage.png'?>" alt="" style="vertical-align:middle">
    </div>
</div>
<?php
if (!empty($_SESSION['S_msg']['ERROR'])) {
    $msg = " - " . implode("\n - ", $_SESSION['S_msg']['ERROR']);?>
<script>
alert('<?php echo sizeof($_SESSION['S_msg']['ERROR'])?> <?php echo (sizeof($_SESSION['S_msg']['ERROR']) > 1) ? gettext('Erreurs') : gettext('Erreur')?> :\n<?php echo escapeJS($msg)?>');
</script>
<?php
    $_SESSION['S_msg']['ERROR'] = array ();
}
?>
