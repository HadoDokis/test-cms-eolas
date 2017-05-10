<?php
$aID_PAGE = explode("@", Paragraphe::getCurrentTemplateRestriction());
if (count($aID_PAGE)==0) {
    Paragraphe::noRender();
    return;
}
?>
<ul class="liste">
<?php
foreach ($aID_PAGE as $ID_PAGE) {
    $oPageChild = new Page($ID_PAGE, CMS::$mode);
    if (!$oPageChild->exist()) {
        continue;
    }
    $url = $oPageChild->getAnchor();
    ?>
    <li class="item">
        <?php if ($oWebo = $oPageChild->getAccroche()) { ?>
        <a <?php echo $url ?>><img alt="" src="<?php echo $oWebo->getSRC('IMF_50')?>"></a>
        <?php } ?>
        <div class="itemInfo">
            <h3><a <?php echo $url?>><?php echo encode($oPageChild->getField('PAG_TITRE_MENU'))?></a></h3>
            <?php if ($oPageChild->getField('PAG_ACCROCHE')) { ?>
            <p class="description"><?php echo encode($oPageChild->getField('PAG_ACCROCHE')) ?></p>
            <?php } ?>
        </div>
    </li>
<?php } ?>
</ul>
