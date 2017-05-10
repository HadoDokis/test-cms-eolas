<?php
require_once CLASS_DIR . 'class.db_commentaireParametrage.php';
require_once CLASS_DIR . 'class.CMSMailer.php';

class Commentaire extends Generic
{

    private $_oCible = null;

    protected $_aType = null;

    /*
    interface i_commentaire

    getLibelleTypeCommentaire() -> renvoi le libelle du type de commentaire -> e.g -> return 'Page', return 'Actualité', etc...
    getLibelleCommentaire() -> renvoi le libelle de la cible du commentaire -> e.g -> return $this->getField('PAG_TITRE_MENU'), return $this->getField('ACT_LIBELLE') etc ...
    getURLCommentaire() -> renvoi l'url de la cible ou est affiché les commentaires en FO
    [pour les pages-> renvoi l'url de la page,
    pour les modules -> renvoi l'url de la fiche]
    getURLBO() -> renvoi l'url de la cible en BO [pseudo FO pour les pages]

    */

    public function __construct($idtf = -1)
    {
        parent::__construct($idtf);
    }

    public static function getModuleCode()
    {
        return 'MOD_COMMENTAIRE';
    }

    public function getTypeInfo()
    {
        if ($this->exist() && is_null($this->_aType)) {
            $sql = "select * from DD_COMMENTAIRE_LIAISONTYPE where ID_COMMENTAIRE_LIAISONTYPE = ".intval($this->getField('ID_COMMENTAIRE_LIAISONTYPE'));
            $this->_aType = $this->dbh->query($sql)->fetch(PDO::FETCH_ASSOC);
        }

        return $this->_aType;
    }

    public function checkAccess($strict = true)
    {

        if ($oConnected = Utilisateur::getConnected()) {
            if ($oConnected->checkProfil(array('PRO_ROOT_SITE'))) {

                return true;
            }

            $aType = $this->getTypeInfo();

            if (is_array($aType)) {
                if ($oConnected->checkProfil(array($aType['PRO_CODE']))) {
                    return true;
                }
            }
        }
        if ($strict) {
            header('Location:' . SERVER_ROOT . 'cms/cms_commentaireListe.php');
            exit ();
        }

        return false;

    }

    public function load()
    {
        $sql = "select * from COMMENTAIRE where ID_COMMENTAIRE=" . $this->getID();
        if ($row = $this->dbh->query($sql)->fetch(PDO::FETCH_ASSOC)) {
            $this->setFields($row);
        } else {
            $this->_idtf = -1;
            $this->setFields(array ());
        }
    }

    public function isDeletable()
    {
        return true;
    }

    public function delete()
    {
        if (!$this->isDeletable()) {
            return false;
        }

        require_once CLASS_DIR . 'class.Link.php';
        Link::delete('COMMENTAIRE', $this->getID(), 'ALL');

        $sql = "delete from COMMENTAIRE_ABUS where ID_COMMENTAIRE=" . $this->getID();
        $this->dbh->exec($sql);

        $sql = "delete from COMMENTAIRE where ID_COMMENTAIRE=" . $this->getID();
        $this->dbh->exec($sql);

        return true;
    }

    public function getLienCible()
    {
        $aType = $this->getTypeInfo();
        require_once (CLASS_DIR . $aType['CLI_CLASSFILE']);
        $oInstance = new $aType['CLI_CLASSNOM']($this->getField('COM_IDLIAISON'));
        echo secureInput($oInstance->getLibelleTypeCommentaire()) . ': <a href="' . $aType['CLI_CHEMINFICHE'] . $this->getField('COM_IDLIAISON') . '">' . secureInput($oInstance->getLibelleCommentaire()) . '</a>';

    }

    public function getCible()
    {
        if (is_null($this->_oCible)) {
            $aType = $this->getTypeInfo();
            require_once (CLASS_DIR . $aType['CLI_CLASSFILE']);
            $this->_oCible = new $aType['CLI_CLASSNOM']($this->getField('COM_IDLIAISON'));
        }

        return $this->_oCible;
    }

    //recupere l'url de la cible avec affichage sur centré sur le bloc commentaire
    public function getURLCible()
    {
        return $this->getCible()->getURLCommentaire(array(), 'com'.$this->getID());
    }


    /**
     *
     * Cette fonction sert à determiner si un module est associé au module commentaire
     * [Pour les pages, le module MOD_CORE est utilisé]
     * @param string $MOD_CODE
     */
    public static function isAssignedTo($MOD_CODE)
    {
        $dbh = DB::getInstance();
        $sql = "select count(ID_COMMENTAIRE_LIAISONTYPE) from DD_COMMENTAIRE_LIAISONTYPE where MOD_CODE = ".$dbh->quote($MOD_CODE);
        if ($dbh->query($sql)->fetchColumn() > 0) {
            return true;
        }

        return false;
    }

