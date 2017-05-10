<?php
require '../include/inc.bo_init.php';

CMS::checkAccess(new Module('MOD_FORMULAIRE'), array ('PRO_FORMGEST', 'PRO_FORMLECT'));

require CLASS_DIR . 'class.db_formulaire.php';

$sql = "select * from FORMULAIREREPONSE
    inner join FORMULAIRE on FORMULAIREREPONSE.ID_FORMULAIRE=FORMULAIRE.ID_FORMULAIRE
where ID_FORMULAIREREPONSE=" . intval($_GET['idtf']);
$row = $dbh->query($sql)->fetch(PDO :: FETCH_ASSOC);

$oFormulaire = new Formulaire($row['ID_FORMULAIRE']);
$oFormulaire->isAuthorized();

if (!isset($_SESSION['FORMULAIRE']['URL']) || empty($_SESSION['FORMULAIRE']['URL'])) {
    $_SESSION['FORMULAIRE']['URL'] = '/formulaire/frm_formulaire.php?idtf='.$oFormulaire->getID();
}
?>
<!DOCTYPE html>
<html>
<head>
    <?php include('../include/inc.bo_enTete.php') ?>
</head>
<body>
<div id="document">
  <?php $aMenuKey = array('FRM', 'MOD_FORMULAIRE'); include('../include/inc.bo_bandeau_haut.php') ?>
  <div id="corps">
    <div id="bo_contenu">
      <h2> <?php echo secureInput($oFormulaire->getField('FRM_LIBELLE'))?> </h2>
      <div id="tab_container">
          <?php if (Utilisateur::getConnected()->checkProfil(array('PRO_FORMGEST'))) {?>
        <div id="tabScrollerContainer">
            <div id="tabScroller">
                <ul id="bo_onglet">
                    <li><a href="<?php echo $_SESSION['FORMULAIRE']['URL']?>&showTab=fieldset_0"><?php echo gettext('Proprietes')?></a></li>
                    <li><a href="<?php echo $_SESSION['FORMULAIRE']['URL']?>&showTab=fieldset_1"><?php echo gettext('Questions')?></a></li>
                    <li class="selected"><a href="<?php echo $_SESSION['FORMULAIRE']['URL']?>&showTab=fieldset_2"><?php echo gettext('Reponses')?></a></li>
                    <?php
                    $aReferants = $oFormulaire->getReferants();
                    if (count($aReferants) > 0) {
                    ?>
                    <li><a href="<?php echo $_SESSION['FORMULAIRE']['URL']?>&showTab=fieldset_3"><?php echo gettext('Affectations')?></a></li>
                    <?php
                    }
                    ?>
                </ul>
              </div>
          </div>
          <?php }?>
          <form method="post" action="frm_formulaireReponseSubmit.php" class="creation">
            <fieldset class="tab tabContent">
                <fieldset>
                    <legend><?php echo gettext('Reponse')?></legend>
                    <table>
                          <tbody>
            <?php
$sql = "select QST_LIBELLE, RED_VALEUR, QTY_CODE, UTILISATEUR.ID_UTILISATEUR, UTILISATEUR.UTI_PRENOM, UTILISATEUR.UTI_NOM, UTILISATEUR.UTI_EMAIL from FORMULAIREREPONSEDETAIL
    inner join FORMULAIREREPONSE on (FORMULAIREREPONSEDETAIL.ID_FORMULAIREREPONSE = FORMULAIREREPONSE.ID_FORMULAIREREPONSE)
    left join UTILISATEUR on (FORMULAIREREPONSE.ID_UTILISATEUR = UTILISATEUR.ID_UTILISATEUR)
    inner join FORMULAIREQUESTION using (ID_FORMULAIREQUESTION)
    inner join FORMULAIREGROUPE using (ID_FORMULAIREGROUPE)
    where FORMULAIREREPONSEDETAIL.ID_FORMULAIREREPONSE=" . intval($_GET['idtf']) . "
    order by FMG_POIDS, QST_POIDS";
