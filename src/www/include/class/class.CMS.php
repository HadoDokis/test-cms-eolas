<?php

/**
 * Class de gestion du CMS, permet d'initialiser le CMS et d'accéder au éléments de réstitution, CSS, JS,
 * @package CMS
 */
class CMS
{

    /**
     * Mode "Pseudo" = 'OFF_' ; Mode "En ligne" = 'ON_'
     *
     * @var string
     */
    public static $mode = 'OFF_';

    /**
     * true si on est en mode édition, false sinon
     *
     * @var boolean
     */
    public static $edition = false;

    /**
     * Types d'éléments accéssible dans le head
     *
     * @var array
     */
    private static $_aHeader = array(
        'ARIANE' => '',
        'CSS' => array(),
        'JS' => array(),
        'DOMREADY' => array(),
        'RSS' => array(),
        'TITREPAGE' => null,
        'TITLE' => null,
        'METADESCRIPTION' => '',
        'HEADER' => array(),
        'METAPROPERTY' => array()
    );

    /**
     * Objet Site courrant
     *
     * @var Site
     */
    private static $_currentSite = null;

    /**
     * Tableau contenant les sites
     *
     * @var array
     */
    private static $_aSite = null;

    /**
     * Retourne le tableau de tous les codes-langues dispo
     *
     * @return array
     */
    public static function getLangueArray()
    {
        $tab = array(
            'ar' => 'Arabie',
            'az' => 'Azerbaïdjan',
            'be' => 'Biélorusse',
            'bg' => 'Bulgarie',
            'bh' => 'Bihari',
            'bn' => 'Bengali',
            'bo' => 'Tibétain',
            'br' => 'Breton',
            'ca' => 'Catalan',
            'co' => 'Corse',
            'cs' => 'Tchèque',
            'cy' => 'Wallis',
            'da' => 'Danois',
            'de' => 'Allemand',
            'el' => 'Grec',
            'en' => 'Anglais',
            'eo' => 'Espéranto',
            'es' => 'Espagnol',
            'et' => 'Estonien',
            'eu' => 'Basque',
            'fa' => 'Perse',
            'fi' => 'Finlandais',
            'fj' => 'Fidji',
            'fo' => 'Féroïen',
            'fr' => 'Français',
            'fy' => 'Frison',
            'ga' => 'Irlandais',
            'he' => 'Hébreu',
            'hi' => 'Hindi',
            'hr' => 'Croate',
            'hu' => 'Hongrois',
            'hy' => 'Arménien',
            'ia' => 'Interlingua',
            'id' => 'Indonésien',
            'ie' => 'Interlingue',
            'is' => 'Islandais',
            'it' => 'Italien',
            'ja' => 'Japonais',
            'ka' => 'Géorgien',
            'kk' => 'Kazakh',
            'km' => 'Khmer',
            'kn' => 'Kannada',
            'ko' => 'Coréen',
            'ks' => 'Cachemire',
            'ku' => 'Kurde',
            'la' => 'Latin',
            'lo' => 'Laotien',
            'lt' => 'Lituanien',
            'lv' => 'Letton',
            'mg' => 'Malgache',
            'mi' => 'Maoris',
            'mk' => 'Macédoine',
            'mn' => 'Mongol',
            'mo' => 'Moldave',
            'mr' => 'Marathe',
            'ms' => 'Malais',
            'mt' => 'Maltais',
            'my' => 'Birman',
            'ne' => 'Népalais',
            'nl' => 'Hollandais',
            'no' => 'Norvégien',
            'oc' => 'Occitan',
            'pl' => 'Polonais',
            'ps' => 'Pachtoune',
            'pt' => 'Portugais',
            'ro' => 'Roumain',
            'ru' => 'Russe',
            'sa' => 'Sanskrit',
            'sk' => 'Slovaque',
            'sl' => 'Slovène',
            'sq' => 'Albanais',
            'sr' => 'Serbe',
            'su' => 'Soudanais',
            'sv' => 'Suédois',
            'te' => 'Tegoulu',
            'tg' => 'Tadjik',
            'th' => 'Thaï',
            'tk' => 'Turkmène',
            'tr' => 'Turc',
            'ts' => 'Tsonga',
            'tt' => 'Tatar',
            'uk' => 'Ukrainien',
            'uz' => 'Ouzbek',
            'vi' => 'Vietnamien',
            'yi' => 'Yiddish',
            'zh' => 'Chinois',
            'zu' => 'Zoulou'
        );
        asort($tab);

        return $tab;
    }

