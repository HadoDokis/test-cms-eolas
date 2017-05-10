<?php
require '../include/inc.bo_init.php';
Utilisateur::checkConnected();
require CLASS_DIR . 'class.db_historique.php';
require CLASS_DIR . 'class.db_page.php';
require CLASS_DIR . 'class.db_webotheque.php';
require CLASS_DIR . 'class.db_formulaire.php';
require CLASS_DIR . 'class.Pagination.php';

// On récupère les info de l'utilisateur...
$sql = "select
            historique.ID_UTILISATEUR as IDENTIFIANT,
            trim(concat_ws(' ', UTI_PRENOM, UTI_NOM, HIS_UTILISATEUR)) as INFO
        from HISTORIQUE_UTILISATEUR historique
        left join UTILISATEUR u on (u.ID_UTILISATEUR = historique.ID_UTILISATEUR)
        where historique.SIT_CODE = " . $dbh->quote(CMS::getCurrentSite()->getID()) . "
            and historique.ID_UTILISATEUR= ".intval($_GET['ID_UTILISATEUR']) . "
            and historique.HIS_DATE >= " . intval($_GET['dateDebut']) . " and historique.HIS_DATE < " . intval($_GET['dateFin']) . "
        order by HIS_DATE desc
        limit 1";
if (! ($rowUtilisateur = $dbh->query($sql)->fetch(PDO::FETCH_ASSOC))) {
    die(gettext('Ressource_non_disponible'));
}
$filtre = " u.SIT_CODE = " . $dbh->quote(CMS::getCurrentSite()->getID()) . "
            and ID_UTILISATEUR = " . intval($rowUtilisateur['IDENTIFIANT']) . "
            and u.HIS_DATE >= " . intval($_GET['dateDebut']) . "
            and u.HIS_DATE < " . intval($_GET['dateFin']);
?>
<!DOCTYPE html>
<html>
<head>
    <?php include '../include/inc.bo_enTete.php';?>
    <script src="<?php echo SERVER_ROOT ?>include/js/onglet.js"></script>
