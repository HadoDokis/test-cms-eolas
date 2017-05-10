<?php
$oConnected = Utilisateur::getConnected();
$enabled = array(
    'MOD_FORMULAIRE' => CMS::getCurrentSite()->hasModule(new Module('MOD_FORMULAIRE')) && $oConnected->checkProfil(array(
        'PRO_FORMGEST',
        'PRO_FORMLECT'
    )),
    'MOD_ABREVIATION' => CMS::getCurrentSite()->hasModule(new Module('MOD_ABREVIATION')) && $oConnected->checkProfil(array(
        'PRO_ABREVIATION'
    )),
    'MOD_LANGUISME' => CMS::getCurrentSite()->hasModule(new Module('MOD_LANGUISME')) && $oConnected->checkProfil(array(
        'PRO_LANGUISME'
    )),
    'MOD_TRADUCTION' => CMS::getCurrentSite()->hasModule(new Module('MOD_TRADUCTION')) && $oConnected->checkProfil(array(
        'PRO_TRADUCTION'
    )),
    'MOD_THEMATIQUE' => CMS::getCurrentSite()->hasModule(new Module('MOD_THEMATIQUE')) && $oConnected->checkProfil(array(
        'PRO_THEMATIQUE'
    )),
    'MOD_RECHERCHE' => CMS::getCurrentSite()->hasModule(new Module('MOD_RECHERCHE')) && $oConnected->checkProfil(array(
        'PRO_RECHERCHE'
    )),
    'MOD_REFERENCEMENT' => CMS::getCurrentSite()->hasModule(new Module('MOD_REFERENCEMENT')) && $oConnected->checkProfil(array(
        'PRO_REFERENCEMENT'
    )),
    'MOD_COMMENTAIRE' => CMS::getCurrentSite()->hasModule(new Module('MOD_COMMENTAIRE')) && $oConnected->checkProfil(array(
        'PRO_COMMENTAIRE'
    )),
    'MOD_WEBOTHEQUE_DOCUMENT' => CMS::getCurrentSite()->hasModule(new Module('MOD_WEBOTHEQUE_DOCUMENT')) && $oConnected->checkProfil(array(
        'PRO_WEBDOCUMENT',
        'PRO_WEBROOT'
    )),
    'MOD_WEBOTHEQUE_FLASH' => CMS::getCurrentSite()->hasModule(new Module('MOD_WEBOTHEQUE_FLASH')) && $oConnected->checkProfil(array(
        'PRO_WEBFLASH',
        'PRO_WEBROOT'
    )),
    'MOD_WEBOTHEQUE_IMAGE' => CMS::getCurrentSite()->hasModule(new Module('MOD_WEBOTHEQUE_IMAGE')) && $oConnected->checkProfil(array(
        'PRO_WEBIMAGE',
        'PRO_WEBROOT'
    )),
    'MOD_WEBOTHEQUE_LIENEXTERNE' => CMS::getCurrentSite()->hasModule(new Module('MOD_WEBOTHEQUE_LIENEXTERNE')) && $oConnected->checkProfil(array(
        'PRO_WEBLIENEXTERNE',
        'PRO_WEBROOT'
    )),
    'MOD_WEBOTHEQUE_MUSIC' => CMS::getCurrentSite()->hasModule(new Module('MOD_WEBOTHEQUE_MUSIC')) && $oConnected->checkProfil(array(
        'PRO_WEBMUSIC',
        'PRO_WEBROOT'
    )),
    'MOD_WEBOTHEQUE_VIDEO' => CMS::getCurrentSite()->hasModule(new Module('MOD_WEBOTHEQUE_VIDEO')) && $oConnected->checkProfil(array(
        'PRO_WEBVIDEO',
        'PRO_WEBROOT'
    )),
    'MOD_WEBOTHEQUE_VIDEOEXTERNE' => CMS::getCurrentSite()->hasModule(new Module('MOD_WEBOTHEQUE_VIDEOEXTERNE')) && $oConnected->checkProfil(array(
        'PRO_WEBVIDEOEXTERNE',
        'PRO_WEBROOT'
    )),
    'MOD_WEBOTHEQUE_WIDGET' => CMS::getCurrentSite()->hasModule(new Module('MOD_WEBOTHEQUE_WIDGET')) && $oConnected->checkProfil(array(
        'PRO_WEBWIDGET',
        'PRO_WEBROOT'
    ))
);
$a = array();

// Tableau de bord
$child = array();
$child['VE'] = array(
    'label' => "Vue d'ensemble",
    'url' => SERVER_ROOT . 'cms/cms_index.php'
);
$_child = array();
$_child['CTB'] = array(
    'label' => 'Activité des contributeurs',
    'url' => SERVER_ROOT . 'statistique/stat_utilisateur.php'
);
$_child['CTN'] = array(
    'label' => 'Activité par type de contenu',
    'url' => SERVER_ROOT . 'statistique/stat_contenu.php'
);
$_child['VST'] = array(
    'label' => 'Statistiques des visites',
    'url' => SERVER_ROOT . 'statistique/stat_visites.php'
);
$child['SSC'] = array(
    'label' => 'Statistiques ' . CMS::getCurrentSite()->getField('SIT_LIBELLE'),
    'url' => SERVER_ROOT . 'statistique/stat_index.php',
    'child' => $_child
);
if ($oConnected->isRoot(true) && count($oConnected->getSites()) > 1) {
    $_child = array();
    $_child['CTB'] = array(
        'label' => 'Activité des contributeurs',
        'url' => SERVER_ROOT . 'statistique/stat_multiutilisateur.php'
    );
    $_child['CTN'] = array(
        'label' => 'Activité par type de contenu',
        'url' => SERVER_ROOT . 'statistique/stat_multicontenu.php'
    );
    $_child['VST'] = array(
        'label' => 'Statistiques des visites',
        'url' => SERVER_ROOT . 'statistique/stat_multiVisites.php'
    );
    $child['SMS'] = array(
        'label' => 'Statistiques multi-sites',
        'url' => SERVER_ROOT . 'statistique/stat_multiindex.php',
        'child' => $_child
    );
}
$a['TDB'] = array(
    'modeN3' => true,
    'stop' => true,
    'label' => 'Tableau de bord',
    'child' => $child,
    'url' => SERVER_ROOT . 'cms/cms_index.php'
);

