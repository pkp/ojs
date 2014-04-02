{**
 * templates/issue/issueGalley.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Issue galley view for PDF files.
 *}
{include file="issue/header.tpl"}
{url|assign:"pdfUrl" op="viewFile" path=$issueId|to_array:$galley->getBestGalleyId($currentJournal)}

<div id="pdfDownloadLinkContainer">
	<a class="action pdf" id="pdfDownloadLink" target="_parent" href="{url op="download" path=$issueId|to_array:$galley->getBestGalleyId($currentJournal)}">{translate key="article.pdf.download"}</a>
</div>

<iframe src="http://docs.google.com/viewer?url={$pdfUrl|escape:url}&embedded=true" style="width:100%; height:800px;" frameborder="0"></iframe>

{include file="common/footer.tpl"}
