/**
 * @file
 * Contains JS functions for the Table page.
 */
lightning.table = {
    init: function() {
        $('.add_image').on('click', lightning.table.clickAddImage);
        $('.linked_images').on('click', '.remove', lightning.table.removeLinkedImage);
        self = this;
        var search = $('#table_search');
        search.keyup(function(){
            self.search(search);
        });
    },

    /**
     * Click handler for the list row.
     *
     * @param event
     *   The click event.
     */
    click: function(event) {
        var id = event.currentTarget.id;
        if (event.target.tagName != "INPUT" && table_data.rowClick != undefined) {
            switch(table_data.rowClick.type) {
                case 'url':
                    document.location = table_data.rowClick.url + id;
                    break;
                case 'action':
                    document.location = lightning.table.createUrl(table_data.rowClick.action, id);
                    break;
            }
        }
    },

    createUrl: function(action, id) {
        var vars = [];
        vars.push('action='+encodeURIComponent(action));
        vars.push('id='+encodeURIComponent(id));
        if (table_data.table) {
            vars.push('table='+encodeURIComponent(table_data.table));
        }
        if (table_data.parent_link) {
            vars.push(table_data.parent_link + '=' + encodeURIComponent(table_data.parent_id));
        }
        if (table_data.vars) {
            for (var i in table_data.vars) {
                if (table_data.vars[i] != null) {
                    vars.push(i + '=' + encodeURIComponent(table_data.vars[i]));
                }
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
    },

    searchTimeout: null,
    searchIndex: 0,
    searchIndexDisplayed: 0,
    search: function(field) {
        var search_terms = $(field).val();
        this.searchIndex++;
        clearTimeout(this.searchTimeout);
        var self = this;
        this.searchTimeout = setTimeout(function() {
            table_data.vars.ste = search_terms;
            $.ajax({
                url: table_data.action_file,
                type: 'GET',
                dataType: 'json',
                data: {
                    ste: search_terms,
                    i: self.searchIndex,
                    action: 'search'
                },
                success: function (data) {
                    if (data.d > self.searchIndexDisplayed) {
                        self.searchIndexDisplayed = data.d;
                        $('.table_list').html(data.html);
                    }
                }
            });
        }, 500);
    },

    /**
     * Add the selected link to the list.
     *
     * @param {string} link
     *   The id of the link container.
     */
    addLink: function(link) {
        var list = $('#' + link + '_list');
        var new_link = list.val();
        var new_link_name = $('#'+link+'_list option:selected').text();
        // TODO: This shouldn't be removed, but just check if it's already there.
        this.removeLink(link, new_link);
        var input_array = $('#'+link+'_input_array');
        input_array.val(input_array.val() + new_link + ',');
        $('#'+link+'_list_container').append($('<div class="' + link + '_box table_link_box_selected" id="' + link + '_box_' + new_link + '">' + new_link_name + ' <a href="#" onclick="javascript:lightning.table.removeLink(\'' + link + '\', ' + new_link + ');return false;">X</a></div>'));
    },

    /**
     * Remove the selected link to the list.
     *
     * @param {string} link
     *   The id of the link container.
     * @param {integer} link_id
     *   The id of the link item.
     */
    removeLink: function(link,link_id) {
        $('#'+link+'_box_'+link_id).remove();
        var input_array = $('#' + link + '_input_array');
        var new_links = input_array.val();
        var regex = new RegExp("[\\^,]" + link_id + ",", "i");
        new_links = new_links.replace(regex,",");
        regex = new RegExp("^" + link_id + ",", "i");
        new_links = new_links.replace(regex, '');
        input_array.val(new_links);
    },

    clickAddImage: function(event) {
        var link_table = event.target.id.replace('add_image_', '');
        CKFinder.popup({
            basePath: lightning.vars.table.links[link_table].web_location,
            chooseFiles: true,
            chooseFilesOnDblClick: true,
            onInit: function( finder ) {
                finder.on( 'files:choose', function( evt ) {
                    var file = evt.data.files.first();
                    lightning.table.addImageCallback(link_table, file.getUrl());
                } );
                finder.on( 'file:choose:resizedImage', function( evt ) {
                    lightning.table.addImageCallback(link_table, evt.data.resizedUrl);
                } );
            }
        });
    },

    addImageCallback: function(link_table, fileUrl) {
        $('#linked_images_' + link_table).append('<span class="selected_image_container">' +
            '<input type="hidden" name="linked_images_' + link_table + '[]" value="' + fileUrl + '">' +
            '<span class="remove">X</span>' +
            '<img src="' + fileUrl + '" /></span>');
    },

    removeLinkedImage: function(event) {
        $(event.target).closest('.selected_image_container').remove();
    },

    autocomplete: function() {
        var field = $(this).attr('id');
        if (!table_data.fields) {
            table_data.fields = {};
            table_data.fields[field] = {};
        }
        var search = $(this).val();
        if (search.length >= 2 && (!table_data.fields[field].last_fetch || table_data.fields[field].last_fetch != search)) {
            table_data.fields[field].last_fetch = search;
            $.ajax({
                url: table_data.action_file,
                type: 'get',
                dataType: 'json',
                data: {
                    action: 'autocomplete',
                    field: field,
                    st: table_data.fields[field].last_fetch,
                },
                success: function(data) {
                    if (search == table_data.fields[field].last_fetch) {
                        lightning.table.autocompleteDropdown(field, data.results);
                    }
                },
                error: function() {
                    alert("error");
                }
            });
        }
    },

    autocompleteDropdown: function(field,list) {
        $('.table_container').each(function() {
            if ($(this).attr('id') != "table_container_" + field) {
                $(this).remove();
            } else {
                $('#list_' + field).empty();
            }
        });
        if ($('.table_container').length == 0) {
            $("#" + field).after("<div id='table_container_"+field+"' class='table_container'><div id='list_" + field + "'></div></div>");
        }
        var count = 0;
        for (var i in list) {
            $('#list_' + field).append('<span id="' + field + '_' + i + '" >' + list[i] + '</span>');
            count++;
        }
        $('#list_' + field + ' span').on('click', lightning.table.setAutocompleteSelection);
    },

    setAutocompleteSelection: function(event) {
        var id = $(this).attr('id').split('_');
        var value = id.pop();
        var field = id.join('_');
        $('#table_container_' + field).remove();
        $('#' + field).val(value);
    },

    /* TABLE SUBTABLE  */

    deleteSubtable: function(button) {
        var entry_id = $(button).closest("div").attr("id").replace("subtable_","");
        var entry_id_no = entry_id.split("_");
        entry_id_no = entry_id_no[entry_id_no.length-1];
        entry_id_table = entry_id.replace("_"+entry_id_no,"");
        if (parseInt(entry_id_no) > 0)
            $('#delete_subtable_'+entry_id_table).val($('#delete_subtable_'+entry_id_table).val()+entry_id_no+",");
        else {
            reg = new RegExp(Math.abs(entry_id_no)+',');
            $('#new_subtable_'+entry_id_table).val($('#new_subtable_'+entry_id_table).val().replace(reg,''));
        }
        $('#subtable_'+entry_id_table+'_'+entry_id_no).fadeOut(function() {$(this).remove();});
    },

    newSubtable: function(table) {
        if (typeof new_subtables === "undefined")
            new_subtables = Array();
        if (typeof new_subtables[table] === "undefined")
            new_subtables[table] = 1;
        else
            new_subtables[table]++;
        $('#subtable_'+table+'__N_').before($("<div class='subtable' id='subtable_"+table+"_-"+new_subtables[table]+"'></div>").html($('#subtable_'+table+'__N_').html().replace(/_N_/g,"-"+new_subtables[table])));
        $('#new_subtable_'+table).val($('#new_subtable_'+table).val()+new_subtables[table]+",");
    },

    newPop: function(loc,pf,pfdf) {
        if (loc.indexOf("?")>-1)
            window.open(loc+"&action=pop&pf="+pf+"&pfdf="+pfdf+"&pop=1",'New','width=400,height=500');
        else
            window.open(loc+"?action=pop&pf="+pf+"&pfdf="+pfdf+"&pop=1",'New','width=400,height=500');
    },

    returnPop: function(data) {
        window.opener.$('#' + data.pf + '_list').append("<option value='"+data.id+"'>"+data.pfdf+"</option>").val(data.id);
        window.close();
    },
    /**
     * add to export button's url search string for export search results
     */
    export: function(link) {
        var searchStr = $('[name="table_search"]').val();
        console.log(link);
        var url = $(link).attr('href');
        if ( searchStr != '' ){
            url += '&ste='+searchStr;
        }
        window.location = url;
    }
};


function reset_field_value(field) {
    // check for ckeditor
    if (typeof CKEDITOR.instances[field] !== "undefined")
        CKEDITOR.instances[field].setData(table_data.defaults[field]);

    // other fields
    else if (typeof ("#"+field).val !== "undefined")
        $('#'+field).val(table_data.defaults[field]);
    else
        $('#'+field).html(table_data.defaults[field]);

}
