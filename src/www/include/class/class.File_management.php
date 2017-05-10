<?php
/**
 * Classe de estion des fichisers
 * @package CMS
 */
class File_management
{
    /**
     * '' => ok; 'FM_EXTENSION', 'FM_MOVEUPLOAD', 'FM_UPLOADSIZE'
     * @var String
     */
    public $error;
    /**
     * Nom du fichier
     * @var String
     */
    public $name;
    /**
     * L'ancien nom du fichier (dans le cas d'un resize dans un nouveau fichier)
     * @var String
     */
    public $old_name;
    /**
     * ID de l'élément inséré
     * @var Int
     */
    public $lastInsertID;
    /**
     * Nom de la table
     * @var String
     */
    private $_table_name;
    /**
     * Nom du champs de la clé primaire
     * @var String
     */
    private $_id_name;
    /**
     * Valeur de l'identifiant de l'objet auquel est lié le fichier
     * @var Int
     */
    private $_id_value;
    /**
     * Valeur de l'identifiant qui et utilisé dans la requête d'insertion de l'objet auquel est lié le fichier
     * @var Int
     */
    private $_id_value_sql;
    /**
     * Chemiun de base (UPLOAD_IMAGE_PHYSIQUE, UPLOAD_EXTERNE_PHYSIQUE, ...)
     * @var String
     */
    private $_path;
    /**
     * Tableau contenant les extention autorisés pour l'upload
     * @var array
     */
    private $_aExtensions;
    /**
     * Objet interface avec la base de données
     * @var PDO
     */
    private $_dbh;

    /**
     * Constructeur
     * @param String $table_name Nom de la table où sera enregistré le chemin du fichier
     * @param String $id_name    Nom de la clé primaire
     * @param Int    $id_value   Valeur de l'Id de l'élément
     * @param String $path       Chemin de base (UPLOAD_IMAGE_PHYSIQUE, UPLOAD_EXTERNE_PHYSIQUE, ...)
     */
    public function File_management($table_name, $id_name, $id_value, $path)
    {
        $this->_table_name = $table_name;
        $this->_id_name = $id_name;
        $this->_path = $path;
        $this->_aExtensions = array ();
        $this->error = '';
        $this->name = '';
        $this->old_name = '';
        $this->lastInsertID = '';
        $this->_dbh = DB :: getInstance();
        if (is_numeric($id_value)) {
            $this->_id_value = intval($id_value);
            $this->_id_value_sql = intval($id_value);
        } else {
            $this->_id_value = $id_value;
            $this->_id_value_sql = $this->_dbh->quote($id_value);
        }
    }

    /**
     * Méthode générale d'upload
     *
     * Permet de traiter l'upload soit à partir d'un fichier physique sur le serveur soit à partir de la variabel $_FILES
     * @param  String  $field_name         Nom du champs dans lequel sera inséré lee chemin du fichier
     * @param  string  $size_field         Nom du champ dans lequel sera inséré la taille du fichier
     * @param  string  $nomFichier         Nom de sauvegarde du fichier (si non précisié, on utiliser le nom du fichier lui même)
     * @param  string  $path_origine       Chemin du fichier d'origine (pour l'upload FTP)
     * @param  string  $nomFichierExistant Nom du fichier existant sur le serveur
     * @return boolean True si tout se passe bien, false sinon
     */
    public function upload($field_name, $size_field = '', $nomFichier = '', $path_origine = '', $nomFichierExistant = '')
    {
        //Si un fichier est précisé, on réalise l'upload à partir d'un fichier
        if (!empty($nomFichierExistant)) {
            return $this->uploadFromFTP($field_name, $size_field, $path_origine, $nomFichierExistant, $nomFichier);
        } else {
            return $this->uploadFromFiles($field_name, $size_field, $nomFichier);
        }
    }

