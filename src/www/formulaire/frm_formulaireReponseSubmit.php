<?php
require '../include/inc.bo_init.php';

CMS::checkAccess(new Module('MOD_FORMULAIRE'), array ('PRO_FORMGEST', 'PRO_FORMLECT'));

require CLASS_DIR . 'class.db_formulaire.php';

if (isset ($_POST['Update'])) {
    $sql = "select ID_FORMULAIRE from FORMULAIREREPONSE where ID_FORMULAIREREPONSE=" . intval($_POST['idtf']);
    $ID_FORMULAIRE = $dbh->query($sql)->fetchColumn();
    $oFormulaire = new Formulaire($ID_FORMULAIRE);
    $oFormulaire->isAuthorized();

    $stmt = $dbh->prepare("update FORMULAIREREPONSE set
        REP_ETAT = :REP_ETAT,
        REP_COMMENTAIRE = :REP_COMMENTAIRE
        where ID_FORMULAIREREPONSE = :idtf");
    $stmt->bindValue(':REP_ETAT', $_POST['REP_ETAT'], PDO :: PARAM_STR);
    $stmt->bindValue(':REP_COMMENTAIRE', $_POST['REP_COMMENTAIRE'], PDO :: PARAM_STR);
    $stmt->bindValue(':idtf', $_POST['idtf'], PDO :: PARAM_INT);
    $stmt->execute();

    setMsg(gettext('UPDATE_OK'));
    header('Location:' . SERVER_ROOT . 'formulaire/frm_formulaireReponse.php?idtf=' . $_POST['idtf']);
    exit ();
} elseif (is_numeric($_GET['Delete'])) {
    $sql = "select ID_FORMULAIRE from FORMULAIREREPONSE where ID_FORMULAIREREPONSE=" . intval($_GET['Delete']);
    $ID_FORMULAIRE = $dbh->query($sql)->fetchColumn();
    $oFormulaire = new Formulaire($ID_FORMULAIRE);
    $oFormulaire->isAuthorized();

    $oFormulaire->deleteReponse($_GET['Delete']);
    header('Location:' . SERVER_ROOT . 'formulaire/frm_formulaire.php?showTab=fieldset_2&idtf=' . $ID_FORMULAIRE);
    exit ();
} elseif (isset ($_POST['massDelete'])) {
    $oFormulaire = new Formulaire($_POST['ID_FORMULAIRE']);
    $oFormulaire->isAuthorized();
    foreach ($_POST['del_Reponse'] as $idreponse) {
        $oFormulaire->deleteReponse($idreponse);
    }
    header('Location:' . SERVER_ROOT . 'formulaire/frm_formulaire.php?showTab=fieldset_2&idtf=' . $_POST['ID_FORMULAIRE']);
    exit ();

} elseif (!empty($_GET['DeleteAll'])) {
    $oFormulaire = new Formulaire($_GET['DeleteAll']);
    $oFormulaire->isAuthorized();
    $oFormulaire->deleteAllReponses();
    header('Location:' . SERVER_ROOT . 'formulaire/frm_formulaire.php?showTab=fieldset_2&idtf=' . $_GET['DeleteAll']);
    exit ();
}
