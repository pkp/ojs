<?php

/**
 * @file classes/notification/NotificationDAO.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NotificationDAO
 * @ingroup notification
 * @see Notification
 *
 * @brief Operations for retrieving and modifying Notification objects.
 */


import('classes.notification.Notification');

class NotificationDAO extends DAO {
	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Retrieve Notification by notification id
	 * @param $notificationId int
	 * @param $userId int optional
	 * @return object Notification
	 */
	function getById($notificationId, $userId = null) {
		$params = array((int) $notificationId);
		if ($userId) $params[] = (int) $userId;

		$result = $this->retrieve(
			'SELECT	*
			FROM	notifications
			WHERE	notification_id = ?
			' . ($userId?' AND user_id = ?':''),
			$params
		);

		$notification = $this->_fromRow($result->GetRowAssoc(false));
		$result->Close();
		return $notification;
	}

	/**
	 * Retrieve Notifications by user id
	 * Note that this method will not return fully-fledged notification objects.  Use
	 *  NotificationManager::getNotificationsForUser() to get notifications with URL, and contents
	 * @param $userId int
	 * @param $level int
	 * @param $type int
	 * @param $contextId int
	 * @param $rangeInfo Object
	 * @return object DAOResultFactory containing matching Notification objects
	 */
	function getByUserId($userId, $level = NOTIFICATION_LEVEL_NORMAL, $type = null, $contextId = null, $rangeInfo = null) {
		$params = array((int) $userId, (int) $level);
		if ($type) $params[] = (int) $type;
		if ($contextId) $params[] = (int) $contextId;

		$result = $this->retrieveRange(
			'SELECT * FROM notifications WHERE user_id = ? AND level = ?' . (isset($type) ?' AND type = ?' : '') . (isset($contextId) ?' AND context_id = ?' : '') . ' ORDER BY date_created DESC',
			$params, $rangeInfo
		);

		return new DAOResultFactory($result, $this, '_fromRow');
	}

	/**
	 * Retrieve Notifications by assoc.
	 * Note that this method will not return fully-fledged notification objects.  Use
	 *  NotificationManager::getNotificationsForUser() to get notifications with URL, and contents
	 * @param $assocType int ASSOC_TYPE_...
	 * @param $assocId int
	 * @param $userId int User ID (optional)
	 * @param $type int
	 * @param $contextId int Context (journal/press/etc.) ID (optional)
	 * @return object DAOResultFactory containing matching Notification objects
	 */
	function getByAssoc($assocType, $assocId, $userId = null, $type = null, $contextId = null) {
		$params = array((int) $assocType, (int) $assocId);
		if ($userId) $params[] = (int) $userId;
		if ($contextId) $params[] = (int) $contextId;
		if ($type) $params[] = (int) $type;

		$result = $this->retrieveRange(
			'SELECT * FROM notifications WHERE assoc_type = ? AND assoc_id = ?' .
			($userId?' AND user_id = ?':'') .
			($contextId?' AND context_id = ?':'') .
			($type?' AND type = ?':'') .
			' ORDER BY date_created DESC',
			$params
		);

		return new DAOResultFactory($result, $this, '_fromRow');
	}

	/**
	 * Retrieve Notifications by notification id
	 * @param $notificationId int
	 * @param $dateRead date
	 * @return boolean
	 */
	function setDateRead($notificationId, $dateRead) {
		$this->update(
			sprintf('UPDATE notifications
				SET date_read = %s
				WHERE notification_id = ?',
				$this->datetimeToDB($dateRead)),
			(int) $notificationId
		);

		return $dateRead;
	}

	/**
	 * Instantiate and return a new data object.
	 * @return Notification
	 */
	function newDataObject() {
		return new Notification();
	}

	/**
	 * Inserts a new notification into notifications table
	 * @param $notification object
	 * @return int Notification Id
	 */
	function insertObject($notification) {
		$this->update(
			sprintf('INSERT INTO notifications
					(user_id, level, date_created, context_id, type, assoc_type, assoc_id)
				VALUES
					(?, ?, %s, ?, ?, ?, ?)',
				$this->datetimeToDB(Core::getCurrentDate())),
			array(
				(int) $notification->getUserId(),
				(int) $notification->getLevel(),
				(int) $notification->getContextId(),
				(int) $notification->getType(),
				(int) $notification->getAssocType(),
				(int) $notification->getAssocId()
			)
		);
		$notification->setId($this->getInsertId());

		return $notification->getId();
	}

