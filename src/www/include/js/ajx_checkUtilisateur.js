;(function ($) {
    "use strict";
    var CMSUtilisateur = {
        sUrl: SERVER_ROOT + 'include/ajax/ajax.checkUtilisateur.php',
        init: function () {
            $('#UTI_LOGIN').blur(CMSUtilisateur.checkLogin);
            $('#UTI_EMAIL').blur(CMSUtilisateur.checkEmail);
        },
        checkLogin: function () {
            var data = {
                CHECK: 'UTI_LOGIN',
                VALUE: $('#UTI_LOGIN').val()
            };
            if ($('#idtf').length > 0) {
                data.idtf = $('#idtf').val();
            }
            $.get(CMSUtilisateur.sUrl,
                data,
                function (returnedData) {
                    if (returnedData.trim() !== '') {
                        $('#UTI_LOGIN_result').text(returnedData);
                        $('#UTI_LOGIN_valid').val(0);
                    } else {
                        $('#UTI_LOGIN_result').text('');
                        $('#UTI_LOGIN_valid').val(1);
                    }
                });
        },
        checkEmail: function () {
            var data = {
                CHECK: 'UTI_EMAIL',
                VALUE: $('#UTI_EMAIL').val()
            };
            if ($('#idtf').length > 0) {
                data.idtf = $('#idtf').val();
            }
            $.get(CMSUtilisateur.sUrl,
                data,
                function (returnedData) {
                    if (returnedData.trim() !== '') {
                        $('#UTI_EMAIL_result').text(returnedData);
                        $('#UTI_EMAIL_valid').val(0);
                    } else {
                        $('#UTI_EMAIL_result').text('');
                        $('#UTI_EMAIL_valid').val(1);
                    }
                });
        }
    };
    $(document).ready(CMSUtilisateur.init);
}(jQuery));