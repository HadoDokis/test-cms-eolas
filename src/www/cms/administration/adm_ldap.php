<?php
require '../../include/inc.bo_init.php';
CMS::checkAccess(new Module('MOD_CORE'), array('PRO_ROOT'));
require CLASS_DIR . 'class.db_ldap.php';
require CLASS_DIR . 'class.Pagination.php';

$oLdap = new Ldap($_GET['idtf']);
$row = $oLdap->getFields();
?>
<!DOCTYPE html>
<html>
<head>
<?php include('../../include/inc.bo_enTete.php')?>
    <script>
        // Si un mdp est renseignée et si le "Compte" comporte le mot clé "$login"
        // On signale que la pré-authentification est déléguéé à l'utilisateur et que le mot de passe doit être vide.
        function extendedControl_LDA_PASSWORD(nField) {
            if ( ($.trim(nField.value) != '') && ($("#LDA_ACCOUNT").get(0).value.indexOf('$login') !=-1) ) {
                alert("La pré-authentification est réalisée avec l'indentifiant et le mot de passe de l'utilisateur grace au compte \""+$("#LDA_ACCOUNT").get(0).value+"\".\r\nLe champ \"Mot de passe\" ne doit donc pas être renseigné.");
                return false;
            }
            return true;
        }
        // La Base DN doit obligatoirement être renseignée si
        // on ne réalise une pré-authentification sans délégation (=> sans le mot clé "$login")
        function extendedControl_LDA_BASEDN(nField) {
            if ( ($.trim(nField.value) == '') && ($("#LDA_ACCOUNT").get(0).value.indexOf('$login') ==-1) ) {
                alert("La pré-authentification n'est pas réalisée avec l'indentifiant et le mot de passe de l'utilisateur qui tente de se connecter mais avec le compte \""+$("#LDA_ACCOUNT").get(0).value+"\".\r\nAfin de réellement valider l'authentification de l'utilisateur, il est donc nécessaire de renseigner le champ \"Base DN\".");
                return false;
            }
            return true;
        }
        function extendedControl_LDA_ATTRLOGIN(nField) {
            if ( ($.trim(nField.value) == '') && ($("#LDA_ACCOUNT").get(0).value.indexOf('$login') ==-1) ) {
                alert("La pré-authentification n'est pas réalisée avec l'indentifiant et le mot de passe de l'utilisateur qui tente de se connecter mais avec le compte \""+$("#LDA_ACCOUNT").get(0).value+"\".\r\nAfin de réellement valider l'authentification de l'utilisateur, il est donc nécessaire de renseigner le champ \"Attribut Identifiant\".");
                return false;
            }
            return true;
        }
    </script>
    <script src="<?php echo SERVER_ROOT ?>include/js/onglet.js"></script>
