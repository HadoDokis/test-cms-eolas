/**
 * @author: davidt@eolas.fr
 */
(function($){
    "use strict";
    var CMS = {
        dragDrop: {
            aSortableParentSelector: ['#colonneDroite', '#contenu', '#colonneGauche'],
            init: function(){
                for(var sortableParentIndex in CMS.dragDrop.aSortableParentSelector){
                    var sortableParentSelector = CMS.dragDrop.aSortableParentSelector[sortableParentIndex];
                    $(sortableParentSelector).sortable({
                        items: ".edition",
                        handle: ".pseudo_boutons_edition",
                        helper: 'clone',
                        placeholder: "ui-state-placeholder",
                        start: function(event, ui) {
                            $('.edition:not(:visible)', this).show().css('opacity', 0.5);
                            ui.placeholder.css('opacity', 0.5).height(ui.helper.height()).width(ui.helper.width());
                        },
                        stop: function(event, ui){
                            $('.edition').css('opacity', 1);
                        },
                        update: function(event, ui){
                            var idOrder = $($(ui.item[0]).parent()).sortable('toArray');
                            var sUrl = SERVER_ROOT + 'cms/PRT/PRT_Submit.php';
                            $.get(sUrl, 'DragDrop=1&order=' + idOrder);
                            CMS.dragDrop.moveBars.updateMoveBars();
                        }
                    });
                }
                CMS.dragDrop.moveBars.updateMoveBars();
            },
            moveBars: {
                moveBarSrc: {
                    up: "../images/pseudo_upParagraphe.gif",
                    down: "../images/pseudo_downParagraphe.gif",
                    left: "../images/pseudo_leftParagraphe.gif",
                    right: "../images/pseudo_rightParagraphe.gif"
                },
                moveBarLibelle: {
                    en: {
                        up: "Move up",
                        down: "Move down",
                        left: "Move left",
                        right: "Move right"
                    },
                    fr:{
                        up: "Monter",
                        down: "Descendre",
                        left: "Déplacer à gauche",
                        right: "Déplacer à droite"
                    }
                },
                updateMoveBars: function(){
                    for(var sortableParentIndex in CMS.dragDrop.aSortableParentSelector){
                        var sortableParentSelector = CMS.dragDrop.aSortableParentSelector[sortableParentIndex];
                        $(sortableParentSelector + ' .edition .move_up').show();
                        $(sortableParentSelector).each(function(){
                            if($('.edition:first .move_up', this).length>0){
                                $('.edition:first .move_up', this).hide();
                            }
                        });
                        $(sortableParentSelector + ' .edition .move_down').show();
                        $(sortableParentSelector).each(function(){
                            if($('.edition:last .move_down', this).length>0){
                                $('.edition:last .move_down', this).hide();
                            }
                        });
                    }
                }
            }
        }
    };
    $(document).ready(CMS.dragDrop.init);
})(jQuery);