<?php

/**
 * @file api/v1/_submissions/BackendSubmissionsHandler.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class BackendSubmissionsHandler
 * @ingroup api_v1_backend
 *
 * @brief Handle API requests for backend operations.
 *
 */

import('lib.pkp.api.v1._submissions.PKPBackendSubmissionsHandler');

class BackendSubmissionsHandler extends PKPBackendSubmissionsHandler {

	/**
	 * Constructor
	 */
	public function __construct() {
		\HookRegistry::register('API::_submissions::params', array($this, 'addAppSubmissionsParams'));

		$this->_endpoints = array_merge_recursive($this->_endpoints, array(
			'POST' => array(
				array(
					'pattern' => '/{contextPath}/api/{version}/_submissions/{submissionId}/payment',
					'handler' => array($this, 'payment'),
					'roles' => array(
						ROLE_ID_SUB_EDITOR,
						ROLE_ID_MANAGER,
						ROLE_ID_ASSISTANT,
					),
				),
			),
		));

		parent::__construct();
	}

	/**
	 * Add ojs-specific parameters to the getMany request
	 *
	 * @param $hookName string
	 * @param $args array [
	 * 		@option $params array
	 * 		@option $slimRequest Request Slim request object
	 * 		@option $response Response object
	 * ]
	 */
	public function addAppSubmissionsParams($hookName, $args) {
		$params =& $args[0];
		$slimRequest = $args[1];
		$response = $args[2];

		$originalParams = $slimRequest->getQueryParams();

		if (!empty($originalParams['sectionIds'])) {
			if (is_array($originalParams['sectionIds'])) {
				$params['sectionIds'] = array_map('intval', $originalParams['sectionIds']);
			} else {
				$params['sectionIds'] = array((int) $originalParams['sectionIds']);
			}
		}
	}

	/**
	 * Change the status of publication payments.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	public function payment($slimRequest, $response, $args) {
		$request = $this->getRequest();
		$context = $request->getContext();
		$submissionId = (int) $args['submissionId'];
		$submissionDao = DAORegistry::getDAO('SubmissionDAO'); /* @var $submissionDao SubmissionDAO */
		$submission = $submissionDao->getById($submissionId);

		if (!$submission || $context->getId() != $submission->getContextId()) {
			return $response->withStatus(404)->withJsonError('api.404.resourceNotFound');
		}

		$paymentManager = \Application::getPaymentManager($context);
		$completedPaymentDao = \DAORegistry::getDAO('OJSCompletedPaymentDAO'); /* @var $completedPaymentDao OJSCompletedPaymentDAO */
		$publicationFeeEnabled = $paymentManager->publicationEnabled();
		$publicationFeePayment = $completedPaymentDao->getByAssoc(null, PAYMENT_TYPE_PUBLICATION, $submission->getId());

		switch ($request->getUserVar('publicationFeeStatus'])) {
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
					$submission->getId(),
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
				$submitterAssignments = $stageAssignmentDao->getBySubmissionAndRoleId($submission->getId(), ROLE_ID_AUTHOR);
				$submitterAssignment = $submitterAssignments->next();
				$queuedPayment = $paymentManager->createQueuedPayment(
					$request,
					PAYMENT_TYPE_PUBLICATION,
					$submitterAssignment->getUserId(),
					$submission->getId(),
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
