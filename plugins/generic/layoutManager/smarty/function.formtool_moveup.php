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
 * Name:     formtool_moveup
 * Purpose:  move selected element(s) up in a list
 * -------------------------------------------------------------
 */
function smarty_function_formtool_moveup($params, &$smarty)
{
    // required parameters
    foreach (array('name', 'save') as $item) {
        if (!array_key_exists($item, $params) || empty($params[$item])) {
            $smarty->trigger_error("formtool_moveup: missing '$item' parameter");
            return;
        } else {
            $local = "_$item";
            $$local = "this.form.elements['{$params[$item]}']";
        }
    }

    // optional parameters
    foreach (array('class', 'style') as $item) {
        $local = "_$item";
        $$local = (array_key_exists($item, $params))
                ? " $item=\"{$params[$item]}\""
                : '';
    }

    $_button_text = isset($params['button_text']) ? $params['button_text'] : 'Move Up';

    return "<input$_class$_style type=\"button\" value=\"$_button_text\" onClick=\"javascript:formtool_moveup($_name,$_save);\" />\n";
}

/* vim: set expandtab: */

?>
