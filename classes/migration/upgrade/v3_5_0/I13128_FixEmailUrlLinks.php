<?php

/**
 * @file classes/migration/upgrade/v3_5_0/I13128_FixEmailUrlLinks.php
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I13128_FixEmailUrlLinks
 *
 * @brief Adds the replacements that only occur in the OJS translations
 */

namespace APP\migration\upgrade\v3_5_0;

class I13128_FixEmailUrlLinks extends \PKP\migration\upgrade\v3_5_0\I13128_FixEmailUrlLinks
{
    public function up(): void
    {
        parent::up();

        $this->replace('SUBSCRIPTION_PURCHASE_INDL', '{$subscriptionUrl}', '<a href="{$subscriptionUrl}">{$subscriptionUrl}</a>', 'href="{$subscriptionUrl}"');
        $this->replace('SUBSCRIPTION_PURCHASE_INSTL', '{$subscriptionUrl}', '<a href="{$subscriptionUrl}">{$subscriptionUrl}</a>', 'href="{$subscriptionUrl}"');
        $this->replace('SUBSCRIPTION_RENEW_INDL', '{$subscriptionUrl}', '<a href="{$subscriptionUrl}">{$subscriptionUrl}</a>', 'href="{$subscriptionUrl}"');
        $this->replace('SUBSCRIPTION_RENEW_INSTL', '{$subscriptionUrl}', '<a href="{$subscriptionUrl}">{$subscriptionUrl}</a>', 'href="{$subscriptionUrl}"');

        // In lt every variable is missplaced and the URL is missing.
        $this->replace(
            'SUBSCRIPTION_RENEW_INSTL',
            '„{contextName}“',
            '„{$contextName}“',
            '',
            'lt'
        );

        $this->replace(
            'SUBSCRIPTION_RENEW_INSTL',
            'Prenumeratos rūšis:<br />' . "\n" . '{$contextName}',
            'Prenumeratos rūšis:<br />' . "\n" . '{$subscriptionType}',
            '',
            'lt'
        );

        $this->replace(
            'SUBSCRIPTION_RENEW_INSTL',
            'Institucija:<br />' . "\n" . '{$subscriptionType}<br />' . "\n" . '{$institutionName}',
            'Institucija:<br />' . "\n" . '{$institutionName}<br />' . "\n" . '{$institutionMailingAddress}',
            '',
            'lt'
        );

        $this->replace(
            'SUBSCRIPTION_RENEW_INSTL',
            'Domenas (jei jis numatytas):<br />' . "\n" . '{$institutionMailingAddress}',
            'Domenas (jei jis numatytas):<br />' . "\n" . '{$domain}',
            '',
            'lt'
        );

        $this->replace(
            'SUBSCRIPTION_RENEW_INSTL',
            'IP intervalas (jei jis numatytas):<br />' . "\n" . '{$domain}',
            'IP intervalas (jei jis numatytas):<br />' . "\n" . '{$ipRanges}',
            '',
            'lt'
        );

        $this->replace(
            'SUBSCRIPTION_RENEW_INSTL',
            'Asmuo susirašinėjimui:<br />' . "\n" . '{$ipRanges}',
            'Asmuo susirašinėjimui:<br />' . "\n" . '{$subscriberDetails}',
            '',
            'lt'
        );

        $this->replace(
            'SUBSCRIPTION_RENEW_INSTL',
            'Informacija apie narystę (jei numatyta):<br />' . "\n" . '{$subscriberDetails}',
            'Informacija apie narystę (jei numatyta):<br />' . "\n" . '{$membership}',
            '',
            'lt'
        );

        $this->replace(
            'SUBSCRIPTION_RENEW_INSTL',
            'Prenumeratos adresas: {$membership}',
            'Prenumeratos adresas: <a href="{$subscriptionUrl}">{$subscriptionUrl}</a>',
            '',
            'lt'
        );

        // Same problem in the individual renewal
        $this->replace(
            'SUBSCRIPTION_RENEW_INDL',
            '„{contextName}“',
            '„{$contextName}“',
            '',
            'lt'
        );

        $this->replace(
            'SUBSCRIPTION_RENEW_INDL',
            'Prenumeratos rūšis:<br />' . "\n" . '{$contextName}',
            'Prenumeratos rūšis:<br />' . "\n" . '{$subscriptionType}',
            '',
            'lt'
        );

        $this->replace(
            'SUBSCRIPTION_RENEW_INDL',
            'Vartotojas:<br />' . "\n" . '{$subscriptionType}',
            'Vartotojas:<br />' . "\n" . '{$subscriberDetails}',
            '',
            'lt'
        );

        $this->replace(
            'SUBSCRIPTION_RENEW_INDL',
            'Informacija apie narystę (jei numatyta):<br />' . "\n" . '{$subscriberDetails}',
            'Informacija apie narystę (jei numatyta):<br />' . "\n" . '{$membership}',
            '',
            'lt'
        );

        $this->replace(
            'SUBSCRIPTION_RENEW_INDL',
            'Prenumeratos adresas: {$membership}',
            'Prenumeratos adresas: <a href="{$subscriptionUrl}">{$subscriptionUrl}</a>',
            '',
            'lt'
        );

        // Two translations translated the variable name, so it was never replaced
        $this->replace('SUBSCRIPTION_PURCHASE_INSTL', '{$ګډون یو آر ایل}', '<a href="{$subscriptionUrl}">{$subscriptionUrl}</a>', '', 'ps');
        $this->replace('USER_VALIDATE_SITE', '{$چالاککردنیUrl}', '<a href="{$activateUrl}">{$activateUrl}</a>', '', 'ckb');

        $this->replace('ISSUE_PUBLISH_NOTIFY', '< p>', '<p>');
        $this->replace('REVIEW_REQUEST_SUBSEQUENT', '< p>', '<p>');
        $this->replace('REVISED_VERSION_NOTIFY', '</ p>', '</p>');
        $this->replace('REVISED_VERSION_NOTIFY', '</a> p>', '</p>');
        $this->replace('REVIEW_REQUEST_SUBSEQUENT', '<a href="{$reviewAssignmentUrl }"{$submissionTitle}', '<a href="{$reviewAssignmentUrl }">{$submissionTitle}');

        $this->replace('EDITOR_ASSIGN', '{ $submissionTitle}', '{$submissionTitle}');
        $this->replace('EDITOR_ASSIGN_PRODUCTION', '{ $submissionTitle}', '{$submissionTitle}');
        $this->replace('EDITOR_ASSIGN_PRODUCTION', '{ $submissionUrl}', '{$submissionUrl}');
        $this->replace('EDITOR_ASSIGN_PRODUCTION', '{$signature }', '{$signature}');
        $this->replace('EDITOR_ASSIGN_PRODUCTION', '{$signature: }', '{$signature}');
        $this->replace('EDITOR_ASSIGN_SUBMISSION', '{ $submissionTitle}', '{$submissionTitle}');
        $this->replace('EDITOR_DECISION_ACCEPT', '"$authorSubmissionUrl}', '"{$authorSubmissionUrl}');
        $this->replace('ISSUE_PUBLISH_NOTIFY', '{$identificare număr}', '{$issueIdentification}');
        $this->replace('ISSUE_PUBLISH_NOTIFY', '{contextName$}', '{$contextName}');
        $this->replace('ISSUE_PUBLISH_NOTIFY', '{recipientName$}', '{$recipientName}');
        $this->replace('LAYOUT_COMPLETE', '{ $senderName}', '{$senderName}');
        $this->replace('LAYOUT_COMPLETE', '{ $signature}', '{$signature}');
        $this->replace('LAYOUT_COMPLETE', '{ $submissionTitle}', '{$submissionTitle}');
        $this->replace('LAYOUT_REQUEST', '{$ submissionTitle}', '{$submissionTitle}');
        $this->replace('LAYOUT_REQUEST', '{$submissionTitle }', '{$submissionTitle}');
        $this->replace('PAYMENT_REQUEST_NOTIFICATION', '{$ kami submissionGuidelinesUrl}', '{$submissionGuidelinesUrl}');
        $this->replace('PAYMENT_REQUEST_NOTIFICATION', '{$ submissionGuidelinesUrl}', '{$submissionGuidelinesUrl}');
        $this->replace('PAYMENT_REQUEST_NOTIFICATION', '{$ uploadGuidelinesUrl}', '{$submissionGuidelinesUrl}');
        $this->replace('REVIEW_CANCEL', '{contextName$}', '{$contextName}');
        $this->replace('REVIEW_CANCEL', '{recipientName$}', '{$recipientName}');
        $this->replace('REVIEW_CANCEL', '{signature$}', '{$signature}');
        $this->replace('REVIEW_REINSTATE', '{$contextName }', '{$contextName}');
        $this->replace('REVIEW_REMIND', '{$ PasswordResetUrl}', '{$passwordResetUrl}');
        $this->replace('REVIEW_REMIND', '{$ reviewDueDate}', '{$reviewDueDate}');
        $this->replace('REVIEW_REMIND_AUTO', '{$ PasswordResetUrl}', '{$passwordResetUrl}');
        $this->replace('REVIEW_REMIND_AUTO', '{$ reviewDueDate}', '{$reviewDueDate}');
        $this->replace('REVIEW_REMIND_AUTO', '{contextName$}', '{$contextName}');
        $this->replace('REVIEW_REMIND_AUTO', '{recipientName$}', '{$recipientName}');
        $this->replace('REVIEW_REMIND_AUTO', '{reviewDueDate$}', '{$reviewDueDate}');
        $this->replace('REVIEW_REMIND_AUTO', '{submissionTitle$}', '{$submissionTitle}');
        $this->replace('REVIEW_REQUEST', '{$submissionTitle }', '{$submissionTitle}');
        $this->replace('REVIEW_REQUEST_SUBSEQUENT', '{$reviewAssignmentUrl }', '{$reviewAssignmentUrl}');
        $this->replace('REVIEW_RESPONSE_OVERDUE_AUTO', '{$ responseDueDate}', '{$responseDueDate}');
        $this->replace('REVIEW_RESPONSE_OVERDUE_AUTO', '{contextName$}', '{$contextName}');
        $this->replace('STATISTICS_REPORT_NOTIFICATION', '{$ editorialStatsLink}', '{$editorialStatsLink}');
        $this->replace('SUBSCRIPTION_AFTER_EXPIRY', '{$ ContextName}', '{$contextName}');
        $this->replace('SUBSCRIPTION_AFTER_EXPIRY', '{contextUrl}', '{$contextUrl}');
        $this->replace('SUBSCRIPTION_AFTER_EXPIRY_LAST', '{ $subscriptionType}', '{$subscriptionType}');
        $this->replace('SUBSCRIPTION_AFTER_EXPIRY_LAST', '{$ username}', '{$recipientUsername}');
        $this->replace('SUBSCRIPTION_AFTER_EXPIRY_LAST', '{contextUrl}', '{$contextUrl}');
        $this->replace('SUBSCRIPTION_PURCHASE_INDL', '{$ contextName}', '{$contextName}');
        $this->replace('SUBSCRIPTION_PURCHASE_INDL', '{$članstvo}', '{$membership}');
        $this->replace('SUBSCRIPTION_PURCHASE_INSTL', '{ $membership}', '{$membership}');
        $this->replace('SUBSCRIPTION_PURCHASE_INSTL', '{$ contextName}', '{$contextName}');
        $this->replace('SUBSCRIPTION_PURCHASE_INSTL', '{$članstvo}', '{$membership}');
        $this->replace('SUBSCRIPTION_RENEW_INDL', '{$članstvo}', '{$membership}');
        $this->replace('SUBSCRIPTION_RENEW_INSTL', '{$članstvo}', '{$membership}');
        $this->replace('USER_VALIDATE_SITE', '{$سایتی واژۆ}', '{$siteSignature}');
        $this->replace('USER_VALIDATE_SITE', '{$ناوی وەرگر}', '{$recipientName}');
    }

