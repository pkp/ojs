{**
 * plugins/blocks/fontSize/block.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common site sidebar menu -- font size selector.
 *
 *}
<script type="text/javascript">
        $(function() {ldelim}
		$('#body').jfontsize({ldelim}
			btnMinusClasseId: '#jfontsize-minus',
			btnDefaultClasseId: '#jfontsize-default',
			btnPlusClasseId: '#jfontsize-plus'
		{rdelim});
	{rdelim});
</script>

<div class="block" id="sidebarFontSize" style="margin-bottom: 4px;">
	<span class="blockTitle">{translate key="plugins.block.fontSize.title"}</span>
	<div id="sizer">
		<a id="jfontsize-minus" class="jfontsize-button" title="{translate key="plugins.block.fontSize.small"}" href="#">A-</a>
		<a id="jfontsize-default" class="jfontsize-button" title="{translate key="plugins.block.fontSize.medium"}" href="#">A</a>
		<a id="jfontsize-plus" class="jfontsize-button" title="{translate key="plugins.block.fontSize.large"}" href="#">A+</a>
	</div>
</div>
<br />