    /**
     * Méthode d'upload à partir de la variable $_FILES
     *
     * @param  String  $field_name Nom du champs dans lequel sera inséré lee chemin du fichier
     * @param  string  $size_field Nom du champ dans lequel sera inséré la taille du fichier
     * @param  string  $nomFichier Nom de sauvegarde du fichier (si non précisé, on utiliser le nom du fichier lui même)
     * @return boolean True si tout se passe bien, false sinon
     */
    public function uploadFromFiles($field_name, $size_field = '', $nomFichier = '')
    {
        $this->error = '';
        $this->name = '';

        //fichier ?
        if ($_FILES[$field_name]['size'] == 0) {
            if ($_FILES[$field_name]['error'] == UPLOAD_ERR_INI_SIZE) {
                $this->error = 'FM_UPLOADSIZE';
            }

            return false;
        }

        $ext = mb_strtolower(mb_strrchr($_FILES[$field_name]['name'], '.'));
        $dir = substr(md5($_FILES[$field_name]['name']), 0, 2);
        $fileName = (!empty($nomFichier)?$nomFichier:$_FILES[$field_name]['name']);
        $this->name = $dir . '/' . $this->genererNomFichier($fileName,'') . $ext;

        //extension ?
        if (sizeof($this->_aExtensions) > 0 && !in_array($ext, $this->_aExtensions)) {
            $this->error = 'FM_EXTENSION';

            return false;
        }

        //repertoire
        if (!is_dir($this->_path . $dir)) {
            mkdir($this->_path . $dir, 0775);
        }

        //upload
        set_time_limit(20);
        if (!move_uploaded_file($_FILES[$field_name]['tmp_name'], $this->getPhysicalName())) {
            $this->error = 'FM_MOVEUPLOAD';

            return false;
        }
        chmod($this->getPhysicalName(), 0644);

        //suppression existant eventuel
        $this->delete($field_name);

        //bd
        $sql = 'update ' . $this->_table_name . ' set ' . $field_name . "=" . $this->_dbh->quote($this->name);
        if ($size_field != '') {
            $sql .= ', ' . $size_field . '=' . intval($_FILES[$field_name]['size']);
        }
        $sql .= ' where ' . $this->_id_name . '=' . $this->_id_value_sql;
        $this->_dbh->exec($sql);

        return true;
    }

    /**
     * Méthode d'upload à partir d'un fichier déposé coté serveur
     *
     * Cette méthode est similaire à la précédente mais prend un fichier du dossier d'import FTP en entrée
     * @param  String  $field_name         Nom du champs dans lequel sera inséré lee chemin du fichier
     * @param  string  $size_field         Nom du champ dans lequel sera inséré la taille du fichier
     * @param  string  $nomFichier         Nom de sauvegarde du fichier (si non précisé, on utilise le $nomFichierExistant)
     * @param  string  $path_origine       Chemin du fichier d'origine (pour l'upload FTP)
     * @param  string  $nomFichierExistant Nom du fichier présent sur le serveur
     * @return boolean True si tout se passe bien, false sinon
     */
    public function uploadFromFTP($field_name, $size_field = '', $path_origine = '', $nomFichierExistant = '', $nomFichier = '')
    {
        $this->error = '';
        $this->name = '';

        $ext = mb_strtolower(mb_strrchr($nomFichierExistant, '.'));
        $dir = substr(md5($nomFichierExistant), 0, 2);
        $fileName = (!empty($nomFichier)?$nomFichier:$nomFichierExistant);
        $this->name = $dir . '/' . $this->genererNomFichier($fileName,'') . $ext;

        //extension correcte ?
        if (sizeof($this->_aExtensions) > 0 && !in_array($ext, $this->_aExtensions)) {
            $this->error = 'FM_EXTENSION';

            return false;
        }

        //repertoire
        if (!is_dir($this->_path . $dir)) {
            mkdir($this->_path . $dir, 0775);
        }

        //upload
        set_time_limit(20);

        //On copie le fichier pour ensuite le supprimer plutôt que de le renommer sinon le propriétaire de dépot est conservé et les droits sont faussés
        if (!copy($path_origine . $nomFichierExistant, $this->getPhysicalName())) {
            $this->error = 'FM_MOVEUPLOAD';

            return false;
        }
        //Suppression de l'ancien fichier
        unlink($path_origine.$nomFichierExistant);
        //Affectation des droits au nouveau fichier
        chmod($this->getPhysicalName(), 0644);

        //suppression de l'ancien fichier existant eventuel précisé dans la base de donnée
        $this->delete($field_name);

        //bd
        $sql = 'update ' . $this->_table_name . ' set ' . $field_name . "=" . $this->_dbh->quote($this->name);
        if ($size_field != '') {
            $sql .= ', ' . $size_field . '=' . intval(filesize($this->getPhysicalName()));
        }
        $sql .= ' where ' . $this->_id_name . '=' . $this->_id_value_sql;
        $this->_dbh->exec($sql);

        return true;
    }

