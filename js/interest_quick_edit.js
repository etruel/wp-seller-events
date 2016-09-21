jQuery(document).ready(function($){
    $('.inline-edit-save.submit .save').live('click', function(){
        
    });
    $('#the-list').on('click', 'a.editinline', function(){
        var tag_id = $(this).parents('tr').attr('id');
        // hide slug
        $('.inline-edit-col :input[name="slug"]').closest('label').hide();
        // slug = name (wp zanitize later)
        $('.inline-edit-col :input[name="slug"]').val( $('.inline-edit-col :input[name="name"]').val() );

        return false;
    });

});