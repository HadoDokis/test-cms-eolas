<?php
include_once CLASS_DIR.'class.db_thematique.php';

$dbh   = DB::getInstance();
$oPage = CMS::getCurrentSite()->getCurrentPage();

$a_oThematiques = Thematique::getListeThematiques();
?>
<div class="tpl_thematiqueListe">
    <?php if (count($a_oThematiques) > 0) { ?>
    <ul class="liste">
        <?php foreach ($a_oThematiques as $oThematique) { ?>
        <li class="item">
            <div class="itemInfo">
                <h3>
                    <a <?php echo $oPage->getAnchor(array('TPL_CODE' => 'TPL_THEMATIQUE', 'PAR_TPL_IDENTIFIANT' => $oThematique->getID())) ?>>
                        <?php echo encode($oThematique->getField('THE_LIBELLE')) ?>
                    </a>
                </h3>
            </div>
        </li>
        <?php } ?>
    </ul>
    <?php } ?>
</div>
