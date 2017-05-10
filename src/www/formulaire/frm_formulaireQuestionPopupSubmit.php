<?php
require '../include/inc.bo_init.php';
CMS::checkAccess(new Module('MOD_FORMULAIRE'), array(
    'PRO_FORMGEST'
));

require CLASS_DIR . 'class.db_formulaire.php';
require_once CLASS_DIR . 'class.db_page.php';

if (isset($_POST['Insert']) && is_numeric($_POST['ID_FORMULAIREGROUPE'])) {
    $stmt = $dbh->prepare("update FORMULAIREQUESTION set QST_POIDS = QST_POIDS + 1
        where ID_FORMULAIREGROUPE=:ID_FORMULAIREGROUPE and QST_POIDS >= :QST_POIDS");
    $stmt->bindValue(':ID_FORMULAIREGROUPE', $_POST['ID_FORMULAIREGROUPE'], PDO::PARAM_INT);
    $stmt->bindValue(':QST_POIDS', $_POST['QST_POIDS'], PDO::PARAM_INT);
    $stmt->execute();

    if ($_POST['QTY_CODE'] == 'QTY_INFORMATION') {
        $_POST['QST_LIBELLEVISIBLE'] = 0;
    }

    $stmt = $dbh->prepare("insert into FORMULAIREQUESTION (
        ID_FORMULAIREGROUPE,
        QTY_CODE,
        QST_LIBELLE,
        QST_LIBELLEVISIBLE,
        QST_ENTETE,
        QST_POIDS,
        QST_VISIBLE,
        QST_OBLIGATOIRE,
        QST_HEIGHT,
        QST_WIDTH,
        QST_MAXLENGTH,
        QST_VALEUR,
        QST_MULTIPLE,
        QST_COMMENTAIRE,
        QST_MESSAGEAIDE,
        QST_PLACEHOLDER
        ) values (
        :ID_FORMULAIREGROUPE,
        :QTY_CODE,
        :QST_LIBELLE,
        :QST_LIBELLEVISIBLE,
        :QST_ENTETE,
        :QST_POIDS,
        :QST_VISIBLE,
        :QST_OBLIGATOIRE,
        :QST_HEIGHT,
        :QST_WIDTH,
        :QST_MAXLENGTH,
        :QST_VALEUR,
        :QST_MULTIPLE,
        :QST_COMMENTAIRE,
        :QST_MESSAGEAIDE,
        :QST_PLACEHOLDER
        )");
    $stmt->bindValue(':ID_FORMULAIREGROUPE', $_POST['ID_FORMULAIREGROUPE'], PDO::PARAM_INT);
    $stmt->bindValue(':QTY_CODE', $_POST['QTY_CODE'], PDO::PARAM_STR);
    $stmt->bindValue(':QST_LIBELLE', $_POST['QST_LIBELLE'], PDO::PARAM_STR);
    $stmt->bindValue(':QST_LIBELLEVISIBLE', is_numeric($_POST['QST_LIBELLEVISIBLE']) ? $_POST['QST_LIBELLEVISIBLE'] : 1, PDO::PARAM_INT);
    $stmt->bindValue(':QST_ENTETE', $_POST['QST_ENTETE'], PDO::PARAM_INT);
    $stmt->bindValue(':QST_POIDS', $_POST['QST_POIDS'], PDO::PARAM_INT);
    $stmt->bindValue(':QST_VISIBLE', $_POST['QST_VISIBLE'], PDO::PARAM_INT);
    $stmt->bindValue(':QST_OBLIGATOIRE', $_POST['QST_OBLIGATOIRE'], PDO::PARAM_INT);
    $stmt->bindValue(':QST_HEIGHT', is_numeric($_POST['QST_HEIGHT']) ? $_POST['QST_HEIGHT'] : null, PDO::PARAM_INT);
    $stmt->bindValue(':QST_WIDTH', is_numeric($_POST['QST_WIDTH']) ? $_POST['QST_WIDTH'] : null, PDO::PARAM_INT);
    $stmt->bindValue(':QST_MAXLENGTH', is_numeric($_POST['QST_MAXLENGTH']) ? $_POST['QST_MAXLENGTH'] : null, PDO::PARAM_INT);
    $stmt->bindValue(':QST_VALEUR', $_POST['QST_VALEUR'], PDO::PARAM_STR);
    $stmt->bindValue(':QST_MULTIPLE', $_POST['QST_MULTIPLE'], PDO::PARAM_INT);
    $stmt->bindValue(':QST_COMMENTAIRE', $_POST['QST_COMMENTAIRE'], PDO::PARAM_STR);
    $stmt->bindValue(':QST_MESSAGEAIDE', $_POST['QST_MESSAGEAIDE'], PDO::PARAM_STR);
    $stmt->bindValue(':QST_PLACEHOLDER', $_POST['QST_PLACEHOLDER'], PDO::PARAM_STR);
    $stmt->execute();
    $idtf = $dbh->lastInsertId();

    // Purge du cache de l'ensemble du site
    Page::clearCache();

    $sql = "select ID_FORMULAIRE from FORMULAIREGROUPE where ID_FORMULAIREGROUPE=" . $_POST['ID_FORMULAIREGROUPE'];
    $ID_FORMULAIRE = $dbh->query($sql)->fetchColumn();
    $oFormulaire = new Formulaire($ID_FORMULAIRE);
    $oFormulaire->historize('CREATION', 'QUESTION', $idtf . ' - ' . $_POST['QST_LIBELLE']);
    ?>
<script>
        window.opener.location.href = 'frm_formulaire.php?idtf=<?php echo $ID_FORMULAIRE?>';
        window.close();
    </script>
<?php
} elseif (isset($_POST['Update'])) {
    // Cas ou l'utilisateur change une question de groupe
    // on recalcule les poids et on réordonnance le groupe d'origine
    $sql = "select * from FORMULAIREQUESTION where ID_FORMULAIREQUESTION=" . intval($_POST['idtf']);
    $row = $dbh->query($sql)->fetch(PDO::FETCH_ASSOC);
    if ($_POST['ID_FORMULAIREGROUPE'] != $row['ID_FORMULAIREGROUPE']) {
        $sql = "select count(ID_FORMULAIREQUESTION) from FORMULAIREQUESTION where ID_FORMULAIREGROUPE =" . $_POST['ID_FORMULAIREGROUPE'];
        $QST_POIDS = ($dbh->query($sql)->fetchColumn() + 1);

        // On met à jour le poids et le groupe pour réordonner le groupe d'origine
        $stmt = $dbh->prepare("update FORMULAIREQUESTION set QST_POIDS=:QST_POIDS, ID_FORMULAIREGROUPE=:ID_FORMULAIREGROUPE  where ID_FORMULAIREQUESTION=:ID_FORMULAIREQUESTION");
        $stmt->bindValue(':QST_POIDS', $QST_POIDS, PDO::PARAM_INT);
        $stmt->bindValue(':ID_FORMULAIREGROUPE', $_POST['ID_FORMULAIREGROUPE'], PDO::PARAM_INT);
        $stmt->bindValue(':ID_FORMULAIREQUESTION', $_POST['idtf'], PDO::PARAM_INT);
        $stmt->execute();

        _reorder($row['ID_FORMULAIREGROUPE']);
    }

    if ($_POST['QTY_CODE'] == 'QTY_INFORMATION') {
        $_POST['QST_LIBELLEVISIBLE'] = 0;
    }

    $stmt = $dbh->prepare("update FORMULAIREQUESTION set
        QTY_CODE=:QTY_CODE,
        QST_LIBELLE=:QST_LIBELLE,
        QST_LIBELLEVISIBLE=:QST_LIBELLEVISIBLE,
        QST_ENTETE=:QST_ENTETE,
        QST_VISIBLE=:QST_VISIBLE,
        QST_OBLIGATOIRE=:QST_OBLIGATOIRE,
        QST_HEIGHT=:QST_HEIGHT,
        QST_WIDTH=:QST_WIDTH,
        QST_MAXLENGTH=:QST_MAXLENGTH,
        QST_VALEUR=:QST_VALEUR,
        QST_MULTIPLE=:QST_MULTIPLE,
        QST_COMMENTAIRE=:QST_COMMENTAIRE,
        QST_MESSAGEAIDE=:QST_MESSAGEAIDE,
        QST_PLACEHOLDER=:QST_PLACEHOLDER
        where ID_FORMULAIREQUESTION=:idtf");
    $stmt->bindValue(':QTY_CODE', $_POST['QTY_CODE'], PDO::PARAM_STR);
    $stmt->bindValue(':QST_LIBELLE', $_POST['QST_LIBELLE'], PDO::PARAM_STR);
    $stmt->bindValue(':QST_LIBELLEVISIBLE', is_numeric($_POST['QST_LIBELLEVISIBLE']) ? $_POST['QST_LIBELLEVISIBLE'] : 1, PDO::PARAM_INT);
    $stmt->bindValue(':QST_ENTETE', $_POST['QST_ENTETE'], PDO::PARAM_INT);
    $stmt->bindValue(':QST_VISIBLE', $_POST['QST_VISIBLE'], PDO::PARAM_INT);
    $stmt->bindValue(':QST_OBLIGATOIRE', $_POST['QST_OBLIGATOIRE'], PDO::PARAM_INT);
    $stmt->bindValue(':QST_HEIGHT', is_numeric($_POST['QST_HEIGHT']) ? $_POST['QST_HEIGHT'] : null, PDO::PARAM_INT);
    $stmt->bindValue(':QST_WIDTH', is_numeric($_POST['QST_WIDTH']) ? $_POST['QST_WIDTH'] : null, PDO::PARAM_INT);
    $stmt->bindValue(':QST_MAXLENGTH', is_numeric($_POST['QST_MAXLENGTH']) ? $_POST['QST_MAXLENGTH'] : null, PDO::PARAM_INT);
    $stmt->bindValue(':QST_VALEUR', $_POST['QST_VALEUR'], PDO::PARAM_STR);
    $stmt->bindValue(':QST_MULTIPLE', $_POST['QST_MULTIPLE'], PDO::PARAM_INT);
    $stmt->bindValue(':QST_COMMENTAIRE', $_POST['QST_COMMENTAIRE'], PDO::PARAM_STR);
    $stmt->bindValue(':QST_MESSAGEAIDE', $_POST['QST_MESSAGEAIDE'], PDO::PARAM_STR);
    $stmt->bindValue(':QST_PLACEHOLDER', $_POST['QST_PLACEHOLDER'], PDO::PARAM_STR);
    $stmt->bindValue(':idtf', $_POST['idtf'], PDO::PARAM_INT);
    $stmt->execute();

    // Purge du cache de l'ensemble du site
    Page::clearCache();

    $sql = "select ID_FORMULAIRE from FORMULAIREGROUPE where ID_FORMULAIREGROUPE=" . intval($_POST['ID_FORMULAIREGROUPE']);
    $ID_FORMULAIRE = $dbh->query($sql)->fetchColumn();
    $oFormulaire = new Formulaire($ID_FORMULAIRE);
    $oFormulaire->historize('MODIFICATION', 'QUESTION', $_POST['idtf'] . ' - ' . $_POST['QST_LIBELLE']);
    ?>
<script>
        window.opener.location.href = 'frm_formulaire.php?idtf=<?php echo $ID_FORMULAIRE?>';
        window.close();
    </script>
<?php
} elseif (is_numeric($_GET['Down'])) {
    $stmt = $dbh->prepare("update FORMULAIREQUESTION set QST_POIDS = QST_POIDS - 1
        where QST_POIDS=:QST_POIDS + 1 and ID_FORMULAIREGROUPE=:ID_FORMULAIREGROUPE");
    $stmt->bindValue(':QST_POIDS', $_GET['QST_POIDS'], PDO::PARAM_INT);
    $stmt->bindValue(':ID_FORMULAIREGROUPE', $_GET['ID_FORMULAIREGROUPE'], PDO::PARAM_INT);
    $stmt->execute();

    $stmt = $dbh->prepare("update FORMULAIREQUESTION set QST_POIDS = QST_POIDS + 1
        where ID_FORMULAIREQUESTION=:ID_FORMULAIREQUESTION and ID_FORMULAIREGROUPE=:ID_FORMULAIREGROUPE");
    $stmt->bindValue(':ID_FORMULAIREQUESTION', $_GET['Down'], PDO::PARAM_INT);
    $stmt->bindValue(':ID_FORMULAIREGROUPE', $_GET['ID_FORMULAIREGROUPE'], PDO::PARAM_INT);
    $stmt->execute();

    _reorder($_GET['ID_FORMULAIREGROUPE']);

    // Purge du cache de l'ensemble du site
    Page::clearCache();

    header('Location:' . SERVER_ROOT . 'formulaire/frm_formulaire.php?idtf=' . $_GET['ID_FORMULAIRE']);
    exit();
} elseif (is_numeric($_GET['Up'])) {
    $stmt = $dbh->prepare("update FORMULAIREQUESTION set QST_POIDS = QST_POIDS + 1
        where QST_POIDS=:QST_POIDS - 1 and ID_FORMULAIREGROUPE=:ID_FORMULAIREGROUPE");
    $stmt->bindValue(':QST_POIDS', $_GET['QST_POIDS'], PDO::PARAM_INT);
    $stmt->bindValue(':ID_FORMULAIREGROUPE', $_GET['ID_FORMULAIREGROUPE'], PDO::PARAM_INT);
    $stmt->execute();

    $stmt = $dbh->prepare("update FORMULAIREQUESTION set QST_POIDS = QST_POIDS - 1
        where ID_FORMULAIREQUESTION=:ID_FORMULAIREQUESTION and ID_FORMULAIREGROUPE=:ID_FORMULAIREGROUPE");
    $stmt->bindValue(':ID_FORMULAIREQUESTION', $_GET['Up'], PDO::PARAM_INT);
    $stmt->bindValue(':ID_FORMULAIREGROUPE', $_GET['ID_FORMULAIREGROUPE'], PDO::PARAM_INT);
    $stmt->execute();

    _reorder($_GET['ID_FORMULAIREGROUPE']);

    // Purge du cache de l'ensemble du site
    Page::clearCache();

    header('location:' . SERVER_ROOT . 'formulaire/frm_formulaire.php?idtf=' . $_GET['ID_FORMULAIRE']);
    exit();
} elseif (is_numeric($_GET['Delete'])) {
    $sql = "select ID_FORMULAIREGROUPE from FORMULAIREQUESTION where ID_FORMULAIREQUESTION=" . intval($_GET['Delete']);
    $ID_FORMULAIREGROUPE = $dbh->query($sql)->fetchColumn();

    $sql = "delete from FORMULAIREREPONSEDETAIL where ID_FORMULAIREQUESTION=" . intval($_GET['Delete']);
    $dbh->exec($sql);

    $name = $dbh->query("select QST_LIBELLE from FORMULAIREQUESTION where ID_FORMULAIREQUESTION=" . intval($_GET['Delete']))->fetch(PDO::FETCH_COLUMN);
    $sql = "delete from FORMULAIREQUESTION where ID_FORMULAIREQUESTION=" . intval($_GET['Delete']);
    $dbh->exec($sql);

    _reorder($ID_FORMULAIREGROUPE);

    // Purge du cache de l'ensemble du site
    Page::clearCache();

    $sql = "select ID_FORMULAIRE from FORMULAIREGROUPE where ID_FORMULAIREGROUPE=" . $ID_FORMULAIREGROUPE;
    $ID_FORMULAIRE = $dbh->query($sql)->fetchColumn();
    $oFormulaire = new Formulaire($ID_FORMULAIRE);
    $oFormulaire->historize('SUPPRESSION', 'QUESTION', $_GET['Delete'] . ' - ' . $name);
    ?>
<script>
    window.opener.location.href = 'frm_formulaire.php?idtf=<?php echo $ID_FORMULAIRE?>';
    window.close();
</script>
<?php
}

/**
 * réordonnance les questions à l'intérieur d'un groupe de questions
 *
 * @param integer $ID_FORMULAIREGROUPE
 *            Identifiant du groupe à réordonner
 */
function _reorder($ID_FORMULAIREGROUPE)
{
    $dbh = DB::getInstance();
    $sql = "select ID_FORMULAIREQUESTION from FORMULAIREQUESTION where ID_FORMULAIREGROUPE=" . intval($ID_FORMULAIREGROUPE) . " order by QST_POIDS";
    $QST_POIDS = 1;
    $stmt = $dbh->prepare("update FORMULAIREQUESTION set QST_POIDS=:QST_POIDS where ID_FORMULAIREQUESTION=:ID_FORMULAIREQUESTION");
    foreach ($dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN) as $ID_FORMULAIREQUESTION) {
        $stmt->bindParam(':QST_POIDS', $QST_POIDS, PDO::PARAM_INT);
        $stmt->bindValue(':ID_FORMULAIREQUESTION', $ID_FORMULAIREQUESTION, PDO::PARAM_INT);
        $stmt->execute();
        $QST_POIDS ++;
    }
}