</head>
<body>
<div id="document">
    <?php $aMenuKey = array('CFG', 'PTF', 'LDAP', 'ADD'); include('../../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">
            <h2><?php echo secureInput($row['LDA_LIBELLE'])?></h2>
            <form method="post" action="adm_ldapSubmit.php" id="formCreation" class="creation">
                <fieldset class="tab">
                    <legend><?php echo gettext('Informations')?></legend>
                    <table>
                        <tfoot>
                            <tr>
                                <td colspan="2">
                                <?php if ($oLdap->exist()) { ?>
                                    <input type="hidden" name="idtf" value="<?php echo secureInput($oLdap->getID())?>">
                                    <input type="submit" name="Update" value="<?php echo gettext('UPDATE')?>" class="modifier">
                                    <?php if ($oLdap->isDeletable()) { ?>
                                    <input type="button" name="Delete" value="<?php echo gettext('DELETE')?>" class="supprimer"<?php if (!$oLdap->isDeletable()) echo ' disabled'?> onclick="if (confirm('Etes-vous sur ? \r\nLes comptes des utilisateus associés seront verrouillés.')) window.location.href='adm_ldapSubmit.php?Delete=<?php echo $oLdap->getID()?>'">
                                    <?php } ?>
                                <?php } else { ?>
                                    <input type="submit" name="Insert" value="<?php echo gettext('INSERT')?>" class="ajouter">
                                <?php } ?>
                                </td>
                            </tr>
                        </tfoot>
                        <tbody>
                            <tr>
                                <th><label for="LDA_LIBELLE">Nom</label></th>
                                <td><input type="text" name="LDA_LIBELLE" id="LDA_LIBELLE" value="<?php echo secureInput($row['LDA_LIBELLE'])?>" size="50" required></td>
                            </tr>
                            <tr>
                                <th><label for="LDA_HOST">Hôte</label></th>
                                <td><input type="text" name="LDA_HOST" id="LDA_HOST" value="<?php echo secureInput($row['LDA_HOST'])?>" size="50" required></td>
                            </tr>
                            <tr>
                                <th><label for="LDA_PORT">Port</label></th>
                                <td>
                                    <input type="text" name="LDA_PORT" id="LDA_PORT" value="<?php echo secureInput($row['LDA_PORT'])?>" size="5" data-type="integer" required>
                                    <input type="checkbox" name="LDA_LDAPS" id="LDA_LDAPS" value="1"<?php echo $row['LDA_LDAPS']?' checked':''?>>
                                    <label for="LDA_LDAPS">LDAPS</label>
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="LDA_ACCOUNT">Compte</label>
                                    <div class="helper">
                                        <strong>Compte LDAP utilisé pour la pré-authentification</strong>
                                        <br>
                                        Entrez l'identifiant d'un compte ayant un accès en lecture à l'annuaire LDAP, sinon laissez ce champ vide si votre LDAP peut être lu anonymement (les serveurs "Active Directory" ne permettent généralement pas un accès anonyme).
                                        <br>
                                        Il est possible d'utiliser le mot-clé "<code>$login</code>" au sein de la valeur du compte.
                                        Dans ce cas, ce mot-clé est alors remplacé par l'identifiant de l'utilisateur CMS qui tente de se connecter.
                                        Le mot de passe doit alors être laissé vide puisque la pré-authentification est réalisée avec le mot de passe dudit utilisateur.
                                    </div>
                                </th>
                                <td><input type="text" name="LDA_ACCOUNT" id="LDA_ACCOUNT" value="<?php echo secureInput($row['LDA_ACCOUNT'])?>" size="50"></td>
                            </tr>
                            <tr>
                                <th><label for="LDA_PASSWORD" class="extendedControl">Mot de passe</label></th>
                                <?php
                                $pwd = '';
                                if ($row['LDA_PASSWORD'] != '') {
                                    $pwd = '********';
                                }
                                ?>
                                <td><input type="password" name="LDA_PASSWORD" id="LDA_PASSWORD" value="<?php echo secureInput($pwd)?>" size="50"></td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="LDA_BASEDN" class="extendedControl">Base DN</label>
                                    <div class="helper">le <code>DN</code> de plus haut niveau de votre arborescence de répertoires LDAP.</div>
                                </th>
                                <td><input type="text" name="LDA_BASEDN" id="LDA_BASEDN" value="<?php echo secureInput($row['LDA_BASEDN'])?>" size="50"></td>
                            </tr>
                            <tr>
                                <th><label for="LDA_FILTER">Filtre LDAP</label></th>
                                <td><input type="text" name="LDA_FILTER" id="LDA_FILTER" value="<?php echo secureInput($row['LDA_FILTER'])?>" size="50"></td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="LDA_ATTRLOGIN" class="extendedControl">Attribut Identifiant</label>
                                    <div class="helper">Entrez le nom de l'attribut LDAP qui sera utilisé comme identifiant des utilisateurs CMS</div>
                                </th>
                                <td><input type="text" name="LDA_ATTRLOGIN" id="LDA_ATTRLOGIN" value="<?php echo secureInput($row['LDA_ATTRLOGIN'])?>" size="50"></td>
                            </tr>
                        </tbody>
                    </table>
                </fieldset>
                <?php
                $p = new Pagination();
                $filtre = " ID_LDAP=" . $dbh->quote($oLdap->getID());
                $p->setFilter($filtre);
                if (!$p->onSearch()) {
                    $p->setOrderBy('UTI_NOM');
                }
                $p->setCount("select count(ID_UTILISATEUR) from UTILISATEUR");
                if ($p->getNb() > 0) {
                ?>
                <fieldset class="tab">
                    <legend>Utilisateurs</legend>
                    <?php
                    echo $p->reglette();
                    ?>
                    <table class="liste">
                        <thead>
                            <tr>
                                <th><?php echo $p->tri(gettext('Utilisateur'), 'UTI_NOM')?></th>
                                <th><?php echo gettext('Origine')?></th>
                                <th><?php echo $p->tri(gettext('Derniere connexion'), 'UTI_LASTCONNEXION')?></th>
                                <th><?php echo $p->tri('Etat', 'statut')?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        // Même ordre de traitement du statut que dans "adm_utilisateurListe.php"
                        $sql = "select *,
                            IF(UTI_STATUT_LOCKED=1,'Verrouillé',IF(UTI_STATUT_BLOCKED=1,'Bloqué',IF(UTI_LASTCONNEXION is NUll, 'Inactif','Actif'))) as STATUT
                            from UTILISATEUR inner join DD_SITE using (SIT_CODE)";
                        foreach ($p->fetch($sql) as $rowListe) {
                            ?>
                            <tr>
                                <td>
                                    <a href="adm_utilisateur.php?idtf=<?php echo $rowListe['ID_UTILISATEUR']?>">
                                        <?php echo secureInput($rowListe['UTI_NOM'] . ' ' . $rowListe['UTI_PRENOM'])?>
                                    </a>
                                    <br><?php echo secureInput($rowListe['UTI_EMAIL'])?>
                                </td>
                                <td><?php echo secureInput($rowListe['SIT_LIBELLE'])?></td>
                                <td class="aligncenter"><?php echo ($rowListe['UTI_LASTCONNEXION']) ? date('d/m/Y H:i', $rowListe['UTI_LASTCONNEXION']) : '-'?></td>
                                <td class="aligncenter"><?php echo secureInput($rowListe['STATUT'])?></td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </fieldset>
                <?php } ?>
            </form>
        </div>
    </div>
    <?php include('../../include/inc.bo_bandeau_bas.php')?>
</div>
</body>
</html>
