<?php
require '../../include/inc.bo_init.php';
CMS::checkAccess(new Module('MOD_CORE'), array(
    'PRO_ROOT'
));
require_once CLASS_DIR . 'class.db_page.php';

if (! empty($_POST['Update'])) {
    if (! empty($_POST['TPL_URLCODE']) && (! testURLFormat($_POST['TPL_URLCODE']) || preg_match('#[/]#', $_POST['TPL_URLCODE']))) {
        setMsg(gettext('ERREUR_URLCODE_INVALIDE'), 'ERROR');
        header('Location:' . SERVER_ROOT . 'cms/administration/adm_template.php?idtf=' . $_POST['idtf']);
        exit();
    }

    if (! empty($_POST['TPL_REWRITEURL']) && preg_match('#[/]#', $_POST['TPL_REWRITEURL'])) {
        setMsg(gettext('ERREUR_REWRITEURL_INVALIDE'), 'ERROR');
        header('Location:' . SERVER_ROOT . 'cms/administration/adm_template.php?idtf=' . $_POST['idtf']);
        exit();
    }

    $i = 0;
    $paramStr = '@';
    while (isset($_POST['LIBELLE_PARAM_' . $i])) {
        if (preg_match('/[\@:]/', $_POST['VALEUR_PARAM_' . $i])) {
            header('Location:' . SERVER_ROOT . 'cms/administration/adm_template.php?caractereInterdit=VALEUR_PARAM_' . $i . '&idtf=' . $_POST['idtf']);
            exit();
        }
        $paramStr .= $_POST['LIBELLE_PARAM_' . $i] . ':' . $_POST['VALEUR_PARAM_' . $i] . '@';
        $i ++;
    }

    // On controle que le TPL_URLCODE est bien unique
    if (! empty($_POST['TPL_URLCODE'])) {
        $_POST['TPL_URLCODE'] = filenameToRfc1738(trim($_POST['TPL_URLCODE']));
        $sql = "select count(TPL_CODE) from DD_TEMPLATE
            where TPL_URLCODE=" . $dbh->quote($_POST['TPL_URLCODE']) . "
            and TPL_CODE<>" . $dbh->quote($_POST['idtf']);
        if ($dbh->query($sql)->fetchColumn() > 0) {
            setMsg(gettext('ERREUR_URLCODE_DOUBLE'), 'ERROR');
            header('Location:' . SERVER_ROOT . 'cms/administration/adm_template.php?idtf=' . $_POST['idtf']);
            exit();
        }
    }
    $stmt = $dbh->prepare("UPDATE DD_TEMPLATE set TPL_PARAMETRAGE=:TPL_PARAMETRAGE, TPL_URLCODE=:TPL_URLCODE, TPL_REWRITEURL=:TPL_REWRITEURL where TPL_CODE=:idtf");
    $stmt->bindValue(':TPL_PARAMETRAGE', $paramStr != '@' ? $paramStr : null, PDO::PARAM_STR);
    $stmt->bindValue(':TPL_URLCODE', $_POST['TPL_URLCODE'] != '' ? $_POST['TPL_URLCODE'] : null, PDO::PARAM_STR);
    $stmt->bindValue(':TPL_REWRITEURL', $_POST['TPL_REWRITEURL'], PDO::PARAM_STR);
    $stmt->bindValue(':idtf', $_POST['idtf'], PDO::PARAM_STR);
    $stmt->execute();

    // On met à parser les paragraphes contenant des liens vers ce template pour regénérer les liens
    $stmt = $dbh->prepare("update OFF_PARAGRAPHE set PAR_APARSER=1 where PAR_CONTENU like :PAR_CONTENU");
    $stmt->bindValue(':PAR_CONTENU', '%id="' . $_POST['idtf'] . '"%', PDO::PARAM_STR);
    $stmt->execute();

    $stmt = $dbh->prepare("update ON_PARAGRAPHE set PAR_APARSER=1 where PAR_CONTENU like :PAR_CONTENU");
    $stmt->bindValue(':PAR_CONTENU', '%id="' . $_POST['idtf'] . '"%', PDO::PARAM_STR);
    $stmt->execute();

    setMsg(gettext('UPDATE_OK'));
    // Purge du cache de l'ensemble des sites
    Page::clearAllCache();
    header('Location:' . SERVER_ROOT . 'cms/administration/adm_template.php?idtf=' . $_POST['idtf']);
    exit();
}
