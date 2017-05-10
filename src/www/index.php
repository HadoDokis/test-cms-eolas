<?php
require './include/inc.fo_init.php';
require './include/inc.debug.php';

if (!CMS::getCurrentSite()->getHomePage()) {
    header('Location:' . SERVER_ROOT . 'redirection.html');
    exit();
}

/**
 * Url attendue
 * @var string
 */
$URI_URLREWRITING = '';

/**
 * Url courante
 * @var string
 */
$PAG_URLREWRITING = '';

/**
 * Tableau des paramètres de l'URL réécrite
 * @var array
 */
$aRewriteRedirect = array();

$REQUEST_URI = substr($_SERVER['REQUEST_URI'], strlen(SERVER_ROOT));

//Gestion des fichiers génériques
$bIsDynnamiqueFile = false;
$aFichierGenerique = CMS::getCurrentSite()->getFichiersGeneriques();
if (count($aFichierGenerique)>0 && in_array($REQUEST_URI,array_keys($aFichierGenerique))) {
    $bIsDynnamiqueFile = true;
}

// Priorité à la configuration Apache sur les 404 et 403
$HTTP_RESPONSE_CODE = $_SERVER['REDIRECT_STATUS'];
if (!$bIsDynnamiqueFile && $HTTP_RESPONSE_CODE == '404') {
    // S'il s'agit d'une 404 sur une ressource pouvant être associée
    // aux images de la wébothèque,
    // on génère une 404 spécifique correspondant à l'image "nopic.jpg"
    $bIsImg404 = false;
    if (file_exists(UPLOAD_IMAGE_PHYSIQUE.'nopic/nopic.jpg')) {
        $aSitImgExt = array_map('strtolower', CMS::getCurrentSite()->getExtension('SIT_EXT_IMAGE'));;
        $uri_parts = explode('?', $REQUEST_URI, 2);
        $ext = strtolower(array_pop(explode('.',$uri_parts[0])));
        if (in_array('.'.$ext,$aSitImgExt)) {
            $bIsImg404 = true;
        }
    }
    if ($bIsImg404) {
        http_response_code(404);
        header('Content-Type: image/jpeg');
        echo file_get_contents(UPLOAD_IMAGE_PHYSIQUE.'nopic/nopic.jpg');
        exit();
    // Sinon on génère la 404 dynamique
    } else {
        $oPage = CMS::getCurrentSite()->getSpecialePage('PGS_404');
        if ($oPage && $oPage->exist()) {
            $URI_URLREWRITING = $PAG_URLREWRITING = $oPage->getField('PAG_URLREWRITING');
        }
    }
} elseif (!$bIsDynnamiqueFile && $HTTP_RESPONSE_CODE == '403') {
    $oPage = CMS::getCurrentSite()->getSpecialePage('PGS_403');
    if ($oPage && $oPage->exist()) {
        $URI_URLREWRITING = $PAG_URLREWRITING = $oPage->getField('PAG_URLREWRITING');
    }
} else {

    //Gestion des fichiers génériques
    $aFichierGenerique = CMS::getCurrentSite()->getFichiersGeneriques();
    if (count($aFichierGenerique)>0 && in_array($REQUEST_URI,array_keys($aFichierGenerique))) {
        header('HTTP/1.1 200 OK');
        header('Content-Type: text/plain');
        //	On gère le cas des pages à exclure de la recherche si robots.txt
        if ($REQUEST_URI == 'robots.txt') {
            echo "User-agent: *";
            echo "\nDisallow: " . SERVER_ROOT . "include/viewfilesecure.php*";
            echo "\nDisallow: " . SERVER_ROOT . "cms/";
            /** #8975 : Mise à jour robot.txt par défaut **/
            echo "\nDisallow: " . SERVER_ROOT . "*UTB_FS=*";
            echo "\nDisallow: " . SERVER_ROOT . "*UTB_S=*";
            echo "\nDisallow: " . SERVER_ROOT . "*UTB_RESET=*";
            echo "\nDisallow: " . SERVER_ROOT . "*fontSize=*";
            echo "\nDisallow: " . SERVER_ROOT . "*style=*";
            echo "\nDisallow: " . SERVER_ROOT . "*reset=*";
            /** Fin #8975 **/
            echo "\n\n";
        }
        echo $aFichierGenerique[$REQUEST_URI];
        if ($REQUEST_URI == 'robots.txt') {
            echo "\n\nSitemap: http://".CMS::getCurrentSite()->getField('SIT_HOST').SERVER_ROOT."sitemap.xml";
        }
        exit();
    }

    if (in_array($_SERVER['REQUEST_URI'], array ('', SERVER_ROOT, SERVER_ROOT . 'index.php'))) {
        $oPage = CMS::getCurrentSite()->getHomePage();
    } elseif (is_dir('.' . $_SERVER['REQUEST_URI'])) {
        if (($oPage = CMS::getCurrentSite()->getSpecialePage('PGS_403')) && URL_REWRITING) {
            $PAG_URLREWRITING = $oPage->getField('PAG_URLREWRITING');
        }
    } elseif (URL_REWRITING) {
        $aURL = Page::getAllURLAlternative();
        // Enleve les parametres rajoutés a l'url alternative éventuelle
        $alternativeUrl = $REQUEST_URI;
        $posParamsMark = strpos($REQUEST_URI, '?');
        if ($posParamsMark !== false) {
           $alternativeUrl = substr($alternativeUrl, 0, $posParamsMark);
        }
        if (!empty($alternativeUrl)
           && array_key_exists(strtolower($alternativeUrl), $aURL)
           && ($oPage = new Page($aURL[strtolower($alternativeUrl)], 'ON_'))
           && $oPage->exist()
        ) {
           require_once CLASS_DIR . 'class.UrlBuilder.php';
           $urlBuilder = new UrlBuilder($oPage);
           $urlBuilder->retrieve($REQUEST_URI);
           $aParamsPre = array();
           $aParamsPre = $urlBuilder->getParams();
           http_response_code(301);
           $oPage->redirect($aParamsPre);
        }

        $_tab = explode('/', $REQUEST_URI);
        $info = array_pop($_tab);
        // $info est de la forme [ID_PAGE]-[PAG_URLREWRITING].htm ou [ID_PAGE]
        if (preg_match('/^(\d*)-(.*)\.htm(\?)?/U', $info, $matches)) {
           $oPage = new Page($matches[1], CMS::$mode);
           $PAG_URLREWRITING = $matches[2];
        } elseif (is_numeric($info)) {
            $oPage = new Page($info, CMS::$mode);
        } elseif (is_numeric($_REQUEST['idtf'])) {
            //cas de la soumission d'un formulaire
            $oPage = new Page($_REQUEST['idtf'], CMS::$mode);
        } elseif (substr($info, 0, 1) == '?' || substr($info, 0, 10) == 'index.php?') {
            //cas d'un lien vers l'accueil par ASPMail (www.domain.tld?param1=val1&param2=val2)
            $oPage = CMS::getCurrentSite()->getHomePage();
        } elseif ($oPage = CMS::getCurrentSite()->getSpecialePage('PGS_404')) {
            $PAG_URLREWRITING = $oPage->getField('PAG_URLREWRITING');
        }

        if (isset($oPage) && $oPage instanceof Page && $oPage->exist()) {

            /**
             * UrlBuilder
             */
            require_once CLASS_DIR . 'class.UrlBuilder.php';

            $urlBuilder = new UrlBuilder($oPage);
            $urlBuilder->retrieve($REQUEST_URI);
            $_REQUEST         = array_merge($_REQUEST, $urlBuilder->getParams());
            $_GET             = array_merge($_GET,     $urlBuilder->getParams());
            $URI_URLREWRITING = $urlBuilder->build()->getUrlrewriting();
            $aRewriteRedirect = $urlBuilder->getParams();
            //*
            // Si une 404 est générée par l'UrlParser d'un module
            if (http_response_code() == 404) {
                $URI_URLREWRITING = $PAG_URLREWRITING;
                if ($oPageTemp = CMS::getCurrentSite()->getSpecialePage('PGS_404')) {
                    $oPage = $oPageTemp;
                } else {
                    unset($_REQUEST['TPL_CODE']);
                }
            }
            // Si une 403 est générée par l'UrlParser d'un module
            if (http_response_code() == 403) {
                $URI_URLREWRITING = $PAG_URLREWRITING;
                // Si présence du module extranet, ...
                // 1 - On charge la page d'authentification
                if (CMS::getCurrentSite()->hasModule(new Module('MOD_EXTRANET'))
                    && ($oPageTemp = CMS::getCurrentSite()->getSpecialePage('PGS_AUTHENTIFICATION'))
                ) {
                    // 1.a - On récupère l'ID de la page initiale (pour le redirection)
                    CMS::getCurrentSite()->setSecuredID($oPage->getID());
                    // 1.b - On charge la page d'authgentification au sein du contexte courant
                    $oPage = $oPageTemp;
                    // 1.c - On affecte les paramètres de l'URL à "$_POST['requestKey']" (pour la redirection)
                    $_POST['requestKey'] = base64_encode(serialize($aRewriteRedirect));
                    // 1.d - On retire la référence à $_REQUEST['TPL_CODE']
                    unset($_REQUEST['TPL_CODE']);
                // Si pas de module extranet, ...
                // 2 - Fallback : On charge la page d'erreur 403
                } elseif ($oPageTemp = CMS::getCurrentSite()->getSpecialePage('PGS_403')) {
                    $oPage = $oPageTemp;
                // 3 - Fallback : On retire la référence à $_REQUEST['TPL_CODE']
                } else {
                    unset($_REQUEST['TPL_CODE']);
                }
            }
            //*/
        }
    } else {
        $oPage = new Page($_REQUEST['idtf'], CMS::$mode);
    }
} // FIN : Priorité à la configuration Apache sur les 404 et 403

