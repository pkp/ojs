package org.pkp.ojs;

import junit.framework.Test;
import junit.framework.TestSuite;

/**
 * Run all JUnit tests for OJS.
 */
public class AllTests extends TestSuite {
	public static Test suite() throws Exception {
		TestSuite suite = new TestSuite();
		suite.addTestSuite(BaseUrlExists.class);

		suite.addTestSuite(DoInstall.class);

		suite.addTestSuite(FreshInstallTests.class);
		suite.addTestSuite(CleanupTests.class);
		return suite;
	}

	 public static void main( String[] args ) throws Exception {
		junit.textui.TestRunner.run(suite());
	}
}

