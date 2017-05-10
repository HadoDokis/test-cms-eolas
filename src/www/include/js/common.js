function selectAll(id) {
	var obj = document.getElementById(id);
	if (obj) for (var i=0; obj.options[i]; i++) obj.options[i].selected = true;
}

/***********/
/*  COOKIE */
/***********/
function createCookie(name, value, days) {
	if (days) {
		var date = new Date();
		date.setTime(date.getTime()+(days*24*60*60*1000));
		var expires = "; expires="+date.toGMTString();
	}
	else expires = "";
	document.cookie = name+"="+value+expires+"; path=/";
}
function readCookie(name) {
	var nameEQ = name + "=";
	var ca = document.cookie.split(';');
	for(var i=0; i < ca.length; i++) {
		var c = ca[i];
		while (c.charAt(0)==' ') c = c.substring(1, c.length);
		if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
	}
	return null;
}

/********************/
/*  DEPLACE CRITERE */
/********************/
function isChildNodeOf(oNode,other) {
	if (oNode.compareDocumentPosition) {
		return (oNode.compareDocumentPosition(other)==10);
	} else if (other.contains) {
		return other.contains(oNode);
	}
	var bIsChildNodeOf = false;
	function _isChildNodeOf(oNode,other) {
		while (other) {
			if (other==oNode) {
				bIsChildNodeOf = true;
				return;
			} else _isChildNodeOf(oNode,other.firstChild);
			other = other.nextSibling;
		}
	}
	_isChildNodeOf(oNode,other.firstChild);
	return bIsChildNodeOf;
}
function cloneOptgroup(elOptgroup, elSelect, elStart) {
	var oNode, compare, elNewOptgroup;
	if (elStart) {
		oNode = (elStart.parentNode.nodeName.toLowerCase()=='optgroup')?
								elStart.parentNode:elStart;
	} else {
		oNode = elSelect.firstChild;
	}
	while (oNode) {
		if ( (oNode.nodeName.toLowerCase()!='optgroup')
					&& (oNode.nodeName.toLowerCase()!='option') )
		{
			oNode = oNode.nextSibling;
			continue;
		}
		compare=(oNode.nodeName.toLowerCase()=='option')?oNode.text:oNode.label;
		if (elOptgroup.label == compare) {
			elNewOptgroup = oNode;
			break;
		} else if (elOptgroup.label < compare) {
			elNewOptgroup = elOptgroup.cloneNode(false);
			oNode.parentNode.insertBefore(elNewOptgroup,oNode);
			break;
		}
		oNode = oNode.nextSibling;
	}
	if (!elNewOptgroup) {
		elNewOptgroup = elOptgroup.cloneNode(false);
		elSelect.appendChild(elNewOptgroup);
	}
	return elNewOptgroup;
}
function DeplaceCritere(elFromSelect, elToSelect) {
	function _fixe_msieIndexOption(elOption) {
		var optionIndex, oNode;
		optionIndex=0;
		if (elOption.parentNode.nodeName.toLowerCase()=='optgroup') {
			oNode = elOption.previousSibling;
			while (oNode) {
				if (oNode.nodeName.toLowerCase()!="option") {
					oNode = oNode.previousSibling;
					continue;
				}
				break;
			}
			if (!oNode || (oNode.nodeName.toLowerCase()!="option")) {
				oNode = elOption.parentNode.previousSibling;
			}
		} else {oNode = elOption.previousSibling;}
		while (oNode) {
			if ( (oNode.nodeName.toLowerCase()!='optgroup')
						&& (oNode.nodeName.toLowerCase()!='option') )
			{
				oNode = oNode.previousSibling;
				continue;
			}
			if (oNode.nodeName.toLowerCase()=='option') {
				optionIndex = oNode.index+1;
				break;
			} else {
				oNode = oNode.lastChild;
				while (oNode) {
					if (oNode.nodeName.toLowerCase()=='option') {
						optionIndex = oNode.index+1;
						break;
					}
					oNode = oNode.previousSibling;
				}
				break;
			}
		}
		return optionIndex;
	}
	var fromIndex, toIndex, elOption, nToParent, nFromParent, oNode;
	fromIndex = toIndex = 0;
	elOption = elFromSelect.options[0];
	fromOptions :
	while (elOption) {
		if (!elOption.selected) {
			elOption = elFromSelect.options[elOption.index+1];
			continue;
		}
		nToParent = elToSelect;
		nFromParent = elOption.parentNode;
		oNode = elToSelect.options[toIndex];
		if (nFromParent.nodeName.toLowerCase()=='optgroup') {
			nToParent = cloneOptgroup(nFromParent, elToSelect,elToSelect.options[toIndex]);
			if (oNode && !isChildNodeOf(oNode,nToParent)) {
				oNode = nToParent.firstChild;
			}
		} else {
			if ( oNode && (oNode.parentNode.nodeName.toLowerCase()=='optgroup') ) {
				oNode = oNode.parentNode;
			}
		}
		var compare;
		while (oNode) {
			if ( (oNode.nodeName.toLowerCase()!='optgroup')
						&& (oNode.nodeName.toLowerCase()!='option') )
			{
				oNode = oNode.nextSibling;
				continue;
			}
			compare=(oNode.nodeName.toLowerCase()=='option')?oNode.text:oNode.label;
			if (elOption.text < compare) {
				fromIndex = elOption.index;
				nToParent.insertBefore(elOption, oNode);
				toIndex = elOption.index;
				if (toIndex > elToSelect.options.length) {
					toIndex = _fixe_msieIndexOption(elOption);
				}
				elOption = elFromSelect.options[fromIndex];
				continue fromOptions;
			}
			oNode = oNode.nextSibling;
		}
		fromIndex = elOption.index;
		nToParent.appendChild(elOption);
		toIndex = elOption.index;
		if (toIndex > elToSelect.options.length) {
			toIndex = _fixe_msieIndexOption(elOption);
		}
		elOption = elFromSelect.options[fromIndex];
	}
	var cFromOptgroup = elFromSelect.getElementsByTagName('optgroup');
	var aFromOptgroupRemove = new Array();
	for (var k=0;cFromOptgroup[k]; k++) {
		if (cFromOptgroup[k].getElementsByTagName('option').length==0) {
			aFromOptgroupRemove.push(cFromOptgroup[k]);
		}
	}
	for (var l=0; aFromOptgroupRemove[l]; l++) {
		aFromOptgroupRemove[l].parentNode.removeChild(aFromOptgroupRemove[l]);
	}
}

