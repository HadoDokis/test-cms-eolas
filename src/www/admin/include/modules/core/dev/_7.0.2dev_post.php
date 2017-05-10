<?php

if ($registeredVersion && version_compare($registeredVersion, '7.0.2dev3', '<')) {
    //liste destinataires (email) dans formulaire dynamique
    $app->dbh->exec("INSERT INTO `DD_FORMULAIREQUESTIONTYPE` (`QTY_CODE`, `QTY_LIBELLE`, `QTY_GROUPE`) VALUES
        ('QTY_SELECTEMAIL', '@fr_FR:Liste destinataires (emails)@en_US:Recipient(email)@', 'Input');");
}

if ($registeredVersion && version_compare($registeredVersion, '7.0.2dev4', '<')) {
    $app->dbh->exec("UPDATE DD_EMAILTEMPLATE
        set EMT_SUJET=REPLACE(EMT_SUJET, '[SIT_LIBELLE]', '[SIT_TITLE]'),
        EMT_BODYHTML=REPLACE(EMT_BODYHTML, '[SIT_LIBELLE]', '[SIT_TITLE]')");
    $app->dbh->exec("DELETE FROM DD_EMAILTEMPLATEKEY WHERE EMK_LIBELLE='SIT_TITLE'");
}

if ($registeredVersion && version_compare($registeredVersion, '7.0.2dev8', '<')) {
    $app->dbh->exec("UPDATE DD_SITE
        set SIT_EXT_MUSIC=CONCAT(SIT_EXT_MUSIC, '.ogg\n')
        where SIT_EXT_MUSIC not like '%.ogg%'");
}

if ($registeredVersion && version_compare($registeredVersion, '7.0.2dev9', '<')) {
    $app->dbh->exec("UPDATE DD_TEMPLATE
        set TPL_PARAMETRAGE_CLES='@[LIBELLE]:Libellé du formulaire@'
        where TPL_CODE = 'TPL_FORMULAIRE'");
}

if ($registeredVersion && version_compare($registeredVersion, '7.0.2dev11', '<')) {
    $app->dbh->exec("delete FROM `FORMULAIREREPONSEDETAIL` WHERE `ID_FORMULAIREQUESTION` in
        (select`ID_FORMULAIREQUESTION` from FORMULAIREQUESTION where QTY_CODE in
        ('QTY_INFORMATION', 'QTY_CAPTCHAGRAPHIC', 'QTY_CAPTCHANUMERIC', 'QTY_CAPTCHANUMERICMINUS'))");
}
