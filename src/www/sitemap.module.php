<?php
require './include/inc.fo_init.php';
require CLASS_DIR . 'class.Sitemap.php';

if (!$oPageRecherche = CMS::getCurrentSite()->getSpecialePage('PGS_RECHERCHE')) {
    $oPageRecherche = CMS::getCurrentSite()->getHomePage();
}

$sql = "select * from DD_RECHERCHE
    inner join SITE_MODULE on DD_RECHERCHE.MOD_CODE=SITE_MODULE.MOD_CODE
    where SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID()) . " and REC_SITEMAP=1";
if (! $aRow = $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC)) {
    return;
}
header("Content-Type: text/xml;charset=UTF-8");
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php
foreach ($aRow as $rowTemp) {
    if (($rowTemp['PGS_CODE'] == '') || !($oPage = CMS::getCurrentSite()->getSpecialePage($rowTemp['PGS_CODE']))) {
        $oPage = $oPageRecherche;
    }
    $sql = "select * from " . $rowTemp['REC_TABLE'] . " where 1=1";
    if ($rowTemp['REC_FILTRESITE']) {
        $sql .= " and SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID());
    }
    if ($rowTemp['REC_FILTRE'] != '') {
        $filtre = ' ' . $rowTemp['REC_FILTRE'];
        eval("\$filtre = \"$filtre\";" );
        $sql .= $filtre;
    }
    foreach ($dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $rowModule) {
        $url = $oPage->getURLESCAPE(array('PAR_TPL_IDENTIFIANT' => $rowModule[$rowTemp['REC_IDENTIFIANT']], 'TPL_CODE' => $rowTemp['TPL_CODE']));
        if (strpos($url, '://') === false) {
            $protocol = $oPage->getField('PAG_HTTPS') ? 'https' : 'http';
            $url = $protocol . '://' . CMS::getCurrentSite()->getField('SIT_HOST') . $url;
        }
    ?>
    <url>
        <loc><?php echo $url ?></loc>
        <?php if (is_numeric($rowTemp['REC_GOOGLELASTMOD'])) { ?>
        <lastmod><?php echo date('Y-m-d', $rowTemp['REC_GOOGLELASTMOD'])?></lastmod>
        <?php } elseif ($rowTemp['REC_GOOGLELASTMOD'] != '') { ?>
        <lastmod><?php echo date('Y-m-d', $rowModule[$rowTemp['REC_GOOGLELASTMOD']])?></lastmod>
        <?php } ?>
        <changefreq><?php echo secureInput($rowTemp['REC_GOOGLEFREQUENCE'])?></changefreq>
        <priority><?php echo secureInput($rowTemp['REC_GOOGLEPRIORITE'])?></priority>
    </url>
<?php
    }
} ?>
</urlset>
