<?php

/**
 * CaptchaManager.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package file
 *
 * Class defining operations for temporary file management.
 *
 * $Id$
 */

import('file.FileManager');

class CaptchaManager {
	/**
	 * Constructor.
	 * Create a manager for handling temporary file uploads.
	 */
	function CaptchaManager() {
		$this->_performPeriodicCleanup();
	}

	function &createCaptcha($length = 6) {
		$captchaDao =& DAORegistry::getDAO('CaptchaDAO');
		$session =& Request::getSession();
		if ($session && $this->isEnabled()) {
			$captcha =& new Captcha();
			$captcha->setSessionId($session->getId());
			$captcha->setValue(Validation::generatePassword($length));
			$captchaDao->insertCaptcha($captcha);
		} else {
			$captcha = null;
		}
		return $captcha;
	}

	function getWidth() {
		return 300;
	}

	function getHeight() {
		return 100;
	}

	function getMimeType() {
		return 'image/png';
	}

	function generateImage(&$captcha) {
		$width = $this->getWidth();
		$height = $this->getHeight();
		$length = String::strlen($captcha->getValue());
		$value = $captcha->getValue();

		$image = imagecreatetruecolor($width, $height);
		$fg = imagecolorallocate($image, rand(128, 255), rand(128, 255), rand(128, 255));
		$bg = imagecolorallocate($image, rand(0, 64), rand(0, 64), rand(0, 64));
		imagefill($image, $width/2, $height/2, $bg);

		$xStart = rand($width / 12, $width / 3);
		$xEnd = rand($width * 2 / 3, $width * 11 / 12);
		for ($i = 0; $i < $length; $i++) imagefttext(
			$image,
			rand(20, 34),	// Size
			rand(-15, 15),	// Angle
			$xStart + (($xEnd - $xStart) * $i / $length) + rand(-5, 5),
			rand(40, 60),	// Y position
			$fg,		// Colour
			Config::getVar('captcha', 'font_location'),	// Font
			String::substr($value, $i, 1)	// Text
		);

		// Add some noise to the image.
		for ($i = 0; $i < 20; $i++) {
			$color = imagecolorallocate($image, rand(0, 255), rand(0, 255), rand(0, 255));
			for ($j = 0; $j < 20; $j++) {
				imagesetpixel(
					$image,
					rand(0, $this->getWidth()),
					rand(0, $this->getHeight()),
					$color
				);
			}
		}

		header ('Content-type: ' . $this->getMimeType());
		imagepng($image);
		imagedestroy($image);
	}

	function isEnabled() {
		return (
			function_exists('imagecreatetruecolor') &&
			Config::getVar('captcha', 'captcha')
		);
	}

	function _performPeriodicCleanup() {
		if (time() % 100 == 0) {
			$captchaDao =& DAORegistry::getDAO('CaptchaDAO');
			$expiredCaptchas = $captchaDao->getExpiredCaptchas();
			foreach ($expiredCaptchas as $expiredCaptcha) {
				$captchaDao->deleteCaptcha($expiredCaptcha);
			}
		}
	}
}

?>