</head>
<body id="popup">
    <?php include('../include/inc.bo_bandeau_hautPopup.php') ?>
    <div id="bo_contenuPopup">
        <h2>Historique de <?php echo secureInput($rowUtilisateur['INFO'])?></h2>
        <h3>Du <?php echo secureInput(date('d/m/Y', intval($_GET['dateDebut'])) . ' au ' . date('d/m/Y', strtotime("-1 day", intval($_GET['dateFin']))))?></h3>
        <div class="onglet_panels">
        <?php
        $pPage = new Pagination('page');
        $pPage->setOrderBy('p.ID_HISTORIQUE_PAGE desc');
        $pPage->setFilter($filtre);
        $pPage->setCount("select count(distinct p.ID_HISTORIQUE_PAGE)
                      from HISTORIQUE_PAGE p
                      inner join HISTORIQUE_UTILISATEUR u on (u.ID_HISTORIQUE_UTILISATEUR = p.ID_HISTORIQUE_UTILISATEUR)");

         $sql = "select *, p.HIS_DETAIL as DETAIL, p.HIS_DATE as DATEHISTO
                  from HISTORIQUE_PAGE p
                 inner join HISTORIQUE_UTILISATEUR u on (u.ID_HISTORIQUE_UTILISATEUR = p.ID_HISTORIQUE_UTILISATEUR)";

        if ($pPage->getNb() > 0) {
            $aWorkflow = $dbh->query("select PST_CODE, PST_LIBELLE from DD_PAGESTATUT")->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE | PDO::FETCH_COLUMN);?>
        <fieldset class="tab" >
            <legend><?php echo gettext('Pages')?></legend>
            <p class="aligncenter"><?php echo $pPage->reglette()?></p>
            <table class="liste">
                <tr>
                    <th><?php echo gettext('Date') ?></th>
                    <th><?php echo gettext('Page')?></th>
                    <th><?php echo gettext('Action')?></th>
                    <th><?php echo gettext('Detail')?></th>
                </tr>
                <?php foreach ($pPage->fetch($sql) as $rowPage) {
                    $oPage = new Page($rowPage['ID_PAGE']);?>
                    <tr>
                        <td class="aligncenter"><?php echo date('d/m/Y H:i', intval($rowPage['DATEHISTO']))?></td>
                        <td>
                            <?php echo secureInput($oPage->exist() ? $oPage->getField('PAG_TITRE_MENU') : $rowPage['HIS_INFO']);?>
                            <?php echo ' (' . intval($rowPage['ID_PAGE']) . ')'?>
                        </td>
                        <td>
                            <?php echo secureInput($aHistoriqueAction[$rowPage['HIS_ACTION']][$rowPage['HIS_TYPE']])?>
                            <?php echo $rowPage['HIS_TYPE'] == 'PARAGRAPHE' ? ' n°' . intval($rowPage['ID_PARAGRAPHE']) : '';?>
                        </td>
                        <?php if ($rowPage['HIS_TYPE'] == 'WORKFLOW') {?>
                            <td class="aligncenter <?php echo secureInput($rowPage['DETAIL'])?>">
                                <?php echo secureInput(extraireLibelle($aWorkflow[$rowPage['DETAIL']]));?>
                            </td>
                        <?php } else {?>
                            <td>
                                <?php echo secureInput($rowPage['DETAIL']);?>
                            </td>
                        <?php }?>
                    </tr>
                <?php }?>
            </table>
        </fieldset>
        <?php }?>

        <?php
        $pWebo = new Pagination('webo');
        $pWebo->setOrderBy('w.ID_HISTORIQUE_WEBOTHEQUE desc');
        $pWebo->setFilter($filtre);
        $pWebo->setCount("select count(distinct w.ID_HISTORIQUE_WEBOTHEQUE)
                      from HISTORIQUE_WEBOTHEQUE w
                      inner join HISTORIQUE_UTILISATEUR u on (u.ID_HISTORIQUE_UTILISATEUR = w.ID_HISTORIQUE_UTILISATEUR)");

         $sql = "select *, w.HIS_DETAIL as DETAIL, w.HIS_DATE as DATEHISTO
                  from HISTORIQUE_WEBOTHEQUE w
                 inner join HISTORIQUE_UTILISATEUR u on (u.ID_HISTORIQUE_UTILISATEUR = w.ID_HISTORIQUE_UTILISATEUR)";

        if ($pWebo->getNb() > 0) {?>
        <fieldset class="tab" >
            <legend><?php echo gettext('Webotheque')?></legend>
            <p class="aligncenter"><?php echo $pWebo->reglette()?></p>
            <table class="liste">
                <tr>
                    <th><?php echo gettext('Date') ?></th>
                    <th><?php echo gettext('Type')?></th>
                    <th><?php echo gettext('Libelle')?></th>
                    <th><?php echo gettext('Action')?></th>
                    <th><?php echo gettext('Detail')?></th>
                </tr>
                <?php foreach ($pWebo->fetch($sql) as $rowWebo) {
                    $classWebo = 'Webo_' . str_replace('WBT_', '', $rowWebo['WBT_CODE']);
                    $oWebo = new $classWebo($rowWebo['ID_WEBOTHEQUE']);?>
                    <tr>
                        <td class="aligncenter"><?php echo date('d/m/Y H:i', intval($rowWebo['DATEHISTO'])) ?></td>
                        <td class="aligncenter"><?php echo gettext(Webotheque::$_aTraduction[$rowWebo['WBT_CODE']]) ?></td>
                        <td>
                            <?php echo secureInput($oWebo->exist() ? $oWebo->getField('WEB_LIBELLE') : $rowWebo['HIS_INFO'])?>
                            <?php echo ' (' . intval($rowWebo['ID_WEBOTHEQUE']) . ')'?>
                        </td>
                        <td><?php echo secureInput($aHistoriqueAction[$rowWebo['HIS_ACTION']]['FICHE'])?></td>
                        <td><?php echo secureInput($rowWebo['DETAIL']);?></td>
                    </tr>
                <?php }?>
            </table>
        </fieldset>
        <?php }?>

        <?php
        $pForm = new Pagination('form');
        $pForm->setOrderBy('f.ID_HISTORIQUE_FORMULAIRE desc');
        $pForm->setFilter($filtre);
        $pForm->setCount("select count(distinct f.ID_HISTORIQUE_FORMULAIRE)
                      from HISTORIQUE_FORMULAIRE f
                      inner join HISTORIQUE_UTILISATEUR u on (u.ID_HISTORIQUE_UTILISATEUR = f.ID_HISTORIQUE_UTILISATEUR)");

         $sql = "select *, f.HIS_DETAIL as DETAIL, f.HIS_DATE as DATEHISTO
                 from HISTORIQUE_FORMULAIRE f
                 inner join HISTORIQUE_UTILISATEUR u on (u.ID_HISTORIQUE_UTILISATEUR = f.ID_HISTORIQUE_UTILISATEUR)";

        if ($pForm->getNb() > 0) {?>
        <fieldset class="tab" >
            <legend><?php echo gettext('Formulaires')?></legend>
            <p class="aligncenter"><?php echo $pForm->reglette()?></p>
            <table class="liste">
                <tr>
                    <th><?php echo gettext('Date') ?></th>
                    <th><?php echo gettext('Libelle')?></th>
                    <th><?php echo gettext('Action')?></th>
                    <th><?php echo gettext('Detail')?></th>
                </tr>
                <?php foreach ($pForm->fetch($sql) as $rowForm) {
                    $oForm = new Formulaire($rowForm['ID_FORMULAIRE']);?>
                    <tr>
                        <td class="aligncenter"><?php echo date('d/m/Y H:i', intval($rowForm['DATEHISTO'])) ?></td>
                        <td>
                            <?php echo secureInput($oForm->exist() ? $oForm->getField('FRM_LIBELLE') : $rowForm['HIS_INFO']);?>
                            <?php echo ' (' . intval($rowForm['ID_FORMULAIRE']) . ')'?>
                        </td>
                        <td><?php echo secureInput($aHistoriqueAction[$rowForm['HIS_ACTION']][$rowForm['HIS_TYPE']])?></td>
                        <td><?php echo secureInput($rowForm['DETAIL']);?></td>
                    </tr>
                <?php }?>
            </table>
        </fieldset>
        <?php }?>

        <?php
        $pModule = new Pagination('module');
        $pModule->setOrderBy('e.ID_HISTORIQUE_EXTERNE desc');
        $pModule->setFilter($filtre);
        $pModule->setCount("select count(distinct e.ID_HISTORIQUE_EXTERNE)
                      from HISTORIQUE_EXTERNE e
                      inner join HISTORIQUE_UTILISATEUR u on (u.ID_HISTORIQUE_UTILISATEUR = e.ID_HISTORIQUE_UTILISATEUR)");

         $sql = "select *, e.HIS_DETAIL as DETAIL, e.HIS_DATE as DATEHISTO
                 from HISTORIQUE_EXTERNE e
                 left join DD_HISTORIQUE_EXTERNE he on(he.HEX_CODE = e.HEX_CODE)
                 left join DD_MODULE m on (m.MOD_CODE = he.MOD_CODE)
                 inner join HISTORIQUE_UTILISATEUR u on (u.ID_HISTORIQUE_UTILISATEUR = e.ID_HISTORIQUE_UTILISATEUR)";

        if ($pModule->getNb() > 0) {?>
        <fieldset class="tab" >
            <legend><?php echo gettext('Modules')?></legend>
            <p class="aligncenter"><?php echo $pModule->reglette()?></p>
            <table class="liste">
                <tr>
                    <th><?php echo gettext('Date') ?></th>
                    <th><?php echo gettext('Modules')?></th>
                    <th><?php echo gettext('Libelle')?></th>
                    <th><?php echo gettext('Action')?></th>
                    <th><?php echo gettext('Detail')?></th>
                </tr>
                <?php foreach ($pModule->fetch($sql) as $rowModule) {

                    $aInfoModule = Historique::getInfoModule($rowModule['HEX_TABLE'], $rowModule['HEX_CHAMP_ID'], $rowModule['HIS_IDENTIFIANT'])
                    ?>
                    <tr>
                        <td class="aligncenter"><?php echo date('d/m/Y H:i', intval($rowModule['DATEHISTO'])) ?></td>
                        <td><?php echo secureInput(extraireLibelle($rowModule['MOD_LIBELLE']))?></td>
                        <td>
                            <?php echo secureInput($aInfoModule ? $aInfoModule[$rowModule['HEX_CHAMP_LIBELLE']] : $rowModule['HIS_INFO'])?>
                            <?php echo ' (' . secureInput($rowModule['HIS_IDENTIFIANT']) . ')'?>
                        </td>
                        <td><?php echo secureInput($aHistoriqueAction[$rowModule['HIS_ACTION']]['FICHE'])?></td>
                        <td>
                            <?php echo secureInput($rowModule['DETAIL']);?>
                        </td>
                    </tr>
                <?php }?>
            </table>
        </fieldset>
        <?php }?>

        <?php
        $pAdmin = new Pagination('admin');
        $pAdmin->setOrderBy('a.ID_HISTORIQUE_ADMIN desc');
        $pAdmin->setFilter($filtre);
        $pAdmin->setCount("select count(distinct a.ID_HISTORIQUE_ADMIN)
                      from HISTORIQUE_ADMIN a
                      inner join HISTORIQUE_UTILISATEUR u on (u.ID_HISTORIQUE_UTILISATEUR = a.ID_HISTORIQUE_UTILISATEUR)");

         $sql = "select a.*, a.HIS_DETAIL as DETAIL, a.HIS_DATE as DATEHISTO
                 from HISTORIQUE_ADMIN a
                 inner join HISTORIQUE_UTILISATEUR u on (u.ID_HISTORIQUE_UTILISATEUR = a.ID_HISTORIQUE_UTILISATEUR)";

        if ($pAdmin->getNb() > 0) {?>
        <fieldset class="tab" >
            <legend>Configuration</legend>
            <p class="aligncenter"><?php echo $pAdmin->reglette()?></p>
            <table class="liste">
                <tr>
                    <th><?php echo gettext('Date') ?></th>
                    <th><?php echo gettext('Libelle')?></th>
                    <th><?php echo gettext('Action')?></th>
                    <th><?php echo gettext('Detail')?></th>
                </tr>
                <?php foreach ($pAdmin->fetch($sql) as $rowAdmin) {
                    $libelle = '';
                    if ($rowAdmin['HIS_TYPE'] == 'SITE') {
                        $oSite = new Site($rowAdmin['HIS_IDENTIFIANT']);
                        // Le site doit normallement toujours exister
                        if ($oSite && $oSite->exist()) {
                            $libelle = $oSite->getField('SIT_LIBELLE');
                        }
                    } else {
                        $libelle = $rowAdmin['HIS_UTILISATEUR'];
                        if (empty($libelle)) {
                            $oUser = new Utilisateur($rowAdmin['HIS_IDENTIFIANT']);
                            if ($oUser && $oUser->exist()) {
                                $libelle = $oUser->getField('UTI_PRENOM') . ' ' . $oUser->getField('UTI_NOM');
                            }
                        }
                    }
                    $libelle .= ' (' . $rowAdmin['HIS_IDENTIFIANT'] . ')';
                    ?>
                    <tr>
                        <td class="aligncenter"><?php echo date('d/m/Y H:i', intval($rowAdmin['DATEHISTO']))?></td>
                        <td>
                            <?php echo secureInput($libelle)?>
                        </td>
                        <td><?php echo secureInput($aHistoriqueAction[$rowAdmin['HIS_ACTION']][$rowAdmin['HIS_TYPE']])?></td>
                        <td><?php echo secureInput($rowAdmin['DETAIL']);?></td>
                    </tr>
                <?php }?>
            </table>
        </fieldset>
        <?php }?>
        </div>
    </div>
    <?php include('../include/inc.bo_bandeau_basPopup.php') ?>
</body>
</html>
