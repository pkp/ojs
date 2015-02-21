{**
 * templates/citation/citationEditor.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Citation editing assistant.
 *}

<script type="text/javascript">
	$(function() {ldelim}
		{if $unprocessedCitations !== false}
			// Activate "Refresh Citation List" button.
			$('#refreshCitationListButton').click(function() {ldelim}
				var $citationGrid = $('#citationGridContainer');

				// Activate the throbber.
				actionThrobber('#citationGridContainer');

				// Trigger the throbber.
				$citationGrid.triggerHandler('actionStart');

				// Reload the citation list.
				$.getJSON('{$citationGridUrl}&refresh=1', function(jsonData) {ldelim}
					// Stop the throbber.
					$citationGrid.triggerHandler('actionStop');

					if (jsonData.status === true) {ldelim}
						// Replace the grid.
						$citationGrid.html(jsonData.content);
					{rdelim} else {ldelim}
						// Display the error message.
						alert(jsonData.content);
					{rdelim}

					// Check whether all missing citations
					// have been added.
					var unprocessedCitationIds = [{strip}
						{foreach name=unprocessedCitations from=$unprocessedCitations item=unprocessedCitation}
							{$unprocessedCitation->getId()}
							{if !$smarty.foreach.unprocessedCitations.last},{/if}
						{/foreach}
					{/strip}];
					var missingIds = false;
					for (var i in unprocessedCitationIds) {ldelim}
						if ($('#component-grid-citation-citationgrid-row-'+unprocessedCitationIds[i]).length == 0) {ldelim}
							missingIds = true;
							break;
						{rdelim}
					{rdelim}

					// Remove the refresh button if all originally
					// missing citations have been processed by now.
					if (!missingIds) {ldelim}
						$('#refreshCitationListMessage').remove();
					{rdelim}
				{rdelim});
			{rdelim});
		{/if}

		// Vertical splitter.
		$('#citationEditorCanvas').splitter({ldelim}
			splitVertical:true,
			A:$('#citationEditorNavPane'),
			minAsize:200,
			B:$('#citationEditorDetailPane'),
			minBsize:300
		{rdelim});

		// Main tabs.
		$mainTabs = $('#citationEditorMainTabs').tabs({ldelim}
			show: function(e, ui) {ldelim}
				// Make sure the citation editor is correctly sized when
				// opened for the first time.
				if (ui.panel.id == 'citationEditorTabEdit') {ldelim}
					$('#citationEditorCanvas').triggerHandler('splitterRecalc');
				{rdelim}
				{if !$citationEditorConfigurationError}
					if (ui.panel.id == 'citationEditorTabExport') {ldelim}
						$('#citationEditorExportPane').html('<div id="citationEditorExportThrobber" class="throbber"></div>');
						$('#citationEditorExportThrobber').show();

						// Re-load export tab whenever it is shown.
						$.getJSON('{$citationExportUrl}', function(jsonData) {ldelim}
							if (jsonData.status === true) {ldelim}
								$("#citationEditorExportCanvas").replaceWith(jsonData.content);
							{rdelim} else {ldelim}
								// Alert that loading failed
								alert(jsonData.content);
							{rdelim}
						{rdelim});
					{rdelim}
				{/if}
			{rdelim}
		{rdelim});

		{if !$introductionHide}
			// Feature to disable introduction message.
			$('#introductionHide').click(function() {ldelim}
				$.getJSON(
					'{url router=$smarty.const.ROUTE_COMPONENT component="api.user.UserApiHandler" op="updateUserMessageState"}?setting-name=citation-editor-hide-intro&setting-value='+($(this).attr('checked')===true ? 'true' : 'false'),
					function(jsonData) {ldelim}
						if (jsonData.status !== true) {ldelim}
							alert(jsonData.content);
						{rdelim}
					{rdelim}
				);
			{rdelim});
		{/if}

		{if $citationEditorConfigurationError}
			// Disable editor when not properly configured.
			$mainTabs.tabs('option', 'disabled', [1, 2]);
		{/if}

		// Throbber feature (binds to ajaxAction()'s 'actionStart' event).
		actionThrobber('#citationEditorDetailCanvas');

		//
		// Fullscreen feature.
		//
		/**
		 * Opera has an ugly bug in connection with
		 * vertical resize and absolute positioning.
		 * This function works around this bug with an
		 * equally ugly hack.
		 */
		var operaVerticalResizeBugWorkaround = function() {ldelim}
			if ($.browser.opera) {ldelim}
				// Opera needs to be remembered that the export
				// pane is positioned absolutely.
				// We do this by resizing, taking a breath so that
				// Opera repaints and then resizing again.
				$('#citationEditorExportPane>.scrollable').css('bottom', '29px');
				setTimeout(function() {ldelim}
					$('#citationEditorExportPane>.scrollable').css('bottom', '30px');
				{rdelim}, 250);
			{rdelim}
		{rdelim};

		var $citationEditor = $('#citationEditor');
		var beforeFullscreen;
		$('#fullScreenButton').click(function() {ldelim}
			if ($citationEditor.hasClass('fullscreen')) {ldelim}
				// Going back to normal:
				// 1) Unbind tab change event (for Opera compat).
				$('.composite-ui>.ui-tabs').unbind('tabsselect');

				// 2) Remove additional CSS.
				$citationEditor
					// Remove the fullscreen layout.
					.removeClass('fullscreen')
					// Remove IE7 width hack (see below, window resizing event handling for IE).
					.css('width', '');

				// 3) Restore original values.
				$('.composite-ui>.ui-tabs').css('margin-top', beforeFullscreen.topMargin);
				$('.composite-ui>.ui-tabs div.main-tabs').each(function() {ldelim}
					$(this).height(beforeFullscreen.height);
				{rdelim});
				$('.composite-ui div.two-pane>div.left-pane .scrollable').first().height(beforeFullscreen.height-30);
				$('body, html').css('overflow', 'auto'); // html for IE7, body for the rest
				window.scroll(beforeFullscreen.x, beforeFullscreen.y);
				$(this).text('{translate key="common.fullscreen"}');
			{rdelim} else {ldelim}
				// Going fullscreen:
				// 1) Save current values.
				beforeFullscreen = {ldelim}
					topMargin: $('.composite-ui>.ui-tabs').css('margin-top'),
					height: $('.composite-ui>.ui-tabs div.main-tabs').not('.ui-tabs-hide').height(),
					x: $(window).scrollLeft(),
					y: $(window).scrollTop()
				{rdelim};

				// 2) Set values needed to go fullscreen.
				$('body, html').css('overflow', 'hidden'); // html for IE7, body for the rest
				$citationEditor.addClass('fullscreen');
				$('.composite-ui>.ui-tabs').css('margin-top', '0');
				canvasHeight=$(window).height()-$('ul.main-tabs').height();
				$('.composite-ui>.ui-tabs div.main-tabs').each(function() {ldelim}
					$(this).height(canvasHeight);
				{rdelim});
				$('.composite-ui div.two-pane>div.left-pane .scrollable').first().height(canvasHeight-30);
				window.scroll(0,0);
				$(this).text('{translate key="common.fullscreenOff"}');

				// 3) Bind event to tab change (for Opera compat).
				$('.composite-ui>.ui-tabs').bind('tabsselect', function() {ldelim}
					window.scroll(0,0);
				{rdelim});
			{rdelim}

			// Resize 2-pane layout.
			$('.two-pane').css('width', '100%').triggerHandler('splitterRecalc');

			// Opera vertical resize bug workaround.
			operaVerticalResizeBugWorkaround();
		{rdelim});

		// Resize citation editor in fullscreen mode
		// when the browser window is being resized.
		$(window).resize(function() {ldelim}
			// Adjust editor height to new window height when in fullscreen mode.
			if ($citationEditor.hasClass('fullscreen')) {ldelim}
				// IE7 needs to be told explicitly that we
				// really want to maintain 100% width.
				$citationEditor.width($(window).width());

				// Correctly adapt the height of scrollable areas.
				canvasHeight=$(window).height()-$('ul.main-tabs').height();
				$('.composite-ui>.ui-tabs div.main-tabs').each(function() {ldelim}
					$(this).height(canvasHeight);
				{rdelim});
				$('.composite-ui div.two-pane>div.left-pane .scrollable').first().height(canvasHeight-30);
			{rdelim}

			// Adjust 2-pane layout to new window width.
			$('.two-pane').css('width', '100%').triggerHandler('splitterRecalc');

			// Opera vertical resize bug workaround.
			operaVerticalResizeBugWorkaround();
		{rdelim});
	{rdelim});
