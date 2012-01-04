{**
 * index.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Displays the notification settings page and unchecks
 *
 *}
{strip}
{assign var="pageTitle" value="notification.settings"}
{include file="common/header.tpl"}
{/strip}

<p>{translate key="notification.settingsDescription"}</p>

<form id="notificationSettings" method="post" action="{url op="saveSettings"}">

<!-- Submission events -->
{if !$canOnlyRead && !$canOnlyReview}
<h4>{translate key="notification.type.submissions"}</h4>

<ul>
	<li>{translate key="notification.type.articleSubmitted" param=$titleVar}
	<ul class="plain">
		<li><span>
			<input id="notificationArticleSubmitted" type="checkbox" name="notificationArticleSubmitted"{if !$smarty.const.NOTIFICATION_TYPE_ARTICLE_SUBMITTED|in_array:$notificationSettings} checked="checked"{/if} />
			{fieldLabel name="notificationArticleSubmitted" key="notification.allow"}
		</span></li>
		<li><span>
			<input id="emailNotificationArticleSubmitted" type="checkbox" name="emailNotificationArticleSubmitted"{if $smarty.const.NOTIFICATION_TYPE_ARTICLE_SUBMITTED|in_array:$emailSettings} checked="checked"{/if} />
			{fieldLabel name="emailNotificationArticleSubmitted" key="notification.email"}
		</span></li>
	</ul>
	</li>
</ul>

<ul>
	<li>{translate key="notification.type.metadataModified" param=$titleVar}
	<ul class="plain">
		<li><span>
			<input id="notificationMetadataModified" type="checkbox" name="notificationMetadataModified"{if !$smarty.const.NOTIFICATION_TYPE_METADATA_MODIFIED|in_array:$notificationSettings} checked="checked"{/if} />
			{fieldLabel name="notificationMetadataModified" key="notification.allow"}
		</span></li>
		<li><span>
			<input id="emailNotificationMetadataModified" type="checkbox" name="emailNotificationMetadataModified"{if $smarty.const.NOTIFICATION_TYPE_METADATA_MODIFIED|in_array:$emailSettings} checked="checked"{/if} />
			{fieldLabel name="emailNotificationMetadataModified" key="notification.email"}
		</span></li>
	</ul>
	</li>
</ul>

<ul>
	<li>{translate key="notification.type.suppFileModified" param=$titleVar}
	<ul class="plain">
		<li><span>
			<input id="notificationSuppFileModified" type="checkbox" name="notificationSuppFileModified"{if !$smarty.const.NOTIFICATION_TYPE_SUPP_FILE_MODIFIED|in_array:$notificationSettings} checked="checked"{/if} />
			{fieldLabel name="notificationSuppFileModified" key="notification.allow"}
		</span></li>
		<li><span>
			<input id="emailNotificationSuppFileModified" type="checkbox" name="emailNotificationSuppFileModified"{if $smarty.const.NOTIFICATION_TYPE_SUPP_FILE_MODIFIED|in_array:$emailSettings} checked="checked"{/if} />
			{fieldLabel name="emailNotificationSuppFileModified" key="notification.email"}
		</span></li>
	</ul>
	</li>
</ul>

<br />

{if !$canOnlyRead}
<!-- Reviewing events -->
<h4>{translate key="notification.type.reviewing"}</h4>


<ul>
	<li>{translate key="notification.type.reviewerComment" param=$titleVar}
	<ul class="plain">
		<li><span>
			<input id="notificationReviewerComment" type="checkbox" name="notificationReviewerComment"{if !$smarty.const.NOTIFICATION_TYPE_REVIEWER_COMMENT|in_array:$notificationSettings} checked="checked"{/if} />
			{fieldLabel name="notificationReviewerComment" key="notification.allow"}
		</span></li>
		<li><span>
			<input id="emailNotificationReviewerComment" type="checkbox" name="emailNotificationReviewerComment"{if $smarty.const.NOTIFICATION_TYPE_REVIEWER_COMMENT|in_array:$emailSettings} checked="checked"{/if} />
			{fieldLabel name="emailNotificationReviewerComment" key="notification.email"}
		</span></li>
	</ul>
	</li>
</ul>

