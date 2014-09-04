function validate_email(email){
    var filter = /^([a-zA-Z0-9_.-])+@(([a-zA-Z0-9-])+.)+([a-zA-Z0-9]{2,4})+$/;
    if (!filter.test(email))
        return false;
    else
        return true;
}

function submit_request_form(form){
    $.ajax({
        url: $('#'+form).attr("action"),
        type: "POST",
        context: document.body,
        dataType: "text",
        data: get_form_data(form),
        success: function(){
            $("#"+form+"_success").show();
            $("#"+form).hide();
        },
        error: function(){
            $("#"+form+"_error").show();
        }
    });

    return false;
}

function get_form_data(form_id){

    var a = [];

    a.push({name: "action", value: "post"});

    $('#'+form_id).find(':input').each(function(){a.push({name: $(this).attr("name"), value: $(this).val()})})

    return a;

}

function edit_page(){
    $(".page_edit").fadeIn();
    $('.page_edit_links').fadeOut();
    $('#page_display').attr('contentEditable', 'true');
    page_editor = CKEDITOR.inline("page_display", {
//            extraPlugins: 'tableresize,inserthtml',
            toolbar:CKEDITOR.config.toolbar_Full
        }
    );
}

function save_page(){
    $('#save_button').fadeOut();
    page_editor.destroy();
    var send = {
        page_id:$('#page_id').val(),
        action:"save",
        title:$("#page_title").val(),
        url:$("#page_url").val(),
        description:$("#page_description").val(),
        keywords:$('#page_keywords').val(),
        sitemap:$('#page_sitemap').is(":checked")?1:0,
        page_body:$('#page_display').html()
    };
    $.ajax({
        url:'/page',
        type:'POST',
        dataType:'json',
        data:send,
        success:function(data){
            if(data.status == 'OK'){
                $(".page_edit").fadeOut();
                $('.page_edit_links').fadeIn();
                $('#page_display').attr('contentEditable', 'false');
                $('#page_id').val(data.page_id);
                $('#page_url').val(data.url);
                document.title = data.title;
                $("#page_title").val(data.title);
            } else {
                alert(data.error);
                edit_page();
            }
        },
        error:function(){
            alert('The page could not be saved, please try again later.');
            edit_page();
        }
    });
}


/* BLOG FUNCTIONS */

function submit_comment(blog_id){
    var email = $('#blog_comment_email').val();
    var name = $('#blog_comment_name').val();
    var web = $('#blog_comment_web').val();
    var comment = $('#blog_new_comment').val();
    $.ajax({
        url: "/blog.php",
        data: {"action":"post_comment_check","name":name,"email":email,"web":web,"comment":comment,"blog_id":blog_id},
        type: "POST",
        dataType: "html",
        success: function(data){
            $.ajax({
                url: "blog.php",
                data: {"action":"post_comment",
                    "name":name,
                    "email":email,
                    "comment":comment,
                    "web":web,
                    "blog_id":blog_id,
                    "check_val":data},
                type: "POST",
                dataType: "html",
                success: function(data){
                    var new_comment = $('#blog_comment_blank').clone().attr('id','').hide();
                    new_comment.find('.blog_comment_body').html(comment);
                    new_comment.find('.blog_comment_name').html("By "+name);
                    date = new Date();
                    new_comment.find('.blog_comment_date').html("just posted");
                    $('#blog_comment_container').prepend(new_comment);
                    $('#new_comment_box').fadeOut(function(){
                        $('#blog_comment_container .blog_comment').fadeIn("slow");
                    });
                }
            })
        }
    });
}

function edit_blog(){
    $('#blog_edit').show();
    $('#blog_body').hide();
}

function delete_blog_comment(comment_id){
    $.ajax({
        url:"/blog.php",
        data:{"action":"remove_blog_comment","blog_comment_id":comment_id},
        type:"POST",
        dataType:"html",
        success:function(data){
            if(data == "ok")
                $('#blog_comment_'+comment_id).fadeOut();
        }
    });
}

function approve_blog_comment(comment_id){
    $.ajax({
        url:"blog.php",
        data:{"action":"approve_blog_comment","blog_comment_id":comment_id},
        type:"POST",
        dataType:"html",
        success:function(data){
            if(data == "ok")
                $('#blog_comment_'+comment_id+" .approve_comment").fadeOut();
        }
    });
}


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