</script>

{if $unprocessedCitations !== false}
	<div id="refreshCitationListMessage" class="composite-ui">
		<p>
			<span class="formError">{translate key="submission.citations.editor.unprocessedCitations"}</span>
		</p>
		<button id="refreshCitationListButton" type="button" title="{translate key="submission.citations.editor.unprocessedCitationsButtonTitle"}">{translate key="submission.citations.editor.unprocessedCitationsButton"}</button>
	</div>
{/if}
<div id="citationEditor" class="composite-ui">
	<div id="citationEditorMainTabs">
		<button id="fullScreenButton" type="button">{translate key="common.fullscreen"}</button>
		<ul class="main-tabs">
			{if !$introductionHide}<li><a href="#citationEditorTabIntroduction">{translate key="submission.citations.editor.introduction"}</a></li>{/if}
			<li><a href="#citationEditorTabEdit">{translate key="submission.citations.editor.edit"}</a></li>
			<li><a href="#citationEditorTabExport">{translate key="submission.citations.editor.export"}</a></li>
		</ul>
		{if !$introductionHide}
			<div id="citationEditorTabIntroduction" class="main-tabs">
				<div id="citationEditorIntroductionCanvas" class="canvas">
					<div id="citationEditorIntroductionPane" class="pane text-pane scrollable">
						<div class="help-message">
							{capture assign="citationSetupUrl"}{url page="manager" op="setup" path="3" anchor="metaCitationEditing"}{/capture}
							{if $citationEditorConfigurationError}
								{translate key=$citationEditorConfigurationError citationSetupUrl=$citationSetupUrl}
								{if $showIntroductoryMessage}{translate key="submission.citations.editor.introduction.introductionMessage" citationSetupUrl=$citationSetupUrl}{/if}
							{else}
								{if $showIntroductoryMessage}{translate key="submission.citations.editor.introduction.introductionMessage" citationSetupUrl=$citationSetupUrl}{/if}
								<input id="introductionHide" type="checkbox" />{translate key="submission.citations.editor.details.dontShowMessageAgain"}
							{/if}
						</div>
					</div>
				</div>
			</div>
		{/if}
		<div id="citationEditorTabEdit" class="main-tabs">
			<div id="citationEditorCanvas" class="canvas two-pane">
				<div id="citationEditorNavPane" class="pane left-pane">
					{if !$citationEditorConfigurationError}
						{load_url_in_div id="citationGridContainer" loadMessageId="submission.citations.editor.loadMessage" url="$citationGridUrl"}
					{/if}
				</div>
				<div id="citationEditorDetailPane" class="pane right-pane">
					<table class="pane_header"><thead><tr><th>&nbsp;</th></tr></thead></table>
					<div id="citationEditorDetailCanvas" class="canvas">
						<div class="wrapper scrollable">
							<div class="help-message">{$initialHelpMessage}</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div id="citationEditorTabExport" class="main-tabs">
			<div id="citationEditorExportCanvas" class="canvas">
				<div id="citationEditorExportPane" class="pane text-pane"></div>
			</div>
		</div>
	</div>
</div>

