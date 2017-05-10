<?php
require '../include/inc.bo_init.php';
require CLASS_DIR . 'class.db_historique.php';
Utilisateur::checkConnected();

$date1erJour = strtotime('-7 days midnight');
$aTabDate = $aTotal = array();

// Pour les sommes des graphes, nous pré-calculons les totaux pour chacun des jours
// ==> Du premier jour à hier
for ($currentDate = $date1erJour; $currentDate <= strtotime('yesterday'); $currentDate = strtotime("+1 day", $currentDate)) {
    $filtre = ' h.HIS_DATE >= ' . $currentDate . '
                 and h.HIS_DATE < ' . strtotime("+1 day", $currentDate);

    $sql = "select count(ID_HISTORIQUE_UTILISATEUR) from HISTORIQUE_UTILISATEUR h where " . $filtre;
    $nb = $dbh->query($sql)->fetchColumn();
    $aTabDate[$currentDate]['CONTRIBUTEUR'] = $nb;
    $aTotal['CONTRIBUTEUR']['CONNEXION'] = intval($aTotal['CONTRIBUTEUR']['CONNEXION']) + $nb;

    $sql = "select count(ID_HISTORIQUE_PAGE) from HISTORIQUE_PAGE h where " . $filtre;
    $nb = $dbh->query($sql)->fetchColumn();
    $aTabDate[$currentDate]['PAGE'] = $nb;
    $aTotal['PAGE']['ACTION'] = intval($aTotal['PAGE']['ACTION']) + $nb;

    $sql = "select count(ID_HISTORIQUE_WEBOTHEQUE) from HISTORIQUE_WEBOTHEQUE h where " . $filtre;
    $nb = $dbh->query($sql)->fetchColumn();
    $aTabDate[$currentDate]['WEBOTHEQUE'] = $nb;
    $aTotal['WEBOTHEQUE']['ACTION'] = intval($aTotal['WEBOTHEQUE']['ACTION']) + $nb;

    $sql = "select count(ID_HISTORIQUE_FORMULAIRE) from HISTORIQUE_FORMULAIRE h where " . $filtre;
    $nb = $dbh->query($sql)->fetchColumn();
    $aTabDate[$currentDate]['FORMULAIRE'] = $nb;
    $aTotal['FORMULAIRE']['ACTION'] = intval($aTotal['FORMULAIRE']['ACTION']) + $nb;
    if (Historique::isModuleActif()) {
        $sql = "select count(ID_HISTORIQUE_EXTERNE) from HISTORIQUE_EXTERNE h where " . $filtre;
        $nb = $dbh->query($sql)->fetchColumn();
        $aTabDate[$currentDate]['MODULE'] = $nb;
    }
    $sql = "select distinct HIS_TYPE, count(ID_HISTORIQUE_ADMIN) from HISTORIQUE_ADMIN h where " . $filtre . " group by HIS_TYPE";
    $rowNb = $dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_UNIQUE | PDO::FETCH_GROUP);
    $aTabDate[$currentDate]['ADMINSITE'] = $rowNb['SITE'];
    $aTabDate[$currentDate]['ADMINUSER'] = $rowNb['UTILISATEUR'];
    $aTotal['ADMIN']['ACTION']['UTILISATEUR'] = intval($aTotal['ADMIN']['ACTION']['UTILISATEUR']) + $rowNb['UTILISATEUR'];
    $aTotal['ADMIN']['ACTION']['SITE'] = intval($aTotal['ADMIN']['ACTION']['SITE']) + $rowNb['SITE'];
}
// Pour les autres infos (détails sous les graphes),
// on récupère directement les totaux entre le 1er jour et hier (aujourd'hui à minuit)
$filtre = ' h.HIS_DATE >= ' . $date1erJour . '
              and h.HIS_DATE < ' . strtotime('midnight');

$sql = "select SIT_LIBELLE, count(distinct ID_UTILISATEUR) from HISTORIQUE_UTILISATEUR h
        left join DD_SITE s on (s.SIT_CODE = h.SIT_CODE)
        where " . $filtre . "
        group by h.SIT_CODE";
