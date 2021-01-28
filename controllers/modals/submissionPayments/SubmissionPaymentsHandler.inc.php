<?php

/**
 * @file controllers/modals/submissionPayments/SubmissionPaymentsHandler.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubmissionPaymentsHandler
 * @ingroup controllers_modals_submissionPayments
 *
 * @brief A handler to manage publication fees.
 */

// Import the base Handler.
import('classes.handler.Handler');

class SubmissionPaymentsHandler extends Handler {

	/** @var Submission **/
	public $submission;

	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(
			[ROLE_ID_SUB_EDITOR, ROLE_ID_MANAGER, ROLE_ID_ASSISTANT],
			['save']
		);
	}


	//
	// Overridden methods from Handler
	//
	/**
	 * @copydoc PKPHandler::initialize()
	 */
	function initialize($request) {
		parent::initialize($request);
		$this->submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		$this->setupTemplate($request);
	}

	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.SubmissionAccessPolicy');
		$this->addPolicy(new SubmissionAccessPolicy($request, $args, $roleAssignments));
		return parent::authorize($request, $args, $roleAssignments);
	}


	//
	// Public handler methods
	//
	/**
	 * Change the status of publication payments.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	public function save($args, $request) {
		$context = $request->getContext();
		$paymentManager = \Application::getPaymentManager($context);
		$completedPaymentDao = \DAORegistry::getDAO('OJSCompletedPaymentDAO'); /* @var $completedPaymentDao OJSCompletedPaymentDAO */
		$publicationFeeEnabled = $paymentManager->publicationEnabled();
		$publicationFeePayment = $completedPaymentDao->getByAssoc(null, PAYMENT_TYPE_PUBLICATION, $this->submission->getId());
		switch ($request->getUserVar('publicationFeeStatus')) {
			case 'waived':
				// Check if a waiver already exists; if so, don't do anything.
				if ($publicationFeePayment && !$publicationFeePayment->getAmount()) break;

				// If a fulfillment (nonzero amount) already exists, remove it.
				if ($publicationFeePayment) $completedPaymentDao->deleteById($publicationFeePayment->getId());

				// Record a waived payment.
				$queuedPayment = $paymentManager->createQueuedPayment(
					$request,
					PAYMENT_TYPE_PUBLICATION,
					$request->getUser()->getId(),
					$this->submission->getId(),
					0, '' // Zero amount, no currency
				);
				$paymentManager->queuePayment($queuedPayment);
				$paymentManager->fulfillQueuedPayment($request, $queuedPayment, 'ManualPayment');
				break;
			case 'paid':
				// Check if a fulfilled payment already exists; if so, don't do anything.
				if ($publicationFeePayment && $publicationFeePayment->getAmount()) break;

				// If a waiver (0 amount) already exists, remove it.
				if ($publicationFeePayment) $completedPaymentDao->deleteById($publicationFeePayment->getId());

				// Record a fulfilled payment.
				$stageAssignmentDao = \DAORegistry::getDAO('StageAssignmentDAO'); /* @var $stageAssignmentDao StageAssignmentDAO */
				$submitterAssignments = $stageAssignmentDao->getBySubmissionAndRoleId($this->submission->getId(), ROLE_ID_AUTHOR);
				$submitterAssignment = $submitterAssignments->next();
				$queuedPayment = $paymentManager->createQueuedPayment(
					$request,
					PAYMENT_TYPE_PUBLICATION,
					$submitterAssignment->getUserId(),
					$this->submission->getId(),
					$context->getSetting('publicationFee'),
					$context->getSetting('currency')
				);
				$paymentManager->queuePayment($queuedPayment);
				$paymentManager->fulfillQueuedPayment($request, $queuedPayment, 'Waiver');
				break;
			case 'unpaid':
				if ($publicationFeePayment) $completedPaymentDao->deleteById($publicationFeePayment->getId());
				break;
			default: throw new Exception('Unknown fee payment status!');
		}
	}
}
