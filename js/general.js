/**
 * general.js
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Site-wide common JavaScript functions. 
 *
 * $Id$
 */

function confirmAction(url, msg)
{
	if(confirm(msg))
	{
		location.href=url;	
	}
}

function openHelp(url)
{
	window.open(url, 'Help', 'width=500,height=550,screenX=100,screenY=100,toolbar=false');
}
