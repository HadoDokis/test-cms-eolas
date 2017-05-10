<?php
$p = new Pagination();
$filtre = ' h.SIT_CODE = ' . $dbh->quote(CMS::getCurrentSite()->getID()) . '
            and HID_TYPEDONNEE =  "ADMIN"
            and cal.HIC_YEAR = ' . $dbh->quote($year);
if ($byMonth) {
     $filtre .= ' and cal.HIC_MONTH = ' . $dbh->quote($month);
}
if (!$p->onSearch()) {
    $p->setOrderBy('h.HID_LASTDATE desc');
}
$p->setFilter($filtre);
$p->setCount("select count(distinct h.HID_IDENTIFIANT)
             from HISTORIQUE_CALENDRIER cal
             inner join HISTORIQUE_PERIODE h on(h.HIC_DATE = cal.HIC_DATE)");

if ($p->getNb() > 0) {
    $sql = "select *
            from HISTORIQUE_CALENDRIER cal
            inner join HISTORIQUE_PERIODE h on(h.HIC_DATE = cal.HIC_DATE)
            where " . $filtre;
    $aDataAction = $aDataTotal = array();
    $NB_ACTION = 0;
    foreach ($dbh->query($sql) as $row) {
        $NB_ACTION += intval($row['HID_TOTALACTION']);
        if (!isset($aDataAction[$row['HID_IDENTIFIANT']])) {
            $aDataAction[$row['HID_IDENTIFIANT']] = $aDataDate;
        }
        if ($byMonth) {
            foreach ($aDataDate as $key => $val) {
                $aDay = unserialize($row['HID_JOUR' . $key]);
                $aDataAction[$row['HID_IDENTIFIANT']][$key] = intval($aDay['ACTION']);
            }
        } else {
            $mois = strftime('%B', mktime(0,0,0,$row['HIC_MONTH']));
            $aDataAction[$row['HID_IDENTIFIANT']][$mois] = intval($aDataAction[$row['HID_IDENTIFIANT']][$mois]) + $row['HID_TOTALACTION'];
        }
        $aDataTotal[$row['HID_IDENTIFIANT']]['ACTION'] = intval($aDataTotal[$row['HID_IDENTIFIANT']]['ACTION']) + $row['HID_TOTALACTION'];
        if (intval($aDataTotal[$row['HID_IDENTIFIANT']]['DATE']) < $row['HID_LASTDATE']) {
            $aDataTotal[$row['HID_IDENTIFIANT']]['DATE'] = $row['HID_LASTDATE'];
        }
    }
    if (isset($_GET['export'])) {
        set_time_limit(0);
        ob_end_clean();
        header("Content-type:text/csv");
        header("Content-Disposition:attachment; filename=\"Export_Configuration_" . date('dmY',$dateDebut) . "_" . date('dmY',strtotime("-1 day", $dateFin)) . ".csv\"");
       $f = fopen("php://temp", 'w');
        $data = array($titre . ' du ' . date('d/m/Y',$dateDebut) . ' au ' . date('d/m/Y',strtotime("-1 day", $dateFin)));
        fputcsv($f, $data, ';');
        $data = array(
                    gettext('Type'),
                    gettext('nb_actions'),
                    gettext('Derniere modification')
                 );
        fputcsv($f, $data, ';');
        foreach ($aDataTotal as $key => $row) {
            $data = array(
                ucfirst(strtolower($key.'s')),
                $row['ACTION'],
                isset($row['DATE']) && $row['DATE'] > 0  ? date('d/m/Y', $row['DATE']) : '-'
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

        <?php if (intval($NB_ACTION) > 0) {?>
            $('.stat_container_default').addClass('invisible');
            $('.stat_container').removeClass('invisible');
            $( '<div id="stat_legend"></div>' ).insertAfter( ".stat_container" );
            var placeholder = $("#placeholder");
            var i = 1;

            var aPlot = new Array();
            <?php
            foreach ($aDataAction as $type => $row) { ?>
                var d = new Array();
                <?php foreach ($row as $key => $value) {?>
                    d.push(["<?php echo secureInput($key) ?>", <?php echo secureInput($value) ?>]);
                <?php }?>
                var plot = {
                    data: d,
                    label: "<?php echo secureInput(ucfirst(strtolower($type.'s')));?>",
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
                   $("#tooltip").html(item.series.label + " :" + y)
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
            <td class="aligncenter"><?php echo intval($NB_ACTION) . ' '; echo $NB_ACTION > 1 ?  gettext('actions_enregistrees') : gettext('action_enregistree');?></td>
        </tr>
    </table>
    <hr>
    <table>
        <tfoot>
            <tr>
                <td>
                    <input type="submit" name="export" id="export" class="btnAction noMargin" value="<?php echo gettext('Exporter_resultats');?>">
                    <input type="hidden" name="selectMonth" value="<?php echo secureInput($_GET['selectMonth'])?>">
                    <input type="hidden" name="selectYear" value="<?php echo secureInput($_GET['selectYear'])?>">
                    <input type="hidden" name="submitMonth" value="<?php echo secureInput($_GET['submitMonth'])?>">
                    <input type="hidden" name="submitYear" value="<?php echo secureInput($_GET['submitYear'])?>">
                    <input type="hidden" name="type" value="<?php echo secureInput($_GET['type'])?>">
                </td>
            </tr>
        </tfoot>
    </table>
</form>
<?php
echo $p->reglette();
if ($p->getNb() > 0) { ?>
<table class="liste">
    <thead>
        <tr>
            <th><?php echo $p->tri('Type', 'HID_IDENTIFIANT')?></th>
            <th><?php echo $p->tri("Nombre d'actions", 'HID_TOTALACTION')?></th>
            <th><?php echo $p->tri('Dernière modification', 'HID_LASTDATE')?></th>
        </tr>
    </thead>
    <tbody>
    <?php
    foreach ($aDataTotal as $key => $row) {?>
        <tr>
            <td><?php echo secureInput(ucfirst(strtolower($key.'s')))?></td>
            <td class="aligncenter">
                <?php if ($row['ACTION']) {?>
                <a href="stat_administrationPopup.php?HIS_TYPE=<?php echo secureInput($key)?>&amp;dateDebut=<?php echo $dateDebut?>&amp;dateFin=<?php echo $dateFin?>" class="popup"><?php echo $row['ACTION']?></a>
                <?php } else {?>
                0
                <?php }?>
            </td>
            <td class="aligncenter"><?php echo isset($row['DATE']) && $row['DATE'] > 0 ? date('d/m/Y', intval($row['DATE'])) : '-'?></td>
        </tr>
    <?php } ?>
    </tbody>
</table>
<?php } ?>
