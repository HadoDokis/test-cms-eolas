<?php
if ($registeredVersion && version_compare($registeredVersion, '6.0.0dev4', '<')) {
    $schema = dbSchema::init($app->dbh);
    if ($si->tableExists('FORMULAIRECAPTCHA') && $si->tableExists('CAPTCHA')) {
        $sql = "INSERT INTO CAPTCHA (ID_CAPTCHA, CAP_DATE, CAP_REPONSE) SELECT ID_FORMULAIRECAPTCHA, CAP_DATE, CAP_REPONSE from FORMULAIRECAPTCHA";
        $app->dbh->exec($sql);
        $schema->dropTable('FORMULAIRECAPTCHA');
    }

    /** module core commentaire **/
    /* voir wiki pour infos et modifs */
    $app->log("\n== Ticket #8799 : Mise en place des commentaires sur pages et modules ==\n");

    $app->dbh->exec("INSERT INTO `DD_MODULE` (`MOD_CODE`, `MOD_LIBELLE`, `MOD_GROUPE`, `MOD_OBLIGATOIRE`, `MOD_POIDS`) VALUES
    ('MOD_COMMENTAIRE', '@fr_FR:Commentaires@', '@fr_FR:Gestion de contenu@', 0, 18);");

    $app->dbh->exec("INSERT INTO `DD_PROFIL` (`PRO_CODE`, `PRO_LIBELLE`, `PRO_PAGE`) VALUES
    ('PRO_COMMENTAIRE', '@fr_FR:Modérateur Pages@en_US:Moderator for page(Comments)@', 0);");

    $app->dbh->exec("INSERT INTO `MODULE_PROFIL` (`MOD_CODE`, `PRO_CODE`) VALUES
    ('MOD_COMMENTAIRE', 'PRO_COMMENTAIRE')");

    $app->dbh->exec("INSERT INTO `DD_PAGESPECIALE` (`PGS_CODE`, `MOD_CODE`, `PGS_LIBELLE`) VALUES
    ('PGS_FORMULAIREABUS', 'MOD_COMMENTAIRE', '@fr_FR:Page Formulaire abus@en_US:Report abuse form Page@');");

    $app->dbh->exec("INSERT INTO `DD_LIAISON` (`LIA_CODE`, `LIA_LIBELLE`, `LIA_NOM_CHAMP_ID`, `LIA_LIBELLE_CHAMP`,`LIA_NOM_CHAMP`) VALUES
    ('COMMENTAIRE', '@fr_FR:Commentaires@', 'ID_COMMENTAIRE', '','');");

    $app->dbh->exec("INSERT INTO `DD_LIAISON` (`LIA_CODE`, `LIA_LIBELLE`, `LIA_NOM_CHAMP_ID`, `LIA_LIBELLE_CHAMP`,`LIA_NOM_CHAMP`) VALUES
    ('COMMENTAIRE_PARAM', '@fr_FR:Commentaires Paramétrage@', 'ID_COMMENTAIRE_PARAM', '','@CPA_MES_DEPOT@CPA_SIGNATUREMAIL@');");

    $app->dbh->exec("INSERT INTO `DD_COMMENTAIRE_LIAISONTYPE` (`MOD_CODE`, `PRO_CODE`, `CLI_CLASSNOM`, `CLI_CLASSFILE`, `CLI_CHEMINFICHE`) VALUES
    ('MOD_CORE', 'PRO_COMMENTAIRE', 'Page', 'class.db_page.php', '/cms/cms_pseudo.php?idtf=');");

    $app->dbh->exec("INSERT INTO `DD_TEMPLATE` (`TPL_CODE`, `MOD_CODE`, `TPL_LIBELLE`, `TPL_PAGE`, `TPL_AFFECTABLE`, `TPL_LINKABLE`, `TPL_COLONNE`, `TPL_POPUP_RESTRICTION`, `TPL_PARAMETRAGE`, `TPL_PARAMETRAGE_CLES`, `TPL_URLCODE`) VALUES
    ('TPL_COMMENTAIREFABUS', 'MOD_COMMENTAIRE', '@fr_FR:Formulaire Abus@en_US:Report Abuse Form@', 'core.tpl_commentaireFormulaireAbus.php', 0, 0, '@PAR_CENTRAL@', '', NULL, NULL, NULL);");

    $app->dbh->exec("INSERT INTO `DD_TRADUCTION` (`TRA_CODE`, `MOD_CODE`, `TRA_DESCRIPTION`) VALUES
    ('com_je_reagis', 'MOD_COMMENTAIRE', 'Libellé du lien pour afficher le formulaire de depôt de commentaire'),
    ('com_commentaire', 'MOD_COMMENTAIRE', 'Libellé du lien (1 xxxxx) pour afficher la liste des commentaires si un seul résultat'),
    ('com_commentaires', 'MOD_COMMENTAIRE', 'Libellé du lien (X xxxxx) pour afficher la liste des commentaires si plus d''un résultat'),
    ('com_commentaires_voir', 'MOD_COMMENTAIRE', 'Libellé lors du hover sur le lien pour afficher la liste des commentaires'),
    ('com_monpseudo', 'MOD_COMMENTAIRE', 'Libellé du champs <Mon pseudo>'),
    ('com_monemail', 'MOD_COMMENTAIRE', 'Libellé du champs <Mon e-mail>'),
    ('com_moncommentaire', 'MOD_COMMENTAIRE', 'Libellé du champs <Mon Commentaire>'),
    ('com_message_abus', 'MOD_COMMENTAIRE', 'Libellé pour signaler un abus'),
    ('com_message_abus_recu', 'MOD_COMMENTAIRE', 'Message affiché après l''envoi du formulaire Abus'),
    ('com_btn_envoyer', 'MOD_COMMENTAIRE', 'Bouton de soumission du formulaire'),
    ('com_retour_page', 'MOD_COMMENTAIRE', 'Libellé du lien de retour vers la page des commentaires'),
    ('com_moncommentaire_info', 'MOD_COMMENTAIRE', 'Message info pour le champs <Mon Commentaire>'),
    ('com_moncommentaire_nombre', 'MOD_COMMENTAIRE', 'Label pour le nombre de caractères du champs <Mon Commentaire>'),
    ('com_commentaire_abus_titre', 'MOD_COMMENTAIRE', 'Titre du formulaire pour la saisie d''une alerte pour un abus'),
    ('com_commentaire_par', 'MOD_COMMENTAIRE', ''),
    ('com_commentaire_le', 'MOD_COMMENTAIRE', '');");

    $app->dbh->exec("INSERT INTO `TRADUCTION_LANGUE` (`TRA_CODE`, `LNG_CODE`, `TRA_LIBELLE`) VALUES
    ('com_je_reagis', 'fr_FR', 'Je réagis'),
    ('com_je_reagis', 'en_US', 'Comment'),
    ('com_commentaire', 'fr_FR', 'commentaire'),
    ('com_commentaire', 'en_US', 'comment'),
    ('com_commentaires', 'fr_FR', 'commentaires'),
    ('com_commentaires', 'en_US', 'comments'),
    ('com_commentaires_voir', 'fr_FR', 'Afficher les commentaires'),
    ('com_commentaires_voir', 'en_US', 'View the comments'),
    ('com_monpseudo', 'fr_FR', 'Mon pseudo'),
    ('com_monpseudo', 'en_US', 'My alias'),
    ('com_monemail', 'fr_FR', 'Mon e-mail'),
    ('com_monemail', 'en_US', 'My email'),
    ('com_moncommentaire', 'fr_FR', 'Mon commentaire'),
    ('com_moncommentaire', 'en_US', 'My comment'),
    ('com_message_abus', 'fr_FR', 'Signaler un abus'),
    ('com_message_abus', 'en_US', 'Report this comment'),
    ('com_message_abus_recu', 'fr_FR', 'Nous vous remercions de nous avoir alerté et tâcherons de modérer ce commentaire dans les plus brefs délais.'),
    ('com_message_abus_recu', 'en_US', 'Thank you for your alert. We shall strive to moderate this comment as soon as possible.'),
    ('com_btn_envoyer', 'fr_FR', 'Envoyer'),
    ('com_btn_envoyer', 'en_US', 'Send'),
    ('com_retour_page', 'fr_FR', 'Retour à la page commentée'),
    ('com_retour_page', 'en_US', 'Back to page commented'),
    ('com_moncommentaire_info', 'fr_FR', 'Votre commentaire ne doit pas excéder 500 caractères sinon il sera tronqué.'),
    ('com_moncommentaire_info', 'en_US', 'If your comment exceeds 500 characters, it will be truncated.'),
    ('com_moncommentaire_nombre', 'fr_FR', 'Nombre de caractères utilisés'),
    ('com_moncommentaire_nombre', 'en_US', 'Number of characters used'),
    ('com_commentaire_abus_titre', 'fr_FR', 'Alerter les modérateurs'),
    ('com_commentaire_abus_titre', 'en_US', 'Report this comment'),
    ('com_commentaire_par', 'fr_FR', 'par'),
    ('com_commentaire_par', 'en_US', 'by'),
    ('com_commentaire_le', 'fr_FR', 'le'),
    ('com_commentaire_le', 'en_US', 'posted on');");

    $app->dbh->exec("INSERT INTO `DD_TRADUCTION` (`TRA_CODE`, `MOD_CODE`, `TRA_DESCRIPTION`) VALUES
    ('com_monform', 'MOD_COMMENTAIRE', 'Titre du formulaire pour le depot d''un commentaire'),
    ('com_form_repere', 'MOD_COMMENTAIRE', 'Libellé du champs <Commentaire repéré>'),
    ('com_form_decrireabus', 'MOD_COMMENTAIRE', 'Libellé du champs <Décrire l''abus>'),
    ('com_fe_pseudo', 'MOD_COMMENTAIRE', 'Libellé message erreur pseudo requis'),
    ('com_fe_mail', 'MOD_COMMENTAIRE', 'Libellé message erreur mail requis'),
    ('com_fe_mail_invalid', 'MOD_COMMENTAIRE', 'Libellé message erreur mail invalid'),
    ('com_fe_message', 'MOD_COMMENTAIRE', 'Libellé message erreur message requis'),
    ('com_afe_prenom', 'MOD_COMMENTAIRE', 'Libellé message erreur abus prénom requis'),
    ('com_afe_mail', 'MOD_COMMENTAIRE', 'Libellé message erreur abus mail requis'),
    ('com_afe_mail_invalid', 'MOD_COMMENTAIRE', 'Libellé message erreur abus mail invalid'),
    ('com_afe_decrireabus', 'MOD_COMMENTAIRE', 'Libellé message erreur abus message requis'),
    ('com_form_prenom', 'MOD_COMMENTAIRE', 'Libellé du champs <Mon prénom>'),
    ('com_form_submitError', 'MOD_COMMENTAIRE', 'Message erreur à la sauvegarde d''un abus'),
    ('com_form_abuscom', 'MOD_COMMENTAIRE', 'Message erreur si l''abus est associé à aucun commentaire')");

    $app->dbh->exec("INSERT INTO `TRADUCTION_LANGUE` (`TRA_CODE`, `LNG_CODE`, `TRA_LIBELLE`) VALUES
    ('com_monform', 'fr_FR', 'Ajouter un commentaire'),
    ('com_monform', 'en_US', 'Add a comment'),
    ('com_form_repere', 'fr_FR', 'Commentaire repéré'),
    ('com_form_repere', 'en_US', 'Reported comment'),
    ('com_form_decrireabus', 'fr_FR', 'Décrire l''abus'),
    ('com_form_decrireabus', 'en_US', 'Define abuse'),
    ('com_fe_pseudo', 'fr_FR', 'Le champs <Mon pseudo> est obligatoire.'),
    ('com_fe_pseudo', 'en_US', 'Field <My alias> is mandatory.'),
    ('com_fe_mail', 'fr_FR', 'Le champs <Mon e-mail> est obligatoire.'),
    ('com_fe_mail', 'en_US', 'Field <My email> is mandatory.'),
    ('com_fe_mail_invalid', 'fr_FR', 'La valeur du champs <Mon e-mail> est invalide.'),
    ('com_fe_mail_invalid', 'en_US', 'Invalid value for field <My email>.'),
    ('com_fe_message', 'fr_FR', 'Le champs <Mon commentaire> est obligatoire.'),
    ('com_fe_message', 'en_US', 'Field <My comment> is mandatory.'),
    ('com_afe_prenom', 'fr_FR', 'Le champs <Mon prénom> est obligatoire.'),
    ('com_afe_prenom', 'en_US', 'Field <My first name> is mandatory.'),
    ('com_afe_mail', 'fr_FR', 'Le champs <Mon e-mail> est obligatoire.'),
    ('com_afe_mail', 'en_US', 'Field <My email> is mandatory.'),
    ('com_afe_mail_invalid', 'fr_FR', 'La valeur du champs <Mon e-mail> est invalide.'),
    ('com_afe_mail_invalid', 'en_US', 'Invalid value for field <My email>.'),
    ('com_afe_decrireabus', 'fr_FR', 'Le champs <Décrire l''abus> est obligatoire.'),
    ('com_afe_decrireabus', 'en_US', 'Field <Define abuse> is mandatory.'),
    ('com_form_prenom', 'fr_FR', 'Mon prénom'),
    ('com_form_prenom', 'en_US', 'My first name'),
    ('com_form_submitError', 'fr_FR', 'Une erreur s''est produit à la sauvegarde de cet abus. Veuillez contacter l''administrateur du site.'),
    ('com_form_submitError', 'en_US', 'An error has occured. Please contact the site admin.'),
    ('com_form_abuscom', 'fr_FR', 'Nous ne retrouvons pas le commentaire lié.'),
    ('com_form_abuscom', 'en_US', 'Nous ne retrouvons pas le commentaire lié.')");

}

if ($registeredVersion && version_compare($registeredVersion, '6.0.0dev5', '<')) {
    $app->dbh->exec("INSERT INTO `DD_FORMULAIREQUESTIONTYPE` (`QTY_CODE`, `QTY_LIBELLE`, `QTY_GROUPE`) VALUES
    ('QTY_URL', '@fr_FR:URL@en_US:URL@', 'Input');");
}

if ($registeredVersion && version_compare($registeredVersion, '6.0.0dev6', '<')) {
    $app->dbh->exec("INSERT INTO `DD_FORMULAIREQUESTIONTYPE` (`QTY_CODE`, `QTY_LIBELLE`, `QTY_GROUPE`) VALUES
    ('QTY_TEL', '@fr_FR:Téléphone@en_US:Telephone@', 'Input');");
}

if ($registeredVersion && version_compare($registeredVersion, '6.0.0dev7', '<')) {
    /** module core commentaire + modifs ajax liaison dans le db-schema **/
    /* voir wiki pour infos et modifs */
    $app->log("\n== Ticket #8917 : Gestion des blocs \'En savoir plus\' sur les paragraphes rédactionnels ==\n");

    $app->dbh->exec("INSERT INTO `DD_MODULE` (`MOD_CODE`, `MOD_LIBELLE`, `MOD_GROUPE`, `MOD_OBLIGATOIRE`, `MOD_POIDS`) VALUES
    ('MOD_ENSAVOIRPLUS', '@fr_FR:Blocs \‘En savoir plus\’ dans les paragraphes@', '@fr_FR:Administration@', 0, 19);");

    $app->dbh->exec("INSERT INTO `DD_TRADUCTION` (`TRA_CODE`, `MOD_CODE`, `TRA_DESCRIPTION`) VALUES
    ('esp_En_Savoir_Plus', 'MOD_ENSAVOIRPLUS', 'Libellé du lien (En savoir plus)'),
    ('esp_Document', 'MOD_ENSAVOIRPLUS', ''),
    ('esp_a_telecharger', 'MOD_ENSAVOIRPLUS', ''),
    ('esp_nouvelle_fenetre', 'MOD_ENSAVOIRPLUS', '');");

    $app->dbh->exec("INSERT INTO `TRADUCTION_LANGUE` (`TRA_CODE`, `LNG_CODE`, `TRA_LIBELLE`) VALUES
    ('esp_En_Savoir_Plus', 'fr_FR', 'En savoir plus'),
    ('esp_En_Savoir_Plus', 'en_US', 'More'),
    ('esp_Document', 'fr_FR', 'Document'),
    ('esp_Document', 'en_US', 'Document'),
    ('esp_a_telecharger', 'fr_FR', 'à télécharger'),
    ('esp_a_telecharger', 'en_US', 'download'),
    ('esp_nouvelle_fenetre', 'fr_FR', 'Nouvelle fenêtre'),
    ('esp_nouvelle_fenetre', 'en_US', 'New window');");
}

if ($registeredVersion && version_compare($registeredVersion, '6.0.0dev8', '<')) {
    //CMSV6
    //#8974 : 2.4 Ouverture URL avec paramètres
    //remettre à parser les paragraphes afin de prendre en compte le changement de generation d'url

    $app->dbh->exec("UPDATE OFF_PARAGRAPHE set PAR_APARSER = 1 where PRT_CODE = 'PRT_TXT'");
    $app->dbh->exec("UPDATE ON_PARAGRAPHE set PAR_APARSER = 1 where PRT_CODE = 'PRT_TXT'");

}

if ($registeredVersion && version_compare($registeredVersion, '6.0.0dev9', '<')) {
    //CMSV6
    //#8968 : 1.8 Template accroche des pages filles
    //création template + format vignette


    $app->dbh->exec("INSERT INTO `DD_TEMPLATE` (`TPL_CODE`, `MOD_CODE`, `TPL_LIBELLE`, `TPL_PAGE`, `TPL_AFFECTABLE`, `TPL_LINKABLE`, `TPL_COLONNE`, `TPL_POPUP_RESTRICTION`, `TPL_PARAMETRAGE`, `TPL_PARAMETRAGE_CLES`, `TPL_URLCODE`, `PGS_CODE`, `TPL_REWRITEURL`, `TPL_REWRITEMETHOD`) VALUES
    ('TPL_LISTEPAGESFILLE', 'MOD_CORE', '@fr_FR:Accroche des pages filles@en_US:List of child pages@', 'core.tpl_accrochePagesFilles.php', 1, 0, '@PAR_CENTRAL@', '', NULL, NULL, NULL, NULL, NULL, NULL);");


}

if ($registeredVersion && version_compare($registeredVersion, '6.0.0dev11', '<')) {
    $app->dbh->exec("UPDATE `DD_GABARIT` SET `GAB_CSS_PATH` = 'include/css/GAB_INIT/default.less' WHERE `GAB_CODE` = 'GAB_INIT';");
}

if ($registeredVersion && version_compare($registeredVersion, '6.0.0dev19', '<')) {
    $app->log("\n== \n== Ticket #9316 - #9321 Révisions + maj bdd ==\n");

    //script pour correction table liaisons, mettre le bon ID_LIAISON dans les tableaux

    $sqlLiasonsPageRevPage = "select * from LIAISON_PAGE where LIA_CODE = 'REVISION_PAGE'";
    foreach ($app->dbh->query($sqlLiasonsPageRevPage)->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $idRevisionPageSql = "select ID_REVISIONPAGE from REVISION_PAGE where ID_REVISION = ".$row['ID_REVISION'];
        $idRevisionPage = $app->dbh->query($idRevisionPageSql)->fetch(PDO::FETCH_COLUMN);
        if (intval($idRevisionPage) > 0) {
           $app->dbh->exec("UPDATE LIAISON_PAGE set ID_LIAISON = ".intval($idRevisionPage)." where ID_LIAISON_PAGE = ".$row['ID_LIAISON_PAGE']);
        }
    }

    $sqlLiasonsPageRevParagraphe = "select * from LIAISON_PAGE where LIA_CODE = 'REVISION_PARAGRAPHE'";
    foreach ($app->dbh->query($sqlLiasonsPageRevParagraphe)->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $idRevisionParagrapheSql = "select ID_REVISIONPARAGRAPHE from REVISION_PARAGRAPHE where ID_REVISION = ".$row['ID_REVISION']." and ID_PARAGRAPHE = ".$row['ID_LIAISON'];
        $idRevisionParagraphe = $app->dbh->query($idRevisionParagrapheSql)->fetch(PDO::FETCH_COLUMN);
        if (intval($idRevisionParagraphe) > 0) {
           $app->dbh->exec("UPDATE LIAISON_PAGE set ID_LIAISON = ".intval($idRevisionParagraphe)." where ID_LIAISON_PAGE = ".$row['ID_LIAISON_PAGE']);
        }
    }

    $sqlLiasonsWebRevPage = "select * from LIAISON_WEBOTHEQUE where LIA_CODE = 'REVISION_PAGE'";
    foreach ($app->dbh->query($sqlLiasonsWebRevPage)->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $idRevisionPageSql = "select ID_REVISIONPAGE from REVISION_PAGE where ID_REVISION = ".$row['ID_REVISION'];
        $idRevisionPage = $app->dbh->query($idRevisionPageSql)->fetch(PDO::FETCH_COLUMN);
        if (intval($idRevisionPage) > 0) {
           $app->dbh->exec("UPDATE LIAISON_WEBOTHEQUE set ID_LIAISON = ".intval($idRevisionPage)." where ID_LIAISON_WEBOTHEQUE = ".$row['ID_LIAISON_WEBOTHEQUE']);
        }
    }

    $sqlLiasonsWebRevParagraphe = "select * from LIAISON_WEBOTHEQUE where LIA_CODE = 'REVISION_PARAGRAPHE'";
    foreach ($app->dbh->query($sqlLiasonsWebRevParagraphe)->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $idRevisionParagrapheSql = "select ID_REVISIONPARAGRAPHE from REVISION_PARAGRAPHE where ID_REVISION = ".$row['ID_REVISION']." and ID_PARAGRAPHE = ".$row['ID_LIAISON'];
        $idRevisionParagraphe = $app->dbh->query($idRevisionParagrapheSql)->fetch(PDO::FETCH_COLUMN);
        if (intval($idRevisionParagraphe) > 0) {
           $app->dbh->exec("UPDATE LIAISON_WEBOTHEQUE set ID_LIAISON = ".intval($idRevisionParagraphe)." where ID_LIAISON_WEBOTHEQUE = ".$row['ID_LIAISON_WEBOTHEQUE']);
        }
    }



}

if ($registeredVersion && version_compare($registeredVersion, '6.0.0dev20', '<')) {
    $app->log("\n== Ticket #7050 : Workflow du Profil Valideur ==\n");
    $app->dbh->exec("UPDATE `WORKFLOW` SET `WKF_PROFIL` = '@PRO_ROOT@PRO_ROOT_SITE@PRO_VALIDATEUR@' WHERE PST_CODE_IN = 'PST_AREDIGER' and PST_CODE_OUT = 'PST_HORSLIGNE';");

}

if ($registeredVersion && version_compare($registeredVersion, '6.0.0dev21', '<')) {
    $app->log("\n== Ticket #7035 : Traduction Champs obligatoire ==\n");
    $app->dbh->exec("UPDATE `TRADUCTION_LANGUE` SET `TRA_LIBELLE` = '* Mandatory fields' WHERE TRA_CODE = 'champs_obligatoire' and LNG_CODE = 'en_US';");
    $app->dbh->exec("UPDATE `TRADUCTION_LANGUE` SET `TRA_LIBELLE` = '* Champs obligatoires' WHERE TRA_CODE = 'champs_obligatoire' and LNG_CODE = 'fr_FR';");
}

if ($registeredVersion && version_compare($registeredVersion, '6.0.0dev25', '<')) {
    $app->dbh->exec("INSERT INTO `DD_TRADUCTION` (`TRA_CODE` ,`MOD_CODE` ,`TRA_DESCRIPTION` ,`TRA_MULTILIGNE`) VALUES
                        ('rechercher', 'MOD_RECHERCHE', '', '0');");

    $app->dbh->exec("INSERT INTO `TRADUCTION_LANGUE` (`TRA_CODE` ,`LNG_CODE` ,`TRA_LIBELLE`) VALUES
                        ('rechercher', 'fr_FR', 'Rechercher'),
                        ('rechercher', 'en_US', 'Search');");
}

if ($registeredVersion && version_compare($registeredVersion, '6.0.0dev27', '<')) {
    $app->log("\n== Ticket #7050 : Workflow du Profil Valideur ==\n");
    $app->dbh->exec("UPDATE `WORKFLOW` SET `WKF_PROFIL` = '@PRO_VALIDATEUR@PRO_ROOT@PRO_ROOT_SITE@' WHERE PST_CODE_IN = 'PST_ENLIGNE' and PST_CODE_OUT = 'PST_HORSLIGNE';");

}

if ($registeredVersion && version_compare($registeredVersion, '6.0.0dev30', '<')) {
    $app->dbh->exec("INSERT INTO `DD_TRADUCTION` (`TRA_CODE`, `MOD_CODE`, `TRA_DESCRIPTION`, `TRA_MULTILIGNE`) VALUES
        ('cor_fermer', 'MOD_CORE', '', 0),
        ('cor_image_sur_total', 'MOD_CORE', '', 0);");

    $app->dbh->exec("INSERT INTO `TRADUCTION_LANGUE` (`TRA_CODE`, `LNG_CODE`, `TRA_LIBELLE`) VALUES
        ('cor_fermer', 'fr_FR', 'Fermer'),
        ('cor_fermer', 'en_US', 'Close'),
        ('cor_image_sur_total', 'en_US', 'Image {current} of {total}'),
        ('cor_image_sur_total', 'fr_FR', 'Image {current} sur {total}');");
}
if ($registeredVersion && version_compare($registeredVersion, '6.0.0dev31', '<')) {
    $app->dbh->exec("UPDATE `DD_MODULE` set `MOD_LIBELLE`='@fr_FR:Formulaires dynamiques@', `MOD_GROUPE`='@fr_FR:Formulaires@' WHERE `MOD_CODE`='MOD_FORMULAIRE'");
}
if ($registeredVersion && version_compare($registeredVersion, '6.0.0dev33', '<')) {
    $app->dbh->exec("delete from TRADUCTION_SITE where TRA_CODE='com_captcha_antispam'");
    $app->dbh->exec("delete from TRADUCTION_LANGUE where TRA_CODE='com_captcha_antispam'");
    $app->dbh->exec("delete from DD_TRADUCTION where TRA_CODE='com_captcha_antispam' and MOD_CODE='MOD_COMMENTAIRE'");
}
if ($registeredVersion && version_compare($registeredVersion, '6.0.0dev34', '<')) {
    $app->dbh->exec("delete from TRADUCTION_SITE where TRA_CODE='com_charte_moderation'");
    $app->dbh->exec("delete from TRADUCTION_LANGUE where TRA_CODE='com_charte_moderation'");
    $app->dbh->exec("delete from DD_TRADUCTION where TRA_CODE='com_charte_moderation' and MOD_CODE='MOD_COMMENTAIRE'");
}
if ($registeredVersion && version_compare($registeredVersion, '6.0.0dev35', '<')) {
    $app->dbh->exec("INSERT INTO `DD_MODULE` (`MOD_CODE`, `MOD_LIBELLE`, `MOD_GROUPE`, `MOD_OBLIGATOIRE`, `MOD_POIDS`) VALUES
        ('MOD_CAPTCHA', '@fr_FR:Captcha@', '@fr_FR:Administration@', 0, 3)");
    $app->dbh->exec("update DD_TRADUCTION set MOD_CODE='MOD_CAPTCHA' where TRA_CODE in ('Captcha_antispam', 'Captcha_invalide', 'Captcha_question')");
    $app->dbh->exec("delete from TRADUCTION_SITE where TRA_CODE in ('com_captcha', 'com_captcha_question', 'com_fe_captcha', 'com_fe_captcha_invalide')");
    $app->dbh->exec("delete from TRADUCTION_LANGUE where TRA_CODE in ('com_captcha', 'com_captcha_question', 'com_fe_captcha', 'com_fe_captcha_invalide')");
    $app->dbh->exec("delete from DD_TRADUCTION where TRA_CODE in ('com_captcha', 'com_captcha_question', 'com_fe_captcha', 'com_fe_captcha_invalide') and MOD_CODE='MOD_COMMENTAIRE'");
    $app->dbh->exec("INSERT INTO `DD_TRADUCTION` (`TRA_CODE`, `MOD_CODE`, `TRA_DESCRIPTION`, `TRA_MULTILIGNE`) VALUES
        ('Captcha_obligatoire', 'MOD_CAPTCHA', '', 0)");
    $app->dbh->exec("INSERT INTO `TRADUCTION_LANGUE` (`TRA_CODE`, `LNG_CODE`, `TRA_LIBELLE`) VALUES
        ('Captcha_obligatoire', 'fr_FR', 'Le champ Captcha antispam est obligatoire'),
        ('Captcha_obligatoire', 'en_US', 'The Captcha antispam field is mandatory')");
}
if ($registeredVersion && version_compare($registeredVersion, '6.0.0dev36', '<')) {
    $app->dbh->exec("INSERT INTO `DD_TRADUCTION` (`TRA_CODE`, `MOD_CODE`, `TRA_DESCRIPTION`) VALUES
        ('com_commentaire_a_ete_valide', 'MOD_COMMENTAIRE', 'Sujet du mail de notification après validation d''un commentaire'),
        ('com_commentaire_a_ete_refuse', 'MOD_COMMENTAIRE', 'Sujet du mail de notification après refus d''un commentaire')");

    $app->dbh->exec("INSERT INTO `TRADUCTION_LANGUE` (`TRA_CODE`, `LNG_CODE`, `TRA_LIBELLE`) VALUES
        ('com_commentaire_a_ete_valide', 'fr_FR', 'Votre commentaire a été validé'),
        ('com_commentaire_a_ete_valide', 'en_US', 'Your comment has been validated'),
        ('com_commentaire_a_ete_refuse', 'fr_FR', 'Votre commentaire a été refusé'),
        ('com_commentaire_a_ete_refuse', 'en_US', 'Your comment has been refused')");
}
