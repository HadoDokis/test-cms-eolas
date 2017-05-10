<?php
if (empty($aVisites)) {
    echo 'Aucune visite, impossible d\'afficher les courbes';
} else { ?>
    <script>
        $(document).ready(function () {

            /****************************/
            /*       Courbes            */
            /****************************/
            $( '<div id="stat_legend"></div>' ).insertAfter( ".visites_container" );
            //Données pour les courbes
            var placeholder = $("#placeholder");
            var d1 = [];
            <?php foreach ($aVisites as $key => $total) { ?>
                d1.push(["<?php echo date('d\<\b\r\>m', $key) ?>", <?php echo $total?>]);
            <?php } ?>

            var d2 = [];
            <?php foreach ($aVisitesMoteur as $key => $total) { ?>
                d2.push(["<?php echo date('d\<\b\r\>m', $key) ?>", <?php echo $total?>]);
            <?php } ?>

            //Construction du graphe "courbes"
            var plot = $.plot(placeholder,
                [
                    {
                        data: d1,
                        label: "Visites",
                        color: "#F08800"
                    },
                    {
                        data: d2,
                        label: "Visites issues des moteurs",
                        color: "#0067EF"
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
                        tickLength: 0
                    },
                    grid: {
                        hoverable: true
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
        });
    </script>
<?php } ?>
