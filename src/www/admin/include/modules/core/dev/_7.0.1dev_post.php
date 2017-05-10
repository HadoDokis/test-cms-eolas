<?php
if ($registeredVersion && version_compare($registeredVersion, '7.0.1dev1', '<')) {
    $app->dbh->exec("DELETE FROM `TRADUCTION_SITE` WHERE `TRA_CODE` IN ('cor_fermer', 'cor_image_sur_total')");
    $app->dbh->exec("DELETE FROM `TRADUCTION_LANGUE` WHERE `TRA_CODE` IN ('cor_fermer', 'cor_image_sur_total')");
    $app->dbh->exec("DELETE FROM `DD_TRADUCTION` WHERE `TRA_CODE` IN ('cor_fermer', 'cor_image_sur_total')");
}
if ($registeredVersion && version_compare($registeredVersion, '7.0.1dev2', '<')) {
    //captcha dans formulaire dynamique
    $app->dbh->exec("INSERT INTO `DD_FORMULAIREQUESTIONTYPE` (`QTY_CODE`, `QTY_LIBELLE`, `QTY_GROUPE`) VALUES
        ('QTY_CAPTCHAGRAPHIC', '@fr_FR:Captcha graphique@en_US:Graphical captcha@', 'Captcha'),
        ('QTY_CAPTCHANUMERIC', '@fr_FR:Captcha numérique (addition)@en_US:Numerical captcha (add)@', 'Captcha'),
        ('QTY_CAPTCHANUMERICMINUS', '@fr_FR:Captcha numérique (soustraction)@en_US:Numerical captcha (sub)@', 'Captcha');");

    $app->dbh->exec("ALTER TABLE `FORMULAIRE` DROP `FRM_CAPTCHA`");

    $app->dbh->exec("DELETE FROM `TRADUCTION_LANGUE` where TRA_CODE in ('Captcha_obligatoire', 'Captcha_antispam', 'Captcha_invalide', 'Captcha_question', 'Preciser_champ', 'Email_invalide', 'Date_invalide')");
    $app->dbh->exec("DELETE FROM `TRADUCTION_SITE` where TRA_CODE in ('Captcha_obligatoire', 'Captcha_antispam', 'Captcha_invalide', 'Captcha_question', 'Preciser_champ', 'Email_invalide', 'Date_invalide')");
    $app->dbh->exec("DELETE FROM `DD_TRADUCTION` where TRA_CODE in ('Captcha_obligatoire', 'Captcha_antispam', 'Captcha_invalide', 'Captcha_question', 'Preciser_champ', 'Email_invalide', 'Date_invalide')");

    $app->dbh->exec("DELETE FROM `SITE_MODULE` where MOD_CODE='MOD_CAPTCHA'");
    $app->dbh->exec("DELETE FROM `DD_MODULE_GABARIT` where MOD_CODE='MOD_CAPTCHA'");

    $app->dbh->exec("DELETE FROM `DD_MODULE` where MOD_CODE='MOD_CAPTCHA'");

    $app->dbh->exec("INSERT INTO `DD_TRADUCTION` (`TRA_CODE`, `MOD_CODE`, `TRA_DESCRIPTION`, `TRA_MULTILIGNE`) VALUES
        ('cor_le_champ_X_n_est_pas_un_captcha_valide', 'MOD_CORE', 'Formulaire : Captcha non valide (utiliser %s)', 0)");

    $app->dbh->exec("INSERT INTO `TRADUCTION_LANGUE` (`TRA_CODE`, `LNG_CODE`, `TRA_LIBELLE`) VALUES
        ('cor_le_champ_X_n_est_pas_un_captcha_valide', 'fr_FR', 'Le champ %s n''est pas valide.')");
}
if ($registeredVersion && version_compare($registeredVersion, '7.0.1dev3', '<')) {
    if ($si->tableExists('LIAISON_PAGE')
            && $si->LIAISON_PAGE->fieldExists('ID_PARAGRAPHE')
            && $si->LIAISON_PAGE->referenceExists('LIAISON_PAGE_ID_PARAGRAPHE','ID_PARAGRAPHE','OFF_PARAGRAPHE','ID_PARAGRAPHE')
    ) {
        $schema = dbSchema::init($app->dbh);
        $schema->dropReference('LIAISON_PAGE', 'LIAISON_PAGE_ID_PARAGRAPHE');
    }
}
