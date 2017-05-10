<?php
ob_start();
$currentCookieParams = session_get_cookie_params();
session_set_cookie_params(
    $currentCookieParams["lifetime"],
    $currentCookieParams["path"],
    $currentCookieParams["domain"],
    $currentCookieParams["secure"],
    true
);
session_start();
/**
 * Quelques outils destinés à l'équipe développement
 * @author Harmen.Christophe<harmen.christophe@businessdecision.com>
 */
require dirname(__FILE__) . '/../include/config.php';
require (dirname(__FILE__).'/../include/config_module_admin.php');
require CLASS_DIR . 'class.DB.php';
require CLASS_DIR . 'class.db_utilisateur.php';
require CLASS_DIR . 'class.File_management.php';
require CLASS_DIR . 'class.db_webotheque.php';
require_once CLASS_DIR . 'class.db_page.php';
require dirname(__FILE__) . '/include/class/class.cms.application.php';
// ERROR AND SLOW HTTP REQUEST
require_once PHYSICAL_PATH . 'admin/include/inc.errorHandler.php';

$dbh = DB::getInstance();

$app = application :: getInstance();
$app->checkInitialInstall();

if (!Utilisateur::isConnected() || !Utilisateur::getConnected()->isRoot(true)) {
    header('Location:' . SERVER_ROOT . 'cms/index.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit ();
}

$app->availableMdl->loadModules();
$app->registeredMdl->loadModules();

$error = $msg = '';


/******************************************************************************
 * Contraintes systèmes
 */
/* NON disponible pour le moment
$checkSystem = !empty($_GET['checkSystem']) ? $_GET['checkSystem'] : null;
if ($checkSystem) {
    try {
        if (!appSystemCheck($app->dbh,$_e)) {
            $msg = "<p>La configuration du système n'est pas conforme :</p><ul class=\"error\"><li>".implode('</li><li>',$_e)."</li></ul>";
        } else {
            $msg = '<p class="valide">La configuration système est conforme.</p>';
        }
    } catch (Exception $e) {
        $error .= '<p>'.$e->getMessage().'</p><pre> Trace :'.$e->getTraceAsString().'</pre>';
    }
}
//*/

/******************************************************************************
 * Sauvegarge de la DB
 */
if (file_exists(ADMIN_LOG_DIR)) {
    $dbDump = !empty($_GET['dbDump']) ? $_GET['dbDump'] : null;
    $dbFormat = in_array($_GET['dbFormat'], array('xml','sql')) ? $_GET['dbFormat'] : 'sql';
    if (ADMIN_DB_SAVE && $dbDump && $dbFormat) {
        $dbDumpFile = DB_NAME.'_'.strftime('%Y-%m-%d_%H-%M-%S').'.'.$dbFormat;
        try {
            $app->dumpDB($dbDumpFile);
            $msg = "<br><p class=\"valide\">Une sauvegarde de la base a été créée : \"<strong>".ADMIN_LOG_DIR.$dbDumpFile.".gz</strong>\".</p>";
        } catch (Exception $e) {
            $msg = "<br><p class=\"error\">".$e->getMessage()."</p>";
        }
    }
}
function getExtension($f)
{
    $f = explode('.',basename($f));
    if (count($f) <= 1) { return ''; }

    return strtolower($f[count($f)-1]);
}

/******************************************************************************
 * Reverse sur la structure actuelle
 */
$dbStructReverse = !empty($_GET['dbStructReverse']) ? $_GET['dbStructReverse'] : null;
$dbStructDl = !empty($_GET['dbStructDl']) ? $_GET['dbStructDl'] : null;
if ($dbStructReverse) {
    try {
        $_s = new dbStruct($app->dbh);
        $msg = $_s->getSchemaFileContent($app);
        if ($dbStructDl) {
            require_once dirname(__FILE__) . '/../include/lib.common.php';
            ob_end_clean();
            header('Pragma: public');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Content-disposition: attachment; filename="db-schema_'.filenameToRfc1738($app->registeredMdl->moduleInfo('core','version')).'_'.strftime('%Y_%m_%d').'.php"');
            header('Content-type: text/plain');
            echo $msg;
            exit;
        }
    } catch (Exception $e) {
        $error .= '<p>'.$e->getMessage().'</p><pre>Traces :'.$e->getTraceAsString().'</pre>';
    }
}

/******************************************************************************
 * Diff entre la DB et les schémas déclarés
 */
$dbStructCmp = !empty($_GET['dbStructCmp']) ? $_GET['dbStructCmp'] : null;
$dbStructDl = !empty($_GET['dbStructDl']) ? $_GET['dbStructDl'] : null;
if ($dbStructCmp) {
    try {
        $_s = new dbStruct($app->dbh);
        // Inclusion de l'ensemble des "db-schema.php"
        $mdl = $app->availableMdl->getModules();
        foreach ($mdl as $id => $m) {
            if (file_exists(PHYSICAL_PATH.$app->availableMdl->moduleInfo($id,'root').'/db-schema.php')) {
                require PHYSICAL_PATH.$app->availableMdl->moduleInfo($id,'root').'/db-schema.php';
            }
        }
        $si = new dbStruct($app->dbh);
        $dbCmp = $si->compare($_s);
        $msg = '';
        $a = array();
        $a['Eléments ajoutés dans les fichiers <code>dbStruct</code>'] = array(
            'tables' => $dbCmp['table_create'],
            'fields' => $dbCmp['field_create'],
            'keys' => $dbCmp['key_create'],
            'indexes' => $dbCmp['index_create'],
            'references' => $dbCmp['reference_create']
        );
        $a['Eléments présents dans les fichiers <code>dbStruct</code> représentant une structure différente de celle de la base'] = array(
            'fields' => $dbCmp['field_update'],
            'keys' => $dbCmp['key_update'],
            'indexes' => $dbCmp['index_update'],
            'references' => $dbCmp['reference_update']
        );
        $a['Eléments absents des fichiers <code>dbStruct</code>'] = array(
            'tables' => $dbCmp['table_delete'],
            'fields' => $dbCmp['field_delete'],
            'keys' => $dbCmp['key_delete'],
            'indexes' => $dbCmp['index_delete'],
            'references' => $dbCmp['reference_delete']
        );
        $differ = false;
        foreach ($a as $k => $v) {
            if (!empty($v['tables']) || !empty($v['fields']) ||
                !empty($v['keys']) || !empty($v['indexes']) || !empty($v['references'])
            )
            {
                $differ = true;
                $msg .= "\n\n<h2># ".$k."</h2>\n";
                $s = dbStruct :: fromArray($v);
                $msg .= "\n<pre class=\"error\">".$s."</pre>\n";
            }
        }
        if ($differ && !$dbStructDl) {
            if ($app->signatureIsUptodate()) {
                $msg .= '<br><p class="valide"><strong>Attention :</strong> Les signatures des différents modules présents sur le système de fichier sont identiques à celles enregistrées en base de données (pas d\'installation automatique possible).</p>';
            }
        }
        if ($differ && $dbStructDl) {
            ob_end_clean();
            header('Pragma: public');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Content-disposition: attachment; filename="db-schema_vs_DB_'.strftime('%Y_%m_%d').'.php"');
            header('Content-type: text/plain');
            echo "<?php".strip_tags($msg)."?>";
            exit;
        }
        if (empty($msg)) {
            $msg .= '<p class="valide">';
            $msg .= 'Aucune différence n\'a été détectée entre la structure de la base de données et celle décrite par les fichiers <code>dbstruct</code>. ';
            if (!$app->signatureIsUptodate()) {
                $msg .= 'Cependant les signatures doivent être mise à jour.';
            }
            $msg .= '</p>';
        }
    } catch (Exception $e) {
        $error .= '<p>'.$e->getMessage().'</p><pre> Trace :'.$e->getTraceAsString().'</pre>';
    }
}

/******************************************************************************
 * Phpinfo
 */
$phpinfo = !empty($_GET['phpinfo']) ? $_GET['phpinfo'] : null;
if ($phpinfo) {
    header('Content-type: text/html; charset=utf-8');
    phpinfo();
    $b = ob_get_contents();
    ob_clean();
    $b = str_ireplace('<div class="center">', '<div class="center"><p><a href="'.$_SERVER["PHP_SELF"].'"><strong>"Informations et outils d\'administration"</strong></a></p>', $b);
    echo $b;
    exit;
}

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/HTML; charset=utf-8">
        <meta http-equiv="Content-script-type" content="text/javascript">
        <meta http-equiv="Content-style-type" content="text/css">
        <meta http-equiv="Content-language" content="fr">
        <meta http-equiv="expires" content="0">
        <meta name="Robots" content="noindex, nofollow">
        <meta name="Author" content="EOLAS">
        <title>Informations et outils d'administration</title>
        <link rel="shortcut icon" href="<?php echo SERVER_ROOT?>images/favicon.ico" type="image/x-icon">
        <link rel="stylesheet" href="<?php echo SERVER_ROOT?>include/css/bo.css">
        <link rel="stylesheet" href="<?php echo SERVER_ROOT?>include/css/bo_pseudo.css">
        <link rel="stylesheet" href="<?php echo SERVER_ROOT?>include/css/print.css" media="print">
        <link rel="stylesheet" href="<?php echo SERVER_ROOT?>include/js/jquery/ui/jquery-ui.min.css">
        <link rel="stylesheet" href="<?php echo SERVER_ROOT ?>include/js/jquery/colorbox/colorbox.css">
        <link rel="stylesheet" href="<?php echo ADMIN_ROOT?>include/css/admin.css">
        <script>var SERVER_ROOT = '<?php echo SERVER_ROOT?>';</script>
        <script>var cms_lang = 'fr';</script>
        <script type="text/javascript">
            function expande_collapse_signatures(node)
            {
                node.focus();
                while (node && node.nodeName != 'TR') {
                    node = node.parentNode;
                }
                var cEl = node.getElementsByTagName('dl');
                for (var i=0; cEl[i]; i++) {
                    if (cEl[i].style.display == 'none') {
                        cEl[i].style.display = "block";
                    } else {
                        cEl[i].style.display = "none";
                    }
                }
            }
        </script>
        <script src="<?php echo SERVER_ROOT?>include/js/formCtrl.js"></script>
        <script src="<?php echo SERVER_ROOT?>include/js/formCtrl-fr.js"></script>
        <script src="<?php echo SERVER_ROOT?>include/js/jquery/jquery.min.js"></script>
        <script src="<?php echo SERVER_ROOT?>include/js/jquery/ui/jquery-ui.min.js"></script>
        <script src="<?php echo SERVER_ROOT?>include/js/jquery/ui/i18n/datepicker-fr.js"></script>
        <script src="<?php echo SERVER_ROOT?>include/js/jquery/colorbox/jquery.colorbox-min.js"></script>
        <script src="<?php echo SERVER_ROOT?>include/js/common.js"></script>
        <script src="<?php echo SERVER_ROOT?>include/js/coreBo.js"></script>
        <script type="text/javascript" src="<?php echo SERVER_ROOT?>include/js/onglet.js"></script>
        <script type="text/javascript" src="include/js/jquery.tablesorter/jquery.tablesorter.min.js"></script>
        <script type="text/javascript" src="include/js/jquery.tablesorter/jquery.metadata.js"></script>
        <script type="text/javascript">
            $(document).ready(function () {
                    $("#slowrequest_table").tablesorter(
                        {
                            sortList: [[0,1]]
                        }
                    );
                    //external
                    $(document).on('click', 'a.external', function (e) {
                        e.preventDefault();
                        window.open(this.href);

                        return false;
                    });
                }
            );
        </script>
    </head>
    <body>
        <div id="document">
            <div id="bandeau_haut">&nbsp;</div>
            <div id="corps" class="creation">
                <h1>Informations et outils d'administration</h1>
                <?php
                if (!$app->isUptodate()) {
                    echo '<div class="error aligncenter"><strong>L\'accès au back office est actuellement bloqué</strong> aux utilisateurs
                                ayant un profil autre que "Super Administrateur" <strong>car l\'application doit être mise à jour</strong>.</div>';
                }
                ?>
                <?php
                // On ne propose le lien vers la gestion des gabarits que si le noyau est à jour
                // car la gestion des gabarits necessite l'appel à CMS::init()
                // (pour la gestions des appels aux fonctions de lib.common.php)
                if ($app->isUptodate('core')) {
                ?>
                <ul class="alignright">
                    <li><a href="index.php">Informations et outils d'administration</a></li>
                    <li><a href="gabarits.php">Gestion des modules CMS et des templates par gabarit</a></li>
                </ul>
                <?php
                }
                ?>
                <?php
                if (!empty($error)) {
                    echo '<div class="msg"><p><strong>Erreur</strong></p>'.$error.'</div>';
                }
                ?>
                <fieldset class="tab">
                    <legend>Modules</legend>
                    <!--<p>
                        <a href="?modulesSignatures=1#modulesSignatures" id="modulesSignatures">
                            Signatures des modules
                        </a>
                    </p>-->
                        <?php
                        /******************************************************************************
                         * Signatures des modules enregistrées et disponibles sur le système de fichier
                         */
                        //$modulesSignatures = !empty($_GET['modulesSignatures']) ? $_GET['modulesSignatures'] : null;
                        //if ($modulesSignatures) {
                            $aMdl = $app->availableMdl->getModules();
                            $rMdl = $app->registeredMdl->getModules();

                            echo '<h2>Signatures des modules</h2>';
                            echo '<table border="1" frame="hsides" rules="rows" width="80%">';
                            echo '<caption>Signatures des modules</caption>';
                            echo '<thead>
                                    <tr>
                                        <td rowspan="2"></td>
                                        <th style="width: 5%" colspan="2">Module à jour</th>
                                        <th style="width: 5%" rowspan="2">Dépendances valides</th>
                                        <th style="width: 45%" rowspan="2">Enregistrées</th>
                                        <th style="width: 45%" rowspan="2">Disponibles sur le système de fichier</th>
                                    </tr>
                                    <tr>
                                        <th>Signature</th>
                                        <th>Version</th>
                                    </tr>
                                </thead>';
                            echo '<tbody>';
                            $mdls = array_merge_recursive($aMdl, $rMdl);
                            foreach ($mdls as $id=> $m) {
                                echo '<tr>';
                                echo '<th class="alignleft"><a onclick="expande_collapse_signatures(this)" href="#mdl'.htmlspecialchars($id, ENT_QUOTES, 'UTF-8').'">'.htmlspecialchars($id, ENT_QUOTES, 'UTF-8').'</a></th>';
                                $signatureIsUptodate = $app->signatureIsUptodate($id);
                                if ($signatureIsUptodate) {
                                    $s = '<span class="txtValide">OUI</span>';
                                } elseif ($signatureIsUptodate === false) {
                                    $s = '<strong class="txtError">NON</strong>';
                                } elseif (is_null($signatureIsUptodate)) {
                                    $s = '<strong class="txtError">NA</strong>';
                                }
                                echo '<td class="aligncenter">'.$s.'</td>'; // Signature à jour
                                if ($signatureIsUptodate) {
                                    $s = '<span class="txtValide">OUI</span>';
                                } else {
                                    $isUpToDate = $app->isUptodate($id);
                                    if ($isUpToDate) {
                                        $s = '<span class="txtValide">OUI</span>';
                                    } elseif ($isUpToDate === false) {
                                        $s = '<strong class="txtError">NON</strong>';
                                    } elseif (is_null($isUpToDate)) {
                                        $s = '<strong class="txtError">NA</strong>';
                                    }
                                }
                                echo '<td class="aligncenter">'.$s.'</td>'; // Module à jour
                                $dependencyIsChecked = $app->availableMdl->dependencyIsChecked($id);
                                if ($dependencyIsChecked) {
                                    $s = '<span class="txtValide">OUI</span>';
                                } elseif ($dependencyIsChecked === false) {
                                    $s = '<strong class="txtError">NON</strong>';
                                } elseif (is_null($dependencyIsChecked)) {
                                    $s = '<span class="txtValide">NA</span>';
                                }
                                echo '<td class="aligncenter">'.$s.'</td>';
                                // Registered Modules
                                if ($rMdl = $app->registeredMdl->getModules($id)) {
                                    echo '<td>';
                                    echo '<dl class="valide" style="display: none;">';
                                    foreach ($rMdl as $k => $v) {
                                        if (!empty($v)) {
                                            echo '<dt>'.htmlspecialchars($k, ENT_QUOTES, 'UTF-8').'</dt>';
                                            if (is_array($v)) {
                                                echo '<dd><ul>';
                                                foreach ($v as $depK => $depV) {
                                                    echo '<li>'.htmlspecialchars($depK, ENT_QUOTES, 'UTF-8').' : '.htmlspecialchars(is_array($depV)?implode(', ', $depV):$depV, ENT_QUOTES, 'UTF-8').'</li>';
                                                }
                                                echo '</ul></dd>';
                                            } else {
                                                echo '<dd>'.htmlspecialchars($v, ENT_QUOTES, 'UTF-8').'</dd>';
                                            }
                                        }
                                    }
                                    echo '</dl>';
                                    echo '</td>';
                                } else {echo '<td class="aligncenter">~</td>';}
                                // Available Modules
                                if ($aMdl = $app->availableMdl->getModules($id)) {
                                    echo '<td>';
                                    echo '<dl class="valide" style="display: none;">';
                                    foreach ($aMdl as $k => $v) {
                                        if (!empty($v)) {
                                            echo '<dt>'.htmlspecialchars($k, ENT_QUOTES, 'UTF-8').'</dt>';
                                            if (is_array($v)) {
                                                echo '<dd><ul>';
                                                foreach ($v as $depK => $depV) {
                                                    echo '<li>'.htmlspecialchars($depK, ENT_QUOTES, 'UTF-8').' : '.htmlspecialchars(is_array($depV)?implode(', ', $depV):$depV, ENT_QUOTES, 'UTF-8').'</li>';
                                                }
                                                echo '</ul></dd>';
                                            } else {
                                                echo '<dd>'.htmlspecialchars($v, ENT_QUOTES, 'UTF-8').'</dd>';
                                            }
                                        }
                                    }
                                    echo '</dl>';
                                    echo '</td>';
                                } else {echo '<td class="aligncenter">~</td>';}

                                echo '</tr>';
                            }
                            echo '</tbody>';
                            echo '</table>';
                        //}
                        ?>
                        <ul>
                            <?php
                            if (!$app->signatureIsUptodate() && $app->isUptodate()) {
                                echo '<li>
                                    <p><a href="upgrade.php?upSignatures=1">Mettre à jour la signature des modules</a></p>
                                </li>';
                            }
                            if (!$app->isUptodate()) {
                                echo '<li>
                                    <p><a href="upgrade.php">Se rendre sur l\'<strong>interface de mise à jour</strong></a> de l\'application</p>
                                </li>';
                            }
                            ?>
                        </ul>
                        <p class="alignright">
                            <a href="#corps">Haut de page</a>
                        </p>
                </fieldset>
                <fieldset class="tab">
                    <legend>Base de données</legend>
                    <?php
                    if (ADMIN_DB_SAVE && file_exists(ADMIN_LOG_DIR)) {
                        echo '<h2>Sauvegarder la base</h2>';
                        echo '<ol>
                                <li><p><a href="?dbDump=1&amp;dbFormat=xml#dbDumpXml" id="dbDumpXml" title="Créer une sauvegarde de la base de données au format XML">Au format XML</a></p></li>
                                <li><p><a href="?dbDump=1&amp;dbFormat=sql#dbDumpSql" id="dbDumpSql" title="Créer une sauvegarde de la base de données au format SQL">Au format SQL</a></p></li>
                            </ol>';
                        if ($dbDump) {echo $msg;}
                    }
                    ?>
                    <h2>Fichier de description de la base de données au format <code>dbstruct</code></h2>
                    <ol>
                        <li>
                            <p><a href="?dbStructReverse=1#dbStructReverse" id="dbStructReverse">Visualiser</a></p>
                            <?php
                            if ($dbStructReverse) {
                                echo'<p><textarea rows="20" cols="100">';
                                echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8');
                                echo '</textarea></p>';
                            }
                            ?>
                        </li>
                        <li><p><a href="?dbStructReverse=1&amp;dbStructDl=1">Télécharger le fichier</a></p></li>
                    </ol>
                    <h2>Comparaison de la structure de la base avec celle décrite au sein des fichiers <code>dbstruct</code></h2>
                    <ol>
                        <li>
                            <p><a href="?dbStructCmp=1#dbStructCmp" id="dbStructCmp">
                                Calculer les éventuelles différences entre les fichiers au format <code>dbstruct</code> et la base de données
                            </a></p>
                        </li>
                    </ol>
                    <?php
                    if ($dbStructCmp) {
                        if ($differ) {
                            echo '<p><a href="?dbStructCmp=1&amp;dbStructDl=1"> &gt; Télécharger le fichier &lt;</a></p>';
                        }
                        echo $msg;
                    }
                    ?>
                    <p class="alignright">
                        <a href="#corps">Haut de page</a>
                    </p>
                    <?php
                    // Liste des sauvegardes existantes
                    if (file_exists(ADMIN_LOG_DIR)) {
                        $aFiles = array();
                        $aGlob = glob(ADMIN_LOG_DIR.DB_NAME.'_[1-2][0-9][0-9][0-9]-[0-1][0-9]-[0-3][0-9]_[0-2][0-9]-[0-6][0-9]-[0-6][0-9].*');
                        foreach ($aGlob as $file) {
                            $filename = basename($file);
                            if (in_array(getExtension($filename), array('sql','gz','xml'))) {
                                // Eventuelle suppression...
                                if (!empty($_POST['dbSaveDel']) && in_array(md5($filename), $_POST['dbSaveDel'])) {
                                    if (!@unlink($file)) {
                                        $aFiles[] = $filename;
                                    }
                                } else {
                                    $aFiles[] = $filename;
                                }
                            }
                        }
                        if (!empty($aFiles)) {
                            echo '<h2>Fichiers de sauvegarde de la base de données présents sur le serveur</h2>';
                            // action="?dbSaveDel=1" :=> Gestion des requêtes lentes
                            echo '<form method="post" action="'.$_SERVER['PHP_SELF'].'?dbSaveDel=1">
                                <table border="1" frame="hsides" rules="rows">
                                    <caption>Fichiers de sauvegarde de la base de données présents sur le serveur</caption>
                                    <thead>
                                        <tr>
                                            <th>Fichier</th>
                                            <th style="width: 10%">
                                                Supprimer
                                                <br>
                                                <input type="checkbox"
                                                    onclick="var cDbSaveDel = document.getElementsByName(\'dbSaveDel[]\'); for (i=0; cDbSaveDel[i]; i++) {cDbSaveDel[i].checked = this.checked;}"
                                                    title="Sélectionner / désélectionner l\'ensemble des fichiers listé dans cette colonne">
                                            </th>
                                    </thead>
                                    <tfoot>
                                        <tr><td colspan="2"><p class="alignright">
                                            <input type="submit" class="submit" value="Supprimer les fichiers sélectionnés"
                                                onclick="return confirm(\'Etes vous sûr de vouloir supprimer définitivement les fichiers sélectionnés ?\')">
                                        </p></td></tr>
                                    </tfoot>
                                    <tbody>';
                            foreach ($aFiles as $f) {
                                echo '<tr>
                                        <td>
                                                <label for="dbs'.md5($f).'">'.htmlspecialchars($f, ENT_QUOTES, 'UTF-8').'</label>
                                        </td>
                                        <td class="aligncenter">
                                            <input type="checkbox" name="dbSaveDel[]" id="dbs'.md5($f).'" value="'.md5($f).'"
                                                title="Supprimer le fichier &quot;'.htmlspecialchars($f, ENT_QUOTES, 'UTF-8').'&quot;">
                                        </td>
                                    </tr>';
                            }
                            echo '	</tbody>';
                            echo '</table></form>';
                            echo '<p class="alignright"><a href="#corps">Haut de page</a></p>';
                        }
                    }
                    ?>
                </fieldset>
                <fieldset class="tab">
                    <legend>Webothèque</legend>
                    <h2>Documents</h2>
                    <ul>
                        <li>
                            <p><a href="?documentIndex=1#documentIndex" id="documentIndex">
                                Lancer l'indexation des documents
                            </a></p>
                        </li>
                    </ul>
                    <?php
                    /******************************************************************************
                     * Webothèque : Indexation des documents
                     */
                    $documentIndex = !empty($_GET['documentIndex']) ? $_GET['documentIndex'] : null;
                    if ($documentIndex) {
                        set_time_limit(0);
                        $sql = "select ID_WEBOTHEQUE from WEBOTHEQUE where WBT_CODE='WBT_DOCUMENT'";
                        echo '<pre>';
                        foreach ($dbh->query($sql)->fetchAll(PDO :: FETCH_COLUMN) as $ID_WEBOTHEQUE) {
                            $oWebo = new Webo_DOCUMENT($ID_WEBOTHEQUE);
                            $oWebo->index();
                            echo '. ';
                            flush();
                        }
                        echo '</pre>';
                        // Purge du cache de l'ensemble des sites
                        Page::clearAllCache();
                    }
                    ?>
                    <?php
                    /******************************************************************************
                     * Webothèque : Les formats d'image
                     */
                    $sql = 'select * from DD_IMAGEFORMAT order by IMF_CODE';
                    $aFormat = $dbh->query($sql)->fetchAll(PDO :: FETCH_ASSOC);
                    if (!empty($aFormat)) {
                        echo '<h2 id="imageFormatDel">Formats des images</h2>';
                        if (!empty($_POST['imgFormatDel'])) {
                            // Les images de la webotheque dont il faut supprimer le cache d'un ou des formats sélectionés
                            $sql = "select * from WEBOTHEQUE where WBT_CODE='WBT_IMAGE'";
                            $rowListe = $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
                        }
                        // action="?imageFormatDel=1" :=> Gestion des requêtes lentes
                        echo '<form method="post" action="'.$_SERVER['PHP_SELF'].'?imageFormatDel=1#imageFormatDel">
                                <table border="1" frame="hsides" rules="rows">
                                <caption>Formats des images présents en base de données</caption>
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Libellé</th>
                                        <th>Largeur</th>
                                        <th>Hauteur</th>
                                        <th>Méthode de génération</th>
                                        <th>Coefficient de qualité</th>
                                        <th style="width: 10%">
                                            Supprimer le cache
                                            <br>
                                            <input type="checkbox"
                                                onclick="var cImgFormatDel = document.getElementsByName(\'imgFormatDel[]\'); for (i=0; cImgFormatDel[i]; i++) {cImgFormatDel[i].checked = this.checked;}"
                                                title="Sélectionner / désélectionner l\'ensemble des formats listé dans cette colonne">
                                        </th>';
                        if (!empty($_POST['imgFormatDel'])) {
                            echo '		<th>Rapport de la suppression du cache</th>';
                        }
                        echo '		</tr>
                                </thead>
                                <tfoot>
                                    <tr><td colspan="6"><p class="alignright">
                                        <input type="submit" class="submit" value="Supprimer le cache des formats sélectionnés">
                                    </p></td></tr>
                                </tfoot>
                                <tbody>';
                        $i = 0; // Compteur de images supprimées pour mettre à jour éventuellement le parsing
                        foreach ($aFormat as $format) {
                            echo '<tr>
                                    <td><code>'.$format['IMF_CODE'].'_'.$format['GAB_CODE'].'</code></td>
                                    <td>'.htmlspecialchars($format['IMF_LIBELLE'], ENT_QUOTES, 'UTF-8').'</td>
                                    <td class="aligncenter">'.$format['IMF_LARGEUR'].'</td>
                                    <td class="aligncenter">'.$format['IMF_HAUTEUR'].'</td>
                                    <td class="aligncenter">'.$format['IMF_ACTION'].'</td>
                                    <td class="aligncenter">'.$format['IMF_QUALITE'].'</td>
                                    <td class="aligncenter">
                                        <input type="checkbox" name="imgFormatDel[]" id="'.$format['IMF_CODE'].'@'.$format['GAB_CODE'].'" value="'.$format['IMF_CODE'].'@'.$format['GAB_CODE'].'"
                                            title="Supprimer le cache du format &quot;'.htmlspecialchars($format['IMF_CODE'].' du gabarit '.$format['GAB_CODE'], ENT_QUOTES, 'UTF-8').'&quot;">
                                    </td>';
                            // Eventuelle suppression du cache du format...
                            if (!empty($_POST['imgFormatDel']) && in_array($format['IMF_CODE'].'@'.$format['GAB_CODE'], $_POST['imgFormatDel'])) {
                                echo '<td><pre>';
                                $j = 0; // Comptage du nombre d'images supprimées pour ce format
                                $dir = UPLOAD_IMAGE_PHYSIQUE;
                                foreach ($rowListe as $row) {
                                    $file = $dir . dirname($row['WEB_CHEMIN']) . '/' . $format['IMF_CODE'] . '/' . $format['GAB_CODE'] . '/' . basename($row['WEB_CHEMIN']);
                                    if (File_management::deleteFromName($file)) {
                                        echo "\n* " . substr($file, strlen(PHYSICAL_PATH));
                                        flush();
                                        $i++;
                                        $j++;
                                    }
                                }
                                // Les images autres
                                if ($format['IMF_DE'] != '') {
                                    $aDE = array_filter(explode('@', $format['IMF_DE']));
                                    foreach ($aDE as $DE) {
                                        $_a = explode(':', $DE); // De la forme NOM_TABLE:NOM_CHAMP ou NOM_TABLE:NOM_CHAMP:NOM_REP_PHYSIQUE
                                        $sql = "select " . $_a[1] . " from " . $_a[0] . " where " . $_a[1] . "<>''";
                                        $dir = !empty($_a[2]) && defined($_a[2]) ? constant($_a[2]) : UPLOAD_EXTERNE_PHYSIQUE;
                                        foreach ($dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN) as $file) {
                                            $file = $dir . dirname($file) . '/' . $format['IMF_CODE'] . '/' . $format['GAB_CODE'] . '/' . basename($file);
                                            if (File_management::deleteFromName($file)) {
                                                echo "\n* " . substr($file, strlen(PHYSICAL_PATH));
                                                flush();
                                                $i++;
                                                $j++;
                                            }
                                        }
                                    }
                                }
                                if ($j == 0) {
                                    echo 'Aucune image supprimée';
                                }
                                echo '</pre></td>';
                            }
                        }
                        echo '	</tbody>';
                        echo '</table></form>';
                    }
                    if ($i > 0) {
                        $sql = 'update OFF_PARAGRAPHE set PAR_APARSER=1';
                        $dbh->exec($sql);
                        $sql = 'update ON_PARAGRAPHE set PAR_APARSER=1';
                        $dbh->exec($sql);
                        // Purge du cache de l'ensemble des sites
                        Page::clearAllCache();
                    }
                    ?>

                    <h2 id="purgeLIAISON">Liaisons</h2>
                    <ul>
                        <li><a href="?purgeLIAISON=1#purgeLIAISON">Vérifier les liaisons orphelines</a></li>
                        <li><a href="?purgeLIAISON=2#purgeLIAISON" onclick="return confirm('Etes-vous sur ?')">Purger les liaisons orphelines</a></li>
                    </ul>
                    <?php
                    if (!empty($_GET['purgeLIAISON'])) {
                        set_time_limit(0);
                        $sql = "select * from DD_LIAISON";
                        $nbTotal = 0;
                        foreach ($dbh->query($sql) as $row) {
                            $sql = "select count(ID_LIAISON_WEBOTHEQUE) from LIAISON_WEBOTHEQUE where
                                LIA_CODE=" . $dbh->quote($row['LIA_CODE']) . "
                                and ID_LIAISON not in (select " . $row['LIA_NOM_CHAMP_ID'] . " from " . $row['LIA_CODE'] . ")";
                            $nb = $dbh->query($sql)->fetchColumn();
                            if ($nb > 0) {
                                echo '<br>' . $sql . ' : ' . $nb;
                                if ($_GET['purgeLIAISON'] == 2) {
                                    $sql = "delete from LIAISON_WEBOTHEQUE where
                                        LIA_CODE=" . $dbh->quote($row['LIA_CODE']) . "
                                        and ID_LIAISON not in (select " . $row['LIA_NOM_CHAMP_ID'] . " from " . $row['LIA_CODE'] . ")";
                                    $dbh->exec($sql);
                                }
                                $nbTotal += $nb;
                            }
                            $sql = "select count(ID_LIAISON_PAGE) from LIAISON_PAGE where
                                LIA_CODE=" . $dbh->quote($row['LIA_CODE']) . "
                                and ID_LIAISON not in (select " . $row['LIA_NOM_CHAMP_ID'] . " from " . $row['LIA_CODE'] . ")";
                            $nb = $dbh->query($sql)->fetchColumn();
                            if ($nb > 0) {
                                echo '<br>' . $sql . ' : ' . $nb;
                                if ($_GET['purgeLIAISON'] == 2) {
                                    $sql = "delete from LIAISON_PAGE where
                                        LIA_CODE=" . $dbh->quote($row['LIA_CODE']) . "
                                        and ID_LIAISON not in (select " . $row['LIA_NOM_CHAMP_ID'] . " from " . $row['LIA_CODE'] . ")";
                                    $dbh->exec($sql);
                                }
                                $nbTotal += $nb;
                            }
                            $sql = "select count(ID_LIAISON_EXTERNE) from LIAISON_EXTERNE where
                                LIA_CODE_TO=" . $dbh->quote($row['LIA_CODE']) . "
                                and ID_LIAISON_TO not in (select " . $row['LIA_NOM_CHAMP_ID'] . " from " . $row['LIA_CODE'] . ")";
                            $nb = $dbh->query($sql)->fetchColumn();
                            if ($nb > 0) {
                                echo '<br>' . $sql . ' : ' . $nb;
                                if ($_GET['purgeLIAISON'] == 2) {
                                    $sql = "delete from LIAISON_EXTERNE where
                                        LIA_CODE_TO=" . $dbh->quote($row['LIA_CODE']) . "
                                        and ID_LIAISON_TO not in (select " . $row['LIA_NOM_CHAMP_ID'] . " from " . $row['LIA_CODE'] . ")";
                                    $dbh->exec($sql);
                                }
                                $nbTotal += $nb;
                            }
                            $sql = "select count(ID_LIAISON_EXTERNE) from LIAISON_EXTERNE where
                                LIA_CODE_FROM=" . $dbh->quote($row['LIA_CODE']) . "
                                and ID_LIAISON_FROM not in (select " . $row['LIA_NOM_CHAMP_ID'] . " from " . $row['LIA_CODE'] . ")";
                            $nb = $dbh->query($sql)->fetchColumn();
                            if ($nb > 0) {
                                echo '<br>' . $sql . ' : ' . $nb;
                                if ($_GET['purgeLIAISON'] == 2) {
                                    $sql = "delete from LIAISON_EXTERNE where
                                        LIA_CODE_FROM=" . $dbh->quote($row['LIA_CODE']) . "
                                        and ID_LIAISON_FROM not in (select " . $row['LIA_NOM_CHAMP_ID'] . " from " . $row['LIA_CODE'] . ")";
                                    $dbh->exec($sql);
                                }
                                $nbTotal += $nb;
                            }
                        }
                        if ($nbTotal == 0) {
                            echo 'Aucune liaison orpheline';
                        }
                    }
                    ?>

                    <h2 id="deletePIXLR">Pixlr</h2>
                    <ul>
                        <li><a href="?deletePIXLR=1#deletePIXLR">Supprimer les éditions temporaires</a></li>
                    </ul>
                    <?php
                    if (!empty($_GET['deletePIXLR'])) {
                        $aGlob = glob(UPLOAD_IMAGE_PHYSIQUE . '*/pixlr*');
                        $nb = 0;
                        foreach ($aGlob as $filename) {
                            File_management::deleteFromName($filename);
                            echo '. ';
                            $nb++;
                        }
                        $aGlob = glob(UPLOAD_IMAGE_PHYSIQUE . '*/THUMB/pixlr*');
                        foreach ($aGlob as $filename) {
                            File_management::deleteFromName($filename);
                            echo '. ';
                            $nb++;
                        }
                        if ($nb == 0) {
                            echo 'Aucune édition à supprimer';
                        }
                    }
                    ?>

                    <?php
                    if (!empty($_GET['genererMd5'])) {
                        $sql = "select ID_WEBOTHEQUE, WBT_CODE from WEBOTHEQUE where
                                    WEB_MD5 = '' and SIT_CODE=" . $dbh->quote($_GET['genererMd5']);
                        $stmt = $dbh->prepare("update WEBOTHEQUE set WEB_MD5=:WEB_MD5 where ID_WEBOTHEQUE=:idtf");
                        foreach ($dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
                            $className = "Webo_" . str_replace('WBT_', '', $row['WBT_CODE']);
                            $oWebo = new $className($row['ID_WEBOTHEQUE']);
                            $stmt->bindValue(':idtf', $oWebo->getID(), PDO::PARAM_INT);
                            $stmt->bindValue(':WEB_MD5', $oWebo->generateMD5(), PDO::PARAM_STR);
                            $stmt->execute();
                        }
                    }
                    $sql = "select SIT_LIBELLE, DD_SITE.SIT_CODE, count(ID_WEBOTHEQUE) as NB from DD_SITE
                                inner join WEBOTHEQUE on WEBOTHEQUE.SIT_CODE=DD_SITE.SIT_CODE
                                where WEB_MD5 = ''
                                group by DD_SITE.SIT_CODE
                                order by SIT_LIBELLE";
                    if ($rowSitesMd5 = $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC)) {?>
                    <h2 id="md5">Contrôle MD5</h2>
                    <ul>
                    <?php foreach ($rowSitesMd5 as $row) { ?>
                        <li><?php echo htmlspecialchars($row['SIT_LIBELLE'] . ' : ' . $row['NB'], ENT_QUOTES, 'UTF-8') ?> éléments sans contrôle md5 <a href="?genererMd5=<?php echo $row['SIT_CODE'] ?>#md5">[ Générer ]</a></li>
                    <?php } ?>
                    </ul>
                    <?php } ?>
                    <p class="alignright">
                        <a href="#corps">Haut de page</a>
                    </p>
                </fieldset>
                <?php
                // Liste des fichiers cache de LessPHP
                if (file_exists(UPLOAD_STYLE_PHYSIQUE)) {
                    $aFiles = array();
                    $aGlob = glob(UPLOAD_STYLE_PHYSIQUE.'*.css');
                    $bUnlinkFile = false;
                    foreach ($aGlob as $file) {
                        $filename = basename($file);
                        // Eventuelle suppression...
                        if (!empty($_POST['lessPhpCacheDel']) && in_array(md5($filename), $_POST['lessPhpCacheDel'])) {
                            if (!unlink($file)) {
                                $aFiles[] = $filename;
                            } else {$bUnlinkFile = true;}
                        } else {
                            $aFiles[] = $filename;
                        }
                    }
                    if ($bUnlinkFile) {
                        // Purge du cache de l'ensemble des sites
                        Page::clearAllCache();
                    }
                    ?>
                    <fieldset class="tab">
                        <legend>LessPHP</legend>
                        <h2>Cache de LessPHP</h2>
                        <?php
                        if (empty($aFiles)) {
                            echo '<p>Le répertoire "<code>'.htmlspecialchars(UPLOAD_STYLE, ENT_QUOTES, 'UTF-8').'</code>" ne contient aucun fichier "<code>.css</code>" pouvant correspondre à des fichiers générés par LessPHP.</p>';
                        } else {
                            echo '<form method="post" action="'.$_SERVER['PHP_SELF'].'?lessPhpCacheDel=1">
                                <table border="1" frame="hsides" rules="rows">
                                    <caption>Fichiers CSS présents dans "<code>'.htmlspecialchars(UPLOAD_STYLE).'</code>"</caption>
                                    <thead>
                                        <tr>
                                            <th>Fichier</th>
                                            <th style="width: 10%">
                                                Supprimer
                                                <br>
                                                <input type="checkbox"
                                                    onclick="var cLessPhpCacheDel = document.getElementsByName(\'lessPhpCacheDel[]\'); for (i=0; cLessPhpCacheDel[i]; i++) {cLessPhpCacheDel[i].checked = this.checked;}"
                                                    title="Sélectionner / désélectionner l\'ensemble des fichiers CSS listé dans cette colonne">
                                            </th>
                                            <th>Télécharger</th></tr>
                                    </thead>
                                    <tfoot>
                                        <tr><td colspan="3"><p class="alignright">
                                            <input type="submit" class="submit" value="Supprimer les fichiers sélectionnés"
                                                onclick="return confirm(\'Etes vous sûr de vouloir supprimer définitivement les fichiers sélectionnés ?\')">
                                        </p></td></tr>
                                    </tfoot>
                                    <tbody>';
                            foreach ($aFiles as $f) {
                                echo '<tr>
                                        <td>
                                                <label for="lps'.md5($f).'">'.htmlspecialchars($f, ENT_QUOTES, 'UTF-8').'</label>
                                        </td>
                                        <td class="aligncenter">
                                            <input type="checkbox" name="lessPhpCacheDel[]" id="lps'.md5($f).'" value="'.md5($f).'"
                                                title="Supprimer le fichier &quot;'.htmlspecialchars($f, ENT_QUOTES, 'UTF-8').'&quot;">
                                        </td>
                                        <td class="aligncenter">
                                            [<a href="'.htmlspecialchars(UPLOAD_STYLE.$f, ENT_QUOTES, 'UTF-8').'" class="external" title="'.htmlspecialchars('Télécharger le fichier "'.$f.'"', ENT_QUOTES, 'UTF-8').'">Télécharger</a>]
                                        </td>
                                    </tr>';
                            }
                            echo '	</tbody>';
                            echo '</table></form>';
                            echo '<p class="alignright"><a href="#corps">Haut de page</a></p>';
                        }
                        ?>
                    </fieldset>
                <?php
                }
                ?>
                <fieldset class="tab">
                    <legend>Cache des pages</legend>
                    <h2>Cache des pages</h2>
                    <p>Seuls les sites avec un nom de domaine renseigné au sein de l'espace d'administration peuvent avoir des pages en cache.</p>
                    <?php
                    $sql = 'select * from DD_SITE where SIT_HOST!= \'\' order by SIT_LIBELLE';
                    if (!$rowListe = $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC)) {
                        echo  '<p class="error">Le ou les sites ne définissent aucun nom de domaine. Le cache des pages n\'est donc pas actif.</p>';
                    } else {
                        echo '<form method="post" action="'.$_SERVER['PHP_SELF'].'?pageCacheDel=1">
                                <table border="1" frame="hsides" rules="all" class="liste">
                                    <caption>Informations sur le cache des pages associé à chacun des sites</caption>
                                    <thead>
                                        <tr>
                                            <th>Nom du site (nom d\'hôte)</th>
                                            <th>Nombre de page en cache</th>
                                            <th style="width: 10%">
                                                Vider le cache des pages
                                                <br>
                                                <input type="checkbox"
                                                    onclick="var cPageCacheDel = document.getElementsByName(\'pageCacheDel[]\'); for (i=0; cPageCacheDel[i]; i++) {cPageCacheDel[i].checked = this.checked;}"
                                                    title="Sélectionner / désélectionner l\'ensemble des sites listé dans cette colonne">
                                            </th>
                                        </tr>
                                    </thead><tfoot>
                                        <tr>
                                            <tr><td colspan="4"><p class="alignright">
                                                <input type="submit" class="submit" value="Vider le cache des sites sélectionnés"
                                                    onclick="return confirm(\'Etes vous sûr de vouloir vider le cache des sites sélectionnés ?\')">
                                            </p></td></tr>
                                        </tr>
                                    </tfoot><tbody>';
                        foreach ($rowListe as $row) {
                            // Eventuelle suppression du cache...
                            if  (!empty($_POST['pageCacheDel']) && in_array($row['SIT_CODE'], $_POST['pageCacheDel'])
                            ) {
                                // Purge du cache de l'ensemble du site
                                Page::clearCache($row['SIT_CODE']);
                            }
                            $nbPage = sizeof(glob(UPLOAD_CACHE_PHYSIQUE . $row['SIT_CODE'] . "-*.htm"));
                            echo '<tr>';
                                echo '<td>
                                            <label for="pc'.$row['SIT_CODE'].'">'.htmlspecialchars($row['SIT_LIBELLE'], ENT_QUOTES, 'UTF-8').' ('.htmlspecialchars($row['SIT_HOST'], ENT_QUOTES, 'UTF-8').')</label>
                                      </td>';
                                echo '<td class="aligncenter">'.$nbPage.'</td>';
                                echo '<td class="aligncenter">
                                            <input type="checkbox" name="pageCacheDel[]" id="pc'.$row['SIT_CODE'].'" value="'.$row['SIT_CODE'].'"
                                                title="Vider le cache du site &quot;'.htmlspecialchars($row['SIT_LIBELLE'], ENT_QUOTES, 'UTF-8').'&quot;">
                                  </td>';
                            echo '</tr>';
                        }
                        echo '	</tbody>';
                        echo '</table></form>';
                        echo '<p class="alignright"><a href="#corps">Haut de page</a></p>';
                    }
                    ?>
                </fieldset>
                <fieldset class="tab">
                    <legend>Système</legend>
                    <h2>Espace disque</h2>
                    <?php
                    $si_prefix = array( 'B', 'KB', 'MB', 'GB', 'TB', 'EB', 'ZB', 'YB' );
                    $base = 1024;
                    ?>
                    <ul>
                        <li>
                            <strong>Espace disque utilisé :</strong>
                            <?php
                            $bytes = disk_total_space(PHYSICAL_ROOT);
                            $class = min((int) log($bytes , $base) , count($si_prefix) - 1);
                            echo sprintf('%1.2f' , $bytes / pow($base,$class)) . ' ' . $si_prefix[$class];
                            ?>
                        </li>
                        <li>
                            <strong>Espace disque disponible :</strong>
                            <?php
                            $bytes = disk_free_space(PHYSICAL_ROOT);
                            $class = min((int) log($bytes , $base) , count($si_prefix) - 1);
                            echo sprintf('%1.2f' , $bytes / pow($base,$class)) . ' ' . $si_prefix[$class];
                            ?>
                        </li>
                    </ul>
                    <h2>Information sur la configuration PHP</h2>
                    <ul>
                        <li>
                            <p><a href="?phpinfo=1">PhpInfo</a></p>
                        </li>
                    </ul>
                    <?php if (defined('SLOWREQUEST_LOG_FILE') && file_exists(SLOWREQUEST_LOG_FILE)) {
                            # cf. http://www.php.net/manual/en/function.fseek.php#106336
                            function read_backward_line($filename, $lines, $revers = false)
                            {
                                $offset = -1;
                                $c = '';
                                $read = '';
                                $i = 0;
                                $fp = @fopen($filename, "r");
                                while ( ($lines+1) && fseek($fp, $offset, SEEK_END) >= 0 ) {
                                    $c = fgetc($fp);
                                    if ($c == "\n" || $c == "\r") {
                                        $lines--;
                                        if ($revers) {
                                            $read[$i] = strrev($read[$i]);
                                            $i++;
                                        }
                                    }
                                    if( $revers ) $read[$i] .= $c;
                                    else $read .= $c;
                                    $offset--;
                                }
                                fclose ($fp);
                                if ($revers) {
                                    if($read[$i] == "\n" || $read[$i] == "\r")
                                        array_pop($read);
                                    else $read[$i] = strrev($read[$i]);
                                    return implode('',$read);
                                }

                                return strrev(rtrim($read,"\n\r"));
                            }
                            echo '<h2 id="slowrequest">Journal des requêtes HTTP lentes</h2>';
                            $aNbLigne = array(10, 50, 100, 500, 1000, 5000);

                            echo '<form method="get" action="#slowrequest">
                                    <p>
                                        <label for="slowrequest_nbline">Analyser les
                                        <select name="slowrequest_nbline" id="slowrequest_nbline">';
                            foreach ($aNbLigne as $v) {
                                echo '<option value="'.$v.'"'.($_GET['slowrequest_nbline']==$v?' selected':'').'>'.$v.'</option>';
                            }
                            echo '		</select>
                                        dernières lignes du journal des requêtes lentes</label>
                                        <input type="submit" class="submit" value="Lancer l\'analyse">
                                    </p>
                                </form>';

                            $aError = $aMatches = array();
                            $nbLines = isset($_GET['slowrequest_nbline']) && in_array($_GET['slowrequest_nbline'], $aNbLigne)?$_GET['slowrequest_nbline']:10;
                            $sLogContent = read_backward_line(SLOWREQUEST_LOG_FILE, $nbLines);
                            $aLogContent = explode("\n", $sLogContent);
                            foreach ($aLogContent as $sContents) {
                                if ($sContents!='' && preg_match('#^\[([^\]]*)\]\s+([0-9]+[,.][0-9]+)\s+(.*)$#', $sContents, $aMatches)) {
                                    $sUrl = preg_replace('#^([^\?]*).*#', '$1',$aMatches[3]);
                                    $sGet = preg_replace('#^[^\?]*(.*)#', '$1',$aMatches[3]);
                                    $aError[$sUrl][] = array(strtotime($aMatches[1]), $aMatches[2], $sGet);
                                }
                            }
                            function cmp($a, $b)
                            {
                                if (count($a) == count($b)) {
                                    return 0;
                                }

                                return (count($a) < count($b)) ? 1 : -1;
                            }
                            uasort($aError, 'cmp');
                            echo '<table id="slowrequest_table" class="tablesorter" border="1" frame="hsides" rules="all">
                                <thead>
                                    <tr>
                                        <th>Nombre d\'appels</th>
                                        <th>Temps moyen d\'execution (en s)</th>
                                        <th>URL</th>
                                        <th class="{sorter: \'text\'}">Occurences des chaînes <code>GET</code></th>
                                    </tr>
                                </thead><tfoot>
                                    <tr>
                                        <th>Nombre d\'appels</th>
                                        <th>Temps moyen d\'execution (en s)</th>
                                        <th>URL</th>
                                        <th>Occurences des chaînes <code>GET</code></th>
                                    </tr>
                                </tfoot><tbody>';
                            foreach ($aError as $sFile => $aInfo) {
                                $tempsMoyen = 0.0;
                                $aGet = array();
                                foreach ($aInfo as $info) {
                                    $tempsMoyen += floatval($info[1]);
                                    if (!empty($info[2]) && !in_array($info[2],$aGet)) {
                                        $aGet[] = $info[2];
                                    }
                                }
                                $tempsMoyen = $tempsMoyen / count($aInfo);
                                echo '<tr>';
                                echo '<td>' . count($aInfo) . '</td>';
                                echo '<td>' . round($tempsMoyen, 2) . '</td>';
                                echo '<td>' . htmlspecialchars($sFile, ENT_QUOTES, 'UTF-8') . '</td>';
                                echo '<td>';
                                if (!empty($aGet)) {
                                    echo '<ul>';
                                    foreach ($aGet as $s) {
                                        echo '<li><code>'.htmlspecialchars($s, ENT_QUOTES, 'UTF-8').'</code></li>';
                                    }
                                    echo '</ul>';
                                }
                                echo '</td>';
                                echo '</tr>';
                            }
                            echo '</tbody></table>';
                     } ?>
                    <p class="alignright">
                        <a href="#corps">Haut de page</a>
                    </p>
                </fieldset>
                <fieldset class="tab">
                    <legend>Divers</legend>
                    <h2>Outils</h2>
                    <ul>
                        <li>
                            <p><a href="?rewriteURL=1#rewriteURL" id="rewriteURL" onclick="return confirm('Attention vous allez peut-être écraser un travail de référencement !')">
                                Regénérer les URL principales, les titles et les metadescriptions
                            </a></p>
                            <?php
                            if (!empty($_GET['rewriteURL'])) {
                                require_once dirname(__FILE__) . '/../include/lib.common.php';
                                set_time_limit(0);
                                $stmt = $dbh->prepare("update OFF_PAGE set
                                    PAG_TITRE_REFERENCEMENT=:PAG_TITRE_REFERENCEMENT,
                                    PAG_URLREWRITING=:PAG_URLREWRITING,
                                    PAG_METADESCRIPTION=:PAG_METADESCRIPTION
                                    where ID_PAGE=:idtf");;
                                $sql = "select * from OFF_PAGE";
                                foreach ($dbh->query($sql) as $row) {
                                    $stmt->bindValue(':PAG_TITRE_REFERENCEMENT', $row['PAG_TITRE'], PDO :: PARAM_STR);
                                    $stmt->bindValue(':PAG_URLREWRITING', filenameToRfc1738(mb_convert_case(trim($row['PAG_TITRE']), MB_CASE_LOWER)), PDO :: PARAM_STR);
                                    $stmt->bindValue(':PAG_METADESCRIPTION', $row['PAG_ACCROCHE'], PDO :: PARAM_STR);
                                    $stmt->bindValue(':idtf', $row['ID_PAGE'], PDO :: PARAM_INT);
                                    $stmt->execute();
                                    echo '. ';
                                    flush();
                                }
                                $stmt = $dbh->prepare("update ON_PAGE set
                                    PAG_TITRE_REFERENCEMENT=:PAG_TITRE_REFERENCEMENT,
                                    PAG_URLREWRITING=:PAG_URLREWRITING,
                                    PAG_METADESCRIPTION=:PAG_METADESCRIPTION
                                    where ID_PAGE=:idtf");;
                                $sql = "select * from ON_PAGE";
                                foreach ($dbh->query($sql) as $row) {
                                    $stmt->bindValue(':PAG_TITRE_REFERENCEMENT', $row['PAG_TITRE'], PDO :: PARAM_STR);
                                    $stmt->bindValue(':PAG_URLREWRITING', filenameToRfc1738(mb_convert_case(trim($row['PAG_TITRE']), MB_CASE_LOWER)), PDO :: PARAM_STR);
                                    $stmt->bindValue(':PAG_METADESCRIPTION', $row['PAG_ACCROCHE'], PDO :: PARAM_STR);
                                    $stmt->bindValue(':idtf', $row['ID_PAGE'], PDO :: PARAM_INT);
                                    $stmt->execute();
                                    echo '. ';
                                    flush();
                                }
                                // Purge du cache de l'ensemble des sites
                                Page::clearAllCache();
                            }
                            ?>
                        </li>
                        <li>
                            <p><a href="?reparsePARA=1#reparsePARA" id="reparsePARA">
                                Remettre tous les paragraphes à parser
                            </a></p>
                            <?php
                            if (!empty($_GET['reparsePARA'])) {
                                $sql = "select count(ID_PARAGRAPHE) from OFF_PARAGRAPHE where PAR_APARSER=0";
                                $nb = $dbh->query($sql)->fetchColumn();
                                if ($nb > 0) {
                                    for ($i=0; $i<$nb; $i++) {
                                        echo '. ';
                                        flush();
                                    }
                                    $sql = "update OFF_PARAGRAPHE set PAR_APARSER=1";
                                    $dbh->exec($sql);
                                } else {
                                    echo 'Aucun paragraphe "OFF" à remettre à parser';
                                }
                                echo '<br>';
                                $sql = "select count(ID_PARAGRAPHE) from ON_PARAGRAPHE where PAR_APARSER=0";
                                $nb = $dbh->query($sql)->fetchColumn();
                                if ($nb > 0) {
                                    for ($i=0; $i<$nb; $i++) {
                                        echo '. ';
                                        flush();
                                    }
                                    $sql = "update ON_PARAGRAPHE set PAR_APARSER=1";
                                    $dbh->exec($sql);
                                    // Purge du cache de l'ensemble des sites
                                    Page::clearAllCache();
                                } else {
                                    echo 'Aucun paragraphe "ON" à remettre à parser';
                                }
                            }
                            ?>
                        </li>
                    </ul>
                </fieldset>
                <hr>
                <p class="aligncenter">
                    "<a href="readme.txt" class="external">ReadMe</a>"
                </p>
            </div><!-- DIN : #corps -->
        </div><!-- DIN : #document -->
    </body>
</html>
