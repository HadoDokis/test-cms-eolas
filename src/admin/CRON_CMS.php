<?php
/*
 * Liste des tâches planifiées associées au CMS
 *
 * Sur un enregistrement au sein du crontab de la forme :
 * *\/30 * * * *  deveolas cd /home/wwwroot/DOC_ROOT/admin/ && php5 ./CRON_CMS.php >> /home/wwwroot/DOC_ROOTX/admin/logs/CRON_$(date +\%Y_\%m_\%d).log 2>&1
 * (Remplacer \/30 par /30 et DOC_ROOT par le document root)
 * Le cron est exécuté toutes les 30mn
 * Les sorties sont concaténées au fichier "admin/logs/CRON_{YYYY_MM_JJ}.log"
 *
 * Pour ajouter une tâche, il suffit de compléter ce fichier en réalisant les opération suivantes :
 * - Ajouter un test sur l'heure souhaitée a associer au traitement
 * -- "if ($dateTime == strtotime("today 03:30"))" => Si l'heure d'exécution du cron est 03h30
 * - Inclure le fichier réalisant le traitement :
 * -- "include dirname(__FILE__) . '/CRON_xxx.php';" => Inclure le fichier "CRON_xxx.php" réalisant le traitement
 *
 * Note : Les fichiers de traitement du type "CRON_xxx.php" doivent necesairement réaliser des "require_once" afin d'éviter les erreurs sur les inclusions multiples des mêmes fichiers
 */
require dirname(__FILE__) . '/../www/include/config.php';
require dirname(__FILE__) . '/../www/include/lib.common.php';
require CLASS_DIR . 'class.DB.php';
require CLASS_DIR . 'class.CMS.php';

ini_set('display_errors', true);
ob_end_flush();
setlocale(LC_ALL, 'fr_FR.UTF-8');
setlocale(LC_NUMERIC, 'en_US.UTF-8');

$dateTime = mktime(date('H'), date('i'), 0);
echo "#" . date('d/m/Y H:i') . "#\n";
echo "##################\n";

// Tâches à 00:30
if ($dateTime == strtotime("today 00:30")) {
    // Gestion de la mise en ligne et hors ligne des pages
    include dirname(__FILE__) . '/CRON_pages.php';
    // Suppression anciens logs
    foreach (glob("logs/CRON_*.log") as $filename) {
        if (filemtime($filename) < (time() - 86400 * 30)) {
            unlink($filename);
        }
    }
    echo "Supression anciens logs\n";
    // Statistiques BO
    include dirname(__FILE__) . '/CRON_statistique_bo.php';
    // Statistiques FO
    include dirname(__FILE__) . '/CRON_statistique_visite.php';
}
