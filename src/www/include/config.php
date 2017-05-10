<?php
error_reporting(E_ALL & ~(E_STRICT|E_NOTICE));
mb_internal_encoding('UTF-8');
ini_set('display_errors', true);

// Time zone
ini_set('date.timezone', 'Europe/Paris');

//BD
define('DB_NAME', 'cms');
define('DB_DSN', 'mysql:dbname='.DB_NAME.';host=localhost');
define('DB_USER', 'deveolas');
define('DB_PASSWORD', '2tZ3Ym9Yc');

//RACINE
define('SERVER_ROOT', '/');
define('PHYSICAL_ROOT', '/home/wwwroot/cmsv7/');
define('PHYSICAL_PATH', PHYSICAL_ROOT . 'www/');

//LOGIN_REFERENCEUR
define('LOGIN_REFERENCEUR', 'refeolas');

//UPLOAD
define('UPLOAD_IMAGE', SERVER_ROOT . 'uploads/Image/');
define('UPLOAD_DOCUMENT', SERVER_ROOT . 'uploads/Document/');
define('UPLOAD_FLASH', SERVER_ROOT . 'uploads/Multimedia/');
define('UPLOAD_VIDEO', SERVER_ROOT . 'uploads/Multimedia/');
define('UPLOAD_MUSIC', SERVER_ROOT . 'uploads/Multimedia/');
define('UPLOAD_EXTERNE', SERVER_ROOT . 'uploads/Externe/');
define('UPLOAD_FORMULAIRE', SERVER_ROOT . 'uploads/Formulaire/');
define('UPLOAD_STYLE', SERVER_ROOT . 'uploads/Style/');
define('UPLOAD_CAPTCHA', SERVER_ROOT . 'uploads/Captcha/');
define('UPLOAD_IMAGE_PHYSIQUE', PHYSICAL_PATH . 'uploads/Image/');
define('UPLOAD_DOCUMENT_PHYSIQUE', PHYSICAL_PATH . 'uploads/Document/');
define('UPLOAD_FLASH_PHYSIQUE', PHYSICAL_PATH . 'uploads/Multimedia/');
define('UPLOAD_VIDEO_PHYSIQUE', PHYSICAL_PATH . 'uploads/Multimedia/');
define('UPLOAD_MUSIC_PHYSIQUE', PHYSICAL_PATH . 'uploads/Multimedia/');
define('UPLOAD_EXTERNE_PHYSIQUE', PHYSICAL_PATH . 'uploads/Externe/');
define('UPLOAD_FORMULAIRE_PHYSIQUE', PHYSICAL_PATH . 'uploads/Formulaire/');
define('UPLOAD_STYLE_PHYSIQUE', PHYSICAL_PATH . 'uploads/Style/');
define('UPLOAD_CACHE_PHYSIQUE', PHYSICAL_PATH . 'uploads/Cache/');
define('UPLOAD_CAPTCHA_PHYSIQUE', PHYSICAL_PATH . 'uploads/Captcha/');

// DOSSIER SOURCE de l'IMPORT par FTP
define('IMPORT_IMAGE_FTP_PHYSIQUE', PHYSICAL_ROOT . 'webothequeImportFTP/Image/');
define('IMPORT_DOCUMENT_FTP_PHYSIQUE', PHYSICAL_ROOT . 'webothequeImportFTP/Document/');
define('IMPORT_FLASH_FTP_PHYSIQUE', PHYSICAL_ROOT . 'webothequeImportFTP/Multimedia/');
define('IMPORT_VIDEO_FTP_PHYSIQUE', PHYSICAL_ROOT . 'webothequeImportFTP/Multimedia/');
define('IMPORT_MUSIC_FTP_PHYSIQUE', PHYSICAL_ROOT . 'webothequeImportFTP/Multimedia/');

//EMAILS
define('EMAIL_SMTPHOST', ''); //Laisser vide pour passer par le sendmail local
define('EMAIL_FROM', 'dev-projets@eolas-service.com');
define('EMAIL_FROMNAME', 'Projet Dev');
define('EMAIL_SUBSTITUTION', ''); //Laisser vide pour envoyer les mails aux bons destinataires, plusieurs destinataires possibles séparés par ;

//URL_REWRITING
define('URL_REWRITING', true);
define('TAB_REP_VIRTUEL_INTERDITS',
        serialize(array (   'uploads',
                            'cgi',
                            'stats',
                            'admin',
                            'index.php',
                            'cms',
                            'externe',
                            'formulaire',
                            'images',
                            'include',
                            'tinymce',
                            'webotheque',
                            'statistique')));

//IMAGEMAGICK
define('IMAGICK_CONVERT', '/usr/bin/convert');

//Minifier la feuille de style
define('MINIFY_CSS', true);
//* Dossier(s) d'import des différents ".less"
define('LESS_IMPORT_PATH', serialize(array(
    PHYSICAL_PATH . 'include/css/less_import/',
)));
//*/

//DIR
define('CLASS_DIR', PHYSICAL_PATH . 'include/class/');

//Site support
define('SIT_SUPPORT','http://cms-support.eolas.fr/');

// Flux RSS d'actu par défaut
define('SIT_RSS_ACTU_DEFAULT', 'http://cms-support.eolas.fr/rss_actualite.php');
// Flus RSS webmarketing par défaut
define('SIT_RSS_WEBMARKET_DEFAULT', 'http://webmarketing.eolas.fr/feed/');

//Activation du mode DEBUG pour le login indiqué
define('CMS_DEBUG', 'eolas');

//activation des descriptions dans les fiches webotheques
//changer a true si activation des descriptions
define('WEB_DESCRIPTION', false);

// Optionnel : Le CMS doit-il prendre en charge la migration de l'algo de hachage du mot de passe (MD5 => BLOWFISH)
define('AUTHHASH_MIGRATED', false);
