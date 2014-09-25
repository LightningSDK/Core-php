
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
                    set_autocomplete_dropdown(ac_field,data.list);
            },
            error:function(){
                alert("error");
            }
        });
    }
}

function set_autocomplete_dropdown(field,list){
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