/*********/
/* COVER */
/*********/
var popupCover = null;
var timeoutCover = null;
function ownWindowOpen(url, name, feature) {
	showCover();
	if (feature) {
		if (feature.indexOf('resizable') == -1) feature += ",resizable";
		if (feature.indexOf('scrollbars') == -1) feature += ",scrollbars";
		if (feature.indexOf('modal') == -1) feature += ",modal";
	} else {
		feature = "resizable,scrollbars,modal";
	}
	if (!name) name = 'POPUP';
	popupCover = window.open(url, name, feature);
	timeoutCover = window.setInterval('intervalCover()', 500);
	return false;
}
function intervalCover() {
	if (!popupCover || popupCover.closed) {
		window.clearInterval(timeoutCover);
		hideCover();
	}
}
function coverFocus(evt) {
    evt.preventDefault();
    if (popupCover) popupCover.focus();
}
function showCover() {
    $('body').css("opacity", "0.5");
    document.body.style.backgroundColor = "#ddd";
    document.body.style.cursor = "wait";
    $(document).bind('click', coverFocus);
}
function hideCover() {
        $('body').css("opacity", "1");
	document.body.style.backgroundColor = "";
	document.body.style.cursor = "";
        $(document).unbind('click', coverFocus);
}

/***************************************************************************************************************/
/* Fonction pour retirer les listener (Utilisé pour les popups tiny ou tiny et formControl entrent en conflit) */
/***************************************************************************************************************/
function removeEventLst (EventTarget,type,listener,useCapture) {
	useCapture = typeof(useCapture)=="boolean"?useCapture:false;
	if (EventTarget.removeEventListener)
		EventTarget.removeEventListener(type, listener, useCapture);
	else if ((EventTarget==window) && document.removeEventListener)
		document.removeEventListener(type, listener, useCapture);
	else if (EventTarget.detachEvent)
		EventTarget.detachEvent("on"+type,listener);
	else
		EventTarget["on"+type]=null;
}
