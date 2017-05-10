<?php
require_once CLASS_DIR . 'class.Pagination_FO.php';

$dbh   = DB::getInstance();
$oPage = CMS::getCurrentSite()->getCurrentPage();
$tag = Paragraphe::getCurrentTemplateRestriction();

$p = new Pagination_FO();
if (!empty($tag)) {
    $p->setParam('PAR_TPL_IDENTIFIANT', $tag, false);
    $aFiltre = array();
    for ($i = 1; $i <= 5; $i++) {
        $aFiltre[] = 'PAG_MOTCLE'.$i .' = '.$dbh->quote($tag);
    }
    $filtre = implode(' or ', $aFiltre);
} else {
    $filtre = "1=0";
}
$p->setOrderBy('PAG_DATEMODIFICATION');
$p->setFilter($filtre);
$p->setMPP(10);
$p->setCount("select count(ID_PAGE) from " . CMS::$mode . "PAGE");
if ($p->getNb() == 0) {
    Paragraphe::noRender();

    return;
}
$sql = "select * from " . CMS::$mode . "PAGE";
$aPage = $p->fetch($sql);
if ($p->getNb() == 1) {
    $oPage = new Page($aPage[0]['ID_PAGE'], CMS::$mode);
    $oPage->setFields($aPage[0]);
    $oPage->redirect();
}
$alter = 1;
?>
<ul class="liste">
<?php
foreach ($aPage as $rowListe) {
    $oPageCour = new Page($rowListe['ID_PAGE'], CMS::$mode);
    $oPageCour->setFields($rowListe);?>
    <li class="item alter<?php echo $alter++ % 2?>">
        <?php
        $oWebImageAccroche = $oPageCour->getAccroche();
        if ($oWebImageAccroche) {
            echo $oWebImageAccroche->getHTML('IMF_SMALL', $oWebImageAccroche->getField('LIA_TEXT'), 'alignleft', false, '', false, false);
        }
        ?>
        <div class="itemInfo">
            <h3>
                <a <?php echo $oPageCour->getAnchor(); if ($rowListe['PAG_TITLE'] != '') echo ' title="'.encode($rowListe['PAG_TITLE'], false).'"'?>><?php echo encode($rowListe['PAG_TITRE_MENU']) ?></a>
            </h3>
            <p class="filAriane">
            <?php foreach ($oPageCour->getParents() as $parent) { ?>
                <a <?php echo $parent->getAnchor(); if ($parent->getField('PAG_TITLE') != '') echo ' title="'.encode($parent->getField('PAG_TITLE'),false).'"'?>><?php echo encode($parent->getField('PAG_TITRE_MENU')) ?></a> &gt;
            <?php } ?>
               <?php echo encode($rowListe['PAG_TITRE_MENU']) ?>
            </p>
            <?php if ($rowListe['PAG_ACCROCHE'] != '') { ?>
            <p class="description"><?php echo encode($rowListe['PAG_ACCROCHE'])?></p>
            <?php } ?>
        </div>
    </li>
<?php } ?>
</ul>
