<?php
require '../include/inc.bo_init.php';
require CLASS_DIR . 'class.Pagination.php';
Utilisateur::checkConnected();
require_once 'stat_searchDateTraitement.inc.php';
$p = new Pagination();

$filtre = ' h.SIT_CODE = ' . $dbh->quote(CMS::getCurrentSite()->getID()) . '
            and HID_TYPEDONNEE =  "UTILISATEUR"
            and cal.HIC_YEAR = ' . $dbh->quote($year);
if ($byMonth) {
     $filtre .= ' and cal.HIC_MONTH = ' . $dbh->quote($month);
}

if ($p->onSearch()) {
    if ($_GET['UTI_NOM'] != '') {
        $filtre .= " and HID_INFO" . $p->makeLike('UTI_NOM');
    }
    if (isset($_GET['type_vue'])) {
        if ($_GET['type_vue'] == 1) {
            $filtre .= ' and HID_TOTALACTION > 0';
        } else if ($_GET['type_vue'] == 2) {
            $filtre .=' and HID_IDENTIFIANT not in (select distinct HID_IDENTIFIANT
                                                     from HISTORIQUE_CALENDRIER cal
                                                        inner join HISTORIQUE_PERIODE h on(h.HIC_DATE = cal.HIC_DATE)
                                                        where ' . $filtre . '
                                                     and HID_TOTALACTION > 0)';
        }
    }
} else {
    $p->setOrderBy('DATE desc');
}
$p->setFilter($filtre);
$p->setCount("select count(distinct h.HID_IDENTIFIANT)
    from HISTORIQUE_CALENDRIER cal
    inner join HISTORIQUE_PERIODE h on(h.HIC_DATE = cal.HIC_DATE)");

$titre = CMS::getCurrentSite()->getField('SIT_LIBELLE') . ' : activité des contributeurs';
if ($p->getNb() > 0) {
    $sqlGraph = "select *
                 from HISTORIQUE_CALENDRIER cal
                 inner join HISTORIQUE_PERIODE h on(h.HIC_DATE = cal.HIC_DATE)
                 where " . $filtre;
    $sqlTab = "select
                    distinct HID_IDENTIFIANT as IDENTIFIANT,
                    h.*,
                    sum(HID_TOTALACTION) as ACTION,
                    sum(HID_TOTALCONNEXION) as CONNEXION,
                    max(HID_LASTDATE) as DATE
               from HISTORIQUE_CALENDRIER cal
               inner join HISTORIQUE_PERIODE h on(h.HIC_DATE = cal.HIC_DATE)";

    $aDataAction = $aDataConnexion = $aDataDate;
    $aNbUtilisateur = array();
    foreach ($dbh->query($sqlGraph) as $row) {
        $NB_ACTION += intval($row['HID_TOTALACTION']);
        $NB_CONNEXION += intval($row['HID_TOTALCONNEXION']);
        if (intval($row['HID_TOTALACTION']) > 0) {
            $aNbUtilisateur[$row['HID_IDENTIFIANT']] = 0;
        }
        if ($byMonth) {
            foreach ($aDataDate as $key => $val) {
                $aDay = unserialize($row['HID_JOUR' . $key]);
                $aDataAction[$key] = intval($aDataAction[$key]) + intval($aDay['ACTION']);
                $aDataConnexion[$key] = intval($aDataConnexion[$key]) + intval($aDay['CONNEXION']);
            }
        } else {
            $mois = strftime('%B', mktime(0,0,0,$row['HIC_MONTH']));
            $aDataAction[$mois] = intval($aDataAction[$mois]) + $row['HID_TOTALACTION'];
            $aDataConnexion[$mois] = intval($aDataConnexion[$mois]) + $row['HID_TOTALCONNEXION'];
        }
    }
    if (isset($_GET['export'])) {
        set_time_limit(0);
        ob_end_clean();
        header("Content-type:text/csv");
        header("Content-Disposition:attachment; filename=\"Export_Contributeur_" . date('dmY',$dateDebut) . "_" . date('dmY',strtotime("-1 day", intval($dateFin))) . ".csv\"");
        $f = fopen("php://temp", 'w');
        $data = array($titre . ' du ' . date('d/m/Y',$dateDebut) . ' au ' . date('d/m/Y',strtotime("-1 day", intval($dateFin))));
        fputcsv($f, $data, ';');
        $data = array('Contributeur',
                    'Nombre d\'actions',
                    'Nombre de connexions',
                    'Dernière connexion'
                 );
        fputcsv($f, $data, ';');
        foreach ($p->fetch($sqlTab,'HID_IDENTIFIANT',false) as $row) {
                $data = array(
                    $row['HID_INFO'],
                    intval($row['ACTION']),
                    intval($row['CONNEXION']),
                    !empty($row['DATE']) ? date('d/m/Y', $row['DATE']) : '-'
                );
                fputcsv($f, $data, ';');
        }
        rewind($f);
        echo utf8_decode(stream_get_contents($f));
        exit();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <?php include('../include/inc.bo_enTete.php') ?>
    <script type="text/javascript">
        $(document).ready(function () {
            <?php if (intval($NB_ACTION) > 0) {?>
                $('.stat_container_default').addClass('invisible');
                $('.stat_container').removeClass('invisible');
                $( '<div id="stat_legend"></div>' ).insertAfter( ".stat_container" );
                var placeholder = $("#placeholder")
                var d1 = [];
                <?php foreach ($aDataAction as $key => $value) {?>
                    d1.push(["<?php echo secureInput($key)?>", <?php echo secureInput($value)?>]);
                <?php }?>

                var d2 = [];
                <?php foreach ($aDataConnexion as $key => $value) {?>
                    d2.push(["<?php echo secureInput($key)?>", <?php echo secureInput($value)?>]);
                <?php }?>

                //Construction du graphe "courbes"
                var plot = $.plot(placeholder,
                    [
                        {
                            data: d1,
                            label: "<?php echo secureInput(ucfirst(gettext('actions_enregistrees')))?>",
                            color: "#F08800",
                            lines: {
                            }
                        },
                        {
                            data: d2,
                            label: "<?php echo secureInput(ucfirst(gettext('connexions_enregistrees')))?>",
                            color: "#0067EF",
                            lines: {
                            }
                        }
                    ],
                    {
                        legend: {
                            show: true,
                            position: "ne",
                            backgroundOpacity: 1,
                            container: $("#stat_legend"),
                            sorted: true,
                        },
                        series: {
                            lines: {
                                show: true
                            },
                            points: {
                                show: true
                            }
                        },
                        xaxis: {
                            mode: "categories",
                            tickLength: 0,
                            labelAngle: 90
                        },
                        grid: {
                            hoverable: true,
                            clickable: true
                        }

                    }
                );
            //Style des Tooltips
            $("<div id='tooltip'></div>").css({
                position: "absolute",
                display: "none",
                border: "1px solid #fdd",
                padding: "2px",
                "background-color": "#fee",
                opacity: 0.80,
                "z-index": "1"
            }).appendTo("body");

            //Affichage des Tooltips
            placeholder.bind("plothover", function (event, pos, item) {
                if (item) {
                    var y = item.datapoint[1].toFixed(0);
                    $("#tooltip").html(item.series.label + " : " + y)
                        .css({top: item.pageY+5, left: item.pageX+5})
                        .fadeIn(200);
                } else {
                    $("#tooltip").hide();
                }
            });
            //Click sur les points
            placeholder.bind("plotclick", function (event, pos, item) {
                if (item) {
                    plot.highlight(item.series, item.datapoint);
                }
            });

            <?php } else {?>
                $('.stat_container_default').removeClass('invisible');
                $('.stat_container').addClass('invisible');
            <?php }?>
        });
    </script>
</head>
<body>
<div id="document">
    <?php $aMenuKey = array('TDB', 'SSC', 'CTB'); include('../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu" class="stats">
            <h2><?php echo secureInput($titre)?></h2>
            <?php include 'stat_searchDateForm.inc.php';?>
            <?php if (intval($NB_ACTION) > 0) {?>
                <div class="stat_container">
                    <div id="placeholder" class="stat_placeholder"></div>
                </div>
            <?php } else {?>
                <div class="stat_container_default">
                    <img src="<?php echo SERVER_ROOT . 'images/graph_default.jpg'?>" alt="">
                </div>
            <?php }?>
            <h4 class="aligncenter"><?php echo $byMonth ? ucfirst(strftime('%B %Y', $dateDebut)) : strftime('%Y', $dateDebut)?></h4>
            <form method="get" action="<?php echo PHP_SELF?>" class="filtre" id="form_stat">
                <fieldset>
                    <legend><?php echo gettext('MOTEUR_RECHERCHE')?></legend>
                    <?php if (!isset($_GET['type_vue']) || $_GET['type_vue'] != 2) {?>
                    <table>
                        <tr>
                            <td class="aligncenter"><?php echo count($aNbUtilisateur) . ' '; echo count($aNbUtilisateur) > 1 ? gettext('contributeurs_actifs') : gettext('contributeur_actif');?></td>
                            <td class="aligncenter"><?php echo intval($NB_ACTION) . ' '; echo intval($NB_ACTION) > 1 ?  gettext('actions_enregistrees') : gettext('action_enregistree');?></td>
                            <td class="aligncenter"><?php echo intval($NB_CONNEXION) . ' '; echo intval($NB_CONNEXION) > 1 ? gettext('connexions') : gettext('connexion');?></td>
                        </tr>
                    </table>
                    <hr>
                    <?php }?>
                    <table>
                        <tfoot>
                            <tr>
                                <td colspan="4">
                                    <?php echo $p->actionRecherche()?>
                                    <input type="submit" name="export" id="export" class="btnAction noMargin" value="<?php echo gettext('Exporter_resultats');?>">
                                    <input type="hidden" name="selectMonth" value="<?php echo secureInput($_GET['selectMonth'])?>">
                                    <input type="hidden" name="selectYear" value="<?php echo secureInput($_GET['selectYear'])?>">
                                    <input type="hidden" name="submitMonth" value="<?php echo secureInput($_GET['submitMonth'])?>">
                                    <input type="hidden" name="submitYear" value="<?php echo secureInput($_GET['submitYear'])?>">
                                </td>
                            </tr>
                        </tfoot>
                        <tbody>
                            <tr>
                                <th>Type de contributeur</th>
                                <td>
                                    <input type="radio" name="type_vue" id="type_vue_0" value="0" <?php if (!$p->getParam('type_vue') || $p->getParam('type_vue') == '0') echo ' checked'?>>
                                    <label for="type_vue_0">Vue globale</label>
                                    <input type="radio" name="type_vue" id="type_vue_1" value="1" <?php if ($p->getParam('type_vue') == '1') echo ' checked'?>>
                                    <label for="type_vue_1">Contributeur actif</label>
                                    <input type="radio" name="type_vue" id="type_vue_2" value="2" <?php if ($p->getParam('type_vue') == '2') echo ' checked'?>>
                                    <label for="type_vue_2">Contributeur inactif</label>
                                </td>
                                <th>
                                    <label for="UTI_NOM">Contributeur</label>
                                </th>
                                <td>
                                    <input type="text" name="UTI_NOM" id="UTI_NOM" value="<?php echo $p->getParam('UTI_NOM')?>" size="20">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </fieldset>
            </form>
<?php
echo $p->reglette();
if ($p->getNb() > 0) { ?>
                <table class="liste">
                    <thead>
                        <tr>
                            <th><?php echo $p->tri('Contributeur', 'HID_INFO')?></th>
                            <th><?php echo $p->tri("Nombre d'actions", 'ACTION')?></th>
                            <th><?php echo $p->tri("Nombre de connexions", 'CONNEXION')?></th>
                            <th><?php echo $p->tri('Dernière connexion', 'DATE')?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($p->fetch($sqlTab,'HID_IDENTIFIANT') as $row) {?>
                        <tr>
                            <td>
                                <?php
                                if (Utilisateur::getConnected()->isRoot()) {
                                    $oUser = new Utilisateur($row['IDENTIFIANT']);
                                    if ($oUser && $oUser->exist()) {
                                        $share = ($oUser->getField('SIT_CODE') != CMS::getCurrentSite()->getID())?'Share':'';
                                        echo '<a href="../cms/administration/adm_utilisateur'. $share.'.php?idtf='.$row['IDENTIFIANT'].'">'.secureInput($row['HID_INFO']).'</a>';
                                    } else {
                                        echo secureInput($row['HID_INFO']);
                                    }
                                } else {
                                    echo secureInput($row['HID_INFO']);
                                }?>
                            </td>
                            <td class="aligncenter">
                                <?php if (intval($row['ACTION']) > 0) {?>
                                    <a href="stat_utilisateurPopup.php?ID_UTILISATEUR=<?php echo $row['IDENTIFIANT']?>&amp;dateDebut=<?php echo intval($dateDebut)?>&amp;dateFin=<?php echo intval($dateFin)?>" class="popup">
                                        <?php echo intval($row['ACTION']) . ' '; echo intval($row['ACTION']) > 1 ? gettext('actions') : gettext('action')?>
                                    </a>
                                <?php } else {
                                    echo '0 ' . gettext('action');
                                }?>
                            </td>
                            <td class="aligncenter"><?php echo secureInput(intval($row['CONNEXION'])) . ' ' . gettext('connexion'); echo intval($row['CONNEXION']) > 1 ? 's':''?></td>
                            <td class="aligncenter"><?php echo !empty($row['DATE']) ? date('d/m/Y', intval($row['DATE'])) : '-'?></td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
        <?php } ?>
        </div>
    </div>
    <?php include('../include/inc.bo_bandeau_bas.php')?>
</div>
</body>
</html>
