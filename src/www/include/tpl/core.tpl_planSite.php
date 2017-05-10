 <?php
if (!function_exists('buildPlan')) {
    function buildPlan($oPage, $dernierNiveau = 3, $niveauCourant = 0)
    {
        $filtreEnfant = " and PAG_VISIBLE_MENU=1";
        if (sizeof(Page :: getForbiddenID(CMS :: $mode)) > 0) {
            $filtreEnfant .= " and ID_PAGE not in (" . implode(',', Page::getForbiddenID(CMS::$mode)) . ")";
        }
        $aChildren = $oPage->getChildren($filtreEnfant);
        if (sizeof($aChildren)>0 && $niveauCourant<$dernierNiveau) {
            $niveauCourant++;
            echo '<ul>';
            foreach ($aChildren as $oPageChild) {
                echo '<li><a ' . $oPageChild->getAnchor();
                if ($oPageChild->getField('PAG_TITLE') != '') {
                    echo ' title="' . encode($oPageChild->getField('PAG_TITLE'), false) . '"';
                }
                echo '>' . encode($oPageChild->getField('PAG_TITRE_MENU')) . '</a>';
                buildPlan($oPageChild, $dernierNiveau, $niveauCourant);
                echo '</li>';
            }
            echo '</ul>';
        }
    }
}
?>
<?php buildPlan(CMS::getCurrentSite()->getHomePage())?>
