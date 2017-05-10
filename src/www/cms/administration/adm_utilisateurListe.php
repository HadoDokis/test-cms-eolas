<?php
require '../../include/inc.bo_init.php';
CMS::checkAccess(new Module('MOD_CORE'), array('PRO_ROOT_SITE'));
require CLASS_DIR . 'class.Pagination.php';
//on recupere tous les params get avant que la pagination ne les efface
$GETPARAMS = $_GET;
$p = new Pagination();
$filtre = "UTILISATEUR.SIT_CODE in (" . $dbh->quote(CMS::getCurrentSite()->getID());
foreach (CMS::getCurrentSite()->getRevertSharedSites() as $SIT_CODE=>$null) {
    $filtre .= ", " . $dbh->quote($SIT_CODE);
}
$filtre .= ")";

if ($p->onSearch()) {
    if (!empty($_GET['UTI_NOM'])) {
        $filtre .= " and (UTI_NOM" . $p->makeLike('UTI_NOM') . " or UTI_PRENOM" . $p->makeLike('UTI_NOM') . ")";
    }
    if (!empty($_GET['UTI_EMAIL'])) {
        $filtre .= " and UTI_EMAIL" . $p->makeLike('UTI_EMAIL');
    }
    if (!empty($_GET['UTI_LOGIN'])) {
        $filtre .= " and UTI_LOGIN" . $p->makeLike('UTI_LOGIN');
    }
    if (!empty($_GET['PRO_CODE'])) {
        $filtre .= " and ID_UTILISATEUR in (select ID_UTILISATEUR from ROLE where PRO_CODE=" . $dbh->quote($_GET['PRO_CODE']);
        if ($_GET['PRO_CODE'] != 'PRO_ROOT') {
            $filtre .= " and SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID());
        }
        $filtre .= ")";
    }
    if (is_numeric($_GET['ID_GROUPE'])) {
        $filtre .= " and ID_UTILISATEUR in (select ID_UTILISATEUR from GROUPE_UTILISATEUR where ID_GROUPE=" . intval($_GET['ID_GROUPE']) . ")";
    }
    if (is_numeric($_GET['origine'])) {
        $filtre .= ($_GET['origine']) ? " and UTILISATEUR.SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID()) : " and UTILISATEUR.SIT_CODE<>" . $dbh->quote(CMS::getCurrentSite()->getID());
    }
    if (is_numeric($_GET['contributeur'])) {
        $filtre .= ($_GET['contributeur'])
            ? " and UTILISATEUR.ID_UTILISATEUR in (select distinct(ID_UTILISATEUR) from ROLE)"
            : " and UTILISATEUR.ID_UTILISATEUR not in (select distinct(ID_UTILISATEUR) from ROLE)";
    }
    if (!empty($_GET['statut'])) {
        switch ($_GET['statut']) {
            case "active":
                $filtre .= " and UTI_LASTCONNEXION is not null and UTI_STATUT_BLOCKED!=1 and UTI_STATUT_LOCKED!=1";
                break;
            case "inactive":
                $filtre .= " and UTI_LASTCONNEXION is null and UTI_STATUT_BLOCKED!=1 and UTI_STATUT_LOCKED!=1";
                break;
            case "blocked":
                $filtre .= " and UTI_STATUT_BLOCKED=1";
                break;
            case "locked":
                $filtre .= " and UTI_STATUT_LOCKED=1";
                break;
        }
    }
    if (is_numeric($_GET['ID_LDAP'])) {
        if ($_GET['ID_LDAP'] == '-1') {
            $filtre .= " and ID_LDAP is null";
        } else {
            $filtre .= " and ID_LDAP=" . $dbh->quote($_GET['ID_LDAP']);
        }
    }
} else {
    $p->setOrderBy('UTI_LASTCONNEXION desc');
}
$p->setFilter($filtre);
$p->setCount("select count(ID_UTILISATEUR) from UTILISATEUR");
$strEmail = "";
if (isset($_GET['export'])) {
        set_time_limit(0);
        ob_clean();
        header("Content-type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=Utilisateur.csv");

        //nom / prénom / e-mail / groupe / profils / dernière connexion
        if (CMS::getCurrentSite()->hasModule(new Module('MOD_EXTRANET'))) {
            $csv = "nom;prénom;e-mail;groupe;profils;dernière connexion;Etat"."\n";
        } else {
            $csv = "nom;prénom;e-mail;profils;dernière connexion;Etat"."\n";
        }
        $aCarRemp = array(';'=>',',chr(10)=>' ',chr(13)=>' ');
        $sql = "select *,
            IF(UTI_STATUT_LOCKED=1,'Verrouillé',IF(UTI_STATUT_BLOCKED=1,'Bloqué',IF(UTI_LASTCONNEXION is NUll, 'Non actif','Actif'))) as STATUT
            from UTILISATEUR inner join DD_SITE using (SIT_CODE)";
        foreach ($p->fetch($sql,'',false) as $rowListe) {

            $csv .= str_replace(array_keys($aCarRemp),array_values($aCarRemp),secureInput($rowListe['UTI_NOM'])).';';
            $csv .= str_replace(array_keys($aCarRemp),array_values($aCarRemp),secureInput($rowListe['UTI_PRENOM'])).';';
            $csv .= str_replace(array_keys($aCarRemp),array_values($aCarRemp),secureInput($rowListe['UTI_EMAIL'])).';';
            //groupe
            if (CMS::getCurrentSite()->hasModule(new Module('MOD_EXTRANET'))) {
                $sql = "select GRP_LIBELLE from GROUPE_UTILISATEUR
                    inner join GROUPE using(ID_GROUPE)
                    where ID_UTILISATEUR=" . intval($rowListe['ID_UTILISATEUR']);
                $strGroupe = '';
                $aGroupeListe = $dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN);
                foreach ($aGroupeListe as $unGroupe) {
                    if(!empty($strGroupe)) $strGroupe .= '|';
                    $strGroupe .= $unGroupe;
                }
                $csv .= str_replace(array_keys($aCarRemp),array_values($aCarRemp),utf8_encode($strGroupe)).';';
            }

            //profils
            $strProfil = '';
            $sql = "select distinct SIT_LIBELLE, MOD_GROUPE, PRO_LIBELLE, DD_PROFIL.PRO_CODE from ROLE
                left join DD_SITE on ROLE.SIT_CODE = DD_SITE.SIT_CODE
                inner join DD_PROFIL on ROLE.PRO_CODE = DD_PROFIL.PRO_CODE
                inner join MODULE_PROFIL on DD_PROFIL.PRO_CODE = MODULE_PROFIL.PRO_CODE
                inner join DD_MODULE on MODULE_PROFIL.MOD_CODE = DD_MODULE.MOD_CODE
                where ID_UTILISATEUR=" . intval($rowListe['ID_UTILISATEUR']) . "
                order by SIT_LIBELLE, MOD_GROUPE, PRO_LIBELLE";
            $aPROFIL = $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            if (count($aPROFIL) > 0) {
                foreach ($aPROFIL as $rowTemp) {
                    if ($rowTemp['PRO_CODE'] == 'PRO_ROOT') {
                        $strProfil .= extraireLibelle($rowTemp['PRO_LIBELLE']);
                    } else {
                        $strProfil .= $rowTemp['SIT_LIBELLE'] . ' : ' . extraireLibelle($rowTemp['MOD_GROUPE']) . ' - ' . extraireLibelle($rowTemp['PRO_LIBELLE']) . '|';
                    }
                }
            }
            $csv .= str_replace(array_keys($aCarRemp),array_values($aCarRemp),secureInput($strProfil)).';';

            $dateDerniereConnexion = '-';
            if ($rowListe['UTI_LASTCONNEXION']) {
                $dateDerniereConnexion = date('d/m/Y H:i', $rowListe['UTI_LASTCONNEXION']);
            }
            $csv .= str_replace(array_keys($aCarRemp),array_values($aCarRemp),secureInput($dateDerniereConnexion)).';';
            $csv .= str_replace(array_keys($aCarRemp),array_values($aCarRemp),secureInput($rowListe['STATUT']));
            $csv .= "\n";

        }
        echo utf8_decode($csv);
        exit();
} else if (isset($_GET['POPUPCOPY'])) {
    $sql = "select UTI_EMAIL from UTILISATEUR inner join DD_SITE using (SIT_CODE)";

    $strEmail .= "<div id='copymail'><h3>".secureInput(gettext('recuperer_la_liste_des_adresses_email'))."</h4><div><p><textarea id='copytext' rows='10' cols='88' readonly>";
    foreach ($p->fetch($sql,'',false) as $rowListe) {
        $strEmail .= $rowListe['UTI_EMAIL'] . "\n";
    }
    $strEmail .= "</textarea></p><p><input class='submit' type='button' value='".secureInput(gettext('info_copier_coller_email'))."' id='copybutton'></p></div></div>";
    $remove = array("\n", "\r\n", "\r");
    $strEmail = str_replace($remove, "\\n", trim($strEmail));
} elseif (isset($_GET['PWDMUSTBECHANGED'])) {
    // Pas de filtre sur le site d'origine car on permet à un administrateur de demander
    // le changement de mot de passe, même à un utilisateur provenant d'un autre site
    $sql = "select ID_UTILISATEUR, UTI_PASSWORD, ID_LDAP, UTI_STATUT_LOCKED from UTILISATEUR inner join DD_SITE using (SIT_CODE)";
    $stmt = $dbh->prepare("update UTILISATEUR
            set UTI_PWD_MUSTBECHANGED=:UTI_PWD_MUSTBECHANGED
            where ID_UTILISATEUR=:idtf");
    $stmt->bindValue(':UTI_PWD_MUSTBECHANGED', 1, PDO::PARAM_INT);
    $countPwdMustBeChanged = 0;
    foreach ($p->fetch($sql,'',false) as $rowListe) {
        if (!empty($rowListe['UTI_PASSWORD']) && empty($rowListe['ID_LDAP'])
            && empty($rowListe['UTI_STATUT_LOCKED'])
        ) {
            $stmt->bindValue(':idtf',$rowListe['ID_UTILISATEUR'], PDO::PARAM_INT);
            $stmt->execute();
            $countPwdMustBeChanged ++;
        }
    }
    if ($countPwdMustBeChanged > 0) {
        setMsg("La demande de changement de mot de passe lors de la prochaine connexion des utilisateurs a bien été enregistrée pour ".$countPwdMustBeChanged." d'entre eux.");
    } else {
        setMsg("La demande de changement de mot de passe ne peut être réalisée car aucun compte ne remplit pas les conditions nécessaires.", 'ERROR');
    }

}
?>
<!DOCTYPE html>
<html>
<head>
<?php include('../../include/inc.bo_enTete.php') ?>
<?php if (isset($_GET['POPUPCOPY'])) {?>
<script>
var contentHTML = <?php echo json_encode($strEmail)?>;
function popUpLight() {
    $.colorbox({
        html:contentHTML.replace(/\\n/g, '\n'),
        onComplete: function () {
            $('#copybutton').click(function () {
                window.prompt ("<?php echo secureInput(gettext('pour_copier_ctrl_x_puis_ok'))?>", $('#copytext').val().replace(/\n/g,';'));
            });
        },
        close: 'Fermer',
        maxWidth: '95%',
        maxHeight: '95%',
        innerWidth: '700px'
    });
}
$(document).ready(function () {
    $.valHooks.textarea = {
        get: function (elem) {
            return elem.value.replace( /\r?\n/g, "\r\n" );
        }
    };
    popUpLight();
});
</script>
<?php }?>
</head>
<body>
<div id="document">
    <?php $aMenuKey = array('CFG', 'USER', 'UTILISATEUR' ,'LISTE'); include('../../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">
            <h2>Utilisateurs</h2>
            <form method="get" action="<?php echo PHP_SELF?>" class="filtre">
                <fieldset>
                    <legend><?php echo gettext('MOTEUR_RECHERCHE')?></legend>
                    <table>
                        <tfoot>
                            <tr>
                                <td colspan="4"><?php echo $p->actionRecherche()?></td>
                            </tr>
                        </tfoot>
                        <tbody>
                            <tr>
                                <th><label for="UTI_NOM">Nom / Prénom</label></th>
                                <td><input type="text" name="UTI_NOM" id="UTI_NOM" value="<?php echo $p->getParam('UTI_NOM')?>" size="25"></td>
                                <th><label for="UTI_EMAIL">Email</label></th>
                                <td><input type="text" name="UTI_EMAIL" id="UTI_EMAIL" value="<?php echo $p->getParam('UTI_EMAIL')?>" size="50"></td>
                            </tr>
                            <tr>
                                <th><label for="UTI_LOGIN">Identifiant</label></th>
                                <td><input type="text" name="UTI_LOGIN" id="UTI_LOGIN" value="<?php echo $p->getParam('UTI_LOGIN')?>" size="35"></td>
                                <th><label for="PRO_CODE">Rôle</label></th>
                                <td>
                                    <select id="PRO_CODE" name="PRO_CODE">
                                        <option value="">&nbsp;</option>
                                        <?php
                                        $MOD_GROUPE = '';
                                        $sql = 'select distinct MOD_GROUPE, PRO_LIBELLE, DD_PROFIL.PRO_CODE from DD_PROFIL
                                            inner join MODULE_PROFIL on DD_PROFIL.PRO_CODE = MODULE_PROFIL.PRO_CODE
                                            inner join DD_MODULE on MODULE_PROFIL.MOD_CODE = DD_MODULE.MOD_CODE
                                            inner join SITE_MODULE on DD_MODULE.MOD_CODE = SITE_MODULE.MOD_CODE
                                            where SITE_MODULE.SIT_CODE=' . $dbh->quote(CMS::getCurrentSite()->getID()) . '
                                            order by MOD_GROUPE, PRO_LIBELLE';
                                        foreach ($dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $rowTemp) {
                                            if ($MOD_GROUPE != $rowTemp['MOD_GROUPE']) {
                                                if ($MOD_GROUPE != '') {
                                                    echo '</optgroup>';
                                                }
                                                $MOD_GROUPE = $rowTemp['MOD_GROUPE'];?>
                                                <optgroup label="<?php echo secureInput(extraireLibelle($MOD_GROUPE))?>">
                                            <?php } ?>
                                            <option value="<?php echo secureInput($rowTemp['PRO_CODE'])?>"<?php if ($_GET['PRO_CODE'] == $rowTemp['PRO_CODE']) echo ' selected'?>><?php echo secureInput(extraireLibelle($rowTemp['PRO_LIBELLE']))?></option>
                                        <?php } ?>
                                        </optgroup>
                                    </select>
                                </td>
                            </tr>
                            <?php if (count(CMS::getCurrentSite()->getRevertSharedSites()) > 0) {?>
                            <tr>
                                <th><label>Origine</label></th>
                                <td>
                                    <input type="radio" name="origine" id="origine_1" value="1"<?php if ($p->getParam('origine') == '1') echo ' checked'?>><label for="origine_1"><?php echo gettext('Site courant')?></label>
                                    <input type="radio" name="origine" id="origine_0" value="0"<?php if ($p->getParam('origine') == '0') echo ' checked'?>><label for="origine_0"><?php echo gettext('Autres sites')?></label>
                                    <input type="radio" name="origine" id="origine" value=""<?php if ($p->getParam('origine') == '') echo ' checked'?>><label for="origine">N/A</label>
                                </td>
                            </tr>
                            <?php } ?>

                            <?php if (CMS::getCurrentSite()->hasModule(new Module('MOD_EXTRANET'))) {?>
                            <tr>
                                <th><label for="ID_GROUPE">Groupe</label></th>
                                <td>
                                    <select name="ID_GROUPE" id="ID_GROUPE">
                                        <option value="">&nbsp;</option>
                                        <?php
                                        $sql = "select * from GROUPE
                                            where SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID()) . "
                                            order by GRP_LIBELLE";
                                        foreach ($dbh->query($sql, PDO::FETCH_ASSOC) as $rowTemp) {?>
                                            <option value="<?php echo $rowTemp['ID_GROUPE']?>" <?php if($p->getParam('ID_GROUPE') == $rowTemp['ID_GROUPE']) echo 'selected'?>><?php echo secureInput($rowTemp['GRP_LIBELLE'])?></option>
                                        <?php } ?>
                                    </select>
                                </td>
                                <th><label>Contributeur</label></th>
                                <td>
                                    <input type="radio" name="contributeur" id="contributeur_1" value="1"<?php if ($p->getParam('contributeur') == '1') echo ' checked'?>><label for="contributeur_1"><?php echo gettext('Oui')?></label>
                                    <input type="radio" name="contributeur" id="contributeur_0" value="0"<?php if ($p->getParam('contributeur') == '0') echo ' checked'?>><label for="contributeur_0"><?php echo gettext('Non')?></label>
                                    <input type="radio" name="contributeur" id="contributeur" value=""<?php if ($p->getParam('contributeur') == '') echo ' checked'?>><label for="contributeur">N/A</label>
                                </td>
                            </tr>
                            <?php } ?>
                            <tr>
                            <?php
                            $sql = "select ID_LDAP, LDA_LIBELLE from LDAP order by LDA_LIBELLE";
                            $aLdap = $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
                            if (extension_loaded('ldap') && count($aLdap) > 0) {
                            ?>
                                <th><label for="ID_LDAP">Mode d'authentification</label></th>
                                <td>
                                    <select name="ID_LDAP" id="ID_LDAP">
                                        <option value="">&nbsp;</option>
                                        <option value="-1"<?php if ($p->getParam('ID_LDAP') == '-1') {echo ' selected';}?>>Interne</option>
                                        <?php
                                        foreach ($aLdap as $rowTemp) {
                                        ?>
                                            <option value="<?php echo secureInput($rowTemp['ID_LDAP'])?>"<?php if($p->getParam('ID_LDAP') == $rowTemp['ID_LDAP']) echo ' selected'?>><?php echo secureInput($rowTemp['LDA_LIBELLE'])?></option>
                                        <?php
                                        }
                                        ?>
                                    </select>
                                </td>
                            <?php
                            }
                            ?>
                                <th><label>Etat</label></th>
                                <td>
                                    <input type="radio" name="statut" id="statut_active" value="active"<?php if ($p->getParam('statut') == 'active') echo ' checked'?>><label for="statut_active">Actif</label>
                                    <input type="radio" name="statut" id="statut_inactive" value="inactive"<?php if ($p->getParam('statut') == 'inactive') echo ' checked'?>><label for="statut_inactive">Non actif</label>
                                    <input type="radio" name="statut" id="statut_blocked" value="blocked"<?php if ($p->getParam('statut') == 'blocked') echo ' checked'?>><label for="statut_blocked">Bloqué</label>
                                    <input type="radio" name="statut" id="statut_locked" value="locked"<?php if ($p->getParam('statut') == 'locked') echo ' checked'?>><label for="statut_locked">Verrouillé</label>
                                    <input type="radio" name="statut" id="statut" value=""<?php if ($p->getParam('statut') == '') echo ' checked'?>><label for="statut">N/A</label>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </fieldset>
            </form>
            <?php
echo $p->reglette();
if ($p->getNb() > 0) {
?>
            <div class="creation">
                <table>
                    <tfoot>
                        <tr>
                            <td class="alignright">
                                <?php
                                $strUrlParam = "";
                                foreach ($GETPARAMS as $param => $val) {
                                    if (!in_array($param, array('export', 'POPUPCOPY', 'PWDMUSTBECHANGED'))) {
                                        $strUrlParam .= '&';
                                        $strUrlParam .= $param . '=' . $val;
                                    }
                                }
                                ?>

                                <a class="btnAction" href="<?php echo PHP_SELF?>?export=1<?php echo secureInput($strUrlParam);?>" title="<?php echo gettext('exporter_la_liste_au_format_csv')?>">
                                <?php echo gettext('exporter_la_liste_au_format_csv')?>
                                </a>
                                <a class="btnAction" href="<?php echo PHP_SELF?>?POPUPCOPY=1<?php echo secureInput($strUrlParam);?>" title="<?php echo gettext('recuperer_la_liste_des_adresses_email')?>">
                                <?php echo gettext('recuperer_la_liste_des_adresses_email')?>
                                </a>
                                <a class="btnAction noMargin"
                                    href="<?php echo PHP_SELF?>?PWDMUSTBECHANGED=1<?php echo secureInput($strUrlParam);?>"
                                    title="Forcer le changement de mot de passe utilisateur"
                                    onclick="return confirm('Vous avez demandé la modification du mot de passe utilisateur pour les utilisateurs de la liste issue du filtre de recherche. Les utilisateurs devront modifier leur mot de passe à leur prochaine connexion.')">
                                Forcer le changement de mot de passe utilisateur
                                </a>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <table class="liste">
                <thead>
                    <tr>
                        <th><?php echo $p->tri(gettext('Utilisateur'), 'UTI_NOM')?></th>
                        <?php if (count(CMS::getCurrentSite()->getRevertSharedSites()) > 0) {?>
                        <th><?php echo gettext('Origine')?></th>
                        <?php } ?>
                        <?php if (CMS::getCurrentSite()->hasModule(new Module('MOD_EXTRANET'))) { ?>
                        <th><?php echo gettext('Groupe')?></th>
                        <?php } ?>
                        <th><?php echo gettext('Profil(s)')?></th>
                        <th><?php echo $p->tri(gettext('Derniere connexion'), 'UTI_LASTCONNEXION')?></th>
                        <th><?php echo $p->tri('Etat', 'statut')?></th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $sql = "select *,
                    IF(UTI_STATUT_LOCKED=1,'Verrouillé',IF(UTI_STATUT_BLOCKED=1,'Bloqué',IF(UTI_LASTCONNEXION is NUll, 'Non actif','Actif'))) as STATUT
                    from UTILISATEUR inner join DD_SITE using (SIT_CODE)";
                foreach ($p->fetch($sql) as $rowListe) {
                    ?>
                    <tr>
                        <td>
                            <a href="adm_utilisateur<?php if ($rowListe['SIT_CODE'] != CMS::getCurrentSite()->getID()) echo 'Share'?>.php?idtf=<?php echo $rowListe['ID_UTILISATEUR']?>"><?php echo secureInput($rowListe['UTI_NOM'] . ' ' . $rowListe['UTI_PRENOM'])?></a>
                            <br><?php echo secureInput($rowListe['UTI_EMAIL'])?>
                        </td>
                        <?php if (count(CMS::getCurrentSite()->getRevertSharedSites()) > 0) {?>
                        <td><?php echo secureInput($rowListe['SIT_LIBELLE'])?></td>
                        <?php } ?>
                        <?php if (CMS::getCurrentSite()->hasModule(new Module('MOD_EXTRANET'))) { ?>
                        <td>
                            <?php
                            $sql = "select GRP_LIBELLE from GROUPE_UTILISATEUR
                                inner join GROUPE using(ID_GROUPE)
                                where ID_UTILISATEUR=" . intval($rowListe['ID_UTILISATEUR']);
                            $aGROUPE = $dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN);
                            if (count($aGROUPE) > 0) {
                                echo '<ul>';
                                foreach ($aGROUPE as $GRP_LIBELLE) echo '<li>'.secureInput($GRP_LIBELLE).'</li>';
                                echo '<ul>';
                            } ?>
                        </td>
                        <?php } ?>
                        <td>
                            <?php
                            $sql = "select distinct SIT_LIBELLE, MOD_GROUPE, PRO_LIBELLE, DD_PROFIL.PRO_CODE from ROLE
                                left join DD_SITE on ROLE.SIT_CODE = DD_SITE.SIT_CODE
                                inner join DD_PROFIL on ROLE.PRO_CODE = DD_PROFIL.PRO_CODE
                                inner join MODULE_PROFIL on DD_PROFIL.PRO_CODE = MODULE_PROFIL.PRO_CODE
                                inner join DD_MODULE on MODULE_PROFIL.MOD_CODE = DD_MODULE.MOD_CODE
                                where ID_UTILISATEUR=" . intval($rowListe['ID_UTILISATEUR']) . "
                                order by SIT_LIBELLE, MOD_GROUPE, PRO_LIBELLE";
                            $aPROFIL = $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
                            if (count($aPROFIL) > 0) {
                                echo '<ul>';
                                foreach ($aPROFIL as $rowTemp) {
                                    if ($rowTemp['PRO_CODE'] == 'PRO_ROOT') {
                                        echo '<li><strong>' . secureInput(extraireLibelle($rowTemp['PRO_LIBELLE'])) . '</strong></li>';
                                    } else {
                                        echo '<li><em>' . secureInput($rowTemp['SIT_LIBELLE']) . '</em> : ' . secureInput(extraireLibelle($rowTemp['MOD_GROUPE']) . ' - ' . extraireLibelle($rowTemp['PRO_LIBELLE'])) . '</li>';
                                    }
                                }
                                echo '</ul>';
                            }?>
                        </td>
                        <td class="aligncenter"><?php echo ($rowListe['UTI_LASTCONNEXION']) ? date('d/m/Y H:i', $rowListe['UTI_LASTCONNEXION']) : '-'?></td>
                        <td class="aligncenter"><?php echo secureInput($rowListe['STATUT'])?></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
<?php } ?>
        </div>
    </div>
    <?php include('../../include/inc.bo_bandeau_bas.php')?>
</div>
</body>
</html>
