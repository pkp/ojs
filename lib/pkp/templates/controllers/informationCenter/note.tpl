{**
 * templates/controllers/informationCenter/note.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display a single information center note.
 *
 *}

{* These variables are both "safe" to be used unescaped. *}
{assign var="noteId" value=$note->getId()}
{assign var="formId" value="deleteNoteForm-$noteId"}

<script type="text/javascript">
	$(function() {ldelim}
			// Attach the form handler.
			$('#{$formId}').pkpHandler('$.pkp.controllers.form.AjaxFormHandler', {ldelim}
				baseUrl: {$baseUrl|json_encode}
			{rdelim});
	{rdelim});
</script>

<div id="note-{$noteId}" class="note{if $noteViewStatus==$smarty.const.RECORD_VIEW_RESULT_INSERTED} new{/if}">
	<div class="details">
		<span class="user">
			{assign var=noteUser value=$note->getUser()}
			{$noteUser->getFullName()|escape}
		</span>
		<span class="date">
			{$note->getDateCreated()|date_format:$datetimeFormatShort}
		</span>
		{if ($notesDeletable && array_intersect(array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR), (array)$userRoles)) || $noteFileDownloadLink}
			<div class="actions">
				{if $noteFileDownloadLink}
					{include file="linkAction/linkAction.tpl" action=$noteFileDownloadLink contextId=$note->getId()}
				{/if}
				{if $notesDeletable && array_intersect(array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR), (array)$userRoles)}
					<form class="pkp_form" id="{$formId}" action="{url op="deleteNote" noteId=$noteId params=$linkParams}">
						{csrf}
						{assign var=deleteNoteButtonId value="deleteNote-$noteId"}
						{include file="linkAction/buttonConfirmationLinkAction.tpl" titleIcon="modal_delete" buttonSelector="#$deleteNoteButtonId" dialogText="informationCenter.deleteConfirm"}
						<button type="submit" id="{$deleteNoteButtonId}" class="pkp_button pkp_button_offset">{translate key='common.delete'}</button>
					</form>
				{/if}
			</div>
		{/if}
	</div>
	<div class="message">
		{include file="controllers/revealMore.tpl" content=$note->getContents()|strip_unsafe_html}
	</div>
</div>
