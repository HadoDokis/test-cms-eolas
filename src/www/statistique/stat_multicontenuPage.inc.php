<?php
$p = new Pagination();
$filtre = ' HID_TYPEDONNEE =  "PAGE"
            and cal.HIC_YEAR = ' . $dbh->quote($year);

if ($byMonth) {
     $filtre .= ' and cal.HIC_MONTH = ' . $dbh->quote($month);
}

if ($p->onSearch()) {
    if ($_GET['SIT_LIBELLE'] != '') {
        $filtre .= " and SIT_LIBELLE" . $p->makeLike('SIT_LIBELLE');
    }
    if (isset($_GET['type_vue'])) {
        if ($_GET['type_vue'] == 1) {
            $filtre .= ' and (HID_TOTALACTION > 0)';
        } else if ($_GET['type_vue'] == 2) {
            $filtre .= ' and h.SIT_CODE not in (select distinct h.SIT_CODE
                                              from HISTORIQUE_CALENDRIER cal
                                              inner join HISTORIQUE_PERIODE h on(h.HIC_DATE = cal.HIC_DATE)
                                              where ' .$filtre. ' and HID_TOTALACTION > 0)';
        } else {
            $filtre .= ' and (HID_TOTALACTION > 0 or HID_TOTALCONNEXION > 0)';
        }
    }
} else {
    $filtre .= ' and (HID_TOTALACTION > 0 or HID_TOTALCONNEXION > 0)';
    $p->setOrderBy('HID_LASTDATE desc');
}