$rowsNb = $dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_UNIQUE | PDO::FETCH_GROUP);
$aSite = array();
foreach ($rowsNb as $site => $nb) {
    $aTotal['CONTRIBUTEUR']['CONNECTE'] = intval($aTotal['CONTRIBUTEUR']['CONNECTE']) + $nb;
    $aSite[] = $site;
}
$sql = 'select count(ID_UTILISATEUR) from UTILISATEUR where ID_UTILISATEUR in (select distinct ID_UTILISATEUR from ROLE)';
$nb = $dbh->query($sql)->fetchColumn();
$aTotal['CONTRIBUTEUR']['TOTAL'] = $nb;

$aPageCount = array();
$NBPAGE = 0;
$sql = "select distinct ID_HISTORIQUE_PAGE, ID_PAGE, HIS_TYPE, HIS_DETAIL from HISTORIQUE_PAGE h where " . $filtre;
$rowsNb = $dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
foreach ($rowsNb as $row) {
    if (!in_array($row['ID_PAGE'], $aPageCount)) {
        $aPageCount[] = $row['ID_PAGE'];
        $NBPAGE++;
    }
    if ($row['HIS_TYPE'] == "WORKFLOW") {
        $aTotal['PAGE'][$row['HIS_DETAIL']] = intval($aTotal['PAGE'][$row['HIS_DETAIL']])+1;
    }
}

$sql = "select count(distinct ID_WEBOTHEQUE) from HISTORIQUE_WEBOTHEQUE h where " . $filtre;
$nb = $dbh->query($sql)->fetchColumn();
$aTotal['WEBOTHEQUE']['TOTAL'] = $nb;

$sql = "select count(distinct ID_FORMULAIRE) from HISTORIQUE_FORMULAIRE h where " . $filtre;
$nb = $dbh->query($sql)->fetchColumn();
$aTotal['FORMULAIRE']['TOTAL'] = $nb;

if (Historique::isModuleActif()) {
    $aTotal['MODULE'] = array();
    $sql = "select distinct MOD_LIBELLE, count(ID_HISTORIQUE_EXTERNE) from HISTORIQUE_EXTERNE h
                    left join DD_HISTORIQUE_EXTERNE dhe on(dhe.HEX_CODE = h.HEX_CODE)
                     left join DD_MODULE m on (m.MOD_CODE = dhe.MOD_CODE)
                     where " . $filtre . "
                     group by m.MOD_CODE";

    $rowsNb = $dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_UNIQUE | PDO::FETCH_GROUP);
    foreach ($rowsNb as $key => $nb) {
        $aTotal['MODULE'][extraireLibelle($key)] = $nb;
    }
    ksort($aTotal['MODULE'], SORT_NATURAL);
}

$sql = "select distinct HIS_TYPE, count(distinct HIS_IDENTIFIANT) from HISTORIQUE_ADMIN h where " . $filtre . " group by HIS_TYPE";
$rowNb = $dbh->query($sql)->fetchAll( PDO::FETCH_COLUMN | PDO::FETCH_UNIQUE | PDO::FETCH_GROUP);

