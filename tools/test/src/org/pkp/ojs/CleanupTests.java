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
		setExpectedJavaScriptConfirm("Are you sure you want to permanently delete this journal and all of its contents?", true);
		clickLinkWithText("Delete");
		assertTextPresent("No journals have been created");
		log("Done.\n");
	}
}
