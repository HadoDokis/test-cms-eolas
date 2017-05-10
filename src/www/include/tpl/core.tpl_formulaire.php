<?php
require_once CLASS_DIR . 'class.db_formulaire.php';
require_once CLASS_DIR . 'class.CMSMailer.php';
require_once CLASS_DIR . 'class.CMSCaptcha.php';
require_once CLASS_DIR . 'class.db_template.php';

$dbh = DB::getInstance();
$oPage = CMS::getCurrentSite()->getCurrentPage();
$oFormulaire = new Formulaire(Paragraphe::getCurrentTemplateRestriction());
if (! $oFormulaire->checkAuthorized(false)) {
    Paragraphe::noRender();
    return;
}
$oTemplate = Paragraphe::getCurrentTemplate();
$oTemplate->replaceProperties($oFormulaire);
$rowTPL = $oFormulaire->getFields();

if ($_GET['submit'] == 1 && sizeof($_POST) == 0) {
    $tabErreur[] = $oTemplate->i18n('for_total_fichier_trop_volumineux');
}
if ($_POST['Insert_tpl_formulaire_' . Paragraphe::getCurrentTemplateRestriction()] != '') {
    // Gestion des erreurs sur les champs
    $oModule = new Module('MOD_CORE');
    $aEmailDestinataireBO = array();

    $sql = "select FORMULAIREQUESTION.* from FORMULAIREQUESTION
            inner join FORMULAIREGROUPE using (ID_FORMULAIREGROUPE)
            inner join DD_FORMULAIREQUESTIONTYPE using (QTY_CODE)
            where ID_FORMULAIRE = " . intval(Paragraphe::getCurrentTemplateRestriction()) . "
            and QST_VISIBLE = 1
            order by FMG_POIDS, QST_POIDS";
    foreach ($dbh->query($sql) as $row) {
        $inputID = 'QST_' . $row['ID_FORMULAIREQUESTION'];
        $inputValue = is_array($_POST[$inputID]) ? implode("\n", $_POST[$inputID]) : $_POST[$inputID];

        if ($row['QTY_CODE'] != 'QTY_FILE') {
            if ($inputValue == '' && ($row['QST_OBLIGATOIRE'] || ($row['QTY_GROUPE'] == 'Captcha'))) {
                $tabErreur[$inputID] = $oModule->i18N('cor_le_champ_X_doit_etre_renseigne', array(
                    $row['QST_LIBELLE']
                ));
            }
        }

        switch ($row['QTY_CODE']) {
            case 'QTY_FILE':
                $aExtPermis = CMS::getCurrentSite()->getExtension('SIT_EXT_ALL');
                $aExtPermis = array_unique($aExtPermis);
                if ($_FILES[$inputID]['name'] != '' && $_FILES[$inputID]['error'] != UPLOAD_ERR_OK) {
                    $tabErreur[$inputID] = $oTemplate->i18n('for_fichier_trop_volumineux') . ' : ' . $row['QST_LIBELLE'];
                } elseif ($_FILES[$inputID]['size'] == 0 && $row['QST_OBLIGATOIRE']) {
                    $tabErreur[$inputID] = $oModule->i18N('cor_le_champ_X_doit_etre_renseigne', array(
                        $row['QST_LIBELLE']
                    ));
                } elseif ($_FILES[$inputID]['size'] != 0 && ! valideFile($_FILES[$inputID]['name'], $aExtPermis)) {
                    $tabErreur[$inputID] = $oTemplate->i18n('Extension_incorrecte') . ' : ' . $row['QST_LIBELLE'] . '[' . implode(', ', $aExtPermis) . ']';
                }
                break;

            case 'QTY_EMAIL':
            case 'QTY_EMAIL_NOTIF':
                if ($inputValue != '' && ! valideMail($inputValue)) {
                    $tabErreur[$inputID] = $oModule->i18N('cor_le_champ_X_n_est_pas_un_email_valide', array(
                        $row['QST_LIBELLE']
                    ));
                }
                break;

            case 'QTY_DATE':
                if ($inputValue != '' && ! valideDate($inputValue, CMS::getCurrentSite()->getField('SIT_LANGUE'))) {
                    $tabErreur[$inputID] = $oModule->i18N('cor_le_champ_X_n_est_pas_une_date_valide', array(
                        $row['QST_LIBELLE']
                    ));
                }
                break;

            case 'QTY_CAPTCHAGRAPHIC':
            case 'QTY_CAPTCHANUMERIC':
            case 'QTY_CAPTCHANUMERICMINUS':
                if ($inputValue != '' && ! CMSCaptcha::check($_POST['ID_CAPTCHA_' . $row['ID_FORMULAIREQUESTION']], $inputValue)) {
                    $tabErreur[$inputID] = $oModule->i18N('cor_le_champ_X_n_est_pas_un_captcha_valide', array(
                        $row['QST_LIBELLE']
                    ));
                }
                break;

            case 'QTY_SELECTEMAIL' :
                $_pattern = $inputValue . '[';
                $_patternLength = strlen($_pattern);
                foreach (explode("\n", $row['QST_VALEUR']) as $val) {
                    $val = str_replace("\r", '', $val);
                    if (substr($val, 0, $_patternLength) == $_pattern) {
                        $email = substr($val, $_patternLength, strpos($val, ']') - $_patternLength);
                        if (valideMail($email)) {
                            $aEmailDestinataireBO[] = $email;
                        } else {
                            die($email);
                        }
                    }
                }
                break;
        }
    }

    if (count($tabErreur) == 0) {
        $stmt = $dbh->prepare("insert into FORMULAIREREPONSE (
            ID_FORMULAIRE,
            ID_UTILISATEUR,
            REP_IPUTILISATEUR,
            REP_DATE,
            REP_ETAT
            ) values (
            :PAR_TPL_IDENTIFIANT,
            :ID_UTILISATEUR,
            :REP_IPUTILISATEUR,
            :REP_DATE,
            :FRM_ETATREPONSE
            )");
        $stmt->bindValue(':PAR_TPL_IDENTIFIANT', Paragraphe::getCurrentTemplateRestriction(), PDO::PARAM_STR);
        $stmt->bindValue(':ID_UTILISATEUR', (($rowTPL['FRM_TRACABLE'] == '1' && is_numeric($_SESSION['S_ID_UTILISATEUR'])) ? $_SESSION['S_ID_UTILISATEUR'] : null), PDO::PARAM_INT);
        $stmt->bindValue(':REP_IPUTILISATEUR', (($rowTPL['FRM_TRACEIP'] == '1') ? $_SERVER['REMOTE_ADDR'] : null), PDO::PARAM_INT);
        $stmt->bindValue(':REP_DATE', time(), PDO::PARAM_INT);
        $stmt->bindValue(':FRM_ETATREPONSE', reset(explode("\n", $rowTPL['FRM_ETATREPONSE'])), PDO::PARAM_STR);
        $stmt->execute();
        $ID_FORMULAIREREPONSE = $dbh->lastInsertID();
        // enregistement des réponses
        $CORPS_MAIL = '';
        $PJ = array();
        $sql = "select *
            from FORMULAIREQUESTION
            inner join FORMULAIREGROUPE using(ID_FORMULAIREGROUPE)
            inner join DD_FORMULAIREQUESTIONTYPE using (QTY_CODE)
            where ID_FORMULAIRE=" . intval(Paragraphe::getCurrentTemplateRestriction()) . "
            and QST_VISIBLE=1
            and FORMULAIREQUESTION.QTY_CODE<>'QTY_INFORMATION' and QTY_GROUPE<>'Captcha'
            order by FMG_POIDS, QST_POIDS";
        $aFORMULAIREQUESTION = $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        foreach ($aFORMULAIREQUESTION as $FORMULAIREQUESTION) {
            if ($FORMULAIREQUESTION['QTY_CODE'] == 'QTY_FILE') {
                if ($_FILES['QST_' . $FORMULAIREQUESTION['ID_FORMULAIREQUESTION']]['size'] > 0) {
                    $ext = mb_strtolower(mb_strrchr($_FILES['QST_' . $FORMULAIREQUESTION['ID_FORMULAIREQUESTION']]['name'], "."));
                    $RED_VALEUR = 'RED_' . $ID_FORMULAIREREPONSE . '_' . $FORMULAIREQUESTION['ID_FORMULAIREQUESTION'] . '_' . time() . $ext;
                    move_uploaded_file($_FILES['QST_' . $FORMULAIREQUESTION['ID_FORMULAIREQUESTION']]['tmp_name'], UPLOAD_FORMULAIRE_PHYSIQUE . $RED_VALEUR);
                    chmod(UPLOAD_FORMULAIRE_PHYSIQUE . $RED_VALEUR, 0644);
                    $CORPS_MAIL .= $FORMULAIREQUESTION['QST_LIBELLE'] . ' : ' . $oTemplate->i18n('Piece_jointe') . chr(13);
                    $PJ[] = array(
                        'PATH' => UPLOAD_FORMULAIRE_PHYSIQUE . $RED_VALEUR,
                        'NAME' => $_FILES['QST_' . $FORMULAIREQUESTION['ID_FORMULAIREQUESTION']]['name']
                    );
                } else {
                    $RED_VALEUR = '';
                    $CORPS_MAIL .= $FORMULAIREQUESTION['QST_LIBELLE'] . ' : ' . $oTemplate->i18n('Aucun_fichier') . chr(13);
                }
            } else {
                $RED_VALEUR = is_array($_POST['QST_' . $FORMULAIREQUESTION['ID_FORMULAIREQUESTION']]) ? implode(",", $_POST['QST_' . $FORMULAIREQUESTION['ID_FORMULAIREQUESTION']]) : '';
                $CORPS_MAIL .= secureInput($FORMULAIREQUESTION['QST_LIBELLE'] . ' : ' . $RED_VALEUR) . chr(13);
            }
            $stmt = $dbh->prepare("insert into FORMULAIREREPONSEDETAIL (
                ID_FORMULAIREREPONSE,
                ID_FORMULAIREQUESTION,
                RED_VALEUR
                ) values (
                :ID_FORMULAIREREPONSE,
                :ID_FORMULAIREQUESTION,
                :RED_VALEUR
                )");
            $stmt->bindValue(':ID_FORMULAIREREPONSE', $ID_FORMULAIREREPONSE, PDO::PARAM_INT);
            $stmt->bindValue(':ID_FORMULAIREQUESTION', $FORMULAIREQUESTION['ID_FORMULAIREQUESTION'], PDO::PARAM_INT);
            $stmt->bindValue(':RED_VALEUR', $RED_VALEUR, PDO::PARAM_STR);
            $stmt->execute();
        }

        // Notification aux contributeurs
        if ($oFormulaire->hasBackNotification()) {
            $aEmailDestinataireBO = array_merge($aEmailDestinataireBO, $oFormulaire->getBackNotification());
        }

        // Notification aux intervenants supplémentaires
        if ($oFormulaire->getField('FRM_NOTIFICATION_EMAIL') != '') {
             foreach (explode(';', $oFormulaire->getField('FRM_NOTIFICATION_EMAIL')) as $email) {
                $email = trim($email);
                if (valideMail($email)) {
                    $aEmailDestinataireBO[] = $email;
                }
            }
        }

        if (! empty($aEmailDestinataireBO)) {
            $oMail = new CMSMailer('EMT_FRM_NOTIF_BO');
            $oMail->SetFrom($oFormulaire->getField('FRM_EXPEDITEUR_EMAIL'), $oFormulaire->getField('FRM_EXPEDITEUR_NOM'));
            $oMail->replace('[FRM_LIBELLE]', secureInput($rowTPL['FRM_LIBELLE']));
            $CORPS_MAIL .= '<a href="http://' . CMS::getCurrentSite()->getField('SIT_HOST') . SERVER_ROOT . 'formulaire/frm_formulaireReponse.php?idtf=' . $ID_FORMULAIREREPONSE . '">http://' . CMS::getCurrentSite()->getField('SIT_HOST') . SERVER_ROOT . 'formulaire/frm_formulaireReponse.php?idtf=' . $ID_FORMULAIREREPONSE . '</a>' . chr(13);
            $oMail->replace('[REPONSES]', nl2br($CORPS_MAIL));
            foreach ($oFormulaire->getNotificationReplyToEmails($ID_FORMULAIREREPONSE) as $email) {
                $oMail->AddReplyTo($email);
            }
            foreach ($aEmailDestinataireBO as $email) {
                $oMail->AddAddress($email);
            }
            foreach ($PJ as $val) {
                $oMail->AddAttachment($val['PATH'], $val['NAME']);
            }
            $oMail->send();
        }

        // Accusé réception
        if ($oFormulaire->hasFrontNotification() && count($oFormulaire->getFrontNotification($ID_FORMULAIREREPONSE)) > 0) {
            $oMail = new CMSMailer('EMT_FRM_NOTIF_FO');
            $oMail->SetFrom($oFormulaire->getField('FRM_EXPEDITEUR_EMAIL'), $oFormulaire->getField('FRM_EXPEDITEUR_NOM'));
            $oMail->replace('[FRM_LIBELLE]', secureInput($rowTPL['FRM_LIBELLE']));
            $oMail->replace('[FRM_ACCUSERECEPTION]', $rowTPL['FRM_ACCUSERECEPTION']);
            foreach ($aFORMULAIREQUESTION as $FORMULAIREQUESTION) {
                if ($FORMULAIREQUESTION['QTY_CODE'] != 'QTY_FILE') {
                    $RED_VALEUR = is_array($_POST['QST_' . $FORMULAIREQUESTION['ID_FORMULAIREQUESTION']]) ? implode(", ", $_POST['QST_' . $FORMULAIREQUESTION['ID_FORMULAIREQUESTION']]) : '';
                    $oMail->replace('[QST_' . $FORMULAIREQUESTION['ID_FORMULAIREQUESTION'] . ']', secureINput($RED_VALEUR));
                }
            }
            foreach ($PJ as $val) {
                $oMail->AddAttachment($val['PATH'], $val['NAME']);
            }
            foreach ($oFormulaire->getFrontNotification($ID_FORMULAIREREPONSE) as $email) {
                $oMail->clearAddresses();
                $oMail->AddAddress($email);
                $oMail->Send();
            }
        }
        $oPage->redirect(array(
            'Insert_tpl_formulaire_' . $oFormulaire->getID() => 1,
            'TPL_CODE' => 'TPL_FORMULAIRE',
            'PAR_TPL_IDENTIFIANT' => $oFormulaire->getID()
        ));
    }
}
?>
<div id="tpl_formulaire<?php echo $oFormulaire->getID()?>">
<?php if (isset($_GET['Insert_tpl_formulaire_' . Paragraphe::getCurrentTemplateRestriction()])) { ?>
    <p><?php echo encode($rowTPL['FRM_MESSAGEREPONSE']) ?></p>
    <p><a <?php echo $oPage->getAnchor()?>><?php echo $oTemplate->i18n('Retour_formulaire')?></a></p>
    <?php
    if (CMS::$mode == 'ON_' && CMS::getCurrentSite()->getField('SIT_GA_TAG') != '') {
        CMS::addDOMREADY('
            $(window).load(function () { // Sur le load pour s\'assurer de la présence de l\'objet \'ga\'
                if (ga) {
                    var location = window.location.protocol + \'//\' + window.location.hostname + window.location.pathname; /*pas le location.search sur les form pour ne pas avoir le "?submit=1" + window.location.search;*/
                    ga(\'send\', \'event\', \'Formulaire\', \'' . escapeJS($rowTPL['FRM_LIBELLE']) . '\', {\'eventLabel\': location, \'nonInteraction\': 1});
                }
            });
         ');
    }
    ?>
<?php } else { ?>
    <form method="post" action="<?php echo $oPage->getURLESCAPE(array('submit'=>1)) ?>#tpl_formulaire<?php echo $oFormulaire->getID()?>" enctype="multipart/form-data" id="cms_formdynamique_<?php echo $oFormulaire->getID() ?>">
        <?php if (! empty($tabErreur)) {
                CMS::replaceTITLE($oTemplate->i18n("erreur_traitement") . ' ',  'BEFORE');?>
        <ul class="form_error">
            <?php foreach ($tabErreur as $label => $error) { ?>
            <li><label for="<?php echo $label?>"><?php echo $error?></label></li>
            <?php }?>
        </ul>
        <?php } ?>
        <?php $oFormulaire->displayHTMLContent($oPage); ?>
    </form>
<?php } ?>
</div>
