{**
 * templates/authorDashboard/submissionEmails.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display submission emails to authors.
 *}

{if $submissionEmails && $submissionEmails->getCount()}

	<div class="pkp_submission_emails">
		<h3>{translate key="notification.notifications"}</h3>

		<ul>
			{iterate from=submissionEmails item=submissionEmail}

				{capture assign=submissionEmailLinkId}submissionEmail-{$submissionEmail->getId()}{/capture}
				<script type="text/javascript">
					// Initialize JS handler.
					$(function() {ldelim}
						$('#{$submissionEmailLinkId|escape:"javascript"}').pkpHandler(
							'$.pkp.pages.authorDashboard.SubmissionEmailHandler',
							{ldelim}
								{* Parameters for parent LinkActionHandler *}
								actionRequest: '$.pkp.classes.linkAction.ModalRequest',
								actionRequestOptions: {ldelim}
									titleIcon: 'modal_information',
									title: {translate|json_encode key="notification.notifications"},
									modalHandler: '$.pkp.controllers.modal.AjaxModalHandler',
									url: {url|json_encode router=$smarty.const.ROUTE_PAGE page="authorDashboard" op="readSubmissionEmail" submissionId=$submission->getId() stageId=$stageId reviewRoundId=$reviewRoundId submissionEmailId=$submissionEmail->getId() escape=false}
								{rdelim}
							{rdelim}
						);
					{rdelim});
				</script>

				<li>
					<span class="message">
						<a href="#" id="{$submissionEmailLinkId|escape}">{$submissionEmail->getSubject()|escape}</a>
					</span>
					<span class="date">
						{$submissionEmail->getDateSent()|date_format:$datetimeFormatShort}
					</span>
				</li>

			{/iterate}
		</ul>
	</div>
{/if}
