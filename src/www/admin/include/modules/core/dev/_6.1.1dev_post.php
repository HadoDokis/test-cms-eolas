<?php
if ($registeredVersion && version_compare($registeredVersion, '6.1.1dev1', '<')) {
    if ($si->tableExists('HISTORIQUE_UTILISATEUR')
            && $si->HISTORIQUE_UTILISATEUR->fieldExists('ID_UTILISATEUR')
            && $si->HISTORIQUE_UTILISATEUR->referenceExists('HISTORIQUE_UTILISATEUR_ID_UTILISATEUR','ID_UTILISATEUR','UTILISATEUR','ID_UTILISATEUR')
    ) {
        $schema = dbSchema::init($app->dbh);
        $schema->dropReference('HISTORIQUE_UTILISATEUR', 'HISTORIQUE_UTILISATEUR_ID_UTILISATEUR');
    }
}

if ($registeredVersion && version_compare($registeredVersion, '6.1.1dev2', '<')) {
    if ($si->DD_SITE->fieldExists('SIT_GA_PASSWORD')) {
        $schema = dbSchema::init($app->dbh);
        $schema->dropField('DD_SITE', 'SIT_GA_PASSWORD');
    }
}
