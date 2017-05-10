<?php
//Si l'utilisateur connecté est lié au mode debug, on affiche les éléments de ce mode
if (CMS_DEBUG == $_SESSION['S_UTI_LOGIN']) {

    CMS::addCSS(SERVER_ROOT . 'include/css/debug.css');

    $_REQUEST['DEBUG_INFO']['TIME_START'] = microtime(true);
    //TODO Problème avec cette ligne car le fichier "include" et donc le "inc.debug" sont appelés plusieurs fois et les infos sont écrasées à chaque fois
    // $_SESSION['DEBUG_INFO']['DB'] = array();
    $ob = ob_get_contents(); //Récupération du tampon de sortie sans l'effacer
    ob_clean(); // On vide le tampon de sortie sans l'envoyer au navigateur

    //Mise en tampon des données envoyés par ob_clean (déclencheur)
    // Ces données sont passées en entrée dans la fonction cms_trace qui retourne le résultat à afficher
    // Attention, si on ne fait pas le ob_get_contents() avant, la partie head du contenu n'est pas dans le tampon
    $retour= ob_start("cms_trace");
    echo $ob; // Affichage du contenu tranformé (sorte de référence sur le tampon d'origine modifié par la fonction cms_trace)
}

/*
 * Méthode permettant l'insertion de la barre de debug et de ces éléments
 */
function cms_trace($b)
{
    //Initialisation des variables contenant les futurs contenus
    $sHead = $sBody = '';
    //Préparation des styles
    $sHead .= '<script src="'. SERVER_ROOT .'include/js/debug.js"></script>';
    $sHead .= '<link rel="stylesheet" href="'. SERVER_ROOT .'include/css/debug.css">';
    $sHead .= '</head>';

    $dbh = DB::getInstance();
    //Préparation du bloc html affichant la barre de debug
    $sBody = '
            <div id="debugBar">
                    <span id="debugInfos">
                            PHP <i>'.phpversion().'</i> |
                            Temps d\'exécution <strong>'.round(((microtime(true) - $_REQUEST['DEBUG_INFO']['TIME_START'])*1000),2).'ms</strong> |
                            Poids <strong>'.round((ob_get_length()/1000),2).'Ko</strong> |
                            Requêtes SQL <strong><a href="#" id="cmsToggleRequests">'.$dbh->getQueryCount().'</a></strong>
                    </span>
                    <a href="#" id="cmsCloseDebug">Fermer</a>
            </div>
            <div id="debugRequests"></div>';

    //Ajout des scripts et styles en fin de HEAD dans le tampon en cours
    $b = preg_replace('/<\/head>/si', $sHead, $b);
    //Ajout de la barre de debug en début de BODY dans le tampon en cours
    $b = preg_replace('/<body([^>]*)>/si', '<body$1>' . $sBody, $b);

    return $b;
    //$_SESSION['DEBUG_INFO']['DB'] = array();
}
