{**
 * plugins/generic/googleViewer/index.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Embedded PDF viewer using Zoho PDF embedder service.
 *}

{url|assign:"pdfUrl" op="viewFile" path=$articleId|to_array:$galley->getBestGalleyId($currentJournal) escape=false}
<iframe src="http://viewer.zoho.com/api/urlview.do?url={$pdfUrl|escape:url}" style="width:100%; height:800px;" frameborder="0"></iframe>
