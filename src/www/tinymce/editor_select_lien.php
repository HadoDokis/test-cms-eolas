<?php
require '../include/inc.bo_init.php';
Utilisateur::checkConnected();
require CLASS_DIR . 'class.db_page.php';
require CLASS_DIR . 'class.Arbo.php';

$aSite = CMS::getCurrentSite()->getRevertSharedSites();
$aSite[CMS::getCurrentSite()->getID()] = CMS::getCurrentSite();

if (empty($_GET['SIT_CODE'])) {
    $_GET['SIT_CODE'] = CMS::getCurrentSite()->getID();
} elseif (!array_key_exists($_GET['SIT_CODE'], $aSite)) {
    die(gettext('Ressource_non_disponible'));
}

if (is_numeric($_GET['idtf'])) {
    $oPage = new Page($_GET['idtf']);
    $PAG_TITRE_MENU = $oPage->getField('PAG_TITRE_MENU');
    if ($oPage->getField('SIT_CODE') != CMS::getCurrentSite()->getID()) {
        if (!$oPage->checkShareAuthorized(false)) {
            die(gettext('Ressource_non_disponible'));
        }
        $PAG_TITRE_MENU = '[' . $aSite[$oPage->getField('SIT_CODE')]->getField('SIT_LIBELLE') . '] ' . $PAG_TITRE_MENU;
    }

    // recup de ses paragraphes
    $sql = "select * from OFF_PARAGRAPHE where ID_PAGE=" . $oPage->getID() . " and PRT_CODE!='PRT_HAUTDEPAGE' and PAR_COLONNE='PAR_CENTRAL' order by PAR_POIDS";
    $rowsTemp = $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rowsTemp as $rowTemp) {
        //Titre pour chaque paragraphe afficher
        $titreParagraphe = ($rowTemp['PAR_TITRE'] != '') ? $rowTemp['PAR_TITRE'] : '<em>' . gettext('pas_de_titre') . '</em>';
        //Titre pour l'ancre deja en place vers le paragraphe choisi
        $titreAncre = ($rowTemp['PAR_TITRE'] != '' ) ? $rowTemp['PAR_TITRE'] : gettext('pas_de_titre');
        //Si on est dans le cas d'un paragraphe partagé, on récupère le titre du paragraphe d'origine
        if ($rowTemp['PRT_CODE'] == 'PRT_PARTAGE') {
            //Récupération du titre origninel
            $sql1 = "select PAR_TITRE from OFF_PARAGRAPHE where ID_PARAGRAPHE=" . intval($rowTemp['PAR_TPL_IDENTIFIANT']);
            if ($TitreOrigine = $dbh->query($sql1)->fetchColumn()) {
                $titreParagraphe = $TitreOrigine;
                $titreAncre = $TitreOrigine;
            }
        }
        $tabParagraphes[$rowTemp['ID_PARAGRAPHE']] = $titreParagraphe;
        if ($_GET['ancre'] == $rowTemp['ID_PARAGRAPHE']) {
            $PAR_TITRE = $titreAncre;
        }
    }
    if ($PAR_TITRE == '') {
        $PAR_TITRE = '<em>Aucune</em>';
    }
} else {
    $PAR_TITRE = '<em>Aucune</em>';
}
$oArbo = new Arbo('TINY', array('ancre' => $_GET['ancre'], 'title' => $_GET['title'], 'SIT_CODE' => $_GET['SIT_CODE']));
?>
<!DOCTYPE html>
<html>
<head>
    <?php include '../include/inc.bo_enTete.php'; ?>
    <script src="tiny_mce_popup.js"></script>
    <script src="plugins/cms/js/cms.js"></script>
    <script>
        $(document).ready(cmsBO.initArbo);

        function postControl_formCreation(oForm) {
            var f_id = $('#f_id').val();
            var f_typelien = 'LienInterne';
            var f_title = $('#f_title').val();
            var f_nofollow = $('#f_nofollow').prop('checked') ? '1' : '';
            var f_ancre = $('#f_ancre').val();
            var ed = tinyMCEPopup.editor;
            ed.execCommand("mceBeginUndoLevel");
            insererLien(ed, f_id, f_typelien, f_title, f_nofollow, f_ancre);
            ed.execCommand("mceEndUndoLevel");
            tinyMCEPopup.close();
            return false;
        }

        function affecterAncre(idPAR, PAR_TITRE) {
            document.getElementById('PAR_TITRE').innerHTML = PAR_TITRE;
            document.getElementById('f_ancre').value = idPAR;
        }

        function effacerAncre() {
            document.getElementById('PAR_TITRE').innerHTML = '<em>Aucune</em>';
            document.getElementById('f_ancre').value = '';
        }

        function Init() {
            var cLinks= document.getElementsByTagName("link");
            for (var i=0; cLinks[i]; i++) {
                if (/tinymce/.test(cLinks[i].href)) {
                    cLinks[i].disabled = true;
                }
            }
            // Suppression et réinitialisation du gestionnaire de formulaire depuis la méthode de chargement de popup de Tiny
            removeEventLst(window, "load", formCtrl.load);
            for(i = 0; i < document.getElementsByTagName('form').length; i++ ) removeEventLst(document.getElementsByTagName('form')[i], "submit", formCtrl.control);
            formCtrl.load();
        }
    </script>
    <base target="_self">
