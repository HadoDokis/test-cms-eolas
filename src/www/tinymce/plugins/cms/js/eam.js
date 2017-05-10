function insererEamLien(ed, id, title) {
    ed.execCommand("mceBeginUndoLevel");
    var elm = ed.selection.getNode();
    elm = ed.dom.getParent(elm, function(n) {return n.nodeName.toLowerCase()=='a'}, ed.getBody());
    if (elm == null) {
        //il faut donner un nom unique
        tmp = "#mce_temp_url#" + new Date().getTime();
        tinyMCEPopup.execCommand("mceInsertLink", false, tmp);
        aElm = tinymce.grep(ed.dom.select("a"), function(n) {return ed.dom.getAttrib(n, 'href') == tmp;});
        elm = aElm[0];
    }
    elm.setAttribute("title", title);
    elm.setAttribute("typelien",  'EamLien');
    elm.setAttribute("type", id);
    ed.execCommand("mceEndUndoLevel");
}

function insererEamTxt(ed, txt) {
    ed.execCommand("mceBeginUndoLevel");
    ed.execCommand("mceInsertContent", false, txt);
    ed.execCommand("mceEndUndoLevel");
}