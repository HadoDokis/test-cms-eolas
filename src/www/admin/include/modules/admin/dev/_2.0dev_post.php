<?php
// #5656 - Suppression de la calsse de référence au sein de la signature des modules
if (version_compare($app->registeredMdl->moduleInfo($mdlId,'version'), '2.0dev2', '<')) {
    if ($si->tableExists('DD_MODULES') && $si->DD_MODULES->fieldExists('MOD_CLASSREF')) {
        $schema = dbSchema::init($app->dbh);
        $schema->dropField('DD_MODULES', 'MOD_CLASSREF');
    }
}
