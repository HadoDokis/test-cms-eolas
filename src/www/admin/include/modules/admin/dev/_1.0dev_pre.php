<?php
// #3486 : Harmonisation des champs : si pas possible de retourner la version (changement de la structure entre "1.0dev2" et "1.0dev3"
if (!$registeredVersion) {
    // Prise en charge de la mise à jour depuis dev1
    $si = new dbStruct($app->dbh);
    $si->reverse();
    if ($si->tableExists('DDD_MODULES')) {
        $sql = "RENAME TABLE DDD_MODULES TO DD_MODULES";
        $app->dbh->exec($sql);
    }
    // Si la table existe et qu'elle possède l'ancienne structure
    $si = new dbStruct($app->dbh);
    $si->reverse();
    if ($si->tableExists('DD_MODULES') && $si->DD_MODULES->fieldExists('MODULE')) {
        $sql = 'ALTER TABLE `DD_MODULES` CHANGE `MODULE` `MOD_CODE` VARCHAR( 64 ) NOT NULL ,
            CHANGE `VERSION` `MOD_VERSION` VARCHAR( 32 ) NOT NULL ,
            CHANGE `SIGNATURE` `MOD_SIGNATURE` TEXT NOT NULL ,
            CHANGE `DATETIME` `MOD_DATETIME` DATETIME NOT NULL';
        $app->dbh->exec($sql);
        // Et on finis par recharger les modules
        $app->registeredMdl->modules = array();
        $app->registeredMdl->loadModules();
        $registeredVersion = $app->registeredMdl->moduleInfo($mdlId,'version');
    }
}
