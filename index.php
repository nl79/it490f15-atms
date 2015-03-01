<?php
#set errors reporting
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);

date_default_timezone_set('America/New_York');

// ****************************************** //
// ************ ERROR MANAGEMENT ************ //

// Create the error handler:
function my_error_handler ($e_number, $e_message, $e_file, $e_line, $e_vars) {

	// Build the error message:
	$message = "An error occurred in script '$e_file' on line $e_line: $e_message\n";
	
	// Add the date and time:
	$message .= "Date/Time: " . date('n-j-Y H:i:s') . "\n";
	
	if (!LIVE) { // Development (print the error).

		// Show the error message:
		echo '<div class="error">' . nl2br($message);
	
		// Add the variables and a backtrace:
		echo '<pre>' . print_r ($e_vars, 1) . "\n";
		#debug_print_backtrace();
		echo '</pre></div>';
		
	} else { // Don't show the error:

		// Send an email to the admin:
		$body = $message . "\n" . print_r ($e_vars, 1);
		mail(EMAIL, 'Site Error!', $body, 'From: email@example.com');
	
		// Only print an error message if the error isn't a notice:
		if ($e_number != E_NOTICE) {
			echo '<div class="error">A system error occurred. We apologize for the inconvenience.</div><br />';
		}
	} // End of !LIVE IF.

} // End of my_error_handler() definition.

// Use my error handler:
set_error_handler ('my_error_handler');

// ************ ERROR MANAGEMENT ************ //
// ****************************************** //


// Site URL (base for all redirections):
#define ('BASE_URL', 'https://web.njit.edu/~nl79/it302/it302register/public/');
#define ('BASE_URL', 'http://osl81.njit.edu/~nl79/it302/it302register/public/');
define ('BASE_URL', '/it302register/public/');


/*
 *require the autoloader class.
 *set the autoloader. 
 */ 
require_once('library/loader.class.php'); 
spl_autoload_register('library\\loader::load');

require_once('mysqli_connect.php');

/*
 *create a router object.
 */ 
$router = new library\Router(); 

#build the page object. 
$page = 'controller\\' . $router->getNode(); 
$controller = new $page($router);   
