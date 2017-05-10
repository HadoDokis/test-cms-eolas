/**
 * @author: davidt@eolas.fr
 * Enévement 'showTab' lancé sur le fieldset qui apparait, faire un
 * $('#bo_onglet').on('showTab', 'fieldset', maFonction);
 **/
;(function ($) {
    "use strict";
    var publicStuff = {},
        tabs = {
            className: 'tab',
            cookieName: '',
            initMarginLeft: '0px',
            init: function () {
                var reg = new RegExp("([^.]*)\\.php(.)*$"),
                    tab = window.location.href.split("/"),
                    index,
                    selectedFieldset,
                    idx,
                    $ulTabs,
                    ulTabsWidth = 0,
                    $div,
                    $tabScrollerContainer,
                    marginLeft = '0px';

                tab = reg.exec(tab[tab.length - 1]);
                tabs.cookieName = "selectedFieldset";
                if (tab && tab[1]) {
                    tabs.cookieName += '_' + tab[1];
                }
                //y a un #fieldset_X dans l'url ?
                index = window.location.href.indexOf('#fieldset_');
                //si oui, selectedFieldset = fieldset_X de l'url, sinon, on recupere du cookie
                selectedFieldset = (index !== -1) ? window.location.href.substr(index + 1) : readCookie(tabs.cookieName);
                //si toujours pas, par defaut on remets à fieldset_0
                if (selectedFieldset === null) {
                    selectedFieldset = 'fieldset_0';
                } else {
                    //si récuperation du cookie, verification de l'existence de l'onglet
                    if (selectedFieldset.lastIndexOf('_') !== -1) {
                        idx = selectedFieldset.substring((selectedFieldset.lastIndexOf('_') + 1), selectedFieldset.length);
                        idx = parseInt(idx, 10);
                        if (!isNaN(idx)) {
                            if (idx >= $('fieldset.' + tabs.className).length) {
                                selectedFieldset = 'fieldset_0';
                            }
                            marginLeft = readCookie(tabs.cookieName + '_marginLeft');
                        }
                    }
                }

                if (marginLeft === null) {
                    marginLeft = '0px';
                }
                tabs.initMarginLeft = marginLeft;

                $('fieldset.' + tabs.className).each(function (index) {
                    if ($(this).hasClass('selected')) {
                        selectedFieldset = 'fieldset_' + index;
                    }
                });
                if(selectedFieldset === 'fieldset_0') {
                	var dataIdFirstFieldset = $('fieldset.' + tabs.className).first().data('id');
                	if(dataIdFirstFieldset !== '' && dataIdFirstFieldset !== undefined) {
                		selectedFieldset = dataIdFirstFieldset;
                	}
                }
                $ulTabs = $('<ul>')
                    .attr('id', 'bo_onglet')
                    .data('selectedTab', selectedFieldset)
                    .on('click', 'a', tabs.showTab);

                $tabScrollerContainer = $('<div>').attr('id', 'tabScrollerContainer').append($('<div>').attr('id', 'tabScroller').append($ulTabs));
                $div = $('<div>').attr('id', 'tab_container').append($tabScrollerContainer).insertBefore($('fieldset.' + tabs.className + ':first'));

                $('fieldset.' + tabs.className + ' > legend').each(function (index) {
                    var $li = $('<li>'), $a = $('<a>'), hash;
                    if ($(this).parent().data('id') !== '' && $(this).parent().data('id') !== undefined) {
                        hash = $(this).parent().data('id');
                    } else {
                        hash = 'fieldset_' + index;
                        $(this).parent().data('id', hash).addClass('tabContent');
                    }
                    $a.attr('href', '#' + hash).html($(this).html());
                    $li.append($a).appendTo($ulTabs);
                    ulTabsWidth += $li.outerWidth(true);
                    if (hash === selectedFieldset) {
                        $a.parent().addClass('selected');
                    }
                    $(this).hide().parent().appendTo($div);
                });
                $ulTabs.width(ulTabsWidth);
                $('fieldset.' + tabs.className).filter(function () { return $(this).data('id') !== selectedFieldset; }).hide();

                $tabScrollerContainer
                    .append($('<a>').attr('href', '#').attr('id', 'tabLeft').click(tabs.scrollLeft))
                    .append($('<a>').attr('href', '#').attr('id', 'tabRight').click(tabs.scrollRight));

                $('a[data-rel="tab"]').click(function (e) {
                    e.preventDefault();
                    tabs.selectTab($(this).attr('href'));
                });
                tabs.resetTabPosition();
                $(window).resize(tabs.resetTabPosition);

                //traitement en cas d'erreur
                $('form').on('click', 'input[type="submit"]', function() {
                    var myForm = $(this).closest('form').get(0);
                    var i = 0;
                    for (i=0; i<myForm.elements.length; i++) {
                        if (!myForm.elements[i].checkValidity()) {
                            // Gestion de la présence des éventuels onglets
                            var el = $(myForm.elements[i]);
                            var fieldsetElTab = el.parents('fieldset.tabContent');
                            if (el.is(":hidden") && fieldsetElTab && fieldsetElTab.data('id')) {
                                $('a[href=#'+fieldsetElTab.data('id')+ ']').click();
                            }
                        }
                    }
                });
                
                cmsBO.resizeWindow();
            },
            resetTabPosition: function (e) {
                var marginLeft = tabs.initMarginLeft, typeofEvnt = typeof e;
                //Si resize, on remet tout à gauche
                if(typeofEvnt !== 'undefined' && e.type === 'resize'){
                    marginLeft = '0px';
                }
                $('#tabLeft:visible').fadeOut();
                $('#tabRight:visible').fadeOut();
                $('#bo_onglet').stop().animate({
                    marginLeft: marginLeft
                }, 'fast',
                function () {
                    var marginLeft = parseInt($('#bo_onglet').css('margin-left'));
                    if (marginLeft < 0) {
                        $('#tabLeft').fadeIn();
                    }
                    if ($('#bo_onglet').width() - $('#tabScroller').width() + marginLeft > 0) {
                        $('#tabRight:visible').fadeIn();
                    }
                });
            },
            scrollLeft: function (e) {
                e.preventDefault();
                var nextStep = '+=100px';
                if (parseInt($('#bo_onglet').css('margin-left')) >= 0) {
                    return false;
                }
                if ((parseInt($('#bo_onglet').css('margin-left')) + 100) >= 0) {
                    nextStep = '0px';
                    $('#tabLeft:visible').fadeOut();
                    $('#tabRight:not(:visible)').fadeIn();
                }
                $('#bo_onglet:not(:animated)').animate({
                    marginLeft: nextStep
                }, 'fast',
                function () {
                    $('#tabRight:not(:visible)').fadeIn();
                });
                return true;
            },
            scrollRight: function (e) {
                e.preventDefault();
                var maxMarginLeft = -1 * ($('#bo_onglet').width() - $('#tabScroller').width()),
                    nextStep = '-=100px';
                if (parseInt($('#bo_onglet').css('margin-left')) < maxMarginLeft) {
                    return false;
                }
                if ((parseInt($('#bo_onglet').css('margin-left')) - 100) < maxMarginLeft) {
                    nextStep = maxMarginLeft + 'px';
                    $('#tabRight:visible').fadeOut();
                    $('#tabLeft:not(:visible)').fadeIn();
                }
                $('#bo_onglet:not(:animated)').animate({
                    marginLeft: nextStep
                }, 'fast',
                function () {
                    $('#tabLeft:not(:visible)').fadeIn();
                });
                return true;
            },
            showTab: function (e) {
                e.preventDefault();
                var id = $(this).attr('href').replace(/^\#/, ''), $selectedTab, calculatedHeight;
                if ($('#bo_onglet').data('selectedTab') === $(this).attr('href').replace(/^\#/, '')) {
                    return false;
                }
                $selectedTab = $('fieldset.' + tabs.className).filter(function () { return $(this).data('id') === id; });
                calculatedHeight = $('fieldset.' + tabs.className + ':visible').height();
                calculatedHeight += parseInt($('fieldset.' + tabs.className + ':visible').css('margin-top'));
                calculatedHeight += parseInt($('fieldset.' + tabs.className + ':visible').css('margin-bottom'));
                calculatedHeight += parseInt($('fieldset.' + tabs.className + ':visible').css('padding-top'));
                calculatedHeight += parseInt($('fieldset.' + tabs.className + ':visible').css('padding-bottom'));
                calculatedHeight += $('#bo_onglet').height();
                calculatedHeight += parseInt($('#bo_onglet').css('margin-top'));
                calculatedHeight += parseInt($('#bo_onglet').css('margin-bottom'));
                calculatedHeight += parseInt($('#bo_onglet').css('padding-top'));
                calculatedHeight += parseInt($('#bo_onglet').css('padding-bottom'));
                $('#tab_container')
                    .height(calculatedHeight);
                $('fieldset.' + tabs.className + ':visible').fadeOut('fast', function () {

                    $selectedTab.fadeIn('fast', function () {
                        calculatedHeight = $('fieldset.' + tabs.className + ':visible').height();
                        calculatedHeight += parseInt($('fieldset.' + tabs.className + ':visible').css('margin-top'));
                        calculatedHeight += parseInt($('fieldset.' + tabs.className + ':visible').css('margin-bottom'));
                        calculatedHeight += parseInt($('fieldset.' + tabs.className + ':visible').css('padding-top'));
                        calculatedHeight += parseInt($('fieldset.' + tabs.className + ':visible').css('padding-bottom'));
                        calculatedHeight += $('#bo_onglet').height();
                        calculatedHeight += parseInt($('#bo_onglet').css('margin-top'));
                        calculatedHeight += parseInt($('#bo_onglet').css('margin-bottom'));
                        calculatedHeight += parseInt($('#bo_onglet').css('padding-top'));
                        calculatedHeight += parseInt($('#bo_onglet').css('padding-bottom'));
                        $('#tab_container')
                            .animate({ height: calculatedHeight}, function () {
                                $('#tab_container').height('auto');
                                cmsBO.resizeWindow();
                            });
                        var evnt = $.Event("showTab", { selectedTabId: $(this).data('id') });
                        $(this).trigger(evnt);
                    });
                });
                $('#bo_onglet li.selected').removeClass('selected');
                $(this).parent().addClass('selected');
                createCookie(tabs.cookieName, id, 10);
                createCookie(tabs.cookieName + '_marginLeft', $('#bo_onglet').css('margin-left'), 10);
                $('#bo_onglet').data('selectedTab', id);
                $('#bo_msg_notification').remove();
                return true;
            },
            /**
             * @params dataRel String data-rel du tab à afficher
             */
            selectTab: function (dataRel) {
                var id,
                    $relatedTab = $(dataRel);
                if ($relatedTab.length > 0 ) {
                    id = $relatedTab.data('id');
                    $('#bo_onglet a[href="#' + id + '"]').trigger('click');
                }
            }
        };

    $(document).ready(tabs.init);
}(jQuery));