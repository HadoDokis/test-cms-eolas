<?php
require '../include/inc.bo_init.php';
require CLASS_DIR . 'class.db_page.php';
require CLASS_DIR . 'class.Arbo.php';
Utilisateur::checkConnected();
Page::unlockAll();

// changement de site ??
if (!empty ($_GET['SIT_CODE'])) {
    $location = SERVER_ROOT . 'cms/cms_index.php';
    if (!empty($_GET['from'])) {
        if ($_GET['from'] == SERVER_ROOT . 'cms/cms_pageArbo.php'
                || $_GET['from'] == SERVER_ROOT . 'cms/cms_pseudo.php'
                || strpos($_GET['from'], 'Liste.php')>0
                || strpos($_GET['from'], 'index.php')>0) {
            $location = $_GET['from'];
        }
    }
    CMS::redirect($_GET['SIT_CODE'], $location);
}

$oArbo = new Arbo(Utilisateur::getConnected()->isSEO() ? 'REFERENCEMENT' : 'REDACTIONNEL');
?>
<!DOCTYPE html>
<html>
<head>
    <?php include('../include/inc.bo_enTete.php') ?>
    <script>
        $(document).ready(cmsBO.initArbo);
        $(document).ready(cmsBO.initArboDnD);
        $(document).ready(hideLegend_initialise);
        function hideLegend_initialise()
        {
            if (readCookie('isLegendHidden') == '1') {
                $('#bo_arbo_legende_inner').css('display', 'none');
                $('#bo_arbo_legende_title span').css('display', 'none');
            }
        }
        function hideLegend()
        {
            var isLegendHidden = readCookie('isLegendHidden');
            var val = '';
            if (isLegendHidden == '1') {
                createCookie('isLegendHidden', '0');
                val = 'block';
            } else {
                createCookie('isLegendHidden', '1');
                val = 'none';
            }
            $('#bo_arbo_legende_inner').css('display', val);
            $('#bo_arbo_legende_title span').css('display', val);
        }
    </script>
</head>
<body>
<div id="document">
    <?php $aMenuKey = array('CTN', 'ARBO'); include('../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">
            <h2 class="titreArbo">Arborescence</h2>
            <div id="bo_arbo_legende">
                <div id="bo_arbo_legende_title"><a href="javascript:hideLegend()"><img src="<?php echo SERVER_ROOT?>images/pictoLegend.png" alt=""><span><?php echo gettext('Legende')?></span></a></div>
                <div id="bo_arbo_legende_inner">
                    <h5>Etat des pages</h5>
                    <div class="bo_arbo_legende_etats">
                        <?php
                        $sql = "select * from DD_PAGESTATUT order by PST_POIDS";
                        foreach ($dbh->query($sql) as $rowTemp) { ?>
                        <p class="<?php echo $rowTemp['PST_CODE']?>"><?php echo secureInput(extraireLibelle($rowTemp['PST_LIBELLE']))?></p>
                        <?php } ?>
                        <p class="denied"><?php echo gettext('Hors perimetre')?></p>
                    </div>
                    <div class="bo_arbo_legende_acces">
                        <p><img src="<?php echo SERVER_ROOT?>images/page_white_key.png" alt=""> Accès restreint</p>
                        <p><img src="<?php echo SERVER_ROOT?>images/page_go.png" alt=""> Redirection</p>
                        <p><img src="<?php echo SERVER_ROOT?>images/page_invisible.png" alt=""> Non visible dans les menus</p>
                    </div>
                </div>
            </div>
            <?php echo Arbo::action() ?>
            <?php echo $oArbo->draw() ?>
        </div>
    </div>
    <?php include('../include/inc.bo_bandeau_bas.php') ?>
</div>
</body>
</html>
