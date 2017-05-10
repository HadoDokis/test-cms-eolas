<?php
$p = new Pagination();
$filtre = ' HID_TYPEDONNEE =  "EXTERNE"
            and cal.HIC_YEAR = ' . $dbh->quote($year);

if ($byMonth) {
     $filtre .= ' and cal.HIC_MONTH = ' . $dbh->quote($month);
}
if (!$p->onSearch()) {
    $p->setOrderBy('h.HID_LASTDATE desc');
}

$p->setFilter($filtre);
$p->setCount("select count(distinct h.SIT_CODE)
              from HISTORIQUE_CALENDRIER cal
              inner join HISTORIQUE_PERIODE h on(h.HIC_DATE = cal.HIC_DATE)
              left join DD_SITE s on (s.SIT_CODE = h.SIT_CODE)");

if ($p->getNb() > 0) {
    // Sélection de la référence des HEX_CODE associés à chacun des libellés de modules
    $sql = "select ddh.HEX_CODE, ddm.MOD_LIBELLE
            from HISTORIQUE_CALENDRIER cal
            inner join HISTORIQUE_PERIODE h on(h.HIC_DATE = cal.HIC_DATE)
            left join DD_HISTORIQUE_EXTERNE ddh on (h.HID_IDENTIFIANT = ddh.HEX_CODE)
            left join DD_MODULE ddm on (ddh.MOD_CODE = ddm.MOD_CODE)
            where " . $filtre ;
    $rowsModulesLibelles = $dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN |  PDO::FETCH_UNIQUE);

    $sqlGraph = "select ddm.MOD_LIBELLE, h.*,
                HIC_MONTH
                from HISTORIQUE_CALENDRIER cal
                inner join HISTORIQUE_PERIODE h on(h.HIC_DATE = cal.HIC_DATE)
                left join DD_HISTORIQUE_EXTERNE ddh on (h.HID_IDENTIFIANT = ddh.HEX_CODE)
                left join DD_MODULE ddm on (ddh.MOD_CODE = ddm.MOD_CODE)
                left join DD_SITE s on (s.SIT_CODE = h.SIT_CODE)
                where " . $filtre;

   $sqlTab = "select
                    distinct h.SIT_CODE as SITE,
                    s.SIT_LIBELLE as INFO,
                    count(distinct h.HID_IDENTIFIANT) as MODULE,
                    sum(HID_TOTALACTION) as ACTION,
                    max(HID_LASTDATE) as DATE
               from HISTORIQUE_CALENDRIER cal
               inner join HISTORIQUE_PERIODE h on(h.HIC_DATE = cal.HIC_DATE)
               inner join DD_SITE s on (s.SIT_CODE = h.SIT_CODE)";

    $ACTION = 0;
    $aDataAction = $aModule = array();
    foreach ($dbh->query($sqlGraph) as $row) {
        $ACTION += intval($row['HID_TOTALACTION']);

        // La clé des tableaux "$aDataAction" correspondent au libellé du module sufixé par l'éventuel HEX_CODE lorsqu'il y a plusieurs HEX_CODE pour un même module
        $Keylibelle = extraireLibelle($rowsModulesLibelles[$row['HID_IDENTIFIANT']]);
        // S'il y a plusieurs HEX_CODE (=> HID_IDENTIFIANT) pour un même libellé de module, on ajoute le code en sufixe
        if (count(array_keys($rowsModulesLibelles,$rowsModulesLibelles[$row['HID_IDENTIFIANT']])) > 1) {
            $Keylibelle .= ' (' . $row['HID_IDENTIFIANT'] . ')';
        }

        if (!isset($aDataAction[$Keylibelle])) {
            $aDataAction[$Keylibelle] = $aDataDate;
        }
        if ($byMonth) {
            foreach ($aDataDate as $key => $val) {
                $aDay = unserialize($row['HID_JOUR' . $key]);
                $aDataAction[$Keylibelle][$key] = $aDataAction[$Keylibelle][$key] + intval($aDay['ACTION']);
            }
        } else {
            $mois = strftime('%B', mktime(0,0,0,$row['HIC_MONTH']));
            $aDataAction[$Keylibelle][$mois] = intval($aDataAction[$Keylibelle][$mois]) + $row['HID_TOTALACTION'];
        }
        $aModule[$Keylibelle] = extraireLibelle($row['HID_INFO']);
    }
    // Tri alpha naturel sur les tableaux
    ksort($aDataAction, SORT_NATURAL);
    if (isset($_GET['export'])) {
        set_time_limit(0);
        ob_end_clean();
        header("Content-type:text/csv");
        header("Content-Disposition:attachment; filename=\"Export_SiteModule_" . date('dmY',$dateDebut) . "_" . date('dmY',strtotime("-1 day", $dateFin)) . ".csv\"");
        $f = fopen("php://temp", 'w');
        $data = array($titre . ' du ' . date('d/m/Y',$dateDebut) . ' au ' . date('d/m/Y',strtotime("-1 day", $dateFin)));
        fputcsv($f, $data, ';');
        $data = array(
                    gettext('Site'),
                    gettext('nb_actions'),
                    gettext('nb_modules'),
                    gettext('Derniere modification')
                 );
        fputcsv($f, $data, ';');

        foreach ($p->fetch($sqlTab, 'h.SIT_CODE', false) as $row) {
            $data = array(
                $row['INFO'],
                $row['ACTION'],
                $row['MODULE'],
                isset($row['DATE']) && $row['DATE'] > 0 ? date('d/m/Y', $row['DATE']) : '-'
            );
            fputcsv($f, $data, ';');
        }
        rewind($f);
        echo utf8_decode(stream_get_contents($f));
        exit();
    }
}?>
<script type="text/javascript">
    $(document).ready(function () {
        <?php if (intval($ACTION) > 0) {?>
            $('.stat_container_default').addClass('invisible');
            $('.stat_container').removeClass('invisible');
            $( '<div id="stat_legend"></div>' ).insertAfter( ".stat_container" );
            var aPlot = new Array();
            var placeholder = $("#placeholder");
            <?php  foreach ($aDataAction as $type => $row) { ?>
                var d = new Array();
                <?php foreach ($row as $key => $value) {?>
                    d.push(["<?php echo secureInput($key) ?>", <?php echo secureInput($value) ?>]);
                <?php }?>
                var plot = {
                    data: d,
                    label: "<?php echo secureInput(ucfirst($type));?>",
                    color: "<?php echo genereColor($type)?>"
                };
               aPlot.push(plot);
           <?php } ?>
             //Construction du graphe "courbes"
             var plot = $.plot(
                 placeholder,
                 aPlot,
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
<form method="get" action="<?php echo PHP_SELF?>" class="filtre" id="form_stat">
    <table>
        <tr>
            <td class="aligncenter"><?php echo count($aModule) .  ' '; echo count($aModule) > 1 ? gettext('modules_modifies') : gettext('module_modifie');?></td>
            <td class="aligncenter"><?php echo intval($ACTION) . ' '; echo $ACTION > 1 ?  gettext('actions_enregistrees') : gettext('action_enregistree');?></td>
        </tr>
    </table>
    <hr>
    <table>
        <tfoot>
            <tr>
                <td colspan="4">
                    <input type="submit" name="export" id="export" class="btnAction noMargin" value="<?php echo gettext('Exporter_resultats');?>">
                    <input type="hidden" name="selectMonth" value="<?php echo secureInput($_GET['selectMonth'])?>">
                    <input type="hidden" name="selectYear" value="<?php echo secureInput($_GET['selectYear'])?>">
                    <input type="hidden" name="submitMonth" value="<?php echo secureInput($_GET['submitMonth'])?>">
                    <input type="hidden" name="submitYear" value="<?php echo secureInput($_GET['submitYear'])?>">
                    <input type="hidden" name="type" value="<?php echo secureInput($_GET['type'])?>">
                </td>
            </tr>
        </tfoot>
        <tbody>
        </tbody>
    </table>
</form>

<?php echo $p->reglette();
    if ($p->getNb() > 0) {
    ?>
        <table class="liste">
            <thead>
                <tr>
                    <th><?php echo $p->tri(gettext('Sites'), 'INFO')?></th>
                    <th><?php echo $p->tri(gettext('nb_actions'), 'ACTION')?></th>
                    <th><?php echo $p->tri(gettext('nb_modules'), 'MODULE')?></th>
                    <th><?php echo $p->tri(gettext('Derniere modification'), 'DATE')?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($p->fetch($sqlTab, 'h.SIT_CODE') as $row) {?>
                <tr>
                    <td><?php echo secureInput(extraireLibelle($row['INFO']))?></td>
                    <td class="aligncenter">
                    <?php if (intval($row['ACTION']) > 0) {?>
                            <a href="stat_sitePopup.php?SIT_CODE=<?php echo $row['SITE']?>&amp;type=MODULE&amp;dateDebut=<?php echo intval($dateDebut)?>&amp;dateFin=<?php echo intval($dateFin)?>#fieldset_4" class="popup">
                                <?php echo intval($row['ACTION']);?>
                            </a>
                    <?php } else {?>
                        <?php echo intval($row['ACTION']);?>
                    <?php }?>
                    </td>
                    <td class="aligncenter"><?php echo intval($row['MODULE']);?></td>
                    <td class="aligncenter"><?php echo isset($row['DATE']) && $row['DATE'] > 0 ? date('d/m/Y', intval($row['DATE'])) : '-'?></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
<?php } ?>
