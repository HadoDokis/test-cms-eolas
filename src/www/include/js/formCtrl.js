/****** BEGIN LICENSE BLOCK *****
 * Copyright (c) 2005-2006 Harmen Christophe and contributors. All rights reserved.
 * 
 * This script is free software; you can redistribute it and/or
 *   modify under the terms of the Creative Commons - Attribution-ShareAlike 2.0
 * <http://creativecommons.org/licenses/by-sa/2.0/>
 * You are free:
 *     * to copy, distribute, display, and perform the work
 *     * to make derivative works
 *     * to make commercial use of the work
 * 
 * Under the following conditions:
 * _Attribution_. You must attribute the work in the manner specified by the
 *   author or licensor.
 * _Share Alike_. If you alter, transform, or build upon this work, you may
 *   distribute the resulting work only under a license identical to this one.
 *     * For any reuse or distribution, you must make clear to others 
 *      the license terms of this work.
 *     * Any of these conditions can be waived if you get permission from 
 *      the copyright holder.
 * 
 * Your fair use and other rights are in no way affected by the above.
 * 
 * This is a human-readable summary of the Legal Code (the full license). 
 * <http://creativecommons.org/licenses/by-sa/2.0/legalcode>
 ***** END LICENSE BLOCK ******/
/**
 * 2007/05/25 V0.6.0
 */
