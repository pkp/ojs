package org.pkp.ojs;

public class CreateTestJournal extends OJSTestCase {

	public CreateTestJournal(String name) {
		super(name);
	}

	public void testBaseUrl() {
		beginAt("/");
		assertLinkPresentWithText("Open Journal Systems");
	}
}
