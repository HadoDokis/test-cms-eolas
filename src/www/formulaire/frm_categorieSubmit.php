<?php
require '../include/inc.bo_init.php';
CMS::checkAccess(new Module('MOD_FORMULAIRE'), array ('PRO_FORMGEST'));
require CLASS_DIR . 'class.db_formulaireCategorie.php';

if (isset ($_POST['Insert'])) {
    if (is_numeric($_POST['CAT_IDPARENT'])) {
        $oFormulaireCategorieParent = new FormulaireCategorie($_POST['CAT_IDPARENT']);
        $oFormulaireCategorieParent->checkAuthorized();
    }
    $stmt = $dbh->prepare("insert into FORMULAIRECATEGORIE (CAT_IDPARENT, SIT_CODE, CAT_LIBELLE) values
    (:CAT_IDPARENT, :SIT_CODE, :CAT_LIBELLE);");
    $stmt->bindValue(':CAT_LIBELLE', $_POST['CAT_LIBELLE'], PDO :: PARAM_STR);
    $stmt->bindValue(':CAT_IDPARENT', (is_numeric($_POST['CAT_IDPARENT'])) ? $_POST['CAT_IDPARENT'] : null, PDO :: PARAM_INT);
    $stmt->bindValue(':SIT_CODE', CMS::getCurrentSite()->getID(), PDO :: PARAM_STR);
    $stmt->execute();
    $idtf = $dbh->lastInsertID();

    setMsg(gettext('INSERT_OK'));
    header('Location:' . SERVER_ROOT . 'formulaire/frm_categorie.php?idtf=' . $idtf);
    exit ();

} elseif (isset ($_POST['Update'])) {
    $oFormulaireCategorie = new FormulaireCategorie($_POST['idtf']);
    $oFormulaireCategorie->checkAuthorized();
    $stmt = $dbh->prepare("update FORMULAIRECATEGORIE set
        CAT_LIBELLE=:CAT_LIBELLE,
        CAT_IDPARENT=:CAT_IDPARENT
        where ID_FORMULAIRECATEGORIE=:idtf");
    $stmt->bindValue(':CAT_LIBELLE', $_POST['CAT_LIBELLE'], PDO :: PARAM_STR);
    $stmt->bindValue(':CAT_IDPARENT', (is_numeric($_POST['CAT_IDPARENT'])) ? $_POST['CAT_IDPARENT'] : null, PDO :: PARAM_INT);
    $stmt->bindValue(':idtf', $_POST['idtf'], PDO :: PARAM_INT);
    $stmt->execute();

    setMsg(gettext('UPDATE_OK'));
    header('Location:' . SERVER_ROOT . 'formulaire/frm_categorie.php?idtf=' . $_POST['idtf']);
    exit ();

} elseif (isset($_GET['Delete'])) {
    $oFormulaireCategorie = new FormulaireCategorie($_GET['Delete']);
    $oFormulaireCategorie->checkAuthorized();
    if ($oFormulaireCategorie->delete()) {
        setMsg(gettext('DELETE_OK'));
    }
    header('Location:' . SERVER_ROOT . 'formulaire/frm_categorieListe.php');
    exit ();
}
