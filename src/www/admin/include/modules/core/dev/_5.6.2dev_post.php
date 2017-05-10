<?php
if ($registeredVersion && version_compare($app->registeredMdl->moduleInfo($mdlId,'version'), '5.6.2dev3', '<')) {
    $app->dbh->exec("INSERT INTO `DD_TRADUCTION` (`TRA_CODE`, `MOD_CODE`, `TRA_DESCRIPTION`) VALUES
        ('login_mdp_oublie', 'MOD_EXTRANET', 'Lien vers la fonctionnalité \"Mot de passe oublié\"'),
        ('login_envoyer_nouveau_mdp', 'MOD_EXTRANET', 'Bouton d''envoi du nouveau mot de passe')");
    $app->dbh->exec("INSERT INTO `TRADUCTION_LANGUE` (`TRA_CODE`, `LNG_CODE`, `TRA_LIBELLE`) VALUES
        ('login_mdp_oublie', 'fr_FR', 'Mot de passe oublié?'),
        ('login_mdp_oublie', 'en_US', 'Forgot password?'),
        ('login_envoyer_nouveau_mdp', 'fr_FR', 'Envoyer'),
        ('login_envoyer_nouveau_mdp', 'en_US', 'Send')");

    $app->dbh->exec("
    UPDATE `TRADUCTION_LANGUE`
    SET
        `TRA_LIBELLE` = 'Aucun utilisateur ne correspond à cet email et à cet identifiant'
    WHERE CONVERT( `TRADUCTION_LANGUE`.`TRA_CODE` USING utf8 ) = 'login_changement_mdp'
    AND CONVERT( `TRADUCTION_LANGUE`.`LNG_CODE` USING utf8 ) = 'fr_FR';");

    $app->dbh->exec("
    UPDATE `TRADUCTION_LANGUE`
    SET
        `TRA_LIBELLE` = 'No user found for that email and login'
    WHERE CONVERT( `TRADUCTION_LANGUE`.`TRA_CODE` USING utf8 ) = 'login_changement_mdp'
    AND CONVERT( `TRADUCTION_LANGUE`.`LNG_CODE` USING utf8 ) = 'en_US';");
}
