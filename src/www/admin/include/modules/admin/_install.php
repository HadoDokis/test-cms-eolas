<?php
/**
 * Eléments permettant d'installer ou de mettre à jour le module "agenda"
 */

$app = application :: getInstance();
$mdlId = 'admin';
$availableVersion = $app->availableMdl->moduleInfo($mdlId,'version');
$registeredVersion = $app->registeredMdl->moduleInfo($mdlId,'version');

if (version_compare($registeredVersion,$availableVersion,'>=')) {
    return;
}

/**
 * Pré-traitements
 */
$app->log("\n== PRE-TRAITEMENTS DU MODULE \"".$mdlId."\" V" . $availableVersion . " ==\n");
require dirname(__FILE__).'/dev/_1.0dev_pre.php';

/**
 * Synchronisation de la structure
 */
$app->log("\n== SYNCHRONISATION DE LA BASE POUR LE MODULE \"".$mdlId."\" V" . $availableVersion . " ==\n");

$_s = new dbStruct($app->dbh);
require dirname(__FILE__).'/db-schema.php';
$si = new dbStruct($app->dbh);
$nb = $si->synchronize($_s);

/**
 * Post-traitements
 */
$app->log("\n== POST-TRAITEMENTS DU MODULE \"".$mdlId."\" V" . $availableVersion . " ==\n");
require dirname(__FILE__).'/dev/_2.0dev_post.php';

/**
 * Mise à jour du numéro de version
 */
$app->log("\n== MISE A JOUR DE LA SIGNATURE DU MODULE \"".$mdlId."\" POUR LA VERSION ".$availableVersion." ==\n");
$app->registeredMdl->registerModule($mdlId,$app->availableMdl->getModules($mdlId));

return true;
