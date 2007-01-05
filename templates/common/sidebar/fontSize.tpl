{**
 * sidebar.tpl
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common site sidebar menu.
 *
 * $Id$
 *}
<div class="block">
	<span class="blockTitle">{translate key="navigation.fontSize"}</span>
	<a href="#" onclick="setFontSize('{translate key="icon.small.alt"}');">{icon name="small"}</a>&nbsp;
	<a href="#" onclick="setFontSize('{translate key="icon.medium.alt"}');">{icon name="medium"}</a>&nbsp;
	<a href="#" onclick="setFontSize('{translate key="icon.large.alt"}');">{icon name="large"}</a>
</div>