<?php
$dbh = DB::getInstance();
if (!$oPageRecherche = CMS::getCurrentSite()->getSpecialePage('PGS_RECHERCHE')) {
    $oPageRecherche = CMS::getCurrentSite()->getCurrentPage();
} ?>
<ul class="liste">
<?php
$sql = "select * from RECHERCHEREFERENCEMENT where SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID()) . " order by REC_TITLE";
foreach ($dbh->query($sql)->fetchAll(PDO :: FETCH_ASSOC) as $row) { ?>
    <li class="item">
        <div class="itemInfo">
            <h3>
                <a <?php echo $oPageRecherche->getAnchor(array('idRef'=>$row['ID_RECHERCHEREFERENCEMENT'], 'searchString'=>$row['REC_EXPRESSION'])) ?>><?php echo encode($row['REC_TITLE']) ?></a>
            </h3>
            <p class="description"><?php echo encode($row['REC_RESUME']) ?></p>
        </div>
    </li>
<?php } ?>
</ul>
