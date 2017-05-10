<?php
require_once '../inc.fo_init.php';

header('Content-type: text/javascript; charset=utf-8');
header('Content-script-type: text/javascript');

$oModule = new Module('MOD_CORE');
?>;$(document).ready(function () {
    "use strict";
    //colorbox
    $("a.lightbox").colorbox({
        title: function () {
            return $(this).data('title') !== '' ? $(this).data('title') : ' ';
        },
        onComplete: function () {
            var alt = '';
            if ($('img', this).attr('alt') !== $(this).data('title') && $('img', this).attr('alt') !== '') {
                alt = $('img', this).attr('alt');
            }
            $('.cboxPhoto').attr('alt', alt);
        },
        maxWidth: '95%',
        maxHeight: '95%'
    });

    //credit image
    $(".spanCredit").hide();
    $('.spanImgOuter').hover(
        function () {
            $(".spanCredit", this).fadeIn("fast");
        },
        function () {
            $(".spanCredit", this).fadeOut("fast");
        }
    );

    //external
    $(document).on('click', 'a.external, a.document', function (e) {
        window.open(this.href);
        return false;
    });

    // Google tracker pour les liens de téléchargement document
    $("a.docEventTrack").click(function () {
        var fileName = $(this).data('file_name'), fileExt = $(this).data('file_ext');
        if (ga) {
            var location = window.location.protocol + '//' + window.location.hostname + window.location.pathname + window.location.search;
            ga('send', 'event', 'Téléchargement de document', fileName + '.' + fileExt, {'eventLabel': location});
        }
    });

    //aide
    $('.helper')
        .each(function () {
            $(this).data('content', $(this).html());
            $(this).html('<img src="<?php echo CMS::getCurrentSite()->getField('SIT_IMAGE')?>tooltip.png" alt=""/>');
            $(this).attr('title', '');//on force le title pour le tooltip
        })
        .tooltip({
            content: function () {return $(this).data('content')},
            track: true,
            position: {
                my: "left+10 top+15",
                at: "left bottom",
                collision: "flipfit flipfit"
            }
        })
        .click(function () {
            if (!$(this).hasClass('open')) {
                $(this).tooltip('open');
                $(this).addClass('open');
            } else {
                $(this).tooltip('close');
                $(this).removeClass('open');
            }
        });
    //gestion nb caractère max
    $('textarea[data-maxchar], input[data-maxchar]')
        .after('<span class="counter"/>')
        .on('keyup', function () {
            var $span = $(this).nextAll('span.counter');
            $span.html('Nombre de caractères : ' + $(this).val().length + ' / ' + $(this).data('maxchar'));
            if ($(this).val().length > $(this).data('maxchar')) {
                $span.css('color', 'red');
            } else {
                $span.css('color', '');
            }
        })
        .keyup();
    //confirm
    $(document).on('click', '.confirm', function () {
        var str = 'Êtes-vous sûr';
        if ($(this).attr('title') !== undefined && $(this).attr('title') !== '') {
            str += ' de vouloir\n"' + $(this).attr('title') + '"';
        }
        str += ' ?';
        return confirm(str);
    });
    //type d'input
    $.datepicker.setDefaults($.datepicker.regional["<?php echo CMS::getCurrentSite()->getField('SIT_SHORT_LANGUE') ?>"]);
    $('input[data-type="date"]')
        .attr('placeholder', 'jj/mm/aaaa')
        .attr('title', 'Format attendu : jj/mm/aaaa')
        .attr('pattern', '^(((0[1-9]|[12]\\d|3[01])/(0[13578]|1[02])/((19|[2-9]\\d)\\d{2}))|((0[1-9]|[12]\\d|30)/(0[13456789]|1[012])/((19|[2-9]\\d)\\d{2}))|((0[1-9]|1\\d|2[0-8])/02/((19|[2-9]\\d)\\d{2}))|(29/02/((1[6-9]|[2-9]\\d)(0[48]|[2468][048]|[13579][26])|((16|[2468][048]|[3579][26])00))))$')
        .attr('maxlength', '10')
        .not('.noCalendar')
        .datepicker({
            changeMonth: true,
            changeYear: true
        });
    $('input[data-type="integer"]')
        .attr('pattern', '^[0-9]*$')
        .attr('title', 'Format attendu : nombre entier')
        .addClass('alignright widthAuto');
    $('input[data-type="float"]')
        .attr('pattern', '^[0-9]*\.?[0-9]*$')
        .attr('title', 'Format attendu : nombre décimal')
        .addClass('alignright widthAuto');
    //gestion de l'étoile
    $('input[required], select[required], textarea[required]').not('input:checkbox, input:radio')
        .each(function () {
            $('label[for="' + $(this).attr('id') + '"]').append(' <span class="obligatoire">*</span>');
        });
    $('input[required]:checkbox, input[required]:radio').closest('p').find('label:first').append(' <span class="obligatoire">*</span>');
    //formatage virgule flottante
    $(document).on('keyup', 'input[data-type="float"]', function () {
        $(this).val($(this).val().replace(/,/, '.'));
    });
    //formatage espace sur nombre
    $(document).on('keyup', 'input[data-type="float"], input[data-type="integer"]', function () {
        $(this).val($(this).val().replace(/\s/, ''));
    });

    // Lecteur audio multi HTML5
    (function audioPlayer() {
        var players = $('.audioPlayerMulti');
        var current = 0;
        var autoplay = true;

        // Pour chaque lecteur
        players.each(function (i, player) {
            player = $(player);
            var audio = player.find('audio');
            var playlist = player.find('.playlist');
            var tracks = playlist.find('li a');

            // Première piste active
            tracks.eq(0).parent().addClass('active');

            // Choix des pistes
            tracks.click(function (e) {
                var track = $(this);
                var li = track.parent();

                e.preventDefault();

                // Remplacement des sources
                audio.find('source').remove();
                var audioProperties = track.data('audioProperties');
                audio.append('<source type="audio/mp3" src="' + track.attr('href') + '">');
                if (audioProperties[0].srcOgg) {
                    audio.append('<source type="audio/ogg" src="' + audioProperties[0].srcOgg + '">');
                }
                // Remplacement de l'alternative HTML
                audio.children(".htmlAlternative").html(audioProperties[0].htmlAlternative);

                // Changement de la piste active
                li.addClass('active').siblings().removeClass('active');

                // Reload et lecture de la piste
                audio[0].load();
                audio[0].play();

                // Nouvelle piste en cours
                current = li.index();
            });

            // Piste suivante
            audio.on('ended', function (e) {
                if (autoplay) {
                    current = current >= tracks.length - 1 ? 0 : current + 1;
                    tracks.eq(current).trigger('click');
                }
            });
        });
    })();
});