foreach ($dbh->query($sql)->fetchAll(PDO :: FETCH_ASSOC) as $REPONSE) {?>
                            <tr>
                                  <th><label><?php echo secureInput($REPONSE['QST_LIBELLE'])?></label></th>
                                  <td>
                  <?php
    if ($REPONSE['QTY_CODE'] == 'QTY_FILE') {
        echo '<a href="' . UPLOAD_FORMULAIRE . $REPONSE['RED_VALEUR'] . '" onclick="window.open(this.href); return false;">' . secureInput($REPONSE['RED_VALEUR']) . '</a>';
    } else {
        echo secureInput($REPONSE['RED_VALEUR']);
    }?>
                                  </td>
                            </tr>
            <?php } ?>
                        </tbody>
                    </table>
                </fieldset>

                <fieldset>
                    <legend><?php echo gettext('Informations')?></legend>
                    <table>
                        <tfoot>
                            <tr>
                              <td colspan="2"><input type="hidden" name="idtf" value="<?php echo secureInput($_GET['idtf'])?>">
                                <input type="submit" name="Update" value="<?php echo gettext('UPDATE')?>" class="modifier">
                                <input type="button" name="Delete" value="<?php echo gettext('DELETE')?>" class="supprimer" onclick="if (confirm('<?php echo gettext('Etes-vous sur ?')?>')) window.location.href='frm_formulaireReponseSubmit.php?Delete=<?php echo secureInput($_GET['idtf']) ?>'">
                              </td>
                            </tr>
                          </tfoot>
                          <tbody>
                            <tr>
                              <th><label><?php echo gettext('Formulaire')?></label></th>
                              <td>
                                <a href="frm_formulaire.php?showTab=fieldset_0&idtf=<?php echo $row['ID_FORMULAIRE']?>"><?php echo secureInput($row['FRM_LIBELLE'])?></a>
                              </td>
                            </tr>
                            <?php if ($row['FRM_TRACABLE'] && intval($row['ID_UTILISATEUR']) > 0) {?>
                            <tr>
                              <th><label><?php echo gettext('Utilisateur')?></label></th>
                              <td>
                                <?php if (Utilisateur::getConnected()->checkprofil(array('PRO_ROOT_SITE'))) { ?>
                                <a href="/cms/administration/adm_utilisateur.php?idtf=<?php echo $row['ID_UTILISATEUR']?>"><?php echo secureInput($REPONSE['UTI_PRENOM'] . ' ' . $REPONSE['UTI_NOM'] . ' [' . $REPONSE['UTI_EMAIL'] . ']');?></a>
                                <?php } else { echo secureInput($REPONSE['UTI_PRENOM'] . ' ' . $REPONSE['UTI_NOM'] . ' [' . $REPONSE['UTI_EMAIL'] . ']'); } ?>
                              </td>
                            </tr>
                            <?php }?>
                            <?php if ($row['FRM_ETATREPONSE'] != '') {?>
                            <tr>
                              <th><label for="REP_ETAT"> <?php echo gettext('Etat')?></label></th>
                              <td><select id="REP_ETAT" name="REP_ETAT" required>
                                  <option value=""> &nbsp; </option>
                                  <?php foreach (explode("\n", $row['FRM_ETATREPONSE']) as $etat) {
                                            $etat = str_replace("\r", '', $etat);?>
                                  <option value="<?php echo secureInput($etat)?>"<?php if ($row['REP_ETAT'] == $etat) echo ' selected';?>><?php echo secureInput($etat)?></option>
                                  <?php } ?>
                                </select></td>
                            </tr>
                            <?php } ?>
                            <tr>
                              <th><label><?php echo gettext('Date')?></label></th>
                              <td><?php echo date("d/m/Y H:i", $row['REP_DATE'])?></td>
                            </tr>
                            <tr>
                              <th><label for="REP_COMMENTAIRE"><?php echo gettext('Commentaire')?></label></th>
                              <td><textarea name="REP_COMMENTAIRE" cols="60" rows="8" id="REP_COMMENTAIRE"><?php echo secureInput($row['REP_COMMENTAIRE']) ?></textarea></td>
                            </tr>
                      </tbody>
                    </table>
                </fieldset>
            </fieldset> <!-- FIN fieldset.tab.tabContent -->
          </form>
      </div> <!-- FIN #tab_container -->
    </div>
  </div>
  <?php include('../include/inc.bo_bandeau_bas.php')?>
</div>
</body>
</html>
