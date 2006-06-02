package org.pkp.ojs;

public class DoInstall extends OJSTestCase {

	public DoInstall(String name) {
		super(name);
	}

	public void testInstall() throws Exception {
		String filesDir = assumeProperty("ojs.filesdir", "Set this property to the files dir of the OJS install.");
		String databaseDriver = assumeProperty("ojs.databasedriver", "Set this property to the PHP database driver name (e.g. mysql).");
		
		beginAt("/");
		usualTests();
		assertLinkPresentWithText("OJS Installation");
		setWorkingForm("install");
		setFormElement("locale", "en_US");
		setFormElement("clientCharset", "utf-8");
		setFormElement("connectionCharset", "");
		setFormElement("databaseCharset", "");
		setFormElement("filesDir", filesDir);
		setFormElement("encryption", "sha1");
		setFormElement("adminUsername", this.adminLogin);
		setFormElement("adminPassword", this.adminPassword);
		setFormElement("adminPassword2", this.adminPassword);
		setFormElement("adminEmail", this.adminEmail);
		setFormElement("databaseDriver", databaseDriver);
		uncheckCheckbox("createDatabase");
		setFormElement("databaseHost", "localhost");
		setFormElement("databaseUsername", "ojs2");
		setFormElement("databasePassword", "ojs2");
		setFormElement("databaseName", "ojs2-junit");
		setFormElement("oaiRepositoryId", "junit.ojs.localhost");
		submit();
		assertTextPresent("Installation of OJS has completed successfully.");
		usualTests();
	}
}
