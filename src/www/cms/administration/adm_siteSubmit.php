<?php
require '../../include/inc.bo_init.php';
require_once CLASS_DIR . 'class.db_page.php';
require_once CLASS_DIR . 'class.db_historique.php';
CMS::checkAccess(new Module('MOD_CORE'), array(
    'PRO_ROOT'
));

if (isset($_POST['Insert'])) {
    $SIT_CODE = 'SIT_' . preg_replace('/[^A-Z_0-9]/', '', strtoupper($_POST['SIT_CODE']));

    // on vérifie que le site code est unique
    $sql = "select SIT_CODE from DD_SITE where SIT_CODE=" . $dbh->quote($SIT_CODE);
    if ($dbh->query($sql)->fetchColumn()) {
        setMsg('Le site code "' . $SIT_CODE . '" existe déjà', 'ERROR');
        header('Location:' . SERVER_ROOT . 'cms/administration/adm_site.php');
        exit();
    }

    $stmt = $dbh->prepare("insert into DD_SITE (
        SIT_CODE,
        GAB_CODE,
        GBS_CODE,
        GBI_CODE,
        LNG_CODE,
        SIT_LIBELLE,
        SIT_TITLE,
        SIT_RECHERCHE,
        SIT_HOST,
        SIT_EMAIL,
        SIT_SUPPORTKEY,
        SIT_RSS_ACTU,
        SIT_RSS_WEBMARKET,
        SIT_AUTHOR,
        SIT_GA_TAG,
        SIT_GA_TAG_CNIL,
        SIT_GA_ID,
        SIT_GA_ID_SITE,
        SIT_EXT_DOC,
        SIT_EXT_IMAGE,
        SIT_EXT_FLASH,
        SIT_EXT_VIDEO,
        SIT_EXT_MUSIC,
        SIT_CHECKMD5,
        SIT_PAGE_TXTACCROCHE,
        SIT_PAGE_IMGACCROCHE,
        SIT_PAGE_HTTPS,
        SIT_PAGE_CACHE,
        SIT_TXTMOBILEHIDDEN,
        SIT_CONNECTION_MAX,
        SIT_CONNECTION_TTL
        ) values (
        :SIT_CODE,
        :GAB_CODE,
        :GBS_CODE,
        :GBI_CODE,
        :LNG_CODE,
        :SIT_LIBELLE,
        :SIT_TITLE,
        :SIT_RECHERCHE,
        :SIT_HOST,
        :SIT_EMAIL,
        :SIT_SUPPORTKEY,
        :SIT_RSS_ACTU,
        :SIT_RSS_WEBMARKET,
        :SIT_AUTHOR,
        :SIT_GA_TAG,
        :SIT_GA_TAG_CNIL,
        :SIT_GA_ID,
        :SIT_GA_ID_SITE,
        :SIT_EXT_DOC,
        :SIT_EXT_IMAGE,
        :SIT_EXT_FLASH,
        :SIT_EXT_VIDEO,
        :SIT_EXT_MUSIC,
        :SIT_CHECKMD5,
        :SIT_PAGE_TXTACCROCHE,
        :SIT_PAGE_IMGACCROCHE,
        :SIT_PAGE_HTTPS,
        :SIT_PAGE_CACHE,
        :SIT_TXTMOBILEHIDDEN,
        :SIT_CONNECTION_MAX,
        :SIT_CONNECTION_TTL)");
    $stmt->bindValue(':SIT_CODE', $SIT_CODE, PDO::PARAM_STR);
    $stmt->bindValue(':GAB_CODE', $_POST['GAB_CODE'], PDO::PARAM_STR);
    $stmt->bindValue(':GBS_CODE', $_POST['GBS_CODE'], PDO::PARAM_STR);
    $stmt->bindValue(':GBI_CODE', $_POST['GBI_CODE'], PDO::PARAM_STR);
    $stmt->bindValue(':LNG_CODE', $_POST['LNG_CODE'], PDO::PARAM_STR);
    $stmt->bindValue(':SIT_LIBELLE', $_POST['SIT_LIBELLE'], PDO::PARAM_STR);
    $stmt->bindValue(':SIT_TITLE', $_POST['SIT_TITLE'], PDO::PARAM_STR);
    $stmt->bindValue(':SIT_RECHERCHE', isset($_POST['SIT_RECHERCHE']) ? implode('@', $_POST['SIT_RECHERCHE']) : '', PDO::PARAM_STR);
    $stmt->bindValue(':SIT_HOST', $_POST['SIT_HOST'], PDO::PARAM_STR);
    $stmt->bindValue(':SIT_EMAIL', $_POST['SIT_EMAIL'], PDO::PARAM_STR);
    $stmt->bindValue(':SIT_SUPPORTKEY', $_POST['SIT_SUPPORTKEY'], PDO::PARAM_STR);
    $stmt->bindValue(':SIT_RSS_ACTU', $_POST['SIT_RSS_ACTU'], PDO::PARAM_STR);
    $stmt->bindValue(':SIT_RSS_WEBMARKET', $_POST['SIT_RSS_WEBMARKET'], PDO::PARAM_STR);
    $stmt->bindValue(':SIT_AUTHOR', $_POST['SIT_AUTHOR'], PDO::PARAM_STR);
    $stmt->bindValue(':SIT_GA_TAG', $_POST['SIT_GA_TAG'], PDO::PARAM_STR);
    $stmt->bindValue(':SIT_GA_TAG_CNIL', $_POST['SIT_GA_TAG_CNIL'], PDO::PARAM_INT);
    $stmt->bindValue(':SIT_GA_ID', $_POST['SIT_GA_ID'], PDO::PARAM_STR);
    $stmt->bindValue(':SIT_GA_ID_SITE', $_POST['SIT_GA_ID_SITE'] ? $_POST['SIT_GA_ID_SITE'] : null, PDO::PARAM_INT);
    $stmt->bindValue(':SIT_EXT_DOC', $_POST['SIT_EXT_DOC'], PDO::PARAM_STR);
    $stmt->bindValue(':SIT_EXT_IMAGE', $_POST['SIT_EXT_IMAGE'], PDO::PARAM_STR);
    $stmt->bindValue(':SIT_EXT_FLASH', $_POST['SIT_EXT_FLASH'], PDO::PARAM_STR);
    $stmt->bindValue(':SIT_EXT_VIDEO', $_POST['SIT_EXT_VIDEO'], PDO::PARAM_STR);
    $stmt->bindValue(':SIT_EXT_MUSIC', $_POST['SIT_EXT_MUSIC'], PDO::PARAM_STR);
    $stmt->bindValue(':SIT_CHECKMD5', ! empty($_POST['SIT_CHECKMD5']), PDO::PARAM_INT);
    $stmt->bindValue(':SIT_PAGE_TXTACCROCHE', ! empty($_POST['SIT_PAGE_TXTACCROCHE']), PDO::PARAM_INT);
    $stmt->bindValue(':SIT_PAGE_IMGACCROCHE', ! empty($_POST['SIT_PAGE_IMGACCROCHE']), PDO::PARAM_INT);
    $stmt->bindValue(':SIT_PAGE_HTTPS', ! empty($_POST['SIT_PAGE_HTTPS']), PDO::PARAM_INT);
    $stmt->bindValue(':SIT_PAGE_CACHE', ! empty($_POST['SIT_PAGE_CACHE']), PDO::PARAM_INT);
    $stmt->bindValue(':SIT_TXTMOBILEHIDDEN', $_POST['SIT_TXTMOBILEHIDDEN'], PDO::PARAM_STR);
    $stmt->bindValue(':SIT_CONNECTION_MAX', $_POST['SIT_CONNECTION_MAX'], PDO::PARAM_INT);
    $stmt->bindValue(':SIT_CONNECTION_TTL', $_POST['SIT_CONNECTION_TTL'], PDO::PARAM_INT);
    $stmt->execute();

    // On crée la page d'accueil
    $stmt = $dbh->prepare("insert into OFF_PAGE (
        SIT_CODE,
        PST_CODE,
        PAG_TITRE,
        PAG_TITRE_MENU,
        PAG_DATEOFFLINE,
        PAG_DATEONLINE,
        PAG_DATEMODIFICATION
        ) values (
        :SIT_CODE,
        :PST_CODE,
        :PAG_TITRE,
        :PAG_TITRE_MENU,
        unix_timestamp('2035-12-31'),
        unix_timestamp(),
        unix_timestamp())");
    $stmt->bindValue(':SIT_CODE', $SIT_CODE, PDO::PARAM_STR);
    $stmt->bindValue(':PST_CODE', 'PST_AREDIGER', PDO::PARAM_STR);
    $stmt->bindValue(':PAG_TITRE', $_POST['PAG_TITRE'], PDO::PARAM_STR);
    $stmt->bindValue(':PAG_TITRE_MENU', $_POST['PAG_TITRE'], PDO::PARAM_STR);
    $stmt->execute();

    // * Création des catégories racines associées aux éléments de la webothque
    $stmt = $dbh->prepare("insert into WEBOTHEQUECATEGORIE (
        SIT_CODE,
        WBT_CODE,
        CAT_LIBELLE
    ) values (
        :SIT_CODE,
        :WBT_CODE,
        'Racine'
    )");
    $stmt->bindValue(':SIT_CODE', $SIT_CODE, PDO::PARAM_STR);
    $sql = 'select WBT_CODE from DD_WEBOTHEQUETYPE';
    foreach ($dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN) as $WBT_CODE) {
        $stmt->bindValue(':WBT_CODE', $WBT_CODE, PDO::PARAM_STR);
        $stmt->execute();
    }
    // */

    // * Création de la catégorie racine associée aux formulaires
    $stmt = $dbh->prepare("insert into FORMULAIRECATEGORIE (
        SIT_CODE,
        CAT_LIBELLE
    ) values (
        :SIT_CODE,
        'Racine'
    )");
    $stmt->bindValue(':SIT_CODE', $SIT_CODE, PDO::PARAM_STR);
    $stmt->execute();
    // */

    // Recharge dans la session
    Utilisateur::getConnected()->initSession(CMS::getCurrentSite()->getID());
    Historique::historizeAdmin('CREATION', 'SITE', $SIT_CODE);
    $oSite = new Site($SIT_CODE);

    // Gestion des fichiers génériques
    if (! empty($_POST['SFI_FICHIER_0'])) {
        $oSite->creerFichierGenerique($_POST['SFI_FICHIER_0'], $_POST['SFI_CONTENU_0']);
    }

    // Mise à jour du logo et favicon
    if ($oSite->upload()) {
        setMsg(gettext('INSERT_OK'));
    }
    $oSite->uploadGAKeyFile();

    // Activation des modules + partage
    $aMOD_CODE = $oSite->activeModules($_POST['MOD_CODE']);
    $oSite->partageSites($_POST['SIT_CODE_AFFECTE']);
    $urlComplement = '';
    if (is_array($aMOD_CODE)) {
        $urlComplement .= '&MOD_CODE[]=' . implode('&MOD_CODE[]=', $aMOD_CODE);
        setMsg('Impossible de désactiver/activer certains modules', ERROR);
    }

    header('Location:' . SERVER_ROOT . 'cms/administration/adm_site.php?idtf=' . $oSite->getID() . $urlComplement);
    exit();
} elseif (isset($_POST['Update'])) {
    // si modifs des champs rss, on suprime le fichier cache existant
    $oSite = new Site($_POST['idtf']);
    $oSite->getFields();
    if (trim($_POST['SIT_RSS_ACTU']) != $oSite->getField('SIT_RSS_ACTU')) {
        $rssFileName = 'RSS_' . CMS::getCurrentSite()->getID() . '_' . date('d') . '_' . date('m') . '_' . date('Y') . '.htm';
        if (file_exists(PHYSICAL_PATH . 'uploads/' . $rssFileName)) {
            unlink(PHYSICAL_PATH . 'uploads/' . $rssFileName);
        }
    }

    if (trim($_POST['SIT_RSS_WEBMARKET']) != $oSite->getField('SIT_RSS_WEBMARKET')) {
        $rssFileName = 'RSSWBM_' . CMS::getCurrentSite()->getID() . '_' . date('d') . '_' . date('m') . '_' . date('Y') . '.htm';
        if (file_exists(PHYSICAL_PATH . 'uploads/' . $rssFileName)) {
            unlink(PHYSICAL_PATH . 'uploads/' . $rssFileName);
        }
    }

    //on réordonne les champs d'extension
    foreach (array('SIT_EXT_IMAGE','SIT_EXT_FLASH','SIT_EXT_DOC','SIT_EXT_VIDEO','SIT_EXT_MUSIC') as $key) {
        $a = explode("\n", $_POST[$key]);
        sort($a);
        $_POST[$key] = implode("\n", $a);
    }

    $stmt = $dbh->prepare("update DD_SITE set
        GAB_CODE=:GAB_CODE,
        GBS_CODE=:GBS_CODE,
        GBI_CODE=:GBI_CODE,
        LNG_CODE=:LNG_CODE,
        SIT_LIBELLE=:SIT_LIBELLE,
        SIT_TITLE=:SIT_TITLE,
        SIT_RECHERCHE=:SIT_RECHERCHE,
        SIT_HOST=:SIT_HOST,
        SIT_EMAIL=:SIT_EMAIL,
        SIT_SUPPORTKEY=:SIT_SUPPORTKEY,
        SIT_RSS_ACTU=:SIT_RSS_ACTU,
        SIT_RSS_WEBMARKET=:SIT_RSS_WEBMARKET,
        SIT_AUTHOR=:SIT_AUTHOR,
        SIT_GA_TAG=:SIT_GA_TAG,
        SIT_GA_TAG_CNIL=:SIT_GA_TAG_CNIL,
        SIT_GA_ID=:SIT_GA_ID,
        SIT_GA_ID_SITE=:SIT_GA_ID_SITE,
        SIT_EXT_DOC=:SIT_EXT_DOC,
        SIT_EXT_IMAGE=:SIT_EXT_IMAGE,
        SIT_EXT_FLASH=:SIT_EXT_FLASH,
        SIT_EXT_VIDEO=:SIT_EXT_VIDEO,
        SIT_EXT_MUSIC=:SIT_EXT_MUSIC,
        SIT_CHECKMD5=:SIT_CHECKMD5,
        SIT_PAGE_TXTACCROCHE=:SIT_PAGE_TXTACCROCHE,
        SIT_PAGE_IMGACCROCHE=:SIT_PAGE_IMGACCROCHE,
        SIT_PAGE_HTTPS=:SIT_PAGE_HTTPS,
        SIT_PAGE_CACHE=:SIT_PAGE_CACHE,
        SIT_TXTMOBILEHIDDEN=:SIT_TXTMOBILEHIDDEN,
        SIT_CONNECTION_MAX=:SIT_CONNECTION_MAX,
        SIT_CONNECTION_TTL=:SIT_CONNECTION_TTL
        where SIT_CODE=:SIT_CODE");
    $stmt->bindValue(':LNG_CODE', $_POST['LNG_CODE'], PDO::PARAM_STR);
    $stmt->bindValue(':GAB_CODE', $_POST['GAB_CODE'], PDO::PARAM_STR);
    $stmt->bindValue(':GBS_CODE', $_POST['GBS_CODE'], PDO::PARAM_STR);
    $stmt->bindValue(':GBI_CODE', $_POST['GBI_CODE'], PDO::PARAM_STR);
    $stmt->bindValue(':SIT_LIBELLE', $_POST['SIT_LIBELLE'], PDO::PARAM_STR);
    $stmt->bindValue(':SIT_TITLE', $_POST['SIT_TITLE'], PDO::PARAM_STR);
    $stmt->bindValue(':SIT_RECHERCHE', isset($_POST['SIT_RECHERCHE']) ? implode('@', $_POST['SIT_RECHERCHE']) : null, PDO::PARAM_STR);
    $stmt->bindValue(':SIT_HOST', $_POST['SIT_HOST'], PDO::PARAM_STR);
    $stmt->bindValue(':SIT_EMAIL', $_POST['SIT_EMAIL'], PDO::PARAM_STR);
    $stmt->bindValue(':SIT_SUPPORTKEY', $_POST['SIT_SUPPORTKEY'], PDO::PARAM_STR);
    $stmt->bindValue(':SIT_RSS_ACTU', $_POST['SIT_RSS_ACTU'], PDO::PARAM_STR);
    $stmt->bindValue(':SIT_RSS_WEBMARKET', $_POST['SIT_RSS_WEBMARKET'], PDO::PARAM_STR);
    $stmt->bindValue(':SIT_AUTHOR', $_POST['SIT_AUTHOR'], PDO::PARAM_STR);
    $stmt->bindValue(':SIT_GA_TAG', $_POST['SIT_GA_TAG'], PDO::PARAM_STR);
    $stmt->bindValue(':SIT_GA_TAG_CNIL', $_POST['SIT_GA_TAG_CNIL'], PDO::PARAM_INT);
    $stmt->bindValue(':SIT_GA_ID', $_POST['SIT_GA_ID'], PDO::PARAM_STR);
    $stmt->bindValue(':SIT_GA_ID_SITE', $_POST['SIT_GA_ID_SITE'] ? $_POST['SIT_GA_ID_SITE'] : null, PDO::PARAM_INT);
    $stmt->bindValue(':SIT_EXT_DOC', $_POST['SIT_EXT_DOC'], PDO::PARAM_STR);
    $stmt->bindValue(':SIT_EXT_IMAGE', $_POST['SIT_EXT_IMAGE'], PDO::PARAM_STR);
    $stmt->bindValue(':SIT_EXT_FLASH', $_POST['SIT_EXT_FLASH'], PDO::PARAM_STR);
    $stmt->bindValue(':SIT_EXT_VIDEO', $_POST['SIT_EXT_VIDEO'], PDO::PARAM_STR);
    $stmt->bindValue(':SIT_EXT_MUSIC', $_POST['SIT_EXT_MUSIC'], PDO::PARAM_STR);
    $stmt->bindValue(':SIT_CHECKMD5', ! empty($_POST['SIT_CHECKMD5']), PDO::PARAM_INT);
    $stmt->bindValue(':SIT_PAGE_TXTACCROCHE', ! empty($_POST['SIT_PAGE_TXTACCROCHE']), PDO::PARAM_INT);
    $stmt->bindValue(':SIT_PAGE_IMGACCROCHE', ! empty($_POST['SIT_PAGE_IMGACCROCHE']), PDO::PARAM_INT);
    $stmt->bindValue(':SIT_PAGE_HTTPS', ! empty($_POST['SIT_PAGE_HTTPS']), PDO::PARAM_INT);
    $stmt->bindValue(':SIT_PAGE_CACHE', ! empty($_POST['SIT_PAGE_CACHE']), PDO::PARAM_INT);
    $stmt->bindValue(':SIT_TXTMOBILEHIDDEN', $_POST['SIT_TXTMOBILEHIDDEN'], PDO::PARAM_STR);
    $stmt->bindValue(':SIT_CONNECTION_MAX', $_POST['SIT_CONNECTION_MAX'], PDO::PARAM_INT);
    $stmt->bindValue(':SIT_CONNECTION_TTL', $_POST['SIT_CONNECTION_TTL'], PDO::PARAM_INT);
    $stmt->bindValue(':SIT_CODE', $_POST['idtf'], PDO::PARAM_STR);
    $stmt->execute();

    // On force le parsage des paragraphes on et off, pour le prise en compte d'un changement de host par exemple
    $sql = 'update OFF_PARAGRAPHE set PAR_APARSER=1';
    $dbh->exec($sql);
    $sql = 'update ON_PARAGRAPHE set PAR_APARSER=1';
    $dbh->exec($sql);

    // Recharge dans la session
    Utilisateur::getConnected()->initSession(CMS::getCurrentSite()->getID());
    Historique::historizeAdmin('MODIFICATION', 'SITE', $_POST['idtf']);

    // Récupération du site mis à jour
    $oSite = new Site($_POST['idtf']);

    // Purge du cache de l'ensemble des sites
    Page::clearAllCache();

    if ($oSite->hasModule(new Module('MOD_EAM'))) {
        $stmt = $dbh->prepare("update DD_SITE set
            SIT_EAM_ID=:SIT_EAM_ID,
            SIT_EAM_SECURITYCODE=:SIT_EAM_SECURITYCODE,
            SIT_EAM_STAT=:SIT_EAM_STAT
            where SIT_CODE=:SIT_CODE");
        $stmt->bindValue(':SIT_EAM_ID', isset($_POST['SIT_EAM_ID']) && ! empty($_POST['SIT_EAM_ID']) ? $_POST['SIT_EAM_ID'] : null, PDO::PARAM_INT);
        $stmt->bindValue(':SIT_EAM_SECURITYCODE', isset($_POST['SIT_EAM_SECURITYCODE']) ? trim($_POST['SIT_EAM_SECURITYCODE']) : null, PDO::PARAM_STR);
        $stmt->bindValue(':SIT_EAM_STAT', isset($_POST['SIT_EAM_STAT']) ? intval($_POST['SIT_EAM_STAT']) : null, PDO::PARAM_INT);
        $stmt->bindValue(':SIT_CODE', $_POST['idtf'], PDO::PARAM_STR);
        $stmt->execute();
    }

    // Gestion des fichiers génériques
    $oSite->deleteFichiersGenerique();
    for ($i = 0; $i < $_POST['NB_FICHIERSGENERIQUES']; $i ++) {
        if (! $_POST['SFI_FICHIER_DELETE_' . $i] && ! empty($_POST['SFI_FICHIER_' . $i])) {
            $oSite->creerFichierGenerique($_POST['SFI_FICHIER_' . $i], $_POST['SFI_CONTENU_' . $i]);
        }
    }

    // Mise à jour du logo et favicon
    if ($oSite->upload()) {
        setMsg(gettext('UPDATE_OK'));
    }
    $oSite->uploadGAKeyFile();

    // Activation des modules + partage
    $aMOD_CODE = $oSite->activeModules($_POST['MOD_CODE']);
    $oSite->partageSites($_POST['SIT_CODE_AFFECTE']);
    $urlComplement = '';
    if (is_array($aMOD_CODE)) {
        $urlComplement .= '&MOD_CODE[]=' . implode('&MOD_CODE[]=', $aMOD_CODE);
        setMsg('Impossible de désactiver/activer certains modules', ERROR);
    }

    header('Location:' . SERVER_ROOT . 'cms/administration/adm_site.php?idtf=' . $_POST['idtf'] . $urlComplement);
    exit();
} elseif (! empty($_GET['Delete'])) {
    $oSite = new Site($_GET['Delete']);
    if ($oSite->delete()) {
        setMsg(gettext('DELETE_OK'));
    }
    Utilisateur::getConnected()->initSession(CMS::getCurrentSite()->getID());
    header('Location:' . SERVER_ROOT . 'cms/administration/adm_siteListe.php');
    exit();
}
