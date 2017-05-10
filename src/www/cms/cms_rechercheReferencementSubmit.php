<?php
require '../include/inc.bo_init.php';
require_once CLASS_DIR . 'class.db_page.php';
CMS::checkAccess(new Module('MOD_REFERENCEMENT'), array(
    'PRO_REFERENCEMENT'
));
require CLASS_DIR . 'class.db_rechercheReferencement.php';
require CLASS_DIR . 'class.Link.php';
require CLASS_DIR . 'class.Editor.php';

if (isset($_POST['Insert'])) {
    $stmt = $dbh->prepare("insert into RECHERCHEREFERENCEMENT (
        REC_TITLE,
        REC_EXPRESSION,
        REC_RESUME,
        REC_GOOGLEPRIORITE,
        REC_GOOGLEFREQUENCE,
        REC_GOOGLELASTMOD,
        SIT_CODE
        ) values (
        :REC_TITLE,
        :REC_EXPRESSION,
        :REC_RESUME,
        :REC_GOOGLEPRIORITE,
        :REC_GOOGLEFREQUENCE,
        :REC_GOOGLELASTMOD,
        :SIT_CODE
        )");
    $stmt->bindValue(':REC_TITLE', $_POST['REC_TITLE'], PDO::PARAM_STR);
    $stmt->bindValue(':REC_EXPRESSION', $_POST['REC_EXPRESSION'], PDO::PARAM_STR);
    $stmt->bindValue(':REC_RESUME', $_POST['REC_RESUME'], PDO::PARAM_STR);
    $stmt->bindValue(':REC_GOOGLEPRIORITE', $_POST['REC_GOOGLEPRIORITE'], PDO::PARAM_STR);
    $stmt->bindValue(':REC_GOOGLEFREQUENCE', $_POST['REC_GOOGLEFREQUENCE'], PDO::PARAM_STR);
    $stmt->bindValue(':REC_GOOGLELASTMOD', unixtime($_POST['REC_GOOGLELASTMOD']), PDO::PARAM_INT);
    $stmt->bindValue(':SIT_CODE', CMS::getCurrentSite()->getID(), PDO::PARAM_STR);
    $stmt->execute();

    $oExterne = new RechercheReferencement($dbh->lastInsertID());
    Editor::updateContent($_POST['REC_DESCRIPTION'], 'RECHERCHEREFERENCEMENT', 'REC_DESCRIPTION', 'ID_RECHERCHEREFERENCEMENT', $oExterne->getID());
    setMsg(gettext('INSERT_OK'));

    header('Location:' . SERVER_ROOT . 'cms/cms_rechercheReferencement.php?idtf=' . $oExterne->getID());
    exit();
} elseif (isset($_POST['Update']) && is_numeric($_POST['idtf'])) {
    $oExterne = new RechercheReferencement($_POST['idtf']);
    $oExterne->checkAuthorized();
    $stmt = $dbh->prepare("update RECHERCHEREFERENCEMENT set
        REC_TITLE=:REC_TITLE,
        REC_EXPRESSION=:REC_EXPRESSION,
        REC_RESUME=:REC_RESUME,
        REC_GOOGLEPRIORITE=:REC_GOOGLEPRIORITE,
        REC_GOOGLEFREQUENCE=:REC_GOOGLEFREQUENCE,
        REC_GOOGLELASTMOD=:REC_GOOGLELASTMOD
        where ID_RECHERCHEREFERENCEMENT=:idtf");
    $stmt->bindValue(':REC_TITLE', $_POST['REC_TITLE'], PDO::PARAM_STR);
    $stmt->bindValue(':REC_EXPRESSION', $_POST['REC_EXPRESSION'], PDO::PARAM_STR);
    $stmt->bindValue(':REC_RESUME', $_POST['REC_RESUME'], PDO::PARAM_STR);
    $stmt->bindValue(':REC_GOOGLEPRIORITE', $_POST['REC_GOOGLEPRIORITE'], PDO::PARAM_STR);
    $stmt->bindValue(':REC_GOOGLEFREQUENCE', $_POST['REC_GOOGLEFREQUENCE'], PDO::PARAM_STR);
    $stmt->bindValue(':REC_GOOGLELASTMOD', unixtime($_POST['REC_GOOGLELASTMOD']), PDO::PARAM_INT);
    $stmt->bindValue(':idtf', $oExterne->getID(), PDO::PARAM_INT);
    $stmt->execute();

    Link::delete('RECHERCHEREFERENCEMENT', $oExterne->getID());
    Editor::updateContent($_POST['REC_DESCRIPTION'], 'RECHERCHEREFERENCEMENT', 'REC_DESCRIPTION', 'ID_RECHERCHEREFERENCEMENT', $oExterne->getID());

    setMsg(gettext('UPDATE_OK'));

    header('Location:' . SERVER_ROOT . 'cms/cms_rechercheReferencement.php?idtf=' . $oExterne->getID());
    exit();
} elseif (is_numeric($_GET['Delete'])) {
    $oExterne = new RechercheReferencement($_GET['Delete']);
    $oExterne->checkAuthorized();

    if ($oExterne->delete()) {
        setMsg(gettext('DELETE_OK'));
    }

    header('Location:' . SERVER_ROOT . 'cms/cms_rechercheReferencementListe.php');
    exit();
}
