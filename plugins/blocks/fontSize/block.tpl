{**
 * block.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common site sidebar menu -- font size selector.
 *
 * $Id$
 *}
<div class="block" id="sidebarFontSize">
	<span class="blockTitle">{translate key="plugins.block.fontSize.title"}</span>
	<a href="#" onclick="setFontSize('{translate|escape:"jsparam" key="plugins.block.fontSize.small.alt"}');" class="icon">{icon path="$fontSizerPluginPath/" name="small"}</a>&nbsp;
	<a href="#" onclick="setFontSize('{translate|escape:"jsparam" key="plugins.block.fontSize.medium.alt"}');" class="icon">{icon path="$fontSizerPluginPath/" name="medium"}</a>&nbsp;
	<a href="#" onclick="setFontSize('{translate|escape:"jsparam" key="plugins.block.fontSize.large.alt"}');" class="icon">{icon path="$fontSizerPluginPath/" name="large"}</a>
</div>
