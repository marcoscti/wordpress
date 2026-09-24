(function($){
    $(function(){
        $(document).on('click', '.dj-view-pdf', function(e){
            e.preventDefault();
            var url = $(this).data('url');
            $('#dj-pdf-iframe').attr('src', url);
            $('#dj-pdf-modal').show();
        });
        $(document).on('click', '.dj-modal-close, .dj-modal-backdrop', function(e){
            e.preventDefault();
            $('#dj-pdf-iframe').attr('src', '');
            $('#dj-pdf-modal').hide();
        });
    });
})(jQuery);
