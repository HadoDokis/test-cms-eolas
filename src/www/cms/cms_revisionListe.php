<?php
require '../include/inc.bo_init.php';
Utilisateur::checkConnected();

register_shutdown_function(array('CMS','loadHeader'));

require CLASS_DIR . 'class.db_page.php';
require CLASS_DIR . 'class.db_revision.php';
require CLASS_DIR . 'class.db_webotheque.php';

$pageNotFound  = false;
$oPageOriginel = new Page($_GET['idtf']);
if ($oPageOriginel->exist()) {
   $oPageOriginel->checkAuthorized();
   $oPageOriginel->lock();
} else {
    $pageNotFound = true;
}

$lPageRevisions = $oPageOriginel->getAllRevision();

if (count($lPageRevisions) > 0) {
    //determine revision to view
    $revIdselected = null;
    if (isset($_GET['rev']) && intval($_GET['rev']) > 0) {
        $revIdselected = intval($_GET['rev']);
    }

    if (is_null($revIdselected)) {
        $oPage = new Page($_GET['idtf'], 'OFF_');
        $isActualData = true;
    } else {
        $oRev = new Revision($revIdselected);
        $oPage = new Page_Revision($oRev);
        $isActualData = false;
    }

    //determine type of display
    $display = 'view';
    if (isset($_GET['details'])) {
        $display = 'details';
    }

    //Colonnage
    CMS::getCurrentSite()->setCurrentPage($oPage);

    $afficheColonneGauche = false;
    $afficheColonneDroite = false;
    $nb = 0;

    //Navigation droite
    $_aParentsID = $oPage->getParentsID(true);

    if (is_numeric($_aParentsID[0])) {
        $oPageMenuNiv1 = new Page($_aParentsID[0], CMS::$mode);
        $aChildrenID = $oPageMenuNiv1->getChildrenForMenu();
        $nb += sizeof($aChildrenID);
    }

    //Modules droite
    $nb += sizeof($oPage->getParagraphes('PAR_RIGHT'));
    if ($nb > 0) {
        $afficheColonneDroite = true;
    }
    CMS::getCurrentSite()->getCurrentPage()->setColumns($afficheColonneGauche, $afficheColonneDroite);

    if ($oPage->hasLeftColumn() && $oPage->hasRightColumn()) {
        $classColonnage = ' class="avecDeuxColonnes"';
    } elseif ($oPage->hasRightColumn()) {
        $classColonnage = ' class="avecColonneDroite"';
    } elseif ($oPage->hasLeftColumn()) {
        $classColonnage = ' class="avecColonneGauche"';
    } else {
        $classColonnage = '';
    } ?>

    <!DOCTYPE html>
    <html lang="fr">
        <head>
            <?php if ($display == 'view') {
                include '../include/inc.pseudo_enTete.php';
            } else {
                include '../include/inc.bo_enTete.php';

                if (CMS::getCurrentSite()->getField('GAB_CSS_PATH') != '') {
                    CMS::addLESS(SERVER_ROOT . CMS::getCurrentSite()->getField('GAB_CSS_PATH'), array('media' => 'screen, print'));
                }
                if (CMS::getCurrentSite()->getField('GBS_PATH') != '') {
                    CMS::addLESS(SERVER_ROOT . CMS::getCurrentSite()->getField('GBS_PATH'), array('media' => 'screen, print'));
                }
            } ?>
        </head>
        <body id="Acceuil" class="pseudo">
            <script>document.body.className="withJS pseudo"</script>
            <?php include('../include/inc.bo_bandeau_haut.php') ?>

            <div class="onglet" id="forRevision">
                <ul class="clearfix">
                    <li class="current<?php if(is_null($revIdselected)) echo ' selected';?>"><a href="/cms/cms_revisionListe.php?idtf=<?php echo $_GET['idtf']?>&<?php echo $display?>=1"><?php echo gettext('Version actuelle');?></a></li>
                    <li class="selection">
                        <select name="ID_REVISION" onchange="window.location.href='<?php echo SERVER_ROOT ?>cms/cms_revisionListe.php?idtf=<?php echo $_GET['idtf'] ?>&<?php echo $display ?>=1&amp;rev=' + this.value;">
                            <option value="">Choisir une révision</option>
                            <?php
                            $lPageRevisionsRev = array_reverse($lPageRevisions);
                            foreach ($lPageRevisionsRev as $uneRevision) {
                                $sqlRev = "select REVISION.REV_DATECREATION as REV_DATECREATION, REVISION_PAGE.* from REVISION inner join REVISION_PAGE on (REVISION.ID_REVISION = REVISION_PAGE.ID_REVISION) where REVISION.ID_REVISION = ".$uneRevision->getID();
                                $row = $dbh->query($sqlRev)->fetch(PDO::FETCH_ASSOC); ?>
                                <option value="<?php echo $uneRevision->getID() ?>" <?php if($uneRevision->getID() == $revIdselected) echo ' selected';?>><?php echo gettext('Revision_du') . ' ' . date('d/m/Y H:i', $row['REV_DATECREATION'])?></option>
                            <?php } ?>
                        </select>
                    </li>
                    <li class="suppr"><a onclick="if (confirm('<?php echo gettext('Etes-vous sur ?')?>')) window.location.href='/cms/cms_revisionSubmit.php?RemAllRev=<?php echo $_GET['idtf']?>'" href="#"><?php echo gettext('revision_delete_all')?></a></li>
                </ul>

                <div class="options_revision">
                    <?php if (!$isActualData) {?>
                    <a href="/cms/cms_revisionListe.php?idtf=<?php echo $_GET['idtf']?>&rev=<?php echo $revIdselected?>&view=1"><?php echo gettext('view_page')?></a>
                    <a href="/cms/cms_revisionListe.php?idtf=<?php echo $_GET['idtf']?>&rev=<?php echo $revIdselected?>&details=1"><?php echo gettext('view_details')?></a>
                    <?php } else {?>
                    <a href="/cms/cms_page.php?idtf=<?php echo $_GET['idtf']?>"><?php echo gettext('Proprietes')?></a>
                    <?php }?>

                    <?php if (!$isActualData) {?>
                    <a onclick="if (confirm('<?php echo gettext('Etes-vous sur ?')?>')) window.location.href='/cms/cms_revisionSubmit.php?DeleteRevision=<?php echo $revIdselected?>'" href="#"><?php echo gettext('revision_delete')?></a>
                    <a href="/cms/cms_revisionSubmit.php?Revert=<?php echo $revIdselected?>"><?php echo gettext('revision_revert')?></a>
                    <?php } else { ?>
                    <a href="/cms/cms_pseudo.php?idtf=<?php echo $_GET['idtf']?>&PFM=1"><?php echo gettext('edit_page')?></a>
                    <?php } ?>
                </div>

                <div class="info">
                    <h2><?php (!$isActualData)? printf(gettext('revision_pour_la_page_x'), date('d/m/Y H:i', $oRev->getField('REV_DATECREATION')), $oPage->getField('PAG_TITRE_MENU')) : printf(gettext('version_existante_pour_la_page_x'), $oPage->getField('PAG_TITRE_MENU'));?></h2>
                </div>
            </div>

            <div id="document"<?php echo $classColonnage?>>
                <?php
                switch ($display) {
                    case 'view':
                        include 'cms_revisionAffichagePseudo.php';
                    break;
                    case 'details':
                        include 'cms_revisionDetails.php';
                    break;
                    default:
                        include 'cms_revisionAffichagePseudo.php';
                    break;
                } ?>
            </div>
            <?php include ('../include/inc.bo_bandeau_bas.php')?>
        </body>
    </html>
<?php } else {
    setMsg('Pas de revision pour cette page');
    header('Location:' . SERVER_ROOT . 'cms/cms_page.php?idtf=' . $_GET['idtf']);
    exit ();
}