// Erreur 404 sur les TPL_CODE qui ne sont pas ou plus disponibles
if ($oPage && $oPage->exist() && !empty($_REQUEST['TPL_CODE'])) {
    require_once CLASS_DIR . 'class.db_template.php';
    $oTemplate = new Template($_REQUEST['TPL_CODE']);
    if (!$oTemplate->isEnabled(CMS::getCurrentSite())) {
        $URI_URLREWRITING = $PAG_URLREWRITING;
        // On cherche la page spéciale "404 Not Found",
        // sinon on garde la page courante et ajoutons un statut HTTP 404
        if ($oPageTemp = CMS::getCurrentSite()->getSpecialePage('PGS_404')) {
            $oPage = $oPageTemp;
        } else {
            unset($_REQUEST['TPL_CODE']);
            http_response_code(404);
        }
    }
}

if (!$oPage || !$oPage->exist() || ($oPage->getField('SIT_CODE') != CMS::getCurrentSite()->getID())) {
    unset($_REQUEST['TPL_CODE']);
    // on cherche la page spéciale "404 Not Found", sinon on va à l'accueil
    http_response_code(404);
    if (!$oPage = CMS::getCurrentSite()->getSpecialePage('PGS_404')) {
        $oPage = CMS::getCurrentSite()->getHomePage();
    }
} elseif ($oWebo = $oPage->getExternalRedirection()) {
    http_response_code(301);
    header('Location:' . $oWebo->getField('WEB_CHEMIN'));
    exit ();
} elseif ($oRedir = $oPage->getInternalRedirection()) {
    http_response_code(301);
    $oRedir->redirect();
} else {
    // On vérifie que les infos sur le répertoire virtuel correspondent sinon on fait un 301 sur la bonne URI
    if (URL_REWRITING && !$oPage->isHome() && ($URI_URLREWRITING != $PAG_URLREWRITING)) {
        http_response_code(301);
        $oPage->redirect($aRewriteRedirect);
    }
    // On vérifie qu'il ne sagit pas d'une page d'erreur de type 404 ou 403 car dans ces cas, il faut envoyer les headers qui vont bien
    if (($oPageTemp = CMS::getCurrentSite()->getSpecialePage('PGS_404')) && ($oPageTemp->getID() == $oPage->getID())) {
        unset($_REQUEST['TPL_CODE']);
        http_response_code(404);
    } elseif (($oPageTemp = CMS::getCurrentSite()->getSpecialePage('PGS_403')) && ($oPageTemp->getID() == $oPage->getID())) {
        unset($_REQUEST['TPL_CODE']);
        http_response_code(403);
    }
}

