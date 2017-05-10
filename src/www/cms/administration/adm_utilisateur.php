<?php
require '../../include/inc.bo_init.php';
CMS::checkAccess(new Module('MOD_CORE'));
require CLASS_DIR . 'class.db_page.php';

if (! Utilisateur::getConnected()->isRoot() || ! is_numeric($_GET['idtf'])) {
    $_GET['idtf'] = Utilisateur::getConnected()->getID();
}

$oUtilisateur = new Utilisateur($_GET['idtf']);
if ($oUtilisateur->exist()) {
    if ($oUtilisateur->getID() != Utilisateur::getConnected()->getID()) {
        $oUtilisateur->checkAuthorized();
    }
    $oPage = new Page($oUtilisateur->getField('ID_PAGE'));
    if ($oPage->exist()) {
        $TITRE_PAGEREDIRECTION = $oPage->getField('PAG_TITRE_MENU') . ' (' . $oPage->getID() . ')';
        $SITE_PAGEREDIRECTION = $oPage->getField('SIT_CODE');
        if ($SITE_PAGEREDIRECTION != CMS::getCurrentSite()->getID()) {
            $sql = "select SIT_LIBELLE from DD_SITE where SIT_CODE=" . $dbh->quote($SITE_PAGEREDIRECTION);
            $TITRE_PAGEREDIRECTION = '[' . $dbh->query($sql)->fetchColumn() . '] ' . $TITRE_PAGEREDIRECTION;
        }
        $ID_PAGE_REDIRECT = $oPage->getID();
    }
}
$row = $oUtilisateur->getFields();
?>
<!DOCTYPE html>
<html>
<head>
    <?php include('../../include/inc.bo_enTete.php')?>
    <script src="<?php echo SERVER_ROOT ?>include/js/onglet.js"></script>
    <script src="<?php echo SERVER_ROOT ?>include/js/ajx_checkUtilisateur.js"></script>
    <script>
        function postControl_formCreation(oForm)
        {
            selectAll('ID_GROUPE');
            selectAll('PRO_CODE');
            return true;
        }
        function extendedControl_UTI_LOGIN(nField)
        {
            if (document.getElementById('UTI_LOGIN_valid').value == 0) {
                alert ("<?php echo gettext('Cet identifiant est deja attribue a un autre utilisateur')?>");

                return false;
            }
            return true;
        }
        function extendedControl_UTI_EMAIL(nField)
        {
            if (document.getElementById('UTI_EMAIL_valid').value == 0) {
                alert ("<?php echo gettext('Cet email est deja attribue a un autre utilisateur')?>");

                return false;
            }

            return true;
        }
    </script>
