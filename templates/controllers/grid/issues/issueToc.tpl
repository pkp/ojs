{**
 * templates/controllers/grid/issues/issueToc.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display the issue's table of contents
 *}
<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#issueTocForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

{url|assign:issueTocGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.toc.TocGridHandler" op="fetchGrid" issueId=$issue->getId() escape=false}
{load_url_in_div id="issueTocGridContainer" url=$issueTocGridUrl}
