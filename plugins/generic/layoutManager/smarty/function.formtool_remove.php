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
 * Name:     formtool_remove
 * Purpose:  remove selected element(s) from a list
 * -------------------------------------------------------------
 */
function smarty_function_formtool_remove($params, &$smarty)
{
    // required parameters
    foreach (array('from', 'save') as $item) {
        if (!array_key_exists($item, $params) || empty($params[$item])) {
            $smarty->trigger_error("formtool_remove: missing '$item' parameter");
            return;
        } else {
            $local = "_$item";
            $$local = "this.form.elements['{$params[$item]}']";
        }
    }

    // optional parameters
    foreach (array('counter') as $item) {
        $local = "_$item";
        $$local = (array_key_exists($item, $params))
                ? "this.form.elements['{$params[$item]}']"
                : 'null';
    }
    foreach (array('class', 'style') as $item) {
        $local = "_$item";
        $$local = (array_key_exists($item, $params))
                ? " $item=\"{$params[$item]}\""
                : '';
    }

    // handle 'all' parameter
    $_all = "false";
    $_default_text = 'Remove';
    if (array_key_exists('all', $params)) {
        if ((bool)$params['all']) {
            $_all = 'true';
            $_default_text = 'Remove All';
        }
    }

    $_button_text = (array_key_exists('button_text', $params)) ? $params['button_text'] : $_default_text;

    return "<input$_class$_style type=\"button\" value=\"$_button_text\" onClick=\"javascript:formtool_remove($_from,$_save,$_counter,$_all);\" />\n";
}

/* vim: set expandtab: */

?>