    /**
     *
     * Sauvegarde d'un abus en bdd
     * return 'true' si sauvegarde réussi, sinon 'false'
     */
    public static function saveAbus()
    {
        $dbh = DB::getInstance();
        if (isset($_POST['NEW_ABUS'])) {
            //insertion de l'abus de commentaire

            $stmt = $dbh->prepare("insert into COMMENTAIRE_ABUS (
                ID_COMMENTAIRE,
                ID_UTILISATEUR,
                CAB_PSEUDO,
                CAB_MESSAGE,
                CAB_DATE,
                CAB_MAIL
                ) values (
                :ID_COMMENTAIRE,
                :ID_UTILISATEUR,
                :CAB_PSEUDO,
                :CAB_MESSAGE,
                :CAB_DATE,
                :CAB_MAIL
                )");
            $stmt->bindValue(':ID_COMMENTAIRE', intval($_POST['ID_COMMENTAIRE']), PDO::PARAM_INT);

            $prenom = $_POST['CAB_PSEUDO'];
            $mail = $_POST['CAB_MAIL'];
            if ($oUtilisateur = Utilisateur::getConnected()) {
                $idUti = intval($oUtilisateur->getID());
            } else {
                $idUti = null;
            }

            $stmt->bindValue(':ID_UTILISATEUR', $idUti, PDO::PARAM_INT);
            $stmt->bindValue(':CAB_PSEUDO', $prenom, PDO::PARAM_STR);
            $stmt->bindValue(':CAB_MAIL', $mail, PDO::PARAM_STR);
            $stmt->bindValue(':CAB_MESSAGE', $_POST['CAB_MESSAGE'], PDO::PARAM_STR);
            $stmt->bindValue(':CAB_DATE', time(), PDO::PARAM_INT);
            $stmt->execute();

            //maj de l'etat du commentaire signalé
            $stmt = $dbh->prepare("update COMMENTAIRE set COM_ETAT=:COM_ETAT where ID_COMMENTAIRE=:idtf");
            $stmt->bindValue(':COM_ETAT', 'MODERATION', PDO::PARAM_STR);
            $stmt->bindValue(':idtf', intval($_POST['ID_COMMENTAIRE']), PDO::PARAM_INT);
            $stmt->execute();

            //* Envoi mail abus
            $oMail = new CMSMailer('EMT_COMMENTAIRE_ABUS');
            $oMail->replace('[NUMERO_COMMENTAIRE]', intval($_POST['ID_COMMENTAIRE']));
            $infoPageCommentee = encode($_SESSION['COMMENTS'][intval($_POST['ID_COMMENTAIRE'])]['TITRE']. ' (n°'. $_SESSION['COMMENTS'][intval($_POST['ID_COMMENTAIRE'])]['ID'].')', false);
            $oMail->replace('[INFO_PAGE_COMMENTEE]', $infoPageCommentee);
            $oMail->replace('[PRENOM]', encode($prenom, false));
            $oMail->replace('[EMAIL]', encode($mail, false));
            $oComTmp = new self(intval($_POST['ID_COMMENTAIRE']));
            $oMail->replace('[COMMENTAIRE]', encode($oComTmp->getField('COM_MESSAGE'), false));
            $oMail->replace('[DESCRIPTION_ABUS]', encode($_POST['CAB_MESSAGE'], false));
            $url = 'http://'.CMS::getCurrentSite()->getField('SIT_HOST').SERVER_ROOT.'cms/cms_commentaire.php?idtf='.intval($_POST['ID_COMMENTAIRE']);
            $oMail->replace('[LIEN_MODERATION]', '<a href="'.$url.'">Modérer ce commentaire</a>');
            $aInfos = $oComTmp->getTypeInfo();
            $sqlModerateurs = "select UTILISATEUR.UTI_EMAIL from ROLE inner join UTILISATEUR on (ROLE.ID_UTILISATEUR = UTILISATEUR.ID_UTILISATEUR) where PRO_CODE = ".$dbh->quote($aInfos['PRO_CODE']);
            $rowListeModerateurs = $dbh->query($sqlModerateurs)->fetchAll(PDO::FETCH_COLUMN);
            // Si pas de modérateurs désigné, la notification est faite auprès des super admin et administrateur du site
            if (empty($rowListeModerateurs)) {
                $sqlModerateurs = "select UTILISATEUR.UTI_EMAIL from ROLE inner join UTILISATEUR on (ROLE.ID_UTILISATEUR = UTILISATEUR.ID_UTILISATEUR) where PRO_CODE = 'PRO_ROOT' OR (PRO_CODE = 'PRO_ROOT_SITE' AND ROLE.SIT_CODE=".$dbh->quote(CMS::getCurrentSite()->getID()).")";
                $rowListeModerateurs = $dbh->query($sqlModerateurs)->fetchAll(PDO::FETCH_COLUMN);
            }
            foreach ($rowListeModerateurs as $em) {
                $oMail->AddAddress($em);
            }

            $oMail->send();
            //*/
            return true;
        }

        return false;
    }