function json_to_ul (jsondata, target) {
    if (typeof(jsondata) == 'undefined' || typeof(target) == 'undefined') {
        return false;
    }

    var items = [];

    $.each(jsondata, function(key, val) {
        items.push('<li class="blogpost"><a href="' + val.url + '">' + val.title + '</a></li>');
    });

    $('<ul/>', {
        'class': 'blogpostlist',
        html: items.join('')
    }).appendTo(target);

}

function json_on_id (jsonreq, target) {
    $.ajax({
        type: "GET",
        url: jsonreq,
        data: "json=1",
        dataType: "jsonp",
        jsonpCallback: target,
        cache: true,
        success: function (data) {
            json_to_ul(data, "#"+target);
        }
    });
}


var table = {
    /**
     * Click handler for the list row.
     *
     * @param event
     *   The click event.
     */
    click: function(event) {
        var id = event.currentTarget.id;
        if(event.target.tagName != "INPUT" && table_data.rowClick != undefined){
            switch(table_data.rowClick.type){
                case 'url':
                    document.location = table_data.rowClick.url + id;
                    break;
                case 'action':
                    document.location = table.createUrl(table_data.rowClick.action, id);
                    break;
            }
        }
    },

    createUrl: function(action, id) {
        var vars = [];
        vars.push('action='+encodeURIComponent(action));
        vars.push('id='+encodeURIComponent(id));
        if (table_data.table){
            vars.push('table='+encodeURIComponent(table_data.table));
        }
        if (table_data.parent_link){
            vars.push(table_data.parent_link + '=' + encodeURIComponent(table_data.parent_id));
        }
        if (table_data.vars) {
            for (var i in table_data.vars) {
                vars.push(i + '=' + encodeURIComponent(table_data.vars[i]));
            }
        }
        return url = "?" + vars.join("&");
    },

    /**
     * Handles the checkall box at the top of the list view.
     *
     * @param name
     *   The name of the select column.
     */
    selectAll: function(name) {
        $('.taf_' + name).prop('checked', ($('#taf_all_' + name).is(':checked')));
    }
};

function table_autocomplete(){
    ac_field = $(this).attr("id");
    if(typeof ac_settings === "undefined"){
        ac_settings = Array();
        ac_settings[ac_field] = {};
    }
    if(typeof ac_settings[ac_field].last_fetch === "undefined" || ac_settings[ac_field].last_fetch != $(this).val()){
        ac_settings[ac_field].last_fetch = $(this).val();
        $.ajax({url:table_data.action_file,
            type:"POST",
            dataType:"json",
            data:{action:'autocomplete',field:ac_field,st:ac_settings[ac_field].last_fetch},
            success:function(data){
                console.log(data);
                if(data.search == ac_settings[ac_field].last_fetch)
                    set_autocmplete_dropdown(ac_field,data.list);
            },
            error:function(){
                alert("error");
            }
        });
    }
}

function set_autocmplete_dropdown(field,list){
    $('.table_ac_container').each(function(){
        if($(this).attr('id')!="table_ac_container_"+field)
            $(this).remove();
        else
            $('#ac_list_'+field).empty();
    });
    if($('.table_ac_container').length == 0){
        $("#"+field).after("<div id='table_ac_container_"+field+"' class='table_ac_container'><div id='ac_list_"+field+"'></div></div>");
    }
    var count = 0;
    for(var i in list){
        if(count < 10){
            $("#ac_list_"+field).append("<a id='ac_"+field+"_"+i+"' >"+list[i][field]+"</a>");
            count++;
        }
        $("#ac_list_"+field+" a").click(set_autocomplete_selection);
    }
}

function set_autocomplete_selection(event){
    var id = $(this).attr('id').split("_");
    var field = id[1];
    id = id[2];
    $("#table_ac_container_"+field).remove();
    $("#"+field).val($(this).html());
    event.preventDefault();
    return false;
}

/* TABLE SUBTABLE  */

