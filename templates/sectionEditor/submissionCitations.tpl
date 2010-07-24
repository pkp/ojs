{**
 * submissionCitations.tpl
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Submission citations.
 *}
{strip}
{translate|assign:"pageTitleTranslated" key="submission.page.citations" id=$submission->getId()}
{assign var="pageCrumbTitle" value="submission.citations"}
{include file="common/header.tpl"}
{/strip}

<ul class="menu">
	<li><a href="{url op="submission" path=$submission->getId()}">{translate key="submission.summary"}</a></li>
	{if $canReview}<li><a href="{url op="submissionReview" path=$submission->getId()}">{translate key="submission.review"}</a></li>{/if}
	{if $canEdit}<li><a href="{url op="submissionEditing" path=$submission->getId()}">{translate key="submission.editing"}</a></li>{/if}
	<li><a href="{url op="submissionHistory" path=$submission->getId()}">{translate key="submission.history"}</a></li>
	<li class="current"><a href="{url op="submissionCitations" path=$submission->getId()}">{translate key="submission.citations"}</a></li>
</ul>

{* JavaScript - FIXME: will be moved to JS file as soon as development is done *}
{literal}
<script type="text/javascript" src="/pkp-ojs/lib/pkp/js/splitter.js"></script>

<script type="text/javascript">
	$(function() {
		// Vertical splitter.
		$('#citationEditorCanvas').splitter({
			splitVertical:true,
			A:$('#citationEditorNavPane'),
			minAsize:200,
			B:$('#citationEditorDetailPane'),
			minBsize:300
		});

		// Main tabs.
		$mainTabs = $('#citationEditorMainTabs').tabs({
			show: function(e, ui) {
				// Make sure the citation editor is correctly sized when
				// opened for the first time.
				if (ui.panel.id == 'citationEditorTabEdit') {
					$('#citationEditorCanvas').triggerHandler('resize');
				}
				{/literal}{if !$citationEditorConfigurationError}{literal}
					if (ui.panel.id == 'citationEditorTabExport') {
						$('#citationEditorExportPane').html('<div id="citationEditorExportThrobber" class="throbber"></div>');
						$('#citationEditorExportThrobber').show();
						
						// Re-load export tab whenever it is shown.
						$.getJSON('{/literal}{$citationExportUrl}{literal}', function(jsonData) {
							if (jsonData.status === true) {
								$("#citationEditorExportPane").html(jsonData.content);
							} else {
								// Alert that loading failed
								alert(jsonData.content);
							}
						});
					}
				{/literal}{/if}{literal}
			}
		});

		// Feature to disable introduction message.
		{/literal}{if !$introductionHide}{literal}
			$('#introductionHide').change(function() {
				$.getJSON(
					'{/literal}{url router=$smarty.const.ROUTE_COMPONENT component="api.user.UserApiHandler" op="setUserSetting"}{literal}?setting-name=citation-editor-hide-intro&setting-value='+($(this).attr('checked')===true ? 'true' : 'false'),
					function(jsonData) {
						if (jsonData.status !== true) {
							alert(jsonData.content);
						}
					}
				);
			});
		{/literal}{/if}{literal}

		// Disable editor when not properly configured.
		{/literal}{if $citationEditorConfigurationError}{literal}
			$mainTabs.tabs('option', 'disabled', [1, 2]);
		{/literal}{/if}{literal}

		// Throbber feature (binds to ajaxAction()'s 'actionStart' event).
		actionThrobber('#citationEditorDetailCanvas');

		// Fullscreen feature.
		var $citationEditor = $('#submissionCitations');
		var beforeFullscreen;
		$('#fullScreenButton').click(function() {
			if ($citationEditor.hasClass('fullscreen')) {
				// Going back to normal: Restore saved values.
				$citationEditor.removeClass('fullscreen');
				$('#citationEditorMainTabs').css('margin-top', beforeFullscreen.topMargin);
				$('div.main-tabs>.canvas').each(function() {
					$(this).css('height', beforeFullscreen.height);
				});
				
				if (beforeFullscreen.index >= beforeFullscreen.$parentElement.children().length) {
					beforeFullscreen.$parentElement.append($citationEditor);
				} else {
					$citationEditor.insertBefore(beforeFullscreen.$parentElement.children().get(beforeFullscreen.index));
				}
				$('body').css('overflow', 'auto');
				window.scroll(beforeFullscreen.x, beforeFullscreen.y);
				$(this).text('{/literal}{translate key="submission.citations.editor.fullscreen"}{literal}');
			} else {
				// Going fullscreen:
				// 1) Save current values.
				beforeFullscreen = {
					$parentElement: $citationEditor.parent(),
					index: $citationEditor.parent().children().index($citationEditor),
					topMargin: $('#citationEditorMainTabs').css('margin-top'),
					height: $('#citationEditorCanvas').css('height'),
					x: $(window).scrollLeft(),
					y: $(window).scrollTop()
				};
		
				// 2) Set values needed to go fullscreen.
				$('body').append($citationEditor).css('overflow', 'hidden');
				$citationEditor.addClass('fullscreen');
				$('#citationEditorMainTabs').css('margin-top', '0');
				$('div.main-tabs>.canvas').each(function() {
					$(this).css('height', ($(window).height()-$('ul.main-tabs').height())+'px');
				});
				window.scroll(0,0);
				$(this).text('{/literal}{translate key="submission.citations.editor.fullscreenOff"}{literal}');
			}

			// Resize the citation editor.
			$('#citationEditorCanvas').css('width', '100%').triggerHandler('resize');
		});

		// Resize citation editor in fullscreen mode
		// when the browser window is being resized.
		$(window).resize(function() {
			if ($citationEditor.hasClass('fullscreen')) {
				$('div.main-tabs>.canvas').each(function() {
					$(this).css('height', ($(window).height()-$('ul.main-tabs').height())+'px');
				});
			}
		});
	});
