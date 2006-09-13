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
 * Name:     formtool_checkall
 * Purpose:  formtool checkall and uncheck all items in a list
 * -------------------------------------------------------------
 */
function smarty_function_formtool_checkall($params, &$smarty)
{
    if (empty($params['name'])) {
        $smarty->trigger_error("formtool_checkall: missing 'name' parameter");
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

    $_checkall_text = empty($params['checkall_text']) ? 'Check All' : $params['checkall_text'];
    $_uncheckall_text = empty($params['uncheckall_text']) ? 'Uncheck All' : $params['uncheckall_text'];

    return "<input$_class$_style type=\"button\" value=\"$_checkall_text\" onClick=\"javascript:this.value=formtool_checkall('$_name', this.form.elements['$_name'],'"
        . addslashes($_checkall_text)
        . "','"
        . addslashes($_uncheckall_text)
        . "');\" />\n";
}

/* vim: set expandtab: */

?>
