/**
 * @autor: davidt
 * @public: multiUpload.settings
 * @requires: plupload.full.js
 *
 * Gestion du multiupload avec plupload
 */
;var multiUpload = (function($) {
    "use strict";
    var publicStuff = {}, multiUpload = {
        uploader: null,
        aFileInfo: [],
        settings: {
            selectors : {
                dropzone: '#dropzone',
                filelist: '#filelist',
                validateBtn: '#uploadfiles',
                reset: '#resetfiles'
            },
            largeurVignette: 100,
            url: 'web_importMultipleSubmit.php',
            max_file_size: '2Mb',
            thumbPrefix: 'THUMB_',
            wbt_code: 'WBT_IMAGE',
            defaultDocPicto: 'unknownformat-thumb.png',
            errors: {
                uploadExtention: 'Ce type de fichier n\'est pas supporté',
                uploadSize: 'Ce fichier dépasse la taille maximum authorisé'
            },
            type: {
                libelle: 'Image files',
                extentions: 'gif,jpg,jpeg,bmp,png'
            },
            docPictos: {
                'doc': 'icone-docx-thumb.png',
                'docx': 'icone-docx-thumb.png',
                'pdf': 'icone-pdf-thumb.png',
                'ppt': 'icone-ppt-thumb.png',
                'rtf': 'icone-rtf-thumb.png',
                'txt': 'icone-txt-thumb.png',
                'xls': 'icone-xls-thumb.png',
                'xlsx': 'icone-xls-thumb.png'
            }
        },
        utils: {
            reduceToISO646: function (fileName) {
                var escPattern = [], key;
                escPattern['A'] = /[\u00C0-\u00C5]/;
                escPattern['AE'] = /\u00C6/;
                escPattern['C'] = /\u00C7/;
                escPattern['D'] = /\u00D0/;
                escPattern['E'] = /[\u00C8-\u00CB]/;
                escPattern['I'] = /[\u00CC-\u00CF]/;
                escPattern['N'] = /\u00D1/;
                escPattern['O'] = /[\u00D2-\u00D6\u00D8]/;
                escPattern['OE'] = /[\u0152]/;
                escPattern['S'] = /\u0160/;
                escPattern['U'] = /[\u00D9-\u00DC]/;
                escPattern['Y'] = /\u00DD/;
                escPattern['Z'] = /\u017D/;

                escPattern['a'] = /[\u00E0-\u00E5]/;
                escPattern['ae'] = /\u00E6/;
                escPattern['c'] = /\u00E7/;
                escPattern['d'] = /\u00F0/;
                escPattern['e'] = /[\u00E8-\u00EB]/;
                escPattern['i'] = /[\u00EC-\u00EF]/;
                escPattern['n'] = /\u00F1/;
                escPattern['o'] = /[\u00F2-\u00F6\u00F8]/;
                escPattern['oe'] = /\u0153/;
                escPattern['s'] = /\u0161/;
                escPattern['u'] = /[\u00F9-\u00FC]/;
                escPattern['y'] = /\u00FD\u00FF/;
                escPattern['z'] = /\u017E/;

                escPattern['ss'] = /\u00DF/;

                for (key in escPattern) {
                    fileName = fileName.replace(escPattern[key], key);
                }

                fileName = fileName.replace(/[^\u0000-\u007F]/g, '-');
                fileName = fileName.replace(/-+/g, '-');
                return fileName;
            },
            safeFromRfc1738: function (fileName) {
                var gendelims = [":", "/", "?", "#", "[", "]","!", "$", "&", "'", "(", ")", "*", "+", ",", ";", "="],
                unsafe = [/\s+/, "<", ">", "\"", "#", "%", "{", "}", "|", "\\", "^", "~", "[", "]", "`", "'"],
                i;
                for(i = 0 ; i < gendelims.length ; i += 1){
                    fileName = fileName.replace(gendelims[i], '-');
                }
                fileName = fileName.replace(/\s+/g, '-');
                for(i = 0 ; i < unsafe.length ; i += 1){
                    fileName = fileName.replace(unsafe[i], '-');
                }
                fileName = fileName.replace(/-+/g, '-');
                return fileName;
            },
            filenameToRfc1738: function (str) {
                str = multiUpload.utils.reduceToISO646(str);
                str = multiUpload.utils.safeFromRfc1738(str);
                return str;
            }
        },
        init: function () {
            var $fileListTableHtml;
            multiUpload.uploader = new plupload.Uploader({
                runtimes : 'gears,html5,flash,silverlight',
                browse_button : 'pickfiles',
                container: 'uploadContainer',
                drop_element: multiUpload.settings.selectors.dropzone.replace('#', ''),
                max_file_size: multiUpload.settings.max_file_size,
                url : multiUpload.settings.url,
                flash_swf_url : SERVER_ROOT + 'include/js/plupload/js/plupload.flash.swf',
                silverlight_xap_url : SERVER_ROOT + 'include/js/plupload/js/plupload.silverlight.xap',
                filters : [
                    {title: multiUpload.settings.type.libelle, extensions: multiUpload.settings.type.extentions}
                ]
            });

            multiUpload.uploader.bind('Init', function(up, params) {
                if(!up.features.dragdrop){
                    $(multiUpload.settings.selectors.dropzone).parents('tr').hide();
                }
                $(multiUpload.settings.selectors.validateBtn + ', ' + multiUpload.settings.selectors.reset).hide();
                $(multiUpload.settings.selectors.filelist).html('');
            });

            multiUpload.uploader.bind('FilesAdded', multiUpload.filesAdded);

            multiUpload.uploader.bind('UploadComplete', multiUpload.uploadComplete);

            multiUpload.uploader.bind('UploadProgress', multiUpload.uploadProgress);

            multiUpload.uploader.bind('Error', multiUpload.error);

            multiUpload.uploader.init();

            $('#uploadContainer').on('click', '.deleteFile', multiUpload.removeFile);

            $('#uploadContainer').on('click', '.validateFile', multiUpload.validateFile);

            $(multiUpload.settings.selectors.reset).bind('click', multiUpload.removeAllFiles);

            $(multiUpload.settings.selectors.validateBtn).bind('click', multiUpload.validateFiles);

            $(window).unload(multiUpload.cleanUp);
        },

        /**
         * Callback à l'ajout de fichiers. Genère les blocks et les liens de validation + suppression
         * @param up Uploader l'élément uploader
         * @param files Array contenant les infos sur les fichiers uplaodés
         */
        filesAdded: function (up, files) {
            multiUpload.lockInterface();
            multiUpload.uploader.bind('QueueChanged', multiUpload.queueChanged);
            var i, CAT_LIBELLE, ID_WEBOTHEQUECATEGORIE, dossierListeHtml, dossierLibelleHtml, fileListHtml, $fileListHtml,
            fadeAllIn = !$(multiUpload.settings.selectors.filelist +' .liste:first').is(':visible');
            $(multiUpload.settings.selectors.validateBtn + ', ' + multiUpload.settings.selectors.reset).show();
            for(i = 0 ; i < files.length ; i++){
                CAT_LIBELLE = $('#CAT_LIBELLE').val();
                ID_WEBOTHEQUECATEGORIE = $('#ID_WEBOTHEQUECATEGORIE').val();
                dossierListeHtml = $('<div>').append($('#ID_WEBOTHEQUECATEGORIE').clone(true).addClass('ID_WEBOTHEQUECATEGORIE').attr('id', 'cat_' + files[i].id).val($('#ID_WEBOTHEQUECATEGORIE').val())).html();
                dossierLibelleHtml = $('<div>').append($('#CAT_LIBELLE').clone(true).addClass('CAT_LIBELLE').attr('id', 'catLib_' + files[i].id)).html();

                fileListHtml = '';
                fileListHtml += '<div class="imageContainer" id="' + files[i].id + '" data-file-name="' + files[i].name + '">';
                fileListHtml += '<a href="#" class="deleteFile">';
                fileListHtml += '<img src="' + SERVER_ROOT + 'images/action_delete.png" alt="">';
                fileListHtml += '</a>';
                fileListHtml += '<a href="#" class="validateFile">';
                fileListHtml += '<img src="' + SERVER_ROOT + 'images/action_validate.png" alt="">';
                fileListHtml += '</a>';
                fileListHtml += '<div class="imgContainer"><img src="' + SERVER_ROOT + 'images/loading.gif" alt="" class="apercu loaderImg"></div>';
                fileListHtml += '<p><input type="text" class="fileLibelle" name="fileLibelle" value="' + files[i].name.replace(/\.[a-z]+$/, '') + '"></p>';
                fileListHtml += '<p class="cat">' + dossierListeHtml + ' / ' + dossierLibelleHtml + '</p>';
                fileListHtml += '<p class="returnMsg"></p>';
                fileListHtml += '</div>';

                $fileListHtml = $(fileListHtml).hide();
                $('.ID_WEBOTHEQUECATEGORIE', $fileListHtml).val(ID_WEBOTHEQUECATEGORIE);
                $('.CAT_LIBELLE', $fileListHtml).val(CAT_LIBELLE);
                $('.returnMsg', $fileListHtml).hide();

                $(multiUpload.settings.selectors.filelist).append($fileListHtml);
                $fileListHtml.fadeIn();
            }

            return true;
        },

        /**
         * Callback lancé lorsqu'un fichier à été ajouté ou enlevé à la pile. Lance l'upload tout de suite pour générer les vignettes
         * @param up Uploader l'élément uploader
         * @param files Array contenant les infos sur les fichiers uplaodés
         */
        queueChanged: function (up) {
            multiUpload.uploader.unbind('QueueChanged', multiUpload.queueChanged);
            multiUpload.uploader.start();
        },

        /**
         * Callback lancé à la fin de l'upload
         * @param up Uploader l'élément uploader
         * @param files Array contenant les infos sur les fichiers uplaodés
         */
        uploadProgress: function (up, file) {/*
            $('#avancement_' + file.id).val(file.percent);
            $('b', $('#avancement_' + file.id).parent()).text(file.percent + '%');*/
        },

        /**
         * Callback lancé à la fin de l'upload
         * @param up Uploader l'élément uploader
         * @param files Array contenant les infos sur les fichiers uplaodés
         */
        uploadComplete: function (up, files) {
            var imgSrc = '', i, typeExt, docExt;
            for (i = 0 ; i < files.length ; i += 1) {
                if (multiUpload.settings.wbt_code === 'WBT_IMAGE') {
                    imgSrc = SERVER_ROOT + 'uploads/' + multiUpload.settings.thumbPrefix + multiUpload.utils.filenameToRfc1738(files[i].name);
                    $('#' + files[i].id + ' .apercu')
                        .width(multiUpload.settings.largeurVignette);
                } else {
                    docExt = files[i].name.replace(/.*\.([a-zA-Z0-9]+)$/, '$1');
                    imgSrc = multiUpload.settings.defaultDocPicto;
                    typeExt = typeof multiUpload.settings.docPictos[docExt];
                    if(typeExt !== 'undefined'){
                        imgSrc = multiUpload.settings.docPictos[docExt]
                    }
                    imgSrc = SERVER_ROOT + 'images/' + imgSrc;
                }
                multiUpload.aFileInfo[files[i].id] = files[i];
                $('#' + files[i].id + ' .apercu')
                    .attr('src', imgSrc)
                    .removeClass('loaderImg');
            }
            multiUpload.freeInterface();
        },

        /**
         * Callback lancé au click sur le bouton supprimer
         * @param e Event L'événement déclancé
         */
        removeFile: function (e) {
            var data = { deleteFile: $(this).parents('.imageContainer').data('file-name') },
            typeE = typeof e;
            if(typeE !== 'undefined'){
                e.preventDefault();
            }
            $.ajax({
                url: multiUpload.settings.url,
                data: data,
                context: this,
                success: function (data){
                    $(this)
                        .parents('.imageContainer')
                        .fadeOut('fast', function () { $(this).remove(); });
                    multiUpload.uploader.removeFile(multiUpload.uploader.getFile($(this).parents('.imageContainer').attr('id')));
                }
            });
        },

        /**
         * Supprime tous les fichiers en cours d'ulpoad. lance le click sur les liens de suppression
         */
        removeAllFiles: function(){
            $('.deleteFile').each(function(){
                $(this).trigger('click');
            });
        },

        /**
         * supprimer tous les fichiers uploadés non validés sur le serveur, appelé sur le unload du window
         */
        cleanUp: function (e) {
            e.preventDefault();
            var data = { cleanUp: 1, files: [] };
            $('.deleteFile').each(function () {
                data.files.push($(this).parents('.imageContainer').data('file-name'));
            });
            $.ajax({
                url: multiUpload.settings.url,
                data: data
            });
        },

        /**
         * Valide le ficher, envoi un appel ajax et gère les retours d'erreurs
         */
        validateFile: function (e) {
            var typeE = typeof e,
            data = {};
            if (typeE !== 'undefined'){
                e.preventDefault();
            }
            data.validateFile = 1;
            data.fileName = $(this).parents('.imageContainer').data('file-name');
            data.fileLibelle = $('.fileLibelle', $(this).parents('.imageContainer')).val();
            data.idWebothequeCat = $('.ID_WEBOTHEQUECATEGORIE', $(this).parents('.imageContainer')).val();
            data.catLibelle = $('.CAT_LIBELLE', $(this).parents('.imageContainer')).val();
            data.wbt_code = multiUpload.settings.wbt_code;

            $.ajax({
                url: multiUpload.settings.url,
                data: data,
                context: $(this).parents('.imageContainer'),
                dataType: 'json',
                success: function (jsonData){
                    var text = '';
                    if(jsonData['success']) {
                        text = 'Le fichier a été ajouté à la webothèque';
                        $('.returnMsg').removeClass('alert');
                    } else {
                        text = 'Erreur : ' + jsonData.erreur;
                        $('.returnMsg').addClass('alert');
                    }
                    $('.returnMsg', this)
                        .text(text)
                        .fadeIn('false',
                            function(){
                                //S'il n'y a pas d'erreur
                                if (!$(this).hasClass('alert')){
                                    setTimeout($.proxy(function(){
                                        $(this).fadeOut('fast', function(){
                                            $(this).parents('.imageContainer').fadeOut('fast', function(){ $(this).remove(); });
                                        });
                                    }, this), 2000);
                                } else {
                                    //Sinon on empêche de ressoummettre l'image
                                    $('.validateFile', $(this).parents('.imageContainer')).remove();
                                }
                            });
                }
            });
        },

        /**
         * Valide tous les fichiers, déclanche l'événement click sur tous les liens de validation
         */
        validateFiles: function (e) {
            $('.validateFile').trigger('click');
        },

        error: function (up, err) {
            var errorMsg = '';
            switch (err.code) {
                case  plupload.FILE_EXTENSION_ERROR:
                    errorMsg = multiUpload.settings.errors.uploadExtention;
                    break;
                case  plupload.FILE_SIZE_ERROR:
                    errorMsg = multiUpload.settings.errors.uploadSize;
                    break;
            }
            $('#' + err.file.id + ' .loaderImg').remove();
            $('#' + err.file.id + ' .validateFile').remove();
            $('#' + err.file.id + ' .returnMsg').addClass('alert').text(errorMsg).fadeIn();
            return false;
        },

        lockInterface: function () {
            $(multiUpload.settings.selectors.validateBtn).prop('disabled', true);
            $(multiUpload.settings.selectors.reset).prop('disabled', true);
        },

        freeInterface: function () {
            $(multiUpload.settings.selectors.validateBtn).prop('disabled', false);
            $(multiUpload.settings.selectors.reset).prop('disabled', false);
        }
    };

    publicStuff.settings = multiUpload.settings;

    $(document).ready(multiUpload.init);

    return publicStuff;
})(jQuery);