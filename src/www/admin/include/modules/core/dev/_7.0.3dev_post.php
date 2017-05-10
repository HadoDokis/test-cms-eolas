<?php
if ($registeredVersion && version_compare($registeredVersion, '7.0.3dev2', '<')) {
    $app->dbh->exec("UPDATE `DD_MODULE` SET `MOD_LIBELLE` = '@fr_FR:Recherche DMK@' WHERE `DD_MODULE`.`MOD_CODE` = 'MOD_REFERENCEMENT'");
    $app->dbh->exec("UPDATE `DD_TEMPLATE` SET `TPL_LIBELLE` = '@fr_FR:Résultat de recherche@en_US:Search result@' WHERE `DD_TEMPLATE`.`TPL_CODE` = 'TPL_RECHERCHEREF'");
    $app->dbh->exec("INSERT INTO `DD_LIAISON` (`LIA_CODE`, `LIA_LIBELLE`, `LIA_NOM_CHAMP_ID`, `LIA_LIBELLE_CHAMP`, `LIA_NOM_CHAMP`, `LIA_NOM_FICHIER`) VALUES ('RECHERCHEREFERENCEMENT', '@fr_FR:Recherche DMK@', 'ID_RECHERCHEREFERENCEMENT', 'REC_TITLE', '@REC_DESCRIPTION@', '../cms/cms_rechercheReferencement.php')");
    $app->dbh->exec("INSERT INTO `DD_PROFIL` (`PRO_CODE`, `PRO_LIBELLE`, `PRO_PAGE`) VALUES ('PRO_REFERENCEMENT', '@fr_FR:Gestionnaire recherche DMK@en_US:DMK Search manager@', '0')");
    $app->dbh->exec("INSERT INTO `MODULE_PROFIL` (`MOD_CODE`, `PRO_CODE`) VALUES ('MOD_REFERENCEMENT', 'PRO_REFERENCEMENT')");
}
if ($registeredVersion && version_compare($registeredVersion, '7.0.3dev5', '<')) {
    $app->dbh->exec("INSERT INTO `DD_TEMPLATE` (`TPL_CODE`, `MOD_CODE`, `TPL_LIBELLE`, `TPL_PAGE`, `TPL_AFFECTABLE`, `TPL_LINKABLE`, `TPL_COLONNE`, `TPL_POPUP_RESTRICTION`, `TPL_PARAMETRAGE`, `TPL_PARAMETRAGE_CLES`, `TPL_URLCODE`, `PGS_CODE`, `TPL_REWRITEURL`, `TPL_REWRITEMETHOD`) VALUES
    ('TPL_LISTEPAGES', 'MOD_CORE', '@fr_FR:Accroche de pages@en_US:List of pages@', 'core.tpl_accrochePages.php', 1, 0, '@PAR_CENTRAL@', 'cms/cms_choisirLienInternePopup.php?IDENTIFIANT=PAR_TPL_IDENTIFIANT&CONCATENATION=1&NOCLOSE=1', NULL, NULL, NULL, NULL, NULL, NULL)");
}
