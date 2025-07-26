jQuery(document).ready(function($){
    $('.mark-review').on('click', function(e){
        e.preventDefault();
        var button = $(this);
        var empleadoId = button.data('empleado');
        var equipoId = button.data('equipo');

        var confirmDialog = $('<div class="confirm-review-dialog" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); background:#fff; border:4px solid #ccc; border-radius:16px; box-shadow:0 4px 12px rgba(0,0,0,0.15); padding:20px; z-index:10000; width:300px;">' +
            '<p style="margin:0 0 20px; font-size:1.1em; text-align:center;">¿Deseas enviar éste empleado a revisión?</p>' +
            '<div style="text-align:center;">' +
                '<a href="#" class="cancel-review" style="margin-right:15px; padding:8px 16px; background:#f5f5f5; border:1px solid #ccc; border-radius:4px; text-decoration:none; color:#333;">Cancelar</a>' +
                '<a href="#" class="confirm-review" style="padding:8px 16px; background:#404040; border:1px solid #404040; border-radius:4px; text-decoration:none; color:#fff;">Enviar</a>' +
            '</div>' +
        '</div>');

        $('body').append(confirmDialog);
        confirmDialog.fadeIn(200);

        confirmDialog.find('.cancel-review').on('click', function(e){
            e.preventDefault();
            confirmDialog.fadeOut(200, function(){ $(this).remove(); });
        });

        confirmDialog.find('.confirm-review').on('click', function(e){
            e.preventDefault();
            $.ajax({
                url: tabla_equipo.ajax_url,
                method: 'POST',
                dataType: 'json',
                data: {
                    action: 'mark_experience_review',
                    empleado_id: empleadoId,
                    equipo_id: equipoId
                },
                success: function(response){
                    if(response.success){
                        alert('Experiencia marcada para revisión.');
                        button.prop('disabled', true);
                    }else{
                        alert('Error: ' + response.data);
                    }
                    confirmDialog.fadeOut(200, function(){ $(this).remove(); });
                },
                error: function(xhr, status, error){
                    alert('Error en la solicitud: ' + error);
                    confirmDialog.fadeOut(200, function(){ $(this).remove(); });
                }
            });
        });
    });
});
