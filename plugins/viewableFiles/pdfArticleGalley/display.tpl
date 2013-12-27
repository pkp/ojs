{if $galley}
	<script src="{$pluginJSPath}/inlinePdf.js"></script>
	<script src="{$baseUrl}/lib/pkp/lib/pdfobject/pdfobject.js"></script>

	{url|assign:"pdfUrl" op="viewFile" path=$articleId|to_array:$galley->getBestGalleyId($currentJournal) escape=false}

	{translate|assign:"noPluginText" key='article.pdf.pluginMissing'}
	<script type="text/javascript"><!--{literal}
		$(document).ready(function(){
			if ($.browser.msie || $.browser.webkit || $.browser.mozilla) { // PDFObject does not correctly work with safari's built-in PDF viewer
				var embedCode = "<object id='pdfObject' type='application/pdf' data='{/literal}{$pdfUrl|escape:'javascript'}{literal}' width='99%' height='800px'><div id='pluginMissing'>{/literal}{$noPluginText|escape:'javascript'}{literal}</div></object>";
				$("#articlePdf").html(embedCode);
				if($("#pluginMissing").is(":hidden")) {
					$('#fullscreenShow').show();
					$("#articlePdf").resizable({ containment: 'parent', handles: 'se' });
				} else { // Chrome Mac hides the embed object, obscuring the text.  Reinsert.
					$("#articlePdf").html('{/literal}{$noPluginText|escape:"javascript"}{literal}');
				}
			} else {
				var success = new PDFObject({ url: "{/literal}{$pdfUrl|escape:'javascript'}{literal}" }).embed("articlePdf");
				if (success) {
					// PDF was embedded; enable fullscreen mode and the resizable widget
					$('#fullscreenShow').show();
						$("#articlePdfResizer").resizable({ containment: 'parent', handles: 'se' });
					}
				}
			});
			{/literal}
		// -->
	</script>
	<div id="articlePdfResizer">
		<div id="articlePdf" class="ui-widget-content">
			{translate key="article.pdf.pluginMissing"}
		</div>
	</div>
	<p>
		{* The target="_parent" is for the sake of iphones, which present scroll problems otherwise. *}
		<a class="action" target="_parent" href="{url op="download" path=$articleId|to_array:$galley->getBestGalleyId($currentJournal)}">{translate key="article.pdf.download"}</a>
		<a class="action" href="#" id="fullscreenShow">{translate key="common.fullscreen"}</a>
		<a class="action" href="#" id="fullscreenHide">{translate key="common.fullscreenOff"}</a>
	</p>
{/if}
