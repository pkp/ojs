package org.pkp.ojs;

import net.sourceforge.jwebunit.junit.WebTestCase;

import com.meterware.httpunit.WebForm;
import com.meterware.httpunit.WebConversation;
import com.meterware.httpunit.WebRequest;
import com.meterware.httpunit.WebResponse;
import com.meterware.httpunit.PostMethodWebRequest;

import org.apache.commons.httpclient.Header;
import org.apache.commons.httpclient.HttpClient;
import org.apache.commons.httpclient.HttpMethod;
import org.apache.commons.httpclient.HttpConnection;
import org.apache.commons.httpclient.methods.GetMethod;
import org.apache.commons.httpclient.methods.PostMethod;
import org.apache.commons.httpclient.methods.multipart.MultipartRequestEntity;
import org.apache.commons.httpclient.methods.multipart.Part;
import org.apache.commons.httpclient.methods.multipart.StringPart;

import java.io.File;
import java.io.ByteArrayOutputStream;
import java.io.PrintStream;
import java.io.FileInputStream;

import java.util.Properties;

import java.net.HttpURLConnection;

abstract class OJSTestCase extends WebTestCase {
	final static String adminLogin = "test_admin";
	final static String adminPassword = "test_admin_pass";
	final static String adminEmail = "ojs-junit-admin@mailinator.com";

	Properties p;

	public OJSTestCase(String name) {
		super(name);

		HttpURLConnection.setFollowRedirects(true);
		HttpMethod getRequest = new GetMethod();
                getRequest.setFollowRedirects(true);
	}

	public void setUp() throws Exception {
		p = new Properties();
		p.load(new FileInputStream("testing.properties"));

		String baseUrl = assumeProperty("baseUrl", "Specify a base URL to the JUnit testing installation of OJS.");

		getTestContext().setBaseUrl(baseUrl);

	}

	public String assumeProperty(String name, String message) throws Exception {
		String value = p.getProperty(name);
		if (value == null || value == "") throw new Exception(name + " property not defined! " + message);
		return value;
	}

	/* public void setFormElement(String name, File file) throws Exception {
		HttpUnitDialog d = getDialog();
		WebForm f = d.getForm();
		f.setParameter(name, file);
	}*/

	public void usualTests() throws Exception {
		assertTextNotPresent("Missing locale key");
		assertTextNotPresent("Error:");
		assertTextNotPresent("Notice:");
		validate();
	}

	public void validate() throws Exception {
		if (assumeProperty("disableValidator", "Set this property to true to disable validation.").equals("true")) return;

		final String w3cUrl = "http://validator.w3.org/check";

		PostMethod post = new PostMethod(w3cUrl);
		String validateHtml = getPageSource();
		Part[] parts = {
			new StringPart("fragment", validateHtml),
			new StringPart("check", "Check")
		};
		post.setRequestEntity(new MultipartRequestEntity(parts, post.getParams()));
		HttpClient client = new HttpClient();
		int status = client.executeMethod(post);
		String body = post.getResponseBodyAsString();
		if (body.indexOf("This Page Is Valid") == -1) {
			System.out.println("RESPONSE: " + body);
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
		setTextField("username", username);
		setTextField("password", password);
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
		System.err.flush();
	}
}
