{**
 * fontSize.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common site sidebar menu -- font size selector.
 *
 * $Id$
 *}
<div class="block" id="sidebarFontSize">
	<span class="blockTitle">{translate key="navigation.fontSize"}</span>
	<a href="#" onclick="setFontSize('{translate key="icon.small.alt"}');" class="icon">{icon name="small"}</a>&nbsp;
	<a href="#" onclick="setFontSize('{translate key="icon.medium.alt"}');" class="icon">{icon name="medium"}</a>&nbsp;
	<a href="#" onclick="setFontSize('{translate key="icon.large.alt"}');" class="icon">{icon name="large"}</a>
</div>
