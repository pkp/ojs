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
 * Name:     formtool_init
 * Purpose:  initialize formtool
 * -------------------------------------------------------------
 */
function smarty_function_formtool_init($params, &$smarty)
{
    if (!empty($params['src'])) {
    	return '<script type="text/javascript" language="JavaScript" src="'.$params['src'].'"></script>' . "\n";
    } else {
        $smarty->trigger_error("formtool_init: missing src parameter");
    }
}

/* vim: set expandtab: */

?>