// Contenu
$child = array();
$child['ARBO'] = array(
    'label' => 'Arborescence',
    'url' => SERVER_ROOT . 'cms/cms_pageArbo.php'
);
$_child = array();
$sql = "select count(ID_PAGE) as NB, PST_CODE from OFF_PAGE where SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID()) . " group by PST_CODE";
$tabPageStatus = array();
foreach ($dbh->query($sql) as $rowTemp) {
    $tabPageStatus[$rowTemp['PST_CODE']] = $rowTemp['NB'];
}
$sql = "select * from DD_PAGESTATUT order by PST_POIDS";
foreach ($dbh->query($sql) as $rowTemp) {
    $_child[$rowTemp['PST_CODE']] = array(
        'label' => extraireLibelle($rowTemp['PST_LIBELLE']) . ' (' . intval($tabPageStatus[$rowTemp['PST_CODE']]) . ')',
        'url' => SERVER_ROOT . 'cms/cms_pageListe.php?PST_CODE=' . $rowTemp['PST_CODE'] . '&amp;Find=1'
    );
}
$child['PAGE'] = array(
    'label' => 'Liste des pages',
    'url' => SERVER_ROOT . 'cms/cms_pageListe.php',
    'child' => $_child
);
$a['CTN'] = array(
    'modeN3' => true,
    'label' => 'Pages',
    'url' => SERVER_ROOT . 'cms/cms_pageArbo.php',
    'child' => $child
);