	/**
	 * Inserts or update a notification into notifications table.
	 * @param $level int
	 * @param $type int
	 * @param $assocType int
	 * @param $assocId int
	 * @param $userId int (optional)
	 * @param $contextId int (optional)
	 * @return mixed Notification or null
	 */
	function build($contextId, $level, $type, $assocType, $assocId, $userId = null) {
		$params = array(
			(int) $contextId,
			(int) $level,
			(int) $type,
			(int) $assocType,
			(int) $assocId
		);

		if ($userId) $params[] = (int) $userId;

		$this->update('DELETE FROM notifications
			WHERE context_id = ? AND level = ? AND type = ? AND assoc_type = ? AND assoc_id = ?'
			. ($userId ? ' AND user_id = ?' : ''),
			$params
		);

		$notification = $this->newDataObject();
		$notification->setContextId($contextId);
		$notification->setLevel($level);
		$notification->setType($type);
		$notification->setAssocType($assocType);
		$notification->setAssocId($assocId);
		$notification->setUserId($userId);

		$notificationId = $this->insertObject($notification);
		if ($notificationId) {
			$notification->setId($notificationId);
			return $notification;
		} else {
			return null;
		}
	}

	/**
	 * Delete Notification by notification id
	 * @param $notificationId int
	 * @param $userId int
	 * @return boolean
	 */
	function deleteById($notificationId, $userId = null) {
		$params = array((int) $notificationId);
		if (isset($userId)) $params[] = (int) $userId;
		$this->update(
			'DELETE FROM notifications WHERE notification_id = ?' . (isset($userId) ? ' AND user_id = ?' : ''),
			$params
		);
		if ($this->getAffectedRows()) {
			// If a notification was deleted (possibly validating
			// $userId in the process) delete associated settings.
			$notificationSettingsDao = DAORegistry::getDAO('NotificationSettingsDAO'); /* @var $notificationSettingsDaoDao NotificationSettingsDAO */
			$notificationSettingsDao->deleteSettingsByNotificationId($notificationId);
			return true;
		}
		return false;
	}

	/**
	 * Delete Notification
	 * @param $notification Notification
	 * @return boolean
	 */
	function deleteObject($notification) {
		return $this->deleteById($notification->getId());
	}

	/**
	 * Delete notification(s) by association
	 * @param $assocType int
	 * @param $assocId int
	 * @param $userId int optional
	 * @param $type int optional
	 * @param $contextId int optional
	 * @return boolean
	 */
	function deleteByAssoc($assocType, $assocId, $userId = null, $type = null, $contextId = null) {
		$notificationsFactory = $this->getByAssoc($assocType, $assocId, $userId, $type, $contextId);
		while ($notification = $notificationsFactory->next()) {
			$this->deleteObject($notification);
		}
	}

	/**
	 * Get the ID of the last inserted notification
	 * @return int
	 */
	function getInsertId() {
		return $this->_getInsertId('notifications', 'notification_id');
	}

	/**
	 * Get the number of unread messages for a user
	 * @param $read boolean Whether to check for read (true) or unread (false) notifications
	 * @param $contextId int
	 * @param $userId int
	 * @param $level int
	 * @return int
	 */
	function getNotificationCount($read = true, $userId, $contextId = null, $level = NOTIFICATION_LEVEL_NORMAL) {
		$params = array((int) $userId, (int) $level);
		if ($contextId) $params[] = (int) $contextId;

		$result = $this->retrieve(
			'SELECT count(*) FROM notifications WHERE user_id = ? AND date_read IS' . ($read ? ' NOT' : '') . ' NULL AND level = ?'
			. (isset($contextId) ? ' AND context_id = ?' : ''),
			$params
		);

		$returner = $result->fields[0];

		$result->Close();
		return $returner;
	}

	/**
	 * Transfer the notifications for a user.
	 * @param $oldUserId int
	 * @param $newUserId int
	 */
	function transferNotifications($oldUserId, $newUserId) {
		$this->update(
			'UPDATE notifications SET user_id = ? WHERE user_id = ?',
			array((int) $newUserId, (int) $oldUserId)
		);
	}

	/**
	 * Creates and returns an notification object from a row
	 * @param $row array
	 * @return Notification object
	 */
	function _fromRow($row) {
		$notification = $this->newDataObject();
		$notification->setId($row['notification_id']);
		$notification->setUserId($row['user_id']);
		$notification->setLevel($row['level']);
		$notification->setDateCreated($this->datetimeFromDB($row['date_created']));
		$notification->setDateRead($this->datetimeFromDB($row['date_read']));
		$notification->setContextId($row['context_id']);
		$notification->setType($row['type']);
		$notification->setAssocType($row['assoc_type']);
		$notification->setAssocId($row['assoc_id']);

		HookRegistry::call('NotificationDAO::_fromRow', array(&$notification, &$row));

		return $notification;
	}
}

?>
