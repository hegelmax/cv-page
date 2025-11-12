<?php
// PROD defaults
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);

const DEFAULT_USER = 'default';
const DEMO_USER    = 'demo';

define('ROOT_DIR'		,realpath( __DIR__ . '/..'));
define('CACHE_DIR'		,ROOT_DIR . '/cache');
define('LIB_DIR'		,ROOT_DIR . '/lib');
define('LOG_DIR'		,ROOT_DIR . '/log');
define('DATA_DIR'		,ROOT_DIR . '/data');
define('TEMPLATES_DIR'	,ROOT_DIR . '/templates');

ini_set('error_log', LOG_DIR.'/php-error.log');

require_once LIB_DIR . '/render.php';
require_once LIB_DIR . '/functions.php';
require_once LIB_DIR . '/router.php';

if (!is_dir(CACHE_DIR)) {
    // If it doesn't exist, attempt to create it
    // The third argument 'true' enables recursive creation of parent directories
    // The second argument '0777' sets the permissions for the directory
    if (!mkdir(CACHE_DIR, 0777, true)) {
        // Handle the case where directory creation fails
        die('Failed to create directory: ' . CACHE_DIR);
    }
}