    /**
     * Retourne la version du CMS tel que définie dans la base de données
     *
     * @return String La version du noyau
     */
    public static function getVersion()
    {
        if (empty($_SESSION['CORE_VERSION'])) {
            $dbh = DB::getInstance();
            $sql = "select MOD_VERSION from DD_MODULES where MOD_CODE='core'";
            $_SESSION['CORE_VERSION'] = $dbh->query($sql)->fetchColumn();
        }

        return $_SESSION['CORE_VERSION'];
    }

    /**
     * inclue le fichier HTML des entrées RSS aspirées depuis le site SUPPORT
     */
    public static function getRSSFeed()
    {
        if (self::getCurrentSite()->getField('SIT_RSS_ACTU')) {
            $rssFileName = 'RSS_' . CMS::getCurrentSite()->getID() . '_' . date('d') . '_' . date('m') . '_' . date('Y') . '.htm';
            if (! file_exists(PHYSICAL_PATH . 'uploads/' . $rssFileName)) {
                $default_socket_timeout = ini_set('default_socket_timeout', 1);
                if ($xml = @simplexml_load_file(self::getCurrentSite()->getField('SIT_RSS_ACTU'))) {
                    foreach (glob(PHYSICAL_PATH . 'uploads/RSS_' . CMS::getCurrentSite()->getID() . '_*.htm') as $filename) {
                        unlink($filename);
                    }
                    $file = fopen(PHYSICAL_PATH . 'uploads/' . $rssFileName, 'w');
                    $title = "<h3>Actualités CMS.Eolas</h3>";

                    fwrite($file, $title . "<ul>\n");
                    $compteur = 1;
                    foreach ($xml->channel->item as $actu) {
                        if ($compteur ++ > 3) {
                            break;
                        }
                        $_aNews[strtotime($actu->pubDate)] = array(
                            'link' => $actu->link,
                            'title' => $actu->title,
                            'description' => $actu->description
                        );
                    }
                    krsort($_aNews);
                    foreach ($_aNews as $date => $actu) {
                        $link = (self::getCurrentSite()->getField('SIT_SUPPORTKEY') != '') ? SIT_SUPPORT . 'index.php?key=' . self::getCurrentSite()->getField('SIT_SUPPORTKEY') . '&amp;redirect=' . base64_encode($actu['link']) : $actu['link'];
                        fwrite($file, "<li><div class=\"gauche\"><span class=\"j\">" . date('d', $date) . "</span><span class=\"m\">" . mb_convert_case(strftime('%b', $date), MB_CASE_TITLE) . "</span></div><div class=\"droite\"><h4><a href=\"" . $link . "\" target=\"_blank\">" . $actu['title'] . "</a></h4>" . $actu['description'] . "</div></li>\n");
                    }
                    fwrite($file, "</ul>\n");
                    fclose($file);
                } else {
                    $rssFileNameOld = glob(PHYSICAL_PATH . 'uploads/RSS_' . CMS::getCurrentSite()->getID() . '_*.htm');
                    if (isset($rssFileNameOld[0])) {
                        rename($rssFileNameOld[0], PHYSICAL_PATH . 'uploads/' . $rssFileName);
                    }
                }
                ini_set('default_socket_timeout', $default_socket_timeout);
            }
            if (file_exists(PHYSICAL_PATH . 'uploads/' . $rssFileName)) {
                include (PHYSICAL_PATH . 'uploads/' . $rssFileName);
            }
        }
    }