if (CMS::getCurrentSite()->hasModule(new Module('MOD_EXTRANET')) && $oPage->isForbidden()) {
    unset($_REQUEST['TPL_CODE']);
    CMS::getCurrentSite()->setSecuredID($oPage->getID());
    if ($oPage = CMS::getCurrentSite()->getSpecialePage('PGS_AUTHENTIFICATION')) {
        http_response_code(403);
    } elseif ($oPage = CMS::getCurrentSite()->getSpecialePage('PGS_403')) {
        http_response_code(403);
    } else {
        // Si vraiment il n'y a rien on redirige vers la racine
        http_response_code(302);
        $oPage = CMS::getCurrentSite()->getHomePage();
        $oPage->redirect();
    }
}
//* HTTPS : http://httpd.apache.org/docs/2.0/mod/mod_rewrite.html#rewritecond
// On verifie qu'on est en HTTPS en demandant une PAG_HTTPS=1
if (CMS::getCurrentSite()->getField('SIT_PAGE_HTTPS') && $oPage->getField('PAG_HTTPS') && $_SERVER['HTTPS'] != 'on') {
    http_response_code(301);
    $oPage->redirect();
}
//*/

// Stockage du nombre d'utilisateur naviguant sur le site
CMS::getCurrentSite()->updateTraficUser();

