<?php

/**
 * @see https://github.com/ircmaxell/password_compat
 */

// password_compat uses namespaces, so ensure PHP version supports this
if (version_compare(phpversion(), '5.3.0', '>=')) {
	require_once(BASE_SYS_DIR . '/lib/password_compat/lib/password.php');
}    

// set a flag to indicate whether modern encryption is supported
define('LEGACY_ENCRYPTION', !(
    defined('PASSWORD_BCRYPT') &&
        function_exists('password_hash') && 
            function_exists('password_verify') && 
                function_exists('password_needs_rehash')
));