    /**
     * inclue le fichier HTML des entrées RSS aspirées depuis le site WEBMARKETING
     */
    public static function getRSSWebMarketFeed()
    {
        if (self::getCurrentSite()->getField('SIT_RSS_WEBMARKET')) {
            $rssFileName = 'RSSWBM_' . CMS::getCurrentSite()->getID() . '_' . date('d') . '_' . date('m') . '_' . date('Y') . '.htm';
            if (! file_exists(PHYSICAL_PATH . 'uploads/' . $rssFileName)) {
                $default_socket_timeout = ini_set('default_socket_timeout', 1);
                if ($xml = @simplexml_load_file(self::getCurrentSite()->getField('SIT_RSS_WEBMARKET'))) {
                    foreach (glob(PHYSICAL_PATH . 'uploads/RSSWBM_' . CMS::getCurrentSite()->getID() . '_*.htm') as $filename) {
                        unlink($filename);
                    }
                    $file = fopen(PHYSICAL_PATH . 'uploads/' . $rssFileName, 'w');
                    $title = "<h3>" . (string) $xml->channel->title . "</h3>";

                    fwrite($file, $title . "<ul>\n");
                    $compteur = 1;
                    foreach ($xml->channel->item as $actu) {
                        if ($compteur ++ > 3) {
                            break;
                        }
                        $_aNews[strtotime($actu->pubDate)] = array(
                            'link' => $actu->link,
                            'title' => $actu->title,
                            'description' => $actu->description
                        );
                    }
                    krsort($_aNews);
                    foreach ($_aNews as $date => $actu) {
                        $link = $actu['link'];
                        fwrite($file, "<li><div class=\"gauche\"><span class=\"j\">" . date('d', $date) . "</span><span class=\"m\">" . mb_convert_case(strftime('%b', $date), MB_CASE_TITLE) . "</span></div><div class=\"droite\"><h4><a href=\"" . $link . "\" target=\"_blank\">" . $actu['title'] . "</a></h4>" . $actu['description'] . "</div></li>\n");
                    }
                    fwrite($file, "</ul>\n");
                    fclose($file);
                } else {
                    $rssFileNameOld = glob(PHYSICAL_PATH . 'uploads/RSSWBM_' . CMS::getCurrentSite()->getID() . '_*.htm');
                    if (isset($rssFileNameOld[0])) {
                        rename($rssFileNameOld[0], PHYSICAL_PATH . 'uploads/' . $rssFileName);
                    }
                }
                ini_set('default_socket_timeout', $default_socket_timeout);
            }
            if (file_exists(PHYSICAL_PATH . 'uploads/' . $rssFileName)) {
                include (PHYSICAL_PATH . 'uploads/' . $rssFileName);
            }
        }
    }

    /**
     * Retourne l'instance du site courant
     *
     * @since 5.6
     *
     * @return Site
     */
    public static function getCurrentSite()
    {
        return self::$_currentSite;
    }

    /**
     * Instancie l'instance du site courant et charge ses modules
     *
     * @since 6.0.1
     *
     * @param  Site $oSite
     *                     Site qui doit être assigné
     * @return Site
     */
    public static function setCurrentSite(Site $oSite)
    {
        self::$_currentSite = $oSite;
        return self::$_currentSite;
    }

    /**
     * Vérifie que l'utilisateur est connecté,
     * que le module $module est activé et
     * que l'utilisateur connecté possède les profils $aProfils
     *
     * @since 5.6
     *
     * @param  Module $module
     *                          Module qui doit être activé
     * @param  array  $aProfils
     *                          Tableau de profils
     * @return void
     */
    public static function checkAccess(Module $module, Array $aProfils = array())
    {
        Utilisateur::checkConnected();
        if (! CMS::getCurrentSite()->hasModule($module)) {
            header('Location:' . SERVER_ROOT . 'cms/cms_index.php');
            exit();
        }
        if (! empty($aProfils) && ! Utilisateur::getConnected()->checkProfil($aProfils)) {
            header('Location:' . SERVER_ROOT . 'cms/cms_index.php');
            exit();
        }
    }

