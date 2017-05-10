<?php
require 'include/inc.fo_init.php';

// Si on appel le flux depuis une page
if ($_SERVER['HTTP_REFERER'] != '') {
    header('Content-Type: application/xml; charset=UTF-8');
} else {
    header('Content-Type: application/rss+xml; charset=UTF-8');
}
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<rss version="2.0" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">
    <channel>
        <title><![CDATA[<?php echo CMS::getCurrentSite()->getField('SIT_TITLE')?>]]></title>
        <link>http://<?php echo CMS::getCurrentSite()->getField('SIT_HOST') . SERVER_ROOT?></link>
        <description><![CDATA[Dernières pages mises à jour sur le site.]]></description>
        <?php
$sql = "select * from ON_PAGE
    where SIT_CODE = " . $dbh->quote(CMS::getCurrentSite()->getID()) . "
    order by PAG_DATEMODIFICATION desc limit 0, 10";
foreach ($dbh->query($sql)->fetchAll(PDO :: FETCH_ASSOC) as $row) {
    $oPage = new Page($row['ID_PAGE'], 'ON_');
    $oPage->setFields($row);?>
        <item>
            <title><![CDATA[<?php echo $row['PAG_TITRE']?>]]></title>
            <?php
            $url = $oPage->getURL();
            if (strpos($url, '://') === false) {
                $url = 'http://' . CMS::getCurrentSite()->getField('SIT_HOST') . $url;
            }
            ?>
            <link><?php echo $url ?></link>
            <description><![CDATA[<?php echo $row['PAG_ACCROCHE']?>]]></description>
            <pubDate><?php echo date('r', $row['PAG_DATEMODIFICATION']?$row['PAG_DATEMODIFICATION']:$row['PAG_DATEMISEENLIGNE'])?></pubDate>
            <guid isPermaLink="false">http://<?php echo $oPage->getURL()?></guid>
        </item>
        <?php } ?>
    </channel>
</rss>
