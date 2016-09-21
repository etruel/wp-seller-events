jQuery(document).ready(function($){
    $('.inline-edit-save.submit .save').live('click', function(){
        
    });
    $('#the-list').on('click', 'a.editinline', function(){
        var tag_id = $(this).parents('tr').attr('id');
        var qty = $('.column-quantity', '#'+tag_id).text();
        //var qty = $(this).closest('tr').find('td.column-quantity').text();
        $('.inline-edit-col :input[name="term_meta[quantity]"]').val(qty);
        var per = $('.column-period', '#'+tag_id).text();
        //var per = $(this).closest('tr').find('td.column-period').text();
        $('.inline-edit-col select[name="term_meta[period]"] > option').prop("selected", false);
        $('.inline-edit-col select[name="term_meta[period]"]').children('option[value="' + per +'"]').attr("selected", "selected"); 
        $('.inline-edit-col select[name="term_meta[period]"] > option[value="' + per +'"]').prop("selected", true); 

        // hide slug
        $('.inline-edit-col :input[name="slug"]').closest('label').hide();
        // slug = name (wp zanitize later)
        $('.inline-edit-col :input[name="slug"]').val( $('.inline-edit-col :input[name="name"]').val() );

        return false;
    });
    
//        $('.inline-edit-col select[name="term_meta[period]"] > option[value="' + per +'"]').attr("selected", "selected");


});