<?php
require '../include/inc.bo_init.php';
Utilisateur::checkConnected();
require CLASS_DIR . 'class.db_historique.php';
require CLASS_DIR . 'class.db_page.php';
require CLASS_DIR . 'class.db_webotheque.php';
require CLASS_DIR . 'class.db_formulaire.php';
require CLASS_DIR . 'class.Pagination.php';

if (!isset($_GET['SIT_CODE'])) {
    die(gettext('Ressource_non_disponible'));
}

$filtre = " h.SIT_CODE = " . $dbh->quote($_GET['SIT_CODE']) . "
            and h.HIS_DATE >= " . intval($_GET['dateDebut']) . "
            and h.HIS_DATE < " . intval($_GET['dateFin']);

$oSite = New Site($_GET['SIT_CODE']);
if ($oSite->exist()) {
    $titre = gettext('Historique_du') . ' ' . gettext('Site') . ' : ' . $oSite->getField('SIT_LIBELLE');
} else {
    return;
}
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
        <h2><?php echo secureInput($titre)?></h2>
        <h3>Du <?php echo date('d/m/Y', intval($_GET['dateDebut'])) . ' au ' . date('d/m/Y', strtotime("-1 day", intval($_GET['dateFin'])))?></h3>
        <div class="onglet_panels">
        <?php
            $pUser = new Pagination('user');
            $pUser->setOrderBy('LAST_CONNEXION desc');
            $pUser->setFilter($filtre);
            $pUser->setCount(array("select distinct u.ID_UTILISATEUR from UTILISATEUR u
                        left join HISTORIQUE_UTILISATEUR h on (u.ID_UTILISATEUR = h.ID_UTILISATEUR)",
                        "select distinct h.ID_UTILISATEUR from HISTORIQUE_UTILISATEUR h
                        left join UTILISATEUR u on (u.ID_UTILISATEUR = h.ID_UTILISATEUR)"));

            $sql = "select
                u.ID_UTILISATEUR,
                trim(concat_ws(' ', UTI_PRENOM, UTI_NOM, HIS_UTILISATEUR)) as LIBELLE,
                sum(HIS_NBACTION) as NB_ACTION,
                count(ID_HISTORIQUE_UTILISATEUR) as NB_CONNEXION,
                max(HIS_DATE) as LAST_CONNEXION
           from UTILISATEUR u
           left join HISTORIQUE_UTILISATEUR h on (u.ID_UTILISATEUR = h.ID_UTILISATEUR)";

            $sqlUnion = "select
                        u.ID_UTILISATEUR,
                        trim(concat_ws(' ', UTI_PRENOM, UTI_NOM, HIS_UTILISATEUR)) as LIBELLE,
                        sum(HIS_NBACTION) as NB_ACTION,
                        count(ID_HISTORIQUE_UTILISATEUR) as NB_CONNEXION,
                        max(HIS_DATE) as LAST_CONNEXION
                   from  HISTORIQUE_UTILISATEUR h
                   left join UTILISATEUR u on (u.ID_UTILISATEUR = h.ID_UTILISATEUR)";
            ?>
            <fieldset class="tab">
                <legend><?php echo gettext('Contributeurs')?></legend>
                <p class="aligncenter"><?php echo $pUser->reglette()?></p>
                <?php if ($pUser->getNb() > 0) {?>
                <table class="liste">
                    <tr>
                        <th><?php echo gettext('Contributeur')?></th>
                        <th><?php echo gettext('nb_actions')?></th>
                        <th><?php echo gettext('nb_connexions')?></th>
                        <th><?php echo gettext('Derniere connexion')?></th>
                    </tr>
                    <?php foreach ($pUser->fetch(array($sql,$sqlUnion),array('u.ID_UTILISATEUR','u.ID_UTILISATEUR')) as $rowListe) {?>
                        <tr>
                            <td class="aligncenter"><?php echo secureInput($rowListe['LIBELLE'])?></td>
                            <td class="aligncenter"><?php echo secureInput(intval($rowListe['NB_ACTION'])) . ' ' . gettext('action'); echo intval($rowListe['NB_ACTION']) > 1 ? 's':''?></td>
                            <td class="aligncenter"><?php echo secureInput(intval($rowListe['NB_CONNEXION'])) . ' ' . gettext('connexion'); echo intval($rowListe['NB_CONNEXION']) > 1 ? 's':''?></td>
                            <td class="aligncenter"><?php echo !empty($rowListe['LAST_CONNEXION']) ? date('d/m/Y', intval($rowListe['LAST_CONNEXION'])) : '-'?></td>
                        </tr>
                    <?php }?>
                </table>
                <?php }?>
            </fieldset>

        <?php
            $pPage = new Pagination('page');
            $pPage->setOrderBy('LAST_MODIFICATION desc');
            $pPage->setFilter($filtre);
            $pPage->setCount(array("select distinct p.ID_PAGE from OFF_PAGE p
                        left join HISTORIQUE_PAGE h on (p.ID_PAGE = h.ID_PAGE)",
                        "select distinct h.ID_PAGE from HISTORIQUE_PAGE h
                        left join OFF_PAGE p on (p.ID_PAGE = h.ID_PAGE)"));

            $sql = "select
                p.ID_PAGE,
                trim(concat_ws(' ', PAG_TITRE, HIS_INFO)) as LIBELLE,
                count(ID_HISTORIQUE_PAGE) as NB_ACTION,
                max(HIS_DATE) as LAST_MODIFICATION
           from OFF_PAGE p
           left join HISTORIQUE_PAGE h on (p.ID_PAGE = h.ID_PAGE)";

            $sqlUnion = "select
                        h.ID_PAGE,
                        trim(concat_ws(' ', PAG_TITRE, HIS_INFO)) as LIBELLE,
                        count(ID_HISTORIQUE_PAGE) as NB_ACTION,
                        max(HIS_DATE) as LAST_MODIFICATION
                   from  HISTORIQUE_PAGE h
                   left join OFF_PAGE p on (p.ID_PAGE = h.ID_PAGE)";
            ?>
            <fieldset class="tab" >
                <legend><?php echo gettext('Pages')?></legend>
                <p class="aligncenter"><?php echo $pPage->reglette()?></p>
                <?php if ($pPage->getNb() > 0) {?>
                <table class="liste">
                    <tr>
                        <th><?php echo gettext('Page')?></th>
                        <th><?php echo gettext('nb_actions')?></th>
                        <th><?php echo gettext('Derniere modification')?></th>
                    </tr>
                    <?php foreach ($pPage->fetch(array($sql,$sqlUnion),array('p.ID_PAGE','h.ID_PAGE')) as $rowListe) {?>
                        <tr>
                            <td class="aligncenter"><?php echo secureInput($rowListe['LIBELLE'])?></td>
                            <td class="aligncenter"><?php echo secureInput(intval($rowListe['NB_ACTION'])). ' ' . gettext('action'); echo intval($rowListe['NB_ACTION']) > 1 ? 's':''?></td>
                            <td class="aligncenter"><?php echo !empty($rowListe['LAST_MODIFICATION']) ? date('d/m/Y', intval($rowListe['LAST_MODIFICATION'])) : '-'?></td>
                        </tr>
                    <?php }?>
                </table>
                <?php }?>
            </fieldset>

            <?php
            $pWebo = new Pagination('webo');
            $pWebo->setOrderBy('LAST_MODIFICATION desc');
            $pWebo->setFilter($filtre);
            $pWebo->setCount("select count(distinct h.ID_WEBOTHEQUE) from HISTORIQUE_WEBOTHEQUE h
                              left join WEBOTHEQUE w on (w.ID_WEBOTHEQUE = h.ID_WEBOTHEQUE)");

            $sql = "select
                h.ID_WEBOTHEQUE,
                h.WBT_CODE,
                trim(concat_ws(' ', WEB_LIBELLE, HIS_INFO)) as LIBELLE,
                count(ID_HISTORIQUE_WEBOTHEQUE) as NB_ACTION,
                max(HIS_DATE) as LAST_MODIFICATION
                   from HISTORIQUE_WEBOTHEQUE h
                left join WEBOTHEQUE w on (w.ID_WEBOTHEQUE = h.ID_WEBOTHEQUE)";
            ?>
            <fieldset class="tab" >
                <legend><?php echo gettext('Webotheque')?></legend>
                <p class="aligncenter"><?php echo $pWebo->reglette()?></p>
                <?php if ($pWebo->getNb() > 0) {?>
                <table class="liste">
                    <tr>
                        <th><?php echo gettext('Type')?></th>
                        <th><?php echo gettext('Libelle')?></th>
                        <th><?php echo gettext('nb_actions')?></th>
                        <th><?php echo gettext('Derniere modification')?></th>
                    </tr>
                    <?php foreach ($pWebo->fetch($sql,'h.ID_WEBOTHEQUE') as $rowListe) {?>
                        <tr>
                            <td class="aligncenter"><?php echo secureInput(Webotheque::$_aTraduction[$rowListe['WBT_CODE']])?></td>
                            <td class="aligncenter"><?php echo secureInput($rowListe['LIBELLE'])?></td>
                            <td class="aligncenter"><?php echo secureInput(intval($rowListe['NB_ACTION'])). ' ' . gettext('action'); echo intval($rowListe['NB_ACTION']) > 1 ? 's':''?></td>
                            <td class="aligncenter"><?php echo !empty($rowListe['LAST_MODIFICATION']) ? date('d/m/Y', intval($rowListe['LAST_MODIFICATION'])) : '-'?></td>
                        </tr>
                    <?php }?>
                </table>
                <?php }?>
            </fieldset>

            <?php
            $pForm = new Pagination('form');
            $pForm->setOrderBy('LAST_MODIFICATION desc');
            $pForm->setFilter($filtre);
            $pForm->setCount("select count(distinct h.ID_FORMULAIRE) from HISTORIQUE_FORMULAIRE h
                              left join FORMULAIRE f on (f.ID_FORMULAIRE = h.ID_FORMULAIRE)");

            $sql = "select
                h.ID_FORMULAIRE,
                trim(concat_ws(' ', FRM_LIBELLE, HIS_INFO)) as LIBELLE,
                count(ID_HISTORIQUE_FORMULAIRE) as NB_ACTION,
                max(HIS_DATE) as LAST_MODIFICATION
                from HISTORIQUE_FORMULAIRE h
                left join FORMULAIRE f on (f.ID_FORMULAIRE = h.ID_FORMULAIRE)";
            ?>
            <fieldset class="tab" >
                <legend><?php echo gettext('Formulaires')?></legend>
                <p class="aligncenter"><?php echo $pForm->reglette()?></p>
                <?php if ($pForm->getNb() > 0) {?>
                <table class="liste">
                    <tr>
                        <th><?php echo gettext('Libelle')?></th>
                        <th><?php echo gettext('nb_actions')?></th>
                        <th><?php echo gettext('Derniere modification')?></th>
                    </tr>
                    <?php foreach ($pForm->fetch($sql,'h.ID_FORMULAIRE') as $rowListe) {?>
                        <tr>
                            <td class="aligncenter"><?php echo secureInput($rowListe['LIBELLE'])?></td>
                            <td class="aligncenter"><?php echo secureInput(intval($rowListe['NB_ACTION'])). ' ' . gettext('action'); echo intval($rowListe['NB_ACTION']) > 1 ? 's':''?></td>
                            <td class="aligncenter"><?php echo !empty($rowListe['LAST_MODIFICATION']) ? date('d/m/Y', intval($rowListe['LAST_MODIFICATION'])) : '-'?></td>
                        </tr>
                    <?php }?>
                </table>
                <?php }?>
            </fieldset>

            <?php
            $pModule = new Pagination('module');
            $pModule->setOrderBy('LAST_MODIFICATION desc');
            $pModule->setFilter($filtre);
            $pModule->setCount("select count(distinct h.HIS_IDENTIFIANT) from HISTORIQUE_EXTERNE h");

            $sql = "select
                h.HEX_CODE,
                MOD_LIBELLE,
                count(ID_HISTORIQUE_EXTERNE) as NB_ACTION,
                max(HIS_DATE) as LAST_MODIFICATION
                from HISTORIQUE_EXTERNE h
                left join DD_HISTORIQUE_EXTERNE he on(he.HEX_CODE = h.HEX_CODE)
                left join DD_MODULE m on (m.MOD_CODE = he.MOD_CODE)
                left join DD_SITE s on (s.SIT_CODE = h.SIT_CODE)";

            ?>
            <fieldset class="tab" >
                <legend><?php echo gettext('Modules')?></legend>
                <p class="aligncenter"><?php echo $pModule->reglette()?></p>
                <?php if ($pModule->getNb() > 0) {?>
                <table class="liste">
                    <tr>
                        <th><?php echo gettext('Modules')?></th>
                        <th><?php echo gettext('nb_actions')?></th>
                        <th><?php echo gettext('Derniere modification')?></th>
                    </tr>
                    <?php foreach ($pModule->fetch($sql,'h.HEX_CODE') as $rowListe) {?>
                        <tr>
                            <td class="aligncenter"><?php echo secureInput(extraireLibelle($rowListe['MOD_LIBELLE']))?></td>
                            <td class="aligncenter"><?php echo secureInput(intval($rowListe['NB_ACTION'])). ' ' . gettext('action'); echo intval($rowListe['NB_ACTION']) > 1 ? 's':''?></td>
                            <td class="aligncenter"><?php echo !empty($rowListe['LAST_MODIFICATION']) ? date('d/m/Y', intval($rowListe['LAST_MODIFICATION'])) : '-'?></td>
                        </tr>
                    <?php }?>
                </table>
                <?php }?>
            </fieldset>

             <?php
            $pAdmin = new Pagination('admin');
            $pAdmin->setOrderBy('LAST_MODIFICATION desc');
            $pAdmin->setFilter($filtre);
            $pAdmin->setCount("select count(distinct h.HIS_IDENTIFIANT) from HISTORIQUE_ADMIN h");

            $sql = "select
                        h.HIS_IDENTIFIANT,
                        h.HIS_TYPE,
                        SIT_LIBELLE,
                        trim(concat_ws(' ', UTI_PRENOM, UTI_NOM, HIS_UTILISATEUR)) as USER,
                        count(ID_HISTORIQUE_ADMIN) as NB_ACTION,
                        max(HIS_DATE) as LAST_MODIFICATION
                    from HISTORIQUE_ADMIN h
                    left join UTILISATEUR u on (u.ID_UTILISATEUR = h.HIS_IDENTIFIANT)
                      left join DD_SITE s on (s.SIT_CODE = h.HIS_IDENTIFIANT)";

            ?>
            <fieldset class="tab" >
                <legend>Configuration</legend>
                <p class="aligncenter"><?php echo $pAdmin->reglette()?></p>
                <?php if ($pAdmin->getNb() > 0) {?>
                <table class="liste">
                    <tr>
                        <th><?php echo gettext('Type')?></th>
                        <th><?php echo gettext('Libelle')?></th>
                        <th><?php echo gettext('nb_actions')?></th>
                        <th><?php echo gettext('Derniere modification')?></th>
                    </tr>
                    <?php foreach ($pAdmin->fetch($sql,'h.HIS_TYPE, h.HIS_IDENTIFIANT') as $rowListe) {?>
                        <tr>
                            <td class="aligncenter"><?php echo $rowListe['HIS_TYPE'] == 'SITE' ? gettext('Site') : gettext('utilisateur')?></td>
                            <td class="aligncenter"><?php echo $rowListe['HIS_TYPE'] == 'SITE' ? secureInput($rowListe['SIT_LIBELLE']) :  secureInput($rowListe['USER'])?></td>
                            <td class="aligncenter"><?php echo secureInput(intval($rowListe['NB_ACTION'])). ' ' . gettext('action'); echo intval($rowListe['NB_ACTION']) > 1 ? 's':''?></td>
                            <td class="aligncenter"><?php echo !empty($rowListe['LAST_MODIFICATION']) ? date('d/m/Y', intval($rowListe['LAST_MODIFICATION'])) : '-'?></td>
                        </tr>
                    <?php }?>
                </table>
                <?php }?>
            </fieldset>

            </div>
        </div>
    <?php include('../include/inc.bo_bandeau_basPopup.php') ?>
</body>
</html>