// Webotheque
if ($enabled['MOD_WEBOTHEQUE_DOCUMENT'] || $enabled['MOD_WEBOTHEQUE_FLASH'] || $enabled['MOD_WEBOTHEQUE_IMAGE'] || $enabled['MOD_WEBOTHEQUE_LIENEXTERNE'] || $enabled['MOD_WEBOTHEQUE_MUSIC'] || $enabled['MOD_WEBOTHEQUE_LIENVIDEO'] || $enabled['MOD_WEBOTHEQUE_LIENVIDEOEXTERNE']) {
    $child = array();
    if ($enabled['MOD_WEBOTHEQUE_IMAGE']) {
        $_child = $__child = array();
        $__child['LISTE'] = array(
            'label' => gettext('Lister'),
            'url' => SERVER_ROOT . 'webotheque/web_imageListe.php'
        );
        $__child['ADD'] = array(
            'label' => gettext('Ajouter'),
            'url' => SERVER_ROOT . 'webotheque/web_image.php'
        );
        $__child['MULTI'] = array(
            'label' => 'Import multiple',
            'url' => SERVER_ROOT . 'webotheque/web_importMultiple.php?WBT_CODE=WBT_IMAGE'
        );
        if ($oConnected->checkProfil(array(
            'PRO_WEBROOT'
        ))) {
            $__child['FTP'] = array(
                'label' => 'Import FTP',
                'url' => SERVER_ROOT . 'webotheque/web_importFTP.php?WBT_CODE=WBT_IMAGE'
            );
        }
        $_child['IMAGE'] = array(
            'hideMenu' => true,
            'label' => 'Images',
            'url' => SERVER_ROOT . 'webotheque/web_imageListe.php',
            'child' => $__child
        );
        $_child['CAT'] = array(
            'label' => 'Dossiers',
            'url' => SERVER_ROOT . 'webotheque/web_categorieListe.php?WBT_CODE=WBT_IMAGE'
        );
        $child['MOD_WEBOTHEQUE_IMAGE'] = array(
            'label' => 'Images',
            'url' => SERVER_ROOT . 'webotheque/web_imageListe.php',
            'child' => $_child
        );
    }
    if ($enabled['MOD_WEBOTHEQUE_DOCUMENT']) {
        $_child = $__child = array();
        $__child['LISTE'] = array(
            'label' => gettext('Lister'),
            'url' => SERVER_ROOT . 'webotheque/web_documentListe.php'
        );
        $__child['ADD'] = array(
            'label' => gettext('Ajouter'),
            'url' => SERVER_ROOT . 'webotheque/web_document.php'
        );
        $__child['MULTI'] = array(
            'label' => 'Import multiple',
            'url' => SERVER_ROOT . 'webotheque/web_importMultiple.php?WBT_CODE=WBT_DOCUMENT'
        );
        if ($oConnected->checkProfil(array(
            'PRO_WEBROOT'
        ))) {
            $__child['FTP'] = array(
                'label' => 'Import FTP',
                'url' => SERVER_ROOT . 'webotheque/web_importFTP.php?WBT_CODE=WBT_DOCUMENT'
            );
        }
        $_child['DOCUMENT'] = array(
            'hideMenu' => true,
            'label' => 'Documents',
            'url' => SERVER_ROOT . 'webotheque/web_documentListe.php',
            'child' => $__child
        );
        $_child['CAT'] = array(
            'label' => 'Dossiers',
            'url' => SERVER_ROOT . 'webotheque/web_categorieListe.php?WBT_CODE=WBT_DOCUMENT'
        );
        $child['MOD_WEBOTHEQUE_DOCUMENT'] = array(
            'label' => 'Documents',
            'url' => SERVER_ROOT . 'webotheque/web_documentListe.php',
            'child' => $_child
        );
    }
    if ($enabled['MOD_WEBOTHEQUE_LIENEXTERNE']) {
        $_child = $__child = array();
        $__child['LISTE'] = array(
            'label' => gettext('Lister'),
            'url' => SERVER_ROOT . 'webotheque/web_lienExterneListe.php'
        );
        $__child['ADD'] = array(
            'label' => gettext('Ajouter'),
            'url' => SERVER_ROOT . 'webotheque/web_lienExterne.php'
        );
        $_child['LIENEXTERNE'] = array(
            'hideMenu' => true,
            'label' => 'Liens externes',
            'url' => SERVER_ROOT . 'webotheque/web_lienExterneListe.php',
            'child' => $__child
        );
        $_child['CAT'] = array(
            'label' => 'Dossiers',
            'url' => SERVER_ROOT . 'webotheque/web_categorieListe.php?WBT_CODE=WBT_LIENEXTERNE'
        );
        $child['MOD_WEBOTHEQUE_LIENEXTERNE'] = array(
            'label' => 'Liens externes',
            'url' => SERVER_ROOT . 'webotheque/web_lienExterneListe.php',
            'child' => $_child
        );
    }
    if ($enabled['MOD_WEBOTHEQUE_MUSIC']) {
        $_child = $__child = array();
        $__child['LISTE'] = array(
            'label' => gettext('Lister'),
            'url' => SERVER_ROOT . 'webotheque/web_musicListe.php'
        );
        $__child['ADD'] = array(
            'label' => gettext('Ajouter'),
            'url' => SERVER_ROOT . 'webotheque/web_music.php'
        );
        if ($oConnected->checkProfil(array(
            'PRO_WEBROOT'
        ))) {
            $__child['FTP'] = array(
                'label' => 'Import FTP',
                'url' => SERVER_ROOT . 'webotheque/web_importFTP.php?WBT_CODE=WBT_MUSIC'
            );
        }
        $_child['MUSIC'] = array(
            'hideMenu' => true,
            'label' => 'Audios',
            'url' => SERVER_ROOT . 'webotheque/web_musicListe.php',
            'child' => $__child
        );
        $_child['CAT'] = array(
            'label' => 'Dossiers',
            'url' => SERVER_ROOT . 'webotheque/web_categorieListe.php?WBT_CODE=WBT_MUSIC'
        );
        $child['MOD_WEBOTHEQUE_MUSIC'] = array(
            'label' => 'Audios',
            'url' => SERVER_ROOT . 'webotheque/web_musicListe.php',
            'child' => $_child
        );
    }
    if ($enabled['MOD_WEBOTHEQUE_FLASH']) {
        $_child = $__child = array();
        $__child['LISTE'] = array(
            'label' => gettext('Lister'),
            'url' => SERVER_ROOT . 'webotheque/web_flashListe.php'
        );
        $__child['ADD'] = array(
            'label' => gettext('Ajouter'),
            'url' => SERVER_ROOT . 'webotheque/web_flash.php'
        );
        if ($oConnected->checkProfil(array(
            'PRO_WEBROOT'
        ))) {
            $__child['FTP'] = array(
                'label' => 'Import FTP',
                'url' => SERVER_ROOT . 'webotheque/web_importFTP.php?WBT_CODE=WBT_FLASH'
            );
        }
        $_child['FLASH'] = array(
            'hideMenu' => true,
            'label' => 'Flashs',
            'url' => SERVER_ROOT . 'webotheque/web_flashListe.php',
            'child' => $__child
        );
        $_child['CAT'] = array(
            'label' => 'Dossiers',
            'url' => SERVER_ROOT . 'webotheque/web_categorieListe.php?WBT_CODE=WBT_FLASH'
        );
        $child['MOD_WEBOTHEQUE_FLASH'] = array(
            'label' => 'Flashs',
            'url' => SERVER_ROOT . 'webotheque/web_flashListe.php',
            'child' => $_child
        );
    }
    if ($enabled['MOD_WEBOTHEQUE_VIDEO']) {
        $_child = $__child = array();
        $__child['LISTE'] = array(
            'label' => gettext('Lister'),
            'url' => SERVER_ROOT . 'webotheque/web_videoListe.php'
        );
        $__child['ADD'] = array(
            'label' => gettext('Ajouter'),
            'url' => SERVER_ROOT . 'webotheque/web_video.php'
        );
        if ($oConnected->checkProfil(array(
            'PRO_WEBROOT'
        ))) {
            $__child['FTP'] = array(
                'label' => 'Import FTP',
                'url' => SERVER_ROOT . 'webotheque/web_importFTP.php?WBT_CODE=WBT_VIDEO'
            );
        }
        $_child['VIDEO'] = array(
            'hideMenu' => true,
            'label' => 'Vidéos',
            'url' => SERVER_ROOT . 'webotheque/web_videoListe.php',
            'child' => $__child
        );
        $_child['CAT'] = array(
            'label' => 'Dossiers',
            'url' => SERVER_ROOT . 'webotheque/web_categorieListe.php?WBT_CODE=WBT_VIDEO'
        );
        $child['MOD_WEBOTHEQUE_VIDEO'] = array(
            'label' => 'Vidéos',
            'url' => SERVER_ROOT . 'webotheque/web_videoListe.php',
            'child' => $_child
        );
    }
    if ($enabled['MOD_WEBOTHEQUE_VIDEOEXTERNE']) {
        $_child = $__child = array();
        $__child['LISTE'] = array(
            'label' => gettext('Lister'),
            'url' => SERVER_ROOT . 'webotheque/web_videoExterneListe.php'
        );
        $__child['ADD'] = array(
            'label' => gettext('Ajouter'),
            'url' => SERVER_ROOT . 'webotheque/web_videoExterne.php'
        );
        $_child['VIDEOEXTERNE'] = array(
            'hideMenu' => true,
            'label' => 'Vidéos externes',
            'url' => SERVER_ROOT . 'webotheque/web_videoExterneListe.php',
            'child' => $__child
        );
        $_child['CAT'] = array(
            'label' => 'Dossiers',
            'url' => SERVER_ROOT . 'webotheque/web_categorieListe.php?WBT_CODE=WBT_VIDEOEXTERNE'
        );
        $child['MOD_WEBOTHEQUE_VIDEOEXTERNE'] = array(
            'label' => 'Vidéos externes',
            'url' => SERVER_ROOT . 'webotheque/web_videoExterneListe.php',
            'child' => $_child
        );
    }
    if ($enabled['MOD_WEBOTHEQUE_WIDGET']) {
        $_child = $__child = array();
        $__child['LISTE'] = array(
            'label' => gettext('Lister'),
            'url' => SERVER_ROOT . 'webotheque/web_widgetListe.php'
        );
        $__child['ADD'] = array(
            'label' => gettext('Ajouter'),
            'url' => SERVER_ROOT . 'webotheque/web_widget.php'
        );
        $_child['WIDGET'] = array(
            'hideMenu' => true,
            'label' => 'Widgets',
            'url' => SERVER_ROOT . 'webotheque/web_widgetListe.php',
            'child' => $__child
        );
        $_child['CAT'] = array(
            'label' => 'Dossiers',
            'url' => SERVER_ROOT . 'webotheque/web_categorieListe.php?WBT_CODE=WBT_WIDGET'
        );
        $child['MOD_WEBOTHEQUE_WIDGET'] = array(
            'label' => 'Widgets',
            'url' => SERVER_ROOT . 'webotheque/web_widgetListe.php',
            'child' => $_child
        );
    }
    $a['WEB'] = array(
        'label' => 'Webothèque',
        'url' => SERVER_ROOT . 'webotheque/web_index.php',
        'child' => $child
    );
}

