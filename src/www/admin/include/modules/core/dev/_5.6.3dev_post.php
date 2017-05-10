<?php
if ($registeredVersion && version_compare($app->registeredMdl->moduleInfo($mdlId,'version'), '5.6.3dev1', '<')) {
    if ($si->DD_SITE->fieldExists('SIT_PICNIK')) {
        $schema = dbSchema::init($app->dbh);
        $schema->dropField('DD_SITE', 'SIT_PICNIK');
        // Suppression des éditions temporaires
        require_once CLASS_DIR . 'class.File_management.php';
        $aGlob = glob(UPLOAD_IMAGE_PHYSIQUE . '*/picnik*');
        foreach ($aGlob as $filename) {
            File_management::deleteFromName($filename);
        }
        $aGlob = glob(UPLOAD_IMAGE_PHYSIQUE . '*/THUMB/picnik*');
        foreach ($aGlob as $filename) {
            File_management::deleteFromName($filename);
        }
    }
}

if ($registeredVersion && version_compare($app->registeredMdl->moduleInfo($mdlId,'version'), '5.6.3dev3', '<')) {
    $app->log("\n== Ticket #8090 Personnalisation des URL avec des clés dynamiques ==\n");

    $sql = 'select *
            from DD_TEMPLATE
            where TPL_URLCODE is not null
            and TPL_URLCODE <> \'\'';

    foreach ($app->dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $row['TPL_URLCODE'] = filenameToRfc1738(trim($row['TPL_URLCODE']));
        $sql = 'update DD_TEMPLATE
                set TPL_URLCODE = ' . $app->dbh->quote($row['TPL_URLCODE']) . '
                where TPL_CODE = ' . $app->dbh->quote($row['TPL_CODE']);
        $app->dbh->exec($sql);
    }
}

if ($registeredVersion && version_compare($app->registeredMdl->moduleInfo($mdlId,'version'), '5.6.3dev5', '<')) {
    $app->log("\n== Ticket #8256 Pieces jointes au formulaires dynamiques ==\n");

    $app->dbh->exec("INSERT INTO `DD_TRADUCTION` (`TRA_CODE`, `MOD_CODE`, `TRA_DESCRIPTION`) VALUES
    ('for_texte_taille_max_upload', 'MOD_FORMULAIRE', 'Taille maximale de fichier'),
    ('for_fichier_trop_volumineux', 'MOD_FORMULAIRE', 'Erreur : fichier trop volumineux')");

    $app->dbh->exec("INSERT INTO `TRADUCTION_LANGUE` (`TRA_CODE`, `LNG_CODE`, `TRA_LIBELLE`) VALUES
    ('for_texte_taille_max_upload', 'fr_FR', '(Taille max. %s)'),
    ('for_texte_taille_max_upload', 'en_US', '(Max file size %s)'),
    ('for_fichier_trop_volumineux', 'fr_FR', 'Fichier trop volumineux'),
    ('for_fichier_trop_volumineux', 'en_US', 'File too big')");
}
if ($registeredVersion && version_compare($app->registeredMdl->moduleInfo($mdlId,'version'), '5.6.3dev6', '<')) {
    $app->log("\n== Ticket #8256 Pieces jointes au formulaires dynamiques (suite) ==\n");

    $app->dbh->exec("INSERT INTO `DD_TRADUCTION` (`TRA_CODE`, `MOD_CODE`, `TRA_DESCRIPTION`) VALUES
    ('for_total_fichier_trop_volumineux', 'MOD_FORMULAIRE', 'Taille maximale de document du formulaire dépassée')");

    $app->dbh->exec("INSERT INTO `TRADUCTION_LANGUE` (`TRA_CODE`, `LNG_CODE`, `TRA_LIBELLE`) VALUES
    ('for_total_fichier_trop_volumineux', 'fr_FR', 'Taille maximale des documents du formulaire dépassée'),
    ('for_total_fichier_trop_volumineux', 'en_US', 'Max form files size exceed.');");
}
