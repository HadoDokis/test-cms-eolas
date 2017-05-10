<?php
require '../include/inc.bo_init.php';
require CLASS_DIR . 'class.Pagination.php';
require CLASS_DIR . 'class.db_commentaire.php';
CMS::checkAccess(new Module('MOD_COMMENTAIRE'));

$arrayEtat = array('VALIDE' =>gettext('com_etat_valide'),'REFUS' =>gettext('com_etat_refuse'),'MODERATION' =>gettext('com_etat_en_attente_de_moderation'));

$sqlProfil = "select DD_COMMENTAIRE_LIAISONTYPE.*, DD_PROFIL.PRO_LIBELLE from DD_COMMENTAIRE_LIAISONTYPE INNER JOIN DD_PROFIL on (DD_COMMENTAIRE_LIAISONTYPE.PRO_CODE = DD_PROFIL.PRO_CODE)";
$aPROCDE = $dbh->query($sqlProfil)->fetchAll(PDO::FETCH_ASSOC);

$aPro_code = array();
$listProCodeStr = "'NORIGHTS'";
foreach ($aPROCDE as $unPROCDE) {
    $aPro_code[] = $unPROCDE['PRO_CODE'];
    $listProCodeStr .= ",'".$unPROCDE['PRO_CODE']."'";
}

$hasRightsModerator = false;
if (Utilisateur::getConnected()->checkProfil($aPro_code)) {
    $hasRightsModerator = true;
}