    public function down(): void
    {
        // Undo the lt steps first, in reverse order
        $this->replace(
            'SUBSCRIPTION_RENEW_INSTL',
            'Prenumeratos adresas: <a href="{$subscriptionUrl}">{$subscriptionUrl}</a>',
            'Prenumeratos adresas: {$membership}',
            '',
            'lt'
        );

        $this->replace(
            'SUBSCRIPTION_RENEW_INSTL',
            'Informacija apie narystę (jei numatyta):<br />' . "\n" . '{$membership}',
            'Informacija apie narystę (jei numatyta):<br />' . "\n" . '{$subscriberDetails}',
            '',
            'lt'
        );

        $this->replace(
            'SUBSCRIPTION_RENEW_INSTL',
            'Asmuo susirašinėjimui:<br />' . "\n" . '{$subscriberDetails}',
            'Asmuo susirašinėjimui:<br />' . "\n" . '{$ipRanges}',
            '',
            'lt'
        );

        $this->replace(
            'SUBSCRIPTION_RENEW_INSTL',
            'IP intervalas (jei jis numatytas):<br />' . "\n" . '{$ipRanges}',
            'IP intervalas (jei jis numatytas):<br />' . "\n" . '{$domain}',
            '',
            'lt'
        );

        $this->replace(
            'SUBSCRIPTION_RENEW_INSTL',
            'Domenas (jei jis numatytas):<br />' . "\n" . '{$domain}',
            'Domenas (jei jis numatytas):<br />' . "\n" . '{$institutionMailingAddress}',
            '',
            'lt'
        );

        $this->replace(
            'SUBSCRIPTION_RENEW_INSTL',
            'Institucija:<br />' . "\n" . '{$institutionName}<br />' . "\n" . '{$institutionMailingAddress}',
            'Institucija:<br />' . "\n" . '{$subscriptionType}<br />' . "\n" . '{$institutionName}',
            '',
            'lt'
        );

        $this->replace(
            'SUBSCRIPTION_RENEW_INSTL',
            'Prenumeratos rūšis:<br />' . "\n" . '{$subscriptionType}',
            'Prenumeratos rūšis:<br />' . "\n" . '{$contextName}',
            '',
            'lt'
        );

        $this->replace(
            'SUBSCRIPTION_RENEW_INSTL',
            '„{$contextName}“',
            '„{contextName}“',
            '',
            'lt'
        );

        $this->replace(
            'SUBSCRIPTION_RENEW_INDL',
            'Prenumeratos adresas: <a href="{$subscriptionUrl}">{$subscriptionUrl}</a>',
            'Prenumeratos adresas: {$membership}',
            '',
            'lt'
        );

        $this->replace(
            'SUBSCRIPTION_RENEW_INDL',
            'Informacija apie narystę (jei numatyta):<br />' . "\n" . '{$membership}',
            'Informacija apie narystę (jei numatyta):<br />' . "\n" . '{$subscriberDetails}',
            '',
            'lt'
        );

        $this->replace(
            'SUBSCRIPTION_RENEW_INDL',
            'Vartotojas:<br />' . "\n" . '{$subscriberDetails}',
            'Vartotojas:<br />' . "\n" . '{$subscriptionType}',
            '',
            'lt'
        );

        $this->replace(
            'SUBSCRIPTION_RENEW_INDL',
            'Prenumeratos rūšis:<br />' . "\n" . '{$subscriptionType}',
            'Prenumeratos rūšis:<br />' . "\n" . '{$contextName}',
            '',
            'lt'
        );

        $this->replace(
            'SUBSCRIPTION_RENEW_INDL',
            '„{$contextName}“',
            '„{contextName}“',
            '',
            'lt'
        );

        $this->replace('SUBSCRIPTION_PURCHASE_INDL', '<a href="{$subscriptionUrl}">{$subscriptionUrl}</a>', '{$subscriptionUrl}');
        $this->replace('SUBSCRIPTION_PURCHASE_INSTL', '<a href="{$subscriptionUrl}">{$subscriptionUrl}</a>', '{$subscriptionUrl}');
        $this->replace('SUBSCRIPTION_RENEW_INDL', '<a href="{$subscriptionUrl}">{$subscriptionUrl}</a>', '{$subscriptionUrl}');
        $this->replace('SUBSCRIPTION_RENEW_INSTL', '<a href="{$subscriptionUrl}">{$subscriptionUrl}</a>', '{$subscriptionUrl}');

        parent::down();
    }
}
