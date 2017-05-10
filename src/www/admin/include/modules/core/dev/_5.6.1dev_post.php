<?php
// #6152 - Déclaration du profil PRO_WEBROOT pour le module MOD_WEBOTHEQUE_WIDGET
// #6155 - Nouvelle gestion des crédits/légende/loupe
if ($registeredVersion && version_compare($app->registeredMdl->moduleInfo($mdlId,'version'), '5.6.1', '<')) {
    $app->dbh->exec("INSERT INTO `MODULE_PROFIL` (`MOD_CODE`, `PRO_CODE`) VALUES
        ('MOD_WEBOTHEQUE_WIDGET', 'PRO_WEBROOT')");
    $app->dbh->exec("INSERT INTO `DD_TRADUCTION` (`TRA_CODE`, `MOD_CODE`, `TRA_DESCRIPTION`) VALUES
        ('web_voir_en_grand', 'MOD_WEBOTHEQUE_IMAGE', 'Texte d\'information sur l\'ouverture de l\'image en lightbox (loupe)')");
    $app->dbh->exec("INSERT INTO `TRADUCTION_LANGUE` (`TRA_CODE`, `LNG_CODE`, `TRA_LIBELLE`) VALUES
        ('web_voir_en_grand', 'fr_FR', 'Voir l\'image en grand'),
        ('web_voir_en_grand', 'en_US', 'Enlarge the picture')");
}
