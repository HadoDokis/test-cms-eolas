(function() {
    var each = tinymce.each;
    tinymce.PluginManager.requireLangPack('cms');

    tinymce.create('tinymce.plugins.CmsPlugin', {
        init : function(ed, url) {
            var t = this;
            t.editor = ed;
            t.url = url;

            ed.addCommand('mceWBT_IMAGE', function() {
                var file = url + '/../../';
                var element = ed.selection.getNode();
                if (element.nodeName.toLowerCase() == 'img') {
                    file += 'editor_insert_image.php?idtf=' + element.getAttribute('idtf')
                                    + '&align=' + element.getAttribute('align')
                                    + '&longdesc=' + ((element.getAttribute('longdesc') != null) ? element.getAttribute('longdesc') : '')
                                    + '&credit=' + ((element.getAttribute('credit') != null) ? element.getAttribute('credit') : '')
                                    + '&popup=' + ((element.getAttribute('popup') != null) ? element.getAttribute('popup') : '')
                                    + '&legende=' + encodeURIComponent(((element.getAttribute('legende') != null) ? element.getAttribute('legende') : ''))
                                    + '&alt=' + encodeURIComponent(element.alt)
                                    + '&format=' + encodeURIComponent(element.getAttribute('format'))
                                    + '&src=' + encodeURIComponent(element.getAttribute('src'));
                }
                else {
                    file += 'editor_select_image.php';
                }     
                ed.windowManager.open({
                    file : file,
                    width : 800,
                    height : 650,
                    inline : 1, 
                    scrollbars : 1
                });
            });
    
            ed.addCommand('mceWBT_FLASH', function() {
                var file = url + '/../../';
                var element = ed.selection.getNode();
                if (ed.dom.isBlock(element) || (element.nodeName.toLowerCase() == 'img') || (element.nodeName.toLowerCase() == 'body')) {
                    if (ed.dom.hasClass(element, 'mceItemFlash')) {
                        var attributs = element.getAttribute('id').toString().split("_");
                        file += 'editor_insert_media.php?idtf=' + attributs[2]
                                        + '&align=' + element.getAttribute('align')
                                        + '&format=' + encodeURIComponent(element.getAttribute('format'))
                                        + '&WBT_CODE=WBT_FLASH';
                    }
                    else if (element.nodeName.toLowerCase() != 'img') {
                        file += 'editor_select_media.php?WBT_CODE=WBT_FLASH';
                    }
                    else {
                        return;
                    }
                    ed.windowManager.open({
                        file : file,
                        width : 800,
                        height : 600,
                        inline : 1, 
                        scrollbars : 1
                    });
                }
            });    
            
            ed.addCommand('mceWBT_VIDEO', function() {
                var file = url + '/../../';
                var element = ed.selection.getNode();
                if (ed.dom.isBlock(element) || (element.nodeName.toLowerCase() == 'img') || (element.nodeName.toLowerCase() == 'body')) {
                    if (ed.dom.hasClass(element, 'mceItemVideo')) {
                        var attributs = element.getAttribute('id').toString().split("_");
                        file += 'editor_insert_media.php?idtf=' + attributs[2]
                                        + '&align=' + element.getAttribute('align')
                                        + '&format=' + encodeURIComponent(element.getAttribute('format'))
                                        + '&WBT_CODE=WBT_VIDEO';
                    }
                    else if (element.nodeName.toLowerCase() != 'img') {
                        file += 'editor_select_media.php?WBT_CODE=WBT_VIDEO';
                    }
                    else {
                        return;
                    }
                    ed.windowManager.open({
                        file : file,
                        width : 800,
                        height : 600,
                        inline : 1, 
                        scrollbars : 1
                    });
                }
            });
    
            ed.addCommand('mceWBT_VIDEOEXTERNE', function() {
                var file = url + '/../../';
                var element = ed.selection.getNode();
                if (ed.dom.isBlock(element) || (element.nodeName.toLowerCase() == 'img') || (element.nodeName.toLowerCase() == 'body')) {
                    if (ed.dom.hasClass(element, 'mceItemVideoExterne')) {
                        var attributs = element.getAttribute('id').toString().split("_");
                        file += 'editor_insert_media.php?idtf=' + attributs[2]
                                        + '&align=' + element.getAttribute('align')
                                        + '&WBT_CODE=WBT_VIDEOEXTERNE';
                    }
                    else if (element.nodeName.toLowerCase() != 'img') {
                        file += 'editor_select_media.php?WBT_CODE=WBT_VIDEOEXTERNE';
                    }
                    else {
                        return;
                    }
                    ed.windowManager.open({
                        file : file,
                        width : 800,
                        height : 600,
                        inline : 1, 
                        scrollbars : 1
                        
                    });
                }
            });
        
            ed.addCommand('mceWBT_MUSIC', function() {
                var file = url + '/../../';
                var element = ed.selection.getNode();
                if (ed.dom.isBlock(element) || (element.nodeName.toLowerCase() == 'img') || (element.nodeName.toLowerCase() == 'body')) {
                    if (ed.dom.hasClass(element, 'mceItemMusic')) {
                        var attributs = element.getAttribute('id').toString().split("_");
                        file += 'editor_insert_media.php?idtf=' + attributs[2]
                                        + '&align=' + element.getAttribute('align')
                                        + '&WBT_CODE=WBT_MUSIC';
                    }
                    else if (element.nodeName.toLowerCase() != 'img') {
                        file += 'editor_select_media.php?WBT_CODE=WBT_MUSIC';
                    }
                    else {
                        return;
                    }
                    ed.windowManager.open({
                        file : file,
                        width : 800,
                        height : 600,
                        inline : 1
                    });
                }
            });
        
            ed.addCommand('mcePAGE', function() {
                var file = url + '/../../editor_select_lien.php';
                var element = ed.selection.getNode();
                element = ed.dom.getParent(element, 'a');
                if (element) {
                    file += '?idtf=' + element.getAttribute('id');
                    if (element.getAttribute('title') != null) {
                        file += '&title=' + encodeURIComponent(element.getAttribute('title'));
                    }
                    if (element.getAttribute('ancre') != null) {
                        file += '&ancre=' + encodeURIComponent(element.getAttribute('ancre'));;
                    }
                    if (element.getAttribute('rel') != null) {
                        file += '&nofollow=1';
                    }
                } 
                ed.windowManager.open({
                    file : file,
                    width : 800,
                    height : 600,
                    inline : 1, 
                    scrollbars : 1
                });
            });

            ed.addCommand('mceWBT_DOCUMENT', function() {
                var file = url + '/../../';
                var element = ed.selection.getNode();
                element = ed.dom.getParent(element, 'a');
                if (element) {
                    file += 'editor_insert_webotheque.php?idtf=' + element.getAttribute('id') + '&typelien=LienDocument';
                    if (element.getAttribute('title') != null) {
                        file += '&title=' + encodeURIComponent(element.getAttribute('title'));
                    }
                    if (element.getAttribute('ancre') != null) {
                        file += '&ancre=1';
                    }
                    if (element.getAttribute('rel') != null) {
                        file += '&nofollow=1';
                    }
                }
                else {
                    file += 'editor_select_webotheque.php?typelien=LienDocument';
                }
                ed.windowManager.open({
                    file : file,
                    width : 800,
                    height : 600,
                    inline : 1, 
                    scrollbars : 1
                });
            });

            ed.addCommand('mceWBT_LIENEXTERNE', function() {
                var file = url + '/../../';
                var element = ed.selection.getNode();
                element = ed.dom.getParent(element, 'a');
                if (element) {
                    file += 'editor_insert_webotheque.php?idtf=' + element.getAttribute('id') + '&typelien=LienExterne';
                    if (element.getAttribute('title') != null) {
                        file += '&title=' + encodeURIComponent(element.getAttribute('title'));
                    }
                    if (element.getAttribute('rel') != null) {
                        file += '&nofollow=1';
                    }
                }
                else {
                    file += 'editor_select_webotheque.php?typelien=LienExterne';
                }
                ed.windowManager.open({
                    file : file,
                    width : 800,
                    height : 600,
                    inline : 1, 
                    scrollbars : 1
                });
            });

            ed.addCommand('mceWBT_LIENIMAGE', function() {
                var file = url + '/../../';
                var element = ed.selection.getNode();
                element = ed.dom.getParent(element, 'a');
                if (element) {
                    file += 'editor_insert_webotheque.php?idtf=' + element.getAttribute('id') + '&typelien=LienImage';
                    if (element.getAttribute('title') != null) {
                        file += '&title=' + encodeURIComponent(element.getAttribute('title'));
                    }
                    if (element.getAttribute('rel') != null) {
                        file += '&nofollow=1';
                    }
                }
                else {
                    file += 'editor_select_webotheque.php?typelien=LienImage';
                }
                ed.windowManager.open({
                    file : file,
                    width : 800,
                    height : 600,
                    inline : 1, 
                    scrollbars : 1
                });
            });

            ed.addCommand('mceTPL', function() {
                var file = url + '/../../editor_select_tpl.php';
                var element = ed.selection.getNode();
                element = ed.dom.getParent(element, 'a');
                if (element) {
                    file += '?idtf=' + element.getAttribute('id');
                    if (element.getAttribute('title') != null) {
                        file += '&title=' + encodeURIComponent(element.getAttribute('title'));
                    }
                    if (element.getAttribute('ancre') != null) {
                        file += '&ancre=' + encodeURIComponent(element.getAttribute('ancre'));
                        if (element.getAttribute('libelle_ancre') != null) {
                            file += '&libelle_ancre=' + encodeURIComponent(element.getAttribute('libelle_ancre'));
                        }
                    }
                    if (element.getAttribute('rel') != null) {
                        file += '&nofollow=1';
                    }
                }
                ed.windowManager.open({
                    file : file,
                    width : 800,
                    height : 600,
                    inline : 1, 
                    scrollbars : 1
                });
            });

            ed.addCommand('mceABBR', function() {
                var file = url + '/../../editor_select_formeAbregee.php';
                var element = ed.selection.getNode();
                element = ed.dom.getParent(element, 'acronym,abbr');
                if (element) {
                    file += '?f_lang=' + element.getAttribute('lang')
                                + '&f_title=' + encodeURIComponent(element.getAttribute('title'))
                                + '&f_tagname=' + element.tagName;
                }
                else {
                    file += '?f_acronyme=' + encodeURIComponent(ed.selection.getContent({format : 'text'}));
                }  
                ed.windowManager.open({
                    file : file,
                    width : 480,
                    height : 275,
                    inline : 1
                });
            });

            ed.addCommand('mceABBR_DEL', function() {
                var element = ed.selection.getNode();
                element = ed.dom.getParent(element, 'acronym,abbr');
                while (element.firstChild) {
                    element.parentNode.insertBefore(element.firstChild, element);
                }
                element.parentNode.removeChild(element);
            });

            ed.addCommand('mceLANGUE', function() {
                var file = url + '/../../editor_select_languisme.php';
                var element = ed.selection.getNode();
                element = ed.dom.getParent(element, 'span');
                if (element) {
                    file += '?f_lang=' + ed.dom.getAttrib(element, 'lang', '');
                }
                ed.windowManager.open({
                    file : file,
                    width : 480,
                    height : 200,
                    inline : 1
                });
            });

            ed.addCommand('mceLANGUE_DEL', function() {
                var element = ed.selection.getNode();
                element = ed.dom.getParent(element, 'span');
                while (element.firstChild) {
                    element.parentNode.insertBefore(element.firstChild, element);
                }
                element.parentNode.removeChild(element);
            });

            ed.addCommand('mceEAM_TXT', function() {
                var file = url + '/../../editor_select_eam_txt.php';
                var element = ed.selection.getNode();
                ed.windowManager.open({
                    file : file,
                    width : 800,
                    height : 200,
                    inline : 1,
                    scrollbar: 1
                });
            });

            ed.addCommand('mceEAM_LIEN', function() {
                var file = url + '/../../editor_select_eam_lien.php';
                var element = ed.selection.getNode();
                element = ed.dom.getParent(element, 'a');
                if (element) {
                    file += '?type=' + element.getAttribute('type')
                            + '&title=' + encodeURIComponent(element.getAttribute('title') ? element.getAttribute('title') : '');
                }
                ed.windowManager.open({
                    file : file,
                    width : 800,
                    height : 200,
                    inline : 1,
                    scrollbar: 1
                });
            });
            
            each([
                ['cmsFlash', 'cms.Flash_desc', 'mceWBT_FLASH', 'flash.gif', true],
                ['cmsVideo', 'cms.Video_desc', 'mceWBT_VIDEO', 'video.gif', true],
                ['cmsVideoExterne', 'cms.VideoExterne_desc', 'mceWBT_VIDEOEXTERNE', 'videoExterne.gif', true],
                ['cmsMusic', 'cms.Music_desc', 'mceWBT_MUSIC', 'music.gif', true],
                ['cmsImage', 'cms.Image_desc', 'mceWBT_IMAGE', 'img.gif', true],
                ['cmsLienInterne', 'cms.LienInterne_desc', 'mcePAGE', 'page.gif', true],
                ['cmsLienDocument', 'cms.LienDocument_desc', 'mceWBT_DOCUMENT', 'doc.gif', true],
                ['cmsLienExterne', 'cms.LienExterne_desc', 'mceWBT_LIENEXTERNE', 'link.gif', true],
                ['cmsLienTemplate', 'cms.LienTemplate_desc', 'mceTPL', 'tpl.gif', true],
                ['cmsLienImage', 'cms.LienImage_desc', 'mceWBT_LIENIMAGE', 'img.gif', true],
                ['cmsAbbr', 'cms.Abbr_desc', 'mceABBR', 'abbr.gif', true],
                ['cmsLangue', 'cms.Langue_desc', 'mceLANGUE', 'lang.gif', true],
                ['cmsEamTxt', 'cms.EamTxt_desc', 'mceEAM_TXT', 'eamTxt.gif', true],
                ['cmsEamLien', 'cms.EamLien_desc', 'mceEAM_LIEN', 'eamLien.gif', true]
            ], function(c) {
                ed.addButton(c[0], {title : c[1], cmd : c[2], image : url + '/img/' + c[3], ui : c[4]});
            });
            

            ed.onInit.add(function(ed) {
                //css
                ed.dom.loadCSS(url + "/css/content.css");
                tinymce.DOM.loadCSS(url + "/css/content.css");

                //Path correct des medias (et non pas IMG)
                var lo = {
                    mceItemFlash : 'flash',
                    mceItemVideo : 'video',
                    mceItemVideoExterne : 'videoExterne',
                    mceItemMusic : 'music'
                };
                if (ed.theme.onResolveName) {
                    ed.theme.onResolveName.add(function(th, o) {
                        if (o.name == 'img') {
                            each(lo, function(v, k) {
                                if (ed.dom.hasClass(o.node, k)) {
                                    o.name = v;
                                    return false;
                                }
                            });
                        }
                    });
                }

                //menu contextuel
                ed.plugins.contextmenu.onContextMenu.add(function(th, m, e) {
                    var selectionTXT = tinymce.trim(ed.selection.getContent({format : 'text'}));
                    var collapsed = ed.selection.isCollapsed();
        
                    // Vérification plugin EAM
                    var isEAM = (document.getElementById(new tinymce.ControlManager(ed).prefix + 'cmsEamLien') != null);
                    
                    //on enlève les commandes par défaut (img et lien)
                    // => semble ne pas marcher => on recrée tout à l'identique de context-menu
                    m.removeAll();
                    m.add({title : 'advanced.cut_desc', icon : 'cut', cmd : 'Cut'}).setDisabled(collapsed);
                    m.add({title : 'advanced.copy_desc', icon : 'copy', cmd : 'Copy'}).setDisabled(collapsed);
                    m.add({title : 'advanced.paste_desc', icon : 'paste', cmd : 'Paste'});
                    m.addSeparator();
                    am = m.addMenu({title : 'contextmenu.align'});
                    am.add({title : 'contextmenu.left', icon : 'justifyleft', cmd : 'JustifyLeft'});
                    am.add({title : 'contextmenu.center', icon : 'justifycenter', cmd : 'JustifyCenter'});
                    am.add({title : 'contextmenu.right', icon : 'justifyright', cmd : 'JustifyRight'});
                    am.add({title : 'contextmenu.full', icon : 'justifyfull', cmd : 'JustifyFull'});

                    m.addSeparator();
                    if (elem = ed.dom.getParent(e, 'a')) {
                        //je suis dans un lien
                        switch (ed.dom.getAttrib(elem, 'typelien')) {
                            case 'LienInterne':
                                m.add({title : 'cms.Lien_modif_desc', cmd : 'mcePAGE', icon : 'PAGE', ui : true});
                                m.add({title : 'cms.Lien_suppr_desc', cmd : 'UnLink'});
                                break;
                            case 'LienExterne':
                                m.add({title : 'cms.Lien_modif_desc', cmd : 'mceWBT_LIENEXTERNE', icon : 'WBT_LIENEXTERNE', ui : true});
                                m.add({title : 'cms.Lien_suppr_desc',  cmd : 'UnLink'});
                                break;
                            case 'LienTemplate':
                                m.add({title : 'cms.Lien_modif_desc', cmd : 'mceTPL', icon : 'TPL', ui : true});
                                m.add({title : 'cms.Lien_suppr_desc', cmd : 'UnLink'});
                                break;
                            case 'LienDocument':
                                m.add({title : 'cms.Lien_modif_desc', cmd : 'mceWBT_DOCUMENT', icon : 'WBT_DOCUMENT', ui : true});
                                m.add({title : 'cms.Lien_suppr_desc', cmd : 'UnLink'});
                                break;
                            case 'LienImage':
                                m.add({title : 'cms.Lien_modif_desc', cmd : 'mceWBT_IMAGE', icon : 'WBT_IMAGE', ui : true});
                                m.add({title : 'cms.Lien_suppr_desc', cmd : 'UnLink'});
                                break;
                            case 'EamLien':
                                m.add({title : 'cms.Lien_modif_desc', cmd : 'mceEAM_LIEN', icon : 'EAM_LIEN', ui : true});
                        }
                    }
                    else if (selectionTXT != '' || (e.nodeName.toLowerCase() == 'img' &&
                                                            !ed.dom.hasClass(e, 'mceItemFlash') &&
                                                            !ed.dom.hasClass(e, 'mceItemVideo') &&
                                                            !ed.dom.hasClass(e, 'mceItemVideoExterne') &&
                                                            !ed.dom.hasClass(e, 'mceItemMusic'))) {
                        //je peux creer un lien
                        m.add({title : 'cms.LienInterne_desc', cmd : 'mcePAGE', icon : 'PAGE', ui : true});
                        m.add({title : 'cms.LienDocument_desc', cmd : 'mceWBT_DOCUMENT', icon : 'WBT_DOCUMENT', ui : true});
                        m.add({title : 'cms.LienExterne_desc', cmd : 'mceWBT_LIENEXTERNE', icon : 'WBT_LIENEXTERNE', ui : true});
                        m.add({title : 'cms.LienTemplate_desc', cmd : 'mceTPL', icon : 'TPL', ui : true});
                        m.add({title : 'cms.LienImage_desc', cmd : 'mceWBT_IMAGE', icon : 'WBT_IMAGE', ui : true});
                    }
                    
                    if (isEAM) {
                        m.add({title : 'cms.EamLien_desc', cmd : 'mceEAM_LIEN', icon : 'EAM_LIEN', ui : true});
                    }
                    
                    //ABBR + LANG
                    if (ed.dom.getParent(e, 'acronym,abbr')) {
                        m.addSeparator();
                        m.add({title : 'cms.Abbr_modif_desc', cmd : 'mceABBR', icon : 'ABBR', ui : true});
                        m.add({title : 'cms.Abbr_suppr_desc', cmd : 'mceABBR_DEL'});
                    }
                    if (elem = ed.dom.getParent(e, 'span')) {
                        if (elem.getAttribute('lang') != null) {
                            m.addSeparator();
                            m.add({title : 'cms.Langue_modif_desc', cmd : 'mceLANGUE', icon : 'LANGUE', ui : true});
                            m.add({title : 'cms.Langue_suppr_desc', cmd : 'mceLANGUE_DEL'});
                        }
                    }
                    
                    //IMG (ou media)
                    m.addSeparator();
                    if (e.nodeName.toLowerCase() == 'img') {
                        if (ed.dom.hasClass(e, 'mceItemFlash')) {
                            m.add({title : 'cms.Flash_desc', cmd : 'mceWBT_FLASH', icon : 'WBT_FLASH'});
                        }
                        else if (ed.dom.hasClass(e, 'mceItemMusic')) {
                            m.add({title : 'cms.Music_desc', cmd : 'mceWBT_MUSIC', icon : 'WBT_MUSIC'});
                        }
                        else if (ed.dom.hasClass(e, 'mceItemVideo')) {
                            m.add({title : 'cms.Video_desc', cmd : 'mceWBT_VIDEO', icon : 'WBT_VIDEO'});
                        }
                        else if (ed.dom.hasClass(e, 'mceItemVideoExterne')) {
                            m.add({title : 'cms.VideoExterne_desc', cmd : 'mceWBT_VIDEOEXTERNE', icon : 'WBT_VIDEOEXTERNE'});
                        }
                        else if (ed.dom.getAttrib(e, 'idtf') != '') {
                            m.add({title : 'cms.Image_desc', cmd : 'mceWBT_IMAGE', icon : 'WBT_IMAGE'});
                        }
                    }
                    else {
                        m.add({title : 'cms.Image_desc', cmd : 'mceWBT_IMAGE', icon : 'WBT_IMAGE'});
                    }
                });
            });

            ed.onNodeChange.add(function(ed, cm, n) {
                var selectionTXT = tinymce.trim(ed.selection.getContent({format : 'text'}));
                
                //par défaut on désactive tout
                cm.setActive('cmsFlash', false);
                cm.setActive('cmsVideo', false);
                cm.setActive('cmsVideoExterne', false);
                cm.setActive('cmsMusic', false);
                cm.setActive('cmsImage', false);
                cm.setActive('cmsLienInterne', false);
                cm.setActive('cmsLienDocument', false);
                cm.setActive('cmsLienExterne', false);
                cm.setActive('cmsLienTemplate', false);
                cm.setActive('cmsLienImage', false);
                cm.setActive('cmsAbbr', false);
                cm.setActive('cmsLangue', false);
                cm.setActive('cmsEamLien', false);
                cm.setDisabled('cmsFlash', true);
                cm.setDisabled('cmsVideo', true);
                cm.setDisabled('cmsVideoExterne', true);
                cm.setDisabled('cmsImage', true);
                cm.setDisabled('cmsLienInterne', true);
                cm.setDisabled('cmsLienDocument', true);
                cm.setDisabled('cmsLienExterne', true);
                cm.setDisabled('cmsLienTemplate', true);
                cm.setDisabled('cmsLienImage', true);
                cm.setDisabled('cmsAbbr', true);
                cm.setDisabled('cmsLangue', true);
                cm.setDisabled('cmsEamLien', true);

                if (elem = ed.dom.getParent(n, 'a')) {
                    //je suis dans un lien, mais lequel ?
                    switch (ed.dom.getAttrib(elem, 'typelien')) {
                        case 'LienInterne':
                            cm.setDisabled('cmsLienInterne', false);
                            cm.setActive('cmsLienInterne', true);
                            break;
                        case 'LienExterne':
                            cm.setDisabled('cmsLienExterne', false);
                            cm.setActive('cmsLienExterne', true);
                            break;
                        case 'LienTemplate':
                            cm.setDisabled('cmsLienTemplate', false);
                            cm.setActive('cmsLienTemplate', true);
                            break;
                        case 'LienDocument':
                            cm.setDisabled('cmsLienDocument', false);
                            cm.setActive('cmsLienDocument', true);
                            break;
                        case 'LienImage':
                            cm.setDisabled('cmsLienImage', false);
                            cm.setActive('cmsLienImage', true);
                            break;   
                        case 'EamLien':
                            cm.setDisabled('cmsEamLien', false);
                            cm.setActive('cmsEamLien', true);
                            break;
                    }
                }
                else if (selectionTXT != '' || (n.nodeName.toLowerCase() == 'img' &&
                                                            !ed.dom.hasClass(n, 'mceItemFlash') &&
                                                            !ed.dom.hasClass(n, 'mceItemVideo') &&
                                                            !ed.dom.hasClass(n, 'mceItemVideoExterne') &&
                                                            !ed.dom.hasClass(n, 'mceItemMusic'))) {
                    //je peux creer un lien
                    cm.setDisabled('cmsLienInterne', false);
                    cm.setDisabled('cmsLienDocument', false);
                    cm.setDisabled('cmsLienExterne', false);
                    cm.setDisabled('cmsLienTemplate', false);
                    cm.setDisabled('cmsLienImage', false);
                    cm.setDisabled('cmsEamLien', false);
                }

                //ABBR + LANG
                if (selectionTXT != '') {
                    cm.setDisabled('cmsAbbr', false);
                    cm.setDisabled('cmsLangue', false);
                }
                if (ed.dom.getParent(n, 'acronym,abbr')) {
                    cm.setDisabled('cmsAbbr', false);
                    cm.setActive('cmsAbbr', true);
                }
                if (elem = ed.dom.getParent(n, 'span')) {
                    if (elem.getAttribute('lang') != null) {
                        cm.setDisabled('cmsLangue', false);
                        cm.setActive('cmsLangue', true);
                    }
                }
                
                //IMG et media
                if (n.nodeName.toLowerCase() == 'img') {
                    if (ed.dom.hasClass(n, 'mceItemFlash')) {
                        cm.setDisabled('cmsFlash', false);
                        cm.setActive('cmsFlash', true);
                    }
                    else if (ed.dom.hasClass(n, 'mceItemMusic')) {
                        cm.setDisabled('cmsMusic', false);
                        cm.setActive('cmsMusic', true);
                    }
                    else if (ed.dom.hasClass(n, 'mceItemVideo')) {
                        cm.setDisabled('cmsVideo', false);
                        cm.setActive('cmsVideo', true);
                    }
                    else if (ed.dom.hasClass(n, 'mceItemVideoExterne')) {
                        cm.setDisabled('cmsVideoExterne', false);
                        cm.setActive('cmsVideoExterne', true);
                    }
                    else if (ed.dom.getAttrib(n, 'idtf') != '') {
                        cm.setDisabled('cmsImage', false);
                        cm.setActive('cmsImage', true);
                    }
                }
                else {
                    cm.setDisabled('cmsImage', false);
                    if (ed.dom.isBlock(n) || (n.nodeName.toLowerCase() == 'body') || (n.nodeName.toLowerCase() == 'br')) {
                        cm.setDisabled('cmsMusic', false);
                        cm.setDisabled('cmsFlash', false);
                        cm.setDisabled('cmsVideo', false);
                        cm.setDisabled('cmsVideoExterne', false);
                    }                
                }
            });

            ed.onBeforeSetContent.add(function(ed, o) {
                var h = o.content;
                h = h.replace(/<object([^>]*)>/gi, '<img $1' + ' src="' + t.url + '/img/trans.gif" />');
                h = h.replace(/<\/(object)([^>]*)>/gi, '');
                h = h.replace(/(id="WBT_MUSIC)/gi, 'class="mceItemMusic" $1');
                h = h.replace(/(id="WBT_FLASH)/gi, 'class="mceItemFlash" $1');
                h = h.replace(/(id="WBT_VIDEO)/gi, 'class="mceItemVideo" $1');
                h = h.replace(/(id="WBT_VIDEOEXTERNE)/gi, 'class="mceItemVideoExterne" $1');
                o.content = h;
            });

            ed.onPreProcess.add(function(ed, o) {
                var dom = ed.dom;
                if (o.get) {
                    each(dom.select('IMG', o.node), function(n) {
                        var wbt_code = dom.getAttrib(n, "id", '').toString().replace(/^(WBT_[^_]*)_.*$/,'$1');
                        if (wbt_code != '') {
                            var obj = dom.create('object', {
                                id: dom.getAttrib(n, "id", ''),
                                width : dom.getAttrib(n, 'width'),
                                height : dom.getAttrib(n, 'height')
                            });
                            dom.replace(obj, n);
                        }
                    });
                }
            });
        },

        getInfo : function() {
            return {
                longname : 'CMS plugin',
                author : 'Eolas',
                authorurl : 'http://www.eolas.fr',
                infourl : '',
                version : "7.0"
            };
        }
        
    });

    tinymce.PluginManager.add('cms', tinymce.plugins.CmsPlugin);
})();
