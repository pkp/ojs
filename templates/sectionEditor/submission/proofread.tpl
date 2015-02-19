{**
 * templates/sectionEditor/submission/proofread.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the proofreading table.
 *
 *}
{assign var=proofSignoff value=$submission->getSignoff('SIGNOFF_PROOFREADING_PROOFREADER')}
{assign var=proofreader value=$submission->getUserBySignoffType('SIGNOFF_PROOFREADING_PROOFREADER')}

<div id="proofread">
<h3>{translate key="submission.proofreading"}</h3>

{if $useProofreaders}
<table class="data" width="100%">
	<tr>
		<td width="20%" class="label">{translate key="user.role.proofreader"}</td>
		{if $proofSignoff->getUserId()}<td class="value" width="20%">{$proofreader->getFullName()|escape}</td>{/if}
		<td class="value"><a href="{url op="selectProofreader" path=$submission->getId()}" class="action">{translate key="editor.article.selectProofreader"}</a></td>
	</tr>
</table>
{/if}

<table width="100%" class="info">
	<tr>
		<td width="28%" colspan="2">&nbsp;</td>
		<td width="18%" class="heading">{translate key="submission.request"}</td>
		<td width="18%" class="heading">{translate key="submission.underway"}</td>
		<td width="18%" class="heading">{translate key="submission.complete"}</td>
		<td width="18%" class="heading">{translate key="submission.acknowledge"}</td>
	</tr>
	<tr>
		<td width="2%">1.</td>
		<td width="26%">{translate key="user.role.author"}</td>
		{assign var="authorProofreadSignoff" value=$submission->getSignoff('SIGNOFF_PROOFREADING_AUTHOR')}
		<td>
			{url|assign:"url" op="notifyAuthorProofreader" articleId=$submission->getId()}
			{if $authorProofreadSignoff->getDateUnderway()}
				{translate|escape:"javascript"|assign:"confirmText" key="sectionEditor.author.confirmRenotify"}
				{icon name="mail" onclick="return confirm('$confirmText')" url=$url}
			{else}
				{icon name="mail" url=$url}
			{/if}

			{$authorProofreadSignoff->getDateNotified()|date_format:$dateFormatShort|default:""}
		</td>
		<td>
				{$authorProofreadSignoff->getDateUnderway()|date_format:$dateFormatShort|default:"&mdash;"}
		</td>
		<td>
			{$authorProofreadSignoff->getDateCompleted()|date_format:$dateFormatShort|default:"&mdash;"}
		</td>
		<td>
			{if $authorProofreadSignoff->getDateCompleted() && !$authorProofreadSignoff->getDateAcknowledged()}
				{url|assign:"url" op="thankAuthorProofreader" articleId=$submission->getId()}
				{icon name="mail" url=$url}
			{else}
				{icon name="mail" disabled="disable"}
			{/if}
			{$authorProofreadSignoff->getDateAcknowledged()|date_format:$dateFormatShort|default:""}
		</td>
	</tr>
	<tr>
		<td>2.</td>
		<td>{translate key="user.role.proofreader"}</td>
		{assign var="proofreaderProofreadSignoff" value=$submission->getSignoff('SIGNOFF_PROOFREADING_PROOFREADER')}
		<td>
			{if $useProofreaders}
				{if $proofSignoff->getUserId() && $authorProofreadSignoff->getDateCompleted()}
					{url|assign:"url" op="notifyProofreader" articleId=$submission->getId()}
					{if $proofreaderProofreadSignoff->getDateUnderway()}
						{translate|escape:"javascript"|assign:"confirmText" key="sectionEditor.proofreader.confirmRenotify"}
						{icon name="mail" onclick="return confirm('$confirmText')" url=$url}
					{else}
						{icon name="mail" url=$url}
					{/if}
				{else}
					{icon name="mail" disabled="disable"}
				{/if}
			{else}
				{if !$proofreaderProofreadSignoff->getDateNotified()}
					<a href="{url op="editorInitiateProofreader" articleId=$submission->getId()}" class="action">{translate key="common.initiate"}</a>
				{/if}
			{/if}
			{$proofreaderProofreadSignoff->getDateNotified()|date_format:$dateFormatShort|default:""}
		</td>
		<td>
			{if $useProofreaders}
					{$proofreaderProofreadSignoff->getDateUnderway()|date_format:$dateFormatShort|default:"&mdash;"}
			{else}
				{translate key="common.notApplicableShort"}
			{/if}
		</td>
		<td>
			{if !$useProofreaders && !$proofreaderProofreadSignoff->getDateCompleted() && $proofreaderProofreadSignoff->getDateNotified()}
				<a href="{url op="editorCompleteProofreader" articleId=$submission->getId()}" class="action">{translate key="common.complete"}</a>
			{else}
				{$proofreaderProofreadSignoff->getDateCompleted()|date_format:$dateFormatShort|default:"&mdash;"}
			{/if}
		</td>
		<td>
			{if $useProofreaders}
				{if $proofreaderProofreadSignoff->getDateCompleted() && !$proofreaderProofreadSignoff->getDateAcknowledged()}
					{url|assign:"url" op="thankProofreader" articleId=$submission->getId()}
					{icon name="mail" url=$url}
				{else}
					{icon name="mail" disabled="disable"}
				{/if}
				{$proofreaderProofreadSignoff->getDateAcknowledged()|date_format:$dateFormatShort|default:""}
			{else}
				{translate key="common.notApplicableShort"}
			{/if}
		</td>
	</tr>
	<tr>
		<td>3.</td>
		<td>{translate key="user.role.layoutEditor"}</td>
		{assign var="layoutEditorProofreadSignoff" value=$submission->getSignoff('SIGNOFF_PROOFREADING_LAYOUT')}
		{assign var="layoutSignoff" value=$submission->getSignoff('SIGNOFF_LAYOUT')}
		<td>
			{if $useLayoutEditors}
				{if $layoutSignoff->getUserId() && $proofreaderProofreadSignoff->getDateCompleted()}
					{url|assign:"url" op="notifyLayoutEditorProofreader" articleId=$submission->getId()}
					{if $layoutEditorProofreadSignoff->getDateUnderway()}
						{translate|escape:"javascript"|assign:"confirmText" key="sectionEditor.layout.confirmRenotify"}
						{icon name="mail" onclick="return confirm('$confirmText')" url=$url}
					{else}
						{icon name="mail" url=$url}
					{/if}
				{else}
					{icon name="mail" disabled="disable"}
				{/if}
			{else}
				{if !$layoutEditorProofreadSignoff->getDateNotified()}
					<a href="{url op="editorInitiateLayoutEditor" articleId=$submission->getId()}" class="action">{translate key="common.initiate"}</a>
				{/if}
			{/if}
				{$layoutEditorProofreadSignoff->getDateNotified()|date_format:$dateFormatShort|default:""}
		</td>
		<td>
			{if $useLayoutEditors}
				{$layoutEditorProofreadSignoff->getDateUnderway()|date_format:$dateFormatShort|default:"&mdash;"}
			{else}
				{translate key="common.notApplicableShort"}
			{/if}
		</td>
		<td>
			{if $useLayoutEditors}
				{$layoutEditorProofreadSignoff->getDateCompleted()|date_format:$dateFormatShort|default:"&mdash;"}
			{elseif $layoutEditorProofreadSignoff->getDateCompleted()}
				{$layoutEditorProofreadSignoff->getDateCompleted()|date_format:$dateFormatShort}
			{elseif $layoutEditorProofreadSignoff->getDateNotified()}
				<a href="{url op="editorCompleteLayoutEditor" articleId=$submission->getId()}" class="action">{translate key="common.complete"}</a>
			{else}
				&mdash;
			{/if}
		</td>
		<td>
			{if $useLayoutEditors}
				{if $layoutEditorProofreadSignoff->getDateCompleted() && !$layoutEditorProofreadSignoff->getDateAcknowledged()}
					{url|assign:"url" op="thankLayoutEditorProofreader" articleId=$submission->getId()}
					{icon name="mail" url=$url}
				{else}
					{icon name="mail" disabled="disable"}
				{/if}
				{$layoutEditorProofreadSignoff->getDateAcknowledged()|date_format:$dateFormatShort|default:""}
			{else}
				{translate key="common.notApplicableShort"}
			{/if}
		</td>
	</tr>
	<tr>
		<td colspan="6" class="separator">&nbsp;</td>
	</tr>
</table>

{translate key="submission.proofread.corrections"}
{if $submission->getMostRecentProofreadComment()}
	{assign var="comment" value=$submission->getMostRecentProofreadComment()}
	<a href="javascript:openComments('{url op="viewProofreadComments" path=$submission->getId() anchor=$comment->getId()}');" class="icon">{icon name="comment"}</a>{$comment->getDatePosted()|date_format:$dateFormatShort}
{else}
	<a href="javascript:openComments('{url op="viewProofreadComments" path=$submission->getId()}');" class="icon">{icon name="comment"}</a>{translate key="common.noComments"}
{/if}

{if $currentJournal->getLocalizedSetting('proofInstructions')}
&nbsp;&nbsp;
<a href="javascript:openHelp('{url op="instructions" path="proof"}')" class="action">{translate key="submission.proofread.instructions"}</a>
{/if}
</div>