function delete_subtable(button){
    var entry_id = $(button).closest("div").attr("id").replace("subtable_","");
    var entry_id_no = entry_id.split("_");
    entry_id_no = entry_id_no[entry_id_no.length-1];
    entry_id_table = entry_id.replace("_"+entry_id_no,"");
    if(parseInt(entry_id_no) > 0)
        $('#delete_subtable_'+entry_id_table).val($('#delete_subtable_'+entry_id_table).val()+entry_id_no+",");
    else{
        reg = new RegExp(Math.abs(entry_id_no)+',');
        $('#new_subtable_'+entry_id_table).val($('#new_subtable_'+entry_id_table).val().replace(reg,''));
    }
    $('#subtable_'+entry_id_table+'_'+entry_id_no).fadeOut(function(){$(this).remove();});
}

function new_subtable(table){
    if(typeof new_subtables === "undefined")
        new_subtables = Array();
    if(typeof new_subtables[table] === "undefined")
        new_subtables[table] = 1;
    else
        new_subtables[table]++;
    $('#subtable_'+table+'__N_').before($("<div class='subtable' id='subtable_"+table+"_-"+new_subtables[table]+"'></div>").html($('#subtable_'+table+'__N_').html().replace(/_N_/g,"-"+new_subtables[table])));
    $('#new_subtable_'+table).val($('#new_subtable_'+table).val()+new_subtables[table]+",");
}

function new_pop(loc,pf,pfdf){
    if(loc.indexOf("?")>-1)
        window.open(loc+"&pf="+pf+"&pfdf="+pfdf+"&pop=1",'New','width=400,height=500');
    else
        window.open(loc+"?pf="+pf+"&pfdf="+pfdf+"&pop=1",'New','width=400,height=500');
}

function update_parent_pop(data){
    window.opener.$('#'+data.pf).append("<option value='"+data.id+"'>"+data.pfdf+"</option>").val(data.id);
    window.close();
}

function table_search(field){
    var search_terms = $(field).val();
    table_search_i++;
    $.ajax({
        url:table_data.action_file,
        type:"POST",
        dataType:"json",
        data:{ste:search_terms,i:table_search_i,action:"search"},
        success:function(data){
            if(data.d > table_search_d){
                table_search_i = data.d;
                $('#list_table_container').html(data.html);
            }
        }
    });
}

function reset_field_value(field){
    // check for ckeditor
    if(typeof CKEDITOR.instances[field] !== "undefined")
        CKEDITOR.instances[field].setData(table_data.defaults[field]);

    // other fields
    else if(typeof ("#"+field).val !== "undefined")
        $('#'+field).val(table_data.defaults[field]);
    else
        $('#'+field).html(table_data.defaults[field]);

}

/*
 function table_set_now(field){
 $('#'+field+'_m').val();
 $('#'+field+'_d').val();
 $('#'+field+'_Y').val();
 }
 */

/* TREE EVENTS */
function tree_clear(click_col){
    var clear=false;
    $(".tree_column").each(function(){
        if(clear)
            $(this).remove();
        if($(this).attr("id").replace("tree_column_","") == click_col)
            clear = true;
    });
}