$p->setFilter($filtre);
$p->setCount("select count(distinct h.SIT_CODE)
              from HISTORIQUE_CALENDRIER cal
              inner join HISTORIQUE_PERIODE h on(h.HIC_DATE = cal.HIC_DATE)
              left join DD_SITE s on (s.SIT_CODE = h.SIT_CODE)");

if ($p->getNb() > 0) {
    $aDataAction = $aDataMiseEnLigne = $aDataDate;

 $sqlGraph = "select distinct h.*,
                 HIC_MONTH
                 from HISTORIQUE_CALENDRIER cal
                 inner join HISTORIQUE_PERIODE h on(h.HIC_DATE = cal.HIC_DATE)
                 left join DD_SITE s on (s.SIT_CODE = h.SIT_CODE)
                 where " . $filtre;

   $sqlTab = "select
                    distinct h.SIT_CODE as SITE,
                    s.SIT_LIBELLE as INFO,
                    count(distinct HID_IDENTIFIANT) as PAGE,
                    sum(HID_TOTALACTION) as ACTION,
                    sum(HID_TOTALENLIGNE) as ENLIGNE,
                    sum(HID_TOTALAVALIDER) as AVALIDER,
                    sum(HID_TOTALHORSLIGNE) as HORSLIGNE,
                    max(HID_LASTDATE) as DATE
               from HISTORIQUE_CALENDRIER cal
               inner join HISTORIQUE_PERIODE h on(h.HIC_DATE = cal.HIC_DATE)
               inner join DD_SITE s on (s.SIT_CODE = h.SIT_CODE)";
    $aTotalPage = $aTotalWorkflow = array();
    if ($rows = $dbh->query($sqlGraph)) {
        foreach ($rows as $row) {
            if ($byMonth) {
                foreach ($aDataDate as $key => $val) {
                    $aDay = unserialize($row['HID_JOUR' . $key]);
                    $aDataAction[$key] = intval($aDataAction[$key]) + intval($aDay['ACTION']);
                    $aDataMiseEnLigne[$key] = intval($aDataMiseEnLigne[$key]) + intval($aDay['MISEENLIGNE']);                        }
            } else {
                $mois = strftime('%B', mktime(0,0,0,$row['HIC_MONTH']));
                $aDataAction[$mois] = intval($aDataAction[$mois]) + $row['HID_TOTALACTION'];
                $aDataMiseEnLigne[$mois] = intval($aDataMiseEnLigne[$mois]) + $row['HID_TOTALENLIGNE'];
            }

            $aTotalWorkflow['ACTION'] = intval($aTotalWorkflow['ACTION']) + $row['HID_TOTALACTION'];
            $aTotalWorkflow['ENLIGNE'] = intval($aTotalWorkflow['ENLIGNE']) + $row['HID_TOTALENLIGNE'];
            $aTotalWorkflow['AVALIDER'] = intval($aTotalWorkflow['AVALIDER']) + $row['HID_TOTALAVALIDER'];
            $aTotalWorkflow['HORSLIGNE'] = intval($aTotalWorkflow['HORSLIGNE']) + $row['HID_TOTALHORSLIGNE'];
            $aTotalPage[$row['HID_IDENTIFIANT']] = 0;
       }
    }
    if (isset($_GET['export'])) {
        set_time_limit(0);
        ob_end_clean();
        header("Content-type:text/csv");
        header("Content-Disposition:attachment; filename=\"Export_SitePage_" . date('dmY',$dateDebut) . "_" . date('dmY',strtotime("-1 day", $dateFin)) . ".csv\"");
        $f = fopen("php://temp", 'w');
        $data = array($titre . ' du ' . date('d/m/Y',$dateDebut) . ' au ' . date('d/m/Y',strtotime("-1 day", $dateFin)));
        fputcsv($f, $data, ';');
        $data = array(
                    gettext('Site'),
                    gettext('nb_pages_modifiees'),
                    gettext('nb_actions'),
                    gettext('mises_en_ligne'),
                    gettext('mises_a_valider'),
                    gettext('mises_hors_ligne'),
                    gettext('Derniere modification')
                 );
        fputcsv($f, $data, ';');

        foreach ($p->fetch($sqlTab,'h.SIT_CODE',false) as $row) {
            $data = array(
                $row['INFO'],
                intval($row['PAGE']). ' ' . gettext('pages'),
                intval($row['ACTION']),
                intval($row['ENLIGNE']) . ' ' . gettext('mises_en_ligne'),
                intval($row['AVALIDER']) . ' ' . gettext('mises_a_valider'),
                intval($row['HORSLIGNE']) . ' ' . gettext('mises_hors_ligne'),
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
    function searchRubrique(idRubrique) {
        if (idRubrique != '') {
            $('#idRubrique').val(idRubrique);
            $('#form_stat').submit();
        }
    }
    $(document).ready(function () {
      <?php if (count($aDataAction) > 0) {?>
          $('.stat_container_default').addClass('invisible');
          $('.stat_container').removeClass('invisible');
          $( '<div id="stat_legend"></div>' ).insertAfter( ".stat_container" );
           var placeholder = $("#placeholder");
           var d1 = [];
           <?php if (!empty($aDataAction)) {
               foreach ($aDataAction as $key => $value) {?>
                   d1.push(["<?php echo secureInput($key)?>", <?php echo secureInput($value)?>]);
               <?php }
           }?>

           var d2 = [];
           <?php if (!empty($aDataMiseEnLigne)) {
               foreach ($aDataMiseEnLigne as $key => $value) {?>
                   d2.push(["<?php echo secureInput($key)?>", <?php echo secureInput($value)?>]);
               <?php }
           }?>

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
                       label: "<?php echo secureInput(ucfirst(gettext('pages')) . ' ' . gettext('mises_en_ligne'))?>",
                       color: "#00B700",
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
    <form method="get" action="<?php echo PHP_SELF?>" class="filtre" id="form_stat">
        <fieldset>
            <legend><?php echo gettext('MOTEUR_RECHERCHE')?></legend>
             <table class="tableWidthAuto">
                <tr>
                    <td class="aligncenter"><?php echo count($aTotalPage) . ' ';echo count($aTotalPage) > 1 ? gettext('pages_modifiees') : gettext('page_modifiee');?></td>
                    <td class="aligncenter"><?php echo intval($aTotalWorkflow['ACTION']) . ' '; echo intval($aTotalWorkflow['ACTION']) > 1 ?  gettext('actions_enregistrees') : gettext('action_enregistree');?></td>
                    <td class="aligncenter PST_ENLIGNE"><?php echo intval($aTotalWorkflow['ENLIGNE']) . ' '; echo intval($aTotalWorkflow['ENLIGNE']) > 1 ? gettext('mises_en_ligne') : gettext('mise_en_ligne');?></td>
                    <td class="aligncenter PST_AVALIDER"><?php echo intval($aTotalWorkflow['AVALIDER']) . ' '; echo intval($aTotalWorkflow['AVALIDER']) > 1 ? gettext('mises_a_valider') : gettext('mise_a_valider');?></td>
                    <td class="aligncenter PST_HORSLIGNE"><?php echo intval($aTotalWorkflow['HORSLIGNE']) . ' '; echo intval($aTotalWorkflow['HORSLIGNE']) > 1 ? gettext('mises_hors_ligne') : gettext('mise_hors_ligne');?></td>
                </tr>
            </table>
            <hr>
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
                            <input type="hidden" name="type" value="<?php echo secureInput($_GET['type'])?>">
                        </td>
                    </tr>
                </tfoot>
                        <tbody>
                            <tr>
                                <th>Type de contributeurs</th>
                                <td>
                                    <input type="radio" name="type_vue" id="type_vue_0" value="0" <?php if (!$p->getParam('type_vue') || $p->getParam('type_vue') == '0') echo ' checked'?>>
                                    <label for="type_vue_0"><?php echo gettext('vue_globale');?></label>

                                    <input type="radio" name="type_vue" id="type_vue_1" value="1" <?php if ($p->getParam('type_vue') == '1') echo ' checked'?>>
                                    <label for="type_vue_1"><?php echo gettext('pages_modifiees');?></label>

                                    <input type="radio" name="type_vue" id="type_vue_2" value="2" <?php if ($p->getParam('type_vue') == '2') echo ' checked'?>>
                                    <label for="type_vue_2"><?php echo gettext('pages non modifiées');?></label>
                                </td>
                                <th><label for="SIT_LIBELLE"><?php echo gettext('Site')?></label></th>
                                <td>
                                    <input type="text" name="SIT_LIBELLE" id="SIT_LIBELLE" value="<?php echo $p->getParam('SIT_LIBELLE')?>" size="20">
                                </td>
                            </tr>
                        </tbody>
            </table>
        </fieldset>
    </form>
<?php echo $p->reglette();
    if ($p->getNb() > 0) {
    ?>
        <table class="liste">
            <thead>
                <tr>
                    <th><?php echo $p->tri(gettext('Site'), 'SIT_LIBELLE')?></th>
                    <th><?php echo $p->tri(gettext('nb_pages_modifiees'), 'PAGE')?></th>
                    <th><?php echo $p->tri(gettext('nb_actions'), 'ACTION')?></th>
                    <th><?php echo gettext('mise_en_ligne')?></th>
                    <th><?php echo gettext('mise_a_valider')?></th>
                    <th><?php echo gettext('mise_hors_ligne')?></th>
                    <th><?php echo $p->tri(gettext('Derniere modification'), 'DATE')?></th>
                </tr>
            </thead>
            <tbody>
            <?php
            foreach ($p->fetch($sqlTab,'h.SIT_CODE') as $row) {?>
                <tr>
                    <td><?php echo secureInput($row['INFO'])?></td>
                    <td class="aligncenter">
                     <?php if (intval($row['PAGE']) > 0) {?>
                            <a href="stat_<?php echo isset($_GET['type_vue']) && $_GET['type_vue'] == 2 ? 'pageInactive':'site';?>Popup.php?SIT_CODE=<?php echo secureInput($row['SITE'])?>&amp;type=PAGE&amp;dateDebut=<?php echo intval($dateDebut)?>&amp;dateFin=<?php echo intval($dateFin)?>#fieldset_1" class="popup">
                                <?php echo secureInput(intval($row['PAGE'])). ' '; echo intval($row['PAGE']) > 1 ? gettext('pages') : gettext('page')?>
                            </a>
                        <?php } else {?>
                            <?php echo '0 ' . gettext('page')?>
                        <?php }?>
                    </td>
                    <td class="aligncenter"><?php echo intval($row['ACTION']). ' ' . gettext('action'); echo intval($row['ACTION']) > 1 ? 's':''?></td>
                    <td class="aligncenter PST_ENLIGNE"><?php echo intval($row['ENLIGNE']) . ' ';  echo intval($row['ENLIGNE']) > 1 ? gettext('mises_en_ligne') : gettext('mise_en_ligne')?></td>
                    <td class="aligncenter PST_AVALIDER"><?php echo intval($row['AVALIDER']) . ' ';  echo intval($row['AVALIDER']) > 1 ? gettext('mises_a_valider') : gettext('mise_a_valider')?></td>
                    <td class="aligncenter PST_HORSLIGNE"><?php echo intval($row['HORSLIGNE']) . ' ';  echo intval($row['HORSLIGNE']) > 1 ? gettext('mises_hors_ligne') : gettext('mise_hors_ligne')?></td>
                    <td class="aligncenter"><?php echo isset($row['DATE']) && $row['DATE'] > 0? date('d/m/Y', intval($row['DATE'])) : '-'?></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
<?php } ?>
