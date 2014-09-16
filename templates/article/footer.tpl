{**
 * templates/article/footer.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Article View -- Footer component.
 *}

{if $sharingEnabled}
<!-- start AddThis -->
	{if isset($sharingDropDownMenu)}
		{if isset($sharingUserName)}
			<script type="text/javascript">
				var addthis_pub = '{$sharingUserName}';
			</script>
		{/if}
		<br />
		<br />
		<div class="addthis_container">
			<a href="http://www.addthis.com/bookmark.php"
				onmouseover="return addthis_open(this, '', '{$sharingArticleURL|escape:"javascript"}', '{$sharingArticleTitle|escape:"javascript"}')"
				onmouseout="addthis_close()" onclick="return addthis_sendto()">
					<img src="{$sharingButtonUrl}" width="{$sharingButtonWidth}" height="{$sharingButtonHeight}" border="0" alt="Bookmark and Share" style="border:0;padding:0" />
			</a>
			<script type="text/javascript" src="http://s7.addthis.com/js/200/addthis_widget.js"></script>
		</div>
	{else}
		<a href="http://www.addthis.com/bookmark.php"
			onclick="window.open('http://www.addthis.com/bookmark.php?pub={$sharingUserName|escape:"url"}&amp;url={$sharingRequestURL|escape:"url"}&amp;title={$sharingArticleTitle|escape:"url"}', 'addthis',
			                     'scrollbars=yes,menubar=no,width=620,height=520,resizable=yes,toolbar=no,location=no,status=no');
			         return false;"
			title="Bookmark using any bookmark manager!" target="_blank">
				<img src="{$sharingButtonUrl}" width="{$sharingButtonWidth}" height="{$sharingButtonHeight}" border="0" alt="Bookmark and Share" style="border:0;padding:0" />
		</a>
	{/if}
<!-- end AddThis -->
{/if}

{if $currentJournal}
	{if $currentJournal->getSetting('includeCopyrightStatement')}
		<br/><br/>
		{translate key="submission.copyrightStatement" copyrightYear=$article->getCopyrightYear()|escape copyrightHolder=$article->getLocalizedCopyrightHolder()|escape}
	{/if}
	{if $currentJournal->getSetting('includeLicense') && $ccLicenseBadge}
		<br /><br />
		{$ccLicenseBadge}
	{/if}
{/if}

{call_hook name="Templates::Article::Footer::PageFooter"}
{if $pageFooter}
<br /><br />
{$pageFooter}
{/if}
</div><!-- content -->
</div><!-- main -->
</div><!-- body -->

{if $defineTermsContextId}
<script type="text/javascript">
{literal}
<!--
	// Open "Define Terms" context when double-clicking any text
	function openSearchTermWindow(url) {
		var term;
		if (window.getSelection) {
			term = window.getSelection();
		} else if (document.getSelection) {
			term = document.getSelection();
		} else if(document.selection && document.selection.createRange && document.selection.type.toLowerCase() == 'text') {
			var range = document.selection.createRange();
			term = range.text;
		}
		if (term != ""){
			if (url.indexOf('?') > -1) openRTWindowWithToolbar(url + '&defineTerm=' + term);
			else openRTWindowWithToolbar(url + '?defineTerm=' + term);
		}
	}

	if(document.captureEvents) {
		document.captureEvents(Event.DBLCLICK);
	}

	// Make sure to only open the reading tools when double clicking within the galley	
	if (document.getElementById('inlinePdfResizer')) {
		context = document.getElementById('inlinePdfResizer');	
	}
	else if (document.getElementById('content')) {
		context = document.getElementById('content');	
	}
	else {
		context = document;
	}

	context.ondblclick = new Function("openSearchTermWindow('{/literal}{url page="rt" op="context" path=$articleId|to_array:$galleyId:$defineTermsContextId escape=false}{literal}')");
// -->
{/literal}
</script>
{/if}

{get_debug_info}
{if $enableDebugStats}{include file=$pqpTemplate}{/if}
</div> <!-- container -->
</body>
</html>
