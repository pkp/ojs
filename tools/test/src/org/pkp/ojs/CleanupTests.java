package org.pkp.ojs;

public class CleanupTests extends OJSTestCase {
	public CleanupTests(String name) {
		super(name);
	}

	public void testDeleteJournal() throws Exception {
		if (assumeProperty("disableJournalCreateDel", "Set this property to true to disable journal creation and deletion.").equals("true")) return;

		log("Deleting test journal... ");
		beginAt("/");
		logIn(adminLogin, adminPassword);
		clickLinkWithText("User Home");
		clickLinkWithText("Site Administrator");
		clickLinkWithText("Hosted Journals");
		clickLinkWithText("Delete");
		assertTextPresent("No journals have been created");
		log("Done.\n");
	}
}
