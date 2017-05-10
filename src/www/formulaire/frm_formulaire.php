<?php
require '../include/inc.bo_init.php';
require CLASS_DIR . 'class.db_formulaire.php';
require CLASS_DIR . 'class.db_formulaireCategorie.php';
require CLASS_DIR . 'class.Editor.php';
require CLASS_DIR . 'class.Pagination.php';
$moduleForm = new Module('MOD_FORMULAIRE');
CMS::checkAccess($moduleForm, array('PRO_FORMGEST', 'PRO_FORMLECT'));

$oFormulaire = new Formulaire($_GET['idtf']);
$row = $oFormulaire->getFields();
if (empty($row['FRM_EXPEDITEUR_NOM'])) {
    $row['FRM_EXPEDITEUR_NOM'] = EMAIL_FROMNAME;
}
if (empty($row['FRM_EXPEDITEUR_EMAIL'])) {
    $row['FRM_EXPEDITEUR_EMAIL'] = EMAIL_FROM;
}

if (!isset($_GET['Export'])) {
    $_SESSION['FORMULAIRE']['URL'] = $_SERVER['REQUEST_URI'];
}

//* Recherche sur les réponses au formulaire
if ($oFormulaire->exist()) {
    $oFormulaire->isAuthorized();
    $p = new Pagination();
    $p->setParam('idtf', $oFormulaire->getID());
    $filtre = "f.ID_FORMULAIRE = " . $oFormulaire->getID();
    if ($p->onSearch()) {
        if (!empty($_GET['REP_ETAT'])) {
            $filtre .= " and f.REP_ETAT=" . $dbh->quote($_GET['REP_ETAT']);
        }

        $sql = "select * from FORMULAIREQUESTION fq
            inner join FORMULAIREGROUPE using (ID_FORMULAIREGROUPE)
            where QST_ENTETE = 1 and ID_FORMULAIRE = " . $oFormulaire->getID() . "
            order by QST_POIDS";
        foreach ($dbh->query($sql) as $rowQTY) {
            $QSTidtf = $rowQTY['ID_FORMULAIREQUESTION'];
            $QTYname = 'QTY_' . $QSTidtf;
            if ($rowQTY['QTY_CODE'] == 'QTY_CHECKBOX') {
                if (is_array($_GET[$QTYname]) && sizeof($_GET[$QTYname]) > 0) {
                    $filtre .= " and fd.RED_VALEUR is not null";
                    foreach ($_GET[$QTYname] as $value) {
                        $filtre .= " and fd.RED_VALEUR like " . $dbh->quote("%" . $value . "%") . "and fq.ID_FORMULAIREQUESTION = " . $QSTidtf;
                    }
                }
            } elseif (!empty ($_GET[$QTYname])) {
                $filtre .= " and fd.RED_VALEUR" . $p->makeLike($QTYname) . "and fq.ID_FORMULAIREQUESTION = " . $QSTidtf;
            }
        }
        if (!empty($_GET['REP_DATE_APARTIRDU'])) {
            $filtre .= " and REP_DATE>=" . intval(unixtime($_GET['REP_DATE_APARTIRDU']));
        }
        if (!empty($_GET['REP_DATE_JUSQUAU'])) {
            $filtre .= " and REP_DATE<" . intval(unixtime($_GET['REP_DATE_JUSQUAU'], true));
        }
    } else {
        $p->setOrderBy('REP_DATE desc');
        $p->setMpp(20);
    }

    $p->setFilter($filtre);
    $p->setCount("select count( distinct f.ID_FORMULAIREREPONSE) from FORMULAIREREPONSE f
        inner join FORMULAIREREPONSEDETAIL fd on f.ID_FORMULAIREREPONSE = fd.ID_FORMULAIREREPONSE
        left join FORMULAIREQUESTION fq on fd.ID_FORMULAIREQUESTION = fq.ID_FORMULAIREQUESTION");
}
// FIN Recherche sur les réponses au formulaire */

//* Export des réponses au formulaire
if ($oFormulaire->exist() && isset($_GET['Export'])) {
    set_time_limit(0);
    header("Content-type:text/csv");
    header("Content-Disposition: attachment; filename=\"" . filenameToRfc1738($row['FRM_LIBELLE']) . ".csv\"");

    $f = fopen("php://temp", 'w');

    $aCSVHeaders = array(gettext('Date'), gettext('Etat'));
    if ($row['FRM_TRACABLE']) {
        $aCSVHeaders[] = gettext('Utilisateur');
    }
    if ($row['FRM_TRACEIP']) {
        $aCSVHeaders[] = gettext('Adresse IP');
    }

    $aFORMULAIREQUESTION = array() ;
    $sql = "select * from FORMULAIREQUESTION
        inner join FORMULAIREGROUPE using (ID_FORMULAIREGROUPE)
        where ID_FORMULAIRE=" . intval($oFormulaire->getID()) . "
        and QTY_CODE not in ('QTY_INFORMATION', 'QTY_CAPTCHAGRAPHIC', 'QTY_CAPTCHANUMERIC', 'QTY_CAPTCHANUMERICMINUS')
        order by FMG_POIDS, QST_POIDS";
    foreach ($dbh->query($sql) as $rowTemp) {
        $aFORMULAIREQUESTION[] = $rowTemp['ID_FORMULAIREQUESTION'];
        $aCSVHeaders[] = $rowTemp['QST_LIBELLE'];
    }
    fputcsv($f, $aCSVHeaders, ';');

    $sql = "select distinct f.ID_FORMULAIREREPONSE, f.*, u.UTI_PRENOM, u.UTI_NOM from FORMULAIREREPONSE f
        left join UTILISATEUR u on f.ID_UTILISATEUR = u.ID_UTILISATEUR
        left join FORMULAIREREPONSEDETAIL fd on f.ID_FORMULAIREREPONSE = fd.ID_FORMULAIREREPONSE
        left join FORMULAIREQUESTION fq on fd.ID_FORMULAIREQUESTION = fq.ID_FORMULAIREQUESTION
        where " . $filtre . " order by REP_DATE";
    $aFORMULAIREREPONSE = $dbh->query($sql)->fetchAll(PDO :: FETCH_ASSOC);
    foreach ($aFORMULAIREREPONSE as $rowListe) {
        $aCSVData = array(date("d/m/Y H:i", $rowListe['REP_DATE']));
        $aCSVData[] = ($rowListe['REP_ETAT'] != '') ? $rowListe['REP_ETAT'] : 'Sans état';

        if ($row['FRM_TRACABLE']) {
            $aCSVData[] = $rowListe['UTI_PRENOM'] . ' ' . $rowListe['UTI_NOM'];
        }
        if ($row['FRM_TRACEIP']) {
            $aCSVData[] = $rowListe['REP_IPUTILISATEUR'];
        }

        //On récupere le détail d'une réponse.
        $sql = "select FORMULAIREQUESTION.ID_FORMULAIREQUESTION, RED_VALEUR, QTY_CODE from FORMULAIREREPONSEDETAIL
            inner join FORMULAIREQUESTION using (ID_FORMULAIREQUESTION)
            where ID_FORMULAIREREPONSE=" . $rowListe['ID_FORMULAIREREPONSE'];
        $aReponse = $dbh->query($sql)->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_UNIQUE|PDO::FETCH_ASSOC);

        // une fois que l'on a récuperé toutes les réponses, on les affiche en fonction de l'ordre des questions
        foreach ($aFORMULAIREQUESTION as $idQuestion) {
            if (isset($aReponse[$idQuestion])) {
                if ($aReponse[$idQuestion]['QTY_CODE'] == 'QTY_FILE') {
                    $aCSVData[] = $_SERVER['HTTP_HOST'] . UPLOAD_FORMULAIRE . $aReponse[$idQuestion]['RED_VALEUR'];
                } else {
                    $aCSVData[] = $aReponse[$idQuestion]['RED_VALEUR'];
                }
            } else {
                 $aCSVData[] = '';
            }
        }
        fputcsv($f, $aCSVData, ';');
    }
    rewind($f);
    echo utf8_decode(stream_get_contents($f));
    fclose($f);
    exit();
}
//FIN Export des réponses au formulaire */
?>
<!DOCTYPE html>
<html>
<head>
<?php include('../include/inc.bo_enTete.php') ?>
<?php if (Utilisateur::getConnected()->checkProfil(array('PRO_FORMGEST'))) { ?>
    <script>
    function postControl_formCreation(oForm)
    {
        selectAll('ID_UTILISATEUR');
        return true;
    }
    </script>

    <?php
    if ($oFormulaire->hasFrontNotification()) {
        Editor::header();
        echo '<script>editorInit("minimal", new Array("FRM_ACCUSERECEPTION"));</script>';
    } ?>
    <script src="<?php echo SERVER_ROOT ?>include/js/onglet.js"></script>
    <?php if (!empty($_GET['showTab'])) {?>
    <script>
    $(document).ready( function () {
        $('#bo_onglet li a').each(function () {
            if($(this).attr('href') == '#<?php echo escapeJS($_GET['showTab'])?>') $(this).click();
        });
    });
    </script>
    <?php }?>
<?php }?>
</head>
<body>
<div id="document">
    <?php $aMenuKey = array('FRM', 'MOD_FORMULAIRE'); if (!$oFormulaire->exist()) $aMenuKey[]='ADD'; include('../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">
            <h2><?php echo ($oFormulaire->exist())? secureInput($oFormulaire->getField('FRM_LIBELLE')) : 'Nouveau formulaire';?></h2>
            <div class="creation formulaireDynamique">
            <?php if (Utilisateur::getConnected()->checkProfil(array('PRO_FORMGEST'))) { ?>
                <fieldset class="tab">
                    <legend><?php echo gettext('Proprietes')?></legend>
                    <form method="post" action="frm_formulaireSubmit.php" id="formCreation">
                        <table>
                            <tfoot>
                                <tr>
                                    <td colspan="2">
                                        <?php if ($oFormulaire->exist()) { ?>
                                        <input type="hidden" name="idtf" value="<?php echo $oFormulaire->getID()?>">
                                        <input type="submit" name="Update" value="<?php echo gettext('UPDATE')?>" class="modifier">
                                        <input type="submit" name="Duplicate" value="<?php echo gettext('Dupliquer')?>" class="submit">
                                        <input type="button" name="Delete" value="<?php echo gettext('DELETE')?>" class="supprimer"<?php if (!$oFormulaire->isDeletable()) echo ' disabled' ?> onclick="if (confirm('<?php echo gettext('Etes-vous sur ?')?>')) window.location.href='frm_formulaireSubmit.php?Delete=<?php echo $oFormulaire->getID()?>'">
                                        <?php } else { ?>
                                        <input type="submit" name="Insert" value="<?php echo gettext('INSERT')?>" class="ajouter">
                                        <?php } ?>
                                    </td>
                                </tr>
                            </tfoot>
                            <tbody>
                                <tr>
                                    <th><label for="FRM_LIBELLE"><?php echo gettext('Libelle')?></label></th>
                                    <td><input name="FRM_LIBELLE" type="text" id="FRM_LIBELLE" value="<?php echo secureInput($row['FRM_LIBELLE'])?>" size="60" required></td>
                                </tr>
                                <?php if (CMS::getCurrentSite()->hasModule(new Module('MOD_EXTRANET'))) {?>
                                <tr>
                                    <th><label><?php echo gettext('Identifier depositaire')?></label></th>
                                    <td>
                                        <input name="FRM_TRACABLE" id="FRM_TRACABLE_1" type="radio" value="1"<?php if ($row['FRM_TRACABLE']) echo ' checked'?>><label for="FRM_TRACABLE_1"><?php echo gettext('Oui')?></label>
                                        <input name="FRM_TRACABLE" id="FRM_TRACABLE_0" type="radio" value="0"<?php if (!$row['FRM_TRACABLE']) echo ' checked'?>><label for="FRM_TRACABLE_0"><?php echo gettext('Non')?></label>
                                    </td>
                                </tr>
                                <?php } ?>
                                <tr>
                                    <th><label><?php echo gettext('Tracer adresse IP depositaire')?></label></th>
                                    <td>
                                        <input name="FRM_TRACEIP" id="FRM_TRACEIP_1" type="radio" value="1"<?php if ($row['FRM_TRACEIP']) echo ' checked'?>><label for="FRM_TRACEIP_1"><?php echo gettext('Oui')?></label>
                                        <input name="FRM_TRACEIP" id="FRM_TRACEIP_0" type="radio" value="0"<?php if (!$row['FRM_TRACEIP']) echo ' checked'?>><label for="FRM_TRACEIP_0"><?php echo gettext('Non')?></label>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label><?php echo gettext('Mention CNIL')?></label></th>
                                    <td>
                                        <input name="FRM_MENTION_CNIL" id="FRM_MENTION_CNIL_1" type="radio" value="1"<?php if ($row['FRM_MENTION_CNIL']) echo ' checked'?>><label for="FRM_MENTION_CNIL_1"><?php echo gettext('Oui')?></label>
                                        <input name="FRM_MENTION_CNIL" id="FRM_MENTION_CNIL_0" type="radio" value="0"<?php if (!$row['FRM_MENTION_CNIL']) echo ' checked'?>><label for="FRM_MENTION_CNIL_0"><?php echo gettext('Non')?></label>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="<?php echo (FormulaireCategorie::getNb() == 0) ? 'CAT_LIBELLE' : 'ID_FORMULAIRECATEGORIE'?>"><?php echo gettext('Categorie')?></label></th>
                                    <td>
                                        <select name="ID_FORMULAIRECATEGORIE" id="ID_FORMULAIRECATEGORIE"<?php if (FormulaireCategorie::getNb() != 0) echo ' required'?>>
                                            <option value="">&nbsp;</option>
                                            <?php echo FormulaireCategorie::getSelectOptions($row['ID_FORMULAIRECATEGORIE']) ?>
                                        </select>
                                        <input name="CAT_LIBELLE" type="text" id="CAT_LIBELLE" size="30" placeholder="<?php echo gettext('Ajouter_dossier')?>"<?php if (FormulaireCategorie::getNb() == 0) echo ' required'?>>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="ID_UTILISATEUR"><?php echo gettext('Intervenants')?></label></th>
                                    <td>
                                        <table class="selection">
                                            <tbody>
                                                <tr>
                                                    <th><?php echo gettext('Affecte(s)')?></th>
                                                    <th>&nbsp;</th>
                                                    <th><?php echo gettext('Disponible(s)')?></th>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <select name="ID_UTILISATEUR[]" id="ID_UTILISATEUR" size="6" multiple ondblclick="DeplaceCritere(document.getElementById('ID_UTILISATEUR'), document.getElementById('ID_UTILISATEUR_ALL'));">
                                                        <?php
                                                        $sql = "select UTILISATEUR.ID_UTILISATEUR, UTI_NOM, UTI_PRENOM from UTILISATEUR
                                                            inner join FORMULAIRE_UTILISATEUR on UTILISATEUR.ID_UTILISATEUR = FORMULAIRE_UTILISATEUR.ID_UTILISATEUR
                                                            where FORMULAIRE_UTILISATEUR.ID_FORMULAIRE = ".$oFormulaire->getID()."
                                                            order by UTI_NOM, UTI_PRENOM";
                                                        foreach ($dbh->query($sql) as $rowTemp) { ?>
                                                            <option value="<?php echo $rowTemp['ID_UTILISATEUR']?>"><?php echo secureInput($rowTemp['UTI_NOM'] . ' ' . $rowTemp['UTI_PRENOM'])?></option>
                                                        <?php } ?>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input type="button" name="Button" value="&lt;&lt;" onclick="DeplaceCritere(document.getElementById('ID_UTILISATEUR_ALL'), document.getElementById('ID_UTILISATEUR'));">
                                                        <input type="button" name="Button2" value="&gt;&gt;" onclick="DeplaceCritere(document.getElementById('ID_UTILISATEUR'), document.getElementById('ID_UTILISATEUR_ALL'));">
                                                    </td>
                                                    <td>
                                                        <select name="ID_UTILISATEUR_ALL[]" id="ID_UTILISATEUR_ALL" size="6" multiple ondblclick="DeplaceCritere(document.getElementById('ID_UTILISATEUR_ALL'), document.getElementById('ID_UTILISATEUR'));">
                                                        <?php
                                                        $sql = "select distinct(UTILISATEUR.ID_UTILISATEUR), UTI_NOM, UTI_PRENOM from UTILISATEUR
                                                            inner join ROLE on UTILISATEUR.ID_UTILISATEUR=ROLE.ID_UTILISATEUR
                                                            where UTILISATEUR.ID_UTILISATEUR not in (select ID_UTILISATEUR from FORMULAIRE_UTILISATEUR where ID_FORMULAIRE = " . $oFormulaire->getID() . ")
                                                            and (
                                                                (PRO_CODE in ('PRO_FORMGEST', 'PRO_FORMLECT', 'PRO_ROOT_SITE') and ROLE.SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID()) . ")
                                                                or
                                                                (PRO_CODE like 'PRO_ROOT' and ROLE.SIT_CODE IS NULL)
                                                                )
                                                            order by UTI_NOM, UTI_PRENOM";
                                                        foreach ($dbh->query($sql) as $rowTemp) {?>
                                                        <option value="<?php echo $rowTemp['ID_UTILISATEUR']?>"><?php echo secureInput($rowTemp['UTI_NOM'] . ' ' . $rowTemp['UTI_PRENOM'])?></option>
                                                        <?php } ?>
                                                        </select>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label><?php echo gettext('Notifier intervenant par email')?></label></th>
                                    <td>
                                        <input name="FRM_NOTIFICATION" id="FRM_NOTIFICATION_1" type="radio" value="1"<?php if ($row['FRM_NOTIFICATION']) echo ' checked'?>><label for="FRM_NOTIFICATION_1"><?php echo gettext('Oui')?></label>
                                        <input name="FRM_NOTIFICATION" id="FRM_NOTIFICATION_0" type="radio" value="0"<?php if (!$row['FRM_NOTIFICATION']) echo ' checked'?>><label for="FRM_NOTIFICATION_0"><?php echo gettext('Non')?></label>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="FRM_NOTIFICATION_EMAIL"><?php echo gettext('Notifications_supplementaires')?></label></th>
                                    <td>
                                        <input name="FRM_NOTIFICATION_EMAIL" type="text" id="FRM_NOTIFICATION_EMAIL" value="<?php echo secureInput($row['FRM_NOTIFICATION_EMAIL'])?>" size="60" maxlength="255">
                                        <br><?php echo gettext('Notifications_supplementaires_explication')?>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="FRM_EXPEDITEUR_NOM">Nom d'expéditeur des courriels de notification</label></th>
                                    <td><input name="FRM_EXPEDITEUR_NOM" type="text" id="FRM_EXPEDITEUR_NOM" value="<?php echo secureInput($row['FRM_EXPEDITEUR_NOM'])?>" size="60" required></td>
                                </tr>
                                <tr>
                                    <th><label for="FRM_EXPEDITEUR_EMAIL">Email d'expéditeur des courriels de notification</label></th>
                                    <td><input name="FRM_EXPEDITEUR_EMAIL" type="email" id="FRM_EXPEDITEUR_EMAIL" value="<?php echo secureInput($row['FRM_EXPEDITEUR_EMAIL'])?>" size="60" required></td>
                                </tr>
                                <tr>
                                    <th><label for="FRM_ETATREPONSE"><?php echo gettext('Etat des reponses')?></label></th>
                                    <td><textarea name="FRM_ETATREPONSE" cols="30" rows="<?php echo max(3, sizeof(explode("\n", $row['FRM_ETATREPONSE'])))?>" id="FRM_ETATREPONSE"><?php echo secureInput($row['FRM_ETATREPONSE'])?></textarea></td>
                                </tr>
                                <tr>
                                    <th><label for="FRM_MESSAGEREPONSE"><?php echo gettext('Message de reponse')?></label></th>
                                    <td><textarea name="FRM_MESSAGEREPONSE" cols="40" rows="5" id="FRM_MESSAGEREPONSE"><?php echo $oFormulaire->exist() ? secureInput($row['FRM_MESSAGEREPONSE']) : $moduleForm->i18n('Message_remerciements_defaut'); ?></textarea></td>
                                </tr>
                            </tbody>
                        </table>
                    </form>
                </fieldset>
                <?php if ($oFormulaire->exist()) {?>
                <fieldset class="tab">
                    <legend><?php echo gettext('Questions')?></legend>
                    <form method="post" action="frm_formulaireSubmit.php">
                        <?php
                        $sql = "select FORMULAIREGROUPE.ID_FORMULAIREGROUPE, count(ID_FORMULAIREQUESTION) as NB_QUESTION from FORMULAIREGROUPE
                            left join FORMULAIREQUESTION using (ID_FORMULAIREGROUPE)
                            where ID_FORMULAIRE=". $oFormulaire->getID() . "
                            group by FORMULAIREGROUPE.ID_FORMULAIREGROUPE";
                        foreach ($dbh->query($sql) as $rowTemp) {
                            $NB_FORMULAIREGROUPE[$rowTemp['ID_FORMULAIREGROUPE']] = $rowTemp['NB_QUESTION'];
                        }
                        $sql = "select * from FORMULAIREGROUPE where ID_FORMULAIRE=". $oFormulaire->getID() . " order by FMG_POIDS";
                        $CPT_FORMULAIREGROUPE = 1;
                        foreach ($dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $key => $rowGroupe) { ?>
                        <p class="ajoutGroupQuestion">
                            <a href="frm_formulaireGroupePopup.php?ID_FORMULAIRE=<?php echo $oFormulaire->getID() ?>&amp;FMG_POIDS=<?php echo $CPT_FORMULAIREGROUPE?>" class="actionAjouter popup">
                                Insérer un nouveau groupe
                            </a>
                        </p>
                        <div>
                            <h3>
                                <?php if ($CPT_FORMULAIREGROUPE < sizeof($NB_FORMULAIREGROUPE)) {?>
                                <a href="frm_formulaireGroupePopupSubmit.php?Down=<?php echo $rowGroupe['ID_FORMULAIREGROUPE']?>&amp;FMG_POIDS=<?php echo $rowGroupe['FMG_POIDS']?>&amp;ID_FORMULAIRE=<?php echo $oFormulaire->getID() ?>" title="<?php echo gettext('Descendre')?>"><img src="../images/pagination/triAsc.gif" alt="<?php echo gettext('Descendre')?>"></a>
                                <?php } else { ?>
                                <img src="../images/pagination/empty.gif" alt="">
                                <?php } ?>
                                <?php if ($CPT_FORMULAIREGROUPE > 1) {?>
                                <a href="frm_formulaireGroupePopupSubmit.php?Up=<?php echo $rowGroupe['ID_FORMULAIREGROUPE']?>&amp;FMG_POIDS=<?php echo $rowGroupe['FMG_POIDS']?>&amp;ID_FORMULAIRE=<?php echo $oFormulaire->getID() ?> " title="<?php echo gettext('Monter')?>"><img src="../images/pagination/triDesc.gif" alt="<?php echo gettext('Monter')?>"></a>
                                <?php } else { ?>
                                <img src="../images/pagination/empty.gif" alt="">
                                <?php } ?>
                                <a href="frm_formulaireGroupePopup.php?idtf=<?php echo $rowGroupe['ID_FORMULAIREGROUPE']?>" class="popup"><?php echo secureInput($rowGroupe['FMG_LIBELLE'])?></a>
                            </h3>
                            <table class="liste">
                                <thead>
                                    <tr>
                                        <th>
                                            Nouvelle question
                                            <?php if ($oFormulaire->hasFrontNotification()) { ?>
                                            / Code
                                            <?php } ?>
                                        </th>
                                        <th>Libellé</th>
                                        <th>Type</th>
                                        <th>Visible</th>
                                        <th>Obligatoire</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql = "select * from FORMULAIREQUESTION
                                        inner join DD_FORMULAIREQUESTIONTYPE on FORMULAIREQUESTION.QTY_CODE = DD_FORMULAIREQUESTIONTYPE.QTY_CODE
                                        inner join FORMULAIREGROUPE on FORMULAIREQUESTION.ID_FORMULAIREGROUPE = FORMULAIREGROUPE.ID_FORMULAIREGROUPE
                                        where FORMULAIREQUESTION.ID_FORMULAIREGROUPE= ".$rowGroupe['ID_FORMULAIREGROUPE']." order by QST_POIDS";
                                    $CPT_FORMULAIREQUESTION = 1;
                                    foreach ($dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $key => $rowQuestion) { ?>
                                    <tr>
                                        <td class="aligncenter"><a href="frm_formulaireQuestionPopup.php?ID_FORMULAIREGROUPE=<?php echo $rowGroupe['ID_FORMULAIREGROUPE']?>&amp;QST_POIDS=<?php echo $rowQuestion['QST_POIDS'] ?>" class="popup"><img src="../images/page_add_hover.png" alt="<?php echo gettext('Ajouter')?>"></a></td>
                                        <td colspan="99">&nbsp;</td>
                                    </tr>
                                    <tr>
                                        <td class="aligncenter">
                                            <?php if ($oFormulaire->hasFrontNotification() && $rowQuestion['QTY_CODE'] != 'QTY_FILE') { ?>
                                            [QST_<?php echo $rowQuestion['ID_FORMULAIREQUESTION']?>]
                                            <?php } ?>
                                        </td>
                                        <td>
                                            <?php if ($CPT_FORMULAIREQUESTION < $NB_FORMULAIREGROUPE[$rowQuestion['ID_FORMULAIREGROUPE']]) {?>
                                            <a href="frm_formulaireQuestionPopupSubmit.php?Down=<?php echo $rowQuestion['ID_FORMULAIREQUESTION']?>&amp;QST_POIDS=<?php echo $rowQuestion['QST_POIDS']?>&amp;ID_FORMULAIRE=<?php echo $rowQuestion['ID_FORMULAIRE']?>&amp;ID_FORMULAIREGROUPE=<?php echo $rowQuestion['ID_FORMULAIREGROUPE']?>" title="<?php echo gettext('Descendre')?>"><img src="../images/pagination/triAsc.gif" alt="<?php echo gettext('Descendre')?>"></a>
                                            <?php } else { ?>
                                            <img src="../images/pagination/empty.gif" alt="">
                                            <?php } ?>
                                            <?php if ($CPT_FORMULAIREQUESTION > 1) {?>
                                            <a href="frm_formulaireQuestionPopupSubmit.php?Up=<?php echo $rowQuestion['ID_FORMULAIREQUESTION']?>&amp;QST_POIDS=<?php echo $rowQuestion['QST_POIDS']?>&amp;ID_FORMULAIRE=<?php echo $rowQuestion['ID_FORMULAIRE']?>&amp;ID_FORMULAIREGROUPE=<?php echo $rowQuestion['ID_FORMULAIREGROUPE']?>" title="<?php echo gettext('Monter')?>"><img src="../images/pagination/triDesc.gif" alt="<?php echo gettext('Monter')?>"></a>
                                            <?php } else { ?>
                                            <img src="../images/pagination/empty.gif" alt="">
                                            <?php } ?>
                                            <a href="frm_formulaireQuestionPopup.php?idtf=<?php echo $rowQuestion['ID_FORMULAIREQUESTION']?>" class="popup"><?php echo secureInput($rowQuestion['QST_LIBELLE'])?></a>
                                        </td>
                                        <td><?php echo secureInput(extraireLibelle($rowQuestion['QTY_LIBELLE']))?></td>
                                        <td class="aligncenter"><?php echo ($rowQuestion['QST_VISIBLE']) ? gettext('Oui') : gettext('Non')?></td>
                                        <td class="aligncenter"><?php echo ($rowQuestion['QST_OBLIGATOIRE']) ? gettext('Oui') : gettext('Non')?></td>
                                    </tr>
                                    <?php
                                        $CPT_FORMULAIREQUESTION++;
                                    } ?>
                                    <tr>
                                        <td class="aligncenter"><a href="frm_formulaireQuestionPopup.php?ID_FORMULAIREGROUPE=<?php echo $rowGroupe['ID_FORMULAIREGROUPE']?>&amp;QST_POIDS=<?php echo ($rowQuestion['QST_POIDS'] + 1) ?>" class="popup"><img src="../images/page_add_hover.png" alt="<?php echo gettext('Ajouter')?>"></a></td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <?php
                            $CPT_FORMULAIREGROUPE++;
                        } ?>
                        <p class="ajoutGroupQuestion">
                            <a href="frm_formulaireGroupePopup.php?ID_FORMULAIRE=<?php echo $oFormulaire->getID() ?>&amp;FMG_POIDS=<?php echo $CPT_FORMULAIREGROUPE?>" class="actionAjouter popup">
                                <?php echo gettext('inserer un nouveau groupe') ?>
                            </a>
                        </p>
                        <table>
                            <tfoot>
                                <tr>
                                    <td colspan="2">
                                        <input type="hidden" name="idtf" value="<?php echo $oFormulaire->getID()?>">
                                        <?php if ($oFormulaire->hasGroups() > 0) {?>
                                        <input type="button" class="submit" onclick= "window.open('<?php echo SERVER_ROOT ?>cms/cms_pseudo.php?TPL_CODE=TPL_FORMULAIRE&amp;PAR_TPL_IDENTIFIANT=<?php echo $oFormulaire->getID()?>')" value="<?php echo gettext('Apercu')?>">
                                        <?php } ?>
                                        <input type="submit" name="UpdateBouton" value="<?php echo gettext('UPDATE')?>" class="modifier">
                                        <input type="hidden" name="UpdateBouton" value="1">
                                    </td>
                                </tr>
                            </tfoot>
                            <tbody>
                                <tr>
                                    <th><label for="FRM_LIBELLE_BOUTON">Libellé du bouton d'envoi</label></th>
                                    <td><input name="FRM_LIBELLE_BOUTON" type="text" id="FRM_LIBELLE_BOUTON" value="<?php echo secureInput($row['FRM_LIBELLE_BOUTON'])?>" size="30" required></td>
                                </tr>
                                <?php if ($oFormulaire->hasFrontNotification()) { ?>
                                <tr>
                                    <th><label for="FRM_ACCUSERECEPTION">Accusé de réception envoyé à l'internaute</label></th>
                                    <td><textarea id="FRM_ACCUSERECEPTION" name="FRM_ACCUSERECEPTION" style="width:100%" rows="20" cols="60"><?php echo secureInput($row['FRM_ACCUSERECEPTION']);?></textarea></td>
                                </tr>
                                <tr>
                                    <th><label>Clés</label></th>
                                    <td>
                                        [DATE_JMA] : Date du jour au format jj/mm/aaaa
                                        <br>[FRM_LIBELLE] : Libellé du formulaire (<em><?php echo secureInput($row['FRM_LIBELLE'])?></em>)
                                        <br>[QST_<em>xx</em>] : Réponse à la question QST_<em>xx</em>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </form>
                </fieldset>
                <?php } ?>
            <?php } //PRO_FORMGEST ?>

                <?php
                if ($oFormulaire->exist()) {
                    $sql = "select count(ID_FORMULAIREREPONSE) from FORMULAIREREPONSE where ID_FORMULAIRE=".$oFormulaire->getID();
                    $nbID_FORMULAIREREPONSE = $dbh->query($sql)->fetchColumn();
                    if ($nbID_FORMULAIREREPONSE > 0) {
                        $etats  = $oFormulaire->getEtat();?>
                <fieldset class="tab">
                    <legend>Réponses</legend>
                    <form method="get" action="<?php echo PHP_SELF?>" class="filtre">
                        <fieldset>
                            <legend><?php echo gettext('MOTEUR_RECHERCHE')?></legend>
                            <table>
                                <tfoot>
                                    <tr>
                                        <td colspan="4">
                                            <?php echo $p->actionRecherche()?>
                                            <input type="submit" class="submit" name="Export" value="<?php echo gettext('Exporter')?>">
                                        </td>
                                    </tr>
                                </tfoot>
                                <tbody>
                                    <tr>
                                        <th><label for="REP_DATE_APARTIRDU" style="border: none;"><?php echo gettext('reponse donnee a partir du');?></label></th>
                                        <td><input type="text" name="REP_DATE_APARTIRDU" id="REP_DATE_APARTIRDU"  value="<?php echo secureInput($_GET['REP_DATE_APARTIRDU']) ?>" data-type="date"></td>
                                        <th><label for="REP_DATE_JUSQUAU" style="border: none;"><?php echo gettext('reponses donnees jusquau');?></label></th>
                                        <td><input type="text" name="REP_DATE_JUSQUAU" id="REP_DATE_JUSQUAU"  value="<?php echo secureInput($_GET['REP_DATE_JUSQUAU']) ?>" data-type="date"></td>
                                    </tr>
                                    <?php if (!empty($etats)) { $aEtats = explode("\n", $etats); ?>
                                    <tr>
                                        <th><label for="REP_ETAT"><?php echo gettext('Etat');?></label></th>
                                        <td>
                                            <select name="REP_ETAT" id="REP_ETAT">
                                                <option value="">&nbsp;</option>
                                                <?php foreach ($aEtats as $rowEtat) { ?>
                                                <option value="<?php echo secureInput($rowEtat) ?>" <?php if ($_GET['REP_ETAT']==$rowEtat) {echo 'selected';}?>><?php echo secureInput($rowEtat) ?></option>
                                                <?php } ?>
                                            </select>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                    <tr>
                                    <?php
                                    $i = -1;
                                    $sql = "select * from FORMULAIREQUESTION
                                        inner join FORMULAIREGROUPE using (ID_FORMULAIREGROUPE)
                                        where QST_ENTETE = 1 and ID_FORMULAIRE = ".$oFormulaire->getID()."
                                        order by QST_POIDS";
                                    foreach ($dbh->query($sql) as $rowQTY) {
                                        if ($i++ % 2 == 1) {
                                            echo '</tr><tr>';
                                        }
                                        $QTYname = 'QTY_' . $rowQTY['ID_FORMULAIREQUESTION']; ?>
                                        <?php if ($rowQTY['QTY_CODE'] == 'QTY_SELECT' || $rowQTY['QTY_CODE'] == 'QTY_LIST') { ?>
                                            <th>
                                                <label for="<?php echo $QTYname ?>"><?php echo secureInput($rowQTY['QST_LIBELLE']) ?></label>
                                            </th>
                                            <td>
                                                <select id="<?php echo $QTYname ?>" name="<?php echo $QTYname ?>">
                                                    <option value="">&nbsp;</option>
                                                    <?php foreach (explode("\n", $rowQTY['QST_VALEUR']) as $val) {
                                                        $val = str_replace("\r", '', $val); ?>
                                                        <option value="<?php echo secureInput($val)?>"<?php if ($_GET[$QTYname] == $val) echo ' selected';?>>
                                                            <?php echo secureInput($val)?>
                                                        </option>
                                                    <?php } ?>
                                                </select>
                                            </td>
                                        <?php } elseif ($rowQTY['QTY_CODE'] == 'QTY_RADIO') { ?>
                                            <th>
                                                <label><?php echo secureInput($rowQTY['QST_LIBELLE']) ?></label>
                                            </th>
                                            <td>
                                                <?php $i = 0;
                                                foreach (explode("\n", $rowQTY['QST_VALEUR']) as $value) {
                                                    $str = array("\r", "[X]");
                                                    $val = str_replace($str, '', $value);
                                                    $i ++; ?>
                                                    <input type="radio" name="<?php echo $QTYname ?>" id="<?php echo $QTYname . '_' . $i ?>" value="<?php echo secureInput($val)?>"<?php if($_GET[$QTYname] == $val) echo ' checked'?>>
                                                    <label for="<?php echo $QTYname . '_' . $i ?>"><?php echo secureInput($val)?></label>
                                                <?php } ?>
                                                <input type="radio" name="<?php echo $QTYname ?>" id="<?php echo $QTYname . '_0' ?>" value=""<?php if($_GET[$QTYname] == '') echo ' checked'?>>
                                                <label for="<?php echo $QTYname . '_0' ?>">N/A</label>
                                            </td>
                                        <?php } elseif ($rowQTY['QTY_CODE'] == 'QTY_CHECKBOX') { ?>
                                            <th>
                                                <label><?php echo secureInput($rowQTY['QST_LIBELLE']) ?></label>
                                            </th>
                                            <td>
                                                <?php $i = 0;
                                                $index   = 0;
                                                foreach (explode("\n", $rowQTY['QST_VALEUR']) as $value) {
                                                    $str = array("\r", "[X]");
                                                    $val = str_replace($str, '', $value);
                                                    $i ++; ?>
                                                    <input type="checkbox" name="<?php echo $QTYname . '[]' ?>" id="<?php echo $QTYname . '_' . $i ?>" value="<?php echo secureInput($val)?>"<?php if($_GET[$QTYname][$index] == $val) echo ' checked'?>>
                                                    <label for="<?php echo $QTYname . '_' . $i ?>"><?php echo secureInput($val)?></label>
                                                <?php if ($_GET[$QTYname][$index] == $val) $index ++;
                                                } ?>
                                            </td>
                                        <?php } else {
                                            if ($rowQTY['QTY_CODE'] == 'QTY_DATE') {
                                                $size = 20;
                                            } else {
                                                $size = 23;
                                            } ?>
                                            <th>
                                                <label for="<?php echo $QTYname ?>"><?php echo secureInput($rowQTY['QST_LIBELLE']) ?></label>
                                            </th>
                                            <td><input type="text" value="<?php echo secureInput($_GET[$QTYname]) ?>" name="<?php echo $QTYname ?>" id="<?php echo $QTYname ?>" size="<?php echo $size?>"></td>
                                         <?php } ?>
                                    <?php } ?>
                                    </tr>
                                </tbody>
                            </table>
                        </fieldset>
                    </form>

                  <?php
            echo $p->reglette();
            if ($p->getNb() > 0) {?>
                  <form method="post" action="frm_formulaireReponseSubmit.php" id="formListe" class="creation">
                      <table class="liste">
                        <thead>
                          <tr>
                            <th><input type="checkbox" onclick="$('.checkbox').each(function () {$(this).prop('checked', !$(this).prop('checked'))})"></th>
                            <th><?php echo $p->tri(gettext('Date'), 'REP_DATE');?></th>
                            <th><?php echo $p->tri(gettext('Etat'), 'REP_ETAT');?></th>
                            <?php if ($row['FRM_TRACABLE']) {?>
                            <th><?php echo $p->tri(gettext('Utilisateur'), 'UTI_PRENOM');?></th>
                            <?php } ?>
                            <?php
                            $_aID_QUESTION = array();
                            $sql = "select FORMULAIREQUESTION.* from FORMULAIREQUESTION
                                inner join FORMULAIREGROUPE using (ID_FORMULAIREGROUPE)
                                where QST_ENTETE=1 and ID_FORMULAIRE=".$oFormulaire->getID()." order by QST_POIDS";
                            foreach ($dbh->query($sql) as $rowTemp) {
                                $_aID_QUESTION[] = $rowTemp['ID_FORMULAIREQUESTION']; ?>
                                <th><?php echo secureInput($rowTemp['QST_LIBELLE'])?></th>
                            <?php } ?>
                            <th>Action</th>
                          </tr>
                        </thead>
                        <?php
                        $sql = "select distinct f.ID_FORMULAIREREPONSE, f.*, u.UTI_PRENOM, u.UTI_NOM, u.UTI_EMAIL from FORMULAIREREPONSE f
                                        left join UTILISATEUR u on f.ID_UTILISATEUR = u.ID_UTILISATEUR
                                        left join FORMULAIREREPONSEDETAIL fd on f.ID_FORMULAIREREPONSE = fd.ID_FORMULAIREREPONSE
                                        left join FORMULAIREQUESTION fq on fd.ID_FORMULAIREQUESTION = fq.ID_FORMULAIREQUESTION";
                        foreach ($p->fetch($sql) as $rowListe) { ?>
                        <tr>
                          <td class="aligncenter"><input type="checkbox" name="del_Reponse[]" value="<?php echo $rowListe['ID_FORMULAIREREPONSE']?>" class="checkbox"></td>
                          <td class="aligncenter"><a href="frm_formulaireReponse.php?idtf=<?php echo $rowListe['ID_FORMULAIREREPONSE']?>"><?php echo date("d/m/Y H:i", $rowListe['REP_DATE'])?></a></td>
                          <td class="aligncenter"><?php echo ($rowListe['REP_ETAT'] != '') ? secureInput($rowListe['REP_ETAT']) : '<em>Sans &eacute;tat</em>'?></td>
                          <?php
                          if ($row['FRM_TRACABLE']) {
                              if (!empty($rowListe['ID_UTILISATEUR'])) {
                                  if (Utilisateur::getConnected()->checkprofil(array('PRO_ROOT_SITE'))) { ?>
                                  <td><a href="/cms/administration/adm_utilisateur.php?idtf=<?php echo $rowListe['ID_UTILISATEUR']?>"><?php echo secureInput($rowListe['UTI_PRENOM'] . ' ' . $rowListe['UTI_NOM'] . ' [' . $rowListe['UTI_EMAIL'] . ']');?></a></td>
                          <?php } else { ?>
                                  <td><?php echo secureInput($rowListe['UTI_PRENOM'] . ' ' . $rowListe['UTI_NOM'] . ' [' . $rowListe['UTI_EMAIL'] . ']')?></td>
                          <?php
                                  }
                              } else echo '<td></td>';
                          }
                          ?>
                          <?php
                          if (count($_aID_QUESTION) > 0) {
                            $sql = "select fq.ID_FORMULAIREQUESTION, RED_VALEUR from FORMULAIREREPONSEDETAIL fd
                                              inner join FORMULAIREQUESTION fq using (ID_FORMULAIREQUESTION)
                                              inner join FORMULAIREGROUPE fg using (ID_FORMULAIREGROUPE)
                                              where fq.ID_FORMULAIREQUESTION in (" . implode(',',  $_aID_QUESTION) . ")
                                              and fd.ID_FORMULAIREREPONSE = " . $rowListe['ID_FORMULAIREREPONSE'] . "
                                              order by FMG_POIDS, QST_POIDS";
                            foreach ($dbh->query($sql) as $rowTemp) {
                                $_aREPONSE[$rowTemp['ID_FORMULAIREQUESTION']] = $rowTemp['RED_VALEUR'];
                            }
                            foreach ($_aID_QUESTION as $ID_FORMULAIREQUESTION) {?>
                                  <td><?php echo secureInput($_aREPONSE[$ID_FORMULAIREQUESTION])?></td>
                        <?php
                            }
                        }?>
                            <td class="aligncenter">
                                <a href="frm_formulaireReponseSubmit.php?Delete=<?php echo $rowListe['ID_FORMULAIREREPONSE']?>" class="actionSupprimer confirm">Supprimer</a>
                            </td>
                        </tr>
                        <?php
                    } ?>
                      </table>

                      <p class="action">
                          <input type="hidden" name="ID_FORMULAIRE" id="ID_FORMULAIRE" value="<?php echo $oFormulaire->getID() ?>">
                          <input type="button" name="deleteAll" value="Supprimer tous les éléments" class="supprimer confirm" title="Supprimer tous les éléments" data-href="frm_formulaireReponseSubmit.php?DeleteAll=<?php echo $oFormulaire->getID() ?>">
                          <input type="submit" name="massDelete" value="Supprimer les éléments sélectionnés" class="supprimer confirm" title="Supprimer les éléments sélectionnés">
                      </p>
                  </form>
            <?php } ?>
                </fieldset>
                <?php   }
                    }
                ?>
                <?php if (Utilisateur::getConnected()->checkProfil(array('PRO_FORMGEST'))) { ?>
                <?php
                $aReferants = $oFormulaire->getReferants();
                if (count($aReferants) > 0) { ?>
                <fieldset class="tab">
                    <legend><?php echo gettext('Affectations')?></legend>
                    <?php
                    if (count($aReferants['OFF_PARAGRAPHE']) > 0) {

                        $sql = "select ID_PAGE, PAG_TITRE_MENU from OFF_PAGE
                            inner join OFF_PARAGRAPHE using(ID_PAGE)
                            where ID_PARAGRAPHE in (" . implode(',', $aReferants['OFF_PARAGRAPHE']) . ")
                            group by ID_PAGE
                            order by PAG_TITRE_MENU";
                        $aPAGE = $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC); ?>
                    <h3><?php echo gettext('Espace de contribution')?></h3>
                    <table class="liste">
                        <tbody>
                            <tr>
                                <th><?php echo gettext('Numero')?></th>
                                <th><?php echo gettext('Page')?></th>
                            </tr>
                            <?php foreach ($aPAGE as $rowListe) { ?>
                            <tr>
                                <td class="alignright"><?php echo $rowListe['ID_PAGE'] ?></td>
                                <td><a href="../cms/cms_page.php?idtf=<?php echo $rowListe['ID_PAGE']?>"><?php echo secureInput($rowListe['PAG_TITRE_MENU'])?></a></td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                    <?php } ?>
                    <?php
                    if (count($aReferants['ON_PARAGRAPHE']) > 0) {
                        $sql = "select ID_PAGE, PAG_TITRE_MENU from ON_PAGE
                            inner join ON_PARAGRAPHE using(ID_PAGE)
                            where ID_PARAGRAPHE in (" . implode(',', $aReferants['ON_PARAGRAPHE']) . ")
                            group by ID_PAGE
                            order by PAG_TITRE_MENU";
                        $aPAGE = $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);?>
                    <h3>Site</h3>
                    <table class="liste">
                        <tbody>
                            <tr>
                                <th><?php echo gettext('Numero')?></th>
                                <th><?php echo gettext('Page')?></th>
                            </tr>
                            <?php foreach ($aPAGE as $rowListe) { ?>
                            <tr>
                                <td class="alignright"> <?php echo $rowListe['ID_PAGE'] ?> </td>
                                <td><a href="../cms/cms_page.php?idtf=<?php echo $rowListe['ID_PAGE']?>"><?php echo secureInput($rowListe['PAG_TITRE_MENU'])?></a></td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                    <?php } ?>
                    <?php
                    if (count($aReferants['REVISION_PARAGRAPHE']) > 0) {
                        $sql = "select REVISION_PAGE.ID_PAGE, REVISION_PAGE.PAG_TITRE_MENU from REVISION_PAGE
                            inner join REVISION_PARAGRAPHE using(ID_PAGE)
                            where REVISION_PARAGRAPHE.ID_PARAGRAPHE in (".implode(',', $aReferants['REVISION_PARAGRAPHE']).")
                            group by REVISION_PAGE.ID_PAGE
                            order by PAG_TITRE_MENU";
                        $aPAGE = $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);?>
                    <h3><?php echo gettext('Revisions')?></h3>
                    <table class="liste">
                        <tbody>
                            <tr>
                                <th><?php echo gettext('Numero')?></th>
                                <th><?php echo gettext('Page')?></th>
                            </tr>
                            <?php foreach ($aPAGE as $rowListe) { ?>
                            <tr>
                                <td class="alignright"> <?php echo $rowListe['ID_PAGE'] ?> </td>
                                <td><a href="../cms/cms_revisionListe.php?idtf=<?php echo $rowListe['ID_PAGE']?>"><?php echo secureInput($rowListe['PAG_TITRE_MENU'])?></a></td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                    <?php } ?>
                </fieldset>
                <?php } ?>
                <?php }?>
            </div>
        </div>
    </div>
    <?php include('../include/inc.bo_bandeau_bas.php') ?>
</div>

</body>
</html>
