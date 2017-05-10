<?php
require './include/inc.fo_init.php';
require CLASS_DIR . 'class.Sitemap.php';

if (!$oPageRecherche = CMS::getCurrentSite()->getSpecialePage('PGS_RECHERCHE')) {
    $oPageRecherche = CMS::getCurrentSite()->getHomePage();
}

$sql = "select * from RECHERCHEREFERENCEMENT where SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID());
if (! $aRow = $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC)) {
    return;
}
header("Content-Type: text/xml;charset=UTF-8");
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php
foreach ($aRow as $rowTemp) {
    $url = $oPageRecherche->getURLESCAPE(array('idRef' => $rowTemp['ID_RECHERCHEREFERENCEMENT']));
    if (strpos($url, '://') === false) {
        $url = 'http://' . CMS::getCurrentSite()->getField('SIT_HOST') . $url;
    } ?>
    <url>
        <loc><?php echo $url ?></loc>
        <?php if ($rowTemp['REC_GOOGLELASTMOD']) { ?>
        <lastmod><?php echo date('Y-m-d', $rowTemp['REC_GOOGLELASTMOD'])?></lastmod>
        <?php } ?>
        <changefreq><?php echo secureInput($rowTemp['REC_GOOGLEFREQUENCE'])?></changefreq>
        <priority><?php echo secureInput($rowTemp['REC_GOOGLEPRIORITE'])?></priority>
    </url>
<?php
}
?>
</urlset>
