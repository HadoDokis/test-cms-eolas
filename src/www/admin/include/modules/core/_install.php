<?php
/**
 * Eléments permettant d'installer ou de mettre à jour le moduel "core"
 */
$app = application::getInstance();
$mdlId = 'core';
$availableVersion = $app->availableMdl->moduleInfo($mdlId, 'version');
$registeredVersion = $app->registeredMdl->moduleInfo($mdlId, 'version');

if (version_compare($registeredVersion, $availableVersion, '>=')) {
    return;
}

// Validation des données necessaire à l'installation
if (! $registeredVersion) {
    $SIT_CODE           = ! empty($_POST['SIT_CODE']) ? $_POST['SIT_CODE'] : null;
    $SIT_LIBELLE        = ! empty($_POST['SIT_LIBELLE']) ? $_POST['SIT_LIBELLE'] : null;
    $UTI_CIVILITE       = ! empty($_POST['UTI_CIVILITE']) ? $_POST['UTI_CIVILITE'] : null;
    $UTI_NOM            = ! empty($_POST['UTI_NOM']) ? $_POST['UTI_NOM'] : null;
    $UTI_PRENOM         = ! empty($_POST['UTI_PRENOM']) ? $_POST['UTI_PRENOM'] : null;
    $UTI_EMAIL          = ! empty($_POST['UTI_EMAIL']) ? $_POST['UTI_EMAIL'] : null;
    $UTI_EMAIL_CONFIRM  = ! empty($_POST['UTI_EMAIL_CONFIRM']) ? $_POST['UTI_EMAIL_CONFIRM'] : null;
    $UTI_LOGIN          = ! empty($_POST['UTI_LOGIN']) ? $_POST['UTI_LOGIN'] : null;
    // Validation des propriétés du site
    if (is_null($SIT_CODE)) {
        $msg .= 'Le code du site doit être renseigné et la version du module admin doit être supérieure ou égale à la version 4.2dev1.';
        throw new Exception($msg);
    } else if (strlen($SIT_CODE) > 27) {
        $msg .= 'Le code du site doit être inférieur à 27 caractères.';
        throw new Exception($msg);
    }
    if (is_null($SIT_LIBELLE)) {
        $msg .= 'Le libellé du site doit être renseigné.';
        throw new Exception($msg);
    }
    //*/
    if (is_null($UTI_CIVILITE) || is_null($UTI_NOM) || is_null($UTI_PRENOM) || is_null($UTI_EMAIL) || is_null($UTI_EMAIL_CONFIRM) || is_null($UTI_LOGIN)) {
        $msg = "Les informations nécessaires à la création du compte utilisateur ne sont pas toutes disponibles.";
        throw new Exception($msg);
        // Validation du mail
    } elseif (! filter_var($UTI_EMAIL, FILTER_VALIDATE_EMAIL)) {
        $msg .= "La valeur de l'email n'est pas valide.";
        throw new Exception($msg);
        // Validation mail email de confirmation
    } elseif ($UTI_EMAIL != $UTI_EMAIL_CONFIRM) {
        $msg .= "Les valeurs de l'email et de la confirmation d'email ne correspondent pas.";
        throw new Exception($msg);
    }
    // Si pas d'erreur, on ajoute le préfixe au SIT_CODE
    $SIT_CODE = 'SIT_' . $SIT_CODE;
}

/**
 * Pré-traitements
 */
$app->log("\n== PRE-TRAITEMENTS DU MODULE \"" . $mdlId . "\" " . ($registeredVersion ? "V" . $registeredVersion : "") . " ==\n");

require dirname(__FILE__) . '/dev/_6.0.0dev_pre.php';
require dirname(__FILE__) . '/dev/_7.0.0dev_pre.php';
if ((require dirname(__FILE__) . '/dev/_7.0.2dev_pre.php') === false) {
    return false;
}

/**
 * Synchronisation de la structure
 */
$app->log("\n== SYNCHRONISATION DE LA BASE POUR LE MODULE \"" . $mdlId . "\" V" . $availableVersion . " ==\n");

$_s = new dbStruct($app->dbh);
require dirname(__FILE__) . '/db-schema.php';
$si = new dbStruct($app->dbh);
$nb = $si->synchronize($_s);

/**
 * Post-traitements
 */
$app->log("\n== POST-TRAITEMENTS DU MODULE \"" . $mdlId . "\" V" . $availableVersion . " ==\n");

// Si pas de version enregistrée, on initialise les données
if (! $registeredVersion) {
    require dirname(__FILE__) . '/_core-data.php';
}

require dirname(__FILE__) . '/dev/_5.6.1dev_post.php';
require dirname(__FILE__) . '/dev/_5.6.2dev_post.php';
require dirname(__FILE__) . '/dev/_5.6.3dev_post.php';
require dirname(__FILE__) . '/dev/_6.0.0dev_post.php';
require dirname(__FILE__) . '/dev/_6.1.0dev_post.php';
require dirname(__FILE__) . '/dev/_6.1.1dev_post.php';
require dirname(__FILE__) . '/dev/_7.0.0dev_post.php';
require dirname(__FILE__) . '/dev/_7.0.1dev_post.php';
require dirname(__FILE__) . '/dev/_7.0.2dev_post.php';
require dirname(__FILE__) . '/dev/_7.0.3dev_post.php';

/**
 * Mise à jour du numéro de version
 */
$app->log("\n== MISE A JOUR DE LA SIGNATURE DU MODULE \"" . $mdlId . "\" POUR LA VERSION " . $availableVersion . " ==\n");
$app->registeredMdl->registerModule($mdlId, $app->availableMdl->getModules($mdlId));

return true;
