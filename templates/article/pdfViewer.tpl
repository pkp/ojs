{**
 * templates/article/pdfViewer.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Embedded PDF viewer.
 *}

{* The target="_parent" is for the sake of iphones, which present scroll problems otherwise. *}
<div id="pdfDownloadLinkContainer">
	<a class="action pdf" id="pdfDownloadLink" target="_parent" href="{url op="download" path=$articleId|to_array:$galley->getBestGalleyId($currentJournal)}">{translate key="article.pdf.download"}</a>
</div>

{url|assign:"pdfUrl" op="viewFile" path=$articleId|to_array:$galley->getBestGalleyId($currentJournal) escape=false}
{translate|assign:"noPluginText" key='article.pdf.pluginMissing'}
<script type="text/javascript"><!--{literal}
	$(document).ready(function(){
		if ($.browser.webkit) { // PDFObject does not correctly work with safari's built-in PDF viewer
			var embedCode = "<object id='pdfObject' type='application/pdf' data='{/literal}{$pdfUrl|escape:'javascript'}{literal}' width='99%' height='99%'><div id='pluginMissing'>{/literal}{$noPluginText|escape:'javascript'}{literal}</div></object>";
			$("#inlinePdf").html(embedCode);
			if($("#pluginMissing").is(":hidden")) {
				$('#fullscreenShow').show();
				$("#inlinePdf").resizable({ containment: 'parent', handles: 'se' });
			} else { // Chrome Mac hides the embed object, obscuring the text.  Reinsert.
				$("#inlinePdf").html('{/literal}<div id="pluginMissing">{$noPluginText|escape:"javascript"}</div>{literal}');
			}
		} else {
			var success = new PDFObject({ url: "{/literal}{$pdfUrl|escape:'javascript'}{literal}" }).embed("inlinePdf");
			if (success) {
				// PDF was embedded; enbale fullscreen mode and the resizable widget
				$('#fullscreenShow').show();
				$("#inlinePdfResizer").resizable({ containment: 'parent', handles: 'se' });
			}
		}
	});
{/literal}
// -->
</script>
<div id="inlinePdfResizer">
	<div id="inlinePdf" class="ui-widget-content">
		<div id='pluginMissing'>{translate key="article.pdf.pluginMissing"}</div>
	</div>
</div>
<p>
	<a class="action" href="#" id="fullscreenShow">{translate key="common.fullscreen"}</a>
	<a class="action" href="#" id="fullscreenHide">{translate key="common.fullscreenOff"}</a>
</p>
<div style="clear: both;"></div>
