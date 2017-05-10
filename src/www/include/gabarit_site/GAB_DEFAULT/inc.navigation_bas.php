<footer id="footer">
    <div>
        <img alt="<?php echo encode(CMS::getCurrentSite()->getField('SIT_TITLE'),false)?>" src="<?php echo CMS::getCurrentSite()->getField('SIT_IMAGE'); ?>logo_bas.png">

        <div class="bloc">
            <p>Outil de gestion de contenu simple et intuitif, open source, CMS.Eolas permet la conception et la mise à jour dynamique de sites intranet / extranet / internet</p>
            <p><a class="external" href="http://cms.eolas.fr">&gt; En savoir plus sur CMS.Eolas</a></p>
        </div>
    </div>
</footer>

<footer id="bandeauBas">
    <!--TODO utilisation ? <a href="<?php echo SERVER_ROOT?>rss_maj.php">RSS nouveauté</a>-->
    <?php $aPGS_CODE = array('PGS_PLANSITE', 'PGS_CONTACT', 'PGS_ACCESSIBILITE', 'PGS_CREDIT', 'PGS_MENTIONLEGALE');
    $aOPGS = array();
    foreach ($aPGS_CODE as $PGS_CODE) {
        if($oPGS = CMS::getCurrentSite()->getSpecialePage($PGS_CODE)) $aOPGS[] = $oPGS;
    }
    if (sizeof($aOPGS)) { ?>
        <ul class="lienPiedPage">
            <?php $oPageAccueil = CMS::getCurrentSite()->getHomePage(); ?>
            <li class="accueil"><a <?php echo $oPageAccueil->getAnchor(); if ($oPageAccueil->getField('PAG_TITLE') != '') echo ' title="' . encode($oPageAccueil->getField('PAG_TITLE'), false) . '"'; ?>><?php echo encode($oPageAccueil->getField('PAG_TITRE_MENU'))?></a></li>
            <?php foreach ($aOPGS as $oPageSpeciale) { ?>
                <li><a <?php echo $oPageSpeciale->getAnchor(); if ($oPageSpeciale->getField('PAG_TITLE') != '') echo ' title="' . encode($oPageSpeciale->getField('PAG_TITLE'), false) . '"'; ?>><?php echo encode($oPageSpeciale->getField('PAG_TITRE_MENU'))?></a></li>
            <?php } ?>
            <li>Réalisation : <a class="external" href="http://www.businessdecision-interactive.com"><img alt="Eolas, groupe Business &amp; Décision" src="<?php echo CMS::getCurrentSite()->getField('SIT_IMAGE'); ?>logo_eolas.png"></a></li>
        </ul>
    <?php } ?>
</footer>
