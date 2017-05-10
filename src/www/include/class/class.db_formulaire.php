<?php
require_once CLASS_DIR . 'class.db_generic.php';

class Formulaire extends Generic
{

    private $_deletable = null;

    private $_frontNotification = null;

    private $_fo_EmailsNotification = null;

    private $_backNotification = null;

    // Liste des emails associés aux contributeurs (sont exclut les mails de notification suplémentaires)
    private $_bo_EmailsNotification = null;

    // Liste des emails utilisés en replyto lors de la génération des notifications backoffice suite au dépôt d'une réponse
    private $_replyto_EmailsNotification = null;

    private $_aReferant = null;

    public static function getModuleCode()
    {
        return 'MOD_FORMULAIRE';
    }

    public function load()
    {
        $sql = "select * from FORMULAIRE where ID_FORMULAIRE=" . $this->getID();
        if ($row = $this->dbh->query($sql)->fetch(PDO::FETCH_ASSOC)) {
            $this->setFields($row);
        } else {
            $this->_idtf = - 1;
            $this->setFields(array());
        }
    }

    /**
     * Renvoi un tableau contenant l'ensemble des paragraphes contenant le formulaire
     *
     * @return array
     */
    public function getReferants()
    {
        if ($this->_aReferant === null) {
            $this->_aReferant = array();

            $sql = 'select distinct(ID_PARAGRAPHE)
                    from OFF_PARAGRAPHE
                    where TPL_CODE = \'TPL_FORMULAIRE\'
                    and PAR_TPL_IDENTIFIANT=' . $this->getID();

            $rowListe = $this->dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN);
            if (! empty($rowListe)) {
                $this->_aReferant['OFF_PARAGRAPHE'] = $rowListe;
            }

            $sql = 'select distinct(ID_PARAGRAPHE)
                    from ON_PARAGRAPHE
                    where TPL_CODE = \'TPL_FORMULAIRE\'
                    and PAR_TPL_IDENTIFIANT=' . $this->getID();

            $rowListe = $this->dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN);
            if (! empty($rowListe)) {
                $this->_aReferant['ON_PARAGRAPHE'] = $rowListe;
            }

            $sql = 'select distinct(ID_PARAGRAPHE)
                    from REVISION_PARAGRAPHE
                    where TPL_CODE = \'TPL_FORMULAIRE\'
                    and PAR_TPL_IDENTIFIANT=' . $this->getID();

