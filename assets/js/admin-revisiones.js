jQuery(document).ready(function($){
    $('.delete-review').on('click', function(e){
        e.preventDefault();
        if(!confirm('¿Estás seguro de eliminar esta revisión?')){
            return;
        }
        var button = $(this);
        var revisionId = button.data('revision');

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'delete_experience_review',
                revision_id: revisionId,
                security: cdb_revisiones.nonce
            },
            success: function(response){
                if(response.success){
                    $('#revision-' + revisionId).fadeOut(300, function(){ $(this).remove(); });
                }else{
                    alert('Error: ' + response.data);
                }
            },
            error: function(xhr, status, error){
                alert('Error en la solicitud: ' + error);
            }
        });
    });
});
