<?php

/**
 * @file plugins/importexport/csv/classes/handlers/WelcomeEmailHandler.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class WelcomeEmailHandler
 *
 * @ingroup plugins_importexport_csv
 *
 * @brief Handles the welcome email when the user uses the user command
 */

namespace PKP\Plugins\ImportExport\CSV\Classes\Handlers;

class WelcomeEmailHandler
{

    /**
     * Send welcome email to user
	 *
	 * @param \Journal $context
	 * @param string $username
	 * @param \User $recipient
	 * @param \User $sender
	 * @param string $password
	 *
	 * @return void
     */
    public static function sendWelcomeEmail($context, $recipient, $sender, $password)
    {
		// Send welcome email to user
		import('lib.pkp.classes.mail.MailTemplate');
		$mail = new \MailTemplate('USER_REGISTER');
		$mail->setReplyTo($context->getData('contactEmail'), $context->getData('contactName'));
		$mail->assignParams([
			'username' => htmlspecialchars($recipient->getUsername()),
			'password' => htmlspecialchars($password),
			'userFullName' => htmlspecialchars($recipient->getFullName())
		]);
		$mail->addRecipient($recipient->getEmail(), $recipient->getFullName());
		if ($mail->isEnabled() && !$mail->send()) {
			import('classes.notification.NotificationManager');
			$notificationMgr = new \NotificationManager();
			$notificationMgr->createTrivialNotification(
				$sender->getId(), NOTIFICATION_TYPE_ERROR, array('contents' => __('email.compose.error')));
		}
    }
}
