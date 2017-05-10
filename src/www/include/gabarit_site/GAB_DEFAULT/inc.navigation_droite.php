<aside id="colonneDroite">
<?php
if (!$oPage->isHome()) {
    $oPageParentsID = $oPage->getParentsID(true);
    if ($oPage->getLevel() == 1) {
        $oPageN1 = $oPage;
    } else {
        $oPageParents = $oPage->getParents();
        $oPageN1 = $oPageParents[1];
    }
    ?>
    <nav id="menuDroite">
        <p><a <?php echo $oPageN1->getAnchor(); if ($oPageN1->getField('PAG_TITLE') != '') echo ' title="' . encode($oPageN1->getField('PAG_TITLE'), false) . '"'; ?>><?php echo encode($oPageN1->getField('PAG_TITRE_MENU')); ?></a></p>
        <?php
        $aChildren = $oPageN1->getChildrenForMenu();
        if (sizeof($aChildren ) > 0) { ?>
        <ul>
            <?php $i = 0;
            foreach ($aChildren as $oPageChild) { ?>
                <li class="<?php if (in_array($oPageChild->getID(),  $oPageParentsID)) echo 'selected'; ?><?php if ($i == 0) echo ' first'; ?>"><a <?php echo $oPageChild->getAnchor(); if ($oPageChild->getField('PAG_TITLE') != '') echo ' title="' . encode($oPageChild->getField('PAG_TITLE'), false) . '"'; ?>><?php echo encode($oPageChild->getField('PAG_TITRE_MENU')); ?></a>
                    <?php
                    if (in_array($oPageChild->getID(),  $oPageParentsID)) {
                        $aChildrenBis = $oPageChild->getChildrenForMenu();
                        if (sizeof($aChildrenBis ) > 0) { ?>
                    <ul>
                        <?php foreach ($aChildrenBis as $oPageChildBis) { ?>
                            <li<?php if (in_array($oPageChildBis->getID(),  $oPageParentsID)) echo ' class="selected"'; ?>><a <?php echo $oPageChildBis->getAnchor(); if ($oPageChildBis->getField('PAG_TITLE') != '') echo ' title="' . encode($oPageChildBis->getField('PAG_TITLE'), false) . '"'; ?>><?php echo encode($oPageChildBis->getField('PAG_TITRE_MENU')); ?></a></li>
                        <?php } ?>
                    </ul>
                    <?php } } ?>
                </li>
                <?php
                $i++;
            } ?>
        </ul>
        <?php } ?>
    </nav>
<?php
} ?>

    <div id="colonneDroiteInner">
        <?php
        echo $oPage->getParagrapheButtons('PAR_RIGHT');
        foreach ($oPage->getParagraphes('PAR_RIGHT') as $oParagraphe) {
            echo $oParagraphe->display();
        } ?>
    </div>
</aside>
