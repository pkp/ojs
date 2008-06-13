<?php

import('classes.plugins.ImplicitAuthPlugin');

class ShibAuthPlugin extends ImplicitAuthPlugin {

	function register($category, $path) {
	
		// We use the callback mechanism to call the implicitAuth function.
		//
		// If there ends up being another implicitAuth plugin - then this registration statement 
		// should be removed - so that the other plugin gets called
		
		HookRegistry::register('ImplicitAuthPlugin::implicitAuth', array(&$this, 'implicitAuth'));
			
		$success = parent::register($category, $path);
		$this->addLocaleData();
		return $success;
	}
	
	
	function getName() {
		return "ShibAuthPlugin";
	}

	function getDisplayName() {
		return Locale::translate('plugins.implicitAuth.shibboleth.displayName');
	}	
	
	function getDescription() {
		return Locale::translate('plugins.implicitAuth.shibboleth.description');
	}
	
	
	/**
	 * Return true that this is a site-wide plugin (over-riding superclass setting).
	 */
	
	function isSitePlugin() {
		return true;
	}
	
	// Log a user in after they have been authenticated via Shibboleth
	
	function implicitAuth($hookname, $args) {	
	
			// Set retuser to point to the user that was passed by reference
			
			$retuser =& $args[0];
						
			// Get the name of the field to use a UIN (primary key) from config file
			
			$uin = Config::getVar('security', 'implicit_auth_header_uin'); // For TDL this is HTTP_TDL_TDLUID
			
			if ($uin == "") 
				die("Implicit Auth enabled in config file - but implicit_auth_uin not defined.");

			// If we can't find the user's UIN - this is a problem - send back to login screen (for the lack of something better to do)
			
			if (!isset($_SERVER[$uin])) {

				syslog(LOG_ERR, "Implicit Auth enabled in config file - but expected header variables not found.");
			
				Validation::logout();
				Validation::redirectLogin();
			}
			
			// Get the header variable indicated by the config variable
				
			$uid = $_SERVER[$uin];		
			
			// If we dont have a UIN in the header then we can't continue - so send them back to the login screen.

			if ($uid == null) {
				
				Validation::logout();
				Validation::redirectLogin();
			}
				
			// Get email from header -- after consulting the map 
			
			$email_key = Config::getVar('security', 'implicit_auth_header_email');
			
			if ($email_key == "") 
				die("Implicit Auth enabled in config file - but email is not defined.");
			
			$email = $_SERVER[$email_key];
			
			// Get the user dao - so we can look up the user
			
			$userDao = &DAORegistry::getDAO('UserDAO');
	
			// Get user by auth string
			
			$user = &$userDao->getUserByAuthStr($uid, true); 	
			
			if (isset($user)) {
				syslog(LOG_ERR, "Found user by uid: " . $uid . " Returning user.");
				syslog(LOG_ERR, "Users UID: " . $user->getAuthStr());
				
				// Go see if this user should be an admin
				
				ShibAuthPlugin::implicitAuthAdmin($user->getUserId(), $user->getAuthStr());
				$retuser = $user;
				
				syslog(LOG_ERR, " In ShibAuthPlugin username: " . $retuser->getUsername());
				return true;
			}
				

			// If we were not succsessful getting user by UIN - see if we can get the user by email.
			// If we find a user with this email - but with an existing UID - then this is a problem
				
			$user = &$userDao->getUserByEmail($email);
			
			if (isset($user)) {
							
				if ($user->getAuthStr() != "") {
					unset($user);
					die("Implicit Auth: New email with existing UID");
				}
				
				$user->setAuthStr($uid);
				$userDao->updateUser($user);
				
				// Go see if this user should be made an admin
				
				ShibAuthPlugin::implicitAuthAdmin($user->getUserId(), $user->getAuthStr());
				
				$retuser = $user;
				return true;
			}
			
			// User not found via UID or by email - so they are new - so just create them
			
			$user = $this->registerUserFromShib(); 
			
			// Go see if this new user should be made an admin
			
			ShibAuthPlugin::implicitAuthAdmin($user->getUserId(), $user->getAuthStr());		
			
			$retuser = $user;
			
			return true;
	}
	
	
	/**
	 * Register a new user. See classes/user/form/RegistrationForm.inc.php - for how this is done for registering a user in a non-shib environment.
	 */
	 
	function registerUserFromShib() {

		// Grab the names of the header fields from the config file
		
		$uin = Config::getVar('security', 'implicit_auth_header_uin'); // For TDL this is HTTP_TDL_TDLUID
			
		$first_name = Config::getVar('security', 'implicit_auth_header_first_name');
		$last_name = Config::getVar('security', 'implicit_auth_header_last_name');
		$email = Config::getVar('security', 'implicit_auth_header_email');
		$phone = Config::getVar('security', 'implicit_auth_header_phone');
		$initials = Config::getVar('security', 'implicit_auth_header_initials');
		$mailing_address = Config::getVar('security', 'implicit_auth_header_mailing_address');
		$uin = Config::getVar('security', 'implicit_auth_header_uin');
		
		// Create a new user object and set it's fields from the header variables
		
		$user = &new User();
		
		$user->setAuthStr($_SERVER[$uin]);

		$user->setUsername($_SERVER[$email]); # Mail is userid
		
		$user->setFirstName($_SERVER[$first_name]);
		$user->setLastName($_SERVER[$last_name]);
		$user->setEmail($_SERVER[$email]);
		$user->setPhone($_SERVER[$phone]);
		$user->setMailingAddress($_SERVER[$mailing_address]);
		$user->setDateRegistered(Core::getCurrentDate());

		// Set the user's  password to their email address. This may or may not be necessary 
		
		$email = Config::getVar('security', 'implicit_auth_header_email');
		$user->setPassword(Validation::encryptCredentials($email, $email . 'pass'));

		// Now go insert the user in the db
		
		$userDao = &DAORegistry::getDAO('UserDAO');
		
		$userDao->insertUser($user); 
	
		$userId = $user->getUserId();
		
		if (!$userId) {
			return false;
		}

		// Go put the user into the session and return it.
		
		$sessionManager = &SessionManager::getManager();
		$session = &$sessionManager->getUserSession();
		$session->setSessionVar('username', $user->getUsername());

		return $user;
	}	
	
	// If this user is in the list of admins then make sure they are set up as an admin.
	// If they are not in the list - make sure they are not an admin. This is so you can
	// take someone off the admin list - and their admin privelege will be revoked.
	
	function implicitAuthAdmin($userID, $authStr) {
	
		$adminstr=Config::getVar('security', "implicit_auth_admin_list");
		
		$adminlist=explode(" ", $adminstr);
				
		$key = array_search($authStr, $adminlist);
		
		$roleDao = &DAORegistry::getDAO('RoleDAO');
					
		// If they are in the list of users who should be admins
		
		if ($key !== false) {
		
			// and if they are not already an admin
			
			if(!$roleDao->roleExists(0, $userID, ROLE_ID_SITE_ADMIN)) {

				syslog(LOG_ERR, "Implicit Auth - Making Admin: " . $userID);
				
				// make them an admin
				
				$role = &new Role();
				$role->setJournalId(0);
				$role->setUserId($userID);
				$role->setRoleId(ROLE_ID_SITE_ADMIN);
				$roleDao->insertRole($role);	
			}
		} else {
			
			// If they are not in the admin list - then be sure they are not an admin in the role table
			
			syslog(LOG_ERR, "removing admin for: " . $userID);
			
			$roleDao->deleteRoleByUserId($userID,0, ROLE_ID_SITE_ADMIN);	
		}
	
	}
}

?>