if ($enabled['MOD_FORMULAIRE']) {
    $child = $_child = array();
    $_child['LISTE'] = array(
        'label' => gettext('Lister'),
        'url' => SERVER_ROOT . 'formulaire/frm_formulaireListe.php'
    );
    if ($oConnected->checkProfil(array(
        'PRO_FORMGEST'
    ))) {
        $_child['ADD'] = array(
            'label' => gettext('Ajouter'),
            'url' => SERVER_ROOT . 'formulaire/frm_formulaire.php'
        );
    }
    $child['MOD_FORMULAIRE'] = array(
        'stop' => true,
        'label' => 'Formulaires dynamiques',
        'url' => SERVER_ROOT . 'formulaire/frm_formulaireListe.php',
        'child' => $_child
    );
    if ($oConnected->checkProfil(array(
        'PRO_FORMGEST'
    ))) {
        $child['CAT'] = array(
            'label' => 'Dossiers',
            'url' => SERVER_ROOT . 'formulaire/frm_categorieListe.php'
        );
    }
    $a['FRM'] = array(
        'modeN3' => true,
        'label' => 'Formulaires',
        'url' => SERVER_ROOT . 'formulaire/frm_formulaireListe.php',
        'child' => $child
    );
}

// Module
$child = array();
include 'inc.bo_bandeau_haut_ext.php';
$a['MDL'] = array(
    'label' => 'Modules',
    'labelBis' => 'Module',
    'child' => $child
);

