{**
 * index.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Journal author index.
 *
 * $Id$
 *}

{assign var="pageTitle" value="author.journalAuthor"}
{include file="common/header.tpl"}

<div class="blockTitle">{translate key="author.journalAuthor"}</div>
<div class="block">
	<ul>
		<li><a href="{$pageUrl}/author/submit">{translate key="author.submit"}</a></li>
		<li><a href="{$pageUrl}/author/track">{translate key="author.track"}</a></li>
	</ul>
</div>


{include file="common/footer.tpl"}
