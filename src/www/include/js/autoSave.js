autoSave = {
	timeout : false,
	ed : null,
	ID_PARAGRAPHE : -1,
	ID_PAGE : -1,
	outputDiv : ' ',
	timeoutSave : 5000,
	saveInProgress : false,
	/**
	 * Si une sauvegarde existe, indiquer l'id de l'élément HTML qui contient le
	 * texte brouillon
	 */
	element : false,
	/**
	 * Messages
	 */
	ERR_DELETE : "Erreur lors de la suppression du paragraphe temporaire",
	ERR_UNDO : "Erreur undo temporaire",
	ERR_SAVE : "Erreur de sauvegarde Temp",
	MSG_REMPLACE : "La sauvegarde va être remplacée par le contenu actuel. Voulez vous continuer ?",
	MSG_SAVEEXIST : "Une sauvegarde existe pour ce paragraphe",
	MSG_ORIGINAL : "Original",
	MSG_BACKUP : "Sauvegarde",
	MSG_CREATE : "Sauvegarde créée le ",
	MSG_RETURN : "Attention vous allez perdre la sauvegarde",

	/**
	 * Supprime le paragraphe en cours
	 */
	deleteTempParagraphe : function() {
		$.post(SERVER_ROOT + 'cms/PRT/PRT_Submit.php', {
			deleteTmp : autoSave.ID_PARAGRAPHE
		}, function() {
			window.location.href = SERVER_ROOT + 'cms/cms_pseudo.php?PFM=1&idtf='
					+ autoSave.ID_PAGE;
		});
		return true;
	},

	/**
	 * Remet la sauvegarde a null
	 * 
	 * @param int
	 *            id Id du paragraphe pour laquelle il faut mettre la sauvegarde
	 *            à null
	 * @param int
	 *            ID_PAGE Id de la page vers laquelle rediriger après
	 */
	undoSave : function() {
		if (!autoSave.saveInProgress || confirm(autoSave.MSG_RETURN)) {
			$.post(SERVER_ROOT + 'cms/PRT/PRT_Submit.php', {
				undoSaveTmp : autoSave.ID_PARAGRAPHE
			}, function() {
				window.location.href = SERVER_ROOT + 'cms/cms_pseudo.php?PFM=1&idtf='
						+ autoSave.ID_PAGE;
			});
		}
		return true;
	},

	getCurrentDate : function() {
		var d = new Date();
		var sDate = ((d.getDate() < 10) ? '0' + d.getDate() : d.getDate())
				+ '/';
		sDate += ((d.getMonth() + 1 < 10) ? '0' + (d.getMonth() + 1) : d
				.getMonth() + 1)
				+ '/';
		sDate += d.getFullYear() + ' ';
		sDate += ((d.getHours() < 10) ? '0' + d.getHours() : d.getHours())
				+ ':';
		sDate += ((d.getMinutes() < 10) ? '0' + d.getMinutes() : d.getMinutes());
		return sDate;
	},

	/**
	 * Met à jour la sauvegarde
	 */
	saveTempParagraphe : function() {
		var data = {
			updateTmp : autoSave.ID_PARAGRAPHE,
			PAR_CONTENU : autoSave.ed.getContent()
		};
		$.post(SERVER_ROOT + 'cms/PRT/PRT_Submit.php', data, function() {
			if (autoSave.getOutputDiv()) {
				autoSave.getOutputDiv().innerHTML = autoSave.MSG_CREATE + ' '
						+ autoSave.getCurrentDate();
			}
			autoSave.saveInProgress = true;
		});
		if (autoSave.getOutputDiv()) {
			$('#outputDiv').removeClass('alert');
		}
	},

	/**
	 * gère la récurence de la sauvegarde
	 */
	manageAutoSave : function() {
		clearTimeout(autoSave.timeout);
		autoSave.timeout = setTimeout("autoSave.saveTempParagraphe()",
				autoSave.timeoutSave);
	},

	/**
	 * charge le contenu de la sauvegarde à l'interieur du tiny
	 */
	loadContent : function() {
		var tmp = autoSave.ed.getContent();
		var elem = document.getElementById(autoSave.element);
		autoSave.ed.setContent(autoSave.HTMLentitiesdecode(elem.value));
		elem.value = tmp;
	},

	/**
	 * Attends que l'éditeur soit chargé et initialise les variables
	 * 
	 * @param String
	 *            Id du tiny
	 * @param String
	 *            Id de la div dans laquelle s'affiche l'état de
	 *            l'enregistrement
	 */
	load : function(ID_TINY, ID_PARAGRAPHE, ID_PAGE) {
		if (tinyMCE.get(ID_TINY) != undefined) {
			autoSave.ed = tinyMCE.get(ID_TINY);
			autoSave.ID_PARAGRAPHE = ID_PARAGRAPHE;
			autoSave.ID_PAGE = ID_PAGE;
			autoSave.ed.onKeyUp.add(autoSave.manageAutoSave);
			if (autoSave.element) {
				autoSave.setSelect();
			}
		} else {
			setTimeout("autoSave.load('" + ID_TINY + "'," + ID_PARAGRAPHE + ","
					+ ID_PAGE + ");", 500);
		}
	},

	setSelect : function() {
		if (autoSave.getOutputDiv()) {
			$('#outputDiv').addClass('alert');
			autoSave.getOutputDiv().innerHTML = autoSave.MSG_SAVEEXIST
					+ ' <select id="select_save"><option value="O">'
					+ autoSave.MSG_ORIGINAL + '</option><option value="S">'
					+ autoSave.MSG_BACKUP + '</option></select>';
			document.getElementById('select_save').onchange = autoSave.loadContent;
		}
		autoSave.saveInProgress = true;
	},

	getOutputDiv : function() {
		if (autoSave.outputDiv != ' '
				&& typeof (autoSave.outputDiv).toString().toLowerCase() == 'string') {
			return document.getElementById(autoSave.outputDiv);
		} else {
			return false;
		}
	},

	/**
	 * equivalent html_entity_decode en php
	 * 
	 * @param String
	 *            le texte à convertir
	 * @return String Le texte converti
	 */
	HTMLentitiesdecode : function(texte) {

		texte = texte.replace(/&amp;/g, '&');// 38 26
		texte = texte.replace(/&quot;/g, '"');// 34 22
		texte = texte.replace(/&lt;/g, '<');// 60 3C
		texte = texte.replace(/&gt;/g, '>');// 62 3E
		texte = texte.replace(/&cent;/g, '\242');
		texte = texte.replace(/&pound;/g, '\243');
		texte = texte.replace(/&euro;/g, '\€');
		texte = texte.replace(/&yen;/g, '\245');
		texte = texte.replace(/&deg;/g, '\260');
		texte = texte.replace(/&OElig;/g, '\274');
		texte = texte.replace(/&oelig;/g, '\275');
		texte = texte.replace(/&Yuml;/g, '\276');
		texte = texte.replace(/&iexcl;/g, '\241');
		texte = texte.replace(/&laquo;/g, '\253');
		texte = texte.replace(/&raquo;/g, '\273');
		texte = texte.replace(/&iquest;/g, '\277');
		texte = texte.replace(/&Agrave;/g, '\300');
		texte = texte.replace(/&Aacute;/g, '\301');
		texte = texte.replace(/&Acirc;/g, '\302');
		texte = texte.replace(/&Atilde;/g, '\303');
		texte = texte.replace(/&Auml;/g, '\304');
		texte = texte.replace(/&Aring;/g, '\305');
		texte = texte.replace(/&AElig;/g, '\306');
		texte = texte.replace(/&Ccedil;/g, '\307');
		texte = texte.replace(/&Egrave;/g, '\310');
		texte = texte.replace(/&Eacute;/g, '\311');
		texte = texte.replace(/&Ecirc;/g, '\312');
		texte = texte.replace(/&Euml;/g, '\313');
		texte = texte.replace(/&Igrave;/g, '\314');
		texte = texte.replace(/&Iacute;/g, '\315');
		texte = texte.replace(/&Icirc;/g, '\316');
		texte = texte.replace(/&Iuml;/g, '\317');
		texte = texte.replace(/&ETH;/g, '\320');
		texte = texte.replace(/&Ntilde;/g, '\321');
		texte = texte.replace(/&Ograve;/g, '\322');
		texte = texte.replace(/&Oacute;/g, '\323');
		texte = texte.replace(/&Ocirc;/g, '\324');
		texte = texte.replace(/&Otilde;/g, '\325');
		texte = texte.replace(/&Ouml;/g, '\326');
		texte = texte.replace(/&Oslash;/g, '\330');
		texte = texte.replace(/&Ugrave;/g, '\331');
		texte = texte.replace(/&Uacute;/g, '\332');
		texte = texte.replace(/&Ucirc;/g, '\333');
		texte = texte.replace(/&Uuml;/g, '\334');
		texte = texte.replace(/&Yacute;/g, '\335');
		texte = texte.replace(/&THORN;/g, '\336');
		texte = texte.replace(/&szlig;/g, '\337');
		texte = texte.replace(/&agrave;/g, '\340');
		texte = texte.replace(/&aacute;/g, '\341');
		texte = texte.replace(/&acirc;/g, '\342');
		texte = texte.replace(/&atilde;/g, '\343');
		texte = texte.replace(/&auml;/g, '\344');
		texte = texte.replace(/&aring;/g, '\345');
		texte = texte.replace(/&aelig;/g, '\346');
		texte = texte.replace(/&ccedil;/g, '\347');
		texte = texte.replace(/&egrave;/g, '\350');
		texte = texte.replace(/&eacute;/g, '\351');
		texte = texte.replace(/&ecirc;/g, '\352');
		texte = texte.replace(/&euml;/g, '\353');
		texte = texte.replace(/&igrave;/g, '\354');
		texte = texte.replace(/&iacute;/g, '\355');
		texte = texte.replace(/&icirc;/g, '\356');
		texte = texte.replace(/&iuml;/g, '\357');
		texte = texte.replace(/&eth;/g, '\360');
		texte = texte.replace(/&ntilde;/g, '\361');
		texte = texte.replace(/&ograve;/g, '\362');
		texte = texte.replace(/&oacute;/g, '\363');
		texte = texte.replace(/&ocirc;/g, '\364');
		texte = texte.replace(/&otilde;/g, '\365');
		texte = texte.replace(/&ouml;/g, '\366');
		texte = texte.replace(/&oslash;/g, '\370');
		texte = texte.replace(/&ugrave;/g, '\371');
		texte = texte.replace(/&uacute;/g, '\372');
		texte = texte.replace(/&ucirc;/g, '\373');
		texte = texte.replace(/&uuml;/g, '\374');
		texte = texte.replace(/&yacute;/g, '\375');
		texte = texte.replace(/&thorn;/g, '\376');
		texte = texte.replace(/&yuml;/g, '\377');
		return texte;
	}
}