    /**
     *
     * Sauvegarde d'un commentaire en bdd
     * return 'true' si sauvegarde réussi, sinon 'false'
     */
    public static function saveCommentaire($idType, $idLiaison, $typeModeration = 1, $idPage = '', $idIdentifiantUnique = '', $parametresPage = array())
    {
        $dbh = DB::getInstance();
        if (intval($idType) > 0 && intval($idLiaison) > 0) {
            try {
                //insertion du commentaire
                $stmt = $dbh->prepare("insert into COMMENTAIRE (
                    ID_UTILISATEUR,
                    SIT_CODE,
                    COM_PSEUDO,
                    COM_MESSAGE,
                    ID_COMMENTAIRE_LIAISONTYPE,
                    COM_IDLIAISON,
                    COM_ETAT,
                    COM_DATE,
                    COM_MAIL
                    ) values (
                    :ID_UTILISATEUR,
                    :SIT_CODE,
                    :COM_PSEUDO,
                    :COM_MESSAGE,
                    :ID_COMMENTAIRE_LIAISONTYPE,
                    :COM_IDLIAISON,
                    :COM_ETAT,
                    :COM_DATE,
                    :COM_MAIL
                    )");


                $pseudo = $_POST['COM_PSEUDO'];
                $mail = $_POST['COM_MAIL'];
                if ($oUtilisateur = Utilisateur::getConnected()) {
                    $idUti = intval($oUtilisateur->getID());
                } else {
                    $idUti = null;
                }

                $stmt->bindValue(':ID_UTILISATEUR', $idUti, PDO::PARAM_INT);
                $stmt->bindValue(':COM_PSEUDO', $pseudo, PDO::PARAM_STR);
                $stmt->bindValue(':COM_MAIL', $mail, PDO::PARAM_STR);
                $stmt->bindValue(':SIT_CODE', CMS::getCurrentSite()->getID(), PDO::PARAM_STR);
                $stmt->bindValue(':COM_MESSAGE', resume($_POST['COM_MESSAGE'.$idIdentifiantUnique], 500), PDO::PARAM_STR);
                $stmt->bindValue(':COM_DATE', time(), PDO::PARAM_INT);

                $stmt->bindValue(':ID_COMMENTAIRE_LIAISONTYPE', $idType, PDO::PARAM_INT);
                $stmt->bindValue(':COM_IDLIAISON', $idLiaison, PDO::PARAM_INT);

                if ($typeModeration == 1) {
                    //avant diffusion
                    $stmt->bindValue(':COM_ETAT', 'MODERATION', PDO::PARAM_STR);
                } else {
                    //apres diffusion
                    $stmt->bindValue(':COM_ETAT', 'VALIDE', PDO::PARAM_STR);
                }

                $stmt->execute();
                $idCommentaire = $dbh->lastInsertID();

                if (intval($idPage) > 0) {
                    $oPageTmp = new Page($idPage, CMS::$mode);

                } else {
                    $oPageTmp = new Page($idLiaison, CMS::$mode);
                }

                if (!$oPageTmp || !$oPageTmp->exist()) {
                    $oPageTmp = CMS::getCurrentSite()->getHomePage(CMS::$mode);
                    $parametresPage = array();
                }

                $oComTmp = new self($idCommentaire);

                //* Envoie mail nouveau commentaire
                $oMail = new CMSMailer('EMT_COMMENTAIRE_NOUVEAU');
                $oMail->replace('[NUMERO_COMMENTAIRE]', intval($idCommentaire));
                $infoPageCommentee = encode($oPageTmp->getField('PAG_TITRE_MENU'). ' (n°'. $oPageTmp->getID().')', false);
                $url = $oPageTmp->getURL($parametresPage);
                if (strpos($url, '://') === false) {
                    $url = 'http://' . CMS::getCurrentSite()->getField('SIT_HOST') . $url;
                }
                $oMail->replace('[LIEN_PAGE_COMMENTEE]', '<a href="'.$url.'">'.$infoPageCommentee.'</a>');
                $oMail->replace('[PSEUDO]', encode($pseudo, false));
                $oMail->replace('[EMAIL]', encode($mail, false));
                $oMail->replace('[MESSAGE]', encode(resume($_POST['COM_MESSAGE'], 500), false));
                $url = 'http://'.CMS::getCurrentSite()->getField('SIT_HOST').SERVER_ROOT.'cms/cms_commentaire.php?idtf='.$idCommentaire;
                $oMail->replace('[LIEN_MODERATION]', '<a href="'.$url.'">Modérer ce commentaire</a>');
                $oMail->replace('[MESSAGE_VALIDATION_DU_SITE]', $paramCommentaire['CPA_COMMENTAIREVALIDE']);
                $oMail->replace('[PARAMETRE_SIGNATURE_DU_SITE]', $paramCommentaire['CPA_SIGNATUREMAIL']);
                $aInfos = $oComTmp->getTypeInfo();
                $sqlModerateurs = "select UTILISATEUR.UTI_EMAIL from ROLE inner join UTILISATEUR on (ROLE.ID_UTILISATEUR = UTILISATEUR.ID_UTILISATEUR) where PRO_CODE = ".$dbh->quote($aInfos['PRO_CODE']);
                $rowListeModerateurs = $dbh->query($sqlModerateurs)->fetchAll(PDO::FETCH_COLUMN);
                // Si pas de modérateurs désigné, la notification est faite auprès des super admin et administrateur du site
                if (empty($rowListeModerateurs)) {
                    $sqlModerateurs = "select UTILISATEUR.UTI_EMAIL from ROLE inner join UTILISATEUR on (ROLE.ID_UTILISATEUR = UTILISATEUR.ID_UTILISATEUR) where PRO_CODE = 'PRO_ROOT' OR (PRO_CODE = 'PRO_ROOT_SITE' AND ROLE.SIT_CODE=".$dbh->quote(CMS::getCurrentSite()->getID()).")";
                    $rowListeModerateurs = $dbh->query($sqlModerateurs)->fetchAll(PDO::FETCH_COLUMN);
                }
                foreach ($rowListeModerateurs as $em) {
                    $oMail->AddAddress($em);
                }
                $oMail->send();
                //*/
                return true;

            } catch (Exception $e) {
                print_r($e);
                die();

                return false;
            }


        }

        return false;
    }


