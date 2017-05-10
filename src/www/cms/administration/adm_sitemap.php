<?php
require '../../include/inc.bo_init.php';
require CLASS_DIR . 'class.Sitemap.php';
CMS::checkAccess(new Module('MOD_CORE'), array('PRO_ROOT'));
?>
<!DOCTYPE html>
<html>
<head>
<?php include('../../include/inc.bo_enTete.php') ?>
</head>
<body>
<div id="document">
    <?php $aMenuKey = array('CFG', 'PTF', 'SITEMAP'); include('../../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">
            <h2><?php echo gettext('Sitemap')?></h2>
            <form method="post" action="adm_sitemapSubmit.php" class="creation">
                <table class="liste">
                    <tfoot>
                        <tr>
                            <td colspan="4">
                                <input type="submit" name="Update" value="<?php echo gettext('UPDATE')?>" class="modifier">
                           </td>
                        </tr>
                    </tfoot>
                    <tbody>
                        <tr>
                            <th>Libellé</th>
                            <th>Filtre sur le site courant <span class="helper">La table doit posséder une colonne <code>SIT_CODE</code></span></th>
                            <th>Autre filtre</th>
                            <th>Priorité</th>
                            <th>Fréquence</th>
                            <th>Date de modification <span class="helper">Peut être le nom d'une colonne de type <code>timestamp</code></span></th>
                        </tr>
                        <?php
                        $sql = "select * from DD_RECHERCHE where REC_SITEMAP=1 order by REC_LIBELLE";
                        foreach ($dbh->query($sql) as $rowListe) {
                            if ($rowListe['REC_GOOGLEFREQUENCE'] == '') {
                                $rowListe['REC_GOOGLEFREQUENCE'] = Sitemap::DEFAULT_FREQUENCY;
                            }
                            if ($rowListe['REC_GOOGLEPRIORITE'] == '') {
                                $rowListe['REC_GOOGLEPRIORITE'] = Sitemap::DEFAULT_PRIORITY;
                            }
                            ?>
                        <tr>
                            <td><?php echo secureInput($rowListe['REC_LIBELLE'])?></td>
                            <td class="aligncenter"><input name="REC_FILTRESITE_<?php echo $rowListe['ID_RECHERCHE']?>" type="checkbox"<?php if ($rowListe['REC_FILTRESITE']) echo ' checked'?>></td>
                            <td><?php echo secureInput($rowListe['REC_FILTRE'])?></td>
                            <td class="aligncenter">
                                <select name="REC_GOOGLEPRIORITE_<?php echo $rowListe['ID_RECHERCHE']?>" id="REC_GOOGLEPRIORITE_<?php echo $rowListe['ID_RECHERCHE']?>">
                                <?php foreach (Sitemap::getPriorityList() as $REC_GOOGLEPRIORITE) { ?>
                                   <option value="<?php echo secureInput($REC_GOOGLEPRIORITE) ?>"<?php if ($REC_GOOGLEPRIORITE == $rowListe['REC_GOOGLEPRIORITE']) echo ' selected';?>><?php echo secureInput($REC_GOOGLEPRIORITE) ?></option>
                                <?php } ?>
                                </select>
                            </td>
                            <td class="aligncenter">
                                <select name="REC_GOOGLEFREQUENCE_<?php echo $rowListe['ID_RECHERCHE']?>" id="REC_GOOGLEFREQUENCE_<?php echo $rowListe['ID_RECHERCHE']?>">
                                <?php foreach (Sitemap::getFrequenceList() as $REC_GOOGLEFREQUENCE) { ?>
                                    <option value="<?php echo secureInput($REC_GOOGLEFREQUENCE) ?>"<?php if ($REC_GOOGLEFREQUENCE == $rowListe['REC_GOOGLEFREQUENCE']) echo ' selected';?>><?php echo secureInput($REC_GOOGLEFREQUENCE) ?></option>
                                <?php } ?>
                                </select>
                            </td>
                            <td class="aligncenter">
                                <?php if (is_numeric($rowListe['REC_GOOGLELASTMOD'])) { ?>
                                <input name="REC_GOOGLELASTMOD_<?php echo $rowListe['ID_RECHERCHE']?>" type="text" id="REC_GOOGLELASTMOD_<?php echo $rowListe['ID_RECHERCHE']?>" value="<?php echo date('d/m/Y', $rowListe['REC_GOOGLELASTMOD'])?>" data-type="date">
                                <?php } else { ?>
                                <code><?php echo $rowListe['REC_GOOGLELASTMOD']?></code>
                                <?php } ?>
                            </td>
                        </tr>
                        <?php } ?>
                    </table>
                </form>
            </div>
        </div>
    <?php include('../../include/inc.bo_bandeau_bas.php')?>
</div>
</body>
</html>
