{**
 * plugins/blocks/mostRead/block.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * A plug-in showing the most-read articles of the site.
 *}
<div class="block" id="sidebarMostRead">
	<span class="blockTitle">{translate key="plugins.block.mostRead.title"}</span>
	{* Dynamic selection of the current report (with graceful fallback) *}
	<script type="text/javascript">
		$(function() {ldelim}
			$('#sidebarMostRead .blockTitle').after(
				{strip}
					'<br />
					<select class="selectMenu" id="timeSpans" onchange="return selectReport();">
						{foreach from=$timeSpans key=timeSpanName item=timeSpanKey}
							<option value="{$timeSpanName}"{if $timeSpanName == $defaultTimeSpan} selected="selected"{/if}>{translate key=$timeSpanKey}</option>
						{/foreach}
					</select>
					<br />'
				{/strip}
			);
			updateReports();
		{rdelim});
		
		function selectReport() {ldelim}
			var timeSpan = $('#timeSpans').val();
			$('.mostReadArticleReport.selected').removeClass('selected');
			$('#mostReadArticles-' + timeSpan).addClass('selected');
			updateReports();
			return true;
		{rdelim}
		
		function updateReports() {ldelim}
			$('.mostReadArticleReport').hide();
			$('.mostReadArticleReport.selected').show();		
		{rdelim}
	</script>
	{foreach from=$articleRanking key=timeSpanName item=report}
		<div class="mostReadArticleReport{if $timeSpanName == $defaultTimeSpan} selected{/if}" id="mostReadArticles-{$timeSpanName}">
			<noscript><br />{translate key=$timeSpans[$timeSpanName]}:<br /></noscript>
			<ol style="padding-left: 20px">
			{foreach from=$report item=articleInfo}
				{if !empty($articleInfo['title'])}
					<li><a href="{$articleInfo.url}">{$articleInfo.title}</a>&nbsp;({$articleInfo.metric})</li>
				{/if}
			{/foreach}
			</ol>
		</div>
	{/foreach}
</div>
