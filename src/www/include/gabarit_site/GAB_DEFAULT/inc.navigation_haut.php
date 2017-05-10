<?php
$oPageAccueil = CMS::getCurrentSite()->getHomePage();
$oPage = CMS::getCurrentSite()->getCurrentPage();
?>
<div id="barreHaut">
    <div>
        <a href="#menu">Menu</a>
        <a href="#contenu">Contenu</a>
        <?php if ($oPageSpeciale = CMS::getCurrentSite()->getSpecialePage('PGS_RECHERCHE')) { ?>
        <a href="#champRecherche">Recherche</a>
        <?php } ?>
        <?php if ($oPageSpeciale = CMS::getCurrentSite()->getSpecialePage('PGS_ACCESSIBILITE')) { ?>
        <a class="accessibilite" <?php echo $oPageSpeciale->getAnchor(); if ($oPageSpeciale->getField('PAG_TITLE') != '') echo ' title="' . encode($oPageSpeciale->getField('PAG_TITLE'), false) . '"'; ?>><?php echo encode($oPageSpeciale->getField('PAG_TITRE_MENU'))?></a>
        <?php } ?>
    </div>
</div>
<header id="bandeauHaut">
    <?php if ($logo = CMS::getCurrentSite()->getLogoSRC()) { ?>
    <div class="logoTitre">
        <?php
        if (!$oPage->isHome()) { ?>
            <a <?php echo $oPageAccueil->getAnchor()?>><img alt="<?php echo encode(CMS::getCurrentSite()->getField('SIT_TITLE'), false)?>" src="<?php echo $logo?>"></a>
        <?php } else { ?>
            <img alt="<?php echo encode(CMS::getCurrentSite()->getField('SIT_TITLE'), false)?>" src="<?php echo $logo?>">
        <?php } ?>
    </div>
    <?php } ?>
    <div id="sousTitre" class="logoTitre">
        <strong>Site de d&eacute;mo</strong><br>
        CMS.Eolas - v<?php echo CMS::getVersion()?>
    </div>

    <?php if ($oPageSpeciale = CMS::getCurrentSite()->getSpecialePage('PGS_RECHERCHE')) {
            $oModule = new Module('MOD_RECHERCHE');
            ?>
    <form method="get" action="<?php echo $oPageSpeciale->getURLESCAPE()?>" id="champRecherche">
        <input type="search" id="searchString" name="searchString" value="<?php if (!empty($_GET['searchString'])) { echo secureInput($_GET['searchString']); } else { echo ''; } ?>" placeholder="<?php echo $oModule->i18n('rechercher') ?>">
        <input type="submit" id="searchSubmit" name="search" value="OK" title="lancer la recherche"> <input type="hidden" name="idtf" value="<?php echo $oPageSpeciale->getID()?>">
    </form>
    <?php } ?>
</header>
<nav id="menu">
    <?php
    CMS::addJS(SERVER_ROOT . 'include/gabarit_site/GAB_DEFAULT/menu.js');
    $oPageParentsID = $oPage->getParentsID();
    $aChildren = $oPageAccueil->getChildrenForMenu();
    if (sizeof($aChildren) > 0) { ?>
        <ul class="menuNiv1">
            <li class="nav1 accueil"><a <?php echo $oPageAccueil->getAnchor(); if ($oPageAccueil->getField('PAG_TITLE') != '') echo ' title="' . encode($oPageAccueil->getField('PAG_TITLE'), false) . '"'; ?>><span><?php echo encode($oPageAccueil->getField('PAG_TITRE_MENU')); ?></span></a></li>
            <?php foreach ($aChildren as $oPageChild) { ?>
                <li class="nav1<?php if ($oPageChild->getID() == $oPage->getID() || $oPageChild->getID() == $oPageParentsID[1]) echo ' selected'; ?>"><a <?php echo $oPageChild->getAnchor(); if ($oPageChild->getField('PAG_TITLE') != '') echo ' title="' . encode($oPageChild->getField('PAG_TITLE'), false) . '"'; ?>><span><?php echo encode($oPageChild->getField('PAG_TITRE_MENU')); ?></span></a>
                    <?php
                    $aChildrenBis = $oPageChild->getChildrenForMenu();
                    if (sizeof($aChildrenBis ) > 0) { ?>
                        <div class="sousMenu">
                            <div class="top">
                            <ul class="menuNiv2">
                                <?php
                                $i = 0;
                                foreach ($aChildrenBis as $oPageChildBis) { ?>
                                    <li<?php if ($i == 0) echo ' class="first"'; ?>><a <?php echo $oPageChildBis->getAnchor(); if ($oPageChildBis->getField('PAG_TITLE') != '') echo ' title="' . encode($oPageChildBis->getField('PAG_TITLE'), false) . '"'; ?>><?php echo encode($oPageChildBis->getField('PAG_TITRE_MENU')); ?></a></li>
                                    <?php
                                    $i++;
                                } ?>
                            </ul>
                            </div>
                            <div class="bas"></div>
                        </div>
                    <?php } ?>
                </li>
            <?php } ?>
        </ul>
    <?php } ?>
</nav>

<div id="arianeUserTools">
    <?php if (!CMS::getCurrentSite()->getCurrentPage()->isHome()) { ?>
    <div id="ariane">
        <div class="first">Vous &ecirc;tes ici : </div>
        <?php foreach ($oPage->getParents() as $oPageChemin) { ?>
        <div itemscope itemtype="http://data-vocabulary.org/Breadcrumb">
            <a <?php echo $oPageChemin->getAnchor(); if ($oPageChemin->getField('PAG_TITLE') != '') echo ' title="' . encode($oPageChemin->getField('PAG_TITLE'), false) . '"'; ?> itemprop="url"><span itemprop="title"><?php echo encode($oPageChemin->getField('PAG_TITRE_MENU'))?></span></a>
        </div>
        <?php } ?>
        <div itemscope itemtype="http://data-vocabulary.org/Breadcrumb">
            <span itemprop="title"><?php echo encode($oPage->getField('PAG_TITRE_MENU'))?></span>
        </div>
    </div>
    <?php }
    include(CMS::getCurrentSite()->getField('SIT_INCLUDE') . '/inc.userTools.php'); ?>
</div>
