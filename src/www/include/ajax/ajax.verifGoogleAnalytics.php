<?php
require_once '../config.php';
if (! empty($_POST['SIT_GA_ID']) && ! empty($_POST['SIT_GA_KEYFILE_URL'])) {
    require_once (CLASS_DIR . 'class.GoogleAnalytics.php');

    // Test accès à google
    try {
        $ga = new gapi($_POST['SIT_GA_ID'], UPLOAD_EXTERNE_PHYSIQUE . $_POST['SIT_GA_KEYFILE_URL']);
    } catch (Exception $e) {}
    if ($ga) {
        $time = strtotime('yesterday');
        $yesterday = date('Y-m-d', $time);
        try {
            $ga->requestReportData($_POST['SIT_GA_ID_SITE'], array(
                'medium'
            ), array(
                'visits',
                'pageviews',
                'timeOnSite',
                'newVisits',
                'bounces',
                'entrances'
            ), array(
                'medium'
            ), null, $yesterday, $yesterday);
            echo 'OK';
        } catch (Exception $e) {
            echo 'ERREUR_IDSITE';
        }
    } else {
        echo 'ERREUR_AUTHENTIFICATION';
    }
}
