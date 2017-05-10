var ajaxLiaisonWeboModule = {
    init: function(idtf) {
        $('li.expand').click(function () {
            if ($(this).find('ul').length > 0) {
                $(this).find('ul').remove();
                $(this).removeClass('expandClose');
            } else {
                $li = $(this);
                $.get("/include/ajax/ajax.webothequeObjetsLies.php?idtf=" + idtf + "&LIA_CODE=" + $(this).data('liaison'), function(data) {
                    $li.html($li.html() + data)
                       .addClass('expandClose');
                });
            }
        });
    }
}