            $rowListe = $this->dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN);
            if (! empty($rowListe)) {
                $this->_aReferant['REVISION_PARAGRAPHE'] = $rowListe;
            }
        }

        return $this->_aReferant;
    }

    public function isDeletable()
    {
        if ($this->_deletable == null) {
            $nb = 0;
            foreach ($this->getReferants() as $tab) {
                $nb += count($tab);
            }
            $nb += $this->getNbResults();
            $this->_deletable = ($nb == 0);
        }

        return $this->_deletable;
    }

    public function delete($force = false)
    {
        // On charge l'objet car on doit accéder à ses propriétés une fois l'enregistrement supprimé de la base de données
        $this->load();

        if (! $force && ! $this->isDeletable()) {
            return false;
        }

        if ($force) {
            require_once CLASS_DIR . 'class.db_page.php';
            require_once CLASS_DIR . 'class.db_paragraphe.php';
            require_once CLASS_DIR . 'class.db_revision.php';

            $this->deleteAllReponses();
            $sql = "select * from ON_PARAGRAPHE where TPL_CODE = 'TPL_FORMULAIRE' and PAR_TPL_IDENTIFIANT=" . $this->getID();
            foreach ($this->dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $oParagraphe = new Paragraphe($row['ID_PARAGRAPHE'], 'ON_');
                $oParagraphe->setFields($row);
                $oParagraphe->delete();
            }
            $sql = "select * from OFF_PARAGRAPHE where TPL_CODE = 'TPL_FORMULAIRE' and PAR_TPL_IDENTIFIANT=" . $this->getID();
            foreach ($this->dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $oParagraphe = new Paragraphe($row['ID_PARAGRAPHE'], 'OFF_');
                $oParagraphe->setFields($row);
                $oParagraphe->delete();
            }
            $sql = "select * from REVISION_PARAGRAPHE where TPL_CODE = 'TPL_FORMULAIRE' and PAR_TPL_IDENTIFIANT=" . $this->getID();
            foreach ($this->dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $oRevision = new Revision($row['ID_REVISION']);
                $oParagraphe = new Paragraphe_Revision($row['ID_PARAGRAPHE'], $oRevision);
                $oParagraphe->setFields($row);
                $oParagraphe->delete();
            }
        }

        $sql = "delete from FORMULAIREQUESTION where ID_FORMULAIREGROUPE in (select ID_FORMULAIREGROUPE from FORMULAIREGROUPE where ID_FORMULAIRE= " . $this->getID() . ")";
        $this->dbh->exec($sql);
        $sql = "delete from FORMULAIREGROUPE where ID_FORMULAIRE=" . $this->getID();
        $this->dbh->exec($sql);
        $sql = "delete from FORMULAIRE_UTILISATEUR where ID_FORMULAIRE=" . $this->getID();
        $this->dbh->exec($sql);

        $this->historize('SUPPRESSION', 'FORMULAIRE');
        $sql = "delete from FORMULAIRE where ID_FORMULAIRE=" . $this->getID();
        $this->dbh->exec($sql);

        return true;
    }

    /**
     * Supprime une réponse du formulaire (ATTENTION, la méthode ne fait pas appel à $this->isAuthorized)
     *
     * @param Int $ID_FORMULAIREREPONSE
     *                                  Identifiant de la réponse à supprimer
     */
    public function deleteReponse($ID_FORMULAIREREPONSE)
    {
        // On récupère les valeurs des réponses aux questions de type file
        $sql = "select RED_VALEUR from FORMULAIREREPONSEDETAIL
            where ID_FORMULAIREREPONSE = " . intval($ID_FORMULAIREREPONSE) . " and ID_FORMULAIREQUESTION in (
                select q.ID_FORMULAIREQUESTION from FORMULAIREQUESTION q
                inner join FORMULAIREGROUPE g on q.ID_FORMULAIREGROUPE = g.ID_FORMULAIREGROUPE
                where g.ID_FORMULAIRE=" . $this->getID() . " and q.QTY_CODE='QTY_FILE')";
        // On efface les fichiers
        foreach ($this->dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN) as $RED_VALEUR) {
            @ unlink(UPLOAD_FORMULAIRE_PHYSIQUE . $RED_VALEUR);
        }

        $sql = "delete from FORMULAIREREPONSEDETAIL where ID_FORMULAIREREPONSE=" . intval($ID_FORMULAIREREPONSE);
        $this->dbh->exec($sql);
        $sql = "delete from FORMULAIREREPONSE where ID_FORMULAIREREPONSE=" . intval($ID_FORMULAIREREPONSE);
        $this->dbh->exec($sql);
    }

    public function deleteAllReponses()
    {
        $sql = "select ID_FORMULAIREREPONSE from FORMULAIREREPONSE where ID_FORMULAIRE=" . $this->getID();
        foreach ($this->dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN) as $ID_FORMULAIREREPONSE) {
            $this->deleteReponse($ID_FORMULAIREREPONSE);
        }
    }

    /**
     * Renvoi le rendu du formulaire en HTML
     */
    public function displayHTMLContent($oPage)
    {
        // Instanciation de l'objet module pour les traductions
        $oModule = new Module($this->getModuleCode());

        $renderContent = '';
        $sql = "select * from FORMULAIREQUESTION
            inner join FORMULAIREGROUPE using(ID_FORMULAIREGROUPE)
            where FORMULAIREGROUPE.ID_FORMULAIRE=" . $this->getID() . " and QST_VISIBLE=1 and FMG_VISIBLE = 1
            order by FMG_POIDS, QST_POIDS";
        $GRP_ID_FORMULAIREGROUPE = - 1;
        $_GRP_LIBELLEVISIBLE = - 1;
        $champsObligatoire = false;
        foreach ($this->dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $rowListe) {
            if ($GRP_ID_FORMULAIREGROUPE != $rowListe['ID_FORMULAIREGROUPE']) {
                if ($GRP_ID_FORMULAIREGROUPE != - 1) {
                    $renderContent .= $_GRP_LIBELLEVISIBLE ? '</div></fieldset>' : '</div></div>';
                }
                $GRP_ID_FORMULAIREGROUPE = $rowListe['ID_FORMULAIREGROUPE'];
                $_GRP_LIBELLEVISIBLE = $rowListe['FMG_LIBELLEVISIBLE'];
                $renderContent .= ($_GRP_LIBELLEVISIBLE)
                    ? '<fieldset class="groupeQuestion"><legend><span>'.secureInput($rowListe['FMG_LIBELLE']).'</span></legend><div class="innerGroupeQuestion">'
                    : '<div class="groupeQuestion"><div class="innerGroupeQuestion">';
            }
            $renderContent .= self::getQuestionRender($rowListe['ID_FORMULAIREQUESTION'], $_POST, $champsObligatoire);
        }
        if ($_GRP_LIBELLEVISIBLE != - 1) {
            $renderContent .= $_GRP_LIBELLEVISIBLE ? '</div></fieldset>' : '</div></div>';
        }

        if ($champsObligatoire) {
            $renderContent .= '<p class="notice">' . $oModule->i18n('champs_obligatoire') . '</p>';
        }

        $renderContent .= '<p class="action">
                                <input type="hidden" value="' . $oPage->getID() . '" name="idtf">
                                <input type="hidden" value="TPL_FORMULAIRE" name="TPL_CODE">
                                <input type="hidden" value="' . $this->getID() . '" name="PAR_TPL_IDENTIFIANT">
                                <span class="submit"><input type="submit" name="Insert_tpl_formulaire_' . $this->getID() . '" value="' . encode($this->getField('FRM_LIBELLE_BOUTON'), false) . '" class="submit"></span>
                           </p>';

        if ($this->getField('FRM_MENTION_CNIL') == 1) {
            $renderContent .= '<p class="mention_cnil">' . $oModule->i18n('mention_cnil') . '</p>';
        }

        echo $renderContent;
    }

    /**
     * Renvoi le rendu HTML d'une question
     */
    private static function getQuestionRender($ID_FORMULAIREQUESTION, $aFORMULAIREREPONSE = array(), &$champsObligatoire = false)
    {
        $dbh = DB::getInstance();
        $sql = "select * from FORMULAIREQUESTION where ID_FORMULAIREQUESTION=" . intval($ID_FORMULAIREQUESTION);
        $row = $dbh->query($sql)->fetch(PDO::FETCH_ASSOC);
        $oModule = new Module(self::getModuleCode());

        /* Gestion du label */
        $renderContent = '<p>';
        if ($row['QST_LIBELLEVISIBLE'] && $row['QTY_CODE'] != 'QTY_INFORMATION') {
            $renderContent .= '<label';
            if (! in_array($row['QTY_CODE'], array('QTY_CHECKBOX', 'QTY_RADIO'))) {
                $renderContent .= ' for="QST_' . $row['ID_FORMULAIREQUESTION'] . '"';
                $sInputID = 'QST_' . $row['ID_FORMULAIREQUESTION'];
            } else {
                // le label est associé explicitement à la 1ere valeur par défaut
                $QST_VALEUR = explode("\n", $row['QST_VALEUR']);
                $cpt = 0;
                $bWithDefaultValue = false;
                foreach ($QST_VALEUR as $val) {
                    $cpt ++;
                    $val = str_replace("\r", '', $val);
                    if (is_int(mb_strpos($val, '[X]'))) {
                        $renderContent .= ' for="QST_' . $row['ID_FORMULAIREQUESTION'] . '_' . $cpt . '"';
                        $sInputID = 'QST_' . $row['ID_FORMULAIREQUESTION'] . '_' . $cpt;
                        $bWithDefaultValue = true;
                        break;
                    }
                }
                // Si pas de valeur par défaut, le label est associé à la première valeur
                if (! $bWithDefaultValue) {
                    $renderContent .= ' for="QST_' . $row['ID_FORMULAIREQUESTION'] . '_1"';
                    $sInputID = 'QST_' . $row['ID_FORMULAIREQUESTION'] . '_1';
                }
            }
            $aClass = array();
            if ($row['QST_OBLIGATOIRE']) {
                $champsObligatoire = true;
            }
            if (count($aClass) > 0) {
                $renderContent .= ' class="' . implode(' ', $aClass) . '"';
            }
            $renderContent .= '>' . encode($row['QST_LIBELLE']);
            if ($row['QTY_CODE'] == 'QTY_FILE') {
                require_once CLASS_DIR . 'class.File_management.php';
                $renderContent .= ' <small>' . $oModule->i18n('for_texte_taille_max_upload', array(File_management::getMaxUpload()), true) . '</small>';
            }
            if ($row['QST_MESSAGEAIDE']) {
                $renderContent .= ' <span class="helper">' . encode($row['QST_MESSAGEAIDE'], false) . '</span>';
            }
            $renderContent .= '</label>';
        }
        /* Affichage du composant de formulaire */
        $QST_VALEUR = '';
        switch ($row['QTY_CODE']) {
            case 'QTY_INFORMATION':
                $renderContent .= '<span class="info">' . encode($row['QST_COMMENTAIRE']) . '</span>';
                break;
            case 'QTY_SELECT':
            case 'QTY_SELECTEMAIL':
            case 'QTY_LIST':
                $QST_VALEUR = explode("\n", $row['QST_VALEUR']);
                $renderContent .= '<select name="QST_' . $row['ID_FORMULAIREQUESTION'] . '[]" id="QST_' . $row['ID_FORMULAIREQUESTION'] . '"';
                if ($row['QTY_CODE'] == 'QTY_LIST') {
                    $renderContent .= ' class="list"';
                    $renderContent .= ($row['QST_HEIGHT'] != '') ? ' size="' . $row['QST_HEIGHT'] . '"' : ' size="' . count($QST_VALEUR) . '"';
                    if ($row['QST_MULTIPLE']) {
                        $renderContent .= ' multiple';
                    }
                }
                if ($row['QST_OBLIGATOIRE']) {
                    $renderContent .= ' required';
                }
                $renderContent .= '>';
                foreach ($QST_VALEUR as $val) {
                    $renderContent .= '<option';
                    $val = $valBis = str_replace("\r", '', $val);
                    if ($row['QTY_CODE'] == 'QTY_SELECTEMAIL') {
                        $val = substr($val, 0, strpos($val, '['));
                    }
                    if (is_array($aFORMULAIREREPONSE['QST_' . $row['ID_FORMULAIREQUESTION']]) && in_array(str_replace('[X]', '', $val), $aFORMULAIREREPONSE['QST_' . $row['ID_FORMULAIREQUESTION']])) {
                        $renderContent .= ' selected';
                    } elseif (! isset($aFORMULAIREREPONSE['QST_' . $row['ID_FORMULAIREQUESTION']]) && is_int(strpos($valBis, '[X]'))) {
                        $renderContent .= ' selected';
                    }
                    $val = secureInput(str_replace('[X]', '', $val));
                    $renderContent .= ' value="' . $val . '">' . $val . '</option>';
                }
                $renderContent .= '</select>';
                break;
            case 'QTY_CHECKBOX':
            case 'QTY_RADIO':
                $QST_VALEUR = explode("\n", $row['QST_VALEUR']);
                $cpt = 0;
                if ($row['QST_LIBELLEVISIBLE']) {
                    $renderContent .= '<span class="cases">';
                }
                foreach ($QST_VALEUR as $val) {
                    $cpt ++;
                    $val = str_replace("\r", '', $val);
                    $renderContent .= '<input class="case" type="' . (($row['QTY_CODE'] == 'QTY_CHECKBOX') ? 'checkbox' : 'radio') . '" id="QST_' . $row['ID_FORMULAIREQUESTION'] . '_' . $cpt . '"';
                    $renderContent .= ' name="QST_' . $row['ID_FORMULAIREQUESTION'] . '[]"';
                    if (@in_array(trim(str_replace('[X]', '', $val)), $aFORMULAIREREPONSE['QST_' . $row['ID_FORMULAIREQUESTION']])) {
                        $renderContent .= ' checked';
                    } elseif (! isset($aFORMULAIREREPONSE['QST_' . $row['ID_FORMULAIREQUESTION']]) && is_int(mb_strpos($val, '[X]'))) {
                        $renderContent .= ' checked';
                    }
                    if ($row['QST_OBLIGATOIRE'] && ($row['QTY_CODE'] == 'QTY_RADIO' || count($QST_VALEUR) == 1)) {
                        //voir comment gérer le coté "obligatoire" sur les cases à cocher multiple
                        $renderContent .= ' required';
                    }
                    $newLine = is_int(mb_strpos($val, '[R]'));
                    $val = secureInput(trim(str_replace(array('[X]', '[R]'), '', $val)));
                    $renderContent .= ' value="' . $val . '"><label for="QST_' . $row['ID_FORMULAIREQUESTION'] . '_' . $cpt . '" class="enLigne">' . $val . '</label>';
                    if ($newLine) {
                        $renderContent .= '<br>';
                    }
                }
                if ($row['QST_LIBELLEVISIBLE']) {
                    $renderContent .= '</span>';
                }
                break;
            case 'QTY_TEXT':
                $renderContent .= '<input name="QST_' . $row['ID_FORMULAIREQUESTION'] . '[]" id="QST_' . $row['ID_FORMULAIREQUESTION'] . '" type="text"';
                $renderContent .= ' value="' . secureInput($aFORMULAIREREPONSE['QST_' . $row['ID_FORMULAIREQUESTION']][0]) . '"';
                if (! empty($row['QST_WIDTH'])) {
                    $renderContent .= ' size="' . $row['QST_WIDTH'] . '"';
                }
                if (! empty($row['QST_MAXLENGTH'])) {
                    $renderContent .= ' maxlength="' . $row['QST_MAXLENGTH'] . '"';
                }
                if ($row['QST_OBLIGATOIRE']) {
                    $renderContent .= ' required';
                }
                if (! empty($row['QST_PLACEHOLDER'])) {
                    $renderContent .= ' placeholder="' . secureInput($row['QST_PLACEHOLDER']) . '"';
                }
                $renderContent .= '>';
                break;
            case 'QTY_EMAIL':
            case 'QTY_EMAIL_NOTIF':
                $renderContent .= '<input name="QST_' . $row['ID_FORMULAIREQUESTION'] . '[]" id="QST_' . $row['ID_FORMULAIREQUESTION'] . '" type="email"';
                $renderContent .= ' value="' . secureInput($aFORMULAIREREPONSE['QST_' . $row['ID_FORMULAIREQUESTION']][0]) . '"';
                if (! empty($row['QST_WIDTH'])) {
                    $renderContent .= ' size="' . $row['QST_WIDTH'] . '"';
                }
                if (! empty($row['QST_MAXLENGTH'])) {
                    $renderContent .= ' maxlength="' . $row['QST_MAXLENGTH'] . '"';
                }
                if ($row['QST_OBLIGATOIRE']) {
                    $renderContent .= ' required';
                }
                if (! empty($row['QST_PLACEHOLDER'])) {
                    $renderContent .= ' placeholder="' . secureInput($row['QST_PLACEHOLDER']) . '"';
                }
                $renderContent .= '>';
                break;
            case 'QTY_DATE':
                CMS::addJS(SERVER_ROOT . 'include/js/jquery/ui/jquery-ui.min.js');
                CMS::addCSS(SERVER_ROOT . 'include/js/jquery/ui/jquery-ui.min.css');
                $renderContent .= '<input name="QST_' . $row['ID_FORMULAIREQUESTION'] . '[]" id="QST_' . $row['ID_FORMULAIREQUESTION'] . '" type="text"';
                if (! empty($aFORMULAIREREPONSE['QST_' . $row['ID_FORMULAIREQUESTION']][0])) {
                    $renderContent .= ' value="' . secureInput($aFORMULAIREREPONSE['QST_' . $row['ID_FORMULAIREQUESTION']][0]) . '"';
                }
                if (! empty($row['QST_MAXLENGTH'])) {
                    $renderContent .= ' maxlength="' . $row['QST_MAXLENGTH'] . '"';
                }
                if ($row['QST_OBLIGATOIRE']) {
                    $renderContent .= ' required';
                }
                if (! empty($row['QST_PLACEHOLDER'])) {
                    $renderContent .= ' placeholder="' . secureInput($row['QST_PLACEHOLDER']) . '"';
                }
                $renderContent .= ' data-type="date">';
                break;
            case 'QTY_TEL':
                $renderContent .= '<input name="QST_' . $row['ID_FORMULAIREQUESTION'] . '[]" id="QST_' . $row['ID_FORMULAIREQUESTION'] . '" type="tel"';
                if (! empty($aFORMULAIREREPONSE['QST_' . $row['ID_FORMULAIREQUESTION']][0])) {
                    $renderContent .= ' value="' . secureInput($aFORMULAIREREPONSE['QST_' . $row['ID_FORMULAIREQUESTION']][0]) . '"';
                }
                if (! empty($row['QST_MAXLENGTH'])) {
                    $renderContent .= ' maxlength="' . $row['QST_MAXLENGTH'] . '"';
                }
                if ($row['QST_OBLIGATOIRE']) {
                    $renderContent .= ' required';
                }
                if (! empty($row['QST_PLACEHOLDER'])) {
                    $renderContent .= ' placeholder="' . secureInput($row['QST_PLACEHOLDER']) . '"';
                }
                $renderContent .= '>';
                break;
            case 'QTY_URL':
                $renderContent .= '<input name="QST_' . $row['ID_FORMULAIREQUESTION'] . '[]" id="QST_' . $row['ID_FORMULAIREQUESTION'] . '" type="url"';
                $renderContent .= ' value="' . secureInput($aFORMULAIREREPONSE['QST_' . $row['ID_FORMULAIREQUESTION']][0]) . '"';
                if (! empty($row['QST_WIDTH'])) {
                    $renderContent .= ' size="' . $row['QST_WIDTH'] . '"';
                }
                if (! empty($row['QST_MAXLENGTH'])) {
                    $renderContent .= ' maxlength="' . $row['QST_MAXLENGTH'] . '"';
                }
                if ($row['QST_OBLIGATOIRE']) {
                    $renderContent .= ' required';
                }
                if (! empty($row['QST_PLACEHOLDER'])) {
                    $renderContent .= ' placeholder="' . secureInput($row['QST_PLACEHOLDER']) . '"';
                }
                $renderContent .= '>';
                break;
            case 'QTY_TEXTAREA':
                $renderContent .= '<textarea name="QST_' . $row['ID_FORMULAIREQUESTION'] . '[]" id="QST_' . $row['ID_FORMULAIREQUESTION'] . '"';
                if (! empty($row['QST_WIDTH'])) {
                    $renderContent .= ' cols="' . $row['QST_WIDTH'] . '"';
                }
                if (! empty($row['QST_HEIGHT'])) {
                    $renderContent .= ' rows="' . $row['QST_HEIGHT'] . '"';
                }
                if ($row['QST_OBLIGATOIRE']) {
                    $renderContent .= ' required';
                }
                if (! empty($row['QST_PLACEHOLDER'])) {
                    $renderContent .= ' placeholder="' . secureInput($row['QST_PLACEHOLDER']) . '"';
                }
                $renderContent .= '>' . secureInput($aFORMULAIREREPONSE['QST_' . $row['ID_FORMULAIREQUESTION']][0]);
                $renderContent .= '</textarea>';
                break;
            case 'QTY_FILE':
                if (empty($aFORMULAIREREPONSE['QST_' . $row['ID_FORMULAIREQUESTION']])) {
                    $renderContent .= '<input type="file" name="QST_' . $row['ID_FORMULAIREQUESTION'] . '" id="QST_' . $row['ID_FORMULAIREQUESTION'] . '"';
                    if ($row['QST_OBLIGATOIRE']) {
                        $renderContent .= ' required';
                    }
                    $renderContent .= '>';
                } else {
                    $renderContent .= '<a href="' . UPLOAD_FORMULAIRE . $aFORMULAIREREPONSE[$row['ID_FORMULAIREQUESTION']] . '" target="_blank">' . gettext('Telecharger fichier') . '</a>';
                }
                break;
            case 'QTY_CAPTCHAGRAPHIC':
                require_once CLASS_DIR . 'class.CMSCaptcha.php';
                $oCaptcha = new Graphical_CAPTCHA();
                $renderContent .= $oCaptcha->render('ID_CAPTCHA_' . $row['ID_FORMULAIREQUESTION'], 'QST_' . $row['ID_FORMULAIREQUESTION'], true);
                break;
            case 'QTY_CAPTCHANUMERIC':
            case 'QTY_CAPTCHANUMERICMINUS':
                require_once CLASS_DIR . 'class.CMSCaptcha.php';
                $oCaptcha = new Numerical_CAPTCHA($row['QTY_CODE'] == 'QTY_CAPTCHANUMERICMINUS');
                $renderContent .= $oCaptcha->render('ID_CAPTCHA_' . $row['ID_FORMULAIREQUESTION'], 'QST_' . $row['ID_FORMULAIREQUESTION'], true);
                break;
        }
        $renderContent .= '</p>';

        return $renderContent;
    }

    /**
     * Permet de vérifier qu'un lecteur de formulaire à accès au formulaire courant
     */
    public function isAuthorized($redirect = true)
    {
        if (! $this->exist()) {
            header('Location:' . SERVER_ROOT . 'formulaire/frm_formulaireListe.php');
            exit();
        }
        if (! Utilisateur::getConnected()->checkProfil(array('PRO_FORMGEST'), false)) {
            $sql = "select * from FORMULAIRE_UTILISATEUR
                where ID_FORMULAIRE=" . $this->getID() . " and ID_UTILISATEUR=" . Utilisateur::getConnected()->getID();
            if ($row = $this->dbh->query($sql)->fetch()) {
                return true;
            } elseif ($redirect) {
                header('Location:' . SERVER_ROOT . 'formulaire/frm_formulaireListe.php');
                exit();
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * Permet de savoir si un formulaire à des notifications Back Office
     *
     * @return bool
     */
    public function hasBackNotification()
    {
        if ($this->_backNotification == null) {
            $this->_backNotification = count($this->getBackNotification()) > 0;
        }

        return $this->_backNotification;
    }

    /**
     * Permet de récupérer un tableau des emails à notifier en Back Office (intervenant seulement, pas les notification suplémentaires)
     *
     * @return array
     */
    public function getBackNotification()
    {
        if ($this->_bo_EmailsNotification == null) {
            $this->_bo_EmailsNotification = array();
            if ($this->getField('FRM_NOTIFICATION')) {
                $sql = "select distinct(UTI_EMAIL) from UTILISATEUR
                    inner join FORMULAIRE_UTILISATEUR using(ID_UTILISATEUR)
                    where UTI_EMAIL<>'' and ID_FORMULAIRE=" . $this->getID();
                $this->_bo_EmailsNotification = $this->dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN);
            }
        }

        return $this->_bo_EmailsNotification;
    }

    /**
     * Permet de savoir si un formulaire à des notifications Front Office
     */
    public function hasFrontNotification()
    {
        if ($this->_frontNotification == null) {
            $sql = "select count(ID_FORMULAIREQUESTION) from FORMULAIREQUESTION
                inner join FORMULAIREGROUPE using(ID_FORMULAIREGROUPE)
                where ID_FORMULAIRE=" . $this->getID() . " and QTY_CODE = 'QTY_EMAIL_NOTIF'";
            $this->_frontNotification = ($this->dbh->query($sql)->fetchColumn() > 0);
        }

        return $this->_frontNotification;
    }

    /**
     * Permet de récupérer un tableau des emails à notifier en Front Office
     */
    public function getFrontNotification($ID_FORMULAIREREPONSE)
    {
        if ($this->_fo_EmailsNotification == null) {
            $sql = "select RED_VALEUR from FORMULAIREREPONSEDETAIL inner join FORMULAIREQUESTION using(ID_FORMULAIREQUESTION)
                where ID_FORMULAIREREPONSE = " . intval($ID_FORMULAIREREPONSE) . " and QTY_CODE = 'QTY_EMAIL_NOTIF' and RED_VALEUR <> '' order by QST_POIDS asc";
            $this->_fo_EmailsNotification = $this->dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN);
        }

        return $this->_fo_EmailsNotification;
    }

    /**
     * Permet de récupérer le ou les emails internaute à ajouter au replyTo dans les mails pour les intervenants
     *
     * @param $ID_FORMULAIREREPONSE Int
     *            Indentifant de la réponse
     * @return $_replyto_EmailsNotification array() Tableau contenant les différents mails (mail de type Accusé de réception ou 1er mail simple
     */
    public function getNotificationReplyToEmails($ID_FORMULAIREREPONSE)
    {
        if ($this->_replyto_EmailsNotification == null) {
            // Si existant, on récupère les mails de type "accusé réception"
            $sql = "select distinct(RED_VALEUR) from FORMULAIREREPONSEDETAIL inner join FORMULAIREQUESTION using(ID_FORMULAIREQUESTION)
                where ID_FORMULAIREREPONSE = " . intval($ID_FORMULAIREREPONSE) . " and QTY_CODE = 'QTY_EMAIL_NOTIF' and RED_VALEUR <> ''";
            $aEmails = $this->dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN);
            if (count($aEmails) == 0) {
                // Si non, on récupère le premier mail
                $sql = "select RED_VALEUR from FORMULAIREREPONSEDETAIL inner join FORMULAIREQUESTION using(ID_FORMULAIREQUESTION)
                    where ID_FORMULAIREREPONSE = " . intval($ID_FORMULAIREREPONSE) . " and QTY_CODE = 'QTY_EMAIL' and RED_VALEUR <> '' order by QST_POIDS asc limit 0,1";
                $aEmails = $this->dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN);
            }
            $this->_replyto_EmailsNotification = $aEmails;
        }

        return $this->_replyto_EmailsNotification;
    }

    /**
     * Permet de connaître le nombre de réponses associer à un formulaire
     *
     * @return int
     */
    public function getNbResults()
    {
        $sql = "select count(ID_FORMULAIREREPONSE) from FORMULAIREREPONSE where ID_FORMULAIRE=" . $this->getID();
        $nbResults = $this->dbh->query($sql)->fetchColumn();

        return $nbResults;
    }

    /**
     * Permet de savoir si un formulaire à des groupes
     *
     * @return bool
     */
    public function hasGroups()
    {
        $sql = "select count(ID_FORMULAIREGROUPE) from FORMULAIREGROUPE where ID_FORMULAIRE=" . $this->getID();

        return ($this->dbh->query($sql)->fetchColumn() > 0);
    }

    public function replaceKey($val)
    {
        $aKey = array(
            '[LIBELLE]'
        );
        $aVal = array(
            $this->getField('FRM_LIBELLE')
        );
        return str_replace($aKey, $aVal, $val);
    }

    public function getEtat()
    {
        $dbh = DB::getInstance();
        $sql = "select FRM_ETATREPONSE from FORMULAIRE where ID_FORMULAIRE = " . $this->getID();

        return $dbh->query($sql)->fetch(PDO::FETCH_COLUMN);
    }

    /**
     *
     * Fonction permettant d'historiser les actions effectuées sur les propriétés d'un formulaire, un groupe ou une question
     *
     * @param string $HIS_ACTION
     *                           : type d'action : CREATION / MODIFICATION / SUPPRESSION
     * @param string $HIS_TYPE
     *                           : type d'élément historisé : FORMULAIRE / GROUPE / QUESTION
     * @param string $HIS_DETAIL
     *                           : Détail de l'action si besoin de précision
     */
    public function historize($HIS_ACTION, $HIS_TYPE = '', $HIS_DETAIL = '')
    {
        $stmt = $this->dbh->prepare("insert into HISTORIQUE_FORMULAIRE (
            SIT_CODE,
            ID_FORMULAIRE,
            ID_HISTORIQUE_UTILISATEUR,
            HIS_ACTION,
            HIS_TYPE,
            HIS_DETAIL,
            HIS_DATE
            ) values(
            :SIT_CODE,
            :ID_FORMULAIRE,
            :ID_HISTORIQUE_UTILISATEUR,
            :HIS_ACTION,
            :HIS_TYPE,
            :HIS_DETAIL,
            :HIS_DATE
            )");

        $stmt->bindValue(':SIT_CODE', $this->getField('SIT_CODE'), PDO::PARAM_STR);
        $stmt->bindValue(':ID_FORMULAIRE', $this->getID(), PDO::PARAM_INT);
        // On peut historiser des modifications sans passer par un utilisateur (ex via un crontab).
        // Dans ce cas, nous n'avons pas les infos sur ledit utilisateur
        $S_ID_HISTORIQUE = '';
        if (isset($_SESSION['S_ID_HISTORIQUE']) && CMS::getCurrentSite() && is_numeric($_SESSION['S_ID_HISTORIQUE'][CMS::getCurrentSite()->getID()])) {
            $S_ID_HISTORIQUE = $_SESSION['S_ID_HISTORIQUE'][CMS::getCurrentSite()->getID()];
        }
        $stmt->bindValue(':ID_HISTORIQUE_UTILISATEUR', (! empty($S_ID_HISTORIQUE)) ? $S_ID_HISTORIQUE : null, PDO::PARAM_INT);
        $stmt->bindValue(':HIS_ACTION', $HIS_ACTION, PDO::PARAM_STR);
        $stmt->bindValue(':HIS_TYPE', $HIS_TYPE, PDO::PARAM_STR);
        $stmt->bindValue(':HIS_DETAIL', $HIS_DETAIL, PDO::PARAM_STR);
        $stmt->bindValue(':HIS_DATE', time(), PDO::PARAM_INT);
        $stmt->execute();

        if (isset($_SESSION['S_ID_HISTORIQUE'])) {
            Utilisateur::historizeAction();
        }

        if (($HIS_TYPE == 'FORMULAIRE') && ($HIS_ACTION == 'SUPPRESSION')) {
            $this->dbh->exec('update HISTORIQUE_FORMULAIRE
                               set HIS_INFO = ' . $this->dbh->quote($this->getField('FRM_LIBELLE')) . '
                               where ID_FORMULAIRE = ' . $this->getID());
        }
    }
}
