<?php
include_once CLASS_DIR.'class.db_thematique.php';
$dbh   = DB::getInstance();
$oPage = CMS::getCurrentSite()->getCurrentPage();

$oThematique = new Thematique(Paragraphe::getCurrentTemplateRestriction());
if (!$oThematique->checkAuthorized(false) && !$oThematique->checkShareAuthorized(false)) {
    Paragraphe::noRender();

    return;
} ?>

<div class="tpl_thematique">
<?php foreach ($oThematique->getAffectes(false) as $libelle => $aAffectes) { ?>
    <div class="affectes">
        <h3><?php echo encode($libelle) ?></h3>
        <ul>
            <?php foreach ($aAffectes as $affecte) { ?>
            <li>
                <?php if ($affecte['TPL_CODE'] != '') { ?>
                <a <?php echo $oPage->getAnchor(array('TPL_CODE' => $affecte['TPL_CODE'], 'PAR_TPL_IDENTIFIANT' => $affecte['ID_LIAISON'])) ?> title="<?php echo encode($affecte['LIBELLE_AFFECTE'], false) ?>">
                <?php } elseif ($affecte['LIA_CODE'] == 'ON_PAGE') {
                    $oPageTmp = new Page($affecte['ID_LIAISON']); ?>
                <a <?php echo $oPageTmp->getAnchor() ?> title="<?php echo $affecte['LIBELLE_AFFECTE'] ?>">
                <?php } ?>

                <div class="description">
                    <?php echo encode($affecte['LIBELLE_AFFECTE']) ?>
                </div>

                <?php if ($affecte['TPL_CODE'] != '' || $affecte['LIA_CODE'] == 'ON_PAGE') { ?>
                </a>
                <?php } ?>
            </li>
            <?php } ?>
        </ul>
    </div>
<?php } ?>
</div>