<ul>
	<li>{translate key="notification.type.reviewerFormComment" param=$titleVar}
	<ul class="plain">
		<li><span>
			<input id="notificationReviewerFormComment" type="checkbox" name="notificationReviewerFormComment"{if !$smarty.const.NOTIFICATION_TYPE_REVIEWER_FORM_COMMENT|in_array:$notificationSettings} checked="checked"{/if} />
			{fieldLabel name="notificationReviewerFormComment" key="notification.allow"}
		</span></li>
		<li><span>
			<input id="emailNotificationReviewerFormComment" type="checkbox" name="emailNotificationReviewerFormComment"{if $smarty.const.NOTIFICATION_TYPE_REVIEWER_FORM_COMMENT|in_array:$emailSettings} checked="checked"{/if} />
			{fieldLabel name="emailNotificationReviewerFormComment" key="notification.email"}
		</span></li>
	</ul>
	</li>
</ul>

<ul>
	<li>{translate key="notification.type.editorDecisionComment" param=$titleVar}
	<ul class="plain">
		<li><span>
			<input id="notificationEditorDecisionComment" type="checkbox" name="notificationEditorDecisionComment"{if !$smarty.const.NOTIFICATION_TYPE_EDITOR_DECISION_COMMENT|in_array:$notificationSettings} checked="checked"{/if} />
			{fieldLabel name="notificationEditorDecisionComment" key="notification.allow"}
		</span></li>
		<li><span>
			<input id="emailNotificationEditorDecisionComment" type="checkbox" name="emailNotificationEditorDecisionComment"{if $smarty.const.NOTIFICATION_TYPE_EDITOR_DECISION_COMMENT|in_array:$emailSettings} checked="checked"{/if} />
			{fieldLabel name="emailNotificationEditorDecisionComment" key="notification.email"}
		</span></li>
	</ul>
	</li>
</ul>

<br />
{/if}

<!-- Editing events -->
<h4>{translate key="notification.type.editing"}</h4>

<ul>
	<li>{translate key="notification.type.galleyModified" param=$titleVar}
	<ul class="plain">
		<li><span>
			<input id="notificationGalleyModified" type="checkbox" name="notificationGalleyModified"{if !$smarty.const.NOTIFICATION_TYPE_GALLEY_MODIFIED|in_array:$notificationSettings} checked="checked"{/if} />
			{fieldLabel name="notificationGalleyModified" key="notification.allow"}
		</span></li>
		<li><span>
			<input id="emailNotificationGalleyModified" type="checkbox" name="emailNotificationGalleyModified"{if $smarty.const.NOTIFICATION_TYPE_GALLEY_MODIFIED|in_array:$emailSettings} checked="checked"{/if} />
			{fieldLabel name="emailNotificationGalleyModified" key="notification.email"}
		</span></li>
	</ul>
	</li>
</ul>

<ul>
	<li>{translate key="notification.type.submissionComment" param=$titleVar}
	<ul class="plain">
		<li><span>
			<input id="notificationSubmissionComment" type="checkbox" name="notificationSubmissionComment"{if !$smarty.const.NOTIFICATION_TYPE_SUBMISSION_COMMENT|in_array:$notificationSettings} checked="checked"{/if} />
			{fieldLabel name="notificationSubmissionComment" key="notification.allow"}
		</span></li>
		<li><span>
			<input id="emailNotificationSubmissionComment" type="checkbox" name="emailNotificationSubmissionComment"{if $smarty.const.NOTIFICATION_TYPE_SUBMISSION_COMMENT|in_array:$emailSettings} checked="checked"{/if} />
			{fieldLabel name="emailNotificationSubmissionComment" key="notification.email"}
		</span></li>
	</ul>
	</li>
</ul>

<ul>
	<li>{translate key="notification.type.layoutComment" param=$titleVar}
	<ul class="plain">
		<li><span>
			<input id="notificationLayoutComment" type="checkbox" name="notificationLayoutComment"{if !$smarty.const.NOTIFICATION_TYPE_LAYOUT_COMMENT|in_array:$notificationSettings} checked="checked"{/if} />
			{fieldLabel name="notificationLayoutComment" key="notification.allow"}
		</span></li>
		<li><span>
			<input id="emailNotificationLayoutComment" type="checkbox" name="emailNotificationLayoutComment"{if $smarty.const.NOTIFICATION_TYPE_LAYOUT_COMMENT|in_array:$emailSettings} checked="checked"{/if} />
			{fieldLabel name="emailNotificationLayoutComment" key="notification.email"}
		</span></li>
	</ul>
	</li>
</ul>

