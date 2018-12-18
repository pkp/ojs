{**
 * templates/admin/journals.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of journals in site administration.
 *
 *}
{include file="common/header.tpl" pageTitle="journal.journals"}

<div class="pkp_page_content pkp_page_admin">
	{capture assign=journalsUrl}{url router=$smarty.const.ROUTE_COMPONENT component="grid.admin.context.ContextGridHandler" op="fetchGrid" escape=false}{/capture}
	{load_url_in_div id="journalGridContainer" url=$journalsUrl refreshOn="form-success"}
</div><!-- .pkp_page_content -->

{include file="common/footer.tpl"}
