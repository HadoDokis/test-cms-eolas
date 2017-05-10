<?php
if ($registeredVersion && version_compare($registeredVersion, '7.0.0dev2', '<')) {
    $si = new dbStruct($app->dbh);
    $si->reverse();
    if ($si->tableExists('UTILISATEUR')
            && $si->UTILISATEUR->fieldExists('UTI_CONNEXION')
    ) {
        $sql = 'ALTER TABLE `UTILISATEUR` CHANGE `UTI_CONNEXION` `UTI_LASTCONNEXION` int(11) unsigned NULL';
        $app->dbh->exec($sql);
    }
}
if ($registeredVersion && version_compare($registeredVersion, '7.0.0dev11', '<')) {
    $si = new dbStruct($app->dbh);
    $si->reverse();
    if ($si->tableExists('UTILISATEUR')
        && $si->UTILISATEUR->fieldExists('UTI_STATUT_INFO')
    ) {
        $sql = 'ALTER TABLE `UTILISATEUR` CHANGE `UTI_STATUT_INFO` `UTI_AUTH_INFO` text  NULL';
        $app->dbh->exec($sql);
    }
}
