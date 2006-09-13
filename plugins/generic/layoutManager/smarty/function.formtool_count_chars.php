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
 * Name:     formtool_count_chars
 * Purpose:  move selected element(s) down in a list
 * -------------------------------------------------------------
 */
function smarty_function_formtool_count_chars($params, &$smarty)
{
    if (empty($params['name'])) {
        $smarty->trigger_error("formtool_count_chars: missing 'name' parameter");
        return;
    }
    if (empty($params['limit'])) {
        $smarty->trigger_error("formtool_count_chars: missing 'limit' parameter");
        return;
    }

    $_name = $params['name'];
    $_limit_field = isset($params['limit_field']) ? $params['limit_field'] : $params['name'] . '_limit';
    $_limit = $params['limit'];
    $_alert = isset($params['alert']) && !$params['alert'] ? 'false' : 'true';

    return "onkeyup=\"javascript:formtool_count_chars(this.form.elements['$_name'],this.form.elements['$_limit_field'],$_limit,$_alert)\"";
}

/* vim: set expandtab: */

?>