// Configuration
$child = array();
if ($oConnected->isRoot() || $enabled['MOD_ABREVIATION'] || $enabled['MOD_LANGUISME'] || $enabled['MOD_TRADUCTION'] || $enabled['MOD_RECHERCHE'] || $enabled['MOD_THEMATIQUE'] || $enabled['MOD_REFERENCEMENT'] || $enabled['MOD_COMMENTAIRE']) {
    $_child = $__child = array();
    if ($oConnected->isRoot()) {
        $_child['ALERTE'] = array(
            'label' => 'Alerte',
            'url' => SERVER_ROOT . 'cms/administration/adm_alerte.php?idtf=' . CMS::getCurrentSite()->getID(),
            'txt' => "Affichez un message sur le tableau de bord du back-office, il sera visible par l’ensemble des contributeurs de votre site. Insérez une date de blocage : les contributeurs du site verront le message, mais le site ne leur sera plus accessible, seuls les administrateurs du site et le(s) super administrateur(s) pourront y accéder."
        );
    }
    if ($enabled['MOD_COMMENTAIRE']) {
        $__child = array();
        $__child['LISTE'] = array(
            'label' => gettext('Lister'),
            'url' => SERVER_ROOT . 'cms/cms_commentaireListe.php'
        );
        if ($oConnected->isRoot()) {
            $__child['PARAM'] = array(
                'label' => 'Paramètres',
                'url' => SERVER_ROOT . 'cms/cms_commentaireParam.php'
            );
        }
        $_child['MOD_COMMENTAIRE'] = array(
            'label' => 'Commentaires',
            'url' => SERVER_ROOT . 'cms/cms_commentaireListe.php',
            'child' => $__child
        );
    }
    if ($enabled['MOD_ABREVIATION']) {
        $__child = array();
        $__child['LISTE'] = array(
            'label' => gettext('Lister'),
            'url' => SERVER_ROOT . 'cms/cms_formeAbregeeListe.php'
        );
        $__child['ADD'] = array(
            'label' => gettext('Ajouter'),
            'url' => SERVER_ROOT . 'cms/cms_formeAbregee.php'
        );
        $_child['MOD_ABREVIATION'] = array(
            'label' => 'Formes abrégées',
            'url' => SERVER_ROOT . 'cms/cms_formeAbregeeListe.php',
            'txt' => "Gérer les acronymes et abréviations de votre site (ex : SNCF, CAF,..). Insérez ensuite ces formes abrégées sur les mots concernés dans vos paragraphes rédactionnels. La signification sera visible au survol de la souris sur le mot concerné.",
            'child' => $__child
        );
    }
    if ($enabled['MOD_LANGUISME']) {
        $__child = array();
        $__child['LISTE'] = array(
            'label' => gettext('Lister'),
            'url' => SERVER_ROOT . 'cms/cms_languismeListe.php'
        );
        $__child['ADD'] = array(
            'label' => gettext('Ajouter'),
            'url' => SERVER_ROOT . 'cms/cms_languisme.php'
        );
        $_child['MOD_LANGUISME'] = array(
            'label' => 'Languismes',
            'url' => SERVER_ROOT . 'cms/cms_languismeListe.php',
            'child' => $__child,
            'txt' => "Gérez les mots étrangers de votre site (ex : « brief », « week-end »,…). Insérez sur les mots concernés dans vos paragraphes rédactionnels. La traduction sera visible au survol de la souris sur le mot concerné."
        );
    }
    if ($enabled['MOD_REFERENCEMENT']) {
        $__child = array();
        $__child['LISTE'] = array(
            'label' => gettext('Lister'),
            'url' => SERVER_ROOT . 'cms/cms_rechercheReferencementListe.php'
        );
        $__child['ADD'] = array(
            'label' => gettext('Ajouter'),
            'url' => SERVER_ROOT . 'cms/cms_rechercheReferencement.php'
        );
        $_child['MOD_REFERENCEMENT'] = array(
            'label' => 'Recherche DMK',
            'url' => SERVER_ROOT . 'cms/cms_rechercheReferencementListe.php',
            'child' => $__child
        );
    }
    if ($enabled['MOD_RECHERCHE']) {
        $__child = array();
        $__child['LISTE'] = array(
            'label' => gettext('Lister'),
            'url' => SERVER_ROOT . 'cms/cms_stopwordListe.php'
        );
        $__child['ADD'] = array(
            'label' => gettext('Ajouter'),
            'url' => SERVER_ROOT . 'cms/cms_stopword.php'
        );
        if ($oConnected->checkProfil(array(
            'PRO_ROOT_SITE'
        )) && sizeof($oConnected->getSites()) > 1) {
            $__child['MULTI'] = array(
                'label' => 'Multi-sites',
                'url' => SERVER_ROOT . 'cms/cms_recherche.php'
            );
        }
        $_child['MOD_RECHERCHE'] = array(
            'label' => 'Stopwords',
            'url' => SERVER_ROOT . 'cms/cms_stopwordListe.php',
            'child' => $__child,
            'txt' => "Gérez la liste des mots qui ne doivent pas être pris en compte par le moteur de recherche du site."
        );
    }
    if ($enabled['MOD_TRADUCTION']) {
        $_child['MOD_TRADUCTION'] = array(
            'label' => 'Traductions',
            'url' => SERVER_ROOT . 'cms/cms_traductionListe.php',
            'txt' => "Gérez les libellés utilisés dans les templates de vos modules, ou dans des éléments du noyau. Modifiez ainsi facilement le libellé d’un bouton ou d’un champ."
        );
    }
    if ($enabled['MOD_THEMATIQUE']) {
        $__child = array();
        $__child['LISTE'] = array(
            'label' => gettext('Lister'),
            'url' => SERVER_ROOT . 'cms/cms_thematiqueListe.php'
        );
        $__child['ADD'] = array(
            'label' => gettext('Ajouter'),
            'url' => SERVER_ROOT . 'cms/cms_thematique.php'
        );
        $_child['MOD_THEMATIQUE'] = array(
            'label' => 'Thématiques',
            'url' => SERVER_ROOT . 'cms/cms_thematiqueListe.php',
            'child' => $__child,
            'txt' => "Votre site utilise la notion de thématiques (affectées à des pages, des modules,…) ? Gérez ici les libellés de ces thématiques."
        );
    }
    if ($oConnected->isRoot(true)) {
        $url = SERVER_ROOT . 'cms/administration/adm_site.php?idtf=' . CMS::getCurrentSite()->getID();
    } else {
        $temp = reset($_child);
        $url = $temp['url'];
    }
    $child['SITE'] = array(
        'label' => CMS::getCurrentSite()->getField('SIT_LIBELLE'),
        'url' => $url,
        'child' => $_child
    );
}
if ($oConnected->isRoot(true)) {
    $_child = $__child = array();
    $_child['ALERTE'] = array(
        'label' => 'Alerte',
        'url' => SERVER_ROOT . 'cms/administration/adm_alerte.php',
        'txt' => "Affichez un message sur le tableau de bord du back-office, il sera visible par l’ensemble des contributeurs de tous vos sites. Insérez une date de blocage : les contributeurs de la plateforme verront le message, mais le site ne leur sera plus accessible, seuls le(s) super administrateur(s) pourront y accéder."
    );
    if (extension_loaded('ldap')) {
        $__child = array();
        $__child['LISTE'] = array(
            'label' => gettext('Lister'),
            'url' => SERVER_ROOT . 'cms/administration/adm_ldapListe.php'
        );
        $__child['ADD'] = array(
            'label' => gettext('Ajouter'),
            'url' => SERVER_ROOT . 'cms/administration/adm_ldap.php'
        );
        $_child['LDAP'] = array(
            'label' => 'Annuaires LDAP',
            'url' => SERVER_ROOT . 'cms/administration/adm_ldapListe.php',
            'child' => $__child,
            'txt' => "Les utilisateurs de vos sites sont issus d’un annuaire LDAP ? Renseignez et paramétrez vos annuaires LDAP."
        );
    }
    $__child = array();
    $sql = "select * from DD_LANGUE where LNG_FO=1 order by LNG_LIBELLE";
    foreach ($dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $rowTemp) {
        $__child['LISTE_' . $rowTemp['LNG_CODE']] = array(
            'label' => gettext('Lister') . ' - ' . $rowTemp['LNG_LIBELLE'],
            'url' => SERVER_ROOT . 'cms/administration/adm_traductionListe.php?LNG_CODE=' . $rowTemp['LNG_CODE']
        );
    }
    $_child['TRADUCTION'] = array(
        'label' => 'Libellés',
        'url' => SERVER_ROOT . 'cms/administration/adm_traductionListe.php',
        'child' => $__child,
        'txt' => "Gérez les libellés utilisés dans les templates de vos modules, ou dans des éléments du noyau. Modifiez ainsi facilement le libellé d’un bouton ou d’un champ."
    );
    $_child['SITEMAP'] = array(
        'label' => 'Sitemap',
        'url' => SERVER_ROOT . 'cms/administration/adm_sitemap.php'
    );
    $__child = array();
    $__child['LISTE'] = array(
        'label' => gettext('Lister'),
        'url' => SERVER_ROOT . 'cms/administration/adm_siteListe.php'
    );
    $__child['ADD'] = array(
        'label' => gettext('Ajouter'),
        'url' => SERVER_ROOT . 'cms/administration/adm_site.php'
    );
    $__child['MODULE'] = array(
        'label' => 'Consulter modules',
        'url' => SERVER_ROOT . 'cms/administration/adm_siteModule.php'
    );
    $_child['SITE'] = array(
        'label' => 'Sites',
        'url' => SERVER_ROOT . 'cms/administration/adm_siteListe.php',
        'child' => $__child,
        'txt' => "Administrez l’ensemble de vos sites : créez un nouveau site et paramétrez-le (activez des modules, gérez le nom de domaine, ajoutez un marqueur Google Analytics, …)"
    );
    $__child = array();
    $sql = "select * from DD_LANGUE where LNG_FO=1 order by LNG_LIBELLE";
    foreach ($dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $rowTemp) {
        $__child['LISTE_' . $rowTemp['LNG_CODE']] = array(
            'label' => gettext('Lister') . ' - ' . $rowTemp['LNG_LIBELLE'],
            'url' => SERVER_ROOT . 'cms/administration/adm_stopwordListe.php?LNG_CODE=' . $rowTemp['LNG_CODE']
        );
        $__child['ADD_' . $rowTemp['LNG_CODE']] = array(
            'label' => gettext('Ajouter') . ' - ' . $rowTemp['LNG_LIBELLE'],
            'url' => SERVER_ROOT . 'cms/administration/adm_stopword.php?LNG_CODE=' . $rowTemp['LNG_CODE']
        );
    }
    $_child['STOPWORD'] = array(
        'label' => 'Stopwords',
        'url' => SERVER_ROOT . 'cms/administration/adm_stopwordListe.php',
        'child' => $__child,
        'txt' => "Gérez la liste des mots qui ne doivent pas être pris en compte par le moteur de recherche présent sur les sites de la plateforme."
    );
    $_child['TPL'] = array(
        'label' => 'Templates',
        'url' => SERVER_ROOT . 'cms/administration/adm_templateListe.php',
        'txt' => "Visualisez en un coup d'œil dans quelles pages sont intégrés les templates de vos modules ou les templates du noyau (formulaires dynamiques, plan du site, etc…). Paramétrez les propriétés de vos templates, notamment leur libellé dans les URLs de vos pages, afin que ces URLs soient plus parlantes pour les moteurs de recherche."
    );
    $_child['EMT'] = array(
        'stop' => true,
        'label' => "Templates d'email",
        'url' => SERVER_ROOT . 'cms/administration/adm_emailTemplateListe.php',
        'txt' => "Gérez les contenus des mails envoyés depuis votre site à vos contributeurs ou internautes. Paramétrez vos mails avec des clés qui personnaliseront vos mails."
    );
    $child['PTF'] = array(
        'label' => 'Plateforme',
        'url' => SERVER_ROOT . 'cms/administration/adm_siteListe.php',
        'child' => $_child
    );
}
if ($oConnected->isRoot()) {
    $_child = $__child = array();
    $__child['LISTE'] = array(
        'label' => gettext('Lister'),
        'url' => SERVER_ROOT . 'cms/administration/adm_utilisateurListe.php'
    );
    $__child['ADD'] = array(
        'label' => gettext('Ajouter'),
        'url' => SERVER_ROOT . 'cms/administration/adm_utilisateur.php?idtf=-1'
    );
    $_child['UTILISATEUR'] = array(
        'hideMenu' => true,
        'label' => 'Utilisateurs',
        'url' => SERVER_ROOT . 'cms/administration/adm_utilisateurListe.php',
        'child' => $__child
    );
    if (CMS::getCurrentSite()->hasModule(new Module('MOD_EXTRANET'))) {
        $__child = array();
        $__child['LISTE'] = array(
            'label' => gettext('Lister'),
            'url' => SERVER_ROOT . 'cms/administration/adm_groupeListe.php'
        );
        $__child['ADD'] = array(
            'label' => gettext('Ajouter'),
            'url' => SERVER_ROOT . 'cms/administration/adm_groupe.php'
        );
        $_child['GROUPE'] = array(
            'label' => 'Groupes',
            'url' => SERVER_ROOT . 'cms/administration/adm_groupeListe.php',
            'child' => $__child
        );
    }
    $child['USER'] = array(
        'stop' => count($_child) < 2,
        'label' => 'Utilisateurs',
        'url' => SERVER_ROOT . 'cms/administration/adm_utilisateurListe.php',
        'txt' => "Gérez les utilisateurs back-office et front-office de votre site : administrez leur profil, affectez-leur des rôles sur les pages et les modules, gérez des groupes d’utilisateurs",
        'child' => $_child
    );
}
if (! empty($child)) {
    $a['CFG'] = array(
        'label' => 'Configuration',
        'child' => $child
    );
}
?>
<div id="bo_bandeau_haut">
    <div id="bo_bandeau_haut_menu">
        <ul class="N1">
            <li class="picto"><a href="<?php echo SERVER_ROOT?>cms/cms_pseudo.php"<?php if ($aMenuKey[0] == 'HOME') echo ' class="selected"'?>><img src="<?php echo SERVER_ROOT?>images/homeBO.png" alt="Home"></a></li>
            <li class="sitelibelle">
                <?php if (count($oConnected->getSites()) > 1) {?>
                <select name="SIT_CODE_HAUT" onchange="window.location.href='<?php echo SERVER_ROOT ?>cms/cms_pageArbo.php?from=<?php echo urlencode(PHP_SELF)?>&amp;SIT_CODE=' + this.value;">
                  <?php foreach ($oConnected->getSites(true) as $_SIT_CODE => $_SIT_LIBELLE) {?>
                  <option value="<?php echo $_SIT_CODE?>"<?php if (CMS::getCurrentSite()->getID() == $_SIT_CODE) echo ' selected'?>><?php echo secureInput($_SIT_LIBELLE)?></option>
                  <?php } ?>
                </select>
                <?php } else { echo '<span>' . secureInput(CMS::getCurrentSite()->getField('SIT_LIBELLE')) . '</span>'; }?>
            </li>
            <?php $i = 1; foreach ($a as $keyN1=>$itemN1) { ?>
            <li<?php if ($i++ == count($a)) echo ' class="last"'?>>
                <a href="<?php echo $itemN1['url'] ? $itemN1['url'] : SERVER_ROOT . 'cms/cms_menu.php?N1=' . $keyN1?>"<?php if ($aMenuKey[0] == $keyN1) echo ' class="selected"'?>><span><?php echo secureInput($itemN1['label'])?></span></a>
                <?php if ($itemN1['child'] && !$itemN1['stop']) { ?>
                <ul class="N2">
                    <?php foreach ($itemN1['child'] as $keyN2=>$itemN2) {?>
                    <li <?php if ($itemN2['child'] && !$itemN2['stop']) echo 'class="parent"'?>>
                        <a href="<?php echo $itemN2['url'] ? $itemN2['url'] : SERVER_ROOT . 'cms/cms_menu.php?N1=' . $keyN1 . '&amp;N2=' . $keyN2 ?>"><?php echo secureInput($itemN2['label'])?></a>
                        <?php if ($itemN2['child'] && !$itemN2['stop']) { ?>
                        <ul class="N3">
                            <?php foreach ($itemN2['child'] as $itemN3) {
                                if ($itemN3['hideMenu']) {continue;} ?>
                                    <li>
                                        <a href="<?php echo $itemN3['url']?>"><?php echo $itemN3['label']?></a>
                                    </li>
                            <?php } ?>
                        </ul>
                        <?php } ?>
                    </li>
                    <?php } ?>
                </ul>
                <?php } ?>
            </li>
            <?php } ?>
            <li class="picto">
                <a href="<?php echo SERVER_ROOT?>cms/administration/adm_utilisateur.php" title="Modifier mon profil"><img src="<?php echo SERVER_ROOT?>images/compte.png" alt=""></a>
                <ul class="N2">
                    <li>
                        <a href="<?php echo SERVER_ROOT?>cms/administration/adm_utilisateur.php">
                            <?php echo secureInput($oConnected->getNom())?>
                            <?php if ($oConnected->getField('UTI_PRELASTCONNEXION')) { ?>
                            <br>Dernière connexion le <?php echo date('d/m/Y \à H:i', $oConnected->getField('UTI_PRELASTCONNEXION'))?>
                            <?php } ?>
                        </a>
                    </li>
                    <li>
                        <?php if (! isset($_COOKIE['C_ancrageMenu'])) { ?>
                        <a href="<?php echo SERVER_ROOT?>cms/cms_index.php?ancrageMenu=1">Ne plus figer le menu</a>
                        <?php } else { ?>
                        <a href="<?php echo SERVER_ROOT?>cms/cms_index.php?ancrageMenu=0">Figer le menu</a>
                        <?php } ?>
                    </li>
                </ul>
            </li>
            <li class="picto"><a href="<?php echo SIT_SUPPORT ?><?php if (CMS::getCurrentSite()->getField('SIT_SUPPORTKEY') != '') echo 'index.php?key=' . CMS::getCurrentSite()->getField('SIT_SUPPORTKEY')?>" title="Aide &amp; Support" onclick="window.open(this.href); return false;"><img src="<?php echo SERVER_ROOT?>images/help.png" alt=""></a></li>
            <li class="picto"><a href="<?php echo SERVER_ROOT?>cms/index.php?logout=1" title="Déconnexion"><img src="<?php echo SERVER_ROOT?>images/cancel.png" alt=""></a></li>
        </ul>
    </div>
