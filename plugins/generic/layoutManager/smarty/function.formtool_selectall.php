<?php

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

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * Type:     function
 * Name:     formtool_selectall
 * Purpose:  formtool selectall and uncheck all items in a list
 * -------------------------------------------------------------
 */
function smarty_function_formtool_selectall($params, &$smarty)
{
    if (empty($params['name'])) {
        $smarty->trigger_error("formtool_selectall: missing 'name' parameter");
        return;
    }
    $_name = $params['name'];

    // optional parameters
    foreach (array('class', 'style') as $item) {
        $local = "_$item";
        $$local = (array_key_exists($item, $params))
                ? " $item=\"{$params[$item]}\""
                : '';
    }

    $_selectall_text = empty($params['selectall_text']) ? 'Select All' : $params['selectall_text'];
    $_unselectall_text = empty($params['unselectall_text']) ? 'Unselect All' : $params['unselectall_text'];

    return "<input$_class$_style type=\"button\" value=\"$_selectall_text\" onClick=\"javascript:this.value=formtool_selectall('$_name', this.form.elements['$_name'],'"
        . addslashes($_selectall_text)
        . "','"
        . addslashes($_unselectall_text)
        . "');\" />\n";
}

/* vim: set expandtab: */

?>
