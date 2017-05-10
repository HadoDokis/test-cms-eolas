<?php
require '../include/inc.bo_init.php';

// HTTP headers for no cache etc
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

//Upload de fichier
if (!empty($_FILES['file'])) {

    // Look for the content type header
    if (isset($_SERVER["HTTP_CONTENT_TYPE"])) {
        $contentType = $_SERVER["HTTP_CONTENT_TYPE"];
    }
    if (isset($_SERVER["CONTENT_TYPE"])) {
        $contentType = $_SERVER["CONTENT_TYPE"];
    }

    $chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
    $chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 0;
    $fileName = isset($_REQUEST["name"]) ? $_REQUEST["name"] : '';
    $filePath = sys_get_temp_dir() . '/' . $fileName;

    // Handle non multipart uploads older WebKit versions didn't support multipart in HTML5
    if (strpos($contentType, "multipart") !== false) {
        if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
            // Open temp file
            $out = fopen($filePath . '.part', $chunk == 0 ? "wb" : "ab");
            if ($out) {
                // Read binary input stream and append it to temp file
                $in = fopen($_FILES['file']['tmp_name'], "rb");

                if ($in) {
                    while ($buff = fread($in, 4096)) {
                        fwrite($out, $buff);
                    }
                } else {
                    die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Echec lors de la lecture du fichier uploadé"}, "id" : "id"}');
                }
                fclose($in);
                fclose($out);
                @unlink($_FILES['file']['tmp_name']);
            } else {
                die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Echec lors de la l\'écriture du fichier"}, "id" : "id"}');
            }
        } else {
            die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Impossible de déplacer le fichier uploadé"}, "id" : "id"}');
        }
    } else {
        // Open temp file
        $out = fopen($filePath . '.part', $chunk == 0 ? "wb" : "ab");
        if ($out) {
            // Read binary input stream and append it to temp file
            $in = fopen("php://input", "rb");

            if ($in) {
                while ($buff = fread($in, 4096))
                    fwrite($out, $buff);
            } else {
                die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Echec lors de la lecture du fichier uploadé."}, "id" : "id"}');
            }
            fclose($in);
            fclose($out);
        } else {
            die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Echec lors de la l\'écriture du fichier."}, "id" : "id"}');
        }
    }

    // Check if file has been uploaded
    if (!$chunks || $chunk == $chunks - 1) {
        // Strip the temp .part suffix off
        rename($filePath . '.part', $filePath);
    }

    //on le déplace vers un repertoire accéssible en web
    $baseName = filenameToRfc1738(basename($filePath));
    rename($filePath, PHYSICAL_PATH . 'uploads/' . $baseName);
    require_once CLASS_DIR . 'class.File_management.php';

    $ext = preg_replace('#.*(\.[a-zA-Z1-9]+)$#', '$1', $baseName);
    if (in_array($ext, CMS::getCurrentSite()->getExtension('SIT_EXT_IMAGE'))) {
        $oFm = new File_management('', '', -1, PHYSICAL_PATH . 'uploads/');
        $oFm->name = $baseName;
        $oFm->resize(150, 150, '', 'THUMB_' .  $baseName);
    }

    // Return JSON-RPC response
    die('{"jsonrpc" : "2.0", "result" : null, "id" : "id"}');

} elseif (!empty($_GET['deleteFile'])) {

    $baseName = filenameToRfc1738(basename($_GET['deleteFile']));
    if (file_exists(PHYSICAL_PATH . 'uploads/' . $baseName)) {
        unlink(PHYSICAL_PATH . 'uploads/' . $baseName);
    }

    $ext = preg_replace('#.*(\.[a-zA-Z1-9]+)$#', '$1', $baseName);
    if (in_array($ext, CMS::getCurrentSite()->getExtension('SIT_EXT_IMAGE'))) {
        if (file_exists(PHYSICAL_PATH . 'uploads/THUMB_' . $baseName)) {
            unlink(PHYSICAL_PATH . 'uploads/THUMB_' . $baseName);
        }
    }
    echo json_encode(array('success' => 1));

} elseif (!empty($_GET['cleanUp'])) {
    if (is_array($_GET['files']) && count($_GET['files'])>0) {
        foreach ($_GET['files'] as $file) {
            $baseName = filenameToRfc1738(basename($filePath));
            if (file_exists(PHYSICAL_PATH . 'uploads/' . $baseName)) {
                unlink(PHYSICAL_PATH . 'uploads/' . $baseName);
            }
            $ext = preg_replace('#.*(\.[a-zA-Z1-9]+)$#', '$1', $baseName);
            if (in_array($ext, CMS::getCurrentSite()->getExtension('SIT_EXT_IMAGE'))) {
                if (file_exists(PHYSICAL_PATH . 'uploads/THUMB_' . $baseName)) {
                    unlink(PHYSICAL_PATH . 'uploads/THUMB_' . $baseName);
                }
            }
        }
    }
} elseif (!empty($_GET['validateFile'])) {
    require CLASS_DIR . 'class.db_webotheque.php';
    require CLASS_DIR . 'class.File_management.php';

    $WBT_CODE = $_GET['wbt_code'];
    $ID_WEBOTHEQUECATEGORIE = $_GET['idWebothequeCat'];
    $CAT_LIBELLE = $_GET['catLibelle'];
    $WEB_LIBELLE = ($_GET['fileLibelle']!=''?$_GET['fileLibelle']:current(explode('.', basename($_GET['fileName']))));
    $baseName = filenameToRfc1738(basename($_GET['fileName']));

    $wbt_type = '';
    switch ($WBT_CODE) {
        case 'WBT_DOCUMENT':
            $wbt_type = 'document';
            break;
        case 'WBT_IMAGE':
            $wbt_type = 'image';
            break;
    }

    //Insertion d'une éventuelle nouvelle catégorie
    if ($CAT_LIBELLE != '') {

        //On récupère le dernier id de catégorie créé à partir du nouveau libellé fourni
        //car dans le cas d'upload multiple, il ne faut pas recréer la catégorie à chaque fois
        //On vérifie aussi le cas où la valeur du parent est vide (isset($fileInfo['ID_WEBOTHEQUECATEGORIE']))
        $sql = "select ID_WEBOTHEQUECATEGORIE from WEBOTHEQUECATEGORIE where CAT_LIBELLE=" . $dbh->quote($CAT_LIBELLE) ;
        $sql .= (is_numeric($_GET['idWebothequeCat'])?" and CAT_IDPARENT=" . $dbh->quote($_GET['idWebothequeCat']) : "") ;
        $sql .= " and SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID()) . " ORDER BY ID_WEBOTHEQUECATEGORIE DESC";
        $ID_WEBOTHEQUECATEGORIE = $dbh->query($sql)->fetchColumn();

        //Si cet id est valide, on réalise les insertions webothéques dans cette catégorie sinon on crée la nouvelle
        if (!$ID_WEBOTHEQUECATEGORIE) {
            $stmt = $dbh->prepare("insert into WEBOTHEQUECATEGORIE (
                SIT_CODE,
                WBT_CODE,
                CAT_IDPARENT,
                CAT_LIBELLE
                ) values (
                :SIT_CODE,
                :WBT_CODE,
                :CAT_IDPARENT,
                :CAT_LIBELLE
                )");
            $stmt->bindValue(':SIT_CODE', CMS::getCurrentSite()->getID(), PDO :: PARAM_STR);
            $stmt->bindValue(':WBT_CODE', $WBT_CODE, PDO :: PARAM_STR);
            $stmt->bindValue(':CAT_IDPARENT', (is_numeric($_GET['idWebothequeCat'])) ? $_GET['idWebothequeCat'] : null, PDO :: PARAM_INT);
            $stmt->bindValue(':CAT_LIBELLE', $CAT_LIBELLE, PDO :: PARAM_STR);
            $stmt->execute();
            $ID_WEBOTHEQUECATEGORIE = $dbh->lastInsertID();
        }
    }

    $weboClass = 'Webo_' . strtoupper($wbt_type);
    $oWebo = new $weboClass();
    if ($idtf = $oWebo->checkMD5(PHYSICAL_PATH . 'uploads/' . $baseName)) {
        //Suppression
        if (file_exists(PHYSICAL_PATH . 'uploads/' . $baseName)) {
            unlink(PHYSICAL_PATH . 'uploads/' . $baseName);
        }
        $ext = preg_replace('#.*(\.[a-zA-Z1-9]+)$#', '$1', $baseName);
        if (in_array($ext, CMS::getCurrentSite()->getExtension('SIT_EXT_IMAGE'))) {
            if (file_exists(PHYSICAL_PATH . 'uploads/THUMB_' . $baseName)) {
                unlink(PHYSICAL_PATH . 'uploads/THUMB_' . $baseName);
            }
        }
        echo json_encode(array('erreur' => sprintf(gettext('md5check_fail'), $idtf)));
        exit();
    }
    if(!Webotheque::insertWebotheque($wbt_type,
                                     $WEB_LIBELLE,
                                     $ID_WEBOTHEQUECATEGORIE,
                                     PHYSICAL_PATH . 'uploads/' . $baseName,
                                     true,
                                     $error,
                                     $oWebo->getMD5(),
                                     'MULTIPLE')){
        echo json_encode(array('erreur' => $error));
        exit();
    }
    echo json_encode(array('success' => 1));
}
