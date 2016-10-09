/**
 * @file
 * Contains JS functions for the Table page.
 */
(function(){
    var self = lightning.table = {
        init: function () {
            $('.add_image').on('click', self.clickAddImage);
            $('.linked_images').on('click', '.remove', self.removeLinkedImage);

            // Initialize the link field buttons.
            $('table.table_form_table').on('click', '.remove-link', function (e) {
                var element = $(e.target);
                self.removeLink(element.data('link'), element.data('link-item'));
            }).on('click', '.add-link', self.clickAddLink);
            self.filterContainer = $('.filters');
            self.filterContainer.on('change', 'select', self.search);
            self.filterContainer.on('click', '.remove', self.removeFilter);
            self.filterSelect = $('#filters');
            self.filterSelect.on('change', self.addNewFilter);
            self.filters = lightning.get('table.filters', []);
            self.textSearch = $('#table_search');
            self.textSearch.on('keyup', self.search);

            $('.autocomplete_field').on('keyup', self.autocomplete);
            $('.autocomplete_link').on('keyup', self.autocomplete);
        },

        addNewFilter: function() {
            var filterName = self.filterSelect.val();
            if (filterName == '') {
                return;
            }
            self.filterSelect.val('');
            var newFilter = $('<fieldset class="filter row"><div class="small-1 column"><i class="fa fa-close remove"></i></div></fieldset>');
            newFilter.data('name', filterName);
            for (var i in self.filters[filterName].options) {
                if (self.filters[filterName].options[i].type == 'select') {
                    var field = $('<select>');
                    field.prop('name', i);
                    for (var j in self.filters[filterName].options[i].options) {
                        var option = $('<option>');
                        option.prop('value', j);
                        option.html(self.filters[filterName].options[i].options[j]);
                        field.append(option);
                    }
                }
                newFilter.append($('<div class="small-11 medium-5 column"></div>').append(field));
            }
            self.filterContainer.append(newFilter);
            self.search();
        },

        removeFilter: function(e) {
            $(e.target).closest('.filter').remove();
            self.search();
        },

        /**
         * Click handler for the list row.
         *
         * @param event
         *   The click event.
         */
        click: function (event) {
            var id = event.currentTarget.id;
            if (event.target.tagName != "INPUT" && lightning.vars.table_data.rowClick != undefined) {
                switch (lightning.vars.table_data.rowClick.type) {
                    case 'url':
                        document.location = lightning.vars.table_data.rowClick.url + id;
                        break;
                    case 'action':
                        document.location = self.createUrl(lightning.vars.table_data.rowClick.action, id);
                        break;
                }
            }
        },

        createUrl: function (action, id) {
            var vars = [];
            vars.push('action=' + encodeURIComponent(action));
            vars.push('id=' + encodeURIComponent(id));
            if (lightning.vars.table_data.table) {
                vars.push('table=' + encodeURIComponent(table_data.table));
            }
            if (lightning.vars.table_data.parent_link) {
                vars.push(table_data.parent_link + '=' + encodeURIComponent(lightning.vars.table_data.parent_id));
            }
            if (lightning.vars.table_data.vars) {
                for (var i in lightning.vars.table_data.vars) {
                    if (lightning.vars.table_data.vars[i] != null) {
                        vars.push(i + '=' + encodeURIComponent(lightning.vars.table_data.vars[i]));
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
        selectAll: function (name) {
            $('.taf_' + name).prop('checked', ($('#taf_all_' + name).is(':checked')));
        },

        searchTimeout: null,
        searchIndex: 0,
        searchIndexDisplayed: 0,
        search: function () {
            var search_terms = self.textSearch.val();
            var filters = [];
            self.filterContainer.find('.filter').each(function(){
                var filter = $(this);
                filters.push({
                    filter: filter.data('name'),
                    operator: filter.find('select[name="operator"] option:selected').val(),
                    value: filter.find('select[name="value"] option:selected').val(),
                });
            });

            self.searchIndex++;
            clearTimeout(self.searchTimeout);
            self.searchTimeout = setTimeout(function () {
                lightning.vars.table_data.vars.ste = search_terms;
                $.ajax({
                    url: lightning.get('table_data.action_file'),
                    type: 'GET',
                    dataType: 'json',
                    data: {
                        ste: search_terms,
                        i: self.searchIndex,
                        action: 'search',
                        filter: filters,
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

        clickAddLink: function (e) {
            var element = $(e.target);
            var selection = element.closest('td').find('select option:selected');
            self.addLink(element.data('link'), selection.val(), selection.html());
        },

        /**
         * Add the selected link to the list.
         *
         * @param {string} link
         *   The id of the link container.
         * @param {integer} value
         *   The id of the selected item.
         * @param {string} name
         *   The name of the item to display in the box.
         */
        addLink: function (link, value, name) {
            // Make sure it's a valid selection.
            if (link == 0 && value == '') {
                return;
            }

            // Make sure it's not already in the list.
            var regex = new RegExp('(^|,)' + value + ',');
            var input_array = $('#' + link + '_input_array');
            if (input_array.val().match(regex) == null) {
                input_array.val(input_array.val() + value + ',');
                $('#' + link + '_list_container').append($('<div class="' + link + '_box table_link_box_selected" id="' + link + '_box_' + value + '">' + name + ' <i class="remove-link fa fa-close" data-link="' + link + '" data-link-item="' + value + '"></i></div>'));
                var processes = lightning.get('table.linkProcess');
                for (var i in processes) {
                    lightning.getMethodReference(processes[i])();
                }
            }
        },

        /**
         * Remove the selected link to the list.
         *
         * @param {string} link
         *   The id of the link container.
         * @param {integer} link_id
         *   The id of the link item.
         */
        removeLink: function (link, link_id) {
            $('#' + link + '_box_' + link_id).remove();
            var input_array = $('#' + link + '_input_array');
            var new_links = input_array.val();
            var regex = new RegExp("[\\^,]" + link_id + ",", "i");
            new_links = new_links.replace(regex, ",");
            regex = new RegExp("^" + link_id + ",", "i");
            new_links = new_links.replace(regex, '');
            input_array.val(new_links);
        },

        clickAddImage: function (event) {
            var link_table = event.target.id.replace('add_image_', '');
            CKFinder.popup({
                basePath: lightning.vars.table.links[link_table].web_location,
                chooseFiles: true,
                chooseFilesOnDblClick: true,
                onInit: function (finder) {
                    finder.on('files:choose', function (evt) {
                        var file = evt.data.files.first();
                        self.addImageCallback(link_table, file.getUrl());
                    });
                    finder.on('file:choose:resizedImage', function (evt) {
                        self.addImageCallback(link_table, evt.data.resizedUrl);
                    });
                }
            });
        },

        addImageCallback: function (link_table, fileUrl) {
            $('#linked_images_' + link_table).append('<span class="selected_image_container">' +
                '<input type="hidden" name="linked_images_' + link_table + '[]" value="' + fileUrl + '">' +
                '<span class="remove">X</span>' +
                '<img src="' + fileUrl + '" /></span>');
        },

        removeLinkedImage: function (event) {
            $(event.target).closest('.selected_image_container').remove();
        },

        autocompleteCache: {
            field: {},
            link: {},
        },
        autocomplete: function () {
            var field = $(this);
            var field_name = field.data('name');
            var search = field.val();
            var type = field.data('type');

            // If the search isn't long enough, quit.
            if (search.length < 2) {
                return;
            }

            // If there is already an equal search in progress, quit.
            if (self.autocompleteCache[type][field_name] && self.autocompleteCache[type][field_name].lastFetch == search) {
                return;
            }

            self.autocompleteCache[type][field_name] = {
                lastFetch: search
            };
            $.ajax({
                url: lightning.get('table_data.action_file'),
                type: 'get',
                dataType: 'json',
                data: {
                    action: 'autocomplete',
                    field: field.data('name'),
                    type: type,
                    search: search,
                },
                success: function (data) {
                    if (search == self.autocompleteCache[type][field_name].lastFetch) {
                        self.autocompleteDropdown(field_name, type, data.results);
                    }
                },
            });
        },

        autocompleteDropdown: function (field, type, list) {
            $('.autocomplete_options').each(function () {
                if ($(this).attr('id') != "table_container_" + field) {
                    $(this).remove();
                } else {
                    $('#list_' + field).empty();
                }
            });
            if ($('.autocomplete_options').length == 0) {
                $('#' + field + '_autocomplete').after('<div id="table_container_' + field + '" class="autocomplete_options" data-field="' + field + '" data-type="' + type + '"><div id="list_' + field + '"></div></div>');
            }

            var container = $('#list_' + field);
            if (type == 'link' && lightning.get('table_data.links.' + field + '.create')) {
                container.append('<span class="create">Create a new entry</span>');
                container.on('click', '.create', self.setAutocompleteCreateNew);
            }
            for (var i in list) {
                container.append('<span class="select" data-value="' + i + '" >' + list[i] + '</span>');
            }
            container.on('click', '.select', self.setAutocompleteSelection);
        },

        setAutocompleteSelection: function (event) {
            var selection = $(this);
            var container = selection.closest('.autocomplete_options');
            var field_name = container.data('field');
            var value = selection.data('value');
            // Remove the list.
            container.remove();

            switch (container.data('type')) {
                case 'link':
                    // Add a new link.
                    self.addLink(field_name, value, selection.html());
                    break;
                case 'field':
                default:
                    // Set the field value.
                    $('#' + field_name).val(value);
                    break;
            }
        },

        setAutocompleteCreateNew: function (event) {
            var selection = $(this);
            var container = selection.closest('.autocomplete_options');
            var field_name = container.data('field');
            var value = container.closest('td').find('input[data-type="link"]').val();
            $.ajax({
                url: lightning.get('table_data.action_file'),
                type: 'POST',
                dataType: 'JSON',
                data: {
                    action: 'create-link',
                    link: field_name,
                    value: value,
                    token: lightning.vars.token,
                },
                success: function(data){
                    // Remove the list.
                    container.remove();
                    // Add the new tag.
                    self.addLink(field_name, data.id, data.value);
                }
            });
        },

        /* TABLE SUBTABLE  */

        deleteSubtable: function (button) {
            var entry_id = $(button).closest("div").attr("id").replace("subtable_", "");
            var entry_id_no = entry_id.split("_");
            entry_id_no = entry_id_no[entry_id_no.length - 1];
            entry_id_table = entry_id.replace("_" + entry_id_no, "");
            if (parseInt(entry_id_no) > 0)
                $('#delete_subtable_' + entry_id_table).val($('#delete_subtable_' + entry_id_table).val() + entry_id_no + ",");
            else {
                reg = new RegExp(Math.abs(entry_id_no) + ',');
                $('#new_subtable_' + entry_id_table).val($('#new_subtable_' + entry_id_table).val().replace(reg, ''));
            }
            $('#subtable_' + entry_id_table + '_' + entry_id_no).fadeOut(function () {
                $(this).remove();
            });
        },

        newSubtable: function (table) {
            if (typeof new_subtables === "undefined")
                new_subtables = Array();
            if (typeof new_subtables[table] === "undefined")
                new_subtables[table] = 1;
            else
                new_subtables[table]++;
            $('#subtable_' + table + '__N_').before($("<div class='subtable' id='subtable_" + table + "_-" + new_subtables[table] + "'></div>").html($('#subtable_' + table + '__N_').html().replace(/_N_/g, "-" + new_subtables[table])));
            $('#new_subtable_' + table).val($('#new_subtable_' + table).val() + new_subtables[table] + ",");
        },

        newPop: function (loc, pf, pfdf) {
            if (loc.indexOf("?") > -1)
                window.open(loc + "&action=pop&pf=" + pf + "&pfdf=" + pfdf + "&pop=1", 'New', 'width=400,height=500');
            else
                window.open(loc + "?action=pop&pf=" + pf + "&pfdf=" + pfdf + "&pop=1", 'New', 'width=400,height=500');
        },

        returnPop: function (data) {
            window.opener.$('#' + data.pf + '_list').append("<option value='" + data.id + "'>" + data.pfdf + "</option>").val(data.id);
            window.close();
        },

        /**
         * add to export button's url search string for export search results
         */
        export: function (link) {
            var searchStr = $('[name="table_search"]').val();
            console.log(link);
            var url = $(link).attr('href');
            if (searchStr != '') {
                url += '&ste=' + searchStr;
            }
            window.location = url;
        },

        resetField: function (field) {
            // check for ckeditor
            if (typeof CKEDITOR.instances[field] !== "undefined") {
                CKEDITOR.instances[field].setData(lightning.vars.table_data.defaults[field]);
            }

            // other fields
            else if (typeof ('#' + field).val !== "undefined") {
                $('#' + field).val(lightning.vars.table_data.defaults[field]);
            }
            else {
                $('#' + field).html(lightning.vars.table_data.defaults[field]);
            }
        }
    };
})();
