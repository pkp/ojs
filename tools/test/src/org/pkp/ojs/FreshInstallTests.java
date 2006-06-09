package org.pkp.ojs;

public class FreshInstallTests extends OJSTestCase {
	final static String siteTitle = "Testing Site Title Here";
	final static String siteIntro = "This is the introduction for the testing site.";
	final static String siteAbout = "Here is a little bit about the site.";
	final static String siteContactName = "SiteContact NameHere";
	final static String siteContactEmail = "siteContact@mailinator.com";

	final static String journalTitle = "Testing Journal Title";
	final static String journalDescription = "This is a description of the testing journal.";
	final static String journalPath = "test_journal";

	public FreshInstallTests(String name) {
		super(name);
	}

	public void testAnonymousLinks() throws Exception {
		beginAt("/");
		clickLinkWithText("About"); // FIXME: This is not validated because of an empty <ul></ul>
		clickAndTest("About this Publishing System");
		clickAndTest("Log In");
		clickAndTest("Forgot your password?");
		clickAndTest("Not a user?");
		clickAndTest("Register");
		clickAndTest("Search");
	}

	public void testAdminFunctions() throws Exception {
		beginAt("/");
		logIn(adminLogin, adminPassword);
		clickAndTest("User Home");
		clickAndTest("Site Administrator");
		clickAndTest("Site Settings");
		setWorkingForm("settings");
		setFormElement("title", siteTitle);
		setFormElement("intro", siteIntro);
		setFormElement("about", siteAbout);
		setFormElement("contactName", siteContactName);
		setFormElement("contactEmail", siteContactEmail);
		setFormElement("minPasswordLength", "7");
		submit();
		usualTests();
		assertTextNotPresent("Errors occurred processing this form");
		assertTextPresent("Your changes have been saved.");

		clickLinkWithText("User Home");
		clickLinkWithText("Site Administrator");
		clickLinkWithText("Site Settings");
		setWorkingForm("settings");
		assertFormElementEquals("title", siteTitle);
		assertFormElementEquals("intro", siteIntro);
		assertFormElementEquals("about", siteAbout);
		assertFormElementEquals("contactName", siteContactName);
		assertFormElementEquals("contactEmail", siteContactEmail);
		assertFormElementEquals("minPasswordLength", "7");
		
		logOut();

		clickLinkWithText("About"); // FIXME: Again, exempted from validation
		assertTextPresent(siteAbout);

		clickAndTest("Home");
		assertTextPresent(siteIntro);

		if (assumeProperty("disableJournalCreateDel", "Set this property to true to disable journal creation and deletion.").equals("true")) return;

		logIn(adminLogin, adminPassword);
		clickLinkWithText("User Home");
		clickLinkWithText("Site Administrator");
		clickAndTest("Hosted Journals");
		clickAndTest("Create Journal");
		setWorkingForm("journal");

		setFormElement("title", journalTitle);
		setFormElement("description", journalDescription);
		setFormElement("path", journalPath);
		submit();

		usualTests();
		assertTextNotPresent("Errors occurred processing this form");
		
		clickAndTest(journalTitle); // Go into management
	}

}
