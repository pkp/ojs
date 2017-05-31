{**
 * templates/controllers/subscriptions/individualSubscriptionsGridFilter.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Filter template for individual subscriptions grid.
 *}
<script type="text/javascript">
	// Attach the form handler to the form.
	$('#individualSubscriptionsSearchForm').pkpHandler('$.pkp.controllers.form.ClientFormHandler',
		{ldelim}
			trackFormChanges: false
		{rdelim}
	);
</script>
<form class="pkp_form filter" id="individualSubscriptionsSearchForm" action="{url router=$smarty.const.ROUTE_COMPONENT component="grid.subscriptions.IndividualSubscriptionsGridHandler" op="fetchGrid"}" method="post">
	{csrf}
	{fbvFormArea id="individualSubscriptionsSearchFormArea"}
		{fbvFormSection title="common.search" for="search"}
			{fbvElement type="text" name="search" id="search" value=$filterSelectionData.search size=$fbvStyles.size.LARGE inline="true"}
		{/fbvFormSection}
		{fbvFormButtons hideCancel=true submitText="common.search"}
	{/fbvFormArea}
</form>
