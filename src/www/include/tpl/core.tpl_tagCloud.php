<?php
$dbh   = DB::getInstance();
$oPage = CMS::getCurrentSite()->getCurrentPage();
if (!$oPageRecherche = CMS::getCurrentSite()->getSpecialePage('PGS_RECHERCHE')) {
    $oPageRecherche = $oPage;
}

$aTag = $oPage->getTag(12);
if (count($aTag) == 0) {
    Paragraphe::noRender();

    return;
}
$cpt = 1;
foreach ($aTag as $key => $null) {
    $aTag[$key] = $cpt++;
}
ksort($aTag, SORT_LOCALE_STRING);
$delimiter = intval(count($aTag) / 3);
?>
<ul>
<?php
foreach ($aTag as $tag => $cpt) {
    $class = ($cpt <= $delimiter ? 'large' : ($cpt > $delimiter*2 ? 'small' : 'medium'));
    echo '<li class="' . $class . '"><a ' . $oPageRecherche->getAnchor(array('TPL_CODE'=>'TPL_TAGLISTE', 'PAR_TPL_IDENTIFIANT'=>$tag)) . '>' . encode($tag) . '</a></li>';
} ?>
</ul>
