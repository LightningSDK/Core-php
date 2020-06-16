/* TREE EVENTS */
function tree_clear(click_col) {
    var clear=false;
    $(".tree_column").each(function() {
        if (clear)
            $(this).remove();
        if ($(this).attr("id").replace("tree_column_","") == click_col)
            clear = true;
    });
}

function tree_sub_click() {
    var click_col = $(this).closest(".tree_column").attr("id").replace("tree_column_","");
    // clear unassociated columns
    tree_clear(click_col);
    // set as selected
    $('#tree_column_'+click_col+" .selected").removeClass('selected');
    $(this).addClass('selected');
    if ($(this).hasClass("tree_column_directory")) {
        // load directory
        var click_id = $(this).attr("id").replace("tree_node_","");
        $.ajax({
            url:tree_data.action_file,
            type:"POST",
            dataType:"json",
            data:{action:'load_node',node_id:click_id},
            success: function (data) {
                if (data.status === "error") {
                    alert(data.error);
                } else {
                    $('#tree_'+tree_data.tree_name).append("<div class='tree_column' id='tree_column_"+data.node_id+"'></div>");
                    $("#tree_column_"+data.node_id).append(tree_node_header(data));
                    for(i in data.nodes)
                        $("#tree_column_"+data.node_id).append(tree_node_string(data.nodes[i].node_name,data.nodes[i].node_id));
                    for(i in data.items)
                        $("#tree_column_"+data.node_id).append("<div class='tree_column_row tree_column_item' id='tree_obj_"+data.items[i].item_id+"'>"+data.items[i].item_title+"</div>");

                    /* 				$("#tree_column_"+data.node_id).append("<div class='tree_column_row tree_column_item'><a href=\"#\">test<a/></div>"); */

                    $("#tree_column_"+data.node_id+" .tree_column_row").on('click', tree_sub_click);
                    var container_width = $('#tree_'+tree_data.tree_name).find('.tree_column').length * ($('#tree_'+tree_data.tree_name).find('.tree_column').first().width()+1);//+1 for border
                    $('#tree_'+tree_data.tree_name).css('width',container_width);
                    $('#tree_'+tree_data.tree_name).parent().scrollLeft($('#tree_'+tree_data.tree_name).width() - $('#tree_'+tree_data.tree_name).parent().width());
                }
            }
        });
    } else {
        // load item description
        var click_id = $(this).attr("id").replace("tree_obj_","");
        $.ajax({
            url:tree_data.action_file,
            type:"POST",
            dataType:"json",
            data:{action:'obj_detail',id:click_id},
            success: function (data) {
                $('#tree_'+tree_data.tree_name).append("<div class='tree_column tree_column_obj' id='tree_column_obj_"+data.id+"'>"+data.html+"</div>");
                var container_width = $('#tree_'+tree_data.tree_name).find('.tree_column').length * ($('#tree_'+tree_data.tree_name).find('.tree_column').first().width()+1);//+1 for border
                $('#tree_'+tree_data.tree_name).css('width',container_width);
                $('#tree_'+tree_data.tree_name).parent().scrollLeft($('#tree_'+tree_data.tree_name).width() - $('#tree_'+tree_data.tree_name).parent().width());
            }
        });
    }

}

function tree_add(obj) {
    var new_name = prompt("Enter a name for the new directory:");
    var col = $(obj).closest('.tree_column');
    if (new_name != null) {
        $.ajax({
            url:tree_data.action_file,
            type:"POST",
            dataType:"json",
            data:{action:'add_node','node_id':col.attr('id').replace('tree_column_',''),name:new_name},
            success: function(data) {
                if (data.status=='ok') {
                    var inserted = false;
                    col.find('.tree_column_row').each(function() {
                        if ($(this).html().replace(/\<span\>.*\<\/span\>/,'').toLowerCase() > data.name.toLowerCase()) {
                            $(this).before(tree_node_string(data.name,data.node_id));
                            $('#tree_node_'+data.node_id).on('click', tree_sub_click);
                            inserted = true;
                            return false;
                        }
                    });
                    if (!inserted) {
                        $(obj).closest('.tree_column').append(tree_node_string(data.name,data.node_id));
                        $('#tree_node_'+data.node_id).on('click', tree_sub_click);
                    }
                }
            }
        });
    }
}

function tree_delete(obj) {
    var node_id = $(obj).closest('.tree_column').attr('id').replace('tree_column_','');
    var prev_name = $('#tree_node_'+node_id).html().replace(/\<span\>.*\<\/span\>/,'');
    var conf = confirm("Are you sure you want to delete the node '"+prev_name+"' ?");
    if (conf==true) {
        $.ajax({
            url:tree_data.action_file,
            type:"POST",
            dataType:"json",
            data:{action:'remove_node','node_id':node_id},
            success: function(data) {
                if (data.status=='ok') {
                    parent_node = $('#tree_node_'+node_id).closest(".tree_column").attr('id').replace("tree_column_",'');
                    $('#tree_node_'+node_id).remove();
                    tree_clear(parent_node);
                } else {
                    alert('Error!');
                }
            }
        });
    }
}

function tree_rename(obj) {
    var node_id = $(obj).closest('.tree_column').attr('id').replace('tree_column_','');
    var prev_name = $('#tree_node_'+node_id).html().replace(/\<span\>.*\<\/span\>/,'');
    var new_name = prompt("Enter a name for the new directory:",prev_name);
    if (new_name != null) {
        $.ajax({
            url:tree_data.action_file,
            type:"POST",
            dataType:"json",
            data:{action:'rename_node','node_id':node_id,name:new_name},
            success: function(data) {
                if (data.status=='ok') {
                    $('#tree_node_'+node_id).html($('#tree_node_'+node_id).html().replace(prev_name,new_name));
                } else {
                    alert('Error!');
                }
            }
        });
    }
}

function tree_upload(obj) {
    var node_id = $(obj).closest('.tree_column').attr('id').replace('tree_column_','');
    document.location = tree_data.action_file+"?node_id="+node_id+"&action=new";
}

function tree_node_string(name,id) {
    return "<div class='tree_column_row tree_column_directory' id='tree_node_"+id+"'>"+name+"<span>&gt</span></div>";
}

function tree_node_header(data) {
    buttons = '';
    buttons+=data.actions_before;
    if (data.upload==true)
        buttons+="<img src='/images/app/send_doc.png' title='Upload' onclick='tree_upload(this);' /> ";
    if (data.add==true)
        buttons+="<img src='/images/app/new2.png' title='New Folder' onclick='tree_add(this);' /> ";
    if (data.edit==true)
        buttons+="<img src='/images/app/pencil.png' title='Rename' onclick='tree_rename(this);' /> <img src='/images/app/remove2.png' title='Remove' onclick='tree_delete(this);' />";

    return "<div class='tree_column_header'><span>"+data.node_name+"</span>"+buttons+"</div>";
}
