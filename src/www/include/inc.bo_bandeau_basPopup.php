<div id="bo_bandeau_basPopup"><a class="btnAction" href="javascript:window.close()"><?php echo gettext('Fermer')?></a></div>
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