function tree_sub_click(){
    var click_col = $(this).closest(".tree_column").attr("id").replace("tree_column_","");
    // clear unassociated columns
    tree_clear(click_col);
    // set as selected
    $('#tree_column_'+click_col+" .selected").removeClass('selected');
    $(this).addClass('selected');
    if($(this).hasClass("tree_column_directory")){
        // load directory
        var click_id = $(this).attr("id").replace("tree_node_","");
        $.ajax({
            url:tree_data.action_file,
            type:"POST",
            dataType:"json",
            data:{action:'load_node',node_id:click_id},
            success: function (data){
                if(data.status == "error"){
                    alert(data.error);
                } else {
                    $('#tree_'+tree_data.tree_name).append("<div class='tree_column' id='tree_column_"+data.node_id+"'></div>");
                    $("#tree_column_"+data.node_id).append(tree_node_header(data));
                    for(i in data.nodes)
                        $("#tree_column_"+data.node_id).append(tree_node_string(data.nodes[i].node_name,data.nodes[i].node_id));
                    for(i in data.items)
                        $("#tree_column_"+data.node_id).append("<div class='tree_column_row tree_column_item' id='tree_obj_"+data.items[i].item_id+"'>"+data.items[i].item_title+"</div>");

                    /* 				$("#tree_column_"+data.node_id).append("<div class='tree_column_row tree_column_item'><a href=\"#\">test<a/></div>"); */

                    $("#tree_column_"+data.node_id+" .tree_column_row").click(tree_sub_click);
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
            success: function (data){
                $('#tree_'+tree_data.tree_name).append("<div class='tree_column tree_column_obj' id='tree_column_obj_"+data.id+"'>"+data.html+"</div>");
                var container_width = $('#tree_'+tree_data.tree_name).find('.tree_column').length * ($('#tree_'+tree_data.tree_name).find('.tree_column').first().width()+1);//+1 for border
                $('#tree_'+tree_data.tree_name).css('width',container_width);
                $('#tree_'+tree_data.tree_name).parent().scrollLeft($('#tree_'+tree_data.tree_name).width() - $('#tree_'+tree_data.tree_name).parent().width());
            }
        });
    }

}

function tree_add(obj){
    var new_name = prompt("Enter a name for the new directory:");
    var col = $(obj).closest('.tree_column');
    if(new_name != null){
        $.ajax({
            url:tree_data.action_file,
            type:"POST",
            dataType:"json",
            data:{action:'add_node','node_id':col.attr('id').replace('tree_column_',''),name:new_name},
            success: function(data){
                if(data.status=='ok'){
                    var inserted = false;
                    col.find('.tree_column_row').each(function(){
                        if($(this).html().replace(/\<span\>.*\<\/span\>/,'').toLowerCase() > data.name.toLowerCase()){
                            $(this).before(tree_node_string(data.name,data.node_id));
                            $('#tree_node_'+data.node_id).click(tree_sub_click);
                            inserted = true;
                            return false;
                        }
                    });
                    if(!inserted) {
                        $(obj).closest('.tree_column').append(tree_node_string(data.name,data.node_id));
                        $('#tree_node_'+data.node_id).click(tree_sub_click);
                    }
                }
            }
        });
    }
}

function tree_delete(obj){
    var node_id = $(obj).closest('.tree_column').attr('id').replace('tree_column_','');
    var prev_name = $('#tree_node_'+node_id).html().replace(/\<span\>.*\<\/span\>/,'');
    var conf = confirm("Are you sure you want to delete the node '"+prev_name+"' ?");
    if(conf==true){
        $.ajax({
            url:tree_data.action_file,
            type:"POST",
            dataType:"json",
            data:{action:'remove_node','node_id':node_id},
            success: function(data){
                if(data.status=='ok'){
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

function tree_rename(obj){
    var node_id = $(obj).closest('.tree_column').attr('id').replace('tree_column_','');
    var prev_name = $('#tree_node_'+node_id).html().replace(/\<span\>.*\<\/span\>/,'');
    var new_name = prompt("Enter a name for the new directory:",prev_name);
    if(new_name != null){
        $.ajax({
            url:tree_data.action_file,
            type:"POST",
            dataType:"json",
            data:{action:'rename_node','node_id':node_id,name:new_name},
            success: function(data){
                if(data.status=='ok'){
                    $('#tree_node_'+node_id).html($('#tree_node_'+node_id).html().replace(prev_name,new_name));
                } else {
                    alert('Error!');
                }
            }
        });
    }
}

function tree_upload(obj){
    var node_id = $(obj).closest('.tree_column').attr('id').replace('tree_column_','');
    document.location = tree_data.action_file+"?node_id="+node_id+"&action=new";
}

function tree_node_string(name,id){
    return "<div class='tree_column_row tree_column_directory' id='tree_node_"+id+"'>"+name+"<span>&gt</span></div>";
}

function tree_node_header(data){
    buttons = '';
    buttons+=data.actions_before;
    if(data.upload==true)
        buttons+="<img src='/images/app/send_doc.png' title='Upload' onclick='tree_upload(this);' /> ";
    if(data.add==true)
        buttons+="<img src='/images/app/new2.png' title='New Folder' onclick='tree_add(this);' /> ";
    if(data.edit==true)
        buttons+="<img src='/images/app/pencil.png' title='Rename' onclick='tree_rename(this);' /> <img src='/images/app/remove2.png' title='Remove' onclick='tree_delete(this);' />";

    return "<div class='tree_column_header'><span>"+data.node_name+"</span>"+buttons+"</div>";
}