    /**
     *
     * Gere l'affichage et les actions liées au commentaires des pages en FO
     * @param int    $ID_PAGE
     * @param string $MOD_CODE
     */
    public static function doCommentairesPage($ID_PAGE, $MOD_CODE = 'MOD_CORE')
    {
        if (CMS::getCurrentSite()->hasModule(new Module('MOD_COMMENTAIRE'))) {

            //return '<span class="alert"><strong>Le module commentaire doit &ecirc;tre initialis&eacute; pour ce site</strong></span>';
            if(empty($ID_PAGE) || intval($ID_PAGE) < 1) return '<span class="alert"><strong>L\'identifiant de cet instance doit &ecirc;tre renseign&eacute;</strong></span>';
            //if(empty($MOD_CODE)) return '<span class="alert"><strong>Le MOD_CODE doit &ecirc;tre renseign&eacute;</strong></span>';
            $oModule = new Module(self::getModuleCode());
            require_once CLASS_DIR . 'class.Editor.php';

           $aParams = array();
            if ($_GET['TPL_CODE']) {
                $aParams['TPL_CODE'] = $_GET['TPL_CODE'];
            }
            if ($_GET['PAR_TPL_IDENTIFIANT']) {
                $aParams['PAR_TPL_IDENTIFIANT'] = $_GET['PAR_TPL_IDENTIFIANT'];
            }

            $str = '';
            $dbh = DB::getInstance();
            $oPageAssocie = new Page(intval($ID_PAGE), CMS::$mode);

            $oPage = CMS::getCurrentSite()->getCurrentPage();
            $error = array();
            $message = array();

            $oSiteParametrage = CommentaireParametrage::getParametrageForSite();
            $aParamsCom = $oSiteParametrage->getFields();
            $showComments = $aParamsCom['CPA_AFFICHAGE_DEFAUT'];

            $sql = "select ID_COMMENTAIRE_LIAISONTYPE from DD_COMMENTAIRE_LIAISONTYPE where MOD_CODE = " . $dbh->quote($MOD_CODE);
            $idLiaisonType = $dbh->query($sql)->fetchColumn();

            CMS::addJS('/include/js/commentaires_functions.js');

            //soumission d'un commentaire
            if (!empty($_POST['COM_SOUMISSION'])) {
                $showComments = 1;
                //on s'assure que c'est bien un depot de commentaire sur les pages
                if (isset($_POST['ID_PAGE']) && intval($_POST['ID_PAGE']) > 0) {

                    //COM_MESSAGE  COM_PSEUDO  COM_MAIL
                    if (empty($_POST['COM_MESSAGE']) || trim($_POST['COM_MESSAGE']) == '') {
                        $error[] = $oModule->i18n('com_fe_message');
                    }
                    if (empty($_POST['COM_PSEUDO']) || trim($_POST['COM_PSEUDO']) == '') {
                        $error[] = $oModule->i18n('com_fe_pseudo');
                    }
                    if (empty($_POST['COM_MAIL']) || trim($_POST['COM_MAIL']) == '') {
                        $error[] = $oModule->i18n('com_fe_mail');
                    } else {
                        if(!valideMail($_POST['COM_MAIL'])) $error[] = $oModule->i18n('com_fe_mail_invalid');
                    }
                    if (count($error) == 0) {
                        if (self::saveCommentaire($idLiaisonType, $ID_PAGE, $aParamsCom['CPA_TYPEMODERATION'])) {
                            $message[] = $aParamsCom['CPA_MES_REMERCIEMENT'];

                        } else {
                            $error[] = 'Erreur à la sauvegarde du commentaire.';
                        }
                    }

                }
            }

            //Affichage du formulaire d'abus
            if (!empty($_POST['COM_ABUS'])) {

                if (isset($_POST['COM_TYPE']) && $_POST['COM_TYPE'] == 'PAGE') {//on s'assure que c'est bien un abus de commentaire sur les pages
                    if (intval($_POST['COM_ABUS']) > 0) {
                        $oComSignale = new self(intval($_POST['COM_ABUS']));
                        if ($oComSignale->exist()) {
                            if(!$oPageAbus = CMS::getCurrentSite()->getSpecialePage('PGS_FORMULAIREABUS', CMS::$mode)) $oPageAbus = $oPage;
                            //$_SESSION['COMMENTS'][intval($_POST['COM_ABUS'])]['RETOUR'] = $oPageAssocie->getURLCommentaire('com' . $ID_PAGE);
                            $_SESSION['COMMENTS'][intval($_POST['COM_ABUS'])]['RETOUR'] = ($oPageAssocie->getMode() == 'ON_')? $oPageAssocie->getURLCommentaire() : $oPageAssocie->getURLBO();
                            $_SESSION['COMMENTS'][intval($_POST['COM_ABUS'])]['TITRE'] = $oPageAssocie->getField('PAG_TITRE_MENU');
                            $_SESSION['COMMENTS'][intval($_POST['COM_ABUS'])]['ID'] = $oPageAssocie->getID();

                            $partplI = intval($_POST['COM_ABUS']);
                            //redirige vers la page de formulaire
                            $oPageAbus->redirect(array('TPL_CODE' => 'TPL_COMMENTAIREFABUS','PAR_TPL_IDENTIFIANT'=>$partplI));

                        }
                    }

                    $error[] = 'Le commentaire signalé n\'a pas été retrouvé.';
                }
            }

            if (!empty($idLiaisonType) && intval($idLiaisonType) > 0) {
                try {

                    //$urlSelf = $_SERVER['PHP_SELF'];
                    //strrpos($haystack, $needle)
                    if ($aParamsCom['CPA_TYPEMODERATION'] == 1) {
                        $commmentairesSql = "select COMMENTAIRE.* from COMMENTAIRE
                        inner join DD_COMMENTAIRE_LIAISONTYPE on (DD_COMMENTAIRE_LIAISONTYPE.ID_COMMENTAIRE_LIAISONTYPE = COMMENTAIRE.ID_COMMENTAIRE_LIAISONTYPE)
                        where COMMENTAIRE.ID_COMMENTAIRE_LIAISONTYPE = " . intval($idLiaisonType) . " and COM_IDLIAISON = " . intval($ID_PAGE) . " and COM_ETAT = 'VALIDE'";

                    } else {
                        Page::setNoCache();
                        $commmentairesSql = "select COMMENTAIRE.* from COMMENTAIRE
                        inner join DD_COMMENTAIRE_LIAISONTYPE on (DD_COMMENTAIRE_LIAISONTYPE.ID_COMMENTAIRE_LIAISONTYPE = COMMENTAIRE.ID_COMMENTAIRE_LIAISONTYPE)
                        where COMMENTAIRE.ID_COMMENTAIRE_LIAISONTYPE = " . intval($idLiaisonType) . " and COM_IDLIAISON = " . intval($ID_PAGE) . " and COM_ETAT in ('VALIDE','MODERATION')";
                    }

                    $aCommmentaires = $dbh->query($commmentairesSql)->fetchAll(PDO::FETCH_ASSOC);
                    $nbCommmentaires = count($aCommmentaires);

                        $str .= '<div id="com' . $ID_PAGE . '" class="commentaire paragraphe">';
                        /** Liens **/
                        $str .= '<div class="liens">';
                        $str .= '<a href="#form_comment" id="a_page_form_comment" class="submit">' . $oModule->i18n('com_je_reagis') . '</a>';
                        switch ($nbCommmentaires) {
                            case 0:
                               //do nothing
                               break;
                            case 1:
                                $str .= '<a href="#list_comment" id="a_page_list_comment" class="voir_liste">' . $nbCommmentaires . ' ' . $oModule->i18n('com_commentaire').'</a>';
                                break;
                            default:
                                $str .= '<a href="#list_comment" id="a_page_list_comment" class="voir_liste">' . $nbCommmentaires . ' ' . $oModule->i18n('com_commentaires').'</a>';
                               break;
                        }
                        $str .= '</div>';
                        /** Fin liens **/

                        /** Liste de commentaire **/
                        $str .= '<div class="page_comments" id="page_comments">';
                        $str .= '<div class="liste_comment" id="list_comment">';
                        foreach ($aCommmentaires as $unCommmentaire) {
                            $str .= '<p class="commentaire_message">'.encode($unCommmentaire['COM_MESSAGE']).'</p>';
                            $str .= '<p class="commentaire_infos note">'.encode(strftime('%d %B %Y %H:%M', $unCommmentaire['COM_DATE']) .' '. $oModule->i18n('com_commentaire_par' ). ' ' . $unCommmentaire['COM_PSEUDO']) .'</p>';
                            $str .= '<div class="commentaire_lien_abus">
                                         <form method="post" action="'.$oPage->getURL($aParams).'">
                                              <p class="submit">
                                                <span class="submit"><input type="submit" value="' . $oModule->i18n('com_message_abus') . '" class="submit"></span>
                                                <input type="hidden" value="PAGE" name="COM_TYPE">
                                                <input type="hidden" value="' . $unCommmentaire['ID_COMMENTAIRE'] . '" name="COM_ABUS">
                                            </p>
                                         </form>
                                     </div>';
                        }
                        $str .= '</div><!-- FIN .liste_comment -->';
                        /** Fin liste de commentaire **/

                        $utiPseudo = '';
                        $utiPseudoReadOnly = false;
                        $utiMail = '';
                        $utiMailReadOnly = false;
                        if ($oUtilisateur = Utilisateur::getConnected()) {
                            $utiPseudo = $oUtilisateur->getField('UTI_PRENOM');
                            $utiMail = $oUtilisateur->getField('UTI_EMAIL');
                        }
                        if (!empty($_POST['COM_PSEUDO'])) {
                            $utiPseudo = $_POST['COM_PSEUDO'];
                        }
                        if (!empty($_POST['COM_MAIL'])) {
                            $utiMail = $_POST['COM_MAIL'];
                        }
                        /** Formulaire de depot commentaire **/
                        $str .= '<form id="form_comment" class="creation" method="post" action="'.$oPage->getURL($aParams).'#form_comment">';
                        $str .= '<fieldset class="groupeQuestion">';
                        $str .= '<legend><span>' . $oModule->i18n('com_monform') . '</span></legend>';

                        /** Message et charte **/
                        // Si commentaire enregistré, on n'affiche pas Message et charte
                        if (!isset($_POST['COM_SOUMISSION']) || !empty($error)) {
                            //[Message aux internautes – dépôt d’un commentaire] 
                            //(cf. paragraphe Paramètres de modération)
                            //suivi, si le lien vers la charte de modération à été précisé (cf. paragraphe Paramètres de modération) du libellé du lien,
                            //cliquable pour arriver vers la page choisie.
                            if (!empty($aParamsCom['CPA_MES_DEPOT'])) {
                                $str .= '<div class="message_internate">';
                                $str .= Editor::displayContent($aParamsCom['CPA_MES_DEPOT'], $oPage);
                                $str .= '</div>';
                            }
                            if (intval($aParamsCom['ID_PAGE']) > 0) {
                                $oPageCharte = new Page($aParamsCom['ID_PAGE'], CMS::$mode);
                                if ($oPageCharte->exist()) {
                                    $str .= '<p class="lien_charte">';
                                    $str .= '<a '.$oPageCharte->getAnchor(array(), '', array('external')).'>'.encode($aParamsCom['CPA_LIBELLELIEN']).'</a>';
                                    $str .= '</p>';
                                }
                            }
                        }
                        /** Fin Message et charte **/

                        /** Erreur **/
                        if (count($error) > 0) {
                            $str .= '<div class="message_error">';
                            foreach ($error as $er) {
                                $str .= '<p>'.encode($er).'</p>';
                            }
                            $str .= '</div>';
                        }
                        /** Fin Erreur **/
                        /** Message **/
                        if (count($message) > 0) {
                            $str .= '<div class="message_commentaires">';
                            foreach ($message as $ms) {
                                $str .= '<p>'.encode($ms).'</p>';
                            }
                            $str .= '</div>';
                        }
                        /** Fin Message **/
                        // Si commentaire enregistré, on n'affiche pas le contenu du formulaire
                        if (!isset($_POST['COM_SOUMISSION']) || !empty($error)) {
                            $str .= '<div class="innerGroupeQuestion">';
                            $str .= '<p>
                                             <label for="COM_MESSAGE">' . $oModule->i18n('com_moncommentaire') . '</label>
                                             <textarea required id="COM_MESSAGE" name="COM_MESSAGE" rows="10" cols="50" onKeyDown="limitText(this.form.COM_MESSAGE,this.form.countdown,500);" onKeyUp="limitText(this.form.COM_MESSAGE,this.form.countdown,500);">'. encode($_POST['COM_MESSAGE']).'</textarea>
                                     </p>';
                            $str .= '<p class="note">'
                                                 . $oModule->i18n('com_moncommentaire_info') . '<br>'
                                                 . $oModule->i18n('com_moncommentaire_nombre') . '
                                                 <input readonly type="text" name="countdown" size="3" value="0"> / 500
                                    </p>';
                            $str .= '<p>
                                             <label for="COM_PSEUDO">' . $oModule->i18n('com_monpseudo') . '</label>
                                             <input required type="text" id="COM_PSEUDO" name="COM_PSEUDO" value="'.encode($utiPseudo, false).'" size="50" maxlength="255"';
                            if ($utiPseudoReadOnly) {
                                 $str .= ' readonly="readonly"';
                            }
                            $str .= '></p>';
                            $str .= '<p>
                                             <label for="COM_MAIL">' . $oModule->i18n('com_monemail') . '</label>
                                             <input required type="email" id="COM_MAIL" name="COM_MAIL" value="'.encode($utiMail, false).'" size="50" maxlength="255"';
                            if ($utiMailReadOnly) {
                                $str .= ' readonly="readonly"';
                            }
                            $str .= '></p>';
                            $str .= '</div><!-- FIN .innerGroupeQuestion -->';
                            $str .= '</fieldset>';
                            $str .= '<p class="action">
                                        <span class="submit"><input name="COM_SOUMISSION" type="submit" class="submit" value="' . $oModule->i18n('com_btn_envoyer') . '"></span>
                                        <input name="ID_PAGE" type="hidden" value="' . $oPage->getID() . '">
                                    </p>';
                        } else {$str .= '</fieldset>';} // FIN if (!isset($_POST['COM_SOUMISSION']) || !empty($error)) {} # ==> Si commentaire enregistré, on n'affiche pas le contenu du formulaire
                        $str .= '</form>';
                        /** Fin formulaire de depot **/

                        $str .= '</div><!-- FIN .page_comments -->';

                        /** Partie JS **/
                        $js = '<script>';
                        $js .= 'function hidePageComments() { $("#page_comments").hide(); } function showPageComments() { $("#page_comments").show(); }';
                        if (!$showComments) {
                           CMS::addDOMREADY('$("#a_page_form_comment").click(function () { showPageComments();return true; });$("#a_page_list_comment").click(function () { showPageComments();return true; });hidePageComments();');
                        }
                        $js .= '</script>';
                        /** Fin partie JS **/

                        $str .= $js;
                        $str .= '</div><!-- FIN .commentaire -->';
                } catch (Exception $e) {
                    $str = 'Une erreur a été détecté durant l\'affichage des liens commentaires. Veuillez contacter l\'adminitrateur du site.';
                }
            }
        }else $str = false;

        return $str;

    }

