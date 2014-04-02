{**
 * plugins/generic/googleViewer/index.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Embedded PDF viewer using Google Doc embedder service.
 *}

<div id="pdfDownloadLinkContainer">
	<a class="action pdf" id="pdfDownloadLink" target="_parent" href="{url op="download" path=$articleId|to_array:$galley->getBestGalleyId($currentJournal)}">{translate key="article.pdf.download"}</a>
</div>

{url|assign:"pdfUrl" op="viewFile" path=$articleId|to_array:$galley->getBestGalleyId($currentJournal) escape=false}
<iframe src="http://docs.google.com/viewer?url={$pdfUrl|escape:url}&embedded=true" style="width:100%; height:800px;" frameborder="0"></iframe>
