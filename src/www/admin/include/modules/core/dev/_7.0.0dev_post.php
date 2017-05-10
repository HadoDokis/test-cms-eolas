<?php
if ($registeredVersion && version_compare($registeredVersion, '7.0.0dev1', '<')) {
    $app->dbh->exec("INSERT INTO `DD_EMAILTEMPLATE` (`EMT_CODE`, `MOD_CODE`, `EMT_DESCRIPTION`, `EMT_EXPEDITEUR`, `EMT_EXPEDITEURFROM`, `EMT_SUJET`, `EMT_BODYHTML`) VALUES
    ('EMT_FRM_NOTIF_BO', 'MOD_FORMULAIRE', 'Notification BO (l''expéditeur est celui défini dans le formulaire)', 'CMS', '', 'Formulaire [FRM_LIBELLE]', '<p>Le [DATE_JMA]</p>\r\n<p>Bonjour,<br />le formulaire \"[FRM_LIBELLE]\" a été renseigné :</p>\r\n<p>[REPONSES]</p>'),
    ('EMT_FRM_NOTIF_FO', 'MOD_FORMULAIRE', 'Notification FO (l''expéditeur est celui défini dans le formulaire)', 'CMS', '', 'AR : [FRM_LIBELLE]', '<p>Le [DATE_JMA]</p>\r\n<p>Bonjour,</p>\r\n<p>merci d''avoir rempli le formulaire \"[FRM_LIBELLE]\"</p>\r\n<p>[FRM_ACCUSERECEPTION]</p>');");

    $app->dbh->exec("INSERT INTO `DD_EMAILTEMPLATEKEY` (`EMT_CODE`, `EMK_LIBELLE`, `EMK_DESCRIPTION`) VALUES
    ('EMT_FRM_NOTIF_BO', 'FRM_LIBELLE', 'Libellé du formulaire'),
    ('EMT_FRM_NOTIF_BO', 'REPONSES', 'Ensemble des question / réponses'),
    ('EMT_FRM_NOTIF_FO', 'FRM_ACCUSERECEPTION', 'Texte d''accusé reception (lui même dynamique)'),
    ('EMT_FRM_NOTIF_FO', 'FRM_LIBELLE', 'Libellé du formulaire');");
}
// Email d'activation de compte et de changement de mot de passe
if ($registeredVersion && version_compare($registeredVersion, '7.0.0dev11', '<')) {
    $app->dbh->exec("INSERT INTO `DD_EMAILTEMPLATE` (`EMT_CODE`, `MOD_CODE`, `EMT_DESCRIPTION`, `EMT_EXPEDITEUR`, `EMT_EXPEDITEURFROM`, `EMT_SUJET`, `EMT_BODYHTML`) VALUES
    ('EMT_EXTRANET_ACCOUNT_ACTIVATE', 'MOD_EXTRANET', 'Activation de compte', 'CMS', '', '[SIT_TITLE] : Activation de compte', '<p>Bonjour,</p><p>L\'administrateur du site \"[SIT_TITLE]\" a créé votre compte utilisateur.</p><p>Votre identifiant est : [UTI_LOGIN]</p><p>Pour activer votre compte et créer directement votre mot de passe, veuillez [LIEN_ACTIVATION].</p>
            <p>Attention ce lien n\'est valable que pendant 2 heures. Passé ce délai, vous devrez soumettre une nouvelle demande d\'activation de compte.</p>
            <p>Vous pouvez également contacter l\'administrateur de la plateforme pour lui demander de vous notifier pour recevoir une nouvelle demande d\'activation.</p>
            <p>[SIT_TITLE].</p>')");

    $app->dbh->exec("INSERT INTO `DD_EMAILTEMPLATEKEY` (`EMT_CODE`, `EMK_LIBELLE`, `EMK_DESCRIPTION`) VALUES
    ('EMT_EXTRANET_ACCOUNT_ACTIVATE', 'SIT_TITLE', 'Nom du site'),
    ('EMT_EXTRANET_ACCOUNT_ACTIVATE', 'UTI_CIVILITE', 'Civilité de l\'utilisateur'),
    ('EMT_EXTRANET_ACCOUNT_ACTIVATE', 'UTI_NOM', 'Nom de l\'utilisateur'),
    ('EMT_EXTRANET_ACCOUNT_ACTIVATE', 'UTI_PRENOM', 'Prénom de l\'utilisateur'),
    ('EMT_EXTRANET_ACCOUNT_ACTIVATE', 'UTI_LOGIN', 'Identifiant de l\'utilisateur'),
    ('EMT_EXTRANET_ACCOUNT_ACTIVATE', 'LIEN_ACTIVATION', 'Lien d\'activation de compte de la forme \"cliquer sur ce lien\"')");

    $app->dbh->exec("INSERT INTO `DD_EMAILTEMPLATE` (`EMT_CODE`, `MOD_CODE`, `EMT_DESCRIPTION`, `EMT_EXPEDITEUR`, `EMT_EXPEDITEURFROM`, `EMT_SUJET`, `EMT_BODYHTML`) VALUES
    ('EMT_EXTRANET_ACCOUNT_FORGOTTEN', 'MOD_EXTRANET', 'Mot de passe perdu', 'CMS', '', '[SIT_TITLE] : Mot de passe perdu', '<p>Bonjour,</p><p>Pour récupérer vos informations d\'identification veuillez [LIEN_ACTIVATION]</p>
                <p>Attention ce lien n\'est valable que pendant 2 heures. Passé ce délai, vous devrez soumettre une nouvelle demande de modification de votre mot de passe.</p>
                <p>[SIT_TITLE].</p>')");

    $app->dbh->exec("INSERT INTO `DD_EMAILTEMPLATEKEY` (`EMT_CODE`, `EMK_LIBELLE`, `EMK_DESCRIPTION`) VALUES
    ('EMT_EXTRANET_ACCOUNT_FORGOTTEN', 'SIT_TITLE', 'Nom du site'),
    ('EMT_EXTRANET_ACCOUNT_FORGOTTEN', 'UTI_CIVILITE', 'Civilité de l\'utilisateur'),
    ('EMT_EXTRANET_ACCOUNT_FORGOTTEN', 'UTI_NOM', 'Nom de l\'utilisateur'),
    ('EMT_EXTRANET_ACCOUNT_FORGOTTEN', 'UTI_PRENOM', 'Prénom de l\'utilisateur'),
    ('EMT_EXTRANET_ACCOUNT_FORGOTTEN', 'UTI_LOGIN', 'Identifiant de l\'utilisateur'),
    ('EMT_EXTRANET_ACCOUNT_FORGOTTEN', 'LIEN_ACTIVATION', 'Lien de récupération des informations d\'identification de la forme \"cliquer sur ce lien\"')");
}
if ($registeredVersion && version_compare($registeredVersion, '7.0.0dev12', '<')) {
    $app->dbh->exec("delete from `TRADUCTION_SITE` where `TRA_CODE` in ('login_envoyer_nouveau_mdp', 'login_sujet_mail_mdp_oublie', 'login_changement_mdp', 'login_nouveau_mdp_envoye_a', 'login_renseigner_tous_champs', 'login_email')");
    $app->dbh->exec("delete from `TRADUCTION_LANGUE` where `TRA_CODE` in ('login_envoyer_nouveau_mdp', 'login_sujet_mail_mdp_oublie', 'login_changement_mdp', 'login_nouveau_mdp_envoye_a', 'login_renseigner_tous_champs', 'login_email')");
    $app->dbh->exec("delete from `DD_TRADUCTION` where `TRA_CODE` in ('login_envoyer_nouveau_mdp', 'login_sujet_mail_mdp_oublie', 'login_changement_mdp', 'login_nouveau_mdp_envoye_a', 'login_renseigner_tous_champs', 'login_email')");
}
if ($registeredVersion && version_compare($registeredVersion, '7.0.0dev13', '<')) {
    $app->dbh->exec("UPDATE TRADUCTION_LANGUE set TRA_LIBELLE='Mot de passe perdu ?' WHERE TRA_CODE='login_mdp_oublie' AND LNG_CODE='fr_FR'");
}

if ($registeredVersion && version_compare($registeredVersion, '7.0.0dev14', '<')) {
    if ($si->tableExists('UTILISATEUR')
        && $si->UTILISATEUR->indexExists('UTI_STATUT_LOCKED','btree','UTI_STATUT_LOCKED')
    ) {
        $schema = dbSchema::init($app->dbh);
        $schema->dropIndex('UTILISATEUR', 'UTI_STATUT_LOCKED');
    }
}

// Emails Lié aux commentaires (Validation / Refus)
if ($registeredVersion && version_compare($registeredVersion, '7.0.0dev15', '<')) {
    $app->dbh->exec("INSERT INTO `DD_EMAILTEMPLATE` (`EMT_CODE`, `MOD_CODE`, `EMT_DESCRIPTION`, `EMT_EXPEDITEUR`, `EMT_EXPEDITEURFROM`, `EMT_SUJET`, `EMT_BODYHTML`) VALUES
        ('EMT_COMMENTAIRE_VALIDE', 'MOD_COMMENTAIRE', 'Commentaire validé', 'CMS', '', '[SIT_TITLE] : Votre commentaire a été validé', '<p>Bonjour,</p>
            <p>Le commentaire que vous avez déposé sur notre site \"[LIEN_COMMENTAIRE]\" vient d\'être validé.</p>
            <p>[MESSAGE_VALIDATION_DU_SITE]</p>
            <p>[PARAMETRE_SIGNATURE_DU_SITE]</p>')");

    $app->dbh->exec("INSERT INTO `DD_EMAILTEMPLATEKEY` (`EMT_CODE`, `EMK_LIBELLE`, `EMK_DESCRIPTION`) VALUES
        ('EMT_COMMENTAIRE_VALIDE', 'SIT_TITLE', 'Nom du site'),
        ('EMT_COMMENTAIRE_VALIDE', 'LIEN_COMMENTAIRE', 'Lien vers le commentaire (le libellé du lien correspond au titre du site).'),
        ('EMT_COMMENTAIRE_VALIDE', 'MESSAGE_VALIDATION_DU_SITE', 'Message de validation des commentaires définit dans chacun des sites.'),
        ('EMT_COMMENTAIRE_VALIDE', 'PARAMETRE_SIGNATURE_DU_SITE', 'Signature de l\'emails associée aux commentaires définit pour chacun des sites.')");

    $app->dbh->exec("INSERT INTO `DD_EMAILTEMPLATE` (`EMT_CODE`, `MOD_CODE`, `EMT_DESCRIPTION`, `EMT_EXPEDITEUR`, `EMT_EXPEDITEURFROM`, `EMT_SUJET`, `EMT_BODYHTML`) VALUES
        ('EMT_COMMENTAIRE_REFUS', 'MOD_COMMENTAIRE', 'Commentaire refusé', 'CMS', '', '[SIT_TITLE] : Votre commentaire a été refusé', '<p>Bonjour,</p>
            <p>Le commentaire que vous avez déposé sur notre site \"[LIEN_COMMENTAIRE]\" vient d\'être refusé..</p>
            <p>[MESSAGE_REFUS_DU_SITE]</p>
            <p>[PARAMETRE_SIGNATURE_DU_SITE]</p>')");

    $app->dbh->exec("INSERT INTO `DD_EMAILTEMPLATEKEY` (`EMT_CODE`, `EMK_LIBELLE`, `EMK_DESCRIPTION`) VALUES
        ('EMT_COMMENTAIRE_REFUS', 'SIT_TITLE', 'Nom du site'),
        ('EMT_COMMENTAIRE_REFUS', 'LIEN_COMMENTAIRE', 'Lien vers le commentaire (le libellé du lien correspond au titre du site).'),
        ('EMT_COMMENTAIRE_REFUS', 'MESSAGE_REFUS_DU_SITE', 'Message de refus des commentaires définit dans chacun des sites.'),
        ('EMT_COMMENTAIRE_REFUS', 'PARAMETRE_SIGNATURE_DU_SITE', 'Signature de l\'emails associée aux commentaires définit pour chacun des sites.')");
}

// Emails Lié aux commentaires (Nouveau / Abus)
if ($registeredVersion && version_compare($registeredVersion, '7.0.0dev16', '<')) {
    $app->dbh->exec("UPDATE DD_COMMENTAIRE_LIAISONTYPE set CLI_CHEMINFICHE='/cms/cms_pseudo.php?idtf=' WHERE MOD_CODE='MOD_CORE' AND PRO_CODE='PRO_COMMENTAIRE' AND CLI_CLASSNOM='Page'");
    $app->dbh->exec("INSERT INTO `DD_EMAILTEMPLATE` (`EMT_CODE`, `MOD_CODE`, `EMT_DESCRIPTION`, `EMT_EXPEDITEUR`, `EMT_EXPEDITEURFROM`, `EMT_SUJET`, `EMT_BODYHTML`) VALUES
        ('EMT_COMMENTAIRE_NOUVEAU', 'MOD_COMMENTAIRE', 'Nouveau commentaire', 'CMS', '', '[SIT_TITLE] : Nouveau commentaire à modérer', '<p>Bonjour,</p>
            <p>Un nouveau commentaire (n°[NUMERO_COMMENTAIRE]) a été déposé sur la page \"[LIEN_PAGE_COMMENTEE]\" :</p>
            <ul>
                <li><strong>Pseudo :</strong>[PSEUDO]</li>
                <li><strong>E-mail :</strong>[EMAIL]</li>
                <li><strong>Message :</strong>[MESSAGE]</li>
            </ul>
            <p><strong>[LIEN_MODERATION]</strong></p>')");

    $app->dbh->exec("INSERT INTO `DD_EMAILTEMPLATEKEY` (`EMT_CODE`, `EMK_LIBELLE`, `EMK_DESCRIPTION`) VALUES
        ('EMT_COMMENTAIRE_NOUVEAU', 'SIT_TITLE', 'Nom du site'),
        ('EMT_COMMENTAIRE_NOUVEAU', 'NUMERO_COMMENTAIRE', 'Numéro du commentaire.'),
        ('EMT_COMMENTAIRE_NOUVEAU', 'LIEN_PAGE_COMMENTEE', 'Lien vers la page où le commentaire a été déposé.'),
        ('EMT_COMMENTAIRE_NOUVEAU', 'PSEUDO', 'Pseudonyme de l\'utilisateur ayant déposé le commentaire.'),
        ('EMT_COMMENTAIRE_NOUVEAU', 'EMAIL', 'Adresse mail de l\'utilisateur ayant déposé le commentaire.'),
        ('EMT_COMMENTAIRE_NOUVEAU', 'MESSAGE', 'Contenu du commentaire.'),
        ('EMT_COMMENTAIRE_NOUVEAU', 'LIEN_MODERATION', 'Lien de modération du commentaire.')");

    $app->dbh->exec("INSERT INTO `DD_EMAILTEMPLATE` (`EMT_CODE`, `MOD_CODE`, `EMT_DESCRIPTION`, `EMT_EXPEDITEUR`, `EMT_EXPEDITEURFROM`, `EMT_SUJET`, `EMT_BODYHTML`) VALUES
        ('EMT_COMMENTAIRE_ABUS', 'MOD_COMMENTAIRE', 'Commentaire abusif signalé', 'CMS', '', '[SIT_TITLE] : Commentaire abusif signalé', '<p>Bonjour,</p>
            <p>Le commentaire (n°[NUMERO_COMMENTAIRE]) déposé sur la page  \"[INFO_PAGE_COMMENTEE]\" a été dénoncé par :</p>
            <ul>
                <li><strong>Prénom :</strong>[PRENOM]</li>
                <li><strong>E-mail :</strong>[EMAIL]</li>
                <li><strong>Commentaire dénoncé :</strong>[COMMENTAIRE]</li>
                <li><strong>Description de l\'abus :</strong>[DESCRIPTION_ABUS]</li>
            </ul>
            <p><strong>[LIEN_MODERATION]</strong></p>')");

    $app->dbh->exec("INSERT INTO `DD_EMAILTEMPLATEKEY` (`EMT_CODE`, `EMK_LIBELLE`, `EMK_DESCRIPTION`) VALUES
        ('EMT_COMMENTAIRE_ABUS', 'SIT_TITLE', 'Nom du site'),
        ('EMT_COMMENTAIRE_ABUS', 'NUMERO_COMMENTAIRE', 'Numéro du commentaire.'),
        ('EMT_COMMENTAIRE_ABUS', 'INFO_PAGE_COMMENTEE', 'Titre et numéro de la page où le commentaire a été déposé.'),
        ('EMT_COMMENTAIRE_ABUS', 'PRENOM', 'Prénom de l\'utilisateur ayant signalé l\'abus.'),
        ('EMT_COMMENTAIRE_ABUS', 'EMAIL', 'Adresse mail de l\'utilisateur ayant signalé l\'abus.'),
        ('EMT_COMMENTAIRE_ABUS', 'COMMENTAIRE', 'Commentaire faisant l\'objet d\'un signalement.'),
        ('EMT_COMMENTAIRE_ABUS', 'DESCRIPTION_ABUS', 'Description du signalement.'),
        ('EMT_COMMENTAIRE_ABUS', 'LIEN_MODERATION', 'Lien de modération du commentaire.')");
}

if ($registeredVersion && version_compare($registeredVersion, '7.0.0dev17', '<')) {
    $app->dbh->exec("INSERT INTO `DD_TRADUCTION` (`TRA_CODE`, `MOD_CODE`, `TRA_DESCRIPTION`, `TRA_MULTILIGNE`) VALUES
        ('cnil_lien_savoir_plus', 'MOD_CORE', 'Marqueur GA conforme CNIL : Libellé du lien permettant d\'afficher les détails au sein d\'une fenêtre modale', 0),
        ('cnil_titre_popup', 'MOD_CORE', 'Marqueur GA conforme CNIL : Titre de la fenêtre modale', 0),
        ('cnil_contenu_popup', 'MOD_CORE', 'Marqueur GA conforme CNIL : Informations CNIL présentées au sein de la fenêtre modale', 1),
        ('cnil_btn_popup_opposer', 'MOD_CORE', 'Marqueur GA conforme CNIL : Libellé du bouton de refus', 0),
        ('cnil_btn_popup_accepter', 'MOD_CORE', 'Marqueur GA conforme CNIL : Libellé du bouton d\'acceptation', 0),
        ('cnil_btn_popup_close', 'MOD_CORE', 'Marqueur GA conforme CNIL : Libelle du lien de fermeture de la fenêtre modale', 0),
        ('cnil_txt_bandeau_cnil', 'MOD_CORE', 'Marqueur GA conforme CNIL : Contenu du bandeau d\'information', 1),
        ('cnil_txt_bandeau_cnil_refus', 'MOD_CORE', 'Marqueur GA conforme CNIL : Contenu du bandeau d\'information après un refus', 1)");

    $app->dbh->exec("INSERT INTO `TRADUCTION_LANGUE` (`TRA_CODE`, `LNG_CODE`, `TRA_LIBELLE`) VALUES
        ('cnil_lien_savoir_plus', 'fr_FR', 'En savoir plus ou s\'opposer'),
        ('cnil_titre_popup', 'fr_FR', 'Les cookies Google Analytics'),
        ('cnil_contenu_popup', 'fr_FR', 'Ce site utilise des cookies de Google Analytics, ces cookies nous aident à identifier le contenu qui vous interesse le plus ainsi qu\'à repérer certains dysfonctionnements. Vos données de navigations sur ce site sont envoyées à Google Inc'),
        ('cnil_btn_popup_opposer', 'fr_FR', 'S\'opposer'),
        ('cnil_btn_popup_accepter', 'fr_FR', 'Accepter'),
        ('cnil_btn_popup_close', 'fr_FR', 'Fermer'),
        ('cnil_txt_bandeau_cnil', 'fr_FR', 'Ce site utilise Google Analytics. En continuant à naviguer, vous nous autorisez à déposer un cookie à des fins de mesure d\'audience.'),
        ('cnil_txt_bandeau_cnil_refus', 'fr_FR', 'Vous vous êtes opposé au dépôt de cookies de mesures d\'audience dans votre navigateur.')");
}

if ($registeredVersion && version_compare($registeredVersion, '7.0.0dev19', '<')) {
    $app->dbh->exec("UPDATE UTILISATEUR set UTI_CIVILITE='Mme' WHERE UTI_CIVILITE='Mlle'");
}

if ($registeredVersion && version_compare($registeredVersion, '7.0.0dev18', '<')) {
    $app->dbh->exec("UPDATE DD_EMAILTEMPLATE set MOD_CODE='MOD_EXTRANET' WHERE EMT_CODE in ('EMT_EXTRANET_ACCOUNT_ACTIVATE', 'EMT_EXTRANET_ACCOUNT_FORGOTTEN')");
}
