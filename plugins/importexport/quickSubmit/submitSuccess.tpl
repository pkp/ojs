{**
 * submitSuccess.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display a message indicating that the article was successfuly submitted.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="plugins.importexport.quickSubmit.success"}
{include file="common/header.tpl"}
{/strip}

<p>{translate key="plugins.importexport.quickSubmit.successDescription"} You can now:</p>
<ul>
   <li><a href="{plugin_url}" class="action">Return to the QuickSubmit Plugin</a> to submit another item</li>
   <li><a href="/ojs/index.php/{journalPath}/editor">Go to the table of contents</a> to arrange and publish this issue</li>
   <li><a href="/ojs/index.php/{$journal->getPath()}/editor/submission/{$article->getID()}">Make a correction</a> to the item you just submitted (<a href="https://vimeo.com/32994126" target="_blank">Watch the training video to learn how to make corrections</a>)</li>
   <li><a href="/ojs/index.php/{journalPath}/manager/>Return to the Configure Journals page</a></li>
</ul>

<p>
	If you uploaded an HTML file, you can add associated image files on the <a href="{url op="editGalley" page="editor" path=$galley->getArticleId()|to_array:$galley->getId()}" class="action">Edit Layout Galley</a> screen.</p>



{include file="common/footer.tpl"}
