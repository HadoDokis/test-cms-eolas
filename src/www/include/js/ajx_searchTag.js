var ajaxSearchTag = {
    init: function() {
        $('input.searchTag')
            .attr('autocomplete', 'off')
            .autocomplete({
                source: SERVER_ROOT + 'include/ajax/ajax.searchTag.php',
                minLength: 3
            });
    }
}
$(ajaxSearchTag.init);