<?php
if (count($aMenuKey) > 1 && isset($a[$aMenuKey[0]])) {
    $itemN1 = $a[$aMenuKey[0]];
    $itemN2 = $itemN1['child'][$aMenuKey[1]];
    if ($itemN1['modeN3']) {
        $itemMOT = $itemN1;
        $itemACTION = $itemN2;
        $indice = 1;
    } else {
        $itemMOT = $itemN2;
        $itemACTION = $itemN2['child'][$aMenuKey[2]];
        $indice = 2;
    }
    ?>
    <div id="bo_bandeau_haut_sousmenu">
        <ul>
            <li class="home">
                <?php echo secureInput($itemN1['labelBis'] ? $itemN1['labelBis'] : $itemN1['label'])?>
                <?php if (!$itemN1['modeN3']) echo secureInput($itemN2['label'])?>
            </li>
            <?php if (is_array($itemMOT['child'])) {?>
                <?php foreach ($itemMOT['child'] as $key => $item) { ?>
                    <li>
                        <a href="<?php echo $item['url']?>"<?php if ($aMenuKey[$indice] == $key) echo ' class="selected"'?>><?php echo secureInput($item['label'])?></a>
                    </li>
                <?php } ?>
            <?php } ?>
        </ul>
    </div>
    <?php if (is_array($itemACTION['child'])) {?>
    <div id="bo_bandeau_haut_sousmenuaction">
        <ul>
            <?php foreach ($itemACTION['child'] as $key => $item) { ?>
            <li>
                <a href="<?php echo $item['url']?>"<?php if ($aMenuKey[$indice + 1] == $key) echo ' class="selected"'?>><?php echo secureInput($item['label'])?></a>
            </li>
            <?php } ?>
        </ul>
    </div>
    <?php } ?>
<?php  } //fin sous menu ?>