    /**
     * Upload un fichier et l'insère dans la wébotheque
     *
     * Parametre optionnel :
     *  - LIBELLE_FICHIER (si différent du nom original)
     *  - COLONNE_PATH_FICHIER (si le nom du champ SQL est différent de celui de l'inputFile)
     *  - COLONNE_DATE_FICHIER (si la date d'upload doit être sauvegardée)
     *  - COLONNE_TAILLE_FICHIER (si la taille d'upload doit etre sauvegardée)
     *  - COMPLETE_PATH_FICHIER (si la génération avec time() n'est pas suffisante)
     *
     * @param  String  $field_name     Nom du champs dans $_FILES
     * @param  String  $colonneLibelle Nom du champs en base
     * @param  array   $aOPTIONS       Tableau d'options
     * @return boolean True si tout se passe bien, false sinon
     */
    public function uploadAndInsert($field_name, $colonneLibelle, $aOPTIONS = array ())
    {
        $this->error = '';
        $this->name = '';
        $this->lastInsertID = '';

        //fichier ?
        if ($_FILES[$field_name]['size'] == 0) {
            if ($_FILES[$field_name]['error'] == UPLOAD_ERR_INI_SIZE) {
                $this->error = 'FM_UPLOADSIZE';
            }

            return false;
        }

        $colonnePath = ($aOPTIONS['COLONNE_PATH_FICHIER'] != '') ? $aOPTIONS['COLONNE_PATH_FICHIER'] : $field_name;
        $original_name = ($aOPTIONS['LIBELLE_FICHIER'] != '') ? $aOPTIONS['LIBELLE_FICHIER'] : $_FILES[$field_name]['name'];

        $ext = mb_strtolower(mb_strrchr($_FILES[$field_name]['name'], '.'));
        $dir = substr(md5($_FILES[$field_name]['name']), 0, 2);
        $this->name = $dir . '/' . $this->genererNomFichier($original_name, $aOPTIONS['COMPLETE_PATH_FICHIER']) . $ext;

        //extension ?
        if (sizeof($this->_aExtensions) > 0 && !in_array($ext, $this->_aExtensions)) {
            $this->error = 'FM_EXTENSION';

            return false;
        }

        //repertoire
        if (!is_dir($this->_path . $dir)) {
            mkdir($this->_path . $dir, 0775);
        }

        //upload
        set_time_limit(20);
        if (!move_uploaded_file($_FILES[$field_name]['tmp_name'], $this->getPhysicalName())) {
            $this->error = 'FM_MOVEUPLOAD';

            return false;
        }
        chmod($this->getPhysicalName(), 0644);

        $sqlKey = $sqlVal = '';
        //sauvegarde date ?
        if ($aOPTIONS['COLONNE_DATE_FICHIER'] != '') {
            $sqlKey .= $aOPTIONS['COLONNE_DATE_FICHIER'] . ',';
            $sqlVal .= time() . ',';
        }
        //sauvegarde taille ?
        if ($aOPTIONS['COLONNE_TAILLE_FICHIER'] != '') {
            $sqlKey .= $aOPTIONS['COLONNE_TAILLE_FICHIER'] . ',';
            $sqlVal .= intval($_FILES[$field_name]['size']) . ',';
        }

        //bd
        $sql = "insert into " . $this->_table_name . " (
            " . $this->_id_name . ",
            " . $sqlKey . "
            " . $colonnePath . ",
            " . $colonneLibelle . "
            ) values (
            " . $this->_id_value_sql . ",
            " . $sqlVal . "
            " . $this->_dbh->quote($this->name) . ",
            " . $this->_dbh->quote($original_name) . ")";
        $this->_dbh->exec($sql);
        $this->lastInsertID = $this->_dbh->lastInsertID();