// Mise en cache de la page
$oPage->checkCache();

//property og
CMS::addMETAPROPERTY('og:title', $oPage->getField('PAG_TITRE'));
if ($oWebo = $oPage->getAccroche()) {
    CMS::addMETAPROPERTY('og:image',  '//' . $_SERVER['HTTP_HOST'] . $oWebo->getSRC());
}
if ($oPage->getField('PAG_ACCROCHE')) {
    CMS::addMETAPROPERTY('og:description', $oPage->getField('PAG_ACCROCHE'));
}

CMS::getCurrentSite()->setCurrentPage($oPage);
include(CMS::getCurrentSite()->getField('SIT_INCLUDE') . '/inc.colonnage.php');
if ($oPage->hasLeftColumn() && $oPage->hasRightColumn()) {$classColonnage = ' class="avecDeuxColonnes"';} elseif ($oPage->hasRightColumn()) {$classColonnage = ' class="avecColonneDroite"';} elseif ($oPage->hasLeftColumn()) {$classColonnage = ' class="avecColonneGauche"';} else {$classColonnage = '';}
?>
<!DOCTYPE html>
<html lang="<?php echo CMS::getCurrentSite()->getField('SIT_SHORT_LANGUE')?>">
<head>
<?php include('./include/inc.fo_enTete.php');?>
</head>
<body<?php if ($oPage->isHome()) echo ' id="Accueil"'?>>
<script>document.body.className="withJS"</script>
<?php include(CMS::getCurrentSite()->getField('SIT_INCLUDE') . '/inc.document.php'); ?>
</body>
</html>
