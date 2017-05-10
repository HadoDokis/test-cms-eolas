<?php
require '../include/inc.bo_init.php';
require CLASS_DIR . 'class.db_formulaire.php';
require CLASS_DIR . 'class.db_formulaireCategorie.php';
require CLASS_DIR . 'class.Pagination.php';
CMS::checkAccess(new Module('MOD_FORMULAIRE'), array('PRO_FORMGEST', 'PRO_FORMLECT'));

$p = new Pagination();
$filtre = (Utilisateur::getConnected()->checkProfil(array('PRO_FORMGEST')))
    ? "1=1"
    : "FORMULAIRE_UTILISATEUR.ID_UTILISATEUR = ". Utilisateur::getConnected()->getID();
$filtre .= " and FORMULAIRE.SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID());
if ($p->onSearch()) {
    if ($_GET['FRM_LIBELLE'] != '') {
        $filtre .= " and (FRM_LIBELLE" . $p->makeLike('FRM_LIBELLE') . ")";
    }
    if (is_numeric($_GET['ID_FORMULAIRECATEGORIE'])) {
        $filtre .= " and FORMULAIRE.ID_FORMULAIRECATEGORIE=" . intval($_GET['ID_FORMULAIRECATEGORIE']);
    }

} else {
    $p->setOrderBy('FRM_LIBELLE');
}
$p->setFilter($filtre);
$p->setCount("select count(distinct FORMULAIRE.ID_FORMULAIRE) from FORMULAIRE left join FORMULAIRE_UTILISATEUR using(ID_FORMULAIRE)");
?>
<!DOCTYPE html>
<html>
<head>
<?php include('../include/inc.bo_enTete.php') ?>
</head>
<body>
<div id="document">
    <?php $aMenuKey = array('FRM', 'MOD_FORMULAIRE', 'LISTE'); include('../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">
            <h2>Formulaires dynamiques</h2>
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
                                <th><label for="FRM_LIBELLE"><?php echo gettext('Libelle')?></label></th>
                                <td><input type="text" name="FRM_LIBELLE" id="FRM_LIBELLE" value="<?php echo $p->getParam('FRM_LIBELLE')?>" size="30"></td>
                                <th><label for="ID_FORMULAIRECATEGORIE"><?php echo gettext('Categorie')?></label></th>
                                <td>
                                    <select name="ID_FORMULAIRECATEGORIE" id="ID_FORMULAIRECATEGORIE">
                                        <option value="">&nbsp;</option>
                                        <?php echo FormulaireCategorie::getSelectOptions($p->getParam('ID_FORMULAIRECATEGORIE')) ?>
                                    </select>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </fieldset>
            </form>
<?php
if ($p->onSearch()) {
    echo $p->reglette();
    if ($p->getNb() > 0) {
    ?>
            <table class="liste">
                <thead>
                    <tr>
                        <th><?php echo $p->tri(gettext('Libelle'), 'FRM_LIBELLE')?></th>
                        <th><?php echo gettext('Reponse')?></th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $sql = "select FORMULAIRE.ID_FORMULAIRE, FRM_LIBELLE, FRM_TRACABLE from FORMULAIRE
                    left join FORMULAIRE_UTILISATEUR on FORMULAIRE.ID_FORMULAIRE=FORMULAIRE_UTILISATEUR.ID_FORMULAIRE";
                foreach ($p->fetch($sql,'FORMULAIRE.ID_FORMULAIRE, FRM_LIBELLE') as $rowListe) {?>
                    <tr>
                        <td>
                            <a href="frm_formulaire.php?idtf=<?php echo $rowListe['ID_FORMULAIRE']?>"><?php echo secureInput($rowListe['FRM_LIBELLE'])?></a>
                        </td>
                        <td class="aligncenter">
                            <?php
                            $sql = "select count(ID_FORMULAIREREPONSE) from FORMULAIREREPONSE where ID_FORMULAIRE=".$rowListe['ID_FORMULAIRE'];
                            $nbID_FORMULAIREREPONSE = $dbh->query($sql)->fetchColumn();
                            if ($nbID_FORMULAIREREPONSE > 0) {
                            ?>
                            <a href="frm_formulaire.php?idtf=<?php echo $rowListe['ID_FORMULAIRE']?>&amp;showTab=fieldset_2">
                            <?php printf(gettext('x_reponses'), $nbID_FORMULAIREREPONSE); ?>
                            </a>
                            - <a href="frm_formulaire.php?idtf=<?php echo $rowListe['ID_FORMULAIRE']?>&amp;Export=1"><?php echo gettext('Exporter')?> </a>
                            <?php } else {
                                echo gettext('aucune_reponse');
                            }?>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
<?php } } ?>
        </div>
    </div>
    <?php include('../include/inc.bo_bandeau_bas.php')?>
</div>
</body>
</html>
