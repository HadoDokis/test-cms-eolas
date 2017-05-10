<?php
// Partie administration de l'application
define('ADMIN_ROOT', SERVER_ROOT . 'admin/'); // Racine web du module
define('ADMIN_PATH', PHYSICAL_PATH . 'admin/'); // Chemin absolu vers la module "admin" sur le système de fichier
define('ADMIN_LOG_DIR', PHYSICAL_ROOT . 'admin/logs/'); // Chemin absolu vers le répertoire contenant l'ensemble des logs et sauvegarde DB générés par le module admin
define('ADMIN_LOG_FILE', ADMIN_LOG_DIR . 'admin.log'); // Localisation du fichier de log
define('MODULES_DIR', ADMIN_PATH . 'include/modules/'); // Répertoire où trouver les différents modules
define('ADMIN_DB_SAVE', true); // La fonctionnalité de sauvegarde de la base de données (dans ADMIN_LOG_DIR) est-elle disponible au sein de l'interface et avant mise à jour de l'application.
define('ERROR_LOG', true); // Les erreurs d'éxécutions doivent-elles être signalées ? (utile seulement dans le cas d'un MAIL_REPORT=true)
define('SLOWREQUEST_LOG', true); // Les requêtes HTTP lentes doivent-elles être enregistrées ?
define('SLOWREQUEST_IGNORED_REGEXP', serialize(array(
    '#'.ADMIN_ROOT.'install\.php#',
    '#'.ADMIN_ROOT.'upgrade\.php#',
    '#'.ADMIN_ROOT.'(index\.php)?\?(dbDump|dbSaveDel|dbStructCmp|deletePIXLR|documentIndex|imageFormatDel|slowrequest_nbline)=.*#',
    '#'.SERVER_ROOT.'cms_viewFile\.php\?.+#',
    '#'.SERVER_ROOT.'include/viewfilesecure\.php\?.+#'
    )
)); // Tableau sérialisé des URL connues pour être lentes (qui ne sont donc pas enregistrées). Ces URL correspondent à des expressions régulières
define('SLOWREQUEST_LOG_TTL', '-2 seconds'); // TTL entre deux enregistrements des requêtes lentes au sein du fichier journal
define('SLOWREQUEST_LOG_FILE', ADMIN_LOG_DIR . 'slow-HTTP-request.log'); // Fichier journal des requêtes HTTP lentes
define('SLOWREQUEST_DURATION','2'); // Durée en seconde à partir de laquelle l'outil déclenche une "requête HTTP lente"
define('MAIL_REPORT',true); // L'outil génrère t-il un mail contenant le détail des requêtes HTTP lentes (si SLOWREQUEST_LOG=true) ou des erreurs d'éxécution (si ERROR_LOG=true)
define('MAIL_REPORT_TO', serialize(array('dev-projets@eolas-service.com'))); // Tableau sérialisé contenant la liste des destinataires des mails
define('MAIL_REPORT_TTL', '-5 minutes'); // TTL entre deux générations d'un mail de chacun des types (requête lente et errreur)
