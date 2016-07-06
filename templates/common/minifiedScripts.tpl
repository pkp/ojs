{**
 * templates/common/minifiedScripts.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * This file contains a list of all JavaScript files that should be compiled
 * for distribution.
 *
 * NB: Please make sure that you add your scripts in the same format as the
 * existing files because this file will be parsed by the build script.
 *}

{* External jQuery plug-ins to be minified *}
<script src="{$baseUrl}/lib/pkp/js/lib/jquery/plugins/validate/jquery.validate{if $useMinifiedJavaScript}.min{/if}.js"></script>
{if in_array($currentLocale, array('pt_PT', 'pt_BR'))}
    <script src="{$baseUrl}/lib/pkp/js/lib/jquery/plugins/validate/localization/messages_{$currentLocale|regex_replace:"/(.*)_(.*)/":"\\1\\2"|strtolower}.js"></script>
{elseif in_array(substr($currentLocale,0,2), array('ar', 'bg', 'cn', 'cs', 'da', 'de', 'es', 'fa', 'fi', 'fr', 'hu', 'it', 'kk', 'nl', 'no', 'pl', 'ro', 'ru', 'se', 'sk', 'tr', 'tw', 'ua'))}
    <script src="{$baseUrl}/lib/pkp/js/lib/jquery/plugins/validate/localization/messages_{$currentLocale|regex_replace:"/(.*)_(.*)/":"\\1"|strtolower}.js"></script>
{/if}
<script src="{$baseUrl}/lib/pkp/js/lib/jquery/plugins/jquery.form.js"></script>
<script src="{$baseUrl}/lib/pkp/js/lib/jquery/plugins/jquery.tag-it.js"></script>
<script src="{$baseUrl}/lib/pkp/js/lib/jquery/plugins/jquery.sortElements.js"></script>
<script src="{$baseUrl}/lib/pkp/js/lib/jquery/plugins/jquery.cookie.js"></script>
<script src="{$baseUrl}/lib/pkp/js/lib/jquery/plugins/jquery.equalizer.js"></script>
<script src="{$baseUrl}/lib/pkp/js/lib/jquery/plugins/jquery.jlabel-1.3.min.js"></script>
<script src="{$baseUrl}/lib/pkp/js/lib/jquery/plugins/jquery.selectBox.min.js"></script>

{* PlUpload component *}
<script src="{$baseUrl}/lib/pkp/lib/vendor/moxiecode/plupload/js/plupload.full.min.js"></script>
<script src="{$baseUrl}/lib/pkp/lib/vendor/moxiecode/plupload/js/jquery.ui.plupload/jquery.ui.plupload.js"></script>
{if in_array($currentLocale, array('pt_BR'))}
	{* Locale files of the form aa-bb.js *}
	<script src="{$baseUrl}/lib/pkp/lib/vendor/moxiecode/plupload/js/i18n/{$currentLocale|regex_replace:"/(.*)_(.*)/":"\\1-\\2"|strtolower}.js"></script>
{elseif in_array(substr($currentLocale,0,2), array('ar', 'da', 'et', 'hr', 'ja', 'lt', 'pl', 'sq', 'tr', 'az', 'de', 'fa', 'hu', 'ka', 'lv', 'sr', 'bs', 'el', 'fi', 'hy', 'kk', 'mn', 'ro', 'cs', 'en', 'fr', 'id', 'km', 'ms', 'ru', 'sv', 'cy', 'es', 'he', 'it', 'ko', 'nl', 'sk'))}
	{* Locale files of the form aa.js *}
	<script src="{$baseUrl}/lib/pkp/lib/vendor/moxiecode/plupload/js/i18n/{$currentLocale|substr:0:2}.js"></script>
{/if}

{* JSON library *}
<script src="{$baseUrl}/lib/pkp/js/lib/json/json2.js"></script>

{* Pines Notify build/cache *}
<script src="{$baseUrl}/lib/pkp/js/lib/pnotify/pnotify.core.js"></script>
<script src="{$baseUrl}/lib/pkp/js/lib/pnotify/pnotify.buttons.js"></script>

{* Our own functions (depend on plug-ins) *}
<script src="{$baseUrl}/lib/pkp/js/functions/general.js"></script>