<ul>
	<li>{translate key="notification.type.copyeditComment" param=$titleVar}
	<ul class="plain">
		<li><span>
			<input id="notificationCopyeditComment" type="checkbox" name="notificationCopyeditComment"{if !$smarty.const.NOTIFICATION_TYPE_COPYEDIT_COMMENT|in_array:$notificationSettings} checked="checked"{/if} />
			{fieldLabel name="notificationCopyeditComment" key="notification.allow"}
		</span></li>
		<li><span>
			<input id="emailNotificationCopyeditComment" type="checkbox" name="emailNotificationCopyeditComment"{if $smarty.const.NOTIFICATION_TYPE_COPYEDIT_COMMENT|in_array:$emailSettings} checked="checked"{/if} />
			{fieldLabel name="emailNotificationCopyeditComment" key="notification.email"}
		</span></li>
	</ul>
	</li>
</ul>

<ul>
	<li>{translate key="notification.type.proofreadComment" param=$titleVar}
	<ul class="plain">
		<li><span>
			<input id="notificationProofreadComment" type="checkbox" name="notificationProofreadComment"{if !$smarty.const.NOTIFICATION_TYPE_PROOFREAD_COMMENT|in_array:$notificationSettings} checked="checked"{/if} />
			{fieldLabel name="notificationProofreadComment" key="notification.allow"}
		</span></li>
		<li><span>
			<input id="emailNotificationProofreadComment" type="checkbox" name="emailNotificationProofreadComment"{if $smarty.const.NOTIFICATION_TYPE_PROOFREAD_COMMENT|in_array:$emailSettings} checked="checked"{/if} />
			{fieldLabel name="emailNotificationProofreadComment" key="notification.email"}
		</span></li>
	</ul>
	</li>
</ul>

<br />
{/if}

<!-- Site events -->
<h4>{translate key="notification.type.site"}</h4>

<ul>
	<li>{translate key="notification.type.userComment" param=$titleVar}
	<ul class="plain">
		<li><span>
			<input id="notificationUserComment" type="checkbox" name="notificationUserComment"{if !$smarty.const.NOTIFICATION_TYPE_USER_COMMENT|in_array:$notificationSettings} checked="checked"{/if} />
			{fieldLabel name="notificationUserComment" key="notification.allow"}
		</span></li>
		<li><span>
			<input id="emailNotificationUserComment" type="checkbox" name="emailNotificationUserComment"{if $smarty.const.NOTIFICATION_TYPE_USER_COMMENT|in_array:$emailSettings} checked="checked"{/if} />
			{fieldLabel name="emailNotificationUserComment" key="notification.email"}
		</span></li>
	</ul>
	</li>
</ul>

<ul>
	<li>{translate key="notification.type.issuePublished" param=$userVar}
	<ul class="plain">
		<li><span>
			<input id="notificationPublishedIssue" type="checkbox" name="notificationPublishedIssue"{if !$smarty.const.NOTIFICATION_TYPE_PUBLISHED_ISSUE|in_array:$notificationSettings} checked="checked"{/if} />
			{fieldLabel name="notificationPublishedIssue" key="notification.allow"}
		</span></li>
		<li><span>
			<input id="emailNotificationPublishedIssue" type="checkbox" name="emailNotificationPublishedIssue"{if $smarty.const.NOTIFICATION_TYPE_PUBLISHED_ISSUE|in_array:$emailSettings} checked="checked"{/if} />
			{fieldLabel name="emailNotificationPublishedIssue" key="notification.email"}
		</span></li>
	</ul>
	</li>
</ul>

<ul>
	<li>{translate key="notification.type.newAnnouncement"}
	<ul class="plain">
		<li><span>
			<input id="notificationNewAnnouncement" type="checkbox" name="notificationNewAnnouncement"{if !$smarty.const.NOTIFICATION_TYPE_NEW_ANNOUNCEMENT|in_array:$notificationSettings} checked="checked"{/if} />
			{fieldLabel name="notificationNewAnnouncement" key="notification.allow"}
		</span></li>
		<li><span>
			<input id="emailNotificationNewAnnouncement" type="checkbox" name="emailNotificationNewAnnouncement"{if $smarty.const.NOTIFICATION_TYPE_NEW_ANNOUNCEMENT|in_array:$emailSettings} checked="checked"{/if} />
			{fieldLabel name="emailNotificationNewAnnouncement" key="notification.email"}
		</span></li>
	</ul>
	</li>
</ul>

<br />

<p><input type="submit" value="{translate key="form.submit"}" class="button defaultButton" />  <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url page="notification" escape=false}'" /></p>

</form>

{include file="common/footer.tpl"}