</head>
<body>
<div id="document">
    <?php $aMenuKey = array('CFG', 'USER', 'UTILISATEUR'); if (!$oUtilisateur->exist()) $aMenuKey[] = 'ADD'; include('../../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">
            <h2><?php echo $oUtilisateur->exist() ? secureInput($row['UTI_PRENOM'] . ' ' . $row['UTI_NOM']) : 'Nouvel utilisateur' ?></h2>
            <form method="post" action="adm_utilisateurSubmit.php" id="formCreation" class="creation">
                <?php
                $bCanActivateAccount = false;
                // Si utilisateur sans mot de passe associé à une authentification interne non verrouillée, on ajoute un bouton permettant l'envoie du mail de demande de création de compte
                if (Utilisateur::getConnected()->isRoot() && $oUtilisateur->exist() && empty($row['UTI_PASSWORD']) && empty($row['ID_LDAP']) && empty($row['UTI_STATUT_LOCKED'])) {
                    $oUtilisateur->initSites();
                    $oUtilisateur->initProfils(CMS::getCurrentSite()->getID());
                    $oUtilisateur->initGroupes();
                    $oUtilisateur->initPages(CMS::getCurrentSite()->getID());
                    $aProfils = $oUtilisateur->getProfils();
                    // Si s'agit d'un contributeur, la génération du mot de passe est réalisée via le BO uniquement
                    if ($oUtilisateur->isRoot() || $oUtilisateur->isPageContributor() || ! empty($aProfils)) {
                        $bCanActivateAccount = true;
                    } else {
                        $sCompteInfo = '<div class="information">
                                    <p>Le compte de ' . secureInput($row['UTI_PRENOM'] . ' ' . $row['UTI_NOM']) . ' a bien été créé.</p>
                                    <p>Pour demander à cet utilisateur d’activer son compte, il vous faut lui attribuer des rôles liés à la contribution (via l’onglet « <a href="#infos_contribution" data-rel="tab">Contribution</a> » ci-dessous)</p>
                                    </div>';
                        $ModuleExtranet = new module("MOD_EXTRANET");
                        // S'il s'agit uniquement d'un contributeur FO, la procédure passe par la mise en place de la page spéciale "Authentification" de son site d'appartenance
                        if (CMS::getCurrentSite()->hasModule($ModuleExtranet) && ! CMS::getCurrentSite()->getSpecialePage('PGS_AUTHENTIFICATION', 'ON_')) {
                            $sCompteInfo = '<div class="information">
                                    <p>Le compte de ' . secureInput($row['UTI_PRENOM'] . ' ' . $row['UTI_NOM']) . ' a bien été créé.</p>
                                    <p>Pour demander à cet utilisateur d’activer son compte, il vous faut tout d’abord finaliser le paramétrage du compte :</p>
                                    <ul>
                                        <li>Il s\'agit d\'un contributeur back-office : Attribuez-lui des rôles liés à la contribution (via l’onglet « <a href="#infos_contribution" data-rel="tab">Contribution</a> » ci-dessous)</li>
                                        <li>Il s\'agit d\'un utilisateur front-office : Mettez-en ligne la page spéciale "Authentification".</li>
                                    </ul><br></div>';
                        } elseif (CMS::getCurrentSite()->getSpecialePage('PGS_AUTHENTIFICATION', 'ON_')) {
                            $bCanActivateAccount = true;
                        }
                        if (! $bCanActivateAccount) {
                            echo $sCompteInfo;
                        }
                    }
                }
                if ($oUtilisateur->exist()) {
                    $sCompteInfo = '';
                    $aAuth_info = ! empty($row['UTI_AUTH_INFO']) ? unserialize($row['UTI_AUTH_INFO']) : array();
                    // Même ordre des if, elseif que dans le select ci-dessous présent dans "adm_utilisateurListe.php"
                    // IF(UTI_STATUT_LOCKED=1,'Verrouillé',IF(UTI_STATUT_BLOCKED=1,'Bloqué',IF(UTI_LASTCONNEXION is NUll, 'Non actif','Actif'))) as STATUT
                    if ($row['UTI_STATUT_LOCKED'] == '1') {
                        $sCompteInfo = secureInput('Ce compte est verrouillé depuis le ' . strftime('%d %B %Y à %H:%M', $aAuth_info['datetime']));
                        echo '<p class="alert">' . $sCompteInfo . '</p>';
                    } elseif ($row['UTI_STATUT_BLOCKED'] == '1') {
                        $sCompteInfo = secureInput('Ce compte utilisateur a été bloqué le ' . strftime('%d %B %Y à %H:%M', $aAuth_info['datetime']) . ' depuis l\'IP ' . $aAuth_info['attempt_ip']);
                        // Si on est dans la période de blocage, on ajoute le bouton de déblocage manuel
                        if ($aAuth_info['datetime'] > strtotime('-' . CMS::getCurrentSite()->getField('SIT_CONNECTION_TTL') . ' mins')) {
                            $sCompteInfo .= '&nbsp;<input type="button" name="unblock" value="Débloquer manuellement le compte" class="modifier" onclick="window.location.href=\'adm_utilisateurSubmit.php?Unblock=' . $oUtilisateur->getID() . '\'">';
                        }
                        echo '<p class="alert">' . $sCompteInfo . '</p>';
                    } elseif ($bCanActivateAccount) {
                        $sCompteInfo = 'L\'utilisateur n\'a pas encore activé son compte. Cliquez sur le bouton « <strong>Demander l’activation du compte</strong> » : l\'utilisateur recevra un email avec un lien d’activation lui permettant de se connecter à son interface.';
                        // On n'affiche la date que si le compte n'est pas bloqué car dans ce cas, l'info correspond à la date de blocage et non à la date de dernier envoie du mail d'activation
                        if (empty($row['UTI_STATUT_BLOCKED']) && $aAuth_info['datetime']) {
                            $sTitle =  ' title="'.secureInput('Dernière demande d\'activation de compte envoyée le '.strftime('%d %B %Y à %H:%M', $aAuth_info['datetime'])).'"';
                        }
                        $sCompteInfo .= '<input type="button" value="Demander l\'activation du compte" class="submit"' . $sTitle . ' onclick="if (confirm(\''.escapeJS('Un mail va être envoyé à l\'utilisateur pour lui demander d\'activer son compte.') . '\')) window.location.href=\'adm_utilisateurSubmit.php?Activate=' .  $oUtilisateur->getID() . '\';">';
                        echo '<p class="information">' . $sCompteInfo . '</p>';
                    } elseif (! empty($row['UTI_LASTCONNEXION'])) {
                        $sCompteInfo = secureInput('Dernière connexion le ' . strftime('%d %B %Y à %H:%M', $row['UTI_LASTCONNEXION']));
                        echo '<p>' . $sCompteInfo . '</p>';
                    }
                }
                ?>
                <fieldset class="tab">
                    <legend><?php echo gettext('Informations')?></legend>
                    <table>
                        <tbody>
                            <tr>
                                <th><label for="UTI_NOM"><?php echo gettext('Nom')?></label></th>
                                <td>
                                    <select name="UTI_CIVILITE" id="UTI_CIVILITE" required>
                                        <option value="">&nbsp;</option>
                                        <option value="Mme"<?php if ($row["UTI_CIVILITE"]=='Mme') echo ' selected'?>><?php echo gettext('Madame')?></option>
                                        <option value="M"<?php if ($row["UTI_CIVILITE"]=='M') echo ' selected'?>><?php echo gettext('Monsieur')?></option>
                                    </select>
                                    <input name="UTI_NOM" type="text" id="UTI_NOM" value="<?php echo secureInput($row["UTI_NOM"])?>" size="30" required>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="UTI_PRENOM"><?php echo gettext('Prenom')?></label></th>
                                <td><input name="UTI_PRENOM" type="text" id="UTI_PRENOM" value="<?php echo secureInput($row['UTI_PRENOM'])?>" size="30" required></td>
                            </tr>
                            <tr>
                                <th><label for="UTI_EMAIL" class="extendedControl"><?php echo gettext('Email')?></label></th>
                                <td>
                                    <input name="UTI_EMAIL" type="email" id="UTI_EMAIL" value="<?php echo secureInput($row['UTI_EMAIL'])?>" size="40" maxlength="100" required>
                                    <span id="UTI_EMAIL_result" class="alert"></span>
                                    <input name="UTI_EMAIL_valid" id="UTI_EMAIL_valid" type="hidden" value="1">
                                </td>
                            </tr>
                            <tr>
                                <th><label for="UTI_TELEPHONE"><?php echo gettext('Telephone')?></label></th>
                                <td><input name="UTI_TELEPHONE" type="text" id="UTI_TELEPHONE" value="<?php echo secureInput($row['UTI_TELEPHONE'])?>" size="20" maxlength="20"></td>
                            </tr>
                            <tr>
                                <th><label for="UTI_FAX"><?php echo gettext('Fax')?></label></th>
                                <td><input name="UTI_FAX" type="text" id="UTI_FAX" value="<?php echo secureInput($row['UTI_FAX'])?>" size="20" maxlength="20"></td>
                            </tr>
                            <tr>
                                <th><label for="UTI_ORGANISME"><?php echo gettext('Organisme')?></label></th>
                                <td><input name="UTI_ORGANISME" type="text" id="UTI_ORGANISME" value="<?php echo secureInput($row['UTI_ORGANISME'])?>" size="40" maxlength="255"></td>
                            </tr>
                            <tr>
                                <th><label for="UTI_FONCTION"><?php echo gettext('Fonction')?></label></th>
                                <td><input name="UTI_FONCTION" type="text" id="UTI_FONCTION" value="<?php echo secureInput($row['UTI_FONCTION'])?>" size="40" maxlength="255"></td>
                            </tr>
                            <tr>
                                <th><label for="UTI_ADRESSE"><?php echo gettext('Adresse')?></label></th>
                                <td><textarea name="UTI_ADRESSE" id="UTI_ADRESSE" rows="4" cols="60"><?php echo secureInput($row["UTI_ADRESSE"])?></textarea></td>
                            </tr>
                            <tr>
                                <th><label for="UTI_CODEPOSTAL"><?php echo gettext('Code postal')?></label></th>
                                <td><input name="UTI_CODEPOSTAL" type="text" id="UTI_CODEPOSTAL" value="<?php echo secureInput($row["UTI_CODEPOSTAL"])?>" size="20" maxlength="255"></td>
                            </tr>
                            <tr>
                                <th><label for="UTI_VILLE"><?php echo gettext('Ville')?></label></th>
                                <td><input name="UTI_VILLE" type="text" id="UTI_VILLE" value="<?php echo secureInput($row["UTI_VILLE"])?>" size="20" maxlength="255"></td>
                            </tr>
                            <tr>
                                <th><label for="UTI_PAYS"><?php echo gettext('Pays')?></label></th>
                                <td><input name="UTI_PAYS" type="text" id="UTI_PAYS" value="<?php echo secureInput($row["UTI_PAYS"])?>" size="20" maxlength="255"></td>
                            </tr>
                        </tbody>
                    </table>
                </fieldset>

                <fieldset class="tab">
                    <legend><?php echo gettext('Authentification')?></legend>
                    <table>
                        <tbody>
                            <tr>
                                <th><label for="UTI_LOGIN" class="extendedControl"><?php echo gettext('Identifiant')?></label></th>
                                <td>
                                    <input name="UTI_LOGIN" type="text" id="UTI_LOGIN" value="<?php echo secureInput($row["UTI_LOGIN"])?>" size="20" maxlength="50" required>
                                    <span id="UTI_LOGIN_result" class="alert"></span>
                                    <input name="UTI_LOGIN_valid" id="UTI_LOGIN_valid" type="hidden" value="1">
                                </td>
                            </tr>
                            <?php
                            if (Utilisateur::getConnected()->isRoot()) {
                                $sql = "select ID_LDAP, LDA_LIBELLE from LDAP order by LDA_LIBELLE";
                                $aLdap = $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
                                if (extension_loaded('ldap') && count($aLdap) > 0) { ?>
                            <tr>
                                <th><label for="ID_LDAP">Mode d'authentification</label></th>
                                <td>
                                    <select name="ID_LDAP" id="ID_LDAP">
                                        <option value=""<?php if ($rowTemp['ID_LDAP'] == '') {echo ' selected';}?>>Interne</option>
                                        <?php foreach ($aLdap as $rowTemp) { ?>
                                        <option value="<?php echo secureInput($rowTemp['ID_LDAP'])?>"<?php if($row['ID_LDAP'] == $rowTemp['ID_LDAP']) echo ' selected'?>><?php echo secureInput($rowTemp['LDA_LIBELLE'])?></option>
                                        <?php } ?>
                                    </select>
                                </td>
                            </tr>
                            <?php
                                }
                            }
                            ?>
                            <tr>
                                <th><label for="LNG_CODE"><?php echo gettext('Langue')?></label></th>
                                <td>
                                <?php
                                $aLangue = $dbh->query("select * from DD_LANGUE where LNG_BO=1")->fetchAll(PDO::FETCH_ASSOC);
                                if (count($aLangue) > 1) { ?>
                                    <select name="LNG_CODE" id="LNG_CODE" required>
                                        <option value="">&nbsp;</option>
                                        <?php foreach ($aLangue as $rowTemp) { ?>
                                        <option value="<?php echo secureInput($rowTemp['LNG_CODE'])?>"<?php if($row['LNG_CODE'] == $rowTemp['LNG_CODE']) echo ' selected'?>><?php echo secureInput($rowTemp['LNG_LIBELLE'])?></option>
                                        <?php } ?>
                                    </select>
                                <?php } else { ?>
                                    <?php echo secureInput($aLangue[0]['LNG_LIBELLE'])?>
                                    <input type="hidden" name="LNG_CODE" id="LNG_CODE" value="<?php echo secureInput($aLangue[0]['LNG_CODE']) ?>">
                                <?php } ?>
                                </td>
                            </tr>
                            <?php if (Utilisateur::getConnected()->isPageContributor()) { ?>
                            <tr>
                                <th><label>Page d'accueil après authentification</label></th>
                                <td>
                                    <input type="hidden" value="<?php echo $ID_PAGE_REDIRECT?>" name="ID_PAGE" id="ID_PAGE">
                                    <input name="PAG_TITRE_MENU" type="text" id="PAG_TITRE_MENU" value="<?php echo secureInput($TITRE_PAGEREDIRECTION)?>" size="30" disabled>
                                    <a href="../cms_choisirLienInternePopup.php?IDENTIFIANT=ID_PAGE&amp;TEXTE=PAG_TITRE_MENU&amp;SIT_CODE=<?php echo $SITE_PAGEREDIRECTION?>"  class="action popup">Choisir</a>
                                    <a href="#" onclick="document.getElementById('PAG_TITRE_MENU').value=''; document.getElementById('ID_PAGE').value=''; return false;" class="action">Supprimer</a>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </fieldset>

                <?php
                if (Utilisateur::getConnected()->isRoot() && CMS::getCurrentSite()->hasModule(new Module('MOD_EXTRANET'))) {
                    // Par défaut, le SIT_CODE à utiliser pour la gestion des groupes correspond à celui du site courant
                    $GROUPE_SIT_CODE = CMS::getCurrentSite()->getID();
                    // Si l'utilisateur existe, on récupère le SIT_CODE du site d'origine
                    // dudit utilisateur pour la gestion des groupes
                    if ($oUtilisateur->exist()) {
                        $GROUPE_SIT_CODE = $oUtilisateur->getField('SIT_CODE');
                    } ?>
                <fieldset class="tab">
                    <legend><?php echo gettext('Extranet')?></legend>
                    <table>
                        <tbody>
                            <tr>
                                <th><label><?php echo gettext('Groupe(s)')?></label></th>
                                <td>
                                    <table class="selection">
                                        <tr>
                                            <th><?php echo gettext('Affecte(s)')?></th>
                                            <th>&nbsp;</th>
                                            <th><?php echo gettext('Disponible(s)')?></th>
                                        </tr>
                                        <tr>
                                            <td>
                                                <select name="ID_GROUPE[]" id="ID_GROUPE" size="6" multiple ondblclick="DeplaceCritere(document.getElementById('ID_GROUPE'), document.getElementById('ID_GROUPE_ALL'));">
                                                <?php
                                                // Si c'est un utilisateur existant on va chercher ses groupes, sinon on récupère les groupes par défaut
                                                if ($oUtilisateur->exist()) {
                                                    $sql = "select GROUPE.*, DD_SITE.* from GROUPE
                                                        inner join DD_SITE on GROUPE.SIT_CODE=DD_SITE.SIT_CODE
                                                        inner join GROUPE_UTILISATEUR on GROUPE.ID_GROUPE=GROUPE_UTILISATEUR.ID_GROUPE
                                                        where ID_UTILISATEUR=" . $oUtilisateur->getID() . "
                                                        order by SIT_LIBELLE, GRP_LIBELLE";
                                                } else {
                                                    $sql = "select * from GROUPE
                                                        inner join DD_SITE on GROUPE.SIT_CODE=DD_SITE.SIT_CODE
                                                        where GRP_DEFAUT_UTILISATEUR=1 and GROUPE.SIT_CODE=" . $dbh->quote($GROUPE_SIT_CODE) . "
                                                        order by SIT_LIBELLE, GRP_LIBELLE";
                                                }
                                                $notIn = " not in (-1";
                                                $SIT_CODE = '';
                                                foreach ($dbh->query($sql, PDO::FETCH_ASSOC) as $rowTemp) {
                                                    $notIn .= "," . intval($rowTemp['ID_GROUPE']);
                                                    if ($SIT_CODE != $rowTemp['SIT_CODE']) {
                                                        if ($SIT_CODE != '') {
                                                            echo '</optgroup>';
                                                        }
                                                        $SIT_CODE = $rowTemp['SIT_CODE'];?>
                                                        <optgroup label="<?php echo secureInput($rowTemp['SIT_LIBELLE'])?>">
                                                    <?php } ?>
                                                    <option value="<?php echo $rowTemp['ID_GROUPE']?>"><?php echo secureInput($rowTemp['GRP_LIBELLE'])?></option>
                                                    <?php
                                                }
                                                $notIn .= ")";?>
                                                        </optgroup>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="button" name="Button" value="&lt;&lt;" onclick="DeplaceCritere(document.getElementById('ID_GROUPE_ALL'), document.getElementById('ID_GROUPE'));">
                                                <input type="button" name="Button2" value="&gt;&gt;" onclick="DeplaceCritere(document.getElementById('ID_GROUPE'), document.getElementById('ID_GROUPE_ALL'));">
                                            </td>
                                            <td>
                                                <select name="ID_GROUPE_ALL[]" id="ID_GROUPE_ALL" size="6" multiple ondblclick="DeplaceCritere(document.getElementById('ID_GROUPE_ALL'), document.getElementById('ID_GROUPE'));">
                                                <?php
                                                //tous les groupes du site (courant ou du site d'origine de l'utilisateur) + ceux "ouverts"
                                                $sql = "select GROUPE.*, DD_SITE.* from GROUPE
                                                    inner join DD_SITE on GROUPE.SIT_CODE=DD_SITE.SIT_CODE
                                                    left join GROUPE_SITE on GROUPE.ID_GROUPE=GROUPE_SITE.ID_GROUPE
                                                    where GROUPE.ID_GROUPE $notIn and (GROUPE.SIT_CODE=" . $dbh->quote($GROUPE_SIT_CODE) . " or GROUPE_SITE.SIT_CODE=" . $dbh->quote($GROUPE_SIT_CODE) . ")
                                                    order by SIT_LIBELLE, GRP_LIBELLE";
                                                $SIT_CODE = '';
                                                foreach ($dbh->query($sql, PDO::FETCH_ASSOC) as $rowTemp) {
                                                    if ($SIT_CODE != $rowTemp['SIT_CODE']) {
                                                        if ($SIT_CODE != '') {
                                                            echo '</optgroup>';
                                                        }
                                                        $SIT_CODE = $rowTemp['SIT_CODE'];?>
                                                        <optgroup label="<?php echo secureInput($rowTemp['SIT_LIBELLE'])?>">
                                                    <?php } ?>
                                                    <option value="<?php echo $rowTemp['ID_GROUPE']?>"><?php echo secureInput($rowTemp['GRP_LIBELLE'])?></option>
                                                <?php } ?>
                                                        </optgroup>
                                                </select>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </fieldset>
                <?php } ?>

                <?php if (Utilisateur::getConnected()->isRoot()) {?>
                <fieldset class="tab" id="infos_contribution">
                    <legend>Contribution</legend>
                    <table>
                        <tbody>
                            <?php if (Utilisateur::getConnected()->isRoot(true)) {?>
                            <tr>
                                <th>
                                    <label>
                                    <?php
                                        $sql = "select PRO_LIBELLE from DD_PROFIL where PRO_CODE='PRO_ROOT'";
                                        echo extraireLibelle($dbh->query($sql)->fetchColumn());
                                        $sql = "select count(PRO_CODE) from ROLE where ID_UTILISATEUR=" . $oUtilisateur->getID() . " and PRO_CODE='PRO_ROOT'";
                                        $isRoot = $dbh->query($sql)->fetchColumn();
                                        $selfRootEdit = Utilisateur::getConnected()->getID() == $oUtilisateur->getID();?>
                                    </label>
                                </th>
                                <td>
                                    <input name="PRO_ROOT" id="PRO_ROOT_1" type="radio" value="1"<?php if ($isRoot) echo ' checked'?><?php if ($selfRootEdit) echo ' disabled';?>>
                                    <label for="PRO_ROOT_1"><?php echo gettext('Oui')?></label>
                                    <input name="PRO_ROOT" id="PRO_ROOT_0"  type="radio" value="0"<?php if (!$isRoot) echo ' checked'?><?php if ($selfRootEdit) echo ' disabled';?>>
                                    <label for="PRO_ROOT_0"><?php echo gettext('Non')?></label>
                                </td>
                            </tr>
                            <?php } ?>
                            <tr>
                                <th><label>Rôles sur les pages</label></th>
                                <td>
                                    <?php if (!$oUtilisateur->exist()) {?>
                                    Vous devez enregistrer l'utilisateur avant de lui associer des rôles sur des pages
                                    <?php } else { ?>
                                    <a href="adm_utilisateurProfilPopup.php?idtf=<?php echo $oUtilisateur->getID() ?>" class="action popup">Ajouter un rôle</a>
                                    <?php } ?>
                                </td>
                            </tr>
                            <?php
                            $sql = "select * from ROLE
                                inner join OFF_PAGE on ROLE.ID_PAGE = OFF_PAGE.ID_PAGE
                                inner join DD_PROFIL on ROLE.PRO_CODE = DD_PROFIL.PRO_CODE
                                where ID_UTILISATEUR=" . $oUtilisateur->getID() . " and ROLE.SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID()) . "
                                order by PRO_LIBELLE";
                            $aROLE = $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
                            if (count($aROLE) > 0) { ?>
                            <tr>
                                <td colspan="2">
                                    <table class="liste">
                                        <thead>
                                            <tr>
                                                <th>Point de départ</th>
                                                <th>Rôle</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($aROLE as $rowListe) { ?>
                                            <tr>
                                                <td><?php echo secureInput($rowListe['PAG_TITRE_MENU'] . ' (' . $rowListe['ID_PAGE'] . ')')?></td>
                                                <td class="aligncenter"><?php echo secureInput(extraireLibelle($rowListe['PRO_LIBELLE']))?></td>
                                                <td class="aligncenter">
                                                    <a href="adm_utilisateurProfilPopupSubmit.php?Delete=<?php echo $rowListe['ID_ROLE']?>&amp;idtf=<?php echo $oUtilisateur->getID() ?>" class="actionSupprimer confirm" title="Supprimer le rôle"><?php echo gettext('Supprimer')?></a>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            <?php } ?>
                            <tr>
                                <th><label><?php echo gettext('Autres roles')?></label></th>
                                <td>
                                    <table class="selection">
                                        <tr>
                                            <th><?php echo gettext('Affecte(s)')?></th>
                                            <th>&nbsp;</th>
                                            <th><?php echo gettext('Disponible(s)')?></th>
                                        </tr>
                                        <tr>
                                            <td>
                                                <select name="PRO_CODE[]" id="PRO_CODE" size="10" multiple ondblclick="DeplaceCritere(document.getElementById('PRO_CODE'), document.getElementById('PRO_CODE_ALL'));">
                                                <?php
                                                $MOD_GROUPE = '';
                                                $sql = 'select distinct MOD_GROUPE, PRO_LIBELLE, DD_PROFIL.PRO_CODE from ROLE
                                                    inner join DD_PROFIL on ROLE.PRO_CODE = DD_PROFIL.PRO_CODE
                                                    inner join MODULE_PROFIL on DD_PROFIL.PRO_CODE = MODULE_PROFIL.PRO_CODE
                                                    inner join DD_MODULE on MODULE_PROFIL.MOD_CODE = DD_MODULE.MOD_CODE
                                                    where ID_UTILISATEUR = ' . $oUtilisateur->getID() . ' and PRO_PAGE <> 1 and ROLE.SIT_CODE=' . $dbh->quote(CMS::getCurrentSite()->getID()) . '
                                                    order by MOD_GROUPE, PRO_LIBELLE';
                                                $aROLE = $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
                                                if (count($aROLE) > 0) {
                                                    foreach ($aROLE as $rowTemp) {
                                                        if ($MOD_GROUPE != $rowTemp['MOD_GROUPE']) {
                                                            if ($MOD_GROUPE != '') {
                                                                echo '</optgroup>';
                                                            }
                                                            $MOD_GROUPE = $rowTemp['MOD_GROUPE'];?>
                                                            <optgroup label="<?php echo secureInput(extraireLibelle($MOD_GROUPE))?>">
                                                        <?php } ?>
                                                        <option value="<?php echo $rowTemp['PRO_CODE']?>"><?php echo secureInput(extraireLibelle($rowTemp['PRO_LIBELLE']))?></option>
                                                    <?php } ?>
                                                    </optgroup>
                                                <?php } ?>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="button" name="Button" value="&lt;&lt;" onclick="DeplaceCritere(document.getElementById('PRO_CODE_ALL'), document.getElementById('PRO_CODE'));">
                                                <input type="button" name="Button2" value="&gt;&gt;" onclick="DeplaceCritere(document.getElementById('PRO_CODE'), document.getElementById('PRO_CODE_ALL'));">
                                            </td>
                                            <td>
                                                <select name="PRO_CODE_ALL[]" id="PRO_CODE_ALL" size="10" multiple ondblclick="DeplaceCritere(document.getElementById('PRO_CODE_ALL'), document.getElementById('PRO_CODE'));">
                                                <?php
                                                $MOD_GROUPE = '';
                                                $sql = 'select * from DD_PROFIL
                                                    inner join MODULE_PROFIL on DD_PROFIL.PRO_CODE = MODULE_PROFIL.PRO_CODE
                                                    inner join DD_MODULE on MODULE_PROFIL.MOD_CODE = DD_MODULE.MOD_CODE
                                                    inner join SITE_MODULE on DD_MODULE.MOD_CODE = SITE_MODULE.MOD_CODE
                                                    where PRO_PAGE <> 1
                                                    and DD_PROFIL.PRO_CODE not in (
                                                        select PRO_CODE
                                                        from ROLE
                                                        where ID_UTILISATEUR=' . $oUtilisateur->getID() . '
                                                        and SIT_CODE=' . $dbh->quote(CMS::getCurrentSite()->getID()) . '
                                                    )
                                                    and DD_PROFIL.PRO_CODE <> \'PRO_ROOT\'
                                                    and SITE_MODULE.SIT_CODE =  ' . $dbh->quote(CMS::getCurrentSite()->getID()) . '
                                                    group by DD_PROFIL.PRO_CODE
                                                    order by MOD_GROUPE, PRO_LIBELLE';
                                                foreach ($dbh->query($sql) as $rowTemp) {
                                                    if ($MOD_GROUPE != $rowTemp['MOD_GROUPE']) {
                                                        if ($MOD_GROUPE != '') {
                                                            echo '</optgroup>';
                                                        }
                                                        $MOD_GROUPE = $rowTemp['MOD_GROUPE']; ?>
                                                        <optgroup label="<?php echo secureInput(extraireLibelle($MOD_GROUPE))?>">
                                                    <?php } ?>
                                                    <option value="<?php echo $rowTemp['PRO_CODE']?>"><?php echo secureInput(extraireLibelle($rowTemp['PRO_LIBELLE']))?></option>
                                                <?php } ?>
                                                    </optgroup>
                                                </select>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </fieldset>
                <?php } ?>

                <table>
                    <tfoot>
                        <tr>
                            <td colspan="2">
                                <?php if ($oUtilisateur->exist()) { ?>
                                    <input type="hidden" name="idtf" id="idtf" value="<?php echo $oUtilisateur->getID()?>">
                                    <input type="submit" name="Update" value="<?php echo gettext('UPDATE')?>" class="modifier">
                                    <?php if (Utilisateur::getConnected()->isRoot()) {?>
                                        <input type="button" name="Delete" value="<?php echo gettext('DELETE')?>" onclick="if (confirm('<?php echo gettext('Etes-vous sur ?')?>')) window.location.href='adm_utilisateurSubmit.php?Delete=<?php echo $oUtilisateur->getID()?>'" class="supprimer"<?php if (!$oUtilisateur->isDeletable()) echo ' disabled'?>>
                                        <?php
                                        $sDisabled = '';
                                        if (Utilisateur::getConnected()->getID() != $oUtilisateur->getID()) {
                                            if ($oUtilisateur->getField('UTI_STATUT_LOCKED')) {
                                                $sValue = "Déverrouiller le compte";
                                                $sLock = 0;
                                                $sClass = 'modifier';
                                                $sConfirm = 'Vous allez déverrouiller ce compte, l\'utilisateur pourra dorénavant se connecter à son interface.';
                                            } else {
                                                $sValue = "Verrouiller le compte";
                                                $sLock = 1;
                                                $sClass = 'supprimer';
                                                $sConfirm = 'Vous allez verrouiller ce compte, l\'utilisateur ne pourra plus se connecter à son interface.';
                                            }
                                        ?>
                                            <input type="button" name="Lock" value="<?php echo secureInput($sValue)?>" class="<?php echo $sClass;?>"<?php echo $sDisabled;?> onclick="if (confirm('<?php echo escapeJS($sConfirm);?>')) window.location.href='adm_utilisateurSubmit.php?Lock=<?php echo intval($sLock)?>&amp;idtf=<?php echo $oUtilisateur->getID()?>'">
                                        <?php
                                        }
                                        // Possibilité de demander le changement de mot de passe ?
                                        // Si utilisateur actif associé à une authentification interne non verrouillée,
                                        // on ajoute un bouton permettant de demander le changement de mot de passe
                                        if ($oUtilisateur->exist()
                                            && !empty($row['UTI_PASSWORD']) && empty($row['ID_LDAP'])
                                            && empty($row['UTI_STATUT_LOCKED'])
                                        ) {
                                            $sClasse =$row['UTI_PWD_MUSTBECHANGED']?'class="submit disabled" disabled':'class="submit"';
                                        ?>
                                            <input type="button" value="Forcer le changement de mot de passe utilisateur"
                                                <?php echo $sClasse;?>
                                                onclick="if (confirm('Vous avez demandé la modification du mot de passe utilisateur. L\'utilisateur devra modifier son mot de passe à sa prochaine connexion.'))
                                                    window.location.href='adm_utilisateurSubmit.php?pwdMustBeChanged=<?php echo $oUtilisateur->getID()?>';">
                                        <?php
                                        }
                                    }
                                    ?>
                                <?php
                                } else {
                                ?>
                                <input type="submit" name="Insert" value="<?php echo gettext('INSERT')?>" class="ajouter">
                                <?php
                                }
                                ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </form>
        </div>
    </div>
    <?php include('../../include/inc.bo_bandeau_bas.php')?>
</div>
</body>
</html>
