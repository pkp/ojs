{**
 * index.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Journal author submit index/intro.
 *
 * $Id$
 *}

{assign var="pageTitle" value="author.submit"}
{include file="common/header.tpl"}

<div class="subTitle">{translate key="author.submit.stepsToSubmit"}</div>

<br />

<div class="blockTitle">{translate key="author.submit.submitArticle"}</div>
<div class="block">
	<ol>
		<li><a href="{$pageUrl}/author/submit/1">{translate key="author.submit.start"}</a></li>
		<li><a href="{$pageUrl}/author/submit/2">{translate key="author.submit.metadata"}</a></li>
		<li><a href="{$pageUrl}/author/submit/3">{translate key="author.submit.upload"}</a></li>
		<li><a href="{$pageUrl}/author/submit/4">{translate key="author.submit.supplementaryFiles"}</a></li>
		<li><a href="{$pageUrl}/author/submit/5">{translate key="author.submit.confirmation"}</a></li>
	</ol>
</div>

{include file="common/footer.tpl"}
