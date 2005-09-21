package org.pkp.ojs;

import java.util.Properties;
import java.util.Enumeration;

import net.sourceforge.jwebunit.WebTestCase;

abstract class OJSTestCase extends WebTestCase {

	public OJSTestCase(String name) {
		super(name);
	}

	public void setUp() throws Exception {
		final String baseUrlPropertyName = "ojs.baseurl";
		String baseUrl = System.getProperty(baseUrlPropertyName);
		if (baseUrl == null) throw new Exception(baseUrlPropertyName + " property not defined! Set this property to the base URL of the OJS web site to be tested.");

		getTestContext().setBaseUrl(baseUrl);
	}
}
