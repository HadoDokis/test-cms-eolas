<?php
require '../include/inc.bo_init.php';

CMS::checkAccess(new Module('MOD_FORMULAIRE'), array('PRO_FORMGEST'));

require CLASS_DIR . 'class.db_formulaire.php';
require_once CLASS_DIR . 'class.db_page.php';

if (isset($_POST['Insert']) && is_numeric($_POST['ID_FORMULAIRE'])) {
    //decalage des groupes suivants
    $stmt = $dbh->prepare("update FORMULAIREGROUPE set FMG_POIDS=FMG_POIDS + 1 where ID_FORMULAIRE=:ID_FORMULAIRE and FMG_POIDS >= :FMG_POIDS");
    $stmt->bindValue(':ID_FORMULAIRE', $_POST['ID_FORMULAIRE'], PDO :: PARAM_INT);
    $stmt->bindValue(':FMG_POIDS', $_POST['FMG_POIDS'], PDO :: PARAM_INT);
    $stmt->execute();

    $stmt = $dbh->prepare("insert into FORMULAIREGROUPE (
        ID_FORMULAIRE,
        FMG_LIBELLE,
        FMG_POIDS,
        FMG_VISIBLE,
        FMG_LIBELLEVISIBLE
        ) values (
        :ID_FORMULAIRE,
        :FMG_LIBELLE,
        :FMG_POIDS,
        :FMG_VISIBLE,
        :FMG_LIBELLEVISIBLE
        )");
    $stmt->bindValue(':ID_FORMULAIRE', $_POST['ID_FORMULAIRE'], PDO :: PARAM_INT);
    $stmt->bindValue(':FMG_LIBELLE', $_POST['FMG_LIBELLE'], PDO :: PARAM_STR);
    $stmt->bindValue(':FMG_POIDS', $_POST['FMG_POIDS'], PDO :: PARAM_INT);
    $stmt->bindValue(':FMG_VISIBLE', $_POST['FMG_VISIBLE'], PDO :: PARAM_INT);
    $stmt->bindValue(':FMG_LIBELLEVISIBLE', $_POST['FMG_LIBELLEVISIBLE'], PDO :: PARAM_INT);
    $stmt->execute();
    $idtf = $dbh->lastInsertId();

    // Purge du cache de l'ensemble du site
    Page::clearCache();

    $oFormulaire = new Formulaire($_POST['ID_FORMULAIRE']);
    $oFormulaire->historize('CREATION', 'GROUPE', $idtf . ' - ' . $_POST['FMG_LIBELLE']);

} elseif (isset($_POST['Update']) && is_numeric($_POST['idtf'])) {

    $stmt = $dbh->prepare("update FORMULAIREGROUPE set
        FMG_LIBELLE = :FMG_LIBELLE,
        FMG_VISIBLE = :FMG_VISIBLE,
        FMG_LIBELLEVISIBLE= :FMG_LIBELLEVISIBLE
        where ID_FORMULAIREGROUPE=:ID_FORMULAIREGROUPE");

    $stmt->bindValue(':FMG_LIBELLE', $_POST['FMG_LIBELLE'], PDO :: PARAM_STR);
    $stmt->bindValue(':FMG_VISIBLE', $_POST['FMG_VISIBLE'], PDO :: PARAM_INT);
    $stmt->bindValue(':FMG_LIBELLEVISIBLE', $_POST['FMG_LIBELLEVISIBLE'], PDO :: PARAM_INT);
    $stmt->bindValue(':ID_FORMULAIREGROUPE', $_POST['idtf'], PDO :: PARAM_INT);
    $stmt->execute();

    // Purge du cache de l'ensemble du site
    Page::clearCache();

    $sql = "select ID_FORMULAIRE from FORMULAIREGROUPE where ID_FORMULAIREGROUPE = " . $_POST['idtf'];
    $ID_FORMULAIRE = $dbh->query($sql)->fetchColumn();
    $oFormulaire = new Formulaire($ID_FORMULAIRE);
    $oFormulaire->historize('MODIFICATION', 'GROUPE', $_POST['idtf'] . ' - ' . $_POST['FMG_LIBELLE']);

} elseif (is_numeric($_GET['Down'])) {
    $stmt = $dbh->prepare("update FORMULAIREGROUPE set FMG_POIDS = FMG_POIDS - 1 where FMG_POIDS = :FMG_POIDS + 1 and ID_FORMULAIRE=:ID_FORMULAIRE");
    $stmt->bindValue(':FMG_POIDS', $_GET['FMG_POIDS'], PDO :: PARAM_INT);
    $stmt->bindValue(':ID_FORMULAIRE', $_GET['ID_FORMULAIRE'], PDO :: PARAM_INT);
    $stmt->execute();

    $stmt = $dbh->prepare("update FORMULAIREGROUPE set FMG_POIDS = FMG_POIDS + 1 where ID_FORMULAIREGROUPE = :ID_FORMULAIREGROUPE and ID_FORMULAIRE=:ID_FORMULAIRE");
    $stmt->bindValue(':ID_FORMULAIREGROUPE', $_GET['Down'], PDO :: PARAM_INT);
    $stmt->bindValue(':ID_FORMULAIRE', $_GET['ID_FORMULAIRE'], PDO :: PARAM_INT);
    $stmt->execute();
    _reorder($_GET['ID_FORMULAIRE']);

    // Purge du cache de l'ensemble du site
    Page::clearCache();

    header('Location:' . SERVER_ROOT . 'formulaire/frm_formulaire.php?idtf='.$_GET['ID_FORMULAIRE']);
    exit();
} elseif (is_numeric($_GET['Up'])) {
    $stmt = $dbh->prepare("update FORMULAIREGROUPE set FMG_POIDS = FMG_POIDS + 1 where FMG_POIDS = :FMG_POIDS - 1 and ID_FORMULAIRE=:ID_FORMULAIRE");
    $stmt->bindValue(':FMG_POIDS', $_GET['FMG_POIDS'], PDO :: PARAM_INT);
    $stmt->bindValue(':ID_FORMULAIRE', $_GET['ID_FORMULAIRE'], PDO :: PARAM_INT);
    $stmt->execute();

    $stmt = $dbh->prepare("update FORMULAIREGROUPE set FMG_POIDS = FMG_POIDS - 1 where ID_FORMULAIREGROUPE = :ID_FORMULAIREGROUPE and ID_FORMULAIRE=:ID_FORMULAIRE");
    $stmt->bindValue(':ID_FORMULAIREGROUPE', $_GET['Up'], PDO :: PARAM_INT);
    $stmt->bindValue(':ID_FORMULAIRE', $_GET['ID_FORMULAIRE'], PDO :: PARAM_INT);
    $stmt->execute();
    _reorder($_GET['ID_FORMULAIRE']);

    // Purge du cache de l'ensemble du site
    Page::clearCache();

    header('Location:' . SERVER_ROOT . 'formulaire/frm_formulaire.php?idtf='.$_GET['ID_FORMULAIRE']);
    exit();
} elseif (is_numeric($_GET['Delete'])) {
    //On calcul si le groupe peut être supprimé
    $sql = "select count(ID_FORMULAIREQUESTION) from FORMULAIREQUESTION where ID_FORMULAIREGROUPE = ".intval($_GET['Delete']);
    $nbID_FORMULAIREQUESTION = $dbh->query($sql)->fetchColumn();
    if ($nbID_FORMULAIREQUESTION > 0) {
        setMsg(gettext('Suppression impossible'), 'ERROR');
        header('Location:' . SERVER_ROOT . 'formulaire/frm_formulaireGroupePopup.php?idtf='.$_GET['Delete']);
        exit();
    }

    $name = $dbh->query("select FMG_LIBELLE from FORMULAIREGROUPE where ID_FORMULAIREGROUPE = " . intval($_GET['Delete']))->fetch(PDO::FETCH_COLUMN);
    $oFormulaire = new Formulaire($_GET['ID_FORMULAIRE']);
    $oFormulaire->historize('SUPPRESSION', 'GROUPE', $_GET['Delete'] . ' - ' . $name);

    $sql = "delete from FORMULAIREGROUPE where ID_FORMULAIREGROUPE = " . intval($_GET['Delete']);
    $dbh->exec($sql);

    _reorder($_GET['ID_FORMULAIRE']);

    // Purge du cache de l'ensemble du site
    Page::clearCache();
}

/**
 * _reorder réordonnance les groupes de questions à l'intérieur d'un formulaire
 *
 * @param integer $ID_FORMULAIRE Identifiant du formulaire à réordonner
 */
function _reorder($ID_FORMULAIRE)
{
    $dbh = DB :: getInstance();
    $sql = "select ID_FORMULAIREGROUPE from FORMULAIREGROUPE where ID_FORMULAIRE= ".$ID_FORMULAIRE." order by FMG_POIDS";
    $aID_FORMULAIREGROUPE = $dbh->query($sql)->fetchAll(PDO :: FETCH_ASSOC);
    $FMG_POIDS = 1;
    foreach ($aID_FORMULAIREGROUPE as $row) {
        $stmt = $dbh->prepare("update FORMULAIREGROUPE set FMG_POIDS = :FMG_POIDS where ID_FORMULAIREGROUPE = :ID_FORMULAIREGROUPE");
        $stmt->bindValue(':FMG_POIDS', $FMG_POIDS++, PDO :: PARAM_INT);
        $stmt->bindValue(':ID_FORMULAIREGROUPE', $row['ID_FORMULAIREGROUPE'], PDO :: PARAM_INT);
        $stmt->execute();
    }
}
?>
<script>
    window.opener.location.href = 'frm_formulaire.php?idtf=<?php echo $_REQUEST['ID_FORMULAIRE']?>';
    window.close();
</script>
