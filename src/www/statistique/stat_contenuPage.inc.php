<?php
$p = new Pagination();
$filtre = ' h.SIT_CODE = ' . $dbh->quote(CMS::getCurrentSite()->getID()) . '
            and HID_TYPEDONNEE =  "PAGE"
            and cal.HIC_YEAR = ' . $dbh->quote($year);
if ($byMonth) {
     $filtre .= ' and cal.HIC_MONTH = ' . $dbh->quote($month);
}
$filtreArbo = ' SIT_CODE = ' . $dbh->quote(CMS::getCurrentSite()->getID());

$vueArborescence = true;
if ($p->onSearch()) {
    if ($_GET['PAG_TITRE'] != '') {
        $filtre .= " and (HID_INFO " . $p->makeLike('PAG_TITRE') . " or HID_IDENTIFIANT  " . $p->makeLike('PAG_TITRE') . " or PAG_TITRE " . $p->makeLike('PAG_TITRE') . " or ID_PAGE " . $p->makeLike('PAG_TITRE') . ")";
        $p->setParam('type_vue', 1);
        $_GET['type_vue'] = 1;
    }
    if (isset($_GET['type_vue']) && $_GET['type_vue'] == 1) { //Pages modifiées
        $vueArborescence = false;
        $filtre .= " and HID_TOTALACTION > 0";

    } else if (isset($_GET['type_vue']) && $_GET['type_vue'] == 2) {//Pages non modifiées
        $vueArborescence = false;
        $filtre .=' and HID_IDENTIFIANT not in (select distinct HID_IDENTIFIANT
                                                     from HISTORIQUE_CALENDRIER cal
                                                        inner join HISTORIQUE_PERIODE h on(h.HIC_DATE = cal.HIC_DATE)
                                                        where ' . $filtre . '
                                                     and HID_TOTALACTION > 0)';

    } else { // Vue arborescence
        if (isset($_GET['idRubrique']) && is_numeric($_GET['idRubrique'])) {
            $idPageRubrique = $_GET['idRubrique'];
            $oPageRubrique = new Page($idPageRubrique);
            $filtreArbo .= " and (PAG_IDPERE = " . $oPageRubrique->getID() . " or ID_PAGE = " . $idPageRubrique .")";
        } else {
            $oPageHome = CMS::getCurrentSite()->getHomePage();
            $idPageRubrique = $oPageHome->getID();
            $filtreArbo .= " and (PAG_IDPERE = " . $oPageHome->getID() . " or PAG_IDPERE is null)";
        }
    }
} else {
    $p->setOrderBy('POIDS');
    if ($vueArborescence) {
        $oPageHome = CMS::getCurrentSite()->getHomePage();
         $idPageRubrique = $oPageHome->getID();
        $filtreArbo .= " and (PAG_IDPERE = " . $oPageHome->getID() . " or PAG_IDPERE is null)";
    }
}
if ($vueArborescence) {
    $p->setFilter($filtreArbo);
    $p->setCount("select distinct p.ID_PAGE from OFF_PAGE p");
} else {
    $p->setFilter($filtre);
    $p->setCount("select distinct h.HID_IDENTIFIANT from HISTORIQUE_CALENDRIER cal
                  inner join HISTORIQUE_PERIODE h on(h.HIC_DATE = cal.HIC_DATE)
                  left join OFF_PAGE p on(p.ID_PAGE = h.HID_IDENTIFIANT)");
}

if ($p->getNb() > 0) {
    $aDataMiseEnLigne = $aDataDate;
    if ($vueArborescence) {
        $aDataAction = $aDataMiseEnLigne;

        $sqlIdPage =  "select distinct ID_PAGE
                       from OFF_PAGE
                       where " . $filtreArbo ."
                       order by PAG_POIDS";

        $aIdPage = $dbh->query($sqlIdPage)->fetchAll(PDO::FETCH_COLUMN);
        $aDataTotal = $aTotalWorkflow = array();
        $aDataTotal[$idPageRubrique] = array(
            'DATE' => 0,
            'NB_FILLE' => 0
        );
        foreach ($aIdPage as $idPage) {
            $oPage = new Page($idPage);

            $DataTotal[$idPage]['DATE'] = 0;
            $DataTotal[$idPage]['NB_FILLE'] = 0;

            if ($oPage->exist()) {
                $sqlHisto = "select h.*,
                             HIC_MONTH,
                             HID_LASTDATE as POIDS
                             from HISTORIQUE_CALENDRIER cal
                             inner join HISTORIQUE_PERIODE h on(h.HIC_DATE = cal.HIC_DATE)
                             where " . $filtre . "
                             and (h.HID_IDENTIFIANT = " . $oPage->getID();
                if ($aIdChild = $oPage->getChildrenID()) {
                    $DataTotal[$idPage]['NB_FILLE'] = count($aIdChild);
                    $sqlHisto .= " or h.HID_IDENTIFIANT in (" . implode(',', $aIdChild) . ")";
                }
                $sqlHisto .= ")" ;

                $aDataTotal[$idPage] = array (
                        'LIBELLE'       =>  $oPage->getField('PAG_TITRE'),
                        'ACTION'        => 0,
                        'ENLIGNE'       => 0,
                        'AVALIDER'      => 0,
                        'HORSLIGNE'     => 0
                );

                if ($rowsHisto = $dbh->query($sqlHisto)) {
                   foreach ($rowsHisto as $row) {
                       if ((!$oPage->isHome() && $oPage->getID() != $_GET['idRubrique']) || $oPage->getID() == $row['HID_IDENTIFIANT']) {
                           if ($byMonth) {
                                foreach ($aDataDate as $key => $val) {
                                    $aDay = unserialize($row['HID_JOUR' . $key]);
                                    $aDataAction[$key] = intval($aDataAction[$key]) + intval($aDay['ACTION']);
                                    $aDataMiseEnLigne[$key] = intval($aDataMiseEnLigne[$key]) + intval($aDay['MISEENLIGNE']);
                                }
                            } else {
                                $mois = strftime('%B', mktime(0,0,0,$row['HIC_MONTH']));
                                $aDataAction[$mois] = intval($aDataAction[$mois]) + $row['HID_TOTALACTION'];
                                $aDataMiseEnLigne[$mois] = intval($aDataMiseEnLigne[$mois]) + $row['HID_TOTALENLIGNE'];
                            }
                            $aTotalWorkflow['ACTION'] = intval($aTotalWorkflow['ACTION']) + $row['HID_TOTALACTION'];
                            $aTotalWorkflow['ENLIGNE'] = intval($aTotalWorkflow['ENLIGNE']) + $row['HID_TOTALENLIGNE'];
                            $aTotalWorkflow['AVALIDER'] = intval($aTotalWorkflow['AVALIDER']) + $row['HID_TOTALAVALIDER'];
                            $aTotalWorkflow['HORSLIGNE'] = intval($aTotalWorkflow['HORSLIGNE']) + $row['HID_TOTALHORSLIGNE'];

                            $aDataTotal[$idPage]['LIBELLE']    = $oPage->getField('PAG_TITRE');
                            $aDataTotal[$idPage]['ACTION']     = intval($aDataTotal[$idPage]['ACTION']) + $row['HID_TOTALACTION'];
                            $aDataTotal[$idPage]['ENLIGNE']    = intval($aDataTotal[$idPage]['ENLIGNE']) + $row['HID_TOTALENLIGNE'];
                            $aDataTotal[$idPage]['AVALIDER']   = intval($aDataTotal[$idPage]['AVALIDER']) + $row['HID_TOTALAVALIDER'];
                            $aDataTotal[$idPage]['HORSLIGNE']  = intval($aDataTotal[$idPage]['HORSLIGNE']) + $row['HID_TOTALHORSLIGNE'];
                            if ($row['HID_LASTDATE'] > $aDataTotal[$idPage]['DATE']) {
                                $aDataTotal[$idPage]['DATE'] = $row['HID_LASTDATE'];
                            }
                       } else {
                            $aTotalWorkflow['LIBELLE'] = $oPage->getField('PAG_TITRE');
                            $aTotalWorkflow['ID'] = $oPage->getID();
                       }

                   }
                }
            }
        }
    } else {
          $NBPAGEMODIFIE = 0;
            $sql = "select
                        h.*,
                        HID_INFO as LIBELLE,
                        HIC_MONTH,
                        HID_LASTDATE as POIDS,
                        HID_TOTALACTION as ACTION
                    from HISTORIQUE_CALENDRIER cal
                    inner join HISTORIQUE_PERIODE h on(h.HIC_DATE = cal.HIC_DATE)
                    left join OFF_PAGE p on(p.ID_PAGE = h.HID_IDENTIFIANT)
                    where " . $filtre;

            $sqlTab = "select
                           distinct(HID_IDENTIFIANT) as IDENTIFIANT,
                           HID_INFO as LIBELLE,
                           max(HID_LASTDATE) as POIDS,
                           sum(HID_TOTALACTION) as ACTION,
                           sum(HID_TOTALENLIGNE) as ENLIGNE,
                           sum(HID_TOTALAVALIDER) as AVALIDER,
                           sum(HID_TOTALHORSLIGNE) as HORSLIGNE
                       from HISTORIQUE_CALENDRIER cal
                       inner join HISTORIQUE_PERIODE h on(h.HIC_DATE = cal.HIC_DATE)
                       left join OFF_PAGE p on(p.ID_PAGE = h.HID_IDENTIFIANT)";

            $aDataAction = $aDataMiseEnLigne;
            $DataTotal = $aTotalWorkflow = array();
            $idPage = $rowPage['ID_PAGE'];
            $DataTotal[$idPage]['LASTMODIFICATION'] = 0;
            $DataTotal[$idPage]['NB_FILLE'] = 0;

            foreach ($dbh->query($sql) as $row) {
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
                $aDataTotal[$row['HID_IDENTIFIANT']]['LIBELLE']    = $row['HID_INFO'];
                $aDataTotal[$row['HID_IDENTIFIANT']]['ACTION']     = intval($aDataTotal[$row['HID_IDENTIFIANT']]['ACTION']) + $row['HID_TOTALACTION'];
                $aDataTotal[$row['HID_IDENTIFIANT']]['ENLIGNE']    = intval($aDataTotal[$row['HID_IDENTIFIANT']]['ENLIGNE']) + $row['HID_TOTALENLIGNE'];
                $aDataTotal[$row['HID_IDENTIFIANT']]['AVALIDER']   = intval($aDataTotal[$row['HID_IDENTIFIANT']]['AVALIDER']) + $row['HID_TOTALAVALIDER'];
                $aDataTotal[$row['HID_IDENTIFIANT']]['HORSLIGNE']  = intval($aDataTotal[$row['HID_IDENTIFIANT']]['HORSLIGNE']) + $row['HID_TOTALHORSLIGNE'];
                if ($row['HID_LASTDATE'] > $aDataTotal[$row['HID_IDENTIFIANT']]['DATE']) {
                    $aDataTotal[$row['HID_IDENTIFIANT']]['DATE'] = $row['HID_LASTDATE'];
                }

                $aTotalWorkflow['ACTION'] = intval($aTotalWorkflow['ACTION']) + $row['HID_TOTALACTION'];
                $aTotalWorkflow['ENLIGNE'] = intval($aTotalWorkflow['ENLIGNE']) + $row['HID_TOTALENLIGNE'];
                $aTotalWorkflow['AVALIDER'] = intval($aTotalWorkflow['AVALIDER']) + $row['HID_TOTALAVALIDER'];
                $aTotalWorkflow['HORSLIGNE'] = intval($aTotalWorkflow['HORSLIGNE']) + $row['HID_TOTALHORSLIGNE'];
           }
    }
    if (isset($_GET['export'])) {
        set_time_limit(0);
        ob_end_clean();
        header("Content-type:text/csv");
        header("Content-Disposition:attachment; filename=\"Export_Page_" . date('dmY',$dateDebut) . "_" . date('dmY',strtotime("-1 day", $dateFin)) . ".csv\"");
        $f = fopen("php://temp", 'w');
        $data = array($titre . ' du ' . date('d/m/Y',$dateDebut) . ' au ' . date('d/m/Y',strtotime("-1 day", $dateFin)));
        fputcsv($f, $data, ';');
        $data = array(
                    gettext('Rubrique') .'/' . gettext('Page'),
                    gettext('Titre'),
                    gettext('nb_actions'),
                    gettext('Modification(s) du workflow'),
                    '',
                    '',
                    gettext('Derniere modification')
                 );
        fputcsv($f, $data, ';');
        foreach ($aDataTotal as $key => $row) {
            $oPage = new Page($key);
            if ($oPage->exist() && ($oPage->isHome() || $key == $_GET['idRubrique'])) {
                $sType = gettext('Page mère');
            } else {
                $sType = $DataTotal[$oPage->getID()]['NB_FILLE'] > 0 ? gettext('Rubrique') : gettext('Page');
            }
            $data = array(
                $sType.' (' . $key . ')',
                $row['LIBELLE'],
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
        $('#type_vue_0').click(function () {
            $('#PAG_TITRE').val('');
        });

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
            <?php if (!$vueArborescence) {?>
             <table>
                <tr>
                    <td class="aligncenter"><?php echo intval($aTotalWorkflow['ACTION']) . ' '; echo intval($aTotalWorkflow['ACTION']) > 1 ?  gettext('actions_enregistrees') : gettext('action_enregistree');?></td>
                    <td class="aligncenter PST_ENLIGNE"><?php echo intval($aTotalWorkflow['ENLIGNE']) . ' '; echo intval($aTotalWorkflow['ENLIGNE']) > 1 ? gettext('mises_en_ligne') : gettext('mise_en_ligne');?></td>
                    <td class="aligncenter PST_AVALIDER"><?php echo intval($aTotalWorkflow['AVALIDER']) . ' '; echo intval($aTotalWorkflow['AVALIDER']) > 1 ? gettext('mises_a_valider') : gettext('mise_a_valider');?></td>
                    <td class="aligncenter PST_HORSLIGNE"><?php echo intval($aTotalWorkflow['HORSLIGNE']) . ' '; echo intval($aTotalWorkflow['HORSLIGNE']) > 1 ? gettext('mises_hors_ligne') : gettext('mise_hors_ligne');?></td>
                </tr>
            </table>
            <hr>
            <?php }?>
            <table>
                <tfoot>
                    <tr>
                        <td colspan="6">
                            <?php echo $p->actionRecherche()?>
                            <input type="submit" name="export" id="export" class="btnAction noMargin" value="<?php echo gettext('Exporter_resultats');?>">
                            <input type="hidden" name="selectMonth" value="<?php echo secureInput($_GET['selectMonth'])?>">
                            <input type="hidden" name="selectYear" value="<?php echo secureInput($_GET['selectYear'])?>">
                            <input type="hidden" name="submitMonth" value="<?php echo secureInput($_GET['submitMonth'])?>">
                            <input type="hidden" name="submitYear" value="<?php echo secureInput($_GET['submitYear'])?>">
                            <input type="hidden" name="type" value="<?php echo secureInput($_GET['type'])?>">
                            <input type="hidden" name="idRubrique" id="idRubrique" value="<?php echo secureInput($_GET['idRubrique'])?>">

                        </td>
                    </tr>
                </tfoot>
                <tbody>
                    <tr>
                        <th>Type de vue</th>
                        <td style="white-space: nowrap;">
                            <input type="radio" name="type_vue" id="type_vue_0" value="0" <?php if (!$p->getParam('type_vue') || $p->getParam('type_vue') == '0') echo ' checked'?>>
                            <label for="type_vue_0">Vue arborescence</label>
                            <input type="radio" name="type_vue" id="type_vue_1" value="1" <?php if ($p->getParam('type_vue') == '1') echo ' checked'?>>
                            <label for="type_vue_1">Vue pages modifiées</label>
                            <input type="radio" name="type_vue" id="type_vue_2" value="2" <?php if ($p->getParam('type_vue') == '2') echo ' checked'?>>
                            <label for="type_vue_2">Vue pages non modifiées</label>
                        </td>
                        <th><label>Page</label></th>
                        <td><input type="text" name="PAG_TITRE" id="PAG_TITRE" value="<?php echo $p->getParam('PAG_TITRE')?>" size="20" style="width:25%"></td>
                    </tr>
                </tbody>
            </table>
        </fieldset>
    </form>
<?php
    if ($p->getNb() > 0) {
    ?>
        <?php if (isset($oPageRubrique) && $oPageRubrique->exist() && is_numeric($oPageRubrique->getField('PAG_IDPERE'))) {?>
            <div class="lienRetour">
                <a href="#" onclick="searchRubrique('<?php echo $oPageRubrique->getField('PAG_IDPERE')?>');return false;"><img src="../images/back.png" alt="Retour">Revenir</a>
            </div>
        <?php }?>
        <?php if ($vueArborescence) {?>

            <style>
                .blocRubrique {
                    margin: 10px auto;
                    padding: 5px 10px;
                    width: 80%;
                    background-color : #CDD3D7;
                    border-radius : 5px;
                }
                .titreRubrique {
                    padding-right : 20px;
                }
                .nbActions {
                    padding : 0 100px;
                }
                .workflow  {
                    padding : 0 10px;
                }
            </style>
            <div class="blocRubrique">
                <span class="titreRubrique"><?php echo '<b>' .gettext('Total') . ' : ' . gettext('Rubrique') . '</b><span style="color: #AAA;font-size:7pt"> ( n°' . intval($aTotalWorkflow['ID']) . ' )</span> - <b>'. secureInput($aTotalWorkflow['LIBELLE']) .'</b>'?></span>
                <span class="nbActions"><?php echo intval($aTotalWorkflow['ACTION']) . ' '; echo intval($aTotalWorkflow['ACTION']) > 1 ?  gettext('actions_enregistrees') : gettext('action_enregistree');?></span>
                <span class="workflow PST_ENLIGNE"><?php echo intval($aTotalWorkflow['ENLIGNE']) . ' '; echo intval($aTotalWorkflow['ENLIGNE']) > 1 ? gettext('mises_en_ligne') : gettext('mise_en_ligne');?></span><?php echo ' - ';?>
                <span class="workflow PST_AVALIDER"><?php echo intval($aTotalWorkflow['AVALIDER']) . ' '; echo intval($aTotalWorkflow['AVALIDER']) > 1 ?  gettext('mises_a_valider') : gettext('mise_a_valider');?></span><?php echo ' - ';?>
                <span class="workflow PST_HORSLIGNE"><?php echo intval($aTotalWorkflow['HORSLIGNE']) . ' '; echo intval($aTotalWorkflow['HORSLIGNE']) > 1 ?  gettext('mises_hors_ligne') : gettext('mise_hors_ligne');?></span>
            </div>
            <table class="liste">
                <thead>
                    <tr>
                        <th>Rubrique / Page</th>
                        <th>Titre</th>
                        <th>Nombre d'actions</th>
                        <th colspan="3">Modification du workflow</th>
                        <th>Dernière modification</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($aDataTotal as $key => $row) {
                        $oPage = new Page($key);?>
                        <tr>
                            <td class="aligncenter"> <?php
                                if ($oPage->exist() && ($oPage->isHome() || $oPage->getID() == $_GET['idRubrique'])) {
                                    echo gettext('Page mère');
                                } else {
                                    echo $DataTotal[$oPage->getID()]['NB_FILLE'] > 0 ? gettext('Rubrique') : gettext('Page');
                                }
                                echo '<span style="color: #AAA;font-size:7pt"> ( n°' . $oPage->getID() . ' )</span>';?>
                            </td>
                            <td>
                                <?php if ($oPage->exist() && !$oPage->isHome() && $oPage->getID() != $_GET['idRubrique'] && $DataTotal[$oPage->getID()]['NB_FILLE'] > 0) {?>
                                    <a href="#" onclick="searchRubrique('<?php echo $oPage->getID()?>');return false;"><?php echo secureInput($row['LIBELLE'])?></a>
                                    <em><?php echo $DataTotal[$oPage->getID()]['NB_FILLE'] > 1 ? ' (' . $DataTotal[$oPage->getID()]['NB_FILLE'] . ' - ' . gettext('pages_filles') : ' - 1 ' . gettext('page_fille'); echo ")"?></em>
                                <?php } else if ($oPage->isHome() || ($oPage->exist() && $oPage->getID() == $_GET['idRubrique'])) {?>
                                    <strong><?php echo secureInput($row['LIBELLE']);?></strong>
                                <?php } else {
                                    echo secureInput($row['LIBELLE']);
                                }?>
                                 <?php if (!$vueArborescence) {?>
                                     <?php echo ' <span style="color: #AAA;font-size:7pt"> ( n°' . $key . ' )</span>';?>
                                 <?php }?>
                            </td>
                            <td class="aligncenter">
                                <?php
                                if (intval($row['ACTION']) > 0) {
                                    if ($oPage->exist() && ($oPage->isHome() || $oPage->getID() == $_GET['idRubrique'])) {
                                        $histoArbo = '';
                                    } else {
                                        $histoArbo = $DataTotal[$oPage->getID()]['NB_FILLE'] > 0 ? 'arbo=1&amp;' : '';
                                    }
                                ?>
                                    <a href="stat_pagePopup.php?<?php echo $histoArbo;?>ID_PAGE=<?php echo $key?>&amp;dateDebut=<?php echo intval($dateDebut)?>&amp;dateFin=<?php echo intval($dateFin)?>" class="popup">
                                        <?php echo intval($row['ACTION']) == 1 ? '1 action' : intval($row['ACTION']) . ' ' . gettext('actions')?>
                                    </a>
                                <?php } else {?>
                                    <?php echo '0 ' . gettext('action')?>
                                <?php }?>
                            </td>
                            <td class="aligncenter PST_ENLIGNE"><?php echo intval($row['ENLIGNE']) . ' '; echo intval($row['ENLIGNE']) > 1 ? gettext('mises_en_ligne') : gettext('mise_en_ligne')?></td>
                            <td class="aligncenter PST_AVALIDER"><?php echo intval($row['AVALIDER']) . ' '; echo intval($row['AVALIDER']) > 1 ? gettext('mises_a_valider') : gettext('mise_a_valider')?></td>
                            <td class="aligncenter PST_HORSLIGNE"><?php echo intval($row['HORSLIGNE']) . ' '; echo intval($row['HORSLIGNE']) > 1 ? gettext('mises_hors_ligne') : gettext('mise_hors_ligne')?></td>
                            <td class="aligncenter"><?php echo isset($row['DATE']) && $row['DATE'] > 0 ? date('d/m/Y', $row['DATE']) : '-'?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } else {?>
            <table class="liste">
                <thead>
                    <tr>
                        <th><?php echo $p->tri(gettext('Titre'), 'LIBELLE')?></th>
                        <th><?php echo $p->tri(gettext('nb_actions'), 'ACTION')?></th>
                        <th colspan="3"><?php echo gettext('Modification(s) du workflow')?></th>
                        <th><?php echo $p->tri(gettext('Derniere modification'), 'POIDS')?></th>
                    </tr>
                </thead>
                <tbody>
                <?php
                    foreach ($p->fetch($sqlTab, 'HID_IDENTIFIANT') as $row) {
                        $oPage = new Page($row['IDENTIFIANT']);?>
                        <tr>
                            <td>
                                <?php if ($oPage->exist()) {
                                    echo secureInput($oPage->getField('PAG_TITRE'));
                                } else {
                                    echo secureInput($row['LIBELLE']);
                                }
                                echo ' <span style="color: #AAA;font-size:7pt"> ( n°' . intval($row['IDENTIFIANT']) . ' )</span>';?>
                            </td>
                            <td class="aligncenter">
                                <?php if (intval($row['ACTION']) > 0) {?>
                                    <a href="stat_pagePopup.php?ID_PAGE=<?php echo $row['IDENTIFIANT']?>&amp;dateDebut=<?php echo intval($dateDebut)?>&amp;dateFin=<?php echo intval($dateFin)?>" class="popup">
                                        <?php echo intval($row['ACTION']) == 1 ? '1 action' : intval($row['ACTION']) . ' ' . gettext('actions')?>
                                    </a>
                                <?php } else {?>
                                    <?php echo '0 ' . gettext('action')?>
                                <?php }?>
                            </td>
                            <td class="aligncenter PST_ENLIGNE"><?php echo intval($row['ENLIGNE']) . ' ' . gettext('mises_en_ligne')?></td>
                            <td class="aligncenter PST_AVALIDER"><?php echo intval($row['AVALIDER']) . ' ' . gettext('mises_a_valider')?></td>
                            <td class="aligncenter PST_HORSLIGNE"><?php echo intval($row['HORSLIGNE']) . ' ' . gettext('mises_hors_ligne')?></td>
                            <td class="aligncenter"><?php echo isset($row['POIDS']) && $row['POIDS'] > 0 ? date('d/m/Y', intval($row['POIDS'])) : '-'?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php }?>
<?php } ?>
