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
 * Name:     formtool_save
 * Purpose:  initialize a "save" field
 * -------------------------------------------------------------
 */
function smarty_function_formtool_save($params, &$smarty)
{
    if (empty($params['name'])) {
        $smarty->trigger_error("formtool_save: missing 'name' parameter");
        return;
    }
    if (empty($params['save'])) {
        $smarty->trigger_error("formtool_save: missing 'save' parameter");
        return;
    }
    if (empty($params['form'])) {
        $smarty->trigger_error("formtool_save: missing 'form' parameter");
        return;
    }

    $_name = $params['name'];
    $_save = $params['save'];
    $_form = $params['form'];
    
    return "<script type=\"text/javascript\" language=\"JavaScript\"> formtool_save(document.$_form.elements['$_name'],document.$_form.elements['$_save']); </script>\n";
}

/* vim: set expandtab: */

?>
