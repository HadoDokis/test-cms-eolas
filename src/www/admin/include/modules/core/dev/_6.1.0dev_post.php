<?php
if ($registeredVersion && version_compare($registeredVersion, '6.1.0dev1', '<')) {
    $schema = dbSchema::init($app->dbh);
    if ($si->tableExists('HISTORIQUE') && $si->tableExists('DD_HISTORIQUETYPE')) {
        $schema->dropTable('HISTORIQUE');
        $schema->dropTable('DD_HISTORIQUETYPE');
    }
}

if ($registeredVersion && version_compare($registeredVersion, '6.1.0dev2', '<')) {
    $schema = dbSchema::init($app->dbh);
    if ($si->tableExists('DD_SITE') && $si->DD_SITE->fieldExists('SIT_GOOGLETAG')) {
        // recuperation de l'ancien champ vers le nouveau
        $sqlUpdate = 'UPDATE DD_SITE set SIT_GA_TAG = SIT_GOOGLETAG';
        $app->dbh->exec($sqlUpdate);
        // suppression de l'ancien champ
        $schema->dropField('DD_SITE', 'SIT_GOOGLETAG');
    }

    // insertion des données relaties aux medium Google Analytics
    $app->dbh->exec("INSERT INTO `STAT_GA_MEDIUM` (`GAM_CODE`, `GAM_LIBELLE`, `GAM_ORDRE`) VALUES
                    ('(none)', 'acces_direct', 1),
                    ('(not set)', 'autres', 2),
                    ('email', 'emailing', 3),
                    ('organic', 'moteur_recherche', 4),
                    ('referral', 'sites_affluents', 5),
                    ('cpc', 'lien_sponsorises', 6)");

}

if ($registeredVersion && version_compare($registeredVersion, '6.1.0dev4', '<')) {
    $schema = dbSchema::init($app->dbh);
    if ($si->tableExists('FORMULAIRE') && $si->FORMULAIRE->fieldExists('FRM_DISPLAY_LIBELLE')) {
        $schema->dropField('FORMULAIRE', 'FRM_DISPLAY_LIBELLE');
    }
}

if ($registeredVersion && version_compare($registeredVersion, '6.1.0dev8', '<')) {
    $app->dbh->exec("UPDATE DD_SITE set SIT_RSS_WEBMARKET=". $app->dbh->quote(SIT_RSS_WEBMARKET_DEFAULT) . " where SIT_RSS_WEBMARKET like '%webmarketing.eolas.fr%'");
}
