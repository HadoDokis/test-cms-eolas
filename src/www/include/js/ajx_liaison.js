var ajaxLiaison = {
    idtf: null,
    classname: null,
    
    libelles : {
        fr: {
            boucle : 'Cette redirection ne peut pas fonctionner, elle provoquerait une boucle (la page choisie redirige déjà vers cette page).',
            erreur : 'Erreur durant le lancement ajax.liaison_checkRedirection.php'
        },
        en: {
            boucle : 'Cette redirection provoque une boucle.',
            erreur : 'Error : ajax.liaison_checkRedirection.php'
        }
    },
    
    pattern: /liaison_(\w+)/,
    init: function(nClassname, nIdtf) {
        ajaxLiaison.classname = nClassname;
        ajaxLiaison.idtf = nIdtf;
    },
    run: function() {
        $('div.ajax').each(function() {
                ajaxLiaison.pattern.exec(this.className);
                $(this).html = '<input type="button" name="Load" value="load" class="loadingMini">';
                ajaxLiaison.getAjax(this.id, RegExp.$1);
            });
    },
    
    saveText: function(idtfLiaison, typeLiaison, textval) {

        sUrl = SERVER_ROOT + "include/ajax/ajax.saveText.php";

        $.ajax({
            type: "POST",
            url: sUrl,
            data: { idtf: idtfLiaison, text: textval, type: typeLiaison}
        });
    },
    
    getAjax: function(idtf, file, action, value) {
        elt = '#' + idtf.replace(/:/, '\\:');

        var onlyOne = $(elt).hasClass('onlyOne') ? 1 : 0;
        var isNotNull = $(elt).hasClass('isNotNull') ? 1 : 0;
        var classStr = $(elt).attr('class');

        if (typeof classStr !== 'undefined') {
            classStr = classStr.replace(/ /g,"@");
        }
        
        //verification des redirections pour eviter les redirection en boucle
        if(idtf == 'ID_PAGE:REDIRECT' && action == 'insert') {
            if(ajaxLiaison.idtf == value) {
                return false;
            } else {
                $.ajax(SERVER_ROOT + "include/ajax/ajax.liaison_checkRedirection.php?from=" + ajaxLiaison.idtf + "&to=" + value)
                
                .done(function(data) {
                    
                    if(data === '1') {
                        var sUrl = SERVER_ROOT + "include/ajax/ajax.liaison_" + file + ".php";
                        sUrl += "?idtf=" + ajaxLiaison.idtf + "&classname=" + ajaxLiaison.classname + "&LIA_CODE=" + idtf + "&onlyOne=" + onlyOne + "&classStr=" + encodeURIComponent(classStr);
                        
                        if (action) {
                            sUrl += "&" + action + "=" + value;
                        }
                        $(elt).load(sUrl, function(){ if(typeof $.colorbox != 'undefined') { $('a.lightbox', this).colorbox({maxWidth: "95%", maxHeight: "95%"}); } else { $('a.lightbox', this).click(function(e){ e.preventDefault(); }) } });
                    } else {
                        alert(eval('ajaxLiaison.libelles.' + cms_lang + '.boucle'));
                        return false;
                    }
                })
                
                .fail(function(data) {
                    alert(eval('ajaxLiaison.libelles.' + cms_lang + '.erreur'));
                    return false;
                });
            }
        } else {
            var sUrl = SERVER_ROOT + "include/ajax/ajax.liaison_" + file + ".php";
            sUrl += "?idtf=" + ajaxLiaison.idtf + "&classname=" + ajaxLiaison.classname + "&LIA_CODE=" + idtf + "&onlyOne=" + onlyOne + "&classStr=" + encodeURIComponent(classStr);
            if (action) {
                sUrl += "&" + action + "=" + value;
            }
            $(elt).load(sUrl, function(){ if(typeof $.colorbox != 'undefined') { $('a.lightbox', this).colorbox({maxWidth: "95%", maxHeight: "95%"}); } else { $('a.lightbox', this).click(function(e){ e.preventDefault(); }) } });
        }
    }
};
$(ajaxLiaison.run);