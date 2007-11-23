package org.pkp.ojs;

import java.io.PrintStream;

public class DoInstall extends OJSTestCase {

	public DoInstall(String name) {
		super(name);
	}

	public void testInstall() throws Exception {
		if (assumeProperty("disableInstall", "Set this property to true to disable installation of OJS.").equals("true")) return;

		String filesDir = assumeProperty("filesDir", "Set this property to the files dir of the OJS install.");

		String databaseDriver = assumeProperty("databaseDriver", "Set this property to the PHP database driver name (e.g. mysql).");
		String databaseName = assumeProperty("databaseName", "Set this property to the database name (e.g. ojs2).");
		String databaseUsername = assumeProperty("databaseUsername", "Set this property to the database username (e.g. ojs2user).");
		String databasePassword = assumeProperty("databasePassword", "Set this property to the database password (e.g. SomePasswordHere).");

		log("Going to install page... ");
		beginAt("/");
		usualTests();
		assertLinkPresentWithText("OJS Installation");
		setWorkingForm("install");
		selectOptionByValue("locale", "en_US");
		selectOptionByValue("clientCharset", "utf-8");
		selectOptionByValue("connectionCharset", "utf8");
		selectOptionByValue("databaseCharset", "utf8");
		setTextField("filesDir", filesDir);
		selectOption("encryption", "SHA1");
		setTextField("adminUsername", this.adminLogin);
		setTextField("adminPassword", this.adminPassword);
		setTextField("adminPassword2", this.adminPassword);
		setTextField("adminEmail", this.adminEmail);
		selectOptionByValue("databaseDriver", databaseDriver);
		uncheckCheckbox("createDatabase");
		setTextField("databaseHost", "localhost");
		setTextField("databaseUsername", databaseUsername);
		setTextField("databasePassword", databasePassword);
		setTextField("databaseName", databaseName);
		setTextField("oaiRepositoryId", "junit.ojs.localhost");
		log("Done.\nSubmitting install form... ");
		clickButton("install");
		log("Done.\nTesting result...");
		assertTextPresent("Installation of OJS has completed successfully.");
		usualTests();
		log("Done.\n");
	}
}
