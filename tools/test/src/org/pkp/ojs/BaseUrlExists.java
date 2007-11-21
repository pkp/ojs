package org.pkp.ojs;

public class BaseUrlExists extends OJSTestCase {

	public BaseUrlExists(String name) {
		super(name);
	}

	public void testBaseUrl() {
		beginAt("/");
		assertLinkPresentWithText("Home");
	}
}
