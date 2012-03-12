{**
 * templates/article/footer.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
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

{if $currentJournal && $currentJournal->getSetting('includeCreativeCommons')}
	<br /><br />
	<a rel="license" target="_new" href="http://creativecommons.org/licenses/by/3.0/"><img alt="Creative Commons License" style="border-width:0" src="http://i.creativecommons.org/l/by/3.0/80x15.png" /></a>
	<br />
	This <span xmlns:dc="http://purl.org/dc/elements/1.1/" href="http://purl.org/dc/dcmitype/Text" rel="dc:type">work</span> is licensed under a <a target="_new" rel="license" href="http://creativecommons.org/licenses/by/3.0/">Creative Commons Attribution 3.0 License</a>.
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
		if (url.indexOf('?') > -1) openRTWindowWithToolbar(url + '&defineTerm=' + term);
		else openRTWindowWithToolbar(url + '?defineTerm=' + term);
	}

	if(document.captureEvents) {
		document.captureEvents(Event.DBLCLICK);
	}
	document.ondblclick = new Function("openSearchTermWindow('{/literal}{url page="rt" op="context" path=$articleId|to_array:$galleyId:$defineTermsContextId escape=false}{literal}')");
// -->
{/literal}
</script>
{/if}

{get_debug_info}
{if $enableDebugStats}{include file=$pqpTemplate}{/if}
</div> <!-- container -->
</body>
</html>

