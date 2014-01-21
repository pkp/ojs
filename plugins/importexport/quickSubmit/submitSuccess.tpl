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

<p>{translate key="plugins.importexport.quickSubmit.successDescription"}</p>  

<p>
	If you uploaded an HTML file, you can add associated image files on the <a href="{url op="editGalley" page="editor" path=$galley->getArticleId()|to_array:$galley->getId()}" class="action">Edit Layout Galley</a> screen.</p>

<p>
	Or return to the <a href="{plugin_url}" class="action">QuickSubmit Plugin</a> to add another article.
</p>

{include file="common/footer.tpl"}
