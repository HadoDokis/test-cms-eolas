<?php
require '../include/inc.bo_init.php';
require '../include/lib.workflow.php';
require CLASS_DIR . 'class.db_page.php';
require CLASS_DIR . 'class.db_webotheque.php';
require CLASS_DIR . 'class.db_thematique.php';

Utilisateur::checkConnected();

$oPage = new Page($_GET['idtf']);
if ($oPage->exist()) {
    $oPage->checkAuthorized();
    $oPage->lock();
    $oPageParent = end($oPage->getParents());
    // verification heritage style parent
    $oPagesParentRev = array_reverse($oPage->getParents());
} else {
    $oPageParent = new Page($_GET['PAG_IDPERE']);
    $oPageParent->checkAuthorized();
    // verification heritage style parent
    $oPagesParentRev = array_reverse($oPageParent->getParents());
}
$row = $oPage->getFields();

$libStyle = '';
$libPageStyleHerite = '';
$libStyleDyn = '';
$libPageStyleDynHerite = '';

if (count($oPagesParentRev) > 0) {
    foreach ($oPagesParentRev as $oPageParentRev) {
        if ($oPageParentRev->getField('PAG_STYLEDEFAUTHERITABLE') == 1) {
            if (! is_null($oPageParentRev->getField('PSS_CODE'))) {
                $sqlStyle = "select PSS_LIBELLE from DD_PAGESTYLE where PSS_CODE = " . $dbh->quote($oPageParentRev->getField('PSS_CODE'));
                $libStyle = $dbh->query($sqlStyle)->fetchColumn();
            }
            $libPageStyleHerite = $oPageParentRev->getField('PAG_TITRE_MENU');
            break;
        }
    }

    foreach ($oPagesParentRev as $oPageParentRev) {
        if ($oPageParentRev->getField('PAG_STYLEPERSOHERITABLE') == 1) {
            if (is_numeric($oPageParentRev->getField('ID_STYLEDYNAMIQUE'))) {
                $sqlStyleDyn = "select STY_LIBELLE from STYLEDYNAMIQUE where ID_STYLEDYNAMIQUE = " . intval($oPageParentRev->getField('ID_STYLEDYNAMIQUE'));
                $libStyleDyn = $dbh->query($sqlStyleDyn)->fetchColumn();
            }
            $libPageStyleDynHerite = $oPageParentRev->getField('PAG_TITRE_MENU');
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <?php include('../include/inc.bo_enTete.php')?>
    <script src="<?php echo SERVER_ROOT ?>include/js/ajx_searchTag.js"></script>
    <script src="<?php echo SERVER_ROOT ?>include/js/ajx_liaison.js"></script>
    <script src="<?php echo SERVER_ROOT ?>include/js/onglet.js"></script>
    <script>
        ajaxLiaison.init('page', <?php echo $oPage->getID();?>);
    </script>
    <?php if (!Utilisateur::getConnected()->isSEO()) { ?>
    <script>
        function postControl_formCreation(oForm)
        {
            selectAll('SIT_CODE');
            selectAll('ID_GROUPE');
            selectAll('ID_THEMATIQUE');
            selectAll('ID_UTILISATEUR');

            <?php if ($oPage->exist()) { ?>
                var dateOnLine = document.getElementById('PAG_DATEONLINE').value.split("/");
                dateOnLine = new Date(dateOnLine[2], dateOnLine[1] - 1, dateOnLine[0]);
                var dateOffLine = document.getElementById('PAG_DATEOFFLINE').value.split("/");
                dateOffLine = new Date(dateOffLine[2], dateOffLine[1] - 1, dateOffLine[0]);
                <?php /* teste si 'compris' dans les dates du parent */
                if (!$oPage->isHome()) {
                    $rowTemp = $oPageParent->getFields();
                    if ($rowTemp['PAG_DATEONLINE_ON'] != '' && $rowTemp['PAG_DATEONLINE_ON'] >= $rowTemp['PAG_DATEONLINE'])
                        $rowTemp['PAG_DATEONLINE'] = $rowTemp['PAG_DATEONLINE_ON'];
                    if ($rowTemp['PAG_DATEOFFLINE_ON'] != '' && $rowTemp['PAG_DATEOFFLINE_ON'] < $rowTemp['PAG_DATEOFFLINE'])
                        $rowTemp['PAG_DATEOFFLINE'] = $rowTemp['PAG_DATEOFFLINE_ON'];?>
                    var dateOnLinePere = new Date(<?php echo date('Y', $rowTemp['PAG_DATEONLINE'])?>, <?php echo date('n', $rowTemp['PAG_DATEONLINE']) - 1?>, <?php echo date('j', $rowTemp['PAG_DATEONLINE'])?>);
                    var dateOffLinePere = new Date(<?php echo date('Y', $rowTemp['PAG_DATEOFFLINE'])?>, <?php echo date('n', $rowTemp['PAG_DATEOFFLINE']) - 1?>, <?php echo date('j', $rowTemp['PAG_DATEOFFLINE'])?>);
                    var pereHasOnlineVersion = <?php echo $oPageParent->hasOnlineVersion()?'true':'false' ?> ;
                    if (!pereHasOnlineVersion && dateOnLinePere > dateOnLine) {
                        alert("<?php echo gettext('Date de mise en ligne avant document parent')?> (n°<?php echo $rowTemp['ID_PAGE']?> - <?php echo date('d/m/Y',$rowTemp['PAG_DATEONLINE'])?>).\n<?php echo gettext('Veuillez ressaisir la date')?>.'");

                        return false;
                    }
                    if (dateOffLinePere < dateOffLine) {
                        alert("<?php echo gettext('Date de mise hors ligne apres document parent')?> (n°<?php echo $rowTemp['ID_PAGE']?> - <?php echo date('d/m/Y',$rowTemp['PAG_DATEOFFLINE'])?>).\n<?php echo gettext('Veuillez ressaisir la date')?>.");

                        return false;
                    }
                <?php } ?>

                /* teste si 'comprend' les dates des enfants */
                if (!document.getElementById('affecterDateFils').checked) {
                <?php
                $sql = "select PAG_DATEONLINE, ID_PAGE from OFF_PAGE where PAG_IDPERE=" . $oPage->getID() . " order by PAG_DATEONLINE";
                if ($rowTemp = $dbh->query($sql)->fetch(PDO :: FETCH_ASSOC)) { //il peut ne pas y avoir de fils
                    $sql = "select PAG_DATEONLINE_ON as PAG_DATEONLINE, ID_PAGE from OFF_PAGE where PAG_IDPERE=" . $oPage->getID() . " and PAG_DATEONLINE_ON<" . $rowTemp['PAG_DATEONLINE'] . " and PAG_DATEONLINE_ON<>'' order by PAG_DATEONLINE_ON";
                    if ($rowTempBis = $dbh->query($sql)->fetch(PDO :: FETCH_ASSOC)) {
                        $rowTemp = $rowTempBis;
                    } ?>
                    var dateOnLineFils = new Date(<?php echo date('Y', $rowTemp['PAG_DATEONLINE'])?>, <?php echo date('n', $rowTemp['PAG_DATEONLINE']) - 1?>, <?php echo date('j', $rowTemp['PAG_DATEONLINE'])?>);
                    if (!document.getElementById('affecterWorkflowFils').checked && dateOnLineFils < dateOnLine) {
                        alert("<?php echo gettext('Date de mise en ligne apres document enfant')?> (n°<?php echo $rowTemp['ID_PAGE']?> - <?php echo date('d/m/Y', $rowTemp['PAG_DATEONLINE'])?>).\n<?php echo gettext('Veuillez ressaisir la date')?>");

                        return false;
                    }
                    <?php $sql = "select PAG_DATEOFFLINE, ID_PAGE from OFF_PAGE where PAG_IDPERE=" . $oPage->getID() . " order by PAG_DATEOFFLINE desc";
                    $rowTemp = $dbh->query($sql)->fetch(PDO :: FETCH_ASSOC);
                    $sql = "select PAG_DATEOFFLINE_ON as PAG_DATEOFFLINE, ID_PAGE from OFF_PAGE where PAG_IDPERE=" . $oPage->getID() . " and PAG_DATEOFFLINE_ON>" . $rowTemp['PAG_DATEOFFLINE'] . " and PAG_DATEOFFLINE_ON<>'' order by PAG_DATEOFFLINE_ON desc";
                    if ($rowTempBis = $dbh->query($sql)->fetch(PDO :: FETCH_ASSOC)) {
                        $rowTemp = $rowTempBis;
                    } ?>
                    var dateOffLineFils = new Date(<?php echo date('Y', $rowTemp['PAG_DATEOFFLINE'])?>, <?php echo date('n', $rowTemp['PAG_DATEOFFLINE']) - 1?>, <?php echo date('j', $rowTemp['PAG_DATEOFFLINE'])?>);
                    if (!document.getElementById('affecterWorkflowFils').checked && dateOffLineFils > dateOffLine) {
                        alert("<?php echo gettext('Date de mise hors ligne avant document enfant')?> (n°<?php echo $rowTemp['ID_PAGE']?> - <?php echo date('d/m/Y', $rowTemp['PAG_DATEOFFLINE'])?>).\n<?php echo gettext('Veuillez ressaisir la date')?>");

                        return false;
                    }
                <?php
                } ?>
                }
           <?php } ?>

            var etatAffecte = document.getElementById('ID_WORKFLOW').options[document.getElementById('ID_WORKFLOW').selectedIndex].value;

            // Test de mise hors ligne
            if (etatAffecte && aID_WORKFLOW_PST_HORSLIGNE && (jQuery.inArray(etatAffecte, aID_WORKFLOW_PST_HORSLIGNE)>-1)) {
                var dateHorsLigne = document.getElementById('PAG_DATEOFFLINE').value;
                MD_Y=dateHorsLigne.substring(6);
                MD_D=dateHorsLigne.substring(0,2);
                MD_M=dateHorsLigne.substring(3,5);
                MD_M=MD_M-1; // Jan-Dec=00-11

                var dateHorsLigne=new Date(MD_Y, MD_M, MD_D);
                var today = new Date();
                if (dateHorsLigne > today) {
                    alert("<?php echo gettext('hors ligne date')?>");

                    return false;
                }
            }

            // Test de mise en ligne
            if (etatAffecte && aID_WORKFLOW_PST_ENLIGNE && (jQuery.inArray(etatAffecte, aID_WORKFLOW_PST_ENLIGNE)>-1)) {
                var dateEnligneLigne = document.getElementById('PAG_DATEONLINE').value;
                MD_Y=dateEnligneLigne.substring(6);
                MD_D=dateEnligneLigne.substring(0,2);
                MD_M=dateEnligneLigne.substring(3,5);
                MD_M=MD_M-1; // Jan-Dec=00-11

                var dateEnligneLigne=new Date(MD_Y, MD_M, MD_D);
                var today = new Date();
                if (dateEnligneLigne > today) {
                    alert("<?php echo gettext('en ligne date')?>");

                    return false;
                }
            }

            return true;
        }
    </script>
    <?php } ?>
</head>
<body>
<div id="document">
    <?php $aMenuKey = array('CTN', 'PAGE'); if (!$oPage->exist()) $aMenuKey[]='ADD'; include('../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">
            <div class="ariane">
                <?php $tempIsParent = false;
                if ($oPage->exist()) {
                    $oPageTemp = $oPage;
                } else {
                    $oPageTemp = $oPageParent;
                    $tempIsParent = true;
                }
                $isFirstAriane = true;
                foreach ($oPageTemp->getParents() as $oPageTempBis) {
                    echo ($oPageTempBis->checkAuthorized(false) && !$oPageTempBis->isLocked())
                        ? (($isFirstAriane)? '&nbsp;&nbsp;':'&nbsp;&gt;&nbsp;') . '<a href="cms_page.php?idtf=' . $oPageTempBis->getID() . '">' . secureInput($oPageTempBis->getField('PAG_TITRE_MENU')) . '</a>'
                        : secureInput($oPageTempBis->getField('PAG_TITRE_MENU'));
                    $isFirstAriane = false;
                }
                $isFirstAriane = true;
                if ($tempIsParent) {
                    echo ($oPageTemp->checkAuthorized(false) && !$oPageTemp->isLocked())
                        ? (($isFirstAriane)? '&nbsp;&nbsp;':'&nbsp;&gt;&nbsp;') . '<a href="cms_page.php?idtf=' . $oPageTemp->getID() . '">' . secureInput($oPageTemp->getField('PAG_TITRE_MENU')) . '</a>'
                        : secureInput($oPageTemp->getField('PAG_TITRE_MENU'));
                    $isFirstAriane = false;
                } ?>
            </div>
            <h2><?php echo ($row['PAG_TITRE_MENU'] != '') ? secureInput($row['PAG_TITRE_MENU']) . ' (n° '.$row['ID_PAGE'].')' : 'Nouvelle page'?></h2>
            <form method="post" action="cms_pageSubmit.php" id="formCreation" class="creation">
                <fieldset class="tab">
                    <legend>Propriétés</legend>
                    <?php if (!Utilisateur::getConnected()->isSEO()) { ?>
                    <fieldset>
                        <legend><?php echo gettext('Titres')?></legend>
                        <table>
                            <tr>
                                <th><label for="PAG_TITRE">Titre de la page (long)</label></th>
                                <td>
                                    <input name="PAG_TITRE" type="text" id="PAG_TITRE" value="<?php echo secureInput($row['PAG_TITRE'])?>" size="70" required>
                                    <script>document.getElementById('PAG_TITRE').focus();</script>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="PAG_TITRE_MENU">Titre dans le menu (court)</label></th>
                                <td>
                                    <input name="PAG_TITRE_MENU" type="text" id="PAG_TITRE_MENU" value="<?php echo secureInput($row['PAG_TITRE_MENU'])?>" size="40" required>
                                    <a href="#" onclick="document.getElementById('PAG_TITRE_MENU').value=document.getElementById('PAG_TITRE').value; return false;" class="btnAction"><?php echo gettext('Copier le titre')?></a>
                                </td>
                            </tr>
                            <tr>
                                <th>&nbsp;</th>
                                <td>
                                    <input type="checkbox" name="PAG_VISIBLE_MENU" id="PAG_VISIBLE_MENU" value="1"<?php if ($row['PAG_VISIBLE_MENU'] || $row['PAG_VISIBLE_MENU'] == '') echo ' checked'?>>
                                    <label for="PAG_VISIBLE_MENU">Faire apparaître cette page dans le menu</label>
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="PAG_TITLE">Info bulle (survol sur titre)</label>
                                    <div class="helper">Balise <cite>title</cite> initialisée lors de la génération du lien</div>
                                </th>
                                <td><input name="PAG_TITLE" type="text" id="PAG_TITLE" value="<?php echo secureInput($row['PAG_TITLE'])?>" size="50"></td>
                            </tr>
                        </table>
                    </fieldset>
                   <?php if ($oPage->exist()) { ?>
                    <fieldset>
                        <legend>Workflow</legend>
                        <table>
                            <tr>
                                <th><label><?php echo gettext('Etat courant')?></label></th>
                                <td><span class="<?php if($oPage->exist()) echo $oPage->getField('PST_CODE');?>"><?php echo secureInput(extraireLibelle($oPage->getStatut()))?></span></td>
                                <th><label for="PAG_DATEONLINE"><?php echo gettext('Date de mise en ligne')?></label></th>
                                <td>
                                    <input name="PAG_DATEONLINE" type="text" id="PAG_DATEONLINE" value="<?php echo !empty($row['PAG_DATEONLINE'])?date('d/m/Y', $row['PAG_DATEONLINE']):''?>" data-type="date" data-subtype="now" required>
                                    <?php if ($row['PAG_DATEONLINE_ON'] != '' && $row['PAG_DATEONLINE_ON'] != $row['PAG_DATEONLINE']) { ?>
                                    <span class="alert"><?php echo gettext('Date utilisee sur FO') . '&nbsp;' . date('d/m/Y', $row['PAG_DATEONLINE_ON']) ?></span>
                                    <?php } ?>
                                </td>
                            </tr>
                            <tr>
                                <th><label><?php echo gettext('Etat suivant')?></label></th>
                                <td>
                                <?php
                                $sql = "select PST_CODE_OUT, WORKFLOW.* from WORKFLOW
                                  where PST_CODE_IN=" . $dbh->quote($oPage->getField('PST_CODE')) . "
                                  and WKF_PROFIL regexp " . $dbh->quote('@' . implode('@|@', Utilisateur::getConnected()->getProfils($oPage->getID())) . '@') . "
                                  order by WKF_POIDS";
                                $aWORKFLOW = $dbh->query($sql)->fetchAll(PDO :: FETCH_ASSOC | PDO :: FETCH_GROUP | PDO :: FETCH_UNIQUE);
                                $aWORKFLOW_PST_ENLIGNE = $aID_WORKFLOW_PST_ENLIGNE = $aID_WORKFLOW_PST_HORSLIGNE = array();
                                if (sizeof($aWORKFLOW) > 0) { ?>
                                    <select name="ID_WORKFLOW" id="ID_WORKFLOW">
                                        <option value="">&nbsp;</option>
                                        <?php
                                        foreach ($aWORKFLOW as $PST_CODE_OUT => $rowTemp) {
                                            if ($PST_CODE_OUT == 'PST_ENLIGNE') {
                                                $aWORKFLOW_PST_ENLIGNE[] = 'val==' . $rowTemp['ID_WORKFLOW'];
                                                $aID_WORKFLOW_PST_ENLIGNE[] = $rowTemp['ID_WORKFLOW']; // Pour contrôle JS sur les dates
                                            } elseif ($PST_CODE_OUT == 'PST_HORSLIGNE') {
                                                $aID_WORKFLOW_PST_HORSLIGNE[] = $rowTemp['ID_WORKFLOW']; // Pour contrôle JS sur les dates
                                            }?>
                                        <option value="<?php echo $rowTemp['ID_WORKFLOW']?>"><?php echo secureInput(extraireLibelle($rowTemp['WKF_LIBELLE'])); if ($rowTemp['WKF_PRE_FONCTION'] != '' && !call_user_func($rowTemp['WKF_PRE_FONCTION'], $oPage)) echo ' *'?></option>
                                        <?php } ?>
                                    </select>
                                    <script>
                                    <?php if (!empty($aID_WORKFLOW_PST_ENLIGNE)) { ?>
                                        var aID_WORKFLOW_PST_ENLIGNE = new Array("<?php echo implode('","',$aID_WORKFLOW_PST_ENLIGNE);?>");
                                    <?php } ?>
                                    <?php if (!empty($aID_WORKFLOW_PST_HORSLIGNE)) {?>
                                        var aID_WORKFLOW_PST_HORSLIGNE = new Array("<?php echo implode('","',$aID_WORKFLOW_PST_HORSLIGNE);?>");
                                    <?php } ?>
                                    </script>
                                <?php } else { echo 'N/A'; } ?>
                                </td>
                                <th><label for="PAG_DATEOFFLINE"><?php echo gettext('Date de mise hors ligne')?></label></th>
                                <td>
                                    <input name="PAG_DATEOFFLINE" type="text" id="PAG_DATEOFFLINE" value="<?php echo !empty($row['PAG_DATEOFFLINE'])?date('d/m/Y', $row['PAG_DATEOFFLINE']):''?>" data-type="date" data-subtype="now later" required>
                                    <?php if ($row['PAG_DATEOFFLINE_ON'] != '' && $row['PAG_DATEOFFLINE_ON'] != $row['PAG_DATEOFFLINE']) { ?>
                                    <span class="alert"><?php echo gettext('Date utilisee sur FO') . '&nbsp;' . date('d/m/Y', $row['PAG_DATEOFFLINE_ON']) ?></span>
                                    <?php } ?>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="PAG_DATEMISEAJOUR"><?php echo gettext('Date de mise à jour')?></label></th>
                                <td><input name="PAG_DATEMISEAJOUR" type="text" id="PAG_DATEMISEAJOUR" value="<?php echo !empty($row['PAG_DATEMISEAJOUR'])?date('d/m/Y', $row['PAG_DATEMISEAJOUR']):''?>" data-type="date" data-subtype="now"></td>
                                <th>&nbsp;</th>
                                <td>
                                    <input type="checkbox" name="affecterDateFils" id="affecterDateFils" value="1">
                                    <label for="affecterDateFils"><?php echo gettext('affecte_ces_dates_aux_pages_filles')?></label>
                                    <br>
                                    <input type="checkbox" name="affecterWorkflowFils" id="affecterWorkflowFils" value="1">
                                    <label for="affecterWorkflowFils"><?php echo sprintf(gettext('AffecterEtatAPagesFilles'), extraireLibelle($oPage->getStatut()))?></label>
                                </td>
                            </tr>
                        </table>
                    </fieldset>
                    <?php } ?>
                <?php } // FIN : if (!Utilisateur::getConnected()->isSEO()) {} ?>
                </fieldset>

               <?php if (!Utilisateur::getConnected()->isSEO()) { ?>
                <fieldset class="tab">
                    <legend>Contenu et mise en page</legend>
                    <fieldset>
                        <legend>Accroche</legend>
                        <table>
                            <?php if (CMS::getCurrentSite()->hasModule(new Module('MOD_WEBOTHEQUE_IMAGE')) && CMS::getCurrentSite()->getField('SIT_PAGE_IMGACCROCHE')) { ?>
                            <tr>
                                <th><label>Image</label></th>
                                <td>
                                    <a href="../webotheque/web_choisirPopup.php?AJAX=WBT_IMAGE&amp;WBT_CODE=WBT_IMAGE" class="action popup">Ajouter / Remplacer</a>
                                    <div id="WBT_IMAGE" class="ajax liaison_webotheque onlyOne hasText:Alternative"></div>
                                </td>
                            </tr>
                            <?php } ?>
                            <?php if (CMS::getCurrentSite()->getField('SIT_PAGE_TXTACCROCHE')) { ?>
                            <tr>
                                <th><label for="PAG_ACCROCHE">Texte</label></th>
                                <td><textarea name="PAG_ACCROCHE" cols="60" rows="8" id="PAG_ACCROCHE"><?php echo secureInput($row['PAG_ACCROCHE'])?></textarea></td>
                            </tr>
                            <?php } ?>
                        </table>
                    </fieldset>
                    <fieldset>
                        <legend>Mise en page</legend>
                        <table>
                            <tr>
                                <th><label for="PSS_CODE">Feuille de style</label></th>
                                <td>
                                    <?php
                                    $sql = "select * from DD_PAGESTYLE
                                        where GBS_CODE = " . $dbh->quote(CMS::getCurrentSite()->getField('GBS_CODE')) . "
                                        order by PSS_LIBELLE";
                                    $aStyles = $dbh->query($sql)->fetchAll(PDO :: FETCH_ASSOC);
                                    if (!$oPage->exist()) {
                                        $row['PSS_CODE'] = $oPageParent->getField('PSS_CODE');
                                    } ?>
                                    <select name="PSS_CODE" id="PSS_CODE">
                                        <option value="">Feuille de style par défaut</option>
                                        <?php foreach ($aStyles as $rowTemp) {?>
                                            <option value="<?php echo $rowTemp['PSS_CODE']?>"<?php if($row['PSS_CODE'] == $rowTemp['PSS_CODE']) echo ' selected';?>><?php echo secureInput(extraireLibelle($rowTemp['PSS_LIBELLE']))?></option>
                                        <?php } ?>
                                    </select>
                                    <?php if (!empty($libPageStyleHerite)) {?>
                                    <?php printf(gettext('Style %s herite de la page mere %s'), $libStyle, $libPageStyleHerite)?>
                                    <?php } ?>
                                    <br>
                                    <input type="checkbox" name="affecterStyleFils" id="affecterStyleFils" value="1"<?php if ($row['PAG_STYLEDEFAUTHERITABLE']) echo ' checked'?>>
                                    <label for="affecterStyleFils">Appliquer cette feuille de style aux pages filles</label>
                                </td>
                                <th><label>Colonne</label></th>
                                <td>
                                    <input type="checkbox" name="PAG_MASQUERGAUCHE" id="PAG_MASQUERGAUCHE" value="1"<?php if ($row['PAG_MASQUERGAUCHE']) echo ' checked'?>>
                                    <label for="PAG_MASQUERGAUCHE">Masquer la colonne de gauche</label>
                                    <br>
                                    <input type="checkbox" name="PAG_MASQUERDROITE" id="PAG_MASQUERDROITE" value="1"<?php if ($row['PAG_MASQUERDROITE']) echo ' checked'?>>
                                    <label for="PAG_MASQUERDROITE">Masquer la colonne de droite</label>
                                </td>
                           </tr>
                                <?php
                                $sql = "select * from STYLEDYNAMIQUE where SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID()) . " order by STY_LIBELLE";
                                $aStyles = $dbh->query($sql)->fetchAll(PDO :: FETCH_ASSOC);
                                if (count($aStyles) > 0) {
                                    if (!$oPage->exist()) {
                                        $row['ID_STYLEDYNAMIQUE'] = $oPageParent->getField('ID_STYLEDYNAMIQUE');
                                    } ?>
                            <tr>
                                <th><label<?php if (count($aStyles) > 0) ?> for="ID_STYLEDYNAMIQUE">Feuille de style perso</label></th>
                                <td>
                                    <select name="ID_STYLEDYNAMIQUE" id="ID_STYLEDYNAMIQUE">
                                        <option value="">&nbsp;</option>
                                        <?php foreach ($aStyles as $rowTemp) {?>
                                        <option value="<?php echo $rowTemp['ID_STYLEDYNAMIQUE']?>"<?php if($row['ID_STYLEDYNAMIQUE'] == $rowTemp['ID_STYLEDYNAMIQUE']) echo ' selected';?>><?php echo secureInput($rowTemp['STY_LIBELLE'])?></option>
                                        <?php } ?>
                                    </select>
                                    <?php if (!empty($libPageStyleDynHerite)) { ?>
                                    <?php printf(gettext('Style %s herite de la page mere %s'), $libStyleDyn, $libPageStyleDynHerite)?>
                                    <?php } ?>
                                    <br>
                                    <input type="checkbox" name="affecterStylePersoFils" id="affecterStylePersoFils" value="1"<?php if ($row['PAG_STYLEPERSOHERITABLE'] == '1') echo ' checked'?>>
                                    <label for="affecterStylePersoFils">Affecter ce style dynamique aux pages filles</label>
                                </td>
                            </tr>
                                <?php } ?>
                           </table>
                    </fieldset>
                    <?php if (Thematique::thematiquesExist() && CMS::getCurrentSite()->hasModule(new Module('MOD_THEMATIQUE'))) { ?>
                    <fieldset>
                        <legend>Thématiques</legend>
                        <table>
                            <tr>
                                <th><label>&nbsp;</label></th>
                                <td>
                                    <table class="selection">
                                        <tr>
                                            <th><?php echo gettext('Affecte(s)')?></th>
                                            <th>&nbsp;</th>
                                            <th><?php echo gettext('Disponible(s)')?></th>
                                        </tr>
                                        <tr>
                                            <td>
                                                <select name="ID_THEMATIQUE[]" id="ID_THEMATIQUE" size="10" multiple ondblclick="DeplaceCritere(document.getElementById('ID_THEMATIQUE'), document.getElementById('ID_THEMATIQUE_ALL'));">
                                                    <?php $sqlFilterSite = Thematique::getSharedSQLFilter();
                                                    $notIn = 'not in (-1';
                                                    if ($oPage->exist()) {
                                                        $sql = "select THEMATIQUE.* from THEMATIQUE
                                                        inner join LIAISON_THEMATIQUE on (THEMATIQUE.ID_THEMATIQUE = LIAISON_THEMATIQUE.ID_THEMATIQUE)
                                                        where LIA_CODE = 'OFF_PAGE'
                                                        and ID_LIAISON=" . $oPage->getID() . "
                                                        and ".$sqlFilterSite." order by THE_LIBELLE";

                                                        foreach ($dbh->query($sql) as $rowTemp) {
                                                            $notIn .= ',' . $rowTemp['ID_THEMATIQUE']; ?>
                                                            <option value="<?php echo $rowTemp['ID_THEMATIQUE'] ?>">
                                                                <?php echo secureInput($rowTemp['THE_LIBELLE']) ?>
                                                            </option>
                                                        <?php }
                                                    }
                                                    $notIn .= ')'; ?>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="button" name="Button" value="&lt;&lt;" onclick="DeplaceCritere(document.getElementById('ID_THEMATIQUE_ALL'), document.getElementById('ID_THEMATIQUE'));">
                                                <input type="button" name="Button2" value="&gt;&gt;" onclick="DeplaceCritere(document.getElementById('ID_THEMATIQUE'), document.getElementById('ID_THEMATIQUE_ALL'));">
                                            </td>
                                            <td>
                                                <?php $sql = "select distinct(THEMATIQUE.ID_THEMATIQUE), THEMATIQUE.THE_LIBELLE from THEMATIQUE
                                                              where THEMATIQUE.ID_THEMATIQUE $notIn
                                                              and " . $sqlFilterSite . "
                                                              order by THE_LIBELLE"; ?>
                                                <select name="ID_THEMATIQUE_ALL[]" id="ID_THEMATIQUE_ALL" size="10" multiple ondblclick="DeplaceCritere(document.getElementById('ID_THEMATIQUE_ALL'), document.getElementById('ID_THEMATIQUE'));">
                                                <?php foreach ($dbh->query($sql) as $rowTemp) {?>
                                                    <option value="<?php echo $rowTemp['ID_THEMATIQUE'] ?>">
                                                        <?php echo secureInput($rowTemp['THE_LIBELLE']) ?>
                                                    </option>
                                                <?php } ?>
                                                </select>
                                            </td>
                                        </tr>
                                    </table>
                                 </td>
                            </tr>
                        </table>
                    </fieldset>
                    <?php } ?>
                </fieldset>
                <?php } ?>

                <?php if (!Utilisateur::getConnected()->isSEO()) { ?>
                <fieldset class="tab">
                    <legend>Comportement</legend>
                    <fieldset>
                        <legend>Comportement</legend>
                        <table>
                            <tr>
                                <th>
                                    <label for="PGS_CODE" class="labelTitle"><?php echo gettext('Page speciale')?></label>
                                    <div class="helper">Permet d'initaliser un comportement prédéfini :
                                    <ul>
                                        <li>dans le noyau CMS.Eolas : page de résultats de recherche, authentification sur les données sécurisées, formulaire de dépôt d'abus,...  </li>
                                        <li>dans le cadre du projet : page d'atterrissage des contenus de détail d'actualités, charte de confidentialité,... </li>
                                    </ul>
                                    <p> <strong>Chaque page spéciale est unique et cette propriété ne peut être utilisée pour une page seulement</strong></p>
                                    </div>
                                </th>
                                <td>
                                    <select name="PGS_CODE" id="PGS_CODE">
                                        <option value="">non</option>
                                        <?php $MOD_LIBELLE = '';
                                        $sql = "select DD_PAGESPECIALE.*, MOD_LIBELLE from DD_PAGESPECIALE
                                                inner join DD_MODULE on DD_PAGESPECIALE.MOD_CODE=DD_MODULE.MOD_CODE
                                                inner join SITE_MODULE on DD_PAGESPECIALE.MOD_CODE=SITE_MODULE.MOD_CODE
                                                where SITE_MODULE.SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID()) . "
                                                and PGS_CODE not in (select distinct(PGS_CODE) from OFF_PAGE where SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID()) . "
                                                and ID_PAGE<>" . $oPage->getID() . "
                                                and PGS_CODE is not null)
                                                order by MOD_LIBELLE, PGS_LIBELLE";
                                        foreach ($dbh->query($sql) as $rowTemp) {
                                            if ($MOD_LIBELLE != $rowTemp['MOD_LIBELLE']) {
                                                if ($MOD_LIBELLE != '') {
                                                    echo '</optgroup>';
                                                }
                                                $MOD_LIBELLE = $rowTemp['MOD_LIBELLE'];?>
                                                <optgroup label="<?php echo secureInput(extraireLibelle($MOD_LIBELLE))?>">
                                            <?php } ?>
                                            <option value="<?php echo $rowTemp['PGS_CODE']?>"<?php if($row['PGS_CODE'] == $rowTemp['PGS_CODE']) echo ' selected'?>><?php echo secureInput(extraireLibelle($rowTemp['PGS_LIBELLE']))?></option>
                                        <?php } ?>
                                        </optgroup>
                                    </select>
                                </td>
                                <?php if (CMS::getCurrentSite()->getField('SIT_PAGE_CACHE')) { ?>
                                <th>&nbsp;</th>
                                <td>
                                    <input type="checkbox" name="PAG_CACHE" id="PAG_CACHE" value="1"<?php if ($row['PAG_CACHE'] || $row['PAG_CACHE'] == '') echo ' checked'?>>
                                    <label for="PAG_CACHE">Mettre la page en cache</label>
                                </td>
                                <?php } ?>
                            </tr>
                            <?php if (CMS::getCurrentSite()->getField('SIT_PAGE_HTTPS') || CMS::getCurrentSite()->hasModule(new Module('MOD_COMMENTAIRE'))) { ?>
                            <tr>
                                <?php if (CMS::getCurrentSite()->getField('SIT_PAGE_HTTPS')) { ?>
                                <th>&nbsp;</th>
                                <td>
                                    <input type="checkbox" id="PAG_HTTPS" name="PAG_HTTPS" value="1"<?php if ($row['PAG_HTTPS']) echo ' checked'?>>
                                    <label for="PAG_HTTPS"><?php echo gettext('Acces securise (HTTPS)') ?></label>
                                </td>
                                <?php } ?>
                                <?php if (CMS::getCurrentSite()->hasModule(new Module('MOD_COMMENTAIRE'))) { ?>
                                <th>&nbsp;</th>
                                <td>
                                    <input type="checkbox" id="PAG_COMMENTAIREACTIF" name="PAG_COMMENTAIREACTIF" value="1"<?php if ($row['PAG_COMMENTAIREACTIF']) echo ' checked'?>>
                                    <label for="PAG_COMMENTAIREACTIF"><?php echo gettext('com_autoriser_depot_commentaire') ?></label>
                                </td>
                                <?php } ?>
                            </tr>
                            <?php } ?>
                            <tr>
                                <th>
                                    <label>Redirection interne</label>
                                    <div class="helper">Redirection utilisée lors de la contruction du lien dans les menus de navigation</div>
                                </th>
                                <td>
                                    <a href="../cms/cms_choisirLienInternePopup.php?AJAX=ID_PAGE:REDIRECT" class="action popup">Ajouter / Remplacer</a>
                                    <div id="ID_PAGE:REDIRECT" class="ajax liaison_page onlyOne"></div>
                                </td>
                                <?php if (CMS::getCurrentSite()->hasModule(new Module('MOD_WEBOTHEQUE_LIENEXTERNE'))) { ?>
                                <th>
                                    <label>Redirection externe</label>
                                    <div class="helper">Redirection utilisée lors de la contruction du lien dans les menus de navigation</div>
                                </th>
                                <td>
                                    <a href="../webotheque/web_choisirPopup.php?AJAX=WBT_LIENEXTERNE&amp;WBT_CODE=WBT_LIENEXTERNE" class="action popup">Ajouter / Remplacer</a>
                                    <div id="WBT_LIENEXTERNE" class="ajax liaison_webotheque onlyOne"></div>
                                </td>
                                <?php } ?>
                            </tr>
                        </table>
                    </fieldset>
                </fieldset>
                <?php } ?>

                <?php if (!Utilisateur::getConnected()->isSEO() && CMS::getCurrentSite()->hasModule(new Module('MOD_RECHERCHE'))) { ?>
                <fieldset class="tab">
                    <legend>Tags / Moteur de recherche</legend>
                    <fieldset>
                        <legend>Tags / Moteur de recherche <span class="helper">Initialisation des balises <cite>meta keyword</cite></span></legend>
                        <table>
                            <tr>
                                <th>&nbsp;</th>
                                <td>
                                    <input type="checkbox" name="PAG_EXCLURECHERCHE" id="PAG_EXCLURECHERCHE" value="1"<?php if ($row['PAG_EXCLURECHERCHE']) echo ' checked'?>>
                                    <label for="PAG_EXCLURECHERCHE">Exclure la page des résultats du moteur de recherche interne</label>
                                </td>
                            </tr>
                            <?php for ($i = 1; $i < 6; $i++) {?>
                            <tr>
                                <th><label for="PAG_MOTCLE<?php echo $i?>"><?php echo gettext('Mot cle') . ' ' . $i ?></label></th>
                                <td><input name="PAG_MOTCLE<?php echo $i?>" type="text" id="PAG_MOTCLE<?php echo $i?>" value="<?php echo secureInput($row['PAG_MOTCLE' . $i])?>" size="40"></td>
                            </tr>
                            <?php } ?>
                        </table>
                    </fieldset>
                </fieldset>
                <?php } ?>

                <?php if (!Utilisateur::getConnected()->isSEO() && CMS::getCurrentSite()->hasModule(new Module('MOD_EXTRANET'))) { ?>
                <fieldset class="tab">
                    <legend>Droits d'accès</legend>
                    <fieldset>
                        <legend>Droits d'accès <span class="helper">Affectation de droits spécificiques selon un ou plusieurs groupes d'utilisateurs</span></legend>
                        <table>
                            <tr>
                                <td class="aligncenter">
                                    <table class="selection" style="margin:auto">
                                        <tr>
                                            <th><?php echo gettext('Affecte(s)')?></th>
                                            <td>&nbsp;</td>
                                            <th><?php echo gettext('Disponible(s)')?></th>
                                        </tr>
                                        <tr>
                                            <td>
                                                <select name="ID_GROUPE[]" id="ID_GROUPE" size="6" multiple ondblclick="DeplaceCritere(document.getElementById('ID_GROUPE'), document.getElementById('ID_GROUPE_ALL'));">
                                                <?php if ($oPage->exist()) {
                                                    $sql = "select GROUPE.* from GROUPE inner join GROUPE_OFF_PAGE using (ID_GROUPE)
                                                    where ID_PAGE=" . $oPage->getID() . " order by GRP_LIBELLE";
                                                } else {
                                                    //nouvelle page : on récupère les groupes du parent
                                                    $sql = "select GROUPE.ID_GROUPE, GRP_LIBELLE from GROUPE inner join GROUPE_OFF_PAGE using (ID_GROUPE)
                                                    where ID_PAGE=" . intval($_GET['PAG_IDPERE']) . "
                                                    order by GRP_LIBELLE";
                                                }
                                                $notIn = 'not in (-1';
                                                foreach ($dbh->query($sql) as $rowTemp) {
                                                    $notIn .= ',' . $rowTemp['ID_GROUPE']; ?>
                                                    <option value="<?php echo $rowTemp['ID_GROUPE']?>">
                                                    <?php echo secureInput($rowTemp['GRP_LIBELLE'])?>
                                                    </option>
                                                <?php }
                                                $notIn .= ')'; ?>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="button" name="Button" value="&lt;&lt;" onclick="DeplaceCritere(document.getElementById('ID_GROUPE_ALL'), document.getElementById('ID_GROUPE'));">
                                                <input type="button" name="Button2" value="&gt;&gt;" onclick="DeplaceCritere(document.getElementById('ID_GROUPE'), document.getElementById('ID_GROUPE_ALL'));">
                                            </td>
                                            <td>
                                                <select name="ID_GROUPE_ALL[]" id="ID_GROUPE_ALL" size="6" multiple ondblclick="DeplaceCritere(document.getElementById('ID_GROUPE_ALL'), document.getElementById('ID_GROUPE'));">
                                                <?php
                                                $sql = "select distinct(GROUPE.ID_GROUPE), GRP_LIBELLE from GROUPE
                                                        left join GROUPE_SITE using(ID_GROUPE)
                                                        where GROUPE.ID_GROUPE $notIn and (GROUPE.SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID()) . " or GROUPE_SITE.SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID()) . ")
                                                        order by GRP_LIBELLE";
                                                foreach ($dbh->query($sql) as $rowTemp) {?>
                                                    <option value="<?php echo $rowTemp['ID_GROUPE']?>">
                                                    <?php echo secureInput($rowTemp['GRP_LIBELLE'])?>
                                                    </option>
                                                <?php } ?>
                                                </select>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                <td>
                                    <input type="radio" name="affectationFils" id="affectationFils_0" value="0" checked>
                                    <label for="affectationFils_0"><?php echo gettext('GroupeNon')?></label>
                                    <br>
                                    <input type="radio" name="affectationFils" id="affectationFils_REPLACE" value="REPLACE">
                                    <label for="affectationFils_REPLACE"><?php echo gettext('GroupeRemplacer')?></label>
                                    <br>
                                    <input type="radio" name="affectationFils" id="affectationFils_ADD" value="ADD">
                                    <label for="affectationFils_ADD"><?php echo gettext('GroupeAjouter')?></label>
                                </td>
                            </tr>
                        </table>
                    </fieldset>
                </fieldset>
                <?php } ?>

                <fieldset class="tab">
                    <legend>Référencement</legend>
                    <fieldset>
                        <legend>Référencement</legend>
                        <table>
                            <tr>
                                <th><label for="PAG_TITRE_REFERENCEMENT">Title</label></th>
                                <td><input name="PAG_TITRE_REFERENCEMENT" type="text" id="PAG_TITRE_REFERENCEMENT" value="<?php echo secureInput($row['PAG_TITRE_REFERENCEMENT'])?>" size="45" data-maxchar="70"></td>
                            </tr>
                            <tr>
                                <th><label for="PAG_METADESCRIPTION">Metadescription</label></th>
                                <td><textarea name="PAG_METADESCRIPTION" cols="50" rows="9" id="PAG_METADESCRIPTION" data-maxchar="200"><?php echo secureInput($row['PAG_METADESCRIPTION'])?></textarea></td>
                                <th>
                                    <label for="PAG_HEAD">Code suplémentaire</label>
                                    <div class="helper">
                                        Ce code est ajouté avant la balise &lt;/head&gt;<br>
                                        Vous pouvez ajouter des balises &lt;meta&gt; ou du javascript.<br>
                                        Dans le cas d'utilisation de JS, penser à l'encapsuler dans les balises &lt;script&gt;...&lt;/script&gt;
                                    </div>
                                </th>
                                <td><textarea name="PAG_HEAD" cols="60" rows="10" id="PAG_HEAD"><?php echo secureInput($row['PAG_HEAD'])?></textarea></td>
                            </tr>
                            <tr>
                                <th><label for="PAG_URLREWRITING">URL principale</label></th>
                                <td><?php echo $oPage->getID() ?>-<input name="PAG_URLREWRITING" type="text" id="PAG_URLREWRITING" value="<?php echo secureInput($row['PAG_URLREWRITING'])?>" size="45">.htm</td>
                                <th>
                                    <label for="URA_LIBELLE">URLs alternatives</label>
                                    <div class="helper">Définition d'une ou plusieurs URL pour accèder à cette page.
                                        <br>Cela permet :
                                        <ul>
                                            <li>l'utilisation d'une URL de communication simplifiée</li>
                                            <li>d'effectuer une redirection depuis une URL qui n'existe plus</li>
                                        </ul>
                                    </div>
                                </th>
                                <td>
                                    <input type="text" id="URA_LIBELLE" name="URA_LIBELLE[]" size="45">
                                    <?php $sql = "select URA_LIBELLE from URLALTERNATIVE where ID_PAGE = " . $oPage->getID() . " order by URA_LIBELLE";
                                    foreach ($dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN) as $URA_LIBELLE) { ?>
                                        <br>
                                        <input type="text" name="URA_LIBELLE[]" value="<?php echo secureInput($URA_LIBELLE) ?>" size="45">
                                    <?php } ?>
                                </td>
                            </tr>
                            <?php if (!Utilisateur::getConnected()->isSEO()) { ?>
                            <tr>
                                <th>
                                    <label>Page canonique</label>
                                    <span class="helper">L'URL canonique est une balise permettant d'indiquer à un moteur de recherche, lorsque plusieurs contenus ont des URLs différentes, mais des contenus identiques, quelle est l'URL principale à prendre en compte pour l’indexation, évitant ainsi de laisser le soin au moteur de choisir à votre place</span>
                                </th>
                                <td>
                                    <a href="<?php echo SERVER_ROOT?>cms/cms_choisirLienInternePopup.php?AJAX=ID_PAGE:CANONICAL" class="action popup">Ajouter / Remplacer</a>
                                    <div id="ID_PAGE:CANONICAL" class="ajax liaison_page onlyOne"></div>
                                </td>
                            </tr>
                            <?php } ?>
                        </table>
                    </fieldset>
                    <fieldset>
                        <legend>Sitemap</legend>
                        <table>
                            <tr>
                                <th>&nbsp;</th>
                                <td colspan="3">
                                    <input type="checkbox" name="PAG_NOINDEX" id="PAG_NOINDEX" value="1"<?php if ($row['PAG_NOINDEX']) echo ' checked'?>>
                                    <label for="PAG_NOINDEX">Exclure la page du sitemap et ne pas l'indexer (noindex)</label>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="PAG_GOOGLEFREQUENCE"><?php echo gettext('Frequence')?></label></th>
                                <td style="width:30%">
                                    <select name="PAG_GOOGLEFREQUENCE" id="PAG_GOOGLEFREQUENCE">
                                        <option value="always"<?php if ($row['PAG_GOOGLEFREQUENCE']=='always') echo ' selected'?>>always</option>
                                        <option value="hourly"<?php if ($row['PAG_GOOGLEFREQUENCE']=='hourly') echo ' selected'?>>hourly</option>
                                        <option value="daily"<?php if ($row['PAG_GOOGLEFREQUENCE']=='daily') echo ' selected'?>>daily</option>
                                        <option value="weekly"<?php if ($row['PAG_GOOGLEFREQUENCE']=='weekly') echo ' selected'?>>weekly</option>
                                        <option value="monthly"<?php if ($row['PAG_GOOGLEFREQUENCE']=='monthly') echo ' selected'?>>monthly</option>
                                        <option value="yearly"<?php if ($row['PAG_GOOGLEFREQUENCE']=='yearly') echo ' selected'?>>yearly</option>
                                        <option value="never"<?php if ($row['PAG_GOOGLEFREQUENCE']=='never') echo ' selected'?>>never</option>
                                    </select>
                                </td>
                                <th><label for="PAG_GOOGLEPRIORITE"><?php echo gettext('Priorite')?></label></th>
                                <td>
                                    <select name="PAG_GOOGLEPRIORITE" id="PAG_GOOGLEPRIORITE">
                                        <option value="0.0"<?php if ($row['PAG_GOOGLEPRIORITE']=='0.0') echo ' selected'?>>0.0</option>
                                        <option value="0.1"<?php if ($row['PAG_GOOGLEPRIORITE']=='0.1') echo ' selected'?>>0.1</option>
                                        <option value="0.2"<?php if ($row['PAG_GOOGLEPRIORITE']=='0.2') echo ' selected'?>>0.2</option>
                                        <option value="0.3"<?php if ($row['PAG_GOOGLEPRIORITE']=='0.3') echo ' selected'?>>0.3</option>
                                        <option value="0.4"<?php if ($row['PAG_GOOGLEPRIORITE']=='0.4') echo ' selected'?>>0.4</option>
                                        <option value="0.5"<?php if ($row['PAG_GOOGLEPRIORITE']=='0.5') echo ' selected'?>>0.5</option>
                                        <option value="0.6"<?php if ($row['PAG_GOOGLEPRIORITE']=='0.6') echo ' selected'?>>0.6</option>
                                        <option value="0.7"<?php if ($row['PAG_GOOGLEPRIORITE']=='0.7') echo ' selected'?>>0.7</option>
                                        <option value="0.8"<?php if ($row['PAG_GOOGLEPRIORITE']=='0.8') echo ' selected'?>>0.8</option>
                                        <option value="0.9"<?php if ($row['PAG_GOOGLEPRIORITE']=='0.9') echo ' selected'?>>0.9</option>
                                        <option value="1.0"<?php if ($row['PAG_GOOGLEPRIORITE']=='1.0') echo ' selected'?>>1.0</option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </fieldset>
                </fieldset>

                <?php if ($oPage->exist() && !Utilisateur::getConnected()->isSEO()) { ?>
                <fieldset class="tab">
                    <legend>Références à la page</legend>
                        <?php
                        $sql = "select distinct(OFF_PAGE.ID_PAGE) as ID_PAGE, (OFF_PARAGRAPHE.ID_PARAGRAPHE) as ID_PARAGRAPHE, PAG_TITRE_MENU, 'PARAGRAPHE' as TYPE, OFF_PARAGRAPHE.TPL_CODE as TPLTYPE, OFF_PARAGRAPHE.PAR_TPL_IDENTIFIANT as PAR_TPL_IDENTIFIANT
                                from OFF_PAGE
                                inner join OFF_PARAGRAPHE on OFF_PAGE.ID_PAGE=OFF_PARAGRAPHE.ID_PAGE
                                inner join LIAISON_PAGE on OFF_PARAGRAPHE.ID_PARAGRAPHE=LIAISON_PAGE.ID_LIAISON and LIA_CODE='OFF_PARAGRAPHE'
                                where LIAISON_PAGE.ID_PAGE=" . $oPage->getID() . "
                                    union
                            select distinct(OFF_PAGE.ID_PAGE) as ID_PAGE, '' as ID_PARAGRAPHE, PAG_TITRE_MENU, 'PAGE' as TYPE, '' as TPLTYPE, '' as PAR_TPL_IDENTIFIANT
                                from OFF_PAGE
                                inner join LIAISON_PAGE on OFF_PAGE.ID_PAGE=LIAISON_PAGE.ID_LIAISON and LIA_CODE='OFF_PAGE'
                                where LIAISON_PAGE.ID_PAGE=" . $oPage->getID() . "
                                order by PAG_TITRE_MENU";
                        $aReference = $dbh->query($sql)->fetchAll(PDO :: FETCH_ASSOC);
                        if (sizeof($aReference) > 0) {?>
                    <fieldset>
                        <legend>Sur l'espace de contribution</legend>
                        <table class="liste">
                            <tr>
                                <th><?php echo gettext('Numero')?></th>
                                <th><?php echo gettext('Titre')?></th>
                            </tr>
                            <?php foreach ($aReference as $row) { ?>
                            <tr>
                                <td class="alignright"><?php echo secureInput($row['TYPE'] . '[' . (($row['TYPE'] == 'PAGE')? $row['ID_PAGE'] : (($row['TPLTYPE'] == 'TPL_PARTAGE')? 'Partagé(' . $row['PAR_TPL_IDENTIFIANT'] . ')' : $row['ID_PARAGRAPHE'])) . ']');?></td>
                                <td><?php echo secureInput($row['PAG_TITRE_MENU'])?></td>
                            </tr>
                            <?php } ?>
                        </table>
                    </fieldset>
                        <?php } ?>

                        <?php
                        $sql = "select distinct(ON_PAGE.ID_PAGE) as ID_PAGE, (ON_PARAGRAPHE.ID_PARAGRAPHE) as ID_PARAGRAPHE, PAG_TITRE_MENU, 'PARAGRAPHE' as TYPE, ON_PARAGRAPHE.TPL_CODE as TPLTYPE, ON_PARAGRAPHE.PAR_TPL_IDENTIFIANT as PAR_TPL_IDENTIFIANT
                                from ON_PAGE
                                inner join ON_PARAGRAPHE on ON_PAGE.ID_PAGE=ON_PARAGRAPHE.ID_PAGE
                                inner join LIAISON_PAGE on ON_PARAGRAPHE.ID_PARAGRAPHE=LIAISON_PAGE.ID_LIAISON and LIA_CODE='ON_PARAGRAPHE'
                                where LIAISON_PAGE.ID_PAGE=" . $oPage->getID() . "
                                    union
                            select distinct(ON_PAGE.ID_PAGE) as ID_PAGE, '' as ID_PARAGRAPHE,
                                PAG_TITRE_MENU, 'PAGE' as TYPE, '' as TPLTYPE, '' as PAR_TPL_IDENTIFIANT
                                from ON_PAGE
                                inner join LIAISON_PAGE on ON_PAGE.ID_PAGE=LIAISON_PAGE.ID_LIAISON and LIA_CODE='ON_PAGE'
                                where LIAISON_PAGE.ID_PAGE=" . $oPage->getID() . "
                                order by PAG_TITRE_MENU";
                        $aReference = $dbh->query($sql)->fetchAll(PDO :: FETCH_ASSOC);
                        if (sizeof($aReference) > 0) {?>
                    <fieldset>
                        <legend>Sur le site</legend>
                        <table class="liste">
                            <tr>
                                <th><?php echo gettext('Numero')?></th>
                                <th><?php echo gettext('Titre')?></th>
                            </tr>
                            <?php foreach ($aReference as $row) { ?>
                            <tr>
                                <td class="alignright"><?php echo $row['TYPE'] . '[' . (($row['TYPE'] == 'PAGE')? $row['ID_PAGE'] : (($row['TPLTYPE'] == 'TPL_PARTAGE')? 'Partagé(' . $row['PAR_TPL_IDENTIFIANT'] . ')' : $row['ID_PARAGRAPHE'])) . ']';?></td>
                                <td><?php echo secureInput($row['PAG_TITRE_MENU'])?></td>
                            </tr>
                            <?php } ?>
                        </table>
                    </fieldset>
                        <?php } ?>

                        <?php
                        $sqlLiasonsrevision = "select LIAISON_PAGE.*, DD_LIAISON.*, REVISION.ID_PAGE as REV_IDPAGE from LIAISON_PAGE
                            inner join DD_LIAISON on (DD_LIAISON.LIA_CODE = LIAISON_PAGE.LIA_CODE)
                            inner join REVISION on (LIAISON_PAGE.ID_REVISION = REVISION.ID_REVISION)
                            where LIAISON_PAGE.ID_REVISION is not null and LIAISON_PAGE.ID_PAGE = ".$oPage->getID();
                        $aLiaisonsRevision   = $dbh->query($sqlLiasonsrevision)->fetchAll(PDO::FETCH_ASSOC);
                        if (sizeof($aLiaisonsRevision) > 0) { ?>
                    <fieldset>
                        <legend>Dans les révisions</legend>
                        <table class="liste">
                           <?php foreach ($aLiaisonsRevision as $liaisonRev) { ?>
                           <tr>
                              <td>
                                  <span><?php echo extraireLibelle($liaisonRev['LIA_LIBELLE']) . ' [' . $liaisonRev['ID_LIAISON'] . ']';?> - <a href="/cms/cms_revisionListe.php?idtf=<?php echo $liaisonRev['REV_IDPAGE']?>&rev=<?php echo $liaisonRev['ID_REVISION']?>"><?php echo gettext('voir_la_revision')?></a></span>
                              </td>
                            </tr>
                            <?php } ?>
                        </table>
                     </fieldset>
                        <?php } ?>
                </fieldset>
                <?php } ?>

                <table>
                    <tfoot>
                        <tr>
                            <td>
                         <?php if (!$oPage->exist() && !Utilisateur::getConnected()->isSEO()) { ?>
                                <input type="hidden" name="PAG_IDPERE" id="PAG_IDPERE" value="<?php echo secureInput($_GET['PAG_IDPERE'])?>">
                                <input type="hidden" name="PAG_POIDS" id="PAG_POIDS" value="<?php echo secureInput($_GET['PAG_POIDS'])?>">
                                <input type="submit" name="Insert" value="<?php echo gettext('INSERT')?>" class="ajouter">
                         <?php } elseif ($oPage->exist() && !Utilisateur::getConnected()->isSEO()) { ?>
                                <input type="hidden" name="idtf" value="<?php echo $oPage->getID()?>">
                                <input type="submit" name="Update" value="<?php echo gettext('UPDATE')?>" class="modifier">
                                <input type="button" name="Delete" value="<?php echo gettext('DELETE')?>" class="supprimer"<?php if (!$oPage->isDeletable()) echo ' disabled'?> onclick="if (confirm('<?php echo gettext('Etes-vous sur ?')?>')) window.location.href='cms_pageSubmit.php?Delete=<?php echo $oPage->getID()?>'">
                            <?php if ($oPage->hasOnlineVersion() && $oPage->getField('PST_CODE') != 'PST_HORSLIGNE') { ?>
                                <p>A noter : seules les pages "hors ligne" peuvent être supprimées </p>
                            <?php } ?>
                        <?php } elseif (Utilisateur::getConnected()->isSEO()) { ?>
                                <input type="hidden" name="idtf" value="<?php echo $oPage->getID()?>">
                                <input type="submit" name="UpdateRef" value="<?php echo gettext('UPDATE')?>" class="modifier">
                        <?php } ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </form>
        </div>
    </div>
    <?php include('../include/inc.bo_bandeau_bas.php')?>
</div>
</body>
</html>
