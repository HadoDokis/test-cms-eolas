<?php
require '../../include/inc.bo_init.php';
CMS::checkAccess(new Module('MOD_CORE'), array('PRO_ROOT'));
require CLASS_DIR . 'class.db_emailTemplate.php';

if (isset($_POST['Update'])) {
    $oEmailTemplate = new EmailTemplate($_POST['idtf']);

    if ($_POST['EMT_EXPEDITEUR'] != 'FROM') {
        $_POST['EMT_EXPEDITEURFROM'] = '';
        $_POST['EMT_EXPEDITEURFROMNAME'] = '';
    } elseif ($_POST['EMT_EXPEDITEURFROM'] == '') {
        $_POST['EMT_EXPEDITEUR'] = 'CMS';
        $_POST['EMT_EXPEDITEURFROMNAME'] = '';
    }

    $stmt = $dbh->prepare('update DD_EMAILTEMPLATE set
        EMT_DESCRIPTION=:EMT_DESCRIPTION,
        EMT_EXPEDITEUR=:EMT_EXPEDITEUR,
        EMT_EXPEDITEURFROM=:EMT_EXPEDITEURFROM,
        EMT_EXPEDITEURFROMNAME=:EMT_EXPEDITEURFROMNAME,
        EMT_SUJET=:EMT_SUJET,
        EMT_BODYHTML=:EMT_BODYHTML
        WHERE EMT_CODE=:idtf');
    $stmt->bindValue(':EMT_DESCRIPTION', $_POST['EMT_DESCRIPTION'], PDO::PARAM_STR);
    $stmt->bindValue(':EMT_EXPEDITEUR', $_POST['EMT_EXPEDITEUR'], PDO::PARAM_STR);
    $stmt->bindValue(':EMT_EXPEDITEURFROM', $_POST['EMT_EXPEDITEURFROM'], PDO::PARAM_STR);
    $stmt->bindValue(':EMT_EXPEDITEURFROMNAME', $_POST['EMT_EXPEDITEURFROMNAME'], PDO::PARAM_STR);
    $stmt->bindValue(':EMT_SUJET', $_POST['EMT_SUJET'], PDO::PARAM_STR);
    $stmt->bindValue(':EMT_BODYHTML', $_POST['EMT_BODYHTML'], PDO::PARAM_STR);
    $stmt->bindValue(':idtf', $oEmailTemplate->getID(), PDO::PARAM_STR);
    $stmt->execute();

    setMSG(gettext('UPDATE_OK'));
    header('location:' . SERVER_ROOT . 'cms/administration/adm_emailTemplate.php?idtf='. $oEmailTemplate->getID());
    exit();
}