$aTotal['ADMIN']['SITE'] = $rowNb['SITE'];
$aTotal['ADMIN']['UTILISATEUR'] = $rowNb['UTILISATEUR'];
?>
<!DOCTYPE html>
<html>
<head>
    <?php include('../include/inc.bo_enTete.php') ?>
    <script src="<?php echo SERVER_ROOT ?>include/js/jquery/flot/jquery.flot.js"></script>
    <script src="<?php echo SERVER_ROOT ?>include/js/jquery/flot/jquery.flot.categories.js"></script>
    <link rel="stylesheet" href="<?php echo SERVER_ROOT ?>include/js/jquery/flot/jquery.flot.css">
    <script type="text/javascript">
        $(document).ready(function () {

            /****************************/
            /*       Courbes            */
            /****************************/
            //Données pour les courbes
            var dContrib = [];
            var dPage = [];
            var dWebo = [];
            var dModule = [];
            var dForm = [];
            var dAdminSite = [];
            var dAdminUser = [];
            <?php foreach ($aTabDate as $date => $row) {?>
                dContrib.push(["<?php echo date('d/m',$date)?>", <?php echo intval($row['CONTRIBUTEUR'])?>]);
                dPage.push(["<?php echo date('d/m',$date)?>", <?php echo intval($row['PAGE'])?>]);
                dWebo.push(["<?php echo date('d/m',$date)?>", <?php echo intval($row['WEBOTHEQUE'])?>]);
                <?php if (Historique::isModuleActif()) {?>
                    dModule.push(["<?php echo date('d/m',$date)?>", <?php echo intval($row['MODULE'])?>]);
                <?php }?>
                dForm.push(["<?php echo date('d/m',$date)?>", <?php echo intval($row['FORMULAIRE'])?>]);
                dAdminSite.push(["<?php echo date('d/m',$date)?>", <?php echo intval($row['ADMINSITE'])?>]);
                dAdminUser.push(["<?php echo date('d/m',$date)?>", <?php echo intval($row['ADMINUSER'])?>]);
        <?php }?>

            var placeholder_contributeur = $("#placeholder_contributeur");

            //Construction du graphe "courbes"
            var plot_contributeur = $.plot(placeholder_contributeur,
                [
                    {
                        data: dContrib,
                        label: "<?php echo secureInput(ucfirst(gettext('connexions_enregistrees')))?>",
                        color: "rgba(227, 15, 114, 1)",
                        lines: {
                        }
                    }
                ],
                {
                    legend: {
                        show: false
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
            //Affichage des Tooltips
            placeholder_contributeur.bind("plothover", function (event, pos, item) {
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
            placeholder_contributeur.bind("plotclick", function (event, pos, item) {
                if (item) {
                    plot_contributeur.highlight(item.series, item.datapoint);
                }
            });

           var placeholder_page = $("#placeholder_page");

          //Construction du graphe "courbes"
            var plot_page = $.plot(placeholder_page,
                [
                    {
                        data: dPage,
                        label: "<?php echo secureInput(ucfirst(gettext('actions_enregistrees')))?>",
                        color: "rgba(227, 15, 114, 1)",
                        lines: {
                        }
                    }
                ],
                {
                    legend: {
                        show: false
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

            //Affichage des Tooltips
            placeholder_page.bind("plothover", function (event, pos, item) {
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
            placeholder_page.bind("plotclick", function (event, pos, item) {
                if (item) {
                    plot_page.highlight(item.series, item.datapoint);
                }
            });

            var placeholder_webotheque = $("#placeholder_webotheque");
          //Construction du graphe "courbes"
            var plot_webotheque = $.plot(placeholder_webotheque,
                [
                    {
                        data: dWebo,
                        label: "<?php echo secureInput(ucfirst(gettext('actions_enregistrees')))?>",
                        color: "rgba(227, 15, 114, 1)",
                        lines: {
                        }
                    }
                ],
                {
                    legend: {
                        show: false
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

            //Affichage des Tooltips
            placeholder_webotheque.bind("plothover", function (event, pos, item) {
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
            placeholder_webotheque.bind("plotclick", function (event, pos, item) {
                if (item) {
                    plot_webotheque.highlight(item.series, item.datapoint);
                }
            });
            <?php if (Historique::isModuleActif()) {?>
                var placeholder_module = $("#placeholder_module");
                //Construction du graphe "courbes"
                var plot_module = $.plot(placeholder_module,
                    [
                        {
                            data: dModule,
                            label: "<?php echo secureInput(ucfirst(gettext('actions_enregistrees')))?>",
                            color: "rgba(227, 15, 114, 1)",
                            lines: {
                            }
                        }
                    ],
                    {
                        legend: {
                            show: false
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

                //Affichage des Tooltips
                placeholder_module.bind("plothover", function (event, pos, item) {
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
                placeholder_module.bind("plotclick", function (event, pos, item) {
                    if (item) {
                        plot_module.highlight(item.series, item.datapoint);
                    }
                });
            <?php }?>
            var placeholder_formulaire = $("#placeholder_formulaire");
          //Construction du graphe "courbes"
            var plot_formulaire = $.plot(placeholder_formulaire,
                [
                    {
                        data: dForm,
                        label: "<?php echo secureInput(ucfirst(gettext('actions_enregistrees')))?>",
                        color: "rgba(227, 15, 114, 1)",
                        lines: {
                        }
                    }
                ],
                {
                    legend: {
                        show: false
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
            //Affichage des Tooltips
            placeholder_formulaire.bind("plothover", function (event, pos, item) {
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
            placeholder_formulaire.bind("plotclick", function (event, pos, item) {
                if (item) {
                    plot_formulaire.highlight(item.series, item.datapoint);
                }
            });
            var placeholder_administration = $("#placeholder_administration");
          //Construction du graphe "courbes"
            var plot_administration = $.plot(placeholder_administration,
                [
                 {
                     data: dAdminSite,
                     label: "<?php echo secureInput(ucfirst(gettext('nb_actions_sites')))?>",
                     color: "rgba(227, 15, 114, 1)",
                     lines: {
                     }
                 },
                 {
                     data: dAdminUser,
                     label: "<?php echo secureInput(ucfirst(gettext('nb_actions_utilisateurs')))?>",
                     color: "rgba(15, 227, 114, 1)",
                     lines: {
                     }
                 }
                ],
                {
                    legend: {
                        show: false
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
            //Affichage des Tooltips
            placeholder_administration.bind("plothover", function (event, pos, item) {
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
            placeholder_administration.bind("plotclick", function (event, pos, item) {
                if (item) {
                    plot_administration.highlight(item.series, item.datapoint);
                }
            });

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
        });
    </script>
</head>
<body>
<div id="document">
    <?php $aMenuKey = array('TDB', 'SMS'); include('../include/inc.bo_bandeau_haut.php') ?>
    <div id="corps">
        <div id="bo_contenu">
            <h2><?php echo ucfirst(gettext('tous_mes_sites')) . ' : ' . gettext('activite_tableaubord');?></h2>
            <div class="creation">
                <fieldset class="demiLeft">
                    <legend><?php echo gettext('Contributeurs');?></legend>
                    <div class="stat_container_demi">
                        <div id="placeholder_contributeur" class="stat_placeholder"></div>
                    </div>

                    <p class="legend"><strong><?php echo intval($aTotal['CONTRIBUTEUR']['CONNECTE']) . ' sur ' . intval($aTotal['CONTRIBUTEUR']['TOTAL']) . ' ' . gettext('contributeurs_se_sont_connectes')?></strong></p>
                    <p class="legend"><?php echo intval($aTotal['CONTRIBUTEUR']['CONNEXION']) . ' '; echo intval($aTotal['CONTRIBUTEUR']['CONNEXION']) > 1 ? gettext('connexions_enregistrees') : gettext('connexion_enregistree')?></p>
                    <p class="alignright">
                        <a class="action" href="stat_multiutilisateur.php" title="statistiques contributeurs"><?php echo gettext('Voir plus');?></a>
                    </p>
                </fieldset>
                <fieldset class="demiRight">
                    <legend><?php echo gettext('Pages');?></legend>
                    <div class="stat_container_demi">
                        <div id="placeholder_page" class="stat_placeholder"></div>
                    </div>
                    <p class="legend"><strong><?php echo intval($aTotal['PAGE']['ACTION']) . ' '; echo intval($aTotal['PAGE']['ACTION']) > 1 ? gettext('actions_enregistrees') : gettext('action_enregistree'); echo intval($NBPAGE) > 1 ? ' sur ' . intval($NBPAGE) . ' ' . gettext('pages') :  ' sur ' . intval($NBPAGE) . ' ' . gettext('page')?></strong></p>
                    <p class="legend PST_ENLIGNE demiLeft"><?php echo intval($aTotal['PAGE']['PST_ENLIGNE']) . ' '; echo intval($aTotal['PAGE']['PST_ENLIGNE']) > 1 ? gettext('pages') . ' ' . gettext('mises_en_ligne') : gettext('page') . ' ' . gettext('mise_en_ligne');?></p>
                    <p class="legend PST_AVALIDER demiRight"><?php echo intval($aTotal['PAGE']['PST_AVALIDER']) . ' '; echo intval($aTotal['PAGE']['PST_AVALIDER']) > 1 ? gettext('pages') . ' ' . gettext('mises_a_valider') : gettext('page') . ' ' . gettext('mise_a_valider');?></p>
                    <p class="legend PST_HORSLIGNE demiLeft"><?php echo intval($aTotal['PAGE']['PST_HORSLIGNE']) . ' '; echo intval($aTotal['PAGE']['PST_HORSLIGNE']) > 1 ? gettext('pages') . ' ' . gettext('mises_hors_ligne') : gettext('page') . ' ' . gettext('mise_hors_ligne');?></p>
                    <p class="legend PST_AREDIGER demiRight"><?php echo intval($NBPAGE) . ' '; echo intval($NBPAGE) > 1 ? gettext('pages_modifiees') : gettext('page_modifiee');?></p>
                    <p class="alignright clear">
                        <a class="action" href="stat_multicontenu.php" title="statistiques pages"><?php echo gettext('Voir plus');?></a>
                    </p>
                </fieldset>
                <fieldset class="demiLeft">
                    <legend><?php echo gettext('Webotheque');?></legend>
                    <div class="stat_container_demi">
                        <div id="placeholder_webotheque" class="stat_placeholder"></div>
                    </div>
                    <p class="legend"><?php echo intval($aTotal['WEBOTHEQUE']['ACTION']) . ' '; echo intval($aTotal['WEBOTHEQUE']['ACTION']) > 1 ?  gettext('actions_enregistrees') : gettext('action_enregistree')?></p>
                    <p class="alignright">
                        <a class="action" href="stat_multicontenu.php?type=webo" title="statistiques webothèque"><?php echo gettext('Voir plus');?></a>
                    </p>
                </fieldset>
                <fieldset class="demiRight">
                    <legend><?php echo gettext('Formulaires');?></legend>
                    <div class="stat_container_demi">
                        <div id="placeholder_formulaire" class="stat_placeholder"></div>
                    </div>
                    <p class="legend"><?php echo intval($aTotal['FORMULAIRE']['ACTION']) . ' '; echo intval($aTotal['FORMULAIRE']['ACTION']) > 1 ? gettext('actions_enregistrees') : gettext('action_enregistree'); echo ' sur ' . intval($aTotal['FORMULAIRE']['TOTAL']) . ' formulaires'?></p>
                    <p class="alignright">
                        <a class="action" href="stat_multicontenu.php?type=form" title="statistiques formulaires"><?php echo gettext('Voir plus');?></a>
                    </p>
                </fieldset>
                <?php if (Historique::isModuleActif()) {?>
                    <fieldset class="demiLeft">
                        <legend><?php echo gettext('Modules');?></legend>
                        <div class="stat_container_demi">
                            <div id="placeholder_module" class="stat_placeholder"></div>
                        </div>
                        <?php
                        if (!empty($aTotal['MODULE'])) {
                            foreach ($aTotal['MODULE'] as $module => $nb) {?>
                                <p class="legend"><?php echo intval($nb) . ' '; echo intval($nb) > 1 ? gettext('actions_enregistrees') : gettext('action_enregistree'); echo  ' ' . gettext('sur_le_module') . ' ' . secureInput($module)?></p>
                            <?php }
                        }?>
                        <p class="alignright">
                            <a class="action" href="stat_multicontenu.php?type=module" title="statistiques modules"><?php echo gettext('Voir plus');?></a>
                        </p>
                    </fieldset>
                <?php }?>
                <fieldset class="<?php echo Historique::isModuleActif() ? 'demiRight' : 'demiLeft';?>">
                    <legend>Configuration</legend>
                    <div class="stat_container_demi">
                        <div id="placeholder_administration" class="stat_placeholder"></div>
                    </div>
                    <p class="legend"><?php echo intval($aTotal['ADMIN']['ACTION']['SITE']) > 1 ?  intval($aTotal['ADMIN']['ACTION']['SITE']) . ' ' . gettext('actions_enregistrees') : intval($aTotal['ADMIN']['ACTION']['SITE']) . ' ' . gettext('action_enregistree') ; echo ' ' . gettext('sur_les_sites')?></p>
                    <p class="legend"><?php echo intval($aTotal['ADMIN']['ACTION']['UTILISATEUR']) > 1 ?  intval($aTotal['ADMIN']['ACTION']['UTILISATEUR']) . ' ' . gettext('actions_enregistrees') : intval($aTotal['ADMIN']['ACTION']['UTILISATEUR']) . ' ' . gettext('action_enregistree'); echo ' sur ' . intval($aTotal['ADMIN']['UTILISATEUR']) . ' ' . gettext('utilisateur'); echo intval($aTotal['ADMIN']['UTILISATEUR']) > 1 ? 's':''?></p>
                    <p class="alignright">
                        <a class="action" href="stat_multicontenu.php?type=admin" title="statistiques administration"><?php echo gettext('Voir plus');?></a>
                    </p>
                </fieldset>
            </div>
        </div>
    </div>
    <?php include('../include/inc.bo_bandeau_bas.php')?>
</div>
</body>
</html>