//legende image, Doit être executé après le chargement des images
$(window).load(function () {
    "use strict";
    $(".spanImgContainer").width(function () {
        var $img = $(".spanImgOuter img", $(this)).outerWidth();
        var $parent = $(this).closest('.innerParagraphe').width();
        if ($img > $parent) {
            return $parent;
        }
        return $img;
    });
});

/*
 * Echappement JS des caratères particuliers des expressions régulières (utilisée pour l'authentification)
 * @param  string str       chaine à protéger
 * @param  string delimiter Délémiteur éventuel utilisé au sein de l'expression régulière complète
 *
 * @return string Chaîne protégée
 */
function preg_quote(str, delimiter) {
    //  discuss at: http://phpjs.org/functions/preg_quote/
    // original by: booeyOH
    // improved by: Ates Goral (http://magnetiq.com)
    // improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // improved by: Brett Zamir (http://brett-zamir.me)
    // bugfixed by: Onno Marsman
    //   example 1: preg_quote("$40");
    //   returns 1: '\\$40'
    //   example 2: preg_quote("*RRRING* Hello?");
    //   returns 2: '\\*RRRING\\* Hello\\?'
    //   example 3: preg_quote("\\.+*?[^]$() {}=!<>|:");
    //   returns 3: '\\\\\\.\\+\\*\\?\\[\\^\\]\\$\\(\\)\\{\\}\\=\\!\\<\\>\\|\\:'

    return String(str)
    .replace(new RegExp('[.\\\\+*?\\[\\^\\]$(){}=!<>|:\\' + (delimiter || '') + '-]', 'g'), '\\$&');
}
