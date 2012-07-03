{**
 * plugins/generic/lucene/templates/preResults.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * A template to be included via Templates::Search::SearchResults::PreResults hook.
 *}
<div id="lucenePreResults">
	<p>
		{translate key="plugins.generic.lucene.results.orderBy"}:&nbsp;
		<select id="luceneSearchOrder" name="luceneOrderBy" class="selectMenu">
			{html_options options=$luceneOrderByOptions selected=$orderBy}
		</select>
		&nbsp;
		<select id="luceneSearchDirection" name="luceneOrderDir" class="selectMenu">
			{html_options options=$luceneOrderDirOptions selected=$orderDir}
		</select>
		&nbsp;
		<script type="text/javascript">
			// Get references to the required elements.
			var $orderBySelect = $('#content #luceneSearchOrder');
			var $orderDirSelect = $('#content #luceneSearchDirection');
			var $searchForm = $('#content #searchForm');

			function luceneReorder(useDefaultOrderDir) {ldelim}
				// Copy the order criteria over into the form.
				// We do it this way so that we do not have to
				// create a new template hook just to inject
				// this bit of code into the form.
				$searchForm.find('input[name="orderBy"]').val($orderBySelect.val());
				if (useDefaultOrderDir) {ldelim}
					$searchForm.find('input[name="orderDir"]').val('');
				{rdelim} else {ldelim}
					$searchForm.find('input[name="orderDir"]').val($orderDirSelect.val());
				{rdelim}

				// Resubmit the search form with the new order criteria:
				$searchForm.submit();
			{rdelim}

			$orderBySelect.change(function() {ldelim} luceneReorder(true); {rdelim});
			$orderDirSelect.change(function() {ldelim} luceneReorder(false); {rdelim});
		</script>
	</p>
</div>
