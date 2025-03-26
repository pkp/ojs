{**
 * templates/controllers/grid/issues/issueToc.tpl
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Display the issue's table of contents
 *}
<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#issueTocForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

{capture assign=issueTocGridUrl}{url router=PKP\core\PKPApplication::ROUTE_COMPONENT component="grid.toc.TocGridHandler" op="fetchGrid" issueId=$issue->getId() escape=false}{/capture}
{load_url_in_div id="issueTocGridContainer" url=$issueTocGridUrl}
