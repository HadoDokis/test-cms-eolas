<?php
require '../include/inc.bo_init.php';
Utilisateur::checkConnected();
require CLASS_DIR . 'class.db_page.php';

$oPage = new Page($_GET['idtf']);
$oPage->checkAuthorized();

//si aucun fils alors une seule possibilité donc redirection
if (sizeof($oPage->getChildren()) == 0) {
    header('Location:' . SERVER_ROOT . 'cms/cms_page.php?PAG_POIDS=1&PAG_IDPERE=' . $oPage->getID());
    exit ();
}
?>
<!DOCTYPE html>
<html>
<head>
    <?php include('../include/inc.bo_enTete.php') ?>
</head>
<body>
<div id="document">
    <?php $aMenuKey = array('CTN', 'PAGE', 'ADD'); include('../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">
            <h2>Nouvelle page</h2>
            <div class="creation ajout">
                <fieldset>
                    <legend><?php echo gettext('Selectionner la place du document')?></legend>
                    <?php foreach ($oPage->getChildren() as $oPageChild) {
                        $dernierPoids = $oPageChild->getField('PAG_POIDS');?>
                        <a href="cms_page.php?PAG_POIDS=<?php echo $dernierPoids?>&amp;PAG_IDPERE=<?php echo $oPage->getID()?>"><img src="<?php echo SERVER_ROOT?>images/choix.gif" alt=""><?php echo gettext('ici') ?></a>
                        <br>
                        <?php echo secureInput($oPageChild->getField('PAG_TITRE_MENU'));?>
                        <br>
                    <?php } ?>
                    <a href="cms_page.php?PAG_POIDS=<?php echo ++$dernierPoids?>&amp;PAG_IDPERE=<?php echo $oPage->getID()?>"><img src="<?php echo SERVER_ROOT?>images/choix.gif" alt=""><?php echo gettext('ici')?></a>
                </fieldset>
            </div>
        </div>
    </div>
    <?php include('../include/inc.bo_bandeau_bas.php')?>
</div>
</body>
</html>
