<?php
if ($registeredVersion && version_compare($registeredVersion, '6.0.0dev23', '<')) {
    $app->log("\n== Ticket ##9896 Différence entre la DB et les fichiers dbStruct lors de l'installation [Maj bdd] ==\n");
    $si = new dbStruct($app->dbh);
    $si->reverse();
    if ($si->tableExists('DD_PAGESTYLE') ) {
        $schema = dbSchema::init($app->dbh);
        $refname = $si->DD_PAGESTYLE->referenceExists('DD_PAGESTYLE_ibfk_1',array(0 => 'GBS_CODE'),'DD_GABARITSTYLE',array(0 => 'GBS_CODE'));
        //on verifie qu'il s'agit bien du bon reference
        if ($refname == 'DD_PAGESTYLE_ibfk_1') {
            $schema->dropReference('DD_PAGESTYLE', 'DD_PAGESTYLE_ibfk_1');
        }
        $indexname = $si->DD_PAGESTYLE->indexExists('GBS_CODE','btree',array(0 => 'GBS_CODE'));
        //on verifie qu'il s'agit bien du bon index
        if ($indexname == 'GBS_CODE') {
            $schema->dropIndex('DD_PAGESTYLE', 'GBS_CODE');
        }
    }
}
