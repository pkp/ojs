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
 * Name:     formtool_move
 * Purpose:  move selected element(s) to another field
 * -------------------------------------------------------------
 */
function smarty_function_formtool_img_move($params, &$smarty)
{
    // required parameters
    foreach (array('from', 'to', 'save_from', 'save_to') as $item) {
        if (!array_key_exists($item, $params) || empty($params[$item])) {
            $smarty->trigger_error("formtool_move: missing '$item' parameter");
            return;
        } else {
            $local = "_$item";
            $$local = "this.form.elements['{$params[$item]}']";
        }
    }

    // optional parameters
    foreach (array('counter_from', 'counter_to') as $item) {
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

	$img = '';
	if (array_key_exists('img', $params)) {
		$src = $params['img'];
		$img = "<img src=$src />";
	}

    // handle 'all' parameter
    $_all = "false";
    $_default_text = 'Move &gt;';
    if (array_key_exists('all', $params)) {
        if ((bool)$params['all']) {
            $_all = 'true';
            $_default_text = 'Move &gt;&gt;';
        }
    }

    $_button_text = (array_key_exists('button_text', $params)) ? $params['button_text'] : $_default_text;

    return "<button style=\"border-style:none;background-color:transparent\" $_class$_style type=\"button\" value=\"$_button_text\" onClick=\"javascript:formtool_move($_from,$_to,$_save_from,$_save_to,$_counter_from,$_counter_to,$_all);\">\n$img\n</button>\n";
}

/* vim: set expandtab: */

?>
