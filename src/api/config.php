<?php

set_error_handler(function($errno, $errstr) {
    var_dump($errno);
    var_dump($errstr);
    var_dump(debug_backtrace());
    die();
});

date_default_timezone_set('Australia/Sydney');

// the maximum number of ids to process for a rest request
define ('REST_MATCH_REQUEST_LIMIT', 10);
define ('REST_ITEM_REQUEST_LIMIT', 60);
define ('REST_HERO_REQUEST_LIMIT', 10);
define ('REST_ABILITY_REQUEST_LIMIT', 40);

// the maximum number of items per page
define ('PAGEINATE_LIMIT', 25);

// dota2 api key (you can get_info it here - http://steamcommunity.com/dev/apikey)
define ('API_KEY', '##############');

//The language to retrieve results in (see http://en.wikipedia.org/wiki/ISO_639-1 for the language codes (first two characters) and http://en.wikipedia.org/wiki/List_of_ISO_639-1_codes for the country codes (last two characters))
define ('LANGUAGE', 'en_us');

error_reporting(0);

set_time_limit(0);

/**
 * Basic class with system's configuration data
 */
class config {
    /**
     * Configuration data
     * @access private
     * @static
     * @var array
     */
    private static $_data = array(
        // CONNECITON INFO GOES HERE
        'db_user' => '##############',
        'db_pass' => '##############',
        'db_host' => '127.0.0.1',
        'db_name' => '##############',
        'db_table_prefix' => ''
    );

    /**
     * Private construct to avoid object initializing
     * @access private
     */
    private function __construct() {}
    public static function init() {
        self::$_data['base_path'] = dirname(__FILE__).DIRECTORY_SEPARATOR.'includes';
        $db = db::obtain(self::get('db_host'), self::get('db_user'), self::get('db_pass'), self::get('db_name'), self::get('db_table_prefix'));
        if (!$db->connect_pdo()) {
            die('DB CONNECTION ERROR');
        };
    }
    /**
     * Get configuration parameter by key
     * @param string $key data-array key
     * @return null
     */
    public static function get($key) {
        if(isset(self::$_data[$key])) {
            return self::$_data[$key];
        }
        return null;
    }
}

config::init();

function __autoload($class) {
    scan(config::get('base_path'), $class);
}

function scan($path = '.', $class) {
    $ignore = array('.', '..');
    $dh = opendir($path);
    while(false !== ($file = readdir($dh))){
        if(!in_array($file, $ignore)) {
            if(is_dir($path.DIRECTORY_SEPARATOR.$file)) {
                scan($path.DIRECTORY_SEPARATOR.$file, $class);
            }
            else {
                if ($file === 'class.'.$class.'.php') {
                    require_once($path.DIRECTORY_SEPARATOR.$file);
                    return;
                }
            }
        }
    }
    closedir($dh);
}