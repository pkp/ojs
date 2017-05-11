{**
 * templates/locales.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Present the locales grid in a basic tab set that can be extended as needed.
 *}
<script type="text/javascript">
	// Attach the JS file tab handler.
	$(function() {ldelim}
		$('#translationTabs').pkpHandler('$.pkp.controllers.TabHandler', {ldelim}
			notScrollable: true
		{rdelim});
	{rdelim});
</script>

<div id="translationTabs" class="pkp_controllers_tab">
	<ul>
		<li><a href="{url component="plugins.generic.translator.controllers.grid.LocaleGridHandler" op="fetchGrid" escape=false tabsSelector="#translationTabs"}">{translate key="plugins.generic.translator.availableLocales"}</a></li>
	</ul>
</div>
