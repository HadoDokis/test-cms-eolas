<div id="contenu">
    <div class="titrePage">
        <h1><?php echo encode($oPage->getField('PAG_TITRE'))?></h1>
    </div>
<?php
$modeExclusif = false;
if (!empty($_REQUEST['TPL_CODE'])) {
    $modeExclusif = true;
    foreach ($oPage->getParagraphes('PAR_CENTRAL') as $oParagraphe) {
        if ($oParagraphe->getField('TPL_CODE') == $_REQUEST['TPL_CODE']) {
            $oParagraphe->setTemplateRestriction($_REQUEST['PAR_TPL_IDENTIFIANT']);
            $modeExclusif = false;
            break;
        }
    }
}
if ($modeExclusif) {
    require_once CLASS_DIR . 'class.db_template.php';
    $oTemplate = new Template($_REQUEST['TPL_CODE']);
    if ($oTemplate->isEnabled(CMS::getCurrentSite())) {
        Paragraphe::setCurrentTemplateRestriction($_REQUEST['PAR_TPL_IDENTIFIANT']);
        Paragraphe::setCurrentTemplate($oTemplate);
        echo '<div class="paragraphe tpl ' . $_REQUEST['TPL_CODE'] . '"><div class="innerParagraphe">';
        $gabCodeSite = CMS::getCurrentSite()->getField('GAB_CODE');
        if (file_exists(PHYSICAL_PATH . '/include/tpl/' . $gabCodeSite . '/' . $oTemplate->getField('TPL_PAGE'))) {
            include (PHYSICAL_PATH . '/include/tpl/' . $gabCodeSite . '/' . $oTemplate->getField('TPL_PAGE'));
        } else {
            include (PHYSICAL_PATH . '/include/tpl/' . $oTemplate->getField('TPL_PAGE'));
        }
        echo '</div></div>';
    }
} else {

    if (CMS::getCurrentSite()->getField('SIT_PAGE_TXTACCROCHE') || CMS::getCurrentSite()->getField('SIT_PAGE_IMGACCROCHE')) {
        $oWeboAccroche = $oPage->getAccroche();
        if ($oPage->getField('PAG_ACCROCHE') != '' || $oWeboAccroche) {
                echo '<div class="paragraphe accrochePage"><div class="innerParagraphe">';
                if ($oWeboAccroche && CMS::getCurrentSite()->getField('SIT_PAGE_IMGACCROCHE')) {
                    echo '<img alt="'.encode($oWeboAccroche->getField('LIA_TEXT'), false).'" src="' . $oWeboAccroche->getSRC('IMF_MOYEN') . '">';
                }
                if ($oPage->getField('PAG_ACCROCHE') != '' && CMS::getCurrentSite()->getField('SIT_PAGE_TXTACCROCHE')) {
                    echo '<p>' . encode($oPage->getField('PAG_ACCROCHE')) . '</p>';
                }
                echo '</div></div>';
        }
    }

    echo $oPage->getParagrapheButtons('PAR_CENTRAL');
    foreach ($oPage->getParagraphes('PAR_CENTRAL') as $oParagraphe) {
        echo $oParagraphe->display();
    }

    //si le module commentaire est assigné
    if (CMS::getCurrentSite()->hasModule(new Module('MOD_COMMENTAIRE'))) {
        require_once CLASS_DIR . 'class.db_commentaire.php';
        if($oPage->showCommentaire()) echo Commentaire::doCommentairesPage($oPage->getID());
    }
}
?>
    <?php if ($oPage->getField('PAG_DATEMISEAJOUR')) { ?>
    <p class="alignright">
        <em>Dernière mise à jour le <?php echo date('d/m/Y', $oPage->getField('PAG_DATEMISEAJOUR'))?></em>
    </p>
    <?php } ?>
</div>
