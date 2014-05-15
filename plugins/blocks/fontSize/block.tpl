{**
 * plugins/blocks/fontSize/block.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common site sidebar menu -- font size selector.
 *
 *}

<!-- Add javascript required for font sizer -->
<script type="text/javascript">{literal}
	<!--
	$(function(){
		fontSize("#sizer", "body", 9, 16, 32, "{/literal}{$basePath|escape:"javascript"}{literal}"); // Initialize the font sizer
	});
	// -->
{/literal}</script>

<div class="block" id="sidebarFontSize" style="margin-bottom: 4px;">
	<span class="blockTitle">{translate key="plugins.block.fontSize.title"}</span>
	<div id="sizer"></div>
</div>
<br />
