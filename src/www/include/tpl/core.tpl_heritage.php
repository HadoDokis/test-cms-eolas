<?php
$oParagraphe = new Paragraphe(Paragraphe::getCurrentTemplateRestriction(), CMS::$mode);
if ($oParagraphe->exist()) {
    $Paragraphe_class = 'Paragraphe' . substr($oParagraphe->getField('PRT_CODE'), 3);
    $oParagraphe = new $Paragraphe_class ($oParagraphe->getID(), CMS::$mode);
    echo $oParagraphe->display(false);
}
