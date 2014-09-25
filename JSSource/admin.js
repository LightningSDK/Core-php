function add_link(link){
    new_link = $('#'+link+'_list').val();
    new_link_name = $('#'+link+'_list option:selected').text();
    remove_link(link,$('#'+link+'_list').val())
    $('#'+link+'_input_array').val($('#'+link+'_input_array').val()+new_link+",");
    $('#'+link+'_list_container').append($("<div class='"+link+"_box' id='"+link+"_box_"+new_link+"'>"+new_link_name+" <a href='#' onclick='javascript:remove_link(\""+link+"\","+new_link+");return false;'>X</a></div>"));
}

function remove_link(link,link_id){
    $('#'+link+'_box_'+link_id).remove();
    var new_links = $('#'+link+'_input_array').val();
    var regex = new RegExp("[\\^,]"+link_id+",", "i");
    new_links = new_links.replace(regex,",");
    regex = new RegExp("^"+link_id+",", "i");
    new_links = new_links.replace(regex,"");
    $('#'+link+'_input_array').val(new_links);
}


function cms_edit_content(container){
    $('#content_block_'+container).hide();
    $('#edit_content_block_'+container).show();
}

function cms_save_content(container){
    $.ajax({
        url:'/cms.php',
        data:{action:'update_content','container':container,content:CKEDITOR.instances["cke_"+container].getData()},
        type:"POST",
        dataType:"html",
        success:function(){
            $('#content_body_'+container).html(CKEDITOR.instances["cke_"+container].getData());
            $('#content_block_'+container).show();
            $('#edit_content_block_'+container).hide();
        }
    });
}