formCtrl = {
    /**
     * @var array   Tableau définissant les classes HTML permetant de lancer un contrôle ainsi que les messages à utiliser en cas d'echec du dit contrôle 
     */         
    schemes : [
        ["isNotNull","Le champ \"%s\" doit être renseigné."],
        ["isDate","Le champ \"%s\" n'est pas une date valide.\nFormat : jj/mm/aaaa."],
        ["isEmail","Le champ \"%s\" n'est pas un email valide."],
        ["isInt","Le champ \"%s\" n'est pas un entier valide."],
        ["isFloat","Le champ \"%s\" n'est pas un réel valide."],
        ["min","La sélection \"%s\" doit comporter %d éléments au minimum."],
        ["max","La sélection \"%s\" doit comporter %d éléments au maximum."],
    ],
    /**
     * Retourne contenu textuel d'un noeud après lui avoir suprimé les espaces redondants et les caractères espaces, : et * présents en début ou fin de chaîne
     * @param   Node    Noeud à partir a utiliser
     * @return  string  Contenu textuel épuré de certain caractère     
     */         
    getClearedTextLabel: function (nLabel) {
        return this.util.getTextContent(nLabel).replace(/\s{2,}/g," ").replace(/^[\s:*]+|[\s:*]+$/g,"");
    },
    /**
     * Charge le contrôleur : Ajoute à chacun des éléments <form /> du document un gestionnaire sur le submit qui lancera la procédure de validation
     * Si un élément <label /> n'est pas valide (label implicite non associé à un champ), une alert le signalera
     */ 
    load: function() {
        var cForms, lg, cLabels, nLabel;
        cForms = document.forms;
        lg = cForms.length;
        for (var i=0; i < lg; i++) {
            cLabels = cForms[i].getElementsByTagName("label");
            for (var j=0; nLabel=cLabels[j];j++) {
                if ((nLabel.htmlFor!="") && (document.getElementById(nLabel.htmlFor)==null)) {
                    alert("L'étiquette  \"" + formCtrl.getClearedTextLabel(nLabel) + "\" est associée à un champ de formulaire inexistant (sans l'id \""+nLabel.htmlFor+"\").");
                }
                if(nLabel.htmlFor=="" && formCtrl.util.hasClassName(nLabel,"isNotNull")){
                    var parent = nLabel.parentNode;              
                    while(parent && !formCtrl.util.hasClassName(parent,"isNotNullContainer")){
                        parent = parent.parentNode;
                    }
                    if(!parent){
                        alert("L'étiquette  \"" + formCtrl.getClearedTextLabel(nLabel) + "\" est un champs de type isNotNull mais n'a pas de parent ayant la classe 'isNotNullContainer'");
                    }
                }
            }
            formCtrl.util.addEventLst(cForms[i],"submit",formCtrl.control);
        }
    },
    /**
     * Gestionnaire associé aux events de type submit qui tente de valider les données saisie dans chacun des champs
     */
    control: function (evt) {
        var bIsValide, cLabels;
        bIsValide = true;
        try {
            if (bIsValide && (typeof(eval("preControl_"+this.id))=="function"))
                bIsValide = eval("preControl_"+this.id+"(this);");
        } catch(err) {}
        cLabels = this.getElementsByTagName("label");
        var nField, oNode;
        for (var i=0; bIsValide && i<cLabels.length; i++) {
            if (((cLabels[i].htmlFor=="") || !(nField=document.getElementById(cLabels[i].htmlFor))) && !formCtrl.util.hasClassName(cLabels[i],"isNotNull")) continue;
            //* Traitement particulier pour les contrôles liés aux traitements ajax (gestion des liaisons page, wébothèque et externe)
            if (formCtrl.util.hasClassName(nField,"ajax")) {
                // Traitement du isNotNull
                // La nField.value n'est pas null si une liaison a été ajoutée :
                nField.value = $(document.getElementById(cLabels[i].htmlFor)).html(); // Obligé de passer par "getElementById" car l'ID peut comporter des ":" qui font planter JQuery lors de l'appel du type $('#'+cLabels[i].htmlFor) @TODO : Voir pourquoi (pseudo-classe du type ":hover" ?) et si cela n'est pas bloquant pour les min et max contrôle)
            }
            //*/
            for (var j=0; bIsValide && formCtrl.schemes[j]; j++) {
                if (formCtrl.util.hasClassName(cLabels[i],formCtrl.schemes[j][0])) {
                    if (formCtrl.schemes[j][0]=="isFloat") nField.value = nField.value.replace(",",".");
                    if (formCtrl.util.hasClassName(cLabels[i],"isNotNull") && 
                            cLabels[i].htmlFor=="" && 
                            formCtrl.schemes[j][0]=="isNotNull" && 
                            !eval("formCtrl.fx."+formCtrl.schemes[j][0]+"(cLabels[i])")){
                        bIsValide = false;
                        alert(formCtrl.schemes[j][1].replace(/[^\W]*%s[^\W]*/g,formCtrl.getClearedTextLabel(cLabels[i])));
                    } else if (cLabels[i].htmlFor!="" && !eval("formCtrl.fx."+formCtrl.schemes[j][0]+"(nField.value)")) {
                        if(nField.tagName.toLowerCase()!='textarea'){ //Si ce n'est pas un textarea
                            bIsValide = false;
                        } else if(document.getElementById(nField.id + '_ifr') // si ce n'est pas un champs riche
                                    && tinymce.get(nField.id) //Et que l'éditeur a pu être récupéré
                                    && !formCtrl.fx.isNotNull(tinymce.get(nField.id).getContent({format : 'text'}))){
                            bIsValide = false; 
                        } else if(!formCtrl.fx.isNotNull(nField.value)){
                            bIsValide = false; 
                        }
                        if (!bIsValide) {
                            alert(formCtrl.schemes[j][1].replace(/[^\W]*%s[^\W]*/g,formCtrl.getClearedTextLabel(cLabels[i])));
                        }
                    }
                }
            }
            //* Traitement particulier pour les contrôles liés aux traitements ajax (gestion des liaisons page, wébothèque et externe)
            if (bIsValide && formCtrl.util.hasClassName(nField,"ajax")) {
                var errormsg  = '';
                // Traitement du Min
                if (bIsValide && /(^|\s)min([0-9]+)($|\s)/.exec(nField.className)) {
                    var min = RegExp.$2;
                    if ($('#'+cLabels[i].htmlFor+' .ajaxItem').length < min) {
                        bIsValide = false;
                        for (var j=0; formCtrl.schemes[j]; j++) {
                            if(jQuery.inArray("min", formCtrl.schemes[j]) == 0) {
                                errormsg = formCtrl.schemes[j][1].replace(/[^\W]*%s[^\W]*/g,formCtrl.getClearedTextLabel(cLabels[i]))
                                errormsg = errormsg.replace(/[^\W]*%d[^\W]*/g, min);
                                break;
                            }
                        }
                    }
                }
                // Traitement du Max
                if (bIsValide && /(^|\s)max([0-9]+)($|\s)/.exec(nField.className)) {
                    var max = RegExp.$2;
                    if ($('#'+cLabels[i].htmlFor+' .ajaxItem').length > max) {
                        bIsValide = false;
                        for (var j=0; formCtrl.schemes[j]; j++) {
                            if(jQuery.inArray("max", formCtrl.schemes[j]) == 0) {
                                errormsg = formCtrl.schemes[j][1].replace(/[^\W]*%s[^\W]*/g,formCtrl.getClearedTextLabel(cLabels[i]))
                                errormsg = errormsg.replace(/[^\W]*%d[^\W]*/g, max);
                                break;
                            }
                        }

                    }
                }
                if (!bIsValide) {alert(errormsg);}
            }
            //*/
            if (bIsValide && formCtrl.util.hasClassName(cLabels[i],"extendedControl"))
                bIsValide = eval("extendedControl_"+cLabels[i].htmlFor+"(nField);");
            if (!bIsValide) {
                try { // MSIE fixe si nField[style=dysplay:none;]
                    // Gestion de la présence des éventuels onglets
                    var el = $('#' + cLabels[i].htmlFor);
                    var fieldsetElTab = el.parents('fieldset.tabContent');
                    if (el.is(":hidden") && fieldsetElTab && fieldsetElTab.data('id')) {
                        $('a[href=#'+fieldsetElTab.data('id')+ ']').click();
                    }
                    if (nField.focus)
                        nField.focus();
                    else if (nField.selected)
                        nField.selected();
                } catch(e) {}
            }
        }
        try {
            if (bIsValide && (typeof(eval("postControl_"+this.id))=="function"))
                bIsValide = eval("postControl_"+this.id+"(this);");
        } catch(err) {}
        if (!bIsValide) {
            if (evt && evt.preventDefault) {
                evt.stopPropagation();
                evt.preventDefault();
            } else if (window.event) {
                window.event.cancelBubble = true;
                window.event.returnValue = false;
            }
        }
        return bIsValide;
    },
    /*
     * Object contenant l'ensemble des fonctions devant être utilisées pour contrôler les données saisies par l'utilisateur
     */     
    fx: {
        trim: function(s) {return s.replace(/^\s+|\s+$/g,"");},
        isNotNull: function(s) {  
            if(typeof s == "string"){
                return this.trim(s)!="";
            }else{
                var cElmtsName, i ;
                var bReturn = false;
                var name = "";
                var parent = s.parentNode;              
                while(parent && !formCtrl.util.hasClassName(parent,"isNotNullContainer")){
                    parent = parent.parentNode;
                }
                if(!parent){
                    return false;
                }
                cLabel = parent.getElementsByTagName('Label');
                for(i = 0 ; i < cLabel.length, name == '' ; i++){
                    if(cLabel[i].htmlFor!='') name = document.getElementById(cLabel[i].htmlFor).name;
                }
                if(name==""){
                    alert("Aucun élément associé");
                    return false;
                }
                cElmtsName = document.getElementsByName(name);
                for(i = 0 ; i < cElmtsName.length ; i++){
                    if(cElmtsName[i].checked) bReturn = true;
                }
                return bReturn;
            }
        },
        isEmail: function(s) {
         if (this.isNotNull(s)) return /^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,10})+$/i.test(s); else return true;
        },
        isInt: function(s) {return this.isNotNull(s)?parseInt(s, 10)==s:true;},
        isFloat: function(s) {return this.isNotNull(s)?parseFloat(s)==s:true;}
    },
    /*
     * Objet contenant quelques fonctions utilisées régulièrement et développées dans un autre cadre
     */
    util: {
        /**
         * Ajout un gestionnaire d'événnement sur un noeud du DOM
         * @param   Node        EventTarget noeud sur lequel enregistrer le gestionnaire
         * @param   string      type        chaîne corresondant au type d'événement à gérer (load, submit, click, etc...)
         * @param   function    listener    Objet fonction correspondant au gestionnaire à déclencher
         * @param   bool        useCapture  Booleen présisant - pour le mobel standard - si le gestionnaire est capturant ou pas
         */
        addEventLst: function(EventTarget,type,listener,useCapture) {
            useCapture = typeof(useCapture)=="boolean"?useCapture:false;
            if (EventTarget.addEventListener) {
                EventTarget.addEventListener(type, listener, useCapture);
            } else if ((EventTarget==window) && document.addEventListener) {
                document.addEventListener(type, listener, useCapture);
            } else if (EventTarget.attachEvent) {
                EventTarget["e"+type+listener] = listener;
                EventTarget[type+listener] = function() {EventTarget["e"+type+listener]( window.event );}
                EventTarget.attachEvent("on"+type, EventTarget[type+listener]);
            }
        },
        /**
         * Retourne le contenu textuel d'une arborescence DOM de manière identique à ce que retourne la propriété "textContent" de l'interface Node du DOM.3-Core
         * @param  Node    oNode           Noeud à partir duquel retourner le contenu textuel
         * @return string  _textContent    Chaine représentant le contenu textuel du noeud source
         */
        getTextContent: function (oNode) {
            if (typeof(oNode.textContent)!="undefined") {return oNode.textContent;}
            switch (oNode.nodeType) {
                case 3: // TEXT_NODE
                case 4: // CDATA_SECTION_NODE
                    return oNode.nodeValue;
                    break;
                case 7: // PROCESSING_INSTRUCTION_NODE
                case 8: // COMMENT_NODE
                    
                    if (this.getTextContent.caller!=this.getTextContent) {
                        return oNode.nodeValue;
                    }
                    break;
                case 9: // DOCUMENT_NODE
                case 10: // DOCUMENT_TYPE_NODE
                case 12: // NOTATION_NODE
                    return null;
                    break;
            }
            var _textContent="";
            oNode=oNode.firstChild;
            while (oNode) {
                _textContent += this.getTextContent(oNode);
                oNode = oNode.nextSibling;
            }
            return _textContent;
        },
        /**
         * Détermine si une classe HTML est spécifiée sur un noeud de type élément 
         * @param  Node    oNode        Noeud à interroger
         * @param  string  className    Chaîne représentant la classe recherchée
         * @return bool    true si le noeud possède la classe, non dans les autres cas     
         */
        hasClassName: function(oNode,className) {
            return (oNode.nodeType==1)?((" "+oNode.className+" ").indexOf(" "+className+" ")!=-1):false;
        }
    }
}
// Au chargement de la page on tente de charger la fonctionnalité
formCtrl.util.addEventLst(window, "load", formCtrl.load);
