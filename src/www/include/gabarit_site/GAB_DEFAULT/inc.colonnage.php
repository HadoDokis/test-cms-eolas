<?php
/**
 * Initialisation du colonnage du site
 */
if (CMS::$edition) {
    $afficheColonneGauche = false;
    $afficheColonneDroite = true;
} else {
    $afficheColonneGauche = false;
    $afficheColonneDroite = false;
    $nb = 0;
    //Navigation droite
    $_aParentsID = $oPage->getParentsID(true);
    if (is_numeric($_aParentsID[0])) {
        $oPageMenuNiv1 = new Page($_aParentsID[0], CMS::$mode);
        $aChildrenID = $oPageMenuNiv1->getChildrenForMenu();
        $nb += sizeof($aChildrenID);
    }
    //Modules droite
    $nb += sizeof($oPage->getParagraphes('PAR_RIGHT'));
    if ($nb > 0) {
        $afficheColonneDroite = true;
    }
}
CMS::getCurrentSite()->getCurrentPage()->setColumns($afficheColonneGauche, $afficheColonneDroite);
