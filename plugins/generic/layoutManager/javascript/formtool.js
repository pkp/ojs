//------------------------------------------------------------------------------
//  SmartyFormtool Javascript Library version 1.3
//  http://www.phpinsider.com/php/code/SmartyFormtool/
//
//  Copyright(c) 2004 ispi. All rights reserved.
//
//  This library is free software; you can redistribute it and/or modify it
//  under the terms of the GNU Lesser General Public License as published by
//  the Free Software Foundation; either version 2.1 of the License, or (at
//  your option) any later version.
//
//  This library is distributed in the hope that it will be useful, but WITHOUT
//  ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
//  FITNESS FOR A PARTICULAR PURPOSE.  See the GNU Lesser General Public
//  License for more details.
//------------------------------------------------------------------------------
ischecked = new Object();
function formtool_checkall(id, field, check_text, uncheck_text) {
   if (ischecked[id] != "true") {
     for (i = 0; i < field.length; i++) { field[i].checked = true; }
     ischecked[id] = "true";
     return uncheck_text;
    } else {
     for (i = 0; i < field.length; i++) { field[i].checked = false; }
     ischecked[id] = "false";
     return check_text;
   }
}

var isselected = new Object();
function formtool_selectall(id, field, select_text, unselect_text) {
   if (isselected[id] != "true") {
     for (i = 0; i < field.length; i++) { field.options[i].selected = true; }
     isselected[id] = "true";
     return unselect_text;
   } else {
     for (i = 0; i < field.length; i++) { field.options[i].selected = false; }
     isselected[id] = "false";
     return select_text;
   }
}

function formtool_moveup(field,save) {
    for (i = 0; i < field.length; i++) {
        if(field.options[i].selected == true && i > 0) {
            var tmplabel = field.options[i-1].label;
            var tmpval = field.options[i-1].value;
            var tmptext = field.options[i-1].text;
            var tmpsel = field.options[i-1].selected;
            field.options[i-1].label = field.options[i].label;
            field.options[i-1].value = field.options[i].value;
            field.options[i-1].text = field.options[i].text;
            field.options[i-1].selected = field.options[i].selected;
            field.options[i].label = tmplabel;
            field.options[i].value = tmpval;
            field.options[i].text = tmptext;
            field.options[i].selected = tmpsel;
        }
    }
    formtool_save(field,save);
}

function formtool_movedown(field,save) {
    var max = field.length - 1;
    for (i = max; i >= 0; i--) {
        if(field.options[i].selected == true && i < max) {
            var tmplabel = field.options[i+1].label;
            var tmpval = field.options[i+1].value;
            var tmptext = field.options[i+1].text;
            var tmpsel = field.options[i+1].selected;
            field.options[i+1].label = field.options[i].label;
            field.options[i+1].value = field.options[i].value;
            field.options[i+1].text = field.options[i].text;
            field.options[i+1].selected = field.options[i].selected;
            field.options[i].label = tmplabel;
            field.options[i].value = tmpval;
            field.options[i].text = tmptext;
            field.options[i].selected = tmpsel;
        }
    }
    formtool_save(field,save);
}

function formtool_save(choices,storage) {
    order = new Array();
    for(i=0; i<choices.length; i++) {
        order[i] = choices.options[i].value;
    }
    storage.value = order.join(",");
}


function formtool_rename(field,text,save) {
    for (i = 0; i < field.length; i++) {
        if(field.options[i].selected == true) {
            field.options[i].text = text;
            field.options[i].value = text;
        }
    }
    formtool_save(field,save);
    return '';
}

function formtool_move(field1,field2,save_from,save_to,counter_from,counter_to,moveall) {
    if (moveall == true) {
        formtool_add_all(field1,field2,false);
        formtool_remove_all(field1,field2);
    } else {
        formtool_add_item(field1,field2,false);
        formtool_remove_item(field1);
    }
    formtool_save(field1,save_from);
    formtool_save(field2,save_to);
    if (counter_from) {
        counter_from.value = field1.length;
    }
    if (counter_to) {
        counter_to.value = field2.length;
    }

}

function formtool_copy(field1,field2,save,counter,copyall) {
    if (copyall == true) {
        formtool_add_all(field1,field2,true);
    } else {
        formtool_add_item(field1,field2,true);
    }
    formtool_save(field2,save);
    if (counter) {
        counter.value = field2.length;
    }
}

function formtool_remove(field,save,counter,removeall) {
    if (removeall == true) {
        formtool_remove_all(field);
    } else {
        formtool_remove_item(field);
    }
    formtool_save(field,save);
    if (counter) {
        counter.value = field.length;
    }
}


// code form adding/removing items originated from devx.com by Boris Feldman
// http://gethelp.devx.com/techtips/js_pro/10min/10min0499/10min0499.asp

function formtool_add_item(field1,field2,ignore_duplicates) {
	var i;
	var j;
	var itemexists;
	var nextitem;

	// step through all items in field1
	for (i = 0; i < field1.options.length; i++) {
		if (field1.options[i].selected) {
			// search field2 to see if duplicate
			j = 0;
			itemexists = false;
			while ((j < field2.options.length) && (!(itemexists))) {
				if (field2.options[j].value == field1.options[i].value) {
					itemexists = true;
					if (!ignore_duplicates) {
					   alert(field1.options[i].value + " found!");
                    }
				}
				j++;
			}
			if (!(itemexists)) {
				// add the item
				nextitem = field2.options.length;
				field2.options[nextitem] = new Option(field1.options[i].text);
				field2.options[nextitem].value = field1.options[i].value;
			}
		}
	}
}

function formtool_remove_item(field1) {
    var i;
	for (i = 0; i < field1.options.length; i++) {
		if (field1.options[i].selected) {
           field1.options[i] = null;
           i--;
        }
	}
}

function formtool_add_all(field1,field2,ignore_duplicates) {
	var i;
	var j;
	var itemexists;
	var nextitem;

	// step through all items in field1
	for (i = 0; i < field1.options.length; i++) {
		// search field2 to see if duplicate
		j = 0;
		itemexists = false;
		while ((j < field2.options.length) && (!(itemexists))) {
			if (field2.options[j].value == field1.options[i].value) {
				itemexists = true;
			}
			j++;
		}
		if (!(itemexists)) {
			// add the item
			nextitem = field2.options.length;
			field2.options[nextitem] = new Option(field1.options[i].text);
			field2.options[nextitem].value = field1.options[i].value;
		}
	}
}

function formtool_remove_all(field1) {
   field1.options.length = 0;
}

function formtool_set_size(list1,list2){
    list1.size = formtool_get_size(list1);
    list2.size = formtool_get_size(list2);
}

function formtool_unselect_all(list1,list2){
    list1.selectedIndex = -1;
    list2.selectedIndex = -1;
    moved_element = -1;
}

function formtool_get_size(list){
    var moz_len = 0;
    for(i=0; i < list.childNodes.length; i++) {
       if( list.childNodes.item(i).nodeType == 1 ) { moz_len++; }
    }
    if(moz_len < 2)
        return 2;
    else
        return moz_len;
}

function formtool_count_chars(textField, countField, maxlen,show_alert) {
    if(textField != null && textField.value != null) {
	    if (textField.value.length > maxlen){
            if(show_alert)
               alert('This field cannot exceed ' +  maxlen + ' characters.');
            textField.value = textField.value.substring(0, maxlen);
	    } else {
            countField.value = maxlen - textField.value.length;
	    }
    }
}