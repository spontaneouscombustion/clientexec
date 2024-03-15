var customFields = function() {

    var ctrl;
    var customFieldsAdded = [];
    var allFieldsDisabled = true;
    var selectListeners = [];
    var clickListeners = [];
    var deleteRowListeners = [];
    var unique_id = 0;

    return {

        load: function(fields, renderer, postload, unique) {
            allFieldsDisabled = true;

            if (!unique) unique = false
            for (var iterator = 0; iterator < fields.length; iterator++) {
                customFields.getMarkup(fields[iterator], renderer, unique);
            }
            //let's perform a post load method
            if (postload) postload();
        },

        bootstrap: function(data) {
            $.each(data, function(key, value) {
                if ($(value).css('display') == 'none') {
                    $(value).addClass('hidden');
                }
                $(value).removeAttr('style');
                $('input, label, select, button, textarea', value).removeAttr('style');

                $('label', value).addClass('control-label');
                if (value.localName == 'label') {
                    $(value).addClass('control-label');
                }
                $('textarea', value).addClass('form-control').removeClass('disableSelect2AutoLoad').css('width', '');
                if (value.localName == 'textarea') {
                    $(value).addClass('form-control').removeClass('disableSelect2AutoLoad').css('width', '');
                }
                $('input', value).removeClass('disableSelect2AutoLoad').addClass('form-control');
                if (value.localName == 'input') {
                    $(value).removeClass('disableSelect2AutoLoad').addClass('form-control');
                }
                $('select', value).removeClass('disableSelect2AutoLoad').addClass('form-control');
                if (value.localName == 'select') {
                    $(value).removeClass('disableSelect2AutoLoad').addClass('form-control');
                }
                $('button', value).removeAttr('onclick').removeClass('rich-button').addClass('form-control');
                if (value.localName == 'button') {
                    $(value).removeAttr('onclick').removeClass('rich-button').addClass('form-control');
                }
                if ($('div input', value).hasClass('disableDatePickerAutoLoad')) {
                    // it's a date value, so adjust it to have an inline calender icon
                    $('div input', value).parent().addClass('input-group').find('span.add-on').addClass('input-group-addon').find('i').addClass('fa fa-calendar');
                }
            });

            return data;
        },

        bootstrapWrap: function(data) {
            var html = '<div class="col-xs-12 header">';
            $.each(data, function(key, value) {
                if ($(value).css('display') == 'none') {
                    $(value).addClass('hidden');
                }
                $(value).removeAttr('style');
                $('input, label, select, button, textarea', value).removeAttr('style');
                if (value.localName == 'label') {
                    $(value).addClass('control-label');

                    if ($(value).hasClass('checkbox')) {
                        $('input', value).removeClass('form-control');
                    }
                } else if (value.localName == 'input') {
                    $(value).addClass('form-control');
                } else if (value.localName == 'textarea') {
                    $(value).addClass('form-control').removeClass('disableSelect2AutoLoad').css('width', '');
                } else if (value.localName == 'span') {
                    $('input, select, textarea', value).addClass('form-control').removeClass('disableSelect2AutoLoad');
                } else if (value.localName == 'select') {
                    $(value).removeClass('disableSelect2AutoLoad').addClass('form-control');
                } else if (value.localName == 'button') {
                    $(value).removeAttr('onclick').removeClass('rich-button').addClass('form-control');
                }
                if (value.localName == 'div' || $(value).has('div')) {
                    $('input:not([type="checkbox"])', value).removeClass('disableSelect2AutoLoad').addClass('form-control');
                    if ($('input', value).hasClass('disableDatePickerAutoLoad')) {
                        // it's a date value, so adjust it to have an inline calender icon
                        $('input', value).parent().addClass('input-group').find('span.add-on').addClass('input-group-addon').find('i').addClass('fa fa-calendar');
                    }
                    if (value.localName == 'div') {
                        $(value).addClass('col-sm-5 col-xs-12');
                    }
                }

                html += value.outerHTML;
            });
            html += '</div>';

            return html;
        },

        getMarkup: function(currentFieldData, renderer, unique) {
            var width;

            unique_id++;

            var isComboType = false;
            //var currentFieldValue = currentFieldData.value;
            var allowBlankValue = true;
            var isDisabled = true;
            var selectFirstOnBlank = true;

            ctrl = null;

            if (currentFieldData.isrequired == 1) {
                allowBlankValue = false;
            }

            if (currentFieldData.ischangeable == 1) {
                isDisabled = false;
                allFieldsDisabled = false;
            }

            if (typeof(currentFieldData.selectfirstonblank) != "undefined") {
                if (!currentFieldData.selectfirstonblank) {
                    selectFirstOnBlank = false;
                }
            }

            var createHiddenTypeField = false;
            var controlid = currentFieldData.id;
            if ($.isNumeric(currentFieldData.id) || currentFieldData.fieldtype == 50) {
                controlid = 'CT_' + currentFieldData.id;
                createHiddenTypeField = true;
            }

            //we need a unique id because we might be showing the same
            //fields for multiple products etc which will use the same id
            //for this element
            currentFieldData.field_name = controlid;
            if (unique) {
                currentFieldData.field_id = controlid + "_" + unique_id;
            } else {
                currentFieldData.field_id = controlid;
            }

            // pass the name of the field through lang)
            currentFieldData.name = clientexec.lang(currentFieldData.name);

            var metaValue = '';
            switch (currentFieldData.fieldtype) {
                case '65': //password
                    currentFieldData.is_password = true;
                case '2': //address
                case '14': //organization
                case '3': //city
                case '5': //zipcode
                case '13': //email
                case '11': //firstname
                case '12': //lastname
                case '7': //phone
                case '0': //textfield
                    this.renderTextField(controlid, currentFieldData, isDisabled, renderer);
                    break;
                case '47': // VAT Number
                    this.renderVATField(controlid, currentFieldData, isDisabled, renderer);
                    break;
                case '10': //textarea
                    this.renderTextArea(controlid, currentFieldData, isDisabled, renderer);
                    break;
                case '15': //date
                    this.renderDate(controlid, currentFieldData, isDisabled, renderer);
                    break;
                case '16': //allow_email
                case '1': //yesno (dropdown)
                    currentFieldData.dropdownoptions = [
                        [0, "No"],
                        [1, "Yes"]
                    ];
                case '8': //language (dropdown)
                case '30': //product status (dropdown)
                case '6': //country
                case '4': //state
                case '9': //dropdown
                    this.renderDropDown(controlid, currentFieldData, isDisabled, renderer);
                    isComboType = true;
                    break;
                case '49': //button
                    this.renderButton(controlid, currentFieldData, isDisabled, renderer);
                    break;
                case '52': //hidden element
                    this.renderHidden(controlid, currentFieldData, isDisabled, renderer);
                    break;
                case '54': //number field
                    this.renderTextField(controlid, currentFieldData, isDisabled, renderer);
                    $('#' + controlid).addClass('number'); //for validation
                    break;
                case '50': //render hostname
                    this.renderHostname(controlid, currentFieldData, isDisabled, renderer);
                    break;
                case '55': //render nameserver entry
                    this.renderNameserver(controlid, currentFieldData, isDisabled, renderer);
                    break;
                case '53':
                    this.renderCheckBox(controlid, currentFieldData, isDisabled, renderer);
                    break;
                case 'subdomain':
                    this.renderSubDomain(controlid, currentFieldData, isDisabled, renderer);
                    break;
                default:
                    //console.debug("error: no field definition found for type: "+currentFieldData.fieldtype , currentFieldData);
                    break;
            }

            //add hidden for type if we have a custom field
            if (createHiddenTypeField) {
                hiddenField = {};
                hiddenField.value = currentFieldData.fieldtype;
                hiddenField.id = 'CTT_' + currentFieldData.id;
                hiddenField.name = currentFieldData.id;
                hiddenField.additional_class = "customfield_hidden";

                //duplicating logic from above for this hidden type
                hiddenField.field_name = hiddenField.id;
                if (unique) {
                    hiddenField.field_id = hiddenField.id + "_" + unique_id;
                } else {
                    hiddenField.field_id = hiddenField.id;
                }

                this.renderHidden(hiddenField.id,
                    hiddenField, false, renderer);

            }

            //dynamic listener for click
            if ((currentFieldData.listener) && (currentFieldData.listener.onclick)) {
                clickListeners[controlid] = currentFieldData.listener.onclick;
                $('#' + controlid).bind('click', function() {
                    eval(clickListeners[this.id] + '()');
                });
            }

            if ((currentFieldData.listener) && (currentFieldData.listener.onselect)) {
                selectListeners[controlid] = currentFieldData.listener.onselect;
                $('#' + controlid).bind('change', function() {
                    eval(selectListeners[this.id] + '()');
                });
            }

            //debug statement REMOVEME
            //console.debug(currentFieldData);
        },

        renderCheckBox: function(controlid, currentFieldData, isDisabled, renderer) {
            var required = (currentFieldData.isrequired && currentFieldData.isrequired == true) ? 'data-mincheck="1" parsley-required="true"' : '';
            var field = '<label class="checkbox"><input id="' + controlid + '" name="' + currentFieldData.id + '" value="' + currentFieldData.value + '" type="checkbox" ' + required + ' />' + currentFieldData.name + '</label>';
            var ctrl = $(field);
            renderer(ctrl);
        },

        renderHidden: function(controlid, currentFieldData, isDisabled, renderer) {
            if (typeof(currentFieldData.name) === 'undefined') {
                currentFieldData.name = currentFieldData.id;
            }
            var label = '<span for="' + controlid + '" class="customfield_hidden" style="display:none;">' + currentFieldData.name + '</span>';
            var field = '<input id="' + controlid + '" name="' + currentFieldData.name + '" value="' + currentFieldData.value + '" type="hidden" />';
            var ctrl = $(label + field);
            renderer(ctrl);

        },

        renderButton: function(controlid, currentFieldData, isDisabled, renderer) {
            hidden = '';
            if (currentFieldData.ishidden) {
                hidden = 'display: none';
            }
            var width = (currentFieldData.width) ? currentFieldData.width : 330;
            var btnClass = 'btn';
            if (typeof(currentFieldData.class) !== 'undefined' && currentFieldData.class.length > 0) {
                btnClass += ' ' + currentFieldData.class;
            }

            var ctrl = $("<div id='" + controlid + "_wrapper'><button id='" + controlid + "' type='button' style='margin-top:20px;width:" + width + "px; " + hidden + "' class='" + btnClass + "'><span>" + clientexec.lang(currentFieldData.value) + "</span></button></div>");
            renderer(ctrl);
        },

        renderDate: function(controlid, currentFieldData, isDisabled, renderer) {

            var options = "",
                field = "",
                ctrl, dateformat;
            var disabledtext = (isDisabled) ? 'disabled="disabled"' : "";
            var width = (currentFieldData.width) ? currentFieldData.width : 288;
            var required = (currentFieldData.isrequired && currentFieldData.isrequired == true) ? 'data-required="true" parsley-required="true"' : '';
            var hidden = (currentFieldData.ishidden) ? "display:none;" : "";
            var name = currentFieldData.name;

            if (currentFieldData.description) {
                name = '<span data-toggle="popover-hover" data-html="true" data-placement="top" title="' + name + '" data-content="' + currentFieldData.description + '" class="tip-target">' + name + '</span>';
            }

            var label = '<label class="customfield_label" for="' + controlid + '" style="display:' + ((currentFieldData.ishidden) ? "none" : "") + '">' + name + '</label>';

            if (currentFieldData.value && currentFieldData.value !== '' && currentFieldData.value !== '00/00/0000') {
                //we format date on server side
            } else {
                currentFieldData.value = 'No date selected';
            }

            if (clientexec.dateFormat === "d/m/Y") {
                dateformat = "dd/mm/yyyy";
            } else {
                dateformat = "mm/dd/yyyy";
            }

            field = '<div style="' + hidden + '" class="input-append date" id="' + currentFieldData.field_id + '" data-date="' + currentFieldData.value + '" >';
            field += '<input name="' + controlid + '"" style="display: inline;width:' + width + 'px" class="span2 disableDatePickerAutoLoad" type="text" value="' + currentFieldData.value + '" readonly>';
            field += '<span class="add-on"><i class="icon-calendar"></i></span>';
            field += '</div>';
            if (currentFieldData.description) {
                field = '<span data-toggle="popover-hover" data-html="true" data-placement="top" title="' + currentFieldData.name + '" data-content="' + currentFieldData.description + '" class="tip-target">' + field + '</span>';
            }

            var arr = controlid.split("_");
            var ctt = "<input type='hidden' name='CTT_" + arr[1] + "' value='15'>"; // typeDate = 15

            ctrl = $(label + field + ctt);
            renderer(ctrl);

            $('#' + currentFieldData.field_id).datepicker({
                format: dateformat,
                autoclose: true
            });

        },

        renderNameserver: function(controlid, currentFieldData, isDisabled, renderer) {

            var disabledtext = (isDisabled) ? 'disabled="disabled"' : "";
            var required = (currentFieldData.isrequired && currentFieldData.isrequired == true) ? 'data-required="true" parsley-required="true"' : '';
            var hidden = (currentFieldData.ishidden) ? "display:none;" : "";
            var label = '<label  class="customfield_label" for="' + controlid + '" style="display:' + ((currentFieldData.ishidden) ? "none" : "") + '">' + currentFieldData.name + '</label>';

            var field = '<input class="nameserver" style="width:250px;' + hidden + '" ' + required + ' value="' + currentFieldData.value + '" ' + disabledtext + ' type="text" id="' + controlid + '" name="' + controlid + '" />';
            var postdiv = '<button type="button" name="' + controlid + '_nameserverdelete" id="' + controlid + '_nameserverdelete" style="margin-left:10px;' + hidden + '" class="rich-button btn" onclick="nameservers_deleteaddress(this);"><span>Delete</span></button>';

            var ctrl = $(label + field + postdiv);

            renderer(ctrl);
        },

        renderHostname: function(controlid, currentFieldData, isDisabled, renderer) {
            var hosttype = "",
                hostname = "",
                address = "",
                ctrl, options = "",
                prediv = "",
                postdiv = "";
            var disabledtext = (isDisabled) ? 'disabled="disabled"' : "";
            var required = (currentFieldData.isrequired && currentFieldData.isrequired == true) ? 'data-required="true" parsley-required="true"' : '';
            var hidden = "";
            var drop_class = "";
            if (currentFieldData.ishidden) {
                hidden = "display:none;";
                drop_class = "disableSelect2AutoLoad";
            }

            //build hosttype options
            selected = (currentFieldData.value.hosttype == "A") ? "selected" : "";
            options = '<option value="A" ' + selected + '>A</option>';

            selected = (currentFieldData.value.hosttype == "AAAA") ? "selected" : "";
            options += '<option value="AAAA" ' + selected + '>AAAA</option>';

            selected = (currentFieldData.value.hosttype == "MXE") ? "selected" : "";
            options += '<option value="MXE" ' + selected + '>MXE</option>';

            selected = (currentFieldData.value.hosttype == "MX") ? "selected" : "";
            options += '<option value="MX" ' + selected + '>MX</option>';

            selected = (currentFieldData.value.hosttype == "CNAME") ? "selected" : "";
            options += '<option value="CNAME" ' + selected + '>CNAME</option>';

            selected = (currentFieldData.value.hosttype == "URL") ? "selected" : "";
            options += '<option value="URL" ' + selected + '>URL</option>';

            selected = (currentFieldData.value.hosttype == "FRAME") ? "selected" : "";
            options += '<option value="FRAME" ' + selected + '>FRAME</option>';

            selected = (currentFieldData.value.hosttype == "TXT") ? "selected" : "";
            options += '<option value="TXT" ' + selected + '>TXT</option>';

            hostname = '<input style="width:176px;margin-right:3px;height:18px;margin-bottom:1px;' + hidden + '" ' + required + ' value="' + currentFieldData.value.hostname + '" ' + disabledtext + ' type="text" id="hostname_' + controlid + '" class="hostname" name="hostname_' + controlid + '"  placeholder="Name" />';
            hosttype = '<select ' + disabledtext + ' ' + required + ' id="hosttype_' + controlid + '" class="hosttype ' + drop_class + '" name="hosttype_' + controlid + '" style="width:90px;' + hidden + '">' + options + '</select>';
            address = '<input style="margin-left:3px;width:176px;height:18px;margin-bottom:1px;' + hidden + '" ' + required + " value='" + currentFieldData.value.address + "' " + disabledtext + ' type="text" id="hostaddress_' + controlid + '" class="hostaddress" name="hostaddress_' + controlid + '" placeholder="Address" />';

            if (!currentFieldData.ishidden) {
                prediv = "<div id='hostdivider_" + controlid + "' style='padding-top:10px;'></div>";
                buttonDisabled = '';
                if (isDisabled) {
                    buttonDisabled = 'disabled';
                }
                postdiv = '<button type="button" name="' + controlid + '_hostdelete" id="' + controlid + '_hostdelete" style="margin-left:10px;" class="rich-button btn" onclick="hostrecords_deleteaddress(this);" ' + buttonDisabled + '><span>Delete</span></button>';
            }

            ctrl = $(prediv + hostname + hosttype + address + postdiv);
            renderer(ctrl);

            if (typeof(packagemanager) !== "undefined") {
                packagemanager.newhostrecordid++;
            } else if (typeof(hostinfo) !== "undefined") {
                hostinfo.newhostrecordid++;
            }

            //set to select2 dropdown
            if (!currentFieldData.ishidden) {

                ctrlid = '#hosttype_' + controlid;
                if ($(ctrlid).hasClass('disableSelect2AutoLoad')) {
                    if (jQuery().select2) {
                        $(ctrlid).select2({
                            minimumResultsForSearch: 10,
                            width: 'resolve'
                        });
                    }
                }
            }

        },

        renderTextArea: function(controlid, currentFieldData, isDisabled, renderer) {

            var field = "",
                ctrl;
            var disabledtext = (isDisabled) ? 'disabled="disabled"' : "";
            var width = (currentFieldData.width) ? currentFieldData.width : 315;
            var height = (currentFieldData.height) ? currentFieldData.height : 100;
            var required = (currentFieldData.isrequired && currentFieldData.isrequired == true) ? 'data-required="true" parsley-required="true"' : '';
            var name = currentFieldData.name;

            if (currentFieldData.description) {
                name = '<span data-toggle="popover-hover" data-html="true" data-placement="top" title="' + name + '" data-content="' + currentFieldData.description + '" class="tip-target">' + name + '</span>';
            }

            var label = '<label class="customfield_label" for="' + controlid + '" style="display:' + ((currentFieldData.ishidden) ? "none" : "") + '">' + name + '</label>';

            field = '<textarea class="type_' + currentFieldData.fieldtype + ' disableSelect2AutoLoad" ' + disabledtext + ' ' + required + ' id="' + controlid + '" name="' + controlid + '" style="width:' + width + 'px; height:' + height + 'px">' + currentFieldData.value + '</textarea>';
            if (currentFieldData.description) {
                field = '<span data-toggle="popover-hover" data-html="true" data-placement="top" title="' + currentFieldData.name + '" data-content="' + currentFieldData.description + '" class="tip-target">' + field + '</span>';
            }

            ctrl = $(label + field);
            renderer(ctrl);

        },

        renderDropDown: function(controlid, currentFieldData, isDisabled, renderer) {

            //if checkbox then add description as new label otherwise add to name
            var options = "",
                field = "",
                ctrl, option_value, option_name;
            var disabledtext = (isDisabled) ? 'disabled="disabled"' : "";
            var width = (currentFieldData.width) ? currentFieldData.width : 330;
            var showlabel = (typeof(currentFieldData.showlabel) != "undefined") ? currentFieldData.showlabel : true;
            var required = (currentFieldData.isrequired && currentFieldData.isrequired == true) ? 'data-required="true" parsley-required="true"' : '';
            var showcheckboxes = (typeof(currentFieldData.showcheckboxes) != "undefined") ? currentFieldData.showcheckboxes : false;

            name = currentFieldData.name;
            if (!showcheckboxes && currentFieldData.description) {
                name = '<span data-toggle="popover-hover" data-html="true" data-placement="top" title="' + name + '" data-content="' + currentFieldData.description + '" class="tip-target">' + name + '</span>';
            } else if (showcheckboxes) {
                options = "<div class='field-description'>" + currentFieldData.description + "</div>";
            }
            var label = '<label class="customfield_label" for="' + controlid + '" style="display:' + ((currentFieldData.ishidden) ? "none" : "") + '">' + name + '</label>';

            var value_is_in_dropdown = false;

            $.each(currentFieldData.dropdownoptions, function(index, object) {
                option_value = object[0];
                option_name = object[1];

                if (showcheckboxes) {
                    selected = (option_value == currentFieldData.value) ? "checked" : "";
                    options += '<label  class="customfield_label radio"><input type="radio" ' + selected + ' name="' + controlid + '" value="' + option_value + '"> ' + option_name + '</label>';
                } else {
                    selected = (option_value == currentFieldData.value) ? "selected" : "";
                    options += "<option value='" + option_value + "' " + selected + ">" + option_name + "</option>";
                }

                if (selected != "") {
                    value_is_in_dropdown = true;
                }
            });

            if (!value_is_in_dropdown && currentFieldData.value != "") {
                if (showcheckboxes) {
                    options += '<label  class="customfield_label radio"><input type="radio" checked name="' + controlid + '" value="' + currentFieldData.value + '"> ' + currentFieldData.value + '</label>';
                } else {
                    options += "<option value='" + currentFieldData.value + "' selected>" + currentFieldData.value + "</option>";
                }
            }

            if (showcheckboxes) {
                field = "<fieldset>" + options + "</fieldset>";
            } else {

                if (typeof(currentFieldData.isquantity) != "undefined" && currentFieldData.isquantity === true) {
                    field = '<table style="width:100%"><tr><td style="width:75px">' + clientexec.lang("Quantity") + '*</br><input type="number" min="0" style="width:70px;" id="quantity_' + currentFieldData.field_id + '" name="quantity_' + controlid + '" class="quantity type_' + currentFieldData.fieldtype + '" value="' + currentFieldData.quantity + '"></td><td>' + clientexec.lang("Option") + '</br><select tabindex="1" class="type_' + currentFieldData.fieldtype + ' disableSelect2AutoLoad form-control-addon-quantity" ' + disabledtext + ' ' + required + ' id="' + currentFieldData.field_id + '" name="' + controlid + '" style="width:' + width + 'px">' + options + '</select></td></tr><tr><td colspan="2">* ' + clientexec.lang("Prices will be multiplied by the quantity") + '</br></td></tr></table>';
                } else {
                    field = '<select tabindex="1" class="type_' + currentFieldData.fieldtype + ' disableSelect2AutoLoad" ' + disabledtext + ' ' + required + ' id="' + currentFieldData.field_id + '" name="' + controlid + '" style="width:' + width + 'px">' + options + '</select>';
                }
            }
            if (currentFieldData.description) {
                field = '<span data-toggle="popover-hover" data-html="true" data-placement="top" title="' + currentFieldData.name + '" data-content="' + currentFieldData.description + '" class="tip-target">' + field + '</span>';
            }

            if (showlabel) {
                ctrl = $(label + field);
            } else {
                ctrl = $(field);
            }

            renderer(ctrl);

            ctrlid = '#' + currentFieldData.field_id;

            //set to select2 dropdown
            if (!showcheckboxes) {
                if ($(ctrlid).hasClass('disableSelect2AutoLoad')) {
                    if (jQuery().select2) {
                        $(ctrlid).select2({
                            minimumResultsForSearch: 10,
                            width: 'resolve'
                        });

                        //let's hide if hidden .. using this since we are
                        //using select2 control
                        if (currentFieldData.ishidden) {
                            $(ctrlid).select2('container').hide();
                        }
                    }
                }
            }

        },

        renderTextField: function(controlid, currentFieldData, isDisabled, renderer) {

            var is_password = (currentFieldData.is_password) ? currentFieldData.is_password : false;
            var disabledtext = (isDisabled) ? 'disabled="disabled"' : "";
            var width = (currentFieldData.width) ? currentFieldData.width : 315;
            var required = (currentFieldData.isrequired && currentFieldData.isrequired == true) ? 'data-required="true" parsley-required="true"' : '';
            var hidden = (currentFieldData.ishidden) ? "display:none;" : "";
            var additional_validation = (currentFieldData.validation_type) ? 'data-type="' + currentFieldData.validation_type + '"' : '';

            var name = currentFieldData.name;
            if (currentFieldData.description) {
                name = '<span data-toggle="popover-hover" data-html="true" data-placement="top" title="' + name + '" data-content="' + currentFieldData.description + '" class="tip-target">' + name + '</span>';
            }

            var label = '<label class="customfield_label" for="' + controlid + '" style="display:' + ((currentFieldData.ishidden) ? "none" : "") + '">' + name + '</label>';

            //undefined value should be set to ""
            if (typeof(currentFieldData.value) == "undefined") {
                currentFieldData.value = "";
            }

            if (currentFieldData.value !== null && currentFieldData.value !== false) {
                currentFieldData.value = currentFieldData.value.replace(/"/g, '&quot;');
            }

            var field = '<input style="width:' + width + 'px;' + hidden + '" ' + required + ' ' + additional_validation + ' class="type_' + currentFieldData.fieldtype + '" value="' + currentFieldData.value + '" ' + disabledtext + ' type="' + ((is_password) ? "password" : "text") + '" id="' + controlid + '" name="' + controlid + '" />';
            if (currentFieldData.description) {
                field = '<span data-toggle="popover-hover" data-html="true" data-placement="top" title="' + currentFieldData.name + '" data-content="' + currentFieldData.description + '" class="tip-target">' + field + '</span>';
            }

            var ctrl = $(label + field);

            renderer(ctrl);
        },

        renderSubDomain: function(controlid, currentFieldData, isDisabled, renderer) {

            var width = (currentFieldData.width) ? currentFieldData.width : 315;
            var name = currentFieldData.name;

            var label = '<table><tr><td><label class="customfield_label" for="' + controlid + '" style="display:' + ((currentFieldData.ishidden) ? "none" : "") + '">' + name + '</label>';
            var field = '<input pattern="^([-0-9A-Za-z]+)$" data-required="true" parsley-required="true" style="width:' + width + 'px;"class="type_' + currentFieldData.fieldtype + '" type="text" id="' + controlid + '" name="' + controlid + '" onchange="validateSubDomain(\'' + controlid + '\');"/></td>';

            var field = field + '<td style="padding-top:26px"  valign="top">&nbsp; . &nbsp; <select name="subdomaintld_' + controlid + '" id="subdomaintld_' + controlid + '" onchange="validateSubDomain(\'' + controlid + '\');">';
            $.each(currentFieldData.subdomains, function(index, value) {
                field = field + '<option value="' + value + '">' + value + '</option>';
            })
            field = field + '</select></td></tr></table>';
            var ctrl = $(label + field);
            renderer(ctrl);
        },

        renderVATField: function(controlid, currentFieldData, isDisabled, renderer) {

            var is_password = (currentFieldData.is_password) ? currentFieldData.is_password : false;
            var disabledtext = (isDisabled) ? 'disabled="disabled"' : "";
            var width = (currentFieldData.width) ? currentFieldData.width : 293;
            var required = (currentFieldData.isrequired && currentFieldData.isrequired == true) ? 'data-required="true" parsley-required="true"' : '';
            var hidden = (currentFieldData.ishidden) ? "display:none;" : "";
            var additional_validation = (currentFieldData.validation_type) ? 'data-type="' + currentFieldData.validation_type + '"' : '';

            var name = currentFieldData.name;
            if (currentFieldData.description) {
                name = '<span data-toggle="popover-hover" data-html="true" data-placement="top" title="' + name + '" data-content="' + currentFieldData.description + '" class="tip-target">' + name + '</span>';
            }

            var label = '<label class="customfield_label" for="' + controlid + '" style="display:' + ((currentFieldData.ishidden) ? "none" : "") + '">' + name + '</label>';

            //undefined value should be set to ""
            if (typeof(currentFieldData.value) == "undefined") {
                currentFieldData.value = "";
            }

            var field = '<div class="input-group"><span class="input-group-addon" id="vat_country"></span><input style="width:' + width + 'px;' + hidden + '" ' + required + ' ' + additional_validation + ' class="type_' + currentFieldData.fieldtype + '" value="' + currentFieldData.value + '" ' + disabledtext + ' type="' + ((is_password) ? "password" : "text") + '" id="' + controlid + '" name="' + controlid + '" /></div>';
            if (currentFieldData.description) {
                field = '<span data-toggle="popover-hover" data-html="true" data-placement="top" title="' + currentFieldData.name + '" data-content="' + currentFieldData.description + '" class="tip-target">' + field + '</span>';
            }
            var message0 = '<div id="vat_validating" style="display:none">'+clientexec.lang("Validating...")+'</div>';
            var message1 = '<div id="vat_valid" style="display:none">'+clientexec.lang("Valid VAT Number")+'</div>';
            var message2 = '<div id="vat_invalid" style="display:none">'+clientexec.lang("Invalid VAT Number")+'.&nbsp;<a href="javascript:validate_vat();"><font color=blue>'+clientexec.lang("Retry")+'</font></a></div>';
            var message3 = '<div id="vat_error" style="display:none">'+clientexec.lang("Unable to validate at the moment.")+'&nbsp;<a href="javascript:validate_vat();"><font color=blue>'+clientexec.lang("Retry")+'</font></a></div>';
            var ctrl = $('<span id="VAT' + controlid + '" name="VAT' + controlid + '">' + label + field + message0 + message1 + message2 + message3 + '</span>');

            renderer(ctrl);
        },

        /*
        getMarkup: function(currentFieldData, renderer) {


            var metaValue = '';
            switch(currentFieldData.fieldtype){
                case '6':   // typeCOUNTRY
                    width = (currentFieldData.width) ? currentFieldData.width : 200;
                    isComboType = true;
                    storeObject = currentFieldData.dropdownoptions;
                    ctrl = new Ext.form.ComboBox({
                        id:               controlid,
                        name:             controlid,
                        fieldLabel:       currentFieldData.name,
                        labelStyle:       'font-weight:bold;font-size:11px;',
                        typeAhead:        true,
                        triggerAction:    'all',
                        lazyRender:       true,
                        mode:             'local',
                        disabled:         isDisabled,
                        width:            width,
                        store:            storeObject,
                        allowBlank:       false,
                        blankText:        clientexec.lang("This field is required"),
                        forceSelection:   true,
                        editable:         false,
                        selectOnFocus:    true,
                        value:            currentFieldValue
                    });
                    if ( (currentFieldValue=="") || (currentFieldValue==null)) {
                        Ext.getCmp(controlid).setValue(Ext.getCmp(controlid).getStore().getAt(0).data.field1);
                    }
                    add(renderer, ctrl, currentFieldData, 'country',metaValue);
                    break;
                case '53': //typeCHECK
                    width = (currentFieldData.width) ? currentFieldData.width : 20;
                    ctrl = new Ext.form.Checkbox({
                        id:               controlid,
                        name:             controlid,
                        width:            width,
                        disabled:         isDisabled,
                        border:           false,
                        value:            currentFieldValue,
                        allowBlank:       allowBlankValue,
                        validationEvent: false,
                        blankText:        clientexec.lang("This field is required")
                    });
                    add(renderer, ctrl, currentFieldData ,'textfield',currentFieldValue);
                    break;
            }



        },*/

        getAllFieldsDisabled: function() {
            return allFieldsDisabled;
        },

        getCustomFieldsAdded: function() {
            return customFieldsAdded;
        },

        resetCustomFields: function() {
            customFieldsAdded = [];
        }
    };

}();