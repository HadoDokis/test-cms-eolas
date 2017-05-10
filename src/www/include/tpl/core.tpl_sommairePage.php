<?php
$dbh = DB::getInstance();

$sql = "select * from ". CMS::$mode ."PARAGRAPHE
    where PAR_COLONNE='PAR_CENTRAL' and (TPL_CODE<>'TPL_SOMMAIREPAGE' or TPL_CODE is NULL)
    and ID_PAGE=" . intval(CMS::getCurrentSite()->getCurrentPage()->getID()) . " order by PAR_POIDS";
$aParagraphe = $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
if (count($aParagraphe) == 0) {
    Paragraphe::noRender();
    return;
}
?>
<ul>
<?php
foreach ($aParagraphe as $rowTPL) {
    $PAR_TITRE = $rowTPL['PAR_TITRE'];
    if ($rowTPL['TPL_CODE'] == 'TPL_PARTAGE' || $rowTPL['TPL_CODE'] == 'TPL_HERITAGE') {
        $sql = "select PAR_TITRE from ". CMS :: $mode ."PARAGRAPHE where ID_PARAGRAPHE=" . intval($rowTPL['PAR_TPL_IDENTIFIANT']);
        $PAR_TITRE = $dbh->query($sql)->fetchColumn();
    } else {
        $PAR_TITRE = $rowTPL['PAR_TITRE'];
    }
    if ($PAR_TITRE == '') {
        continue;
    } ?>
    <li>
        <a href="#par<?php echo $rowTPL['ID_PARAGRAPHE']?>">
            <?php echo encode($PAR_TITRE)?>
        </a>
    </li>
<?php } ?>
</ul>
