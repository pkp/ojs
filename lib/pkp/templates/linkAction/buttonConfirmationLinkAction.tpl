{**
 * buttonConfirmationLinkAction.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Attach a link action to a button that opens a confirmation dialog and
 * only clicks the button when the user confirms the dialog.
 *
 * Parameters:
 *  buttonSelector: A jQuery selector that designates the button to attach this
 *   link action to.
 *  dialogText: A translation key for the message to be displayed in the dialog.
 *}
{if !$titleIcon}{assign var="titleIcon" value="modal_confirm"}{/if}
<script>
	$(function() {ldelim}
		$('{$buttonSelector|escape:javascript}').pkpHandler(
			'$.pkp.controllers.linkAction.LinkActionHandler',
			{ldelim}
				actionRequest: '$.pkp.classes.linkAction.ModalRequest',
				actionRequestOptions: {ldelim}
					modalHandler: '$.pkp.controllers.modal.ButtonConfirmationModalHandler',
					title: {translate|json_encode key="form.confirmSubmit.title"},
					okButton: {translate|json_encode key="common.ok"},
					cancelButton: {translate|json_encode key="common.cancel"},
					dialogText: {translate|json_encode key=$dialogText},
					$button: $({$buttonSelector|json_encode}),
					titleIcon: {$titleIcon|json_encode},
					width: 'auto'
				{rdelim},
		{rdelim});
	{rdelim});
</script>
