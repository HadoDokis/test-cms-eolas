<?php
require './include/inc.fo_init.php';
require CLASS_DIR . 'class.Sitemap.php';

header("Content-Type: text/xml;charset=UTF-8");
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php
// Inclusion des sitemaps dédiés
$aSitemap = glob(PHYSICAL_PATH . 'sitemap.*.php');
foreach ($aSitemap as $file) {
    $filename = basename($file);
    if (preg_match('/(sitemap\.[^\.]+)\.php/', $filename, $matche)) {
        echo '<sitemap>
                <loc>http://'.CMS::getCurrentSite()->getField('SIT_HOST').SERVER_ROOT.$matche[1].'.xml</loc>
                <lastmod>'.date('Y-m-d').'</lastmod>
            </sitemap>';
    }
}
?>
</sitemapindex>
