<?php
require './include/inc.fo_init.php';
require CLASS_DIR . 'class.Sitemap.php';

header("Content-Type: text/xml;charset=UTF-8");
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php
$sql = "select * from " . CMS::$mode . "PAGE where PAG_NOINDEX=0 and ID_WEBOTHEQUE_LIENEXTERNE is null and ID_PAGE_REDIRECT is null and SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID());
if (sizeof(Page::getForbiddenID(CMS::$mode)) > 0) {
    $sql .= " and ID_PAGE not in(" . implode(',', Page::getForbiddenID(CMS::$mode)) . ")";
}
foreach ($dbh->query($sql, PDO::FETCH_ASSOC) as $rowTemp) {
    $oPage = new Page($rowTemp['ID_PAGE'], CMS::$mode);
    $oPage->setFields($rowTemp);
    $url = $oPage->getURLESCAPE();
    if (strpos($url, '://') === false) {
        $protocol = $oPage->getField('PAG_HTTPS') ? 'https' : 'http';
        $url = $protocol . '://' . CMS::getCurrentSite()->getField('SIT_HOST') . $url;
    }
    ?>
    <url>
        <loc><?php echo $url ?></loc>
        <lastmod><?php echo date('Y-m-d', $rowTemp['PAG_DATEMODIFICATION'] ? $rowTemp['PAG_DATEMODIFICATION'] : $rowTemp['PAG_DATEMISEENLIGNE'])?></lastmod>
        <changefreq><?php echo (empty($rowTemp['PAG_GOOGLEFREQUENCE'])) ? Sitemap::DEFAULT_FREQUENCY : $rowTemp['PAG_GOOGLEFREQUENCE']?></changefreq>
        <priority><?php echo (empty($rowTemp['PAG_GOOGLEPRIORITE'])) ? Sitemap::DEFAULT_PRIORITY : $rowTemp['PAG_GOOGLEPRIORITE']?></priority>
    </url>
<?php
} ?>
</urlset>
