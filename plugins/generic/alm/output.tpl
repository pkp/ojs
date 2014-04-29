{**
 * plugins/generic/alm/output.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * ALM plugin settings
 *
 *}

<div class="separator"></div>
<a name="alm"></a>
<h4>{translate key="plugins.generic.alm.title"}</h4>
<div id="alm" class="alm"><div id="loading">{translate key="plugins.generic.alm.loading"}</div></div>

<br />
<span style="float: right"><sub>Metrics powered by <a href="http://pkp-alm.lib.sfu.ca/">PLOS ALM</a><sub></span>
<br />

<script type="text/javascript">
	options = {ldelim}
		almStatsJson: $.parseJSON('{$almStatsJson|escape:"javascript"}'),
		additionalStatsJson: $.parseJSON('{$additionalStatsJson|escape:"javascript"}'),
		baseUrl: '{$smarty.const.ALM_BASE_URL}',
		minItemsToShowGraph: {ldelim}
			minEventsForYearly: 0,
			minEventsForMonthly: 0,
			minEventsForDaily: 0,
			minYearsForYearly: 0,
			minMonthsForMonthly: 0,
			minDaysForDaily: 0
		{rdelim},
		hasIcon: ['wikipedia', 'scienceseeker', 'researchblogging', 'pubmed', 'nature', 'mendeley', 'facebook', 'crossref', 'citeulike', 'ojsViews'],
		categories: [{ldelim} name: "html", display_name: '{translate key="plugins.generic.alm.categories.html"}', tooltip_text: '{translate key="plugins.generic.alm.categories.html.description"|escape:"jsparam"}' {rdelim},
			{ldelim} name: "pdf", display_name: '{translate key="plugins.generic.alm.categories.pdf"}', tooltip_text: '{translate key="plugins.generic.alm.categories.pdf.description"|escape:"jsparam"}' {rdelim},
			{ldelim} name: "likes", display_name: '{translate key="plugins.generic.alm.categories.likes"}', tooltip_text: '{translate key="plugins.generic.alm.categories.likes.description"|escape:"jsparam"}' {rdelim},
			{ldelim} name: "shares", display_name: '{translate key="plugins.generic.alm.categories.shares"}', tooltip_text: '{translate key="plugins.generic.alm.categories.shares.description"|escape:"jsparam"}' {rdelim},
			{ldelim} name: "citations", display_name: '{translate key="plugins.generic.alm.categories.citations"}', tooltip_text: '{translate key="plugins.generic.alm.categories.citations.description"|escape:"jsparam"}' {rdelim}],
		vizDiv: "#alm"
	{rdelim}

	// Import JQuery 1.10 version, needed for the tooltip plugin
	// that we use below. jQuery.noConflict puts the old $ back.
	$.getScript('{$jqueryImportPath}', function() {ldelim}
		$.getScript('{$tooltipImportPath}', function() {ldelim}
			// Assign the last inserted JQuery version to a new variable, to avoid
			// conflicts with the current version in $ variable.
			options.jQuery = $;
			var almviz = new AlmViz(options);
			almviz.initViz();
			jQuery.noConflict(true);
		{rdelim});
	{rdelim});

</script>