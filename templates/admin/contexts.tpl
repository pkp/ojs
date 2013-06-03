{**
 * templates/admin/journals.tpl
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of journals in site administration.
 *
 *}
{strip}
{assign var="pageTitle" value="journal.journals"}
{include file="common/header.tpl"}
{/strip}

{url|assign:journalsUrl router=$smarty.const.ROUTE_COMPONENT component="grid.admin.journal.JournalGridHandler" op="fetchGrid" escape=false}
{load_url_in_div id="journalGridContainer" url=$journalsUrl}

{include file="common/footer.tpl"}
