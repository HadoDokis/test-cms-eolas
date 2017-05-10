/* Scripts de la barre de debug */

(function(){
    var debug = {
        init: function(){
            $('#cmsCloseDebug').toggle(function(){ $('#debugInfos').fadeOut(); $('#cmsCloseDebug').text('Ouvrir'); }, function(){ $('#debugInfos').fadeIn(); $('#cmsCloseDebug').text('Fermer');});
            $('#debugRequests')
                .height(0)
                .hide();
            var sUrl = SERVER_ROOT + 'include/ajax/ajax.debug.php';
            $('#debugRequests').load(sUrl);
            $('#cmsToggleRequests').toggle(
                function(){
                	$('#debugRequests')
                    .show()
                    .animate(
                        {height: 500},
                        500);
                    /*var sUrl = SERVER_ROOT + 'include/ajax/ajax.debug.php';
                    $('#debugRequests').load(sUrl, function() {
                        $('#debugRequests')
                            .show()
                            .animate(
                                {height: 500},
                                500);
                    });*/
                },
                function(){
                    $('#debugRequests').animate(
                        {height: 0},
                        500,
                        function(){ $(this).hide(); });
                }
            );
            delete debug.init;
        }
    };
    $(document).ready(debug.init);
})();