        return true;
    }

    /**
     * Méthode unique pour la génération du nom des fichiers stockés par le CMS
     *
     * Les noms de fichiers sont générés selon la norme RFC1738
     *
     * @param  String $nom_original Nom original du fichier
     * @param  String $complement   ajouté au début du nom, après l'id du fichier
     * @return String Nom généré du fichier
     */
    private function genererNomFichier($nom_original, $complement)
    {
        //Nom du fichier sans l'extension
        $nom_original = current(explode('.', $nom_original));
        //Limitation sur la longueur
        $nom_original = trim(substr($nom_original, 0, 150));
        //Traitement des caractères non admis
        $nom_original = filenameToRfc1738($nom_original);
        //Suppression de l'éventuel caractère "-" final relativement disgracieux
        $nom_original = preg_replace('/-$/', '', $nom_original, 1);
        //Retour de la valeur du nom du fichier
        return $this->_id_value . $complement . '_' . substr(time(), -3) . '_' . $nom_original;
    }

    /**
     * Vérifie s'il faut supprimer le fichier et le suprime si c'est le cas
     *
     * Il doit y avoir un champ (généralement un checkox) contenant le nom du fichier suivi de '_DELETE'. le formulaire doit être en post
     * exemple pour le champs WEB_CHEMIN : <inpu type="checkbox" name="WEB_CHEMIN_DELETE" value="1">
     * => $_POST['WEB_CHEMIN_DELETE'] est donc à 1 et le fichier sera supprimé et les champs correspondant mis à NULL
     * @param  String  $field_name Nom du champs
     * @param  string  $size_field Nom du champs contenant la taille du fichier
     * @return boolean Renvoi true si la valeur et le fichier sont supprimé, false sinon
     */
    public function checkDelete($field_name, $size_field = '')
    {
        if (!empty ($_POST[$field_name . '_DELETE'])) {
            $this->delete($field_name);
            $sql = 'update ' . $this->_table_name . ' set ' . $field_name . "=''";
            if ($size_field != '') {
                $sql .= ', ' . $size_field . "=''";
            }
            $sql .= ' where ' . $this->_id_name . '=' . $this->_id_value_sql;
            $this->_dbh->exec($sql);

            return true;
        }

        return false;
    }

    /**
     * Supprime le fichier et efface le chemin dans le champs $field_name
     *
     * @param  String  $field_name Nom du champs contenant les données à supprimer
     * @return boolean Renvoi true si la valeur et le fichier sont supprimé, false sinon
     */
    public function delete($field_name)
    {
        $sql = 'select ' . $field_name . ' from ' . $this->_table_name . ' where ' . $this->_id_name . '=' . $this->_id_value_sql;
        $file = $this->_dbh->query($sql)->fetchColumn();

        if ($file != '') {
            // formats divers + vignette si image
            if (strstr(self::mimeType($this->_path . $file), 'image') !== false) {
                $sql = "select * from DD_IMAGEFORMAT";
                foreach ($this->_dbh->query($sql)->fetchAll(PDO :: FETCH_ASSOC) as $row) {
                    self::deleteFromName($this->_path . dirname($file) . '/' . $row['IMF_CODE'] . '/' . $row['GAB_CODE'] . '/' . basename($file));
                }
                self::deleteFromName($this->_path . dirname($file) . '/THUMB/' . basename($file));
            }

            if (@ unlink($this->_path . $file)) {
                $this->old_name = $file;
                $_dir = dirname($this->_path . $file);
                if (is_dir($_dir)) {
                    $a = scandir($_dir);
                    if (sizeof($a) == 2) {
                        rmdir($_dir);
                    }
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Redmiensionne l'image
     *
     * @param  Int    $width    la largeur de l'image
     * @param  Int    $height   la hauteur de l'image
     * @param  string $quality  le pourcentage de qualité souhaité pour l'image retaillée (75% par défaut)
     * @param  string $new_name le nom du nouveau fichier (si vide le fichier source est modifié)
     * @param  string $action   l'action à réaliser (resize|crop)
     * @return void
     */
    public function resize($width, $height, $quality = '', $new_name = '', $action = 'resize')
    {
        set_time_limit(5);

        //on crée un éventuel sous dossier
        if ($new_name != '') {
            $new_name = $this->_path . $new_name;
            if (!is_dir(dirname($new_name))) {
                mkdir(dirname($new_name), 0775, true);
            }
        }

        //on enlève les infos
        $param = ' -strip';

        //on personnalise la qualité
        $ext = mb_strtolower(mb_strrchr($this->getPhysicalName(), '.'));
        if ($ext != '.gif') {
            $param .= ' -interlace line -quality '. (empty($quality) ? 75 : $quality);
        } else {
            $param .= ' -coalesce';
        }

        if ($action=='crop' && !empty($width) && !empty($height)) {
            //on crop, mais avant on met à la bonne taille
            //$param .= ' -resize "' . $width . 'x' . $height . '^"'; //necessite imagemagick >= 6.3.8-2 (squeeze)
            $info = getimagesize($this->getPhysicalName());
            $multi = max ($width / $info[0], $height / $info[1]);
            $param .= ' -resize "' . ceil($info[0]*$multi) . 'x' . ceil($info[1]*$multi) . '"';

            //on se met au centre
            $param .= ' -gravity center';
            //on crop
            $param .= ' -crop "' . $width . 'x' . $height . '+0+0" +repage';
        } else {
            //on redimensionne
            $param .= ' -resize "';
            //width ou height peuvent etre vide
            if (!empty($width)) {
                $param .= $width;
            }
            if (!empty ($height)) {
                $param .= 'x' . $height;
            }
            $param .= '>"';
        }

        //nom de destination
        $param .= ' ';
        $param .= ($new_name == '') ? $this->getPhysicalName() : $new_name;

        exec(IMAGICK_CONVERT . ' ' . $this->getPhysicalName() . $param);
    }

    /**
     * Convertit les images CMYK en RGB
     */
    public function convertCMYKtoRGB()
    {
        //exec(IMAGICK_CONVERT . ' ' . $this->getPhysicalName() . ' -profile ' . UPLOAD_IMAGE_PHYSIQUE . 'nopic/USWebCoatedSWOP.icc -profile ' . UPLOAD_IMAGE_PHYSIQUE . 'nopic/AdobeRGB1998.icc ' . $this->getPhysicalName());
    }

    /**
     * Renvoi le chemin vers l'image au bon format
     *
     * @param  String $file   Nom du fichier de l'image
     * @param  String $format Format shouaité
     * @return String chemin vers le fichier au bon format
     */
    public function getSRC($file, $format)
    {
        if (!file_exists($this->_path . $file)) {
            //on prend une image par défaut
            $nopic = new self('NOTABLE', 'NOID', 0, UPLOAD_IMAGE_PHYSIQUE);

            return $nopic->getSRC('nopic/nopic.jpg', $format);
        }
        //on reconstruit le web_path depuis le physical_path
        $web_root = SERVER_ROOT . substr($this->_path, strlen(PHYSICAL_PATH));
        if ($format == '') {
            return $web_root . $file;
        }
        $gabarit_file = dirname($file) . '/' . $format . '/' . CMS::getCurrentSite()->getField('GAB_CODE') . '/' . basename($file);
        if (!file_exists($this->_path . $gabarit_file)) {
            $sql = "select * from DD_IMAGEFORMAT where IMF_CODE=" . $this->_dbh->quote($format) . " and GAB_CODE=" . $this->_dbh->quote(CMS::getCurrentSite()->getField('GAB_CODE'));
            if ($row = $this->_dbh->query($sql)->fetch(PDO::FETCH_ASSOC)) {
                $this->name = $file;
                $this->resize($row['IMF_LARGEUR'], $row['IMF_HAUTEUR'], $row['IMF_QUALITE'], $gabarit_file, $row['IMF_ACTION']);

                return $web_root . $gabarit_file;
            }

            return $this->getThumbSRC($file);
        }

        return $web_root . $gabarit_file;
    }

    /**
     * Renvoi le chemin vers le thumb, la génére si besoin
     *
     * @param  String $file Nom du fichier
     * @return String chemin ver le thumb
     */
    public function getThumbSRC($file)
    {
        $gabarit_file = dirname($file) . '/THUMB/' . basename($file);
        $web_root = SERVER_ROOT . substr($this->_path, strlen(PHYSICAL_PATH));
        if (!file_exists($this->_path . $gabarit_file)) {
            if (!file_exists($this->_path . $file)) {
                //on prend une image par défaut
                return $web_root . 'nopic/nopicThumb.jpg';
            }
            $this->name = $file;
            $this->resize(150, 50, '', $gabarit_file);
        }

        return $web_root . $gabarit_file;
    }

    /**
     * Setter des extentions autorisés
     *
     * @param array $aExtensions Tableau contenant les extentions autorisés
     */
    public function setExtensions($aExtensions)
    {
        $this->_aExtensions = (is_array($aExtensions)) ? $aExtensions : array ();
    }

    /**
     * Getter des extentions autorisés
     *
     * @return array Tableau contenant les extentions autorisés
     */
    public function getExtensions()
    {
        return $this->_aExtensions;
    }

    /**
     * Getter du chemin physique du fichier
     *
     * @return String Le chemin physique du fichier
     */
    public function getPhysicalName()
    {
        return $this->_path . $this->name;
    }

    /**
     * Renvoi la taille du fichier formaté en fonction de l'ordre de grandeur
     *
     * @param  Int    $octets La taille du fichier en octets
     * @return String La taille du fichier formaté en fonction de l'ordre de grandeur
     */
    public static function displayFileSize($octets)
    {
        if ($octets >= pow(2, 20)) {
            $return = round($octets / pow(1024, 2), 2);
            $suffix = "Mo";
        } elseif ($octets >= pow(2, 10)) {
            $return = round($octets / pow(1024, 1), 2);
            $suffix = "Ko";
        } else {
            $return = $octets;
            $suffix = "Octets";
        }

        return $return . ' ' . $suffix;
    }

    /**
     * Renvoi la taille maximale autorisé par le serveur à l'upload formaté en fonction de l'ordre de grandeur
     *
     * @return String La taille maximale autorisé par le serveur à l'upload formaté en fonction de l'ordre de grandeur
     */
    public static function getMaxUpload()
    {
        $postMaxSize = self :: stringToByte(ini_get('post_max_size'));
        $uploadMaxSize = self :: stringToByte(ini_get('upload_max_filesize'));

        return self :: displayFileSize(min($postMaxSize, $uploadMaxSize));
    }

    /**
     * Supprime un fichier nommé
     *
     * @param  String  $file Le nom du fichier
     * @return boolean True si le fichier a été trouvé et supprimé, false sinon
     */
    public static function deleteFromName($file)
    {
        if ($file != '') {
            if (@ unlink($file)) {
                $_dir = dirname($file);
                if (is_dir($_dir)) {
                    $a = scandir($_dir);
                    if (sizeof($a) == 2) {
                        rmdir($_dir);
                    }
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Calcule le type mime du fichier $filename
     *
     * @param  String $filename le Nom du fichier
     * @return String Le type mime du fichier
     */
    public static function mimeType($filename)
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimetype = finfo_file($finfo, $filename);
        finfo_close($finfo);
        return $mimetype;
    }

    /**
     * @param la chaine à convertir (du type xK, xM,xG)
     * @return la valeur convertie en octets
     */
    private static function stringToByte($string)
    {
        $value = substr($string, 0, -1);
        $unit = strtolower(substr($string, -1));
        switch ($unit) {
            case 'k' :
                $value *= pow(2, 10);
                break;
            case 'm' :
                $value *= pow(2, 20);
                break;
            case 'g' :
                $value *= pow(2, 30);
                break;
        }

        return $value;
    }
}
