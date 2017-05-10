<?php
$dbh = DB::getInstance();
$sql = 'update ON_PARAGRAPHE set PAR_APARSER=1 where ID_PARAGRAPHE=' . Paragraphe::getCurrentTemplateRestriction();
$dbh->exec($sql);
$oParagraphe = new Paragraphe(Paragraphe::getCurrentTemplateRestriction(), 'ON_');
if ($oParagraphe->exist() &&
(
    $oParagraphe->getPage()->getField('SIT_CODE') == CMS::getCurrentSite()->getID() ||
    $oParagraphe->getPage()->checkShareAuthorized(false)
)) {
    $Paragraphe_class = 'Paragraphe' . substr($oParagraphe->getField('PRT_CODE'), 3);
    $oParagraphe = new $Paragraphe_class ($oParagraphe->getID(), 'ON_');
    echo $oParagraphe->display(false);
    $dbh->exec($sql);
} else {
    Paragraphe::noRender();

    return;
}