</script>
{/literal}

{* CSS - FIXME: will be moved to JS file as soon as development is done *}
{literal}
<style type="text/css">
	/* Style specific to the citation editor */
	#citationEditorMainTabs {
		margin-top: 20px;
		padding: 0;
		border: 0 none;
	}
	
	div.main-tabs>.canvas {
		height: 800px;
	}
	
	div.canvas div.text-pane {
		background-color: #CED7E1;
		padding-top: 30px;
	}
	
	div.grid tr div.row_container {
		background-color: #FFFFFF;
		border-bottom: 1px solid #B6C9D5;
	}
	
	div.grid tr.approved-citation div.row_file,
	div.grid tr.approved-citation div.row_container {
		background-color: #E8F0F8;
	}

	div.grid tr.approved-citation div.row_file span {
		color: #777777;
	}
	
	#fullScreenButton {
		float: right;
		margin-top: 5px;
	}
	
	.citation-form-block {
		margin-bottom: 40px;
	}
	
	.citation-comparison {
		margin-bottom: 10px;
	}
		
	.citation-comparison div.value {
		border: 1px solid #AAAAAA;
		padding: 5px;
		background-color: #FFFFFF;
		margin-right: 25px;
	}
	
	#editableRawCitation div.value {
		margin-right: 41px;  // FIXME: check for box model bug in IE
	}
		
	#editableRawCitation textarea.textarea {
		width: 100%;
		padding: 5px;
	}
	
	.citation-comparison span,
	#editableRawCitation textarea.textarea {
		font-size: 1.3em;
	}
	
	.citation-comparison-deletion {
		color: red;
		text-decoration: line-through;
	}
		
	.citation-comparison-addition {
		color: green;
		text-decoration: underline;
	}
	
	#rawCitationWithMarkup a {
		display: block;
		float: right;
		width: 14px;
		height: 14px;
		margin-top: 0.8em; 
	}
		
	#generatedCitationWithMarkup span {
		cursor: default;
	}
	
	#citationFormErrorsAndComparison .throbber {
		height: 150px;
	}
		
	/* Generic styles for composite UIs and 2-pane layout */
	.ui-tabs ul.main-tabs {
		background: none #FBFBF3;
		border: 0 none;
		padding: 0;
	}

	.ui-tabs ul.main-tabs li.ui-tabs-selected a {
		color: #555555;
	}

	.ui-tabs ul.main-tabs li.ui-tabs-selected {
		padding-bottom: 2px;
		background: none #CED7E1;
	}
	
	.ui-tabs ul.main-tabs a {
		color: #CCCCCC;
		font-size: 1.5em;
		padding: 0.2em 3em;
	}
	
	.ui-tabs div.main-tabs {
		border: 0 none;
		padding: 0;
		padding: 0;
	}

	div.canvas {
		margin: 0;
		padding: 0;
		background-color:#EFEFEF;
		width: 100%;
	}
	
	div.pane {
		border: 1px solid #B6C9D5;
		background-color: #EFEFEF;
		height: 100%;
		overflow: auto;
	}
	
	.left-pane, .right-pane {
		float: left;
	}

	.left-pane {
		width: 25%;
	}
	
	div.pane table.pane_header {
		width: 100%;
	}
	
	div.pane table.pane_header th {
		padding: 4px;
		height: 30px;
		background-color: #CED7E1;
		color: #20538D;
		text-align: center;
		vertical-align: middle;
	}
	
	div.pane div.wrapper {
		padding: 10px 30px;
	}
	
	div.pane div.help-message {
		margin: 20px 40px 40px 40px;
		padding-left: 30px;
		background: transparent url("/pkp-ojs/lib/pkp/templates/images/icons/alert.gif") no-repeat;
	}

	div.pane div.pane_actions {
		width: 100%;
	}
	
	div.pane div.pane_actions button {
		float: right;
	}

	div.pane div.pane_actions button.secondary-button {
		float: left;
	}

	/* Style for fullscreen support */
	.fullscreen {
		display: block;
		position: absolute;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		z-index: 999;
		margin: 0;
		padding: 0;
		background: inherit;
		font-size: 80%;
	}

	/* Splitbar styles */
	.splitbarV {
		float: left;
		width: 6px;
		height: 100%;
		line-height: 0;
		font-size: 0;
		border-top: solid 1px #9cbdff;
		border-bottom: solid 1px #9cbdff;
		background: #cbe1fb url(/pkp-ojs/lib/pkp/styles/splitter/ui-bg_pane.gif) 0% 50%;
	}

	.splitbarV.working,.splitbuttonV.working {
		 -moz-opacity: .50;
		 filter: alpha(opacity=50);
		 opacity: .50;
	}


	/* Grid subcomponent styles */
	div.pane div.grid table {
		border: 0 none;
	}

	div.pane div.grid th .options {
		margin: 0;
	}
	
	div.pane div.grid th .options a {
		margin: 0;
	}
	
	div.pane div.grid div.clickable-row:hover,
	div.pane div.grid div.clickable-row:hover div.row_file {
		background-color: #B6C9D5;
		cursor: pointer;
		text-decoration: underline:
	}
	
	div.pane div.grid .current-item .row_container {
		border-left: 3px solid #20538D;
		padding-left: 22px;
	}
	
	div.pane div.grid .current-item .row_actions {
		width: 22px;
	}
