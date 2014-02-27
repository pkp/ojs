{**
 * index.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Journal author index.
 *
 * $Id$
 *}
{strip}
{if $pageToDisplay == 'active'}
        {assign var="helpLink" value='<a href="https://submit.escholarship.org/help/journals/authors.html" target="_blank"><img src="'|concat:$baseUrl:'/eschol/images/help_A.png"></a>'}
        {translate|assign:"pageTitleTranslated" key="common.queue.long.$pageToDisplay.with.help" helpLink=$helpLink}
	{assign var="pageCrumbTitle" value="common.queue.long.$pageToDisplay}
{else}
	{assign var="pageTitle" value="common.queue.long.$pageToDisplay"}
{/if}
{include file="common/header.tpl"}
{/strip}

<ul class="menu">
	<li{if ($pageToDisplay == "active")} class="current"{/if}><a href="{url op="index" path="active"}">{translate key="common.queue.short.active"}</a></li>
	<li{if ($pageToDisplay == "completed")} class="current"{/if}><a href="{url op="index" path="completed"}">{translate key="common.queue.short.completed"}</a></li>
</ul>

<br />

{include file="author/$pageToDisplay.tpl"}
<div id="submitStart">
<h3>{translate key="author.submit.startHereTitle"}</h3>{* 20110829 BLH Increased heading weight from h4 to h3 *}
{url|assign:"submitUrl" op="submit"}
{translate submitUrl=$submitUrl key="author.submit.startHereLink"}<br />
</div>

{call_hook name="Templates::Author::Index::AdditionalItems"}

{include file="common/footer.tpl"}

