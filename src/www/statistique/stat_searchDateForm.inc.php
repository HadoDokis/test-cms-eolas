
<script src="<?php echo SERVER_ROOT ?>include/js/onglet.js"></script>
<script src="<?php echo SERVER_ROOT ?>include/js/jquery/flot/jquery.flot.js"></script>
<script src="<?php echo SERVER_ROOT ?>include/js/jquery/flot/jquery.flot.categories.js"></script>
<script src="<?php echo SERVER_ROOT ?>include/js/jquery/flot/excanvas.js"></script>
<link rel="stylesheet" href="<?php echo SERVER_ROOT ?>include/js/jquery/flot/jquery.flot.css">
<script>
    $(document).ready(function () {
        $('.filtreStatDate input[type=image]').on( "click", function (event) {
            var targetID =  $(this).data("target"); // ID de l'élément "select" cible
            var submitID = targetID == 'selectMonth'? "submitMonth" : "submitYear"; // ID du boutons de soumission du formulaire cible
            var optionEl = $('#' + targetID + ' option:selected').first();
            if ($(this).data("handler") == 'next') {
                // Si présence d'une option "suivante"
                if (optionEl.next('option').get(0)) {
                    optionEl.prop("selected", false).next('option').prop("selected", true);
                // Pour la sélection du mois, on tente de passer au mois de janvier de l'année suivante
                } else if ( (targetID == 'selectMonth') &&
                        ($('#selectYear').val() != $('#selectYear option:last').attr('value'))
                ) {
                    optionEl.prop("selected", false);
                    $('#selectMonth option:first').prop("selected", true);
                    $('#selectYear option:selected').prop("selected", false).next('option').prop("selected", true);;
                }
            } else if ($(this).data("handler") == 'prev') {
                // Si présence d'une option "précédante"
                if (optionEl.prev('option').get(0)) {
                    optionEl.prop("selected", false).prev('option').prop("selected", true);
                // Pour la sélection du mois, on tente de passer au mois de décembre de l'année précédante
                } else if ( (targetID == 'selectMonth') &&
                        ($('#selectYear').val() != $('#selectYear option:first').attr('value'))
                ) {
                    optionEl.prop("selected", false);
                    $('#selectMonth option:last').prop("selected", true);
                    $('#selectYear option:selected').prop("selected", false).prev('option').prop("selected", true);
                }
            }
            event.preventDefault();
            $( "#" +  submitID).trigger( "click" );
        });
        $('#selectMonth, #selectYear').on( "change", function () {
            $('input[data-target=selectMonth][data-handler=prev]').attr('disabled', false);
            $('input[data-target=selectMonth][data-handler=next]').attr('disabled', false);
            // Si la valeur sélectionnée correspond à celle de la première option
            if ( ($('#selectMonth').val() == '01') && ($('#selectYear').val() == $('#selectYear option').first().attr('value')) ) {
                $('input[data-target=selectMonth][data-handler=prev]').attr('disabled', true);
             // Si la valeur sélectionnée correspond à celle de la dernière option
            } else if ( ($('#selectMonth').val() == '12') && ($('#selectYear').val() == $('#selectYear option').last().attr('value')) ) {
                $('input[data-target=selectMonth][data-handler=next]').attr('disabled', true);
            }
        });
        $('#selectYearOnly').on( "change", function () {
            $('input[data-target=selectYearOnly][data-handler=prev]').attr('disabled', false);
            $('input[data-target=selectYearOnly][data-handler=next]').attr('disabled', false);
            // Si la valeur sélectionnée correspond à celle de la première option
            if ( $('#selectYearOnly').val() == $('#selectYearOnly option').first().attr('value') ) {
                $('input[data-target=selectYearOnly][data-handler=prev]').attr('disabled', true);
            }
            // Si la valeur sélectionnée correspond à celle de la dernière option
            if ( $('#selectYearOnly').val() == $('#selectYearOnly option').last().attr('value') ) {
                $('input[data-target=selectYearOnly][data-handler=next]').attr('disabled', true);
            }
        });
        // Lors de la soumission du mois et de l'année, on désactive la sélection de l'année seule
        $('#submitMonth, #submitCurrentMonth').on( "click", function (event) {
            $("#selectYearOnly").prop("disabled", true);
        });
        // Lors de la soumission de l'année seule, on désactive les éléments de sélections des mois et années
        $('#submitYear, #submitCurrentYear').on( "click", function (event) {
            $("#selectMonth, #selectYear").prop("disabled", true);
        });
        $( "#selectMonth, #selectYearOnly").trigger( "change" );
    });
</script>
<?php
$aYearsAvailable = $dbh->query('Select distinct HIC_YEAR from HISTORIQUE_CALENDRIER ORDER BY HIC_YEAR asc')->fetchAll(PDO::FETCH_ASSOC|PDO::FETCH_COLUMN);
if (!empty($aYearsAvailable)) {
?>
<form method="get" action="<?php echo PHP_SELF?>" class="filtre filtreStatDate">
    <fieldset class="tab <?php echo $classTabSelectMonth?>">
        <legend>Par mois</legend>
        <p>
            <input data-target="selectMonth" data-handler="prev" type="image" src="/include/css/images/arrowLeftDatePicker.png" alt="Mois précédant">
            <select name="selectMonth" id="selectMonth" title="Mois">
                <?php
                $date = strtotime("first day of January");
                for ($i = 1; $i <= 12; $i++) {
                    echo '<option value="' . strftime('%m',$date) . '"'.(strftime('%m',$date)== $month?' selected':'').'>'. secureInput(ucfirst(strftime('%B',$date))).'</option>';
                    $date = strtotime("next month", $date);
                }
                ?>
            </select>
            <select name="selectYear" id="selectYear" title="Année">
                <?php
                foreach ($aYearsAvailable as  $v) {
                    echo '<option value="' . $v . '"'.($v==$year?' selected':'').'>'. secureInput($v).'</option>';
                }
                ?>
            </select>
            <input data-target="selectMonth" data-handler="next" type="image" src="/include/css/images/arrowRightDatePicker.png" alt="Mois suivant">
            <span class="aligncenter submit">
                <input type="hidden" name="type" value="<?php echo secureInput($_GET['type'])?>">
                <input class="btnAction" type="submit" name="submitCurrentMonth" id="submitCurrentMonth" value="Mois en cours">
                <input class="btnAction" type="submit" name="submitMonth" id="submitMonth" value="Ok">
            </span>
        </p>
    </fieldset>
    <fieldset class="tab <?php echo $classTabSelectYear?>">
        <legend>Par année</legend>
        <p>
            <input data-target="selectYearOnly" data-handler="prev" type="image" src="/include/css/images/arrowLeftDatePicker.png" alt="Année précédante">
            <select name="selectYear" id="selectYearOnly" title="Année">
                <?php
                foreach ($aYearsAvailable as  $v) {
                    echo '<option value="' . $v . '"'.($v==$year?' selected':'').'>'. secureInput($v).'</option>';
                }
                ?>
            </select>
            <input data-target="selectYearOnly" data-handler="next" type="image" src="/include/css/images/arrowRightDatePicker.png" alt="Année suivante">
            <span class="aligncenter submit">
                <input type="hidden" name="type" value="<?php echo secureInput($_GET['type'])?>">
                <input class="btnAction" type="submit" name="submitCurrentYear" id="submitCurrentYear" value="Année en cours">
                <input class="btnAction" type="submit" name="submitYear" id="submitYear" value="Ok">
            </span>
        </p>
    </fieldset>
</form>
<?php
}
?>
