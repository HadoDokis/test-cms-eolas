<?php
//USERTOOLBAR
$ob = ob_get_contents();
ob_clean();
$aParam = $_GET;
unset($aParam['idtf']);
unset($aParam['UTB_RESET']);
unset($aParam['UTB_FS']);
unset($aParam['UTB_S']);

// Tailles des polices & Styles autorisés
$fontSize_default = 1;
$aFontSize = array($fontSize_default * 0.8, $fontSize_default * 0.9, $fontSize_default, $fontSize_default * 1.1, $fontSize_default * 1.25);
$aStyle = array('default', 'inverse');

$fontSize_selected = array_search($fontSize_default, $aFontSize);
$style_selected = 0;
if (!isset($_GET['UTB_RESET'])) {
    // On tente de récupérer les valeurs utilisateur pour la taille de police
    if (isset($_GET['UTB_FS']) && isset($aFontSize[$_GET['UTB_FS']])) {
        $fontSize_selected = $_GET['UTB_FS'];
    } elseif (isset($_COOKIE['UTB_FS']) && isset($aFontSize[$_COOKIE['UTB_FS']])) {
        $fontSize_selected = $_COOKIE['UTB_FS'];
    }

    // On tente de récupérer les valeurs utilisateur pour le style
    if (isset($_GET['UTB_S']) && isset($aStyle[$_GET['UTB_S']])) {
        $style_selected = $_GET['UTB_S'];
    } elseif (isset($_COOKIE['UTB_S']) && isset($aStyle[$_COOKIE['UTB_S']])) {
        $style_selected = $_COOKIE['UTB_S'];
    }
}

if ($fontSize_selected != array_search($fontSize_default, $aFontSize)) {
    // On met à jour le cookie pour J+365
    setcookie("UTB_FS", $fontSize_selected, time() + 86400*365, SERVER_ROOT);
    // On (rem)place en début de head le bloc qui va bien (style[#userSize])
    $ob = preg_replace('|<head>(<style media="projection, screen">/*id="userSize"*/[^<]*</style>)?|si',
            '<head><style media="projection, screen">/*id="userSize"*/#document {font-size: '.number_format($aFontSize[$fontSize_selected],3).'em !important;}</style>', $ob);
} else {
    setcookie('UTB_FS', '', 0, SERVER_ROOT);
}

if ($style_selected != 0) {
    // On met à jour le cookie pour J+365
    setcookie('UTB_S', $style_selected, time() + 86400*365, SERVER_ROOT);
    // On (rem)place en fin de head le bloc qui va bien (style[#userSize])
    $ob = preg_replace('|(<style media="projection, screen">/*id="userStyle"*/[^<]*</style>)?</head>|si',
            '<style media="projection, screen">/*id="userStyle"*/body, * {color: yellow!important; background: #000!important;} a, a * {color: #fff!important;}}</style></head>', $ob);
} else {
    setcookie('UTB_S', '', 0, SERVER_ROOT);
}

echo $ob;
$oModule = new Module('MOD_CORE');
$SIT_IMAGE = CMS::getCurrentSite()->getField('SIT_IMAGE');
?>
<div id="userTools">
    <a rel="nofollow" href="<?php echo $oPage->getURLESCAPE(array_merge($aParam, array('UTB_FS' => $fontSize_selected - 1)))?>" title="Diminuer la taille de police">
        <img alt="Diminuer la taille de police" src="<?php echo $SIT_IMAGE ?>userTools/userTools_moins.gif">
    </a>
    <a rel="nofollow" class="nospace" href="<?php echo $oPage->getURLESCAPE(array_merge($aParam, array('UTB_FS' => $fontSize_selected + 1)))?>" title="Augmenter la taille de police">
        <img alt="Augmenter la taille de police" src="<?php echo $SIT_IMAGE ?>userTools/userTools_plus.gif">
    </a>
    <a rel="nofollow" href="<?php echo $oPage->getURLESCAPE(array_merge($aParam, array('UTB_S' => ($style_selected + 1) % 2))) ?>" title="Augmenter les contrastes">
        <img alt="Augmenter les contrastes" src="<?php echo $SIT_IMAGE ?>userTools/userTools_contrast.gif">
    </a>
    <a rel="nofollow" href="<?php echo $oPage->getURLESCAPE(array_merge($aParam, array('UTB_RESET' => 1))) ?>" title="Supprimer la personnalisation">
        <img alt="Supprimer la personnalisation" src="<?php echo $SIT_IMAGE ?>userTools/userTools_reset.gif">
    </a>
    <a rel="nofollow" href="javascript:window.print();" title="Lancer l'impression">
        <img alt="Lancer l'impression" src="<?php echo $SIT_IMAGE ?>userTools/userTools_print.gif">
    </a>
    <a rel="nofollow" href="javascript:AddToBookmark();" title="Ajouter aux favoris">
        <img alt="Ajouter aux favoris" src="<?php echo $SIT_IMAGE ?>userTools/userTools_favori.gif">
    </a>
</div>

<script>
    function AddToBookmark()
    {
        if (window.sidebar) {
            window.sidebar.addPanel(document.title, window.location.href, "");
        } else if (window.external) {
            window.external.AddFavorite(window.location.href, document.title);
        }
    }
</script>