    /**
     * Initialise l'application en fonction du contexte
     *
     * @since 5.6
     *
     * @return void
     */
    public static function init()
    {
        $dbh = DB::getInstance();
        $SIT_CODE = '';
        if ($_SESSION['S_CONST']['SIT_CODE'] != '') { // dans le BO
            $SIT_CODE = $_SESSION['S_CONST']['SIT_CODE'];
        } elseif (($_SERVER['HTTP_HOST'] != '') && (strpos($_SERVER['PHP_SELF'], 'cms/index.php') === false)) {

            $sql = 'select SIT_CODE from DD_SITE where SIT_HOST=' . $dbh->quote($_SERVER['HTTP_HOST']);
            if ($row = $dbh->query($sql)->fetch(PDO::FETCH_ASSOC)) {
                $SIT_CODE = $row['SIT_CODE'];
            } else {
                header('Location:' . SERVER_ROOT . 'redirection.html');
                exit();
            }
        }
        if ($SIT_CODE != '') {
            // Initialisation du site courant
            $oSite = new Site($SIT_CODE);
            self::setCurrentSite($oSite);
        } else {
            // ce cas arrive uniquement si appel par un CRON
        }
    }

    /**
     * Recharge les sessions de l'utilisateur puis le redirige (si pas d'alerte)
     *
     * @since 5.6
     *
     * @param string $SIT_CODE
     *                         Code du site pour lequel on doit charger les sessions
     * @param string $location
     *                         Url de redirection
     */
    public static function redirect($SIT_CODE, $location = null)
    {
        Utilisateur::getConnected()->initSession($SIT_CODE);
        // le super admin passe toujours
        if (! Utilisateur::getConnected()->isRoot(true)) {

            // Si pas d'accès au site demandé, ==> logout
            $aSite = Utilisateur::getConnected()->getSites(true);
            if (empty($aSite) || ! in_array($SIT_CODE, array_keys($aSite))) {
                header('Location:' . SERVER_ROOT . 'cms/index.php?logout=1');
                exit();
            }

            // pas d'alerte tout le monde passe
            require_once CLASS_DIR . 'class.db_alerte.php';
            if ($oAlerte = Alerte::getCurrent($SIT_CODE)) {
                // alerte non bloquante tout le monde passe
                if ($oAlerte->isLocked()) {
                    // alerte non generale et admin site on passe
                    if ($oAlerte->isGenerale() || ! Utilisateur::getConnected()->isRoot()) {
                        return $oAlerte;
                    }
                }
            }
        }

        // On conserve l'URL absolue (avec protocole) pour éviter les attaques de type "Open Redirect"
        if (! empty($location)) {
            header('Location:' . (($_SERVER['HTTPS'] != 'on') ? 'http' : 'https') . '://' . $_SERVER['HTTP_HOST'] . $location);
            exit();
        }
        header('Location:' . SERVER_ROOT . 'cms/cms_index.php');
        exit();
    }

    /**
     * Ajoute une balise <script src="%src" {$param}></script> dans l'entete de la page
     * Si plusieurs appels sont fait avec une même source, un seul ajout est effectué
     *
     * @param  string $src
     *                          la source du fichier
     * @param  array  $params
     *                          un tableau à ajouter comme attribut à la balise (ex array('defer'=>'true') donnera defer="true")
     *                          une clé particulière existe: "condition"=>"[if lt ie7]", elle entoure la balise du code <!--[condition]><script ...><![endif]-->
     * @param  bool   $end=true
     *                          si le fichier est positionné à la fin (true) ou au début (false) de la liste des fichiers js à inclure par une balise <script />
     * @return void
     */
    public static function addJS($src, $params = array(), $end = true)
    {
        if (! array_key_exists($src, self::$_aHeader['JS'])) {
            if ($end) {
                self::$_aHeader['JS'][$src] = $params;
            } else {
                self::$_aHeader['JS'] = array_merge(array(
                    $src => $params
                ), self::$_aHeader['JS']);
            }
        }
    }

    /**
     * Ajoute du code javascript à l'évèneent onDomReady
     *
     * @param  string $str
     *                     le code javascript à ajouter
     * @return void
     */
    public static function addDOMREADY($str)
    {
        if (! in_array($str, self::$_aHeader['DOMREADY'])) {
            self::$_aHeader['DOMREADY'][] = $str;
        }
    }

