<?php
require_once 'PHPUnit/Extensions/SeleniumTestCase.php';

class OjsWebTest extends PHPUnit_Extensions_SeleniumTestCase {
	const
		// This base URL will be added to all URLs used in the test
		BASE_URL = 'http://localhost/ojs/',

		// This is the timeout for wait* operations in seconds
		TIMEOUT = 30,

		// Set the admin user/password for the test environment
		WEB_ADMIN_USER = 'admin',
		WEB_ADMIN_PASSWORD = 'admin';

	protected function setUp() {
		$this->setBrowser('*iexplore');
		$this->setBrowserUrl(self::BASE_URL);
		$this->setTimeout(self::TIMEOUT * 1000);
	}

	public function testAuthorSubmissionPrototype() {
		$this->webLogInAsAdmin();
		//$this->authorSubmissionStep1();
		//$this->authorSubmissionStep2();
		$this->open("index.php/web-test/author/submit/3?articleId=1");
		$this->authorSubmissionStep3();
		$this->webLogOut();
	}

	protected function authorSubmissionStep1() {
		$this->clickAndWait("link=Author");
		$this->assertTitleEquals("Active Submissions");
		$this->clickAndWait("link=CLICK HERE");
		$this->waitForLocation(self::BASE_URL . "index.php/web-test/author/submit/1");
		$this->assertTitleEquals("Step 1. Starting the Submission");
		$this->select("sectionId", "label=Articles");
		$this->click("checklist-1");
		$this->click("checklist-2");
		$this->click("checklist-3");
		$this->click("checklist-4");
		$this->click("checklist-5");
		$this->click("checklist-6");
		$this->clickAndWait("//input[@value='Save and continue']");
	}

	protected function authorSubmissionStep2() {
		$this->waitForLocation(self::BASE_URL . "index.php/web-test/author/submit/2?articleId=1");
		$this->assertTitleEquals("Step 2. Entering the Submission's Metadata");
		$this->type("authors-0-lastName", "Test");
		$this->type("title", "Test Article");
		$this->type("abstract", "Test Article description");
		$this->clickAndWait("//input[@value='Save and continue']");
	}

	protected function authorSubmissionStep3() {
		$this->waitForLocation(self::BASE_URL . "index.php/web-test/author/submit/3?articleId=1");
		$this->assertTitleEquals("Step 3. Uploading the Submission");
		$this->type("submissionFile", "C:\\Dokumente und Einstellungen\\Florian Grandel\\Desktop\\test2.txt");
		$this->clickAndWait("uploadSubmissionFile");
		$this->assertLocationEquals(self::BASE_URL . "index.php/web-test/author/saveSubmit/3");
		$this->assertTitleEquals("Step 3. Uploading the Submission");
	}

	/**
	 * Log into OJS as admin
	 */
	protected function webLogInAsAdmin() {
		$this->webLogInAs(self::WEB_ADMIN_USER, self::WEB_ADMIN_PASSWORD);
	}

	/**
	 * Log into OJS
	 *
	 * @param $user string
	 * @param $password string
	 */
	protected function webLogInAs($user, $password) {
		$this->open("index.php/index/login/signOut");
		$this->open("index.php/index/login");
		$this->assertLocationEquals(self::BASE_URL . "index.php/index/login");
		$this->assertTitleEquals("Log In");
		$this->type("loginUsername", $user);
		$this->type("loginPassword", $password);
		$this->clickAndWait("//div[@id='content']/form/table/tbody/tr[4]/td[2]/input");
		$this->waitForLocation(self::BASE_URL . "index.php/index/user");
		$this->assertTitleEquals("User Home");
	}

	/**
	 * Log out from OJS
	 */
	protected function webLogOut() {
		$this->open("index.php/index/login/signOut");
		$this->waitForLocation(self::BASE_URL . "index.php/index/login");
		$this->assertEquals("Log In", $this->getTitle());
	}

	/**
	 * This emulates waitForLocation from Selenium IDE
	 *
	 * @param $location string
	 */
	protected function waitForLocation($location) {
		$timeout = time() + self::TIMEOUT;
		while(true) {
			if (time() >= $timeout) $this->fail("timeout");
			if ($location == $this->getLocation()) break;
			sleep(1);
		}
	}
}
?>
