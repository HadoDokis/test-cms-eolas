<aside id="colonneGauche">
<?php
echo $oPage->getParagrapheButtons('PAR_LEFT');
foreach ($oPage->getParagraphes('PAR_LEFT') as $oParagraphe) {
    echo $oParagraphe->display();
} ?>
</aside>