    /**
     *
     * Fonction génératrice du code commentaire à utiliser dans les templates
     * S'occupe aussi de la gestion des abus et depot de commentaires
     * @param unknown_type $ID_PAGE
     * @param unknown_type $MOD_CODE
     * @param unknown_type $PAR_TPL_IDENTIFIANT
     */

    public static function doCommentairesTemplate($ID_PAGE, $MOD_CODE, $PAR_TPL_IDENTIFIANT)
    {
        //la fonction pour utilisation dans les templates
        if (CMS::getCurrentSite()->hasModule(new Module('MOD_COMMENTAIRE'))) {
            if(empty($ID_PAGE) || intval($ID_PAGE) < 1) return '<span class="alert"><strong>L\'identifiant de la page courante doit &ecirc;tre renseign&eacute;</strong></span>';

            if(empty($PAR_TPL_IDENTIFIANT) || intval($PAR_TPL_IDENTIFIANT) < 1) return '<span class="alert"><strong>L\'identifiant de cet instance doit &ecirc;tre renseign&eacute;</strong></span>';
            if (empty($MOD_CODE)) {
                return '<span class="alert"><strong>Le MOD_CODE doit &ecirc;tre renseign&eacute;</strong></span>';
            } else {
                $oModuleTmp = new Module($MOD_CODE);

                if(!$oModuleTmp->exist() || !Commentaire::isAssignedTo($MOD_CODE)) return '<span class="alert"><strong>Ce module n\'existe pas ou n\'est pas associé au commentaires</strong></span>';
            }

            $oModule = new Module(self::getModuleCode());
            require_once CLASS_DIR . 'class.Editor.php';

            /**  pour recuperer les pages en mode exclusif **/
            $aParams = array();
            if ($_GET['TPL_CODE']) {
                $aParams['TPL_CODE'] = $_GET['TPL_CODE'];
            }
            if ($_GET['PAR_TPL_IDENTIFIANT']) {
                $aParams['PAR_TPL_IDENTIFIANT'] = $_GET['PAR_TPL_IDENTIFIANT'];
            }
            $str = '';
            $dbh = DB::getInstance();
            $oPageAssocie = new Page(intval($ID_PAGE));

            $oPage = CMS::getCurrentSite()->getCurrentPage();
            $error = array();
            $message = array();

            $oSiteParametrage = CommentaireParametrage::getParametrageForSite();
            $aParamsCom = $oSiteParametrage->getFields();
            $showComments = $aParamsCom['CPA_AFFICHAGE_DEFAUT'];

            $sql = "select ID_COMMENTAIRE_LIAISONTYPE from DD_COMMENTAIRE_LIAISONTYPE where MOD_CODE = " . $dbh->quote($MOD_CODE);
            $idLiaisonType = $dbh->query($sql)->fetchColumn();

            $idIdentifiantUnique = $MOD_CODE.'_'.$PAR_TPL_IDENTIFIANT;

            CMS::addJS('/include/js/commentaires_functions.js');

            //soumission d'un commentaire
            if (!empty($_POST['COM_MODULESOUMISSION'])) {
                $showComments = true;

                //on s'assure que c'est bien un depot de commentaire sur cet instance (module et id)
                if (isset($_POST['ID_MODULE']) && isset($_POST['TYPE_MODULE']) && intval($_POST['ID_MODULE']) == $PAR_TPL_IDENTIFIANT && $_POST['TYPE_MODULE'] == $MOD_CODE) {

                    //COM_MESSAGE  COM_PSEUDO  COM_MAIL
                    if (empty($_POST['COM_MESSAGE_'.$idIdentifiantUnique]) || trim($_POST['COM_MESSAGE_'.$idIdentifiantUnique]) == '') {
                        $error[] = $oModule->i18n('com_fe_message');
                    }
                    if (empty($_POST['COM_PSEUDO']) || trim($_POST['COM_PSEUDO']) == '') {
                        $error[] = $oModule->i18n('com_fe_pseudo');
                    }
                    if (empty($_POST['COM_MAIL']) || trim($_POST['COM_MAIL']) == '') {
                        $error[] = $oModule->i18n('com_fe_mail');
                    } else {
                        if(!valideMail($_POST['COM_MAIL'])) $error[] = $oModule->i18n('com_fe_mail_invalid');
                    }

                    if (count($error) == 0) { //si pas d'erreurs
                        if (self::saveCommentaire($idLiaisonType, $PAR_TPL_IDENTIFIANT, $aParamsCom['CPA_TYPEMODERATION'], $oPageAssocie->getID(), '_'.$idIdentifiantUnique,$aParams)) {
                            $message[] = $aParamsCom['CPA_MES_REMERCIEMENT'];

                        } else {
                            $error[] = 'Erreur à la sauvegarde du commentaire.';
                        }
                    }

                }
            }

            //Affichage du formulaire d'abus
            if (!empty($_POST['COM_ABUS'])) {
                if (isset($_POST['COM_TYPE_ID']) && isset($_POST['COM_TYPE']) && $_POST['COM_TYPE'] == $MOD_CODE && $_POST['COM_TYPE_ID'] == $PAR_TPL_IDENTIFIANT) {//on s'assure que c'est bien un abus de commentaire sur le module
                    if (intval($_POST['COM_ABUS']) > 0) {
                        $oComSignale = new self(intval($_POST['COM_ABUS']));
                        if ($oComSignale->exist()) {
                            if(!$oPageAbus = CMS::getCurrentSite()->getSpecialePage('PGS_FORMULAIREABUS', CMS::$mode)) $oPageAbus = $oPage;
                            $_SESSION['COMMENTS'][$MOD_CODE][$PAR_TPL_IDENTIFIANT]['RETOUR'] = $oPage->getURL($aParams);
                            $_SESSION['COMMENTS'][$MOD_CODE][$PAR_TPL_IDENTIFIANT]['TITRE'] = $oPage->getField('PAG_TITRE_MENU');
                            $_SESSION['COMMENTS'][$MOD_CODE][$PAR_TPL_IDENTIFIANT]['ID'] = $oPage->getID();

                            $partplI = intval($_POST['COM_ABUS']);
                            //redirige vers la page de formulaire
                            $oPageAbus->redirect(array('TPL_CODE' => 'TPL_COMMENTAIREFABUS','PAR_TPL_IDENTIFIANT'=>$partplI));

                        }
                    }

                    $error[] = 'Le commentaire signalé n\'a pas été retrouvé.';
                }
            }

            if (!empty($idLiaisonType) && intval($idLiaisonType) > 0) {
                try {
                    //$urlSelf = $_SERVER['PHP_SELF'];
                    //strrpos($haystack, $needle)
                    if ($aParamsCom['CPA_TYPEMODERATION'] == 1) {
                        $commmentairesSql = "select COMMENTAIRE.* from COMMENTAIRE
                        inner join DD_COMMENTAIRE_LIAISONTYPE on (DD_COMMENTAIRE_LIAISONTYPE.ID_COMMENTAIRE_LIAISONTYPE = COMMENTAIRE.ID_COMMENTAIRE_LIAISONTYPE)
                        where COMMENTAIRE.ID_COMMENTAIRE_LIAISONTYPE = " . intval($idLiaisonType) . " and COM_IDLIAISON = " . intval($PAR_TPL_IDENTIFIANT) . " and COM_ETAT = 'VALIDE'";
                    } else {
                        Page::setNoCache();
                        $commmentairesSql = "select COMMENTAIRE.* from COMMENTAIRE
                        inner join DD_COMMENTAIRE_LIAISONTYPE on (DD_COMMENTAIRE_LIAISONTYPE.ID_COMMENTAIRE_LIAISONTYPE = COMMENTAIRE.ID_COMMENTAIRE_LIAISONTYPE)
                        where COMMENTAIRE.ID_COMMENTAIRE_LIAISONTYPE = " . intval($idLiaisonType) . " and COM_IDLIAISON = " . intval($PAR_TPL_IDENTIFIANT) . " and COM_ETAT in ('VALIDE','MODERATION')";
                    }

                    $aCommmentaires = $dbh->query($commmentairesSql)->fetchAll(PDO::FETCH_ASSOC);
                    $nbCommmentaires = count($aCommmentaires);

                    $str .= '<div id="com_' . $idIdentifiantUnique . '" class="commentaire paragraphe">';
                    /** Liens **/
                    $str .= '<div class="liens">';
                    $str .= '<a href="#form_comment_'.$idIdentifiantUnique.'" id="a_page_form_comment_'.$idIdentifiantUnique.'" class="submit">' . $oModule->i18n('com_je_reagis') . '</a>';
                    switch ($nbCommmentaires) {
                        case 0:
                           //do nothing
                           break;
                        case 1:
                            $str .= '<a href="#list_comment_'.$idIdentifiantUnique.'" id="a_page_list_comment_'.$idIdentifiantUnique.'" class="voir_liste">' . $nbCommmentaires . ' ' . $oModule->i18n('com_commentaire').'</a>';
                            break;
                        default:
                            $str .= '<a href="#list_comment_'.$idIdentifiantUnique.'" id="a_page_list_comment_'.$idIdentifiantUnique.'" class="voir_liste">' . $nbCommmentaires . ' ' . $oModule->i18n('com_commentaires').'</a>';
                           break;
                    }
                    $str .= '</div>';
                    /** Fin liens **/

                    /** Liste de commentaire **/
                    $str .= '<div class="page_comments" id="page_comments_'.$idIdentifiantUnique.'">';
                    $str .= '<div class="liste_comment" id="list_comment_'.$idIdentifiantUnique.'">';
                    foreach ($aCommmentaires as $unCommmentaire) {
                        $str .= '<p class="commentaire_message">'.encode($unCommmentaire['COM_MESSAGE']).'</p>';
                        $str .= '<p class="commentaire_infos note">'.encode(strftime('%d %B %Y %H:%M', $unCommmentaire['COM_DATE']) .' '. $oModule->i18n('com_commentaire_par' ). ' ' . $unCommmentaire['COM_PSEUDO']) .'</p>';
                        $str .= '<p class="commentaire_lien_abus">';
                        $str .= '<div class="commentaire_lien_abus">
                                     <form method="post" action="'.$oPage->getURL($aParams).'">
                                          <p class="submit">
                                            <span class="submit"><input type="submit" value="' . $oModule->i18n('com_message_abus') . '" class="submit"></span>
                                            <input type="hidden" value="'.$PAR_TPL_IDENTIFIANT.'" name="COM_TYPE_ID">
                                            <input type="hidden" value="'.$MOD_CODE.'" name="COM_TYPE">
                                            <input type="hidden" value="' . $unCommmentaire['ID_COMMENTAIRE'] . '" name="COM_ABUS">
                                        </p>
                                     </form>
                                 </div>';
                    }
                    $str .= '</div>';
                    /** Fin liste de commentaire **/

                    $utiPseudo = '';
                    $utiPseudoReadOnly = false;
                    $utiMail = '';
                    $utiMailReadOnly = false;
                    if ($oUtilisateur = Utilisateur::getConnected()) {
                        $utiPseudo = $oUtilisateur->getField('UTI_PRENOM');
                        $utiMail = $oUtilisateur->getField('UTI_EMAIL');
                    }
                    if (!empty($_POST['COM_PSEUDO'])) {
                        $utiPseudo = $_POST['COM_PSEUDO'];
                    }
                    if (!empty($_POST['COM_MAIL'])) {
                        $utiMail = $_POST['COM_MAIL'];
                    }

                    /** Formulaire de depot commentaire **/
                    $str .= '<form id="form_comment_'.$idIdentifiantUnique.'" method="post" action="'.$oPage->getURL($aParams).'#form_comment_'.$idIdentifiantUnique.'">';
                    $str .= '<fieldset class="groupeQuestion">';
                    $str .= '<legend><span>' . $oModule->i18n('com_monform') . '</span></legend>';

                    /** Message et charte **/
                    // Si commentaire enregistré, on n'affiche pas Message et charte
                    if (!isset($_POST['COM_MODULESOUMISSION']) || !empty($error)) {
                        //[Message aux internautes – dépôt d’un commentaire] 
                        //(cf. paragraphe Paramètres de modération)
                        //suivi, si le lien vers la charte de modération à été précisé (cf. paragraphe Paramètres de modération) du libellé du lien,
                        //cliquable pour arriver vers la page choisie.
                        if (!empty($aParamsCom['CPA_MES_DEPOT'])) {
                            $str .= '<div class="message_internate">';
                            $str .= Editor::displayContent($aParamsCom['CPA_MES_DEPOT'], $oPage);
                            $str .= '</div>';
                        }

                        if (intval($aParamsCom['ID_PAGE']) > 0) {
                            $oPageCharte = new Page($aParamsCom['ID_PAGE'], CMS::$mode);
                            if ($oPageCharte->exist()) {
                                $str .= '<p class="lien_charte">';
                                $str .= '<a '.$oPageCharte->getAnchor(array(), '', array('external')).'>'.encode($aParamsCom['CPA_LIBELLELIEN']).'</a>';
                                $str .= '</p>';
                            }
                        }
                    }
                    /** Fin Message et charte **/

                    /** Erreur **/
                    if (count($error) > 0) {
                        $str .= '<div class="message_error">';
                        foreach ($error as $er) {
                            $str .= '<p>'.encode($er).'</p>';
                        }
                        $str .= '</div>';
                    }
                    /** Fin Erreur **/
                    /** Message **/
                    if (count($message) > 0) {
                        $str .= '<div class="message_commentaires">';
                        foreach ($message as $ms) {
                            $str .= '<p>'.encode($ms).'</p>';
                        }
                        $str .= '</div>';
                    }
                    /** Fin Message **/
                    // Si commentaire enregistré, on n'affiche pas le contenu du formulaire
                    if (!isset($_POST['COM_MODULESOUMISSION']) || !empty($error)) {
                        $str .= '<div class="innerGroupeQuestion">';
                        $str .= '<p>
                                         <label for="COM_MESSAGE_'.$MOD_CODE.'_'.$PAR_TPL_IDENTIFIANT.'">' . $oModule->i18n('com_moncommentaire') . '</label>
                                         <textarea required id="COM_MESSAGE_'.$MOD_CODE.'_'.$PAR_TPL_IDENTIFIANT.'" name="COM_MESSAGE_'.$MOD_CODE.'_'.$PAR_TPL_IDENTIFIANT.'" rows="10" cols="50" onKeyDown="limitText(this,this.form.countdown,500);" onKeyUp="limitText(this,this.form.countdown,500);">'. encode($_POST['COM_MESSAGE']).'</textarea>
                                 </p>';
                        $str .= '<p class="note">'
                                             . $oModule->i18n('com_moncommentaire_info') . '<br>'
                                             . $oModule->i18n('com_moncommentaire_nombre') . '
                                             <input readonly type="text" name="countdown" size="3" value="0"> / 500
                                </p>';
                        $str .= '<p>
                                         <label for="COM_PSEUDO">' . $oModule->i18n('com_monpseudo') . '</label>
                                         <input required type="text" id="COM_PSEUDO" name="COM_PSEUDO" value="'.encode($utiPseudo, false).'" size="50" maxlength="255"';
                        if ($utiPseudoReadOnly) {
                             $str .= ' readonly="readonly"';
                        }
                        $str .= '></p>';
                        $str .= '<p>
                                         <label for="COM_MAIL">' . $oModule->i18n('com_monemail') . '</label>
                                         <input required type="email" id="COM_MAIL" name="COM_MAIL" value="'.encode($utiMail, false).'" size="50" maxlength="255"';
                        if ($utiMailReadOnly) {
                             $str .= ' readonly="readonly"';
                        }
                        $str .= '></p>';

                        $str .= '</div><!-- FIN .innerGroupeQuestion -->';
                        $str .= '</fieldset>';
                        $str .= '<p class="action">
                                    <span class="submit"><input name="COM_MODULESOUMISSION" type="submit" class="submit" value="' . $oModule->i18n('com_btn_envoyer') . '"></span>
                                    <input name="TYPE_MODULE" type="hidden" value="' . $MOD_CODE . '">
                                    <input name="ID_MODULE" type="hidden" value="' . $PAR_TPL_IDENTIFIANT . '">
                                </p>';
                    } else {$str .= '</fieldset>';} // FIN if (!isset($_POST['COM_MODULESOUMISSION']) || !empty($error)) {} # ==> Si commentaire enregistré, on n'affiche pas le contenu du formulaire
                    $str .= '</form>';
                    /** Fin formulaire de depot **/

                    $str .= '</div><!-- FIN .page_comments -->';

                     /** Partie JS **/
                    $js = '<script>';
                    $js .= 'function hideTPLComments_'.$idIdentifiantUnique.'() { $("#page_comments_'.$idIdentifiantUnique.'").hide(); } function showTPLComments_'.$idIdentifiantUnique.'() { $("#page_comments_'.$idIdentifiantUnique.'").show(); }';
                    if (!$showComments) {
                       CMS::addDOMREADY('$("#a_page_form_comment_'.$idIdentifiantUnique.'").click(function () { showTPLComments_'.$idIdentifiantUnique.'();return true; });$("#a_page_list_comment_'.$idIdentifiantUnique.'").click(function () { showTPLComments_'.$idIdentifiantUnique.'();return true; });hideTPLComments_'.$idIdentifiantUnique.'();');
                    }
                    $js .= '</script>';
                    /** Fin partie JS **/

                    $str .= $js;
                    $str .= '</div><!-- FIN .commentaire -->';
                } catch (Exception $e) {
                    $str = 'Une erreur a été détecté durant l\'affichage des liens commentaires. Veuillez contacter l\'adminitrateur du site.';
                }
            }

        }else $str = false;

        return $str;
    }

}