if ($hasRightsModerator) {

    $p = new Pagination();
    $filtre = "SIT_CODE=" . $dbh->quote(CMS::getCurrentSite()->getID()) . " and PRO_CODE in (".$listProCodeStr.")";

    if ($p->onSearch()) {

        if (!empty($_GET['MOTS_CLES'])) {
            $filtre .= " and (COM_MESSAGE" . $p->makeLike('MOTS_CLES') ." or COM_PSEUDO" . $p->makeLike('MOTS_CLES') ." or COM_MAIL" . $p->makeLike('MOTS_CLES') . ")";
        }

        if (!empty($_GET['COM_ETAT'])) {
            $filtre .= " and COM_ETAT = " . $dbh->quote($_GET['COM_ETAT']);
        }
        if (is_numeric($_GET['ID_COMMENTAIRE_LIAISONTYPE'])) {
            $filtre .= ' and COMMENTAIRE.ID_COMMENTAIRE_LIAISONTYPE = ' . intval($_GET['ID_COMMENTAIRE_LIAISONTYPE']);
        }

    } else {
        $p->setOrderBy('COM_DATE desc');
    }
    $p->setFilter($filtre);
    $p->setCount("select count(COMMENTAIRE.ID_COMMENTAIRE) from COMMENTAIRE INNER JOIN DD_COMMENTAIRE_LIAISONTYPE on (COMMENTAIRE.ID_COMMENTAIRE_LIAISONTYPE = DD_COMMENTAIRE_LIAISONTYPE.ID_COMMENTAIRE_LIAISONTYPE)");
?>
<!DOCTYPE html>
<html>
<head>
<?php include('../include/inc.bo_enTete.php') ?>
</head>
<body>
<div id="document">
    <?php $aMenuKey = array('CFG', 'SITE', 'MOD_COMMENTAIRE', 'LISTE'); include('../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">
            <h2><?php echo gettext('com_commentaires')?></h2>
            <form method="get" action="<?php echo PHP_SELF?>" class="filtre">
                <fieldset>
                    <legend><?php echo gettext('MOTEUR_RECHERCHE')?></legend>
                    <table>
                        <tfoot>
                            <tr>
                                <td colspan="2"><?php echo $p->actionRecherche()?></td>
                            </tr>
                        </tfoot>
                        <tbody>
                            <tr>
                                <th><label for="MOTS_CLES"><?php echo gettext('com_mots_cles')?></label></th>
                                <td><input type="text" name="MOTS_CLES" id="MOTS_CLES" value="<?php echo $p->getParam('MOTS_CLES')?>" size="30"></td>
                            </tr>
                            <tr>
                                <th><label for="COM_ETAT"><?php echo gettext('com_etat')?></label></th>
                                <td>
                                    <select name="COM_ETAT" id="COM_ETAT">
                                        <option value=""><?php echo gettext('com_etat_indifferent')?></option>
                                        <option value="MODERATION"<?php if($_GET['COM_ETAT'] == 'MODERATION') echo ' selected'?>><?php echo gettext('com_etat_en_attente_de_moderation')?></option>
                                        <option value="REFUS"<?php if($_GET['COM_ETAT'] == 'REFUS') echo ' selected'?>><?php echo gettext('com_etat_refuse')?></option>
                                        <option value="VALIDE"<?php if($_GET['COM_ETAT'] == 'VALIDE') echo ' selected'?>><?php echo gettext('com_etat_valide')?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="ID_COMMENTAIRE_LIAISONTYPE"><?php echo gettext('com_type')?></label></th>
                                <td>
                                    <select name="ID_COMMENTAIRE_LIAISONTYPE" id="ID_COMMENTAIRE_LIAISONTYPE">
                                        <option value="">&nbsp;</option>
                                        <?php
                                        foreach ($aPROCDE as $unType) {
                                            if (Utilisateur::getConnected()->checkProfil(array($unType['PRO_CODE']))) {
                                        ?>
                                            <option value="<?php echo $unType['ID_COMMENTAIRE_LIAISONTYPE'] ?>" <?php if ($unType['ID_COMMENTAIRE_LIAISONTYPE'] == $_GET['ID_COMMENTAIRE_LIAISONTYPE']) echo 'selected' ?> ><?php echo secureInput($unType['CLI_CLASSNOM']) ?></option>
                                        <?php }
                                        }?>
                                    </select>
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

            <table class="liste">
                <thead>
                    <tr>
                        <th><?php echo $p->tri(gettext('com_commentaire'), 'COM_MESSAGE')?></th>
                        <th><?php echo gettext('com_depositaire') ?></th>
                        <th><?php echo gettext('com_cible') ?></th>
                        <th><?php echo $p->tri(gettext('com_date_depot'),'COM_DATE') ?></th>
                        <th><?php echo $p->tri(gettext('com_etat'),'COM_ETAT') ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $sql = "select * from COMMENTAIRE INNER JOIN DD_COMMENTAIRE_LIAISONTYPE on (COMMENTAIRE.ID_COMMENTAIRE_LIAISONTYPE = DD_COMMENTAIRE_LIAISONTYPE.ID_COMMENTAIRE_LIAISONTYPE)";
                foreach ($p->fetch($sql) as $rowListe) { ?>
                    <tr class="evtcolor_<?php echo $rowListe['EVT_DEPO']?>">
                        <td><a href="cms_commentaire.php?idtf=<?php echo $rowListe['ID_COMMENTAIRE'] ?>"><?php echo resume(strip_tags($rowListe['COM_MESSAGE']), 200)?></a></td>
                        <td class="aligncenter"><?php echo secureInput($rowListe['COM_PSEUDO'])?></td>
                        <td class="aligncenter">
                        <?php
                        require_once CLASS_DIR . $rowListe['CLI_CLASSFILE'];
                        $classCible = new $rowListe['CLI_CLASSNOM']($rowListe['COM_IDLIAISON']);

                        $libCible = secureInput($classCible->getLibelleTypeCommentaire()) . ' : ' . '<a href="'.$rowListe['CLI_CHEMINFICHE'].$rowListe['COM_IDLIAISON'].'">'. secureInput($classCible->getLibelleCommentaire()) . '</a>';
                        echo $libCible;
                        ?>
                        </td>
                        <td class="aligncenter"><?php echo empty($rowListe['COM_DATE'])?'-':date(gettext('com_date_format'), $rowListe['COM_DATE'])?></td>
                        <td class="aligncenter"><?php echo secureInput($arrayEtat[$rowListe['COM_ETAT']])?></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
<?php  } ?>
        </div>
    </div>
    <?php include('../include/inc.bo_bandeau_bas.php')?>
</div>
</body>
</html>
<?php } else {?>
<!DOCTYPE html>
<html>
<head>
<?php include('../include/inc.bo_enTete.php') ?>
</head>
<body>
<div id="document">
    <?php $aMenuKey = array('CTN', 'MOD_COMMENTAIRE', 'LISTE'); include('../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">
            <h2><?php echo gettext('com_commentaires')?></h2>
            <h3><?php echo gettext('com_pas_de_droits_moderateur')?></h3>
        </div>
    </div>
    <?php include('../include/inc.bo_bandeau_bas.php')?>
</div>
</body>
</html>
<?php }
