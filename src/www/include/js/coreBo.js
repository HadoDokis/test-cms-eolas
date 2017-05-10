;var oBo = (function () {
    var colorboxBo = {}, notificationMsg = {};
    notificationMsg = {
        timeout: null,
        timeToPutAsside: 2000,
        init: function (){
            if ($("#bo_msg_notification").length === 0) {
                return false;
            }
            $("#bo_msg_notification").click(notificationMsg.putAsside);
            notificationMsg.timeout = setTimeout(notificationMsg.putAsside, notificationMsg.timeToPutAsside);
            return true;
        },
        putAsside: function () {
            if (notificationMsg.timeout !== null) {
                clearTimeout(notificationMsg.timeout);
                notificationMsg.timeout = null;
            }
            var position = $("#bo_bandeau_bas").position();
            $("#bo_msg_notification").animate({left: 300, top: position.top}, 500);
        }
    };
    colorboxBo = {
        init: function () {
            $('a.lightbox').colorbox({
                title: function(){
                    return $(".spanLegende", $(this).parent().parent()).html();
                },
                onComplete: function(){
                    var alt = '';
                    if($('img', this).attr('alt') != $(this).attr('title') && $('img', this).attr('alt') != ''){
                        alt = $('img', this).attr('alt');
                    }
                    $('.cboxPhoto').attr('alt', alt);
                },
                maxWidth:"95%",
                maxHeight:"95%",
                current:"Image {current} sur {total}"
            });
        }
    };
    $(document).ready(function () {
        notificationMsg.init();
        colorboxBo.init();
    });
}());
var cmsBO = {
    arboShowN2: true,
    timerMenuBO: null,
    popupWidth: 800,
    coverPopup: false,
    coverTimeout: false,
    init: function() {
        //aide
        $('.helper')
            .each(function() {
                $(this).data('content', $(this).html());
                $(this).html('<img src="' + SERVER_ROOT + 'images/tooltip.png" alt=""/>');
                $(this).attr('title', '');//on force le title pour le tooltip
            })
            .tooltip({
                content: function() {return $(this).data('content')},
                track: true,
                position: {
                    my: "left+10 top+15",
                    at: "left bottom",
                    collision: "flipfit flipfit"
                }
            });
        //confirm
        $(document).on('click', 'a.confirm, input[type="submit"].confirm', function() {
            str = 'Êtes-vous sûr';
            if ($(this).attr('title') !== undefined && $(this).attr('title') !== '') {
                str += ' de vouloir\n"' + $(this).attr('title') + '"';
            }
            str += ' ?';
            return confirm(str);
        });
        $(document).on('click', 'input[type="button"].confirm', function() {
            str = 'Êtes-vous sûr';
            if ($(this).attr('title') !== undefined && $(this).attr('title') !== '') {
                str += ' de vouloir\n"' + $(this).attr('title') + '"';
            }
            str += ' ?';
            if (confirm(str)) {
                window.location.href = $(this).data('href');
                return true;
            }
            return false;
        });
        
        //gestion nb caractère max
        $('textarea[data-maxchar], input[data-maxchar]')
            .after('<br><span class="counter"/>')
            .on('keyup', function() {
                $span = $(this).nextAll('span.counter');
                $span.html('Nombre de caractères : ' + $(this).val().length + ' / ' + $(this).data('maxchar'));
                if ($(this).val().length > $(this).data('maxchar')) {
                    $span.css('color', 'red');
                } else {
                    $span.css('color', '');
                }
            })
            .keyup();
        $('input[data-subtype$="later"]').each(function() {
            var $_this = $(this);
            $a = $('<a class="action" href="#">Date lointaine</a>');
            $a.on('click', function() {
                $_this.val('31/12/2035');
                return false;
            });
            $(this).after($a).after(' ');
        });
        $('input[data-subtype^="now"]').each(function() {
            var $_this = $(this);
            $a = $('<a class="action" href="#">Date du jour</a> ');
            $a.on('click', function() {
                var date = new Date();
                str = (date.getDate() < 10) ? '0' + date.getDate() : date.getDate();
                var month = date.getMonth() + 1;
                str += (month < 10) ? '/0' + month: '/' + month;
                var year = date.getFullYear();
                str += '/' + year;
                $_this.val(str);
                return false;
            });
            $(this).after($a);
        });
        //type d'input
        $.datepicker.setDefaults($.datepicker.regional["fr"]);
        $('input[data-type="date"]')
            .attr('placeholder', 'jj/mm/aaaa')
            .attr('title', 'jj/mm/aaaa')
            .attr('pattern', '^(((0[1-9]|[12]\\d|3[01])/(0[13578]|1[02])/((19|[2-9]\\d)\\d{2}))|((0[1-9]|[12]\\d|30)/(0[13456789]|1[012])/((19|[2-9]\\d)\\d{2}))|((0[1-9]|1\\d|2[0-8])/02/((19|[2-9]\\d)\\d{2}))|(29/02/((1[6-9]|[2-9]\\d)(0[48]|[2468][048]|[13579][26])|((16|[2468][048]|[3579][26])00))))$')
            .attr('maxlength', '10')
            .attr('size', '9')
            .not('.noCalendar')
            .datepicker({
                showOn: "button",
                buttonImage: SERVER_ROOT + "images/calendar.png",
                buttonImageOnly: true,
                changeMonth: true,
                changeYear: true
            });
        $('input[data-type="integer"]')
            .attr('pattern', '^[0-9]*$')
            .attr('title', 'Nombre entier')
            .addClass('alignright');
        $('input[data-type="float"]')
            .attr('pattern', '^[0-9]*\.?[0-9]*$')
            .attr('title', 'Nombre décimal')
            .addClass('alignright');
        //gestion de l'étoile
        $('input[required], select[required], textarea[required]').not('input:checkbox, input:radio')
            .each(function() {
                $('label[for="' + $(this).attr('id') + '"]').append(' <span style="color:red">*</span>');
            });
        $('input[required]:checkbox, input[required]:radio').closest('td').prev().find('label').append(' <span style="color:red">*</span>');
        //formatage virgule flottante
        $(document).on('keyup', 'input[data-type="float"]', function() {
            $(this).val($(this).val().replace(/,/, '.'));
        });
        //formatage espace sur nombre
        $(document).on('keyup', 'input[data-type="float"], input[data-type="integer"]', function() {
            $(this).val($(this).val().replace(/\s/, ''));
        });
        cmsBO.initMenu();
        cmsBO.resizeWindow();
    },
    initMenu: function() {
        //menu
        $('#bo_bandeau_haut_menu ul.N1 > li').hover(
            function () {
                $_this = $(this);
                $('ul.N2').each(function () {
                    if (! $(this).parent().is($_this)) {
                        $(this).hide();
                        $('ul.N3', $(this)).hide();
                    }
                });
                window.clearTimeout(cmsBO.timerMenuBO);
                cmsBO.timerMenuBO = window.setTimeout(function () {$('ul.N2', $_this).slideDown(200);}, 250);
            },
            function () {
                window.clearTimeout(cmsBO.timerMenuBO);
                cmsBO.timerMenuBO = window.setTimeout(function () {
                    $('ul.N3').hide();
                    $('ul.N2').slideUp(200);
                }, 500);
                
            }
        );
        $('#bo_bandeau_haut_menu ul.N2 > li').hover(
            function () {
                window.clearTimeout(cmsBO.timerMenuBO);
                $_this = $(this);
                cmsBO.timerMenuBO = window.setTimeout(function () {
                    $('ul.N3').each(function () {
                        if ($(this).parent().is($_this)) {
                            $(this).slideDown(200);
                        } else {
                            $(this).hide();
                        }
                    });
                }, 250);
            },
            function () {
                window.clearTimeout(cmsBO.timerMenuBO);
            }
        );
        //popup (ici pour être executé en pseudo)
        $(document).on('click', 'a.popup', function() {
            cmsBO.coverShow();
            cmsBO.coverPopup = window.open(this.href, (window.name == 'POPUP') ? 'POPUP2' : 'POPUP', 'resizable,scrollbars,modal');
            cmsBO.coverTimeout = window.setInterval(cmsBO.coverInterval, 500);
            return false;
        });
    },
    initArbo: function() {
        $('.bo_arbo .fade .actionArbo').hide();
        $('.bo_arbo .fade')
            .prepend('<span class="depli"/>')
            .hover(
                function() {
                    $('.actionArbo', $(this)).show();
                },
                function() {
                    $('.actionArbo', $(this)).hide();
                }
            );
        $('.bo_arbo li').each(function() {
            if ($('ul', $(this)).length > 0) {
                 $(this).addClass('closable');
            }
        });
        if (cmsBO.arboShowN2) {
            $('.bo_arbo li li.closable ul').hide();
            $('.bo_arbo > ul > li').has('ul').addClass('opened');
        } else {
            $('.bo_arbo li.closable ul').hide();
        }
        cmsBO.resizeWindow();
        $('.bo_arbo li .depli').click(function() {
            $li = $(this).closest('li');
            if ($li.hasClass('closable')) {
                $li.children('ul').slideToggle(function() {
                    $li.toggleClass('opened');
                    cmsBO.resizeWindow();
                });
            }
        });
        $('#closeArbo').click(function() {
            $('.bo_arbo li.closable ul').slideUp(function() {
                $('.bo_arbo li.closable').removeClass('opened');
                cmsBO.resizeWindow(); 
            });
            
        });
        $('#expandArbo').click(function() {
            $('.bo_arbo li.closable ul').slideDown(function() {
                $('.bo_arbo li.closable').addClass('opened');
                cmsBO.resizeWindow();
            });
        });
    },
    initArboDnD: function() {
        var timer = '';
        var dndStart = false;
        $(".bo_arbo li li .fade .depli").before('<span class="drag"/>');
        $('.bo_arbo li:not(.closable)').append('<ul/>');
        $(document).on('mouseenter', '.bo_arbo li.closable:not(.opened)', function() {
            if (dndStart) {
                var $_this = $(this);
                timer = window.setTimeout(function() {
                    $('.depli:first', $_this).click(); 
                }, 750);
            }
        });
        $(document).on('mouseenter', '.bo_arbo li:not(.closable)', function() {
            if (dndStart) {
                var $_this = $(this);
                timer = window.setTimeout(function() {
                    $_this.children('ul').animate({minHeight: 40}, 500);
                }, 750);
            }
        });
        $(document).on('mouseleave', '.bo_arbo li', function() {
            window.clearTimeout(timer);
        });
        $(".bo_arbo li ul").sortable({
            handle: ".drag",
            connectWith: ".bo_arbo li ul",
            placeholder: "moved",
            revert: true,
            tolerance: "pointer",
            cursorAt: {left: -2, top: -2},
            items: "> li:not(.denied)",
            start: function(event, ui) {
                dndStart = true;
                if (ui.item.hasClass('opened')) {
                    $('.depli:first', ui.item).click(); 
                }
            },
            beforeStop: function(event, ui) {
                dndStart = false;
                $('.bo_arbo li').each(function() {
                    var $ul = $(this).children('ul');
                    if ($ul.children('li').length == 0) {
                        $(this).removeClass('closable opened');
                        $ul.animate({minHeight: 0}, 500)
                    }
                });
            },
            stop: function(event, ui) {
                var $ul = ui.item.closest('ul');
                var $liParent = $ul.closest('li');
                $liParent.addClass('closable opened');
                $('span.txt:first', ui.item).addClass('PST_AREDIGER');
                ui.item.addClass('justDropped');
                window.setTimeout(function() {ui.item.removeClass('justDropped')}, 3000);
                $.get(SERVER_ROOT + 'cms/cms_pageArboSubmit.php', {idtf: ui.item.attr('id'), idtfParent: $liParent.attr('id'), children: $ul.sortable('toArray')});
            }
        });
    },
    coverShow: function() {
        $('body').css('opacity', 0.5).css('background-color', '#ddd').css('cursor', 'wait');
        $(document).on('click', cmsBO.coverFocus);
    },
    coverHide: function() {
        $('body').css('opacity', '').css('background-color', '').css('cursor', '');
        $(document).off('click', cmsBO.coverFocus);
    },
    coverFocus: function(event) {
        event.preventDefault();
        if (cmsBO.coverPopup) {
            cmsBO.coverPopup.focus();
        }
    },
    coverInterval: function() {
        if (!cmsBO.coverPopup || cmsBO.coverPopup.closed) {
            window.clearInterval(cmsBO.coverTimeout);
            cmsBO.coverHide();
        }
    },
    resizeWindow: function() {
        if (window.name == 'POPUP' || window.name == 'POPUP2') {
            h = $('body').height() + 120;
            p = window.screen.height;
            if (h > p) {
                h = p - 50;
            }
            window.resizeTo(cmsBO.popupWidth, h);
         }
    }
}