    /**
     * Ajoute une balise <link rel="alternate" type="application/rss+xml" title="$title" href="$src"> dans l'entete de la page
     * Si plusieurs appels sont fait avec une même source, un seul ajout est effectué
     *
     * @param  string $src
     *                       la source du fichier
     * @param  string $title
     *                       le titre de la balise
     * @return void
     */
    public static function addRSS($src, $title)
    {
        self::$_aHeader['RSS'][$src] = $title;
    }

    /**
     * Ajoute une balise <link rel="stylesheet" href="%src" {$param}> dans l'entete de la page
     * Si plusieurs appels sont fait avec une même source, un seul ajout est effectué
     *
     * @param  string $src
     *                          la source du fichier
     * @param  array  $params
     *                          un tableau à ajouter comme attribut à la balise (ex array('media'=>'screen, print') donnera media="screen, print")
     *                          une clé particulière existe: "condition"=>"[if lt ie7]", elle entoure la balise du code <!--[condition]><link ...><![endif]-->
     * @param  bool   $end=true
     *                          si le fichier est positionné à la fin (true) ou au début (false) de la liste des fichiers css à inclure par une balise <link />
     * @return void
     */
    public static function addCSS($src, array $params = array('media' => 'screen'), $end = true)
    {
        if (! array_key_exists($src, self::$_aHeader['CSS'])) {
            if ($end) {
                self::$_aHeader['CSS'][$src] = $params;
            } else {
                self::$_aHeader['CSS'] = array_merge(array(
                    $src => $params
                ), self::$_aHeader['CSS']);
            }
        }
    }

    /**
     * Genère un fichier css à partir du fichier less et appel addCSS dessus
     *
     * @param  string $srcLess
     *                          la source du fichier less, si le fichier est un .css, il sera traité comme tel
     * @param  array  $params
     *                          un tableau à ajouter comme attribut à la balise (ex array('media'=>'screen, print') donnera media="screen, print")
     *                          une clé particulière existe: "condition"=>"[if lt ie7]", elle entoure la balise du code <!--[condition]><link ...><![endif]-->
     * @param  bool   $end=true
     *                          si le fichier est positionné à la fin (true) ou au début (false) de la liste des fichiers css à inclure par une balise <link />
     * @return void
     */
    public static function addLESS($srcLess, array $params = array('media' => 'screen'), $end = true)
    {
        if (preg_match('/\.css$/', basename($srcLess))) {
            CMS::addCSS($srcLess, $params, $end);

            return true;
        } elseif (! preg_match('/\.less$/', basename($srcLess))) {
            throw new Exception('Le fichier less n\'est pas valide!');
        }
        // On calcule le chemin physique du fichier
        $physicalDir = PHYSICAL_PATH . preg_replace('#^' . preg_quote(SERVER_ROOT, '#') . '#', '', dirname($srcLess));
        $srcLessPhysical = $physicalDir . '/' . basename($srcLess);

        if (! file_exists($srcLessPhysical)) {
            throw new Exception('Le fichier less "<code>' . $srcLessPhysical . '</code>" n\'existe pas !');
        }
        // On construit le nom du css final à reprennant les éléments d'arborescence du fichier source (fichier less)
        $aDir = explode(DIRECTORY_SEPARATOR, dirname($srcLess));
        $srcCss = CMS::getCurrentSite()->getID() . implode('_', $aDir) . '_' . basename($srcLess);
        $srcCss = preg_replace('#\.less$#', '.css', $srcCss);
        $srcCssPhysical = UPLOAD_STYLE_PHYSIQUE . $srcCss;

        // instanciation du less & import des répertoires
        require_once PHYSICAL_PATH . 'include/lessCss/lessc.inc.php';
        $oLess = new lessc();

        // Traitements des dossiers d'import en définissant
        // la priorité selon GBS_CODE > GAB_CODE > LESS_IMPORT_PATH
        // lorsque qu'un fichiers less inclut est présent au sein des différents dossiers d'import
        if (CMS::getCurrentSite() && CMS::getCurrentSite()->exist()) {
            // GBS_PATH éventuel prioritaire
            if ((CMS::getCurrentSite()->getField('GBS_PATH') != '') && is_dir(PHYSICAL_PATH . dirname(CMS::getCurrentSite()->getField('GBS_PATH')))) {
                $oLess->addImportDir(PHYSICAL_PATH . dirname(CMS::getCurrentSite()->getField('GBS_PATH')));
            }

            if (is_dir(PHYSICAL_PATH . dirname(CMS::getCurrentSite()->getField('GAB_CSS_PATH')))) {
                $oLess->addImportDir(PHYSICAL_PATH . dirname(CMS::getCurrentSite()->getField('GAB_CSS_PATH')));
            }
        }

        if (defined('LESS_IMPORT_PATH') && (LESS_IMPORT_PATH != '')) {
            $aPaths = unserialize(LESS_IMPORT_PATH);
            foreach ($aPaths as $path) {
                if (is_dir($path)) {
                    $oLess->addImportDir($path);
                }
            }
        }

        // On ne génére le fichier css que si le fichier css n'existe pas ou que si la date de modif du fichier less > date de modif du fichier css
        if (! file_exists($srcCssPhysical) || CMS::checkLESSDependecies($oLess, $srcLessPhysical, $srcCssPhysical)) {
            $cssDir = str_replace(PHYSICAL_PATH, SERVER_ROOT, dirname($srcLessPhysical)) . '/';
            // cssDir : Variable contenant le chemin vers les images
            // edition : 1 si édition, -1 sinon
            $oLess->setVariables(array(
                'cssDir' => '"' . $cssDir . '"',
                'edition' => (CMS::$edition ? 1 : - 1)
            ));

            // Compresser ou non le less
            if (MINIFY_CSS) {
                $oLess->setFormatter("compressed");
            } else {
                // Si le css n'est pas compressé on conserve les commentaires
                $oLess->setPreserveComments(true);
            }

            $css = $oLess->compileFile($srcLessPhysical);

            // Gestion des imports des fichiers ".css" (les imports ".less" sont directement inclus)
            if (preg_match('#.cms_importfiles\s*\{\s*([^\}]+)\s*\}#Usi', $css, $aFileImports)) {
                if (preg_match_all('#url\(([^\)]+)\)#Usi', $aFileImports[1], $files)) {
                    $importFiles = '';
                    foreach ($files[1] as $file) {
                        $importFiles .= "@import url($file);\n";
                    }
                    $css = $importFiles . $css;
                }
                $css = preg_replace('#\s*.cms_importfiles\s*\{\s*([^\}]+)\s*\}\s*#Usi', '', $css);
            }
            $css = '/*generated on ' . date('r') . "*/\n" . $css;
            file_put_contents($srcCssPhysical, $css);
        }
        CMS::addCSS(UPLOAD_STYLE . $srcCss, $params, $end);
    }