<?php
$_page = basename(PHP_SELF);
if ($_page == 'cms_pseudo.php' || $_page == 'cms_page.php') {
    if (CMS::$edition && ($oPage->isLocked() || $oConnected->isSEO()) || $oPage->isRevision()) {
        CMS::$edition = false;
    }
    if ($oPage->checkAuthorized(false)) { ?>
    <div id="bo_menu_haut_action">
        <div id="bo_menu_haut_action_title">
            <a href="javascript:hideAction()"><img src="<?php echo SERVER_ROOT?>images/pictoLegend.png" alt=""><span>Action</span></a>
        </div>
        <div id="bo_menu_haut_action_inner">
        <?php if (!$oPage->isRevision()) { ?>
            <h5>Contribution</h5>
            <div class="bo_menu_haut_action_bloc">
                <?php if (!$oConnected->isSEO()) { ?>
                     <?php if (CMS::$edition) {?>
                        <a class="actionVoir" href="cms_pseudo.php?idtf=<?php echo $oPage->getID()?>"><?php echo gettext('Visualiser')?></a>
                    <?php } elseif (!$oPage->isLocked()) { ?>
                        <?php if ($_page == 'cms_page.php') {?>
                            <a class="actionVoir" href="cms_pseudo.php?idtf=<?php echo $oPage->getID()?>"><?php echo gettext('Visualiser')?></a>
                        <?php } ?>
                        <a class="actionEditer" href="cms_pseudo.php?idtf=<?php echo $oPage->getID()?>&amp;PFM=1"><?php echo gettext('Editer')?></a>
                    <?php } ?>
                <?php } elseif ($_page == 'cms_page.php') { ?>
                    <a class="actionVoir" href="cms_pseudo.php?idtf=<?php echo $oPage->getID()?>"><?php echo gettext('Visualiser')?></a>
                <?php } ?>
            </div>

            <h5>Action</h5>
            <div class="bo_menu_haut_action_bloc">
                <?php if (!$oConnected->isSEO()) { ?>
                    <a class="actionAjouter" href="cms_pageAjout.php?idtf=<?php echo $oPage->getID()?>"><?php echo gettext('Ajouter une page')?></a>
                    <?php if ($_page == 'cms_pseudo.php' && !$oPage->isLocked()) { ?>
                    <a class="actionProprietes" href="cms_page.php?idtf=<?php echo $oPage->getID()?>"><?php echo gettext('Proprietes')?></a>
                    <a class="actionProprietesSimplifiees popup" href="cms_pageLightPopup.php?idtf=<?php echo $oPage->getID()?>&amp;PFM=<?php echo intval(CMS::$edition)?>"><?php echo gettext('Proprietes simplifiees')?></a>
                    <?php } ?>
                <?php } elseif ($_page != 'cms_page.php') { ?>
                    <a class="actionProprietes" href="cms_page.php?idtf=<?php echo $oPage->getID()?>"><?php echo gettext('Proprietes')?></a>
                <?php } ?>
            </div>

            <h5><?php echo gettext('Workflow')?></h5>
            <div class="bo_menu_haut_action_bloc workflow">
                <p><?php echo gettext('Etat courant')?> : <span class="<?php echo $oPage->getField('PST_CODE')?>"><?php echo extraireLibelle($oPage->getStatut())?></span></p>
                <?php
                $sql = "select PST_CODE_OUT, WORKFLOW.* from WORKFLOW where PST_CODE_IN=" . $dbh->quote($oPage->getField('PST_CODE')) . " and WKF_PROFIL regexp " . $dbh->quote('@' . implode('@|@', $oConnected->getProfils($oPage->getID())) . '@') . " order by WKF_POIDS";
                $aWORKFLOW = $dbh->query($sql)->fetchAll(PDO :: FETCH_ASSOC | PDO :: FETCH_GROUP | PDO :: FETCH_UNIQUE);
                if (isset($aWORKFLOW['PST_ENLIGNE'])) {?>
                <p><?php echo gettext('Etat suivant')?> : <a href="cms_pageSubmit.php?idtf=<?php echo $oPage->getID()?>&amp;ID_WORKFLOW=<?php echo $aWORKFLOW['PST_ENLIGNE']['ID_WORKFLOW']?>&amp;from=<?php echo $_page?>"><span class="PST_ENLIGNE"><?php echo extraireLibelle($aWORKFLOW['PST_ENLIGNE']['WKF_LIBELLE'])?></span></a></p>
                <p><?php echo gettext('ou')?> : <a href="cms_pageSubmit.php?idtf=<?php echo $oPage->getID()?>&amp;ID_WORKFLOW=<?php echo $aWORKFLOW['PST_ENLIGNE']['ID_WORKFLOW']?>&amp;from=<?php echo $_page?>&amp;majDate=1"><span class="PST_ENLIGNE"><?php /*echo extraireLibelle($aWORKFLOW['PST_ENLIGNE']['WKF_LIBELLE'])*/?> Plus date mise à jour</span></a></p>
                <?php
                } elseif (isset($aWORKFLOW['PST_AVALIDER'])) {?>
                <p><?php echo gettext('Etat suivant')?> : <a href="cms_pageSubmit.php?idtf=<?php echo $oPage->getID()?>&amp;ID_WORKFLOW=<?php echo $aWORKFLOW['PST_AVALIDER']['ID_WORKFLOW']?>&amp;from=<?php echo $_page?>"><span class="PST_AVALIDER"><?php echo extraireLibelle($aWORKFLOW['PST_AVALIDER']['WKF_LIBELLE'])?></span></a></p>
                <?php
                } ?>
            </div>

            <h5><?php echo gettext('Information')?></h5>
            <div class="bo_menu_haut_action_bloc">
                <?php
                $oPageOn = new Page($oPage->getID(), 'ON_');
                if ($oPageOn->exist()) {
                    $urlPgEnLigne = $oPageOn->getAnchor();
                    $urlPgEnLigne = str_ireplace(' class="external"', '', $urlPgEnLigne);
                ?>
                <a class="actionVoir" <?php echo $urlPgEnLigne?> onclick="window.open(this.href); return false;"><?php echo gettext('Voir la page en ligne')?></a>
                <?php
                } ?>
                <a class="actionHistorique popup" href="cms_historiquePopup.php?idtf=<?php echo $oPage->getID()?>"><?php echo gettext('Historique et revisions')?></a>
                <a class="actionUtilisateur popup" href="administration/adm_utilisateurPopup.php?idtf=<?php echo $oPage->getID()?>"><?php echo gettext('Utilisateurs')?></a>
                <a class="actionAnalyse popup" href="cms_analyseContenuPopup.php?idtf=<?php echo $oPage->getID()?>"><?php echo gettext('Analyse du contenu')?></a>
            </div>
            <?php if (!$oConnected->isSEO()) { ?>
                <h5><?php echo gettext('Revision')?></h5>
                <div class="bo_menu_haut_action_bloc last">
                    <a class="actionCreerRevision" href="cms_revisionSubmit.php?CreateRevision=<?php echo $oPage->getID()?>"><?php echo gettext('Creer une revision')?></a>
                    <?php
                    $sqlRevNum = "select count(ID_REVISION) from REVISION where ID_PAGE = ".intval($oPage->getID());
                    if ($dbh->query($sqlRevNum)->fetchColumn() > 0) { ?>
                    <a class="actionGereRevision" href="cms_revisionListe.php?idtf=<?php echo $oPage->getID()?>"><?php echo gettext('Gerer les revisions')?></a>
                    <?php } ?>
                </div>
            <?php } ?>

            <?php } elseif (!$oConnected->isSEO()) { ?>
            <h5><?php echo gettext('Page courante')?></h5>
            <div class="bo_menu_haut_action_bloc last">
                <a class="actionVoir" href="cms_pseudo.php?idtf=<?php echo $oPage->getID()?>"><?php echo gettext('Visualiser')?></a>
                <?php if (!$oPage->isLocked()) { ?>
                <a class="actionEditer" href="cms_pseudo.php?idtf=<?php echo $oPage->getID()?>&amp;PFM=1"><?php echo gettext('Editer')?></a>
                <?php } ?>
                <?php if ($_page == 'cms_pseudo.php' && !$oPage->isLocked()) { ?>
                <a class="actionProprietes" href="cms_page.php?idtf=<?php echo $oPage->getID()?>"><?php echo gettext('Proprietes')?></a>
                <?php } ?>
            </div>
        <?php } ?>
            <script>
            $('#rev').change(function () { if ($(this).val() != '') { $('#formRevision').submit(); }});
            </script>
        </div>
    </div>
    <script>
        $(document).ready(hideAction_initialise);
        function hideAction_initialise()
        {
            if (readCookie('isActionHidden') == '1') {
                $('#bo_menu_haut_action_inner').css('display', 'none');
                $('#bo_menu_haut_action_title span').css('display', 'none');
            }
        }
        function hideAction()
        {
            var isActionHidden = readCookie('isActionHidden');
            var val = '';
            if (isActionHidden == '1') {
                createCookie('isActionHidden', '0');
                val = 'block';
            } else {
                createCookie('isActionHidden', '1');
                val = 'none';
            }
            $('#bo_menu_haut_action_inner').css('display', val);
            $('#bo_menu_haut_action_title span').css('display', val);
        }
    </script>
    <?php }
} //fin fieldset action?>
</div>

<?php if (($_page == 'cms_pseudo.php' || $_page == 'cms_page.php') && $oPage->isRevision() && $oPage->checkAuthorized(true) && !$oConnected->isSEO()) { ?>
<div id="bo_bandeau_haut_revision">
    <div id="bo_bandeau_haut_revision_header">
        <h3><span><?php echo $oPage->getField('PAG_TITRE') . ' - ' . gettext('Revision_du') . ' ' .date('d/m/Y H:i', $oRevision->getField('REV_DATECREATION'))?></span></h3>
    </div>
</div>
<?php } ?>

<?php if (!empty($_SESSION['S_msg']) && !empty($_SESSION['S_msg']['NOTIFICATION']) && empty($_SESSION['S_msg']['ERROR'])) { ?>
<div id="bo_msg_notification"><?php echo implode('<br>', $_SESSION['S_msg']['NOTIFICATION'])?></div>
<?php
    $_SESSION['S_msg']['NOTIFICATION'] = array();
}
?>

<?php if (isset($_COOKIE['C_ancrageMenu'])) { ?>
<script>$(document).ready($('body').addClass('ancrageMenu'));</script>
<?php } ?>
