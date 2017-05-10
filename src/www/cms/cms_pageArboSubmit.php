<?php
require '../include/inc.bo_init.php';
Utilisateur::checkConnected();
require CLASS_DIR . 'class.db_page.php';
require CLASS_DIR . 'class.db_paragraphe.php';

//les identifiants sont préfixés d'un caractère
$oPage = new Page(substr($_GET['idtf'], 1));
$oPage->checkAuthorized();

$oPageParent = new Page(substr($_GET['idtfParent'], 1));
$oPageParent->checkAuthorized();

$aID = array();
foreach ($_GET['children'] as $idtf) {
    $aID[] = substr($idtf, 1);
}

if (!$oPage->insertInto($oPageParent, $aID)) {
    echo "KO";
    exit();
}
$oPage->historize('MODIFICATION', 'PAGE', 'Déplacement de la page DnD');
echo 'OK';