    /**
     * Compare les dates de tous les fichiers @import dans le less en plus de celui ci
     *
     * @param  string $srcLessPhysical
     *                                 fichier less
     * @param  string $srcCssPhysical
     *                                 fichier css
     * @return bool
     */
    public static function checkLESSDependecies($oLess, $srcLessPhysical, $srcCssPhysical)
    {
        if (filectime($srcLessPhysical) > filectime($srcCssPhysical)) {
            return true;
        }

        if ($sLess = file_get_contents($srcLessPhysical)) {
            $sFiles = array();
            if (preg_match_all("#\@import\s+(url\(\s*)?['\"]?([^'\"\)]+)['\"]?(\s*\))?\s*;#", $sLess, $sFiles) && ! empty($sFiles[2])) {
                foreach ($sFiles[2] as $dependency) {
                    if (strpos($dependency, '.css') === false) {
                        if (strpos($dependency, '.less') === false) {
                            $dependency .= '.less';
                        }

                        foreach ($oLess->importDir as $dir) {
                            $dir = $dir . (substr($dir, - 1) != '/' ? '/' : '');
                            if (file_exists($dir . $dependency) && filectime($dir . $dependency) > filectime($srcCssPhysical)) {
                                return true;
                            }
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Ajoute la chaine de texte (sans aucun traitement) dans l'entete de la page
     *
     * @param  string $str
     *                     la directive à ajouter
     * @return void
     */
    public static function addHEADER($str)
    {
        if (! in_array($str, self::$_aHeader['HEADER'])) {
            self::$_aHeader['HEADER'][] = $str;
        }
    }

    /**
     * Ajoute la meta dans l'entete de la page,
     *
     * @param $property la
     *            propriété (si déjà déclarée, la propriété précédente est remplacée)
     * @param $content la
     *            valeur (non encodée)
     *
     * @return void
     */
    public static function addMETAPROPERTY($property, $content)
    {
        if ($content) {
            self::$_aHeader['METAPROPERTY'][$property] = $content;
        } else {
            unset(self::$_aHeader['METAPROPERTY'][$property]);
        }
    }

    /**
     * Ajoute la chaine de texte au fil d'ariane, comme nouvel élement
     * Attention, selon la structure HTML du fil d'ariane, la méthode loadHeader doit être modifiée
     *
     * @param  string $str
     *                      le texte à ajouter
     * @param  string $href
     *                      un lien href optionnel qui entoure l'avant dernier élément
     * @return void
     */
    public static function addToARIANE($str, $href = '')
    {
        self::$_aHeader['ARIANE'] = array(
            'text' => $str
        );
        if ($href) {
            self::$_aHeader['ARIANE']['href'] = $href;
        }
    }

    /**
     * Remplace le contenu de la 1ere balise h1 de la page (balise <h1> seule, sans classe).
     * Astuce : si on ne souhaite pas remplacer le 1er h1 du code (menu par exemple) il suffit d'y rajouter un classe de type (<h1 class="dummy">)
     *
     * @param $str le
     *            titre (non encodé) à remplacer
     * @return void
     */
    public static function replaceTITREPAGE($str)
    {
        self::$_aHeader['TITREPAGE'] = $str;
    }

    /**
     * Remplace le contenu du title de la page
     *
     * @param $str le
     *            texte (non encodé) à remplacer
     * @param $concat [AFTER|BEFORE]
     *            si le texte doit se concaténer au précédent replaceTITLE (faux par défaut)
     * @return void
     */
    public static function replaceTITLE($str, $concat = false)
    {
        if ($concat == 'AFTER' || $concat == 'BEFORE') {
            if ($concat == 'AFTER') {
                self::$_aHeader['TITLE'] = strval(self::$_aHeader['TITLE']) . $str;
            } else {
                self::$_aHeader['TITLE'] = $str . strval(self::$_aHeader['TITLE']);
            }
        } else {
            self::$_aHeader['TITLE'] = $str;
        }
    }

    /**
     * Remplace le contenu de la metadescription de la page
     *
     * @param $str la
     *            metadescription (non encodée) à remplacer
     * @return void
     */
    public static function replaceMETADESCRIPTION($str)
    {
        self::$_aHeader['METADESCRIPTION'] = $str;
    }

    /**
     * Prend en compte tous les différents appels aux méthodes replaceXX et addYY
     * Cette méthode est appellé dans les fichiers index.php et cms_pseudo.php via register_shutdown_function
     * Vérifie également si le champ "Google analytics" du site courant existe et le restitue
     *
     * @return void
     */
    public static function loadHeader()
    {
        $ob = ob_get_contents();
        ob_clean();

        $_file = array();
        foreach (self::$_aHeader['CSS'] as $src => $param) {
            if (array_key_exists('condition', $param)) {
                $_file[] = '<!--' . $param['condition'] . '>';
            }
            $args = array();
            foreach ($param as $key => $val) {
                if ($key == 'condition') {
                    continue;
                }
                $args[] = $key . '="' . $val . '"';
            }
            $_file[] = sprintf('<link rel="stylesheet" href="%s" %s>', $src, implode(' ', $args));
            if (array_key_exists('condition', $param)) {
                $_file[] = '<![endif]-->';
            }
        }
        foreach (self::$_aHeader['RSS'] as $src => $title) {
            $_file[] = '<link rel="alternate" type="application/rss+xml" title="' . $title . '" href="' . $src . '">';
        }
        foreach (self::$_aHeader['HEADER'] as $str) {
            $_file[] = $str;
        }
        foreach (self::$_aHeader['METAPROPERTY'] as $property => $content) {
            $_file[] = '<meta property="' . secureInput($property) . '" content="' . secureInput($content) . '">';
        }
        // Inclusion du script GA conforme à la CNIL
        if (CMS::$mode == 'ON_' && (CMS::getCurrentSite()->getField('SIT_GA_TAG') != '') && (CMS::getCurrentSite()->getField('SIT_GA_TAG_CNIL') == 1)) {
            self::addJS(SERVER_ROOT . 'include/js/tagAnalyticsCNIL.php');
            self::addDOMREADY('tagAnalyticsCNIL.CookieConsent.start();');
        }
        foreach (self::$_aHeader['JS'] as $src => $param) {
            if (array_key_exists('condition', $param)) {
                $_file[] = '<!--' . $param['condition'] . '>';
            }
            $args = array();
            foreach ($param as $key => $val) {
                if ($key == 'condition') {
                    continue;
                }
                $args[] = $key . '="' . $val . '"';
            }
            $_file[] = sprintf('<script src="%s" %s></script>', $src, implode(' ', $args));
            if (array_key_exists('condition', $param)) {
                $_file[] = '<![endif]-->';
            }
        }
        if (CMS::$mode == 'ON_' && (CMS::getCurrentSite()->getField('SIT_GA_TAG') != '') && (CMS::getCurrentSite()->getField('SIT_GA_TAG_CNIL') != 1)) {
            $_file[] = CMS::getCurrentSite()->getField('SIT_GA_TAG');
        }
        if (! empty($_file)) {
            $_file[] = '</head>';
            $ob = str_replace('</head>', implode("\n", $_file), $ob);
        }
        if (! empty(self::$_aHeader['ARIANE'])) {
            $str = encode(self::$_aHeader['ARIANE']['text']);
            if (self::$_aHeader['ARIANE']['href']) {
                $str = '<a href="' . self::$_aHeader['ARIANE']['href'] . '" itemprop="url">' . $str . '</a>';
            }
            $ob = preg_replace('@(<div id="ariane">)(.*)(</div>)@sUu', '${1}${2} &gt; <span itemscope itemtype="http://data-vocabulary.org/Breadcrumb"><span itemprop="title">' . $str . '</span></span>${3}', $ob);
        }
        if (! is_null(self::$_aHeader['TITREPAGE'])) {
            if (self::$_aHeader['TITREPAGE'] == '') {
                $ob = preg_replace('/(<h1>(<span[^>]*>)?.*(<\/span>)?<\/h1>)/sUu', '', $ob, 1);
            } else {
                $ob = preg_replace('/<h1>(<span[^>]*>)?(.*)(<\/span>)?<\/h1>/sUu', '<h1>${1}' . encode(self::$_aHeader['TITREPAGE']) . '${3}</h1>', $ob, 1);
            }
        }
        if (! is_null(self::$_aHeader['TITLE'])) {
            $ob = preg_replace('/<title>[^<]*<\/title>/su', '<title>' . secureInput(self::$_aHeader['TITLE']) . '</title>', $ob, 1);
        }
        if (! empty(self::$_aHeader['METADESCRIPTION'])) {
            $ob = preg_replace('/<meta name="Description" content="[^"]*">/su', '<meta name="Description" content="' . secureInput(self::$_aHeader['METADESCRIPTION']) . '">', $ob, 1);
        }
        if (! empty(self::$_aHeader['DOMREADY'])) {
            $str = '<script>' . "\n";
            $str .= '$(document).ready(function () {';
            $str .= implode("\n", self::$_aHeader['DOMREADY']);
            $str .= "\n});\n</script></body>";
            $ob = str_replace('</body>', $str, $ob);
        }
        echo $ob;
    }

    public static function isMobile()
    {
        if (! isset($_SESSION['CMS_isMobile'])) {
            $useragent = $_SERVER['HTTP_USER_AGENT'];
            $_SESSION['CMS_isMobile'] = preg_match('/android.+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i', $useragent) || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|e\-|e\/|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(di|rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|xda(\-|2|g)|yas\-|your|zeto|zte\-/i', substr($useragent, 0, 4));
        }
        return $_SESSION['CMS_isMobile'];
    }
}