</style>
{/literal}

<div id="submissionCitations">
	<div id="citationEditorMainTabs">
		<button id="fullScreenButton" type="button">{translate key="submission.citations.editor.fullscreen"}</button>
		<ul class="main-tabs">
			{if !$introductionHide}<li><a href="#citationEditorTabIntroduction">{translate key="submission.citations.editor.tab.introduction"}</a></li>{/if}
			<li><a href="#citationEditorTabEdit">{translate key="submission.citations.editor.tab.edit"}</a></li>
			<li><a href="#citationEditorTabExport">{translate key="submission.citations.editor.tab.export"}</a></li>
		</ul>
		{if !$introductionHide}
			<div id="citationEditorTabIntroduction" class="main-tabs">
				<div id="citationEditorIntroductionCanvas" class="canvas">
					<div id="citationEditorIntroductionPane" class="pane text-pane">
						<div class="help-message">
							{if $citationEditorConfigurationError}
								{capture assign="citationSetupUrl"}{url page="manager" op="setup" path="3" anchor="metaCitationEditing"}{/capture}
								{translate key=$citationEditorConfigurationError citationSetupUrl=$citationSetupUrl}
							{else}
								{translate key="submission.citations.editor.introductionMessage"}
								<input id="introductionHide" type="checkbox" >Don't show this message again.</input>
							{/if}
						</div>
					</div>
				</div>
			</div>
		{/if}
		<div id="citationEditorTabEdit" class="main-tabs">
			<div id="citationEditorCanvas" class="canvas">
				<div id="citationEditorNavPane" class="pane left-pane">
					{if !$citationEditorConfigurationError}
						{load_url_in_div id="#citationGridContainer" loadMessageId="submission.citations.form.loadMessage" url="$citationGridUrl"}
					{/if}
				</div>
				<div id="citationEditorDetailPane" class="pane right-pane">
					<table class="pane_header"><thead><tr><th>{translate key="submission.citations.form.citationDetails"}</th></tr></thead></table>
					<div id="citationEditorDetailCanvas" class="canvas">
						<div class="wrapper">
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

{include file="common/footer.tpl"}
