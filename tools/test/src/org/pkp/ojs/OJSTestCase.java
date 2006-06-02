package org.pkp.ojs;

import net.sourceforge.jwebunit.WebTestCase;
import net.sourceforge.jwebunit.HttpUnitDialog;

import com.meterware.httpunit.WebForm;
import com.meterware.httpunit.WebConversation;
import com.meterware.httpunit.WebRequest;
import com.meterware.httpunit.WebResponse;
import com.meterware.httpunit.PostMethodWebRequest;

import org.apache.commons.httpclient.HttpClient;
import org.apache.commons.httpclient.HttpConnection;
import org.apache.commons.httpclient.methods.PostMethod;
import org.apache.commons.httpclient.methods.multipart.MultipartRequestEntity;
import org.apache.commons.httpclient.methods.multipart.Part;
import org.apache.commons.httpclient.methods.multipart.StringPart;

import java.io.File;
import java.io.ByteArrayOutputStream;
import java.io.PrintStream;

abstract class OJSTestCase extends WebTestCase {
	final static String adminLogin = "test_admin";
	final static String adminPassword = "test_admin_pass";
	final static String adminEmail = "ojs-junit-admin@mailinator.com";

	public OJSTestCase(String name) {
		super(name);
	}

	public void setUp() throws Exception {
		final String baseUrlPropertyName = "ojs.baseurl";
		String baseUrl = System.getProperty(baseUrlPropertyName);
		if (baseUrl == null) throw new Exception(baseUrlPropertyName + " property not defined! Set this property to the base URL of the OJS web site to be tested.");

		getTestContext().setBaseUrl(baseUrl);
	}

	public String assumeProperty(String name, String message) throws Exception {
		String value = System.getProperty(name);
		if (value == null || value == "") throw new Exception(name + " property not defined! " + message);
		return value;
	}

	public void setFormElement(String name, File file) {
		HttpUnitDialog d = getDialog();
		WebForm f = d.getForm();
		f.setParameter(name, file);
	}

	public void usualTests() throws Exception {
		assertTextNotPresent("Missing locale key");
		assertTextNotPresent("Error:");
		assertTextNotPresent("Notice:");
		validate();
	}

	public void validate() throws Exception {
		final String w3cUrl = "http://validator.w3.org/check";
		ByteArrayOutputStream ba = new ByteArrayOutputStream();
		dumpResponse(new PrintStream(ba));

		PostMethod post = new PostMethod(w3cUrl);
		Part[] parts = {
			new StringPart("fragment", ba.toString())
		};
		post.setRequestEntity(new MultipartRequestEntity(parts, post.getParams()));
		HttpClient client = new HttpClient();
		int status = client.executeMethod(post);
		String body = post.getResponseBodyAsString();
		if (body.indexOf("This Page Is Valid") == -1) {
			throw new Exception ("This page did not validate!");
		}
	}

	public void clickAndTest(String name) throws Exception {
		log("Clicking link with text \"" + name + "\"... ");
		clickLinkWithText(name);
		usualTests();
		log("Done.\n");
	}

	public void logIn(String username, String password) throws Exception {
		log("Logging in as \"" + username + "\"... ");
		clickLinkWithText("Log In");
		setWorkingForm("login");
		setFormElement("username", username);
		setFormElement("password", password);
		submit();
		usualTests();
		assertTextNotPresent("Invalid username or password");
		assertTextPresent(username); // Check sidebar for username
		log("Done.\n");
	}

	public void logOut() {
		log("Logging out... ");
		clickLinkWithText("Log Out");
		assertTextNotPresent("User Home");
		log("Done.\n");
	}

	public void log(String text) {
		System.err.print(text);
	}
}
