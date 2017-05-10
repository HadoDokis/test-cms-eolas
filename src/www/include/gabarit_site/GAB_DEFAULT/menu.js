var menu = {
    timer: null,
    currentLi : null,
    init: function() {
        //menu principal
        $('body').addClass('withJS');
        $("#menu .nav1").hover(menu.openTimer,menu.closeTimer);
        $("#menu .nav1").children('a').each(function() { $(this).focus(function() { menu.open($(this).parent());}) });
        menu.closeAll();
    },
    openTimer : function() {
        window.clearTimeout(menu.timer);
        menu.currentLi = this;
        menu.timer = window.setTimeout(function() { menu.open(); } , 100);
    },
    closeTimer : function() {
        window.clearTimeout(menu.timer);
        menu.timer = window.setTimeout(menu.closeAll, 100);

    },
    open : function(li) {
        if(!li) {
             li = menu.currentLi;
        }
        if(!$(li).hasClass('over')) {
            menu.closeAll();
            $(li).addClass('over');
            $(li).children('.sousMenu').each(function() { 
                $(this).show();
                
                //Positionner le menu niv2 à droite
                var div = $(this).parent().position();
                div = div.left + 462; /* 462 = largeur du bloc du sous menu */
                
                if (div > 980) { /* 980 = largeur du document */
                    $(this).css('left',  989 - div + 'px'); /* 980 + 9 = largeur de l'ombre à droite */
                }
            });
        }
        
    },
    closeAll : function(li) {
        $("#menu .nav1").removeClass('over');
        $("#menu .nav1").children('.sousMenu').each(function() { $(this).hide(); });
    }
}
$(menu.init);