{* Our own classes (depend on plug-ins) *}
<script src="{$baseUrl}/lib/pkp/js/classes/Helper.js"></script>
<script src="{$baseUrl}/lib/pkp/js/classes/ObjectProxy.js"></script>
<script src="{$baseUrl}/lib/pkp/js/classes/Handler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/classes/TinyMCEHelper.js"></script>
<script src="{$baseUrl}/lib/pkp/js/classes/linkAction/LinkActionRequest.js"></script>
<script src="{$baseUrl}/lib/pkp/js/classes/linkAction/RedirectRequest.js"></script>
<script src="{$baseUrl}/lib/pkp/js/classes/linkAction/OpenWindowRequest.js"></script>
<script src="{$baseUrl}/lib/pkp/js/classes/linkAction/PostAndRedirectRequest.js"></script>
<script src="{$baseUrl}/lib/pkp/js/classes/linkAction/NullAction.js"></script>
<script src="{$baseUrl}/lib/pkp/js/classes/linkAction/EventAction.js"></script>
<script src="{$baseUrl}/lib/pkp/js/classes/linkAction/AjaxRequest.js"></script>
<script src="{$baseUrl}/lib/pkp/js/classes/linkAction/ModalRequest.js"></script>
<script src="{$baseUrl}/lib/pkp/js/classes/notification/NotificationHelper.js"></script>
<script src="{$baseUrl}/lib/pkp/js/classes/features/Feature.js"></script>
<script src="{$baseUrl}/lib/pkp/js/classes/features/OrderItemsFeature.js"></script>
<script src="{$baseUrl}/lib/pkp/js/classes/features/OrderGridItemsFeature.js"></script>
<script src="{$baseUrl}/lib/pkp/js/classes/features/OrderCategoryGridItemsFeature.js"></script>
<script src="{$baseUrl}/lib/pkp/js/classes/features/OrderListbuilderItemsFeature.js"></script>
<script src="{$baseUrl}/lib/pkp/js/classes/features/OrderMultipleListsItemsFeature.js"></script>
<script src="{$baseUrl}/lib/pkp/js/classes/features/GridCategoryAccordionFeature.js"></script>
<script src="{$baseUrl}/lib/pkp/js/classes/features/GeneralPagingFeature.js"></script>
<script src="{$baseUrl}/lib/pkp/js/classes/features/PagingFeature.js"></script>
<script src="{$baseUrl}/lib/pkp/js/classes/features/InfiniteScrollingFeature.js"></script>
<script src="{$baseUrl}/lib/pkp/js/classes/features/CollapsibleGridFeature.js"></script>

{* Generic controllers *}
<script src="{$baseUrl}/lib/pkp/js/controllers/SiteHandler.js"></script><!-- Included only for namespace definition -->
<script src="{$baseUrl}/lib/pkp/js/controllers/UrlInDivHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/ExtrasOnDemandHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/PageHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/TabHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/MenuHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/UploaderHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/AutocompleteHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/RangeSliderHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/RevealMoreHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/NotificationHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/HelpPanelHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/form/FormHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/form/DropdownHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/form/AjaxFormHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/form/ClientFormHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/form/ToggleFormHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/form/FileUploadFormHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/form/MultilingualInputHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/form/CancelActionAjaxFormHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/form/UserFormHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/form/reviewer/ReviewerReviewStep3FormHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/grid/GridHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/grid/CategoryGridHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/listbuilder/ListbuilderHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/listbuilder/MultipleListsListbuilderHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/modal/ModalHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/modal/ConfirmationModalHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/modal/RedirectConfirmationModalHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/modal/RemoteActionConfirmationModalHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/modal/ButtonConfirmationModalHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/modal/JsEventConfirmationModalHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/modal/AjaxModalHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/modal/WizardModalHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/modals/editorDecision/form/EditorDecisionFormHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/linkAction/LinkActionHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/wizard/WizardHandler.js"></script>

{* Specific controllers *}
<script src="{$baseUrl}/lib/pkp/js/controllers/grid/queries/ReadQueryHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/wizard/fileUpload/FileUploadWizardHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/wizard/fileUpload/form/FileUploadFormHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/wizard/fileUpload/form/RevisionConfirmationHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/grid/filter/form/FilterFormHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/grid/representations/form/RepresentationFormHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/grid/settings/user/form/UserDetailsFormHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/grid/settings/roles/form/UserGroupFormHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/grid/settings/metadata/MetadataGridHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/tab/settings/form/FileViewFormHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/tab/settings/announcements/form/AnnouncementSettingsFormHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/tab/settings/permissions/form/PermissionSettingsFormHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/tab/settings/paymentMethod/PaymentMethodHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/grid/notifications/NotificationsGridHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/tab/settings/siteAccessOptions/form/SiteAccessOptionsFormHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/tab/settings/managementSettings/UsersAndRolesTabHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/tab/workflow/WorkflowTabHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/tab/representations/RepresentationsTabHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/informationCenter/NotesHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/statistics/ReportGeneratorFormHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/dashboard/form/DashboardTaskFormHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/pages/header/HeaderHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/pages/header/TasksHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/pages/admin/ContextsHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/pages/submission/SubmissionTabHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/pages/submission/SubmissionStep2FormHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/pages/submission/SubmissionStep3FormHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/pages/workflow/WorkflowHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/pages/workflow/SubmissionHeaderHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/grid/users/reviewer/AdvancedReviewerSearchHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/grid/users/reviewer/form/EditReviewFormHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/grid/users/reviewer/form/AddReviewerFormHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/grid/users/stageParticipant/form/StageParticipantNotifyHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/controllers/grid/users/stageParticipant/form/AddParticipantFormHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/pages/reviewer/ReviewerTabHandler.js"></script>
<script src="{$baseUrl}/lib/pkp/js/pages/authorDashboard/SubmissionEmailHandler.js"></script>
<script src="{$baseUrl}/js/controllers/grid/articleGalleys/ArticleGalleyGridHandler.js"></script>
<script src="{$baseUrl}/js/controllers/form/DOIPubIdExportFormHandler.js"></script>
<script src="{$baseUrl}/js/controllers/tab/issueEntry/IssueEntryTabHandler.js"></script>
<script src="{$baseUrl}/js/controllers/tab/issueEntry/form/IssueEntryPublicationMetadataFormHandler.js"></script>
<script src="{$baseUrl}/js/pages/search/SearchFormHandler.js"></script>
<script src="{$baseUrl}/plugins/generic/lucene/js/LuceneAutocompleteHandler.js"></script>

{* Our own plug-in (depends on classes) *}
<script src="{$baseUrl}/lib/pkp/js/lib/jquery/plugins/jquery.pkp.js"></script>
