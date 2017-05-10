<?php
ob_start();
$currentCookieParams = session_get_cookie_params();
session_set_cookie_params(
    $currentCookieParams["lifetime"],
    $currentCookieParams["path"],
    $currentCookieParams["domain"],
    $currentCookieParams["secure"],
    true
);
session_start();
/**
 * Quelques outils destinés à l'équipe développement
 * @author Harmen.Christophe<harmen.christophe@businessdecision.com>
 */
require dirname(__FILE__) . '/../include/config.php';
require (dirname(__FILE__).'/../include/config_module_admin.php');
require dirname(__FILE__) . '/../include/lib.common.php';
require CLASS_DIR . 'class.DB.php';
require CLASS_DIR . 'class.CMS.php';
require CLASS_DIR . 'class.db_site.php';
require CLASS_DIR . 'class.db_utilisateur.php';
require_once CLASS_DIR . 'class.db_page.php';
require dirname(__FILE__) . '/include/class/class.cms.application.php';
// ERROR AND SLOW HTTP REQUEST
require_once PHYSICAL_PATH . 'admin/include/inc.errorHandler.php';

$dbh = DB::getInstance();

$app = application :: getInstance();
$app->checkInitialInstall();

CMS::init();

if (!Utilisateur::isConnected() || !Utilisateur::getConnected()->isRoot(true)) {
    header('Location:' . SERVER_ROOT . 'cms/index.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit ();
}
header('Content-Type: text/html; charset=UTF-8');

if ($_POST['Update']) {
    $sql = "delete from DD_TEMPLATE_GABARIT where TPL_CODE in (
                select distinct(tpl.TPL_CODE) from DD_TEMPLATE tpl
                inner join DD_MODULE m on tpl.MOD_CODE=m.MOD_CODE)";
    $dbh->exec($sql);
    $sql = "delete from DD_MODULE_GABARIT where MOD_CODE in (
                select distinct(m.MOD_CODE)
                from DD_MODULE m
                inner join DD_TEMPLATE tpl on m.MOD_CODE=tpl.MOD_CODE)";
    $dbh->exec($sql);
    $stmtMdl = $dbh->prepare('insert into DD_MODULE_GABARIT (
                        MOD_CODE,
                        GAB_CODE
                    ) values (
                        :MOD_CODE,
                        :GAB_CODE
                    )');
    $stmtTpl = $dbh->prepare('insert into DD_TEMPLATE_GABARIT (
                        TPL_CODE,
                        GAB_CODE
                    ) values (
                        :TPL_CODE,
                        :GAB_CODE
                    )');
    // Récupération de l'ensemble des modules possedant des templates
    $sqlMdl = 'select MOD_CODE, MOD_OBLIGATOIRE from DD_MODULE where MOD_CODE in (select distinct MOD_CODE from DD_TEMPLATE)';
    $rowsModules = $dbh->query($sqlMdl)->fetchAll(PDO::FETCH_ASSOC);
    $aMld = array();
    foreach ($rowsModules as $row) {
        $aMld[] = $row['MOD_CODE'];
        if ($row['MOD_OBLIGATOIRE'] == 1) {continue;} // Si le module est obligatoire, on ne permet pas de le limiter à des gabarits
        // Si limitation du module à un gabarit
        $aMdl_gabarits = isset($_POST['GAB_CODE_'.$row['MOD_CODE']])?$_POST['GAB_CODE_'.$row['MOD_CODE']]:null;
        if (is_array($aMdl_gabarits) && !empty($aMdl_gabarits)) {
            $stmtMdl->bindValue(':MOD_CODE', $row['MOD_CODE'], PDO::PARAM_STR);
            foreach ($aMdl_gabarits as $gabarit) {
                $stmtMdl->bindValue(':GAB_CODE', $gabarit, PDO::PARAM_STR);
                $stmtMdl->execute();
            }
        }
    }
    if (!empty($aMld)) {
        $sqlTpl = 'select TPL_CODE from DD_TEMPLATE where MOD_CODE in (' . implode(',',array_map(array($dbh, 'quote'),$aMld)). ')';
        $rowsTemplates = $dbh->query($sqlTpl)->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rowsTemplates as $row) {
            // Si limitation du template à un gabarit
            $aTpl_gabarits = isset($_POST['GAB_CODE_'.$row['TPL_CODE']])?$_POST['GAB_CODE_'.$row['TPL_CODE']]:null;
            if (is_array($aTpl_gabarits) && !empty($aTpl_gabarits)) {
                $stmtTpl->bindValue(':TPL_CODE', $row['TPL_CODE'], PDO::PARAM_STR);
                foreach ($aTpl_gabarits as $gabarit) {
                    $stmtTpl->bindValue(':GAB_CODE', $gabarit, PDO::PARAM_STR);
                    $stmtTpl->execute();
                }
            }
        }
    }

    // Purge du cache de l'ensemble des sites
    Page::clearAllCache();
}
?>
<!DOCTYPE>
<html>
    <head>
        <?php include '../include/inc.bo_enTete.php' ?>
        <link rel="stylesheet" href="<?php echo ADMIN_ROOT?>include/css/admin.css">
        <script src="<?php echo SERVER_ROOT?>include/js/onglet.js"></script>
    </head>
    <body>
        <div id="document">
            <div id="bandeau_haut">&nbsp;</div>
            <div id="corps" class="creation">
                <h1>Gestion des modules CMS et des templates par gabarit</h1>
                <ul class="alignright">
                    <li><a href="index.php">Informations et outils d'administration</a></li>
                    <li><a href="gabarits.php">Gestion des modules CMS et des templates par gabarit</a></li>
                </ul>
                <p>
                    Cette interface permet d'affecter les modules (non obligatoires et possédant des templates) ou les templates associés à certains gabarits seulement.
                    <br>
                    Si un module ou template n'est affecté à aucun gabarit, il est alors disponible à l'ensemble des gabarits (ou pour les templates, à ceux assignés au module).
                </p>
                <form method="post" action="<?php echo $_SERVER['PHP_SELF'];?>" id="formCreation" class="creation" onsubmit="$('.selection option').prop('selected',true);">
                    <?php
                    // Récupération de l'ensemble des modules possedant des templates
                    $sql = 'select MOD_CODE, MOD_LIBELLE, MOD_OBLIGATOIRE from DD_MODULE where MOD_CODE in (select distinct MOD_CODE from DD_TEMPLATE) order by MOD_LIBELLE';
                    $rowsModules = $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($rowsModules as $row) {
                        echo '<fieldset class="tab">';
                        echo '<legend>' . secureInput(extraireLibelle($row['MOD_LIBELLE'])) . '</legend>';
                        echo '<h2>Module "' . secureInput(extraireLibelle($row['MOD_LIBELLE'])) . '"</h2>';
                        // Si le module est obligatoire, on ne permet pas de le limiter à des gabarits
                        if ($row['MOD_OBLIGATOIRE'] == 1) {
                            echo '<p>Ce module est obligatoire. Seuls ses templates peuvent éventuellement être associés à des gabarits.</p>';
                        } else { ?>
                        <table class="selection">
                            <tr>
                                <th rowspan="2">Module<br><code><?php echo secureInput($row['MOD_CODE'])?></code></th>
                                <th>Gabarits affectés</th>
                                <td>&nbsp;</td>
                                <th>Gabarits disponibles</th>
                            </tr>
                            <tr>
                                <td>
                                    <select name="GAB_CODE_<?php echo secureInput($row['MOD_CODE']);?>[]" id="GAB_CODE_<?php echo encode($row['MOD_CODE'], false);?>" size="5" multiple ondblclick="DeplaceCritere(document.getElementById('GAB_CODE_<?php echo encode($row['MOD_CODE'], false);?>'), document.getElementById('GAB_CODE_<?php echo encode($row['MOD_CODE'], false);?>_ALL'));">
                                        <?php
                                        $notIn = "('-1'";
                                        $sql = "select DD_GABARIT.GAB_CODE, DD_GABARIT.GAB_LIBELLE from DD_GABARIT
                                            inner join DD_MODULE_GABARIT on DD_GABARIT.GAB_CODE = DD_MODULE_GABARIT.GAB_CODE
                                            where MOD_CODE=" . $dbh->quote($row['MOD_CODE']) . "
                                            order by GAB_LIBELLE";
                                            foreach ($dbh->query($sql) as $rowTemp) {
                                                $notIn .= "," . $dbh->quote($rowTemp['GAB_CODE']); ?>
                                                <option value="<?php echo $rowTemp['GAB_CODE'] ?>">
                                                    <?php echo secureInput(extraireLibelle($rowTemp['GAB_LIBELLE'])); ?>
                                                </option>
                                            <?php }
                                        $notIn .= ")"; ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="button" name="Button" value="&lt;&lt;" onclick="DeplaceCritere(document.getElementById('GAB_CODE_<?php echo encode($row['MOD_CODE'], false);?>_ALL'), document.getElementById('GAB_CODE_<?php echo encode($row['MOD_CODE'], false);?>'));">
                                    <input type="button" name="Button2" value="&gt;&gt;" onclick="DeplaceCritere(document.getElementById('GAB_CODE_<?php echo encode($row['MOD_CODE'], false);?>'), document.getElementById('GAB_CODE_<?php echo encode($row['MOD_CODE'], false);?>_ALL'));">
                                </td>
                                <td>
                                    <?php
                                    $sql = "select distinct(GAB_CODE), GAB_LIBELLE from DD_GABARIT
                                        where GAB_CODE not in " . $notIn . "
                                        order by GAB_LIBELLE"; ?>
                                    <select name="GAB_CODE_<?php echo encode($row['MOD_CODE'], false);?>_ALL[]" id="GAB_CODE_<?php echo encode($row['MOD_CODE'], false);?>_ALL" size="5" multiple ondblclick="DeplaceCritere(document.getElementById('GAB_CODE_<?php echo encode($row['MOD_CODE'], false);?>_ALL'), document.getElementById('GAB_CODE_<?php echo encode($row['MOD_CODE'], false);?>'));">
                                    <?php foreach ($dbh->query($sql) as $rowTemp) {?>
                                        <option value="<?php echo $rowTemp['GAB_CODE'] ?>">
                                            <?php echo secureInput(extraireLibelle($rowTemp['GAB_LIBELLE'])); ?>
                                        </option>
                                    <?php } ?>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        <?php } ?>

                        <?php
                        $sqlTpl = 'select TPL_CODE, TPL_LIBELLE from DD_TEMPLATE where MOD_CODE=' . $dbh->quote($row['MOD_CODE']). ' order by TPL_LIBELLE';
                        $rowsTpls = $dbh->query($sqlTpl)->fetchAll(PDO::FETCH_ASSOC);
                        if (!empty($rowsTpls)) { ?>
                        <h3>Templates du module "<?php echo secureInput(extraireLibelle($row['MOD_LIBELLE']))?>"</h3>
                        <table class="selection">
                            <?php foreach ($rowsTpls as $rowTpl) { ?>
                            <tr>
                                <th rowspan="2"><?php echo secureInput(extraireLibelle($rowTpl['TPL_LIBELLE']))?><br><code><?php echo encode($rowTpl['TPL_CODE']);?></code></th>
                                <th>Gabarits affectés</th>
                                <td>&nbsp;</td>
                                <th>Gabarits disponibles</th>
                            </tr>
                            <tr>
                                <td>
                                    <select name="GAB_CODE_<?php echo encode($rowTpl['TPL_CODE'], false);?>[]" id="GAB_CODE_<?php echo encode($rowTpl['TPL_CODE'], false);?>" size="5" multiple ondblclick="DeplaceCritere(document.getElementById('GAB_CODE_<?php echo encode($rowTpl['TPL_CODE'], false);?>'), document.getElementById('GAB_CODE_<?php echo encode($rowTpl['TPL_CODE'], false);?>_ALL'));">
                                        <?php
                                        $notIn2 = "('-1'";
                                        $sql = "select DD_GABARIT.* from DD_GABARIT
                                            inner join DD_TEMPLATE_GABARIT on DD_GABARIT.GAB_CODE = DD_TEMPLATE_GABARIT.GAB_CODE
                                            where TPL_CODE=" . $dbh->quote($rowTpl['TPL_CODE']) . "
                                            order by GAB_LIBELLE";
                                            foreach ($dbh->query($sql) as $rowTemp) {
                                                $notIn2 .= "," . $dbh->quote($rowTemp['GAB_CODE']); ?>
                                                <option value="<?php echo $rowTemp['GAB_CODE'] ?>">
                                                    <?php echo secureInput(extraireLibelle($rowTemp['GAB_LIBELLE'])); ?>
                                                </option>
                                            <?php }
                                        $notIn2 .= ")"; ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="button" name="Button" value="&lt;&lt;" onclick="DeplaceCritere(document.getElementById('GAB_CODE_<?php echo encode($rowTpl['TPL_CODE'], false);?>_ALL'), document.getElementById('GAB_CODE_<?php echo encode($rowTpl['TPL_CODE'], false);?>'));">
                                    <input type="button" name="Button2" value="&gt;&gt;" onclick="DeplaceCritere(document.getElementById('GAB_CODE_<?php echo encode($rowTpl['TPL_CODE'], false);?>'), document.getElementById('GAB_CODE_<?php echo encode($rowTpl['TPL_CODE'], false);?>_ALL'));">
                                </td>
                                <td>
                                    <?php
                                    if ($notIn != "('-1')") {
                                        $notIn2 .= " and GAB_CODE in " . $notIn;
                                    }
                                    $sql = "select distinct(GAB_CODE), GAB_LIBELLE from DD_GABARIT
                                        where GAB_CODE not in " . $notIn2 . "
                                        order by GAB_LIBELLE";?>
                                    <select name="GAB_CODE_<?php echo encode($rowTpl['TPL_CODE'], false);?>_ALL[]" id="GAB_CODE_<?php echo encode($rowTpl['TPL_CODE'], false);?>_ALL" size="5" multiple ondblclick="DeplaceCritere(document.getElementById('GAB_CODE_<?php echo encode($rowTpl['TPL_CODE'], false);?>_ALL'), document.getElementById('GAB_CODE_<?php echo encode($rowTpl['TPL_CODE'], false);?>'));">
                                    <?php foreach ($dbh->query($sql) as $rowTemp) {?>
                                        <option value="<?php echo $rowTemp['GAB_CODE'] ?>">
                                            <?php echo secureInput(extraireLibelle($rowTemp['GAB_LIBELLE'])); ?>
                                        </option>
                                    <?php } ?>
                                    </select>
                                </td>
                            </tr>
                        <?php } ?>
                        </table>
                        <?php
                        }
                        echo '</fieldset>';
                    }
                    ?>
                    <p class="aligncenter">
                        <input type="submit" name="Update" class="submit" value="Enregistrer">
                    </p>
                 </form>
            </div>
        </div>
    </body>
</html>