</head>
<body id="popup" onload="tinyMCEPopup.executeOnLoad(Init());">
    <?php include('../include/inc.bo_bandeau_hautPopup.php')?>
    <div id="bo_contenuPopup">
        <h2>Choisir une page</h2>
        <form method="get" action="<?php echo PHP_SELF?>" class="creation" id="formCreation" style="position:relative;">
            <?php if (sizeof($aSite) > 1) { ?>
            <div style="position:absolute; right:20px; top:20px;">
                <label for="SIT_CODE"><?php echo gettext('Site courant')?></label>
                <select id="SIT_CODE" name="SIT_CODE" onchange="window.location.href='<?php echo PHP_SELF?>?idtf=<?php echo $_GET['idtf'] ?>&ancre=<?php echo $_GET['ancre']?>&amp;title=<?php echo urlencode($_GET['title'])?>&amp;SIT_CODE='+this.value;">
                    <?php foreach ($aSite as $_oSite) { ?>
                    <option value="<?php echo $_oSite->getID()?>"<?php if ($_GET['SIT_CODE'] == $_oSite->getID()) echo ' selected';?>>
                        <?php echo secureInput($_oSite->getField('SIT_LIBELLE'))?>
                    </option>
                    <?php } ?>
                </select>
            </div>
            <?php } ?>
            <table>
                <tfoot>
                    <tr>
                        <td colspan="2">
                            <input type="hidden" name="f_ancre" id="f_ancre" value="<?php echo secureInput($_GET['ancre'])?>">
                            <input type="submit" value="Valider" class="ajouter">
                        </td>
                    </tr>
                </tfoot>
                <tbody>
                    <tr>
                        <th><label for="f_id" class="isNotNull">Page</label></th>
                        <td><?php echo $PAG_TITRE_MENU?> <input type="hidden" value="<?php echo secureInput($_GET['idtf'])?>" id="f_id" required></td>
                    </tr>
                    <?php if (sizeof($tabParagraphes) > 0) {?>
                   <tr>
                        <th><label>Ancre actuelle</label></th>
                        <td>
                            <span id="PAR_TITRE"><?php echo $PAR_TITRE?></span>
                            <a href="#" onclick="effacerAncre()" class="action">Supprimer l'ancre</a>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Ancres disponibles</label></th>
                        <td>
                             <ul>
                                <?php foreach ($tabParagraphes as $key=>$val) { ?>
                                <li><a href="javascript:affecterAncre(<?php echo $key?>, '<?php echo escapeJS(strip_tags($val))?>')"><?php echo $val?> </a></li>
                                <?php } ?>
                            </ul>
                        </td>
                    </tr>
                    <?php } ?>
                    <tr>
                        <th><label for="f_title"><?php echo gettext('Titre survol')?></label></th>
                        <td><input name="f_title" type="text" id="f_title" value="<?php echo secureInput($_GET['title'])?>" size="40"></td>
                    </tr>
                    <tr>
                        <th><label>Référencement</label></th>
                        <td>
                            <input type="checkbox" name="f_nofollow" id="f_nofollow" value="1"<?php if ($_GET['nofollow']) echo ' checked'?>>
                            <label for="f_nofollow">Ne pas suivre le lien (ajout d'un attribut rel="nofollow")</label>
                        </td>
                    </tr>
                </tbody>
            </table>
            <br>
            <?php echo Arbo::action() ?>
            <?php echo $oArbo->draw($aSite[$_GET['SIT_CODE']]->getHomePage()->getID()) ?>
        </form>
    </div>
    <?php include('../include/inc.bo_bandeau_basPopup.php')?>
</body>
</html>
