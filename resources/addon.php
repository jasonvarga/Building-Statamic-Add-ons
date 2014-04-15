<?php
/**
 * Addon
 * Abstract implementation for extending Statamic
 *
 * @author      Mubashar Iqbal
 * @author      Jack McDade
 * @author      Fred LeBlanc
 *
 * @copyright   2013 Statamic
 * @link        http://www.statamic.com
 * @license     http://www.statamic.com
 */
use Symfony\Component\Finder\Finder as Finder;

abstract class Addon
{
    /**
     * Contextual log object for this add-on
     * @protected ContextualLog
     */
    protected $log;

    /**
     * Contextual session object for this add-on
     * @protected ContextualSession
     */
    protected $session;

    /**
     * Contextual cookies object for this add-on
     * @protected ContextualCookies
     */
    protected $cookies;

    /**
     * Contextual add-on object for this add-on
     * @protected ContextualAddon
     */
    protected $addon;

    /**
     * Contextual cache object for this add-on
     * @protected ContextualCache
     */
    protected $cache;

    /**
     * Contextual flash object for this add-on
     * @protected ContextualFlash
     */
    protected $flash;

    /**
     * Contextual blink object for this add-on
     * @protected ContextualBlink
     */
    protected $blink;

    /**
     * Contextual token object for this add-on
     * @protected ContextualTokens
     */
    protected $tokens;

    /**
     * Related tasks object if it exists
     * @protected Tasks
     */
    protected $tasks;

    /**
     * Name of Addon
     * @protected string
     */
    protected $addon_name;

    /**
     * Type of Addon
     * @protected string
     */
    protected $addon_type;

    /**
     * URL path to Addon
     * @protected string
     */
    protected $addon_path;

    /**
     * File path to Addon in filesystem
     * @protected string
     */
    protected $addon_location;

    /**
     * Array of settings from this add-ons config
     * @protected array
     */
    protected $config;

    /**
     * Should we skip loading tasks? Only for the Tasks object
     * @protected boolean
     */
    protected $skip_tasks = false;

    /**
     * Array of add-on information for caching purposes
     * @protected array
     */
    protected static $addon_cache = array();


    /**
     * Initializes object
     *
     * @return Addon
     */
    public function __construct()
    {
        $this->addon_name     = $this->parseAddonName();
        $this->addon_type     = $this->parseAddonType();
        $this->addon_path     = Path::tidy(Config::getSiteRoot() . Config::getAddonPath($this->addon_name));
        $this->addon_location = BASE_PATH . '/' . self::find($this->addon_name);
        $this->config         = $this->getConfig();
        $this->tasks          = (!$this->skip_tasks) ? $this->getTasks() : null;

        // contextual objects
        $this->log        = ContextualLog::createObject($this);
        $this->cache      = ContextualCache::createObject($this);     // save data in file, longest cache available
        $this->cookies    = ContextualCookies::createObject($this);   // save data in cookie
        $this->session    = ContextualSession::createObject($this);   // save data in session
        $this->flash      = ContextualFlash::createObject($this);     // save data in flash
        $this->blink      = ContextualBlink::createObject($this);     // save data until page loads, shortest cache available
        $this->tokens     = ContextualTokens::createObject($this);    // create and check tokens for form submission
        $this->css        = ContextualCSS::createObject($this);
        $this->js         = ContextualJS::createObject($this);
        $this->assets     = ContextualAssets::createObject($this);
        $this->addon      = ContextualInteroperability::createObject($this);
    }


    /**
     * Retrieves the name of this Add-on
     *
     * @return string
     */
    private function parseAddonName()
    {
        return ltrim(strstr(get_called_class(), '_'), '_');
    }


    /**
     * Retrieves the type of this Add-on
     *
     * @return string
     */
    private function parseAddonType()
    {
        return strtolower(substr(get_called_class(), 0, strpos(get_called_class(), '_')));
    }


    /**
     * Retrieves the Task object for this add-on
     *
     * @return Tasks|null
     */
    private function getTasks()
    {
        // only do this for non-Tasks objects
        if ($this->addon_type == "Tasks") {
            return null;
        }

        // check that a task file exists
        $tasks_object_path = null;
        foreach (Config::getAddOnLocations() as $location) {
            $file = BASE_PATH . '/' . $location . $this->addon_name . '/tasks.' . $this->addon_name . '.php';

            if (File::exists($file)) {
                $tasks_object_path = $file;
                break;
            }
        }

        // did we find a tasks object?
        if (!$tasks_object_path) {
            return null;
        }

        // make sure that the tasks file is loaded
        require_once $tasks_object_path;

        $class_name = "Tasks_" . $this->addon_name;
        return new $class_name();
    }


    /**
     * Returns the name of this addon
     *
     * @return string
     */
    public function getAddonName()
    {
        return $this->addon_name;
    }


    /**
     * Returns the name of this addon
     *
     * @return string
     */
    public function getAddonType()
    {
        return $this->addon_type;
    }


    /**
     * Returns the location of this addon
     *
     * @return string
     */
    public function getAddonLocation()
    {
        return $this->addon_location;
    }


    /**
     * Returns the config path for this add-on
     *
     * @return string
     */
    public function getConfigPath()
    {
        if (Folder::exists($path = Config::getConfigPath() . '/bundles/' . $this->addon_name . '/')) {
            return $path;
        } elseif (Folder::exists($path = Config::getConfigPath() . '/add-ons/' . $this->addon_name . '/')) {
            return $path;
        }

        return null;
    }


    /**
     * Retrieves the config file for this Add-on
     *
     * @return array
     */
    public function getConfig()
    {
        $config = array();

        // load defaults if they exist
        if (File::exists($file = $this->getAddonLocation() . 'default.yaml')) {
            $config = YAML::parse($file);
        }

        // load config
        if (File::exists($file = Config::getConfigPath() . '/bundles/' . $this->addon_name . '/' . $this->addon_name . '.yaml')) {
            $config = YAML::parseFile($file) + $config;
        } elseif (File::exists($file = Config::getConfigPath() . '/add-ons/' . $this->addon_name . '/' . $this->addon_name . '.yaml')) {
            $config = YAML::parseFile($file) + $config;
        } elseif (File::exists($file = Config::getConfigPath() . '/add-ons/' . $this->addon_name . '.yaml')) {
            $config = YAML::parseFile($file) + $config;
        }

        return $config;
    }


    /**
     * Loads a given config file for this add-on
     *
     * @param string  $path  Path to load relative to this add-on's config directory
     * @param boolean  $log_error  Write an error message on fail?
     * @param boolean  $throw_exception  Throw an exception on fail?
     * @return array
     * @throws Exception
     */
    public function loadConfigFile($path, $log_error=false, $throw_exception=false)
    {
        $path = trim($path);
        $path .= (preg_match('/\.y[a]?ml$/i', $path, $matches)) ? '' : '.yaml';

        $full_path = $this->getConfigPath() . $path;

        if (!File::exists($full_path)) {
            if ($log_error) {
                $this->log->debug("Could not load config `" . $path . "`, file does not exist.");
            }

            if ($throw_exception) {
                throw new Exception("Could not load config `" . $path . "`, file does not exist.");
            }

            return array();
        }

        return YAML::parseFile($full_path);
    }


    /**
     * Fetches a value from the configuration
     *
     * @param string  $keys  Key of value to retrieve
     * @param mixed  $default  Default value if no value is found
     * @param string  $validity_check  Allows a boolean callback function to validate parameter
     * @param boolean  $is_boolean  Indicates parameter is boolean
     * @param boolean  $force_lower  Force the parameter's value to be lowercase?
     * @return mixed
     */
    protected function fetchConfig($keys, $default=null, $validity_check=null, $is_boolean=false, $force_lower=true)
    {
        $keys = Helper::ensureArray($keys);

        foreach ($keys as $key) {
            if (isset($this->config[$key])) {
                $value = $this->config[$key];

                if ($force_lower) {
                    if (is_array($this->config[$key])) {
                        array_walk_recursive($value, function(&$item, $key) {
                            $item = strtolower($item);
                        });
                    } else {
                        $value = strtolower($value);
                    }
                }

                if (is_null($validity_check) || (!is_null($validity_check) && function_exists($validity_check) && $validity_check($value) === true)) {
                    // account for yes/no parameters
                    if ($is_boolean === true) {
                        return !in_array(strtolower($value), array("no", "false", "0", "", "-1"));
                    }

                    // otherwise, standard return
                    return $value;
                }
            }
        }

        return $default;
    }


    /**
     * Gets the full absolute path to a given CSS $file
     *
     * @deprecated
     * @param string  $file  CSS file to find
     * @return string
     */
    public function getCSS($file)
    {
        $this->log->warn('Use of $this->getCSS() is deprecated. Use $this->css->get() instead.');
        return $this->css->get($file);
    }


    /**
     * Gets the full absolute path to a given JavaScript $file
     *
     * @deprecated
     * @param string  $file  JavaScript file to find
     * @return string
     */
    public function getJS($file)
    {
        $this->log->warn('Use of $this->getJS() is deprecated. Use $this->js->get() instead.');
        return $this->js->get($file);
    }


    /**
     * Gets the full absolute path to a given asset $file
     *
     * @deprecated
     * @param string  $file  Asset file to find
     * @return string
     */
    public function getAsset($file)
    {
        $this->log->warn('Use of $this->getAsset() is deprecated. Use $this->assets->get() instead.');
        return $this->assets->get($file);
    }


    /**
     * Creates calls to a list of given stylesheets
     *
     * @deprecated
     * @param mixed  $stylesheet  Single or multiple stylesheets
     * @return string
     */
    public function includeCSS($stylesheet)
    {
        $this->log->warn('Use of $this->includeCSS() is deprecated. Use $this->css->link() instead.');
        return $this->css->link($stylesheet);
    }


    /**
     * Creates calls to a list of given javascript scripts
     *
     * @deprecated
     * @param mixed  $script  Single or multiple scripts
     * @return string
     */
    public function includeJS($script)
    {
        $this->log->warn('Use of $this->includeJS() is deprecated. Use $this->js->link() instead.');
        return $this->css->link($script);
    }


    /**
     * Creates an inline JavaScript block
     *
     * @deprecated
     * @param mixed  $javascript  JavaScript to put within block
     * @return string
     */
    public function inlineJS($javascript)
    {
        $this->log->warn('Use of $this->inlineJS() is deprecated. Use $this->js->inline() instead.');
        return $this->css->inline($javascript);
    }


    /**
     * Creates an inline style block
     *
     * @deprecated
     * @param mixed  $style  CSS to put within block
     * @return string
     */
    public function inlineCSS($style)
    {
        $this->log->warn('Use of $this->inlineCSS() is deprecated. Use $this->css->inline() instead.');
        return $this->css->inline($style);
    }


    /**
     * Runs a hook for this add-on
     *
     * @param string  $hook  Hook to run
     * @param string  $type  Type of hook to run (cumulative|replace|call)
     * @param mixed  $return  Pass-through values
     * @param mixed  $data  Data to pass to hook method
     * @return mixed
     */
    public function runHook($hook, $type=null, $return=null, $data=null)
    {
        return Hook::run($this->addon_name, $hook, $type, $return, $data);
    }


    /**
     * Is this a first-party bundle?
     *
     * @return boolean
     */
    public function isBundle()
    {
        return Folder::exists(APP_PATH . "/core/bundles/" . $this->getAddonName());
    }


    // --

    /**
     * Checks to see if a given $addon is installed
     *
     * @param string  $addon  Name of add-on to check for
     * @return bool
     */
    public static function isInstalled($addon)
    {
        return !is_null(self::find($addon));
    }


    /**
     * Checks to see if a given $addon has an API
     *
     * @param string  $addon  Name of add-on to check
     * @return bool
     */
    public static function hasAPI($addon)
    {
        if (!self::isInstalled($addon)) {
            return false;
        }

        return File::exists(self::$addon_cache[$addon] . "api." . $addon . ".php");
    }


    /**
     * Gets a given $addon's API object
     *
     * @param string  $addon  Name of add-on to load API for
     * @return mixed
     * @throws Exception
     */
    public static function getAPI($addon)
    {
        $class = "API_" . $addon;

        // check to see that this is installed
        // this check will guarantee self::$addon_cache[$addon] exists
        if (!self::isInstalled($addon)) {
            throw new Exception('The ' . $addon . ' addon is not installed.');
        }

        // require the file
        require_once(self::$addon_cache[$addon] . 'api.' . $addon . '.php');

        if (!self::hasAPI($addon) || !class_exists($class)) {
            throw new Exception('The ' . $addon . ' addon does not have an API.');
        }

        return new $class();
    }


    /**
     * Finds an addon
     *
     * @param string  $addon  Name of add-on to find
     * @return mixed
     */
    protected static function find($addon)
    {
        // if this is new, check for it
        if (!isset(self::$addon_cache[$addon])) {
            // this is a new one, find it
            $locations = Config::getAddOnLocations();

            $addon_location = null;
            foreach ($locations as $location) {
                if (Folder::exists($location . $addon . "/")) {
                    $addon_location = $location . $addon . "/";
                    break;
                }
            }

            // set this for future reference
            self::$addon_cache[$addon] = $addon_location;
        }

        // return it
        return self::$addon_cache[$addon];
    }
}


/**
 * ContextualObject
 * An object with the context of a given AddOn
 */
class ContextualObject
{
    /**
     * Context
     * @protected Addon
     */
    protected $context;


    /**
     * Initialized object
     *
     * @param Addon  $context  Contact object
     * @return ContextualObject
     */
    public function __construct(Addon $context)
    {
        $this->context = $context;
    }
}


/**
 * ContextualLog
 * Supports logging via an Addon context
 */
class ContextualLog extends ContextualObject
{
    /**
     * Logs a debug message
     *
     * @param string  $message  Message to log
     * @return void
     */
    public function debug($message)
    {
        $this->log(Log::DEBUG, $message);
    }


    /**
     * Logs a info message
     *
     * @param string  $message  Message to log
     * @return void
     */
    public function info($message)
    {
        $this->log(Log::INFO, $message);
    }


    /**
     * Logs a warn message
     *
     * @param string  $message  Message to log
     * @return void
     */
    public function warn($message)
    {
        $this->log(Log::WARN, $message);
    }


    /**
     * Logs a error message
     *
     * @param string  $message  Message to log
     * @return void
     */
    public function error($message)
    {
        $this->log(Log::ERROR, $message);
    }


    /**
     * Logs a fatal message
     *
     * @param string  $message  Message to log
     * @return void
     */
    public function fatal($message)
    {
        $this->log(Log::FATAL, $message);
    }


    /**
     * Logs a message to the logger with context
     *
     * @param int  $level  Level of message to log
     * @param string  $message  Message to log
     * @return void
     */
    private function log($level, $message)
    {
        switch ($level) {
            case Log::DEBUG:
                Log::debug($message, $this->context->getAddonType(), $this->context->getAddonName());
                break;

            case Log::INFO:
                Log::info($message, $this->context->getAddonType(), $this->context->getAddonName());
                break;

            case Log::WARN:
                Log::warn($message, $this->context->getAddonType(), $this->context->getAddonName());
                break;

            case Log::ERROR:
                Log::error($message, $this->context->getAddonType(), $this->context->getAddonName());
                break;

            default:
                Log::fatal($message, $this->context->getAddonType(), $this->context->getAddonName());
                break;
        }
    }


    /**
     * Creates a new ContextualLog
     *
     * @param Addon  $context  Addon context for this object
     * @return ContextualLog
     */
    public static function createObject(Addon $context)
    {
        return new ContextualLog($context);
    }
}


/**
 * ContextualSession
 * Supports session variables management via an Addon context
 */
class ContextualSession extends ContextualObject
{
    /**
     * The type of session data being stored
     */
    protected $type = '_addon_data';

    /**
     * Gets the value of a given $key for this addon's namespace
     *
     * @param string  $key  Key to retrieve
     * @param mixed  $default  Default value to return if no value exists
     * @return mixed
     */
    public function get($key, $default=null)
    {
        return Session::get($this->type, $this->context->getAddonName(), $key, $default);
    }


    /**
     * Sets the value of a given $key for this addon's namespace
     *
     * @param string  $key  Key to set
     * @param mixed  $value  Value to set
     * @return void
     */
    public function set($key, $value)
    {
        Session::set($this->type, $this->context->getAddonName(), $key, $value);
    }


    /**
     * Unsets all variables in the session within this addon's namespace
     *
     * @return void
     */
    public function destroy()
    {
        Session::destroy($this->type, $this->context->getAddonName());
    }


    /**
     * Checks to see if a given key exists in the session within this addon's namespace
     *
     * @param string  $key  Key to check
     * @return boolean
     */
    public function exists($key)
    {
        return Session::isKey($this->type, $this->context->getAddonName(), $key);
    }


    /**
     * delete
     * Unsets a given key from this addon's namespace
     *
     * @param string  $key  Key to unset
     * @return void
     */
    public function delete($key)
    {
        Session::unsetKey($this->type, $this->context->getAddonName(), $key);
    }


    /**
     * Creates a new ContextualSession
     *
     * @param Addon  $context  Addon context for this object
     * @return ContextualSession
     */
    public static function createObject(Addon $context)
    {
        return new ContextualSession($context);
    }
}


/**
 * ContextualFlash
 * Supports flash variable management via an Addon context
 */
class ContextualFlash extends ContextualObject
{
    /**
     * Creates the namespaced key to use in flash data
     *
     * @param string  $key  Key to use
     * @return string
     */
    private function getNamespacedKey($key='')
    {
        return '_addon_' . $this->context->getAddonName() . '_' . $key;
    }


    /**
     * Gets the value of a given $key for this addon's flash namespace
     *
     * @param string  $key  Key to retrieve
     * @param mixed  $default  Default value to return if $key isn't set
     * @return mixed
     */
    public function get($key, $default=null)
    {
        return Session::getFlash($this->getNamespacedKey($key), $default);
    }


    /**
     * Sets the value of a given $key for this addon's flash namespace
     *
     * @param string  $key  Key to set
     * @param mixed  $value  Value to set
     * @return void
     */
    public function set($key, $value)
    {
        Session::setFlash($this->getNamespacedKey($key), $value);
    }


    /**
     * Checks of a given $key has been set for this addon's flash namespace
     *
     * @param string  $key  Key to check
     * @return bool
     */
    public function exists($key)
    {
        return isset($_SESSION['slim.flash'][$this->getNamespacedKey($key)]);
    }


    /**
     * Deletes the value of a given $key for this addon's flash namespace
     *
     * @param string  $key  Key to delete
     * @return void
     */
    public function delete($key)
    {
        if ($this->exists($key)) {
            unset($_SESSION['slim.flash'][$this->getNamespacedKey($key)]);
        }
    }


    /**
     * Destroys all values stored in this addon's flash namespace
     *
     * @return void
     */
    public function destroy()
    {
        $namespace = $this->getNamespacedKey();

        foreach ($_SESSION['slim.flash'] as $flash_key => $flash_value) {
            if (strpos($flash_key, $namespace) !== 0) {
                continue;
            }

            unset($_SESSION['slim.flash'][$flash_key]);
        }
    }


    /**
     * Creates a new ContextualFlash
     *
     * @param Addon  $context  Addon context for this object
     * @return ContextualFlash
     */
    public static function createObject(Addon $context)
    {
        return new ContextualFlash($context);
    }
}


/**
 * ContextualCookies
 * Supports cookie variable management via an Addon context
 */
class ContextualCookies extends ContextualObject
{
    private static $cookies;
    private static $loaded = false;


    /**
     * Gets the value of a given $key for this plugin's cookie namespace
     *
     * @param string  $key  Key to retrieve
     * @param mixed  $default  Default value to return if $key isn't set
     * @return mixed
     */
    public function get($key, $default=null)
    {
        $this->load();
        return (isset(self::$cookies[$key])) ? self::$cookies[$key] : $default;
    }


    /**
     * Sets the value of a given $key for this plugin's cookie namespace
     *
     * @param string  $key  Key to set
     * @param mixed  $value  Value to set
     * @param string  $expires  Length of time cookie should exist (example: "1 day")
     * @return void
     */
    public function set($key, $value, $expires="1 day")
    {
        $this->load();
        Session::setCookie($this->context->getAddonName() . "__" . $key, $value, $expires);

        // because cookie values aren't available right away, we add to the local list now
        self::$cookies[$key] = $value;
    }


    /**
     * Unsets all variables in the session within this plugin's cookie namespace
     *
     * @return void
     */
    public function destroy()
    {
        $this->load();
        foreach (self::$cookies as $key => $value) {
            $this->delete($key);
        }
    }


    /**
     * Checks to see if a given key exists in the session within this plugin's namespace
     *
     * @param string  $key  Key to check
     * @return boolean
     */
    public function exists($key)
    {
        $this->load();
        return isset(self::$cookies[$key]);
    }


    /**
     * Unsets a given key from this plugin's cookie namespace
     *
     * @param string  $key  Key to unset
     * @return void
     */
    public function delete($key)
    {
        $this->load();
        Session::setCookie($this->context->getAddonName() . "__" . $key, "", "-1 day");

        if ($this->exists($key)) {
            unset(self::$cookies[$key]);
        }
    }


    /**
     * Loads up cookies if we haven't already done that
     *
     * @return void
     */
    public function load()
    {
        if (self::$loaded) {
            return;
        }

        self::$loaded  = true;
        self::$cookies = array();

        $namespace_length = strlen($this->context->getAddonName() . "__");

        foreach ($_COOKIE as $key => $value) {
            if (strpos($key, $this->context->getAddonName() . "__") === 0) {
                self::$cookies[substr($key, $namespace_length)] = Session::getCookie($key);
            }
        }
    }


    /**
     * Creates a new ContextualCookies
     *
     * @param Addon  $context  Addon context for this object
     * @return ContextualCookies
     */
    public static function createObject(Addon $context)
    {
        return new ContextualCookies($context);
    }
}


/**
 * ContextualCache
 * Supports cache file manipulation and maintenance via an Addon context
 */
class ContextualCache extends ContextualObject
{
    /**
     * Contextual path to cache folder
     * @private string
     */
    private $path;


    /**
     * Initializes object
     *
     * @param Addon  $context  Context object
     * @return ContextualCache
     */
    public function __construct(Addon $context)
    {
        $this->path = BASE_PATH . '/_cache/_add-ons/' . $context->getAddonName() . '/';
        parent::__construct($context);
    }


    /**
     * Checks to see if a given $filename exists within this plugin's namespaced cache
     *
     * @param string  $filename  Name of file to check for
     * @return boolean
     */
    public function exists($filename)
    {
        $this->isValidFilename($filename);
        return File::exists($this->contextualize($filename));
    }


    /**
     * Gets a file from this plugin's namespaced cache
     *
     * @param string  $filename  Name of file to get
     * @param mixed  $default  Default value to return if no file is found
     * @return mixed
     */
    public function get($filename, $default=null)
    {
        $this->isValidFilename($filename);
        return File::get($this->contextualize($filename), $default);
    }


    /**
     * Gets a file from this plugin's namespaced cache and parses it as YAML
     *
     * @param string  $filename  Name of file to get
     * @param mixed  $default  Default value to return if no file is found, or file is not YAML-parsable
     * @return mixed
     */
    public function getYAML($filename, $default=null)
    {
        $data = $this->get($filename);

        if (!is_null($data)) {
            return YAML::parse($data);
        }

        return $default;
    }


    /**
     * Puts a file to this plugin's namespaced cache
     *
     * @param string  $filename  Name of file to put
     * @param mixed  $content  Content to write to file
     * @return int
     */
    public function put($filename, $content)
    {
        $this->isValidFilename($filename);
        return File::put($this->contextualize($filename), $content);
    }


    /**
     * Parses the given $content array and puts a file to this plugin's namespaced cache
     *
     * @param string  $filename  Name of file to put
     * @param array  $content  Array to parse and write to file
     * @return int
     */
    public function putYAML($filename, Array $content)
    {
        return $this->put($filename, YAML::dump($content));
    }


    /**
     * Appends content to the bottom of a file in this plugin's namespaced cache
     *
     * @param string  $filename  Name of file to use
     * @param mixed  $content  Content to append to file
     * @return boolean
     */
    public function append($filename, $content)
    {
        $this->isValidFilename($filename);
        return File::append($this->contextualize($filename), $content);
    }


    /**
     * Prepends content to the start of a file in this plugin's namespaced cache
     *
     * @param string  $filename  Name of file to use
     * @param mixed  $content  Content to prepend to file
     * @return boolean
     */
    public function prepend($filename, $content)
    {
        $this->isValidFilename($filename);
        return File::prepend($this->contextualize($filename), $content);
    }


    /**
     * Moves a file from one location to another within this plugin's namespaced cache
     *
     * @param string  $filename  Name of file to move
     * @param string  $new_filename  New file name to move it to
     * @return boolean
     */
    public function move($filename, $new_filename)
    {
        $this->isValidFilename($filename);
        $this->isValidFilename($new_filename);
        return File::move($this->contextualize($filename), $this->contextualize($new_filename));
    }


    /**
     * Copies a file from one location to another within this plugin's namespaced cache
     *
     * @param string  $filename  Name of file to copy
     * @param string  $new_filename  New file name to copy it to
     * @return boolean
     */
    public function copy($filename, $new_filename)
    {
        $this->isValidFilename($filename);
        $this->isValidFilename($new_filename);
        return File::copy($this->contextualize($filename), $this->contextualize($new_filename));
    }


    /**
     * Deletes a file from this plugin's namespaced cache
     *
     * @param string  $filename  Name of file to delete
     * @return boolean
     */
    public function delete($filename)
    {
        $this->isValidFilename($filename);
        return File::delete($this->contextualize($filename));
    }


    /**
     * Destroys all content within this plugin's namespaced cache
     *
     * @param string  $folder  Folder within cache to destroy
     * @return void
     */
    public function destroy($folder="")
    {
        $this->isValidFilename($folder);
        Folder::wipe($this->contextualize($folder . "/"));
    }


    /**
     * Retrieves and array of all files within this plugin's namespaced cache
     *
     * @param string  $folder  Folder within cache to limit to
     * @return array
     */
    public function listAll($folder="")
    {
        $path = $this->contextualize($folder . "/");

        $finder = new Finder();
        $files  = $finder->files()
            ->in($path)
            ->followLinks();

        $output = array();

        foreach ($files as $file) {
            array_push($output, str_replace($path, "", $file));
        }

        return $output;
    }


    /**
     * Gets the age of a given file within this plugin's namespaced cache
     *
     * @param string  $filename  Name of file to check
     * @return mixed
     */
    public function getAge($filename)
    {
        $this->isValidFilename($filename);
        $file = $this->contextualize($filename);
        return ($this->exists($filename)) ? time() - File::getLastModified($file) : false;
    }


    /**
     * Removes all cache files older than a given age in seconds
     *
     * @param int  $seconds  Threshold of seconds for wiping
     * @param string  $folder  Folder to apply wipe to with namespaced cache
     * @return void
     */
    public function purgeOlderThan($seconds, $folder="")
    {
        $this->isValidFilename($folder);

        $path  = $this->contextualize($folder . "/");

	  if ($folder && !Folder::exists($path)) {
		  return;
	  }

        $finder = new Finder();
        $files  = $finder->files()
            ->in($path)
            ->date("<= " . Date::format("F j, Y H:i:s", time() - $seconds))
            ->followLinks();

        foreach ($files as $file) {
            File::delete($file);
        }
    }


    /**
     * Removes all cache files last modified before a given $date
     *
     * @param mixed  $date  Date to use as threshold for deletion
     * @param string  $folder  Folder to apply wipe to with namespaced cache
     * @return void
     */
    public function purgeFromBefore($date, $folder="")
    {
        $this->isValidFilename($folder);
        $path = $this->contextualize($folder . "/");

        if ($folder && !Folder::exists($path)) {
            return;
        }

        $finder = new Finder();
        $files  = $finder->files()
            ->in($path)
            ->date("< " . Date::format("F j, Y H:i:s", $date))
            ->followLinks();

        foreach ($files as $file) {
            File::delete($file);
        }
    }


    /**
     * Returns the filepath for a given $filename for this plugin's namespaced cache
     *
     * @param string  $filename  File name to use
     * @return string
     */
    private function contextualize($filename)
    {
        return Path::tidy($this->path . $filename);
    }


    /**
     * Checks for a valid filename string
     *
     * @throws Exception
     *
     * @param string  $filename  File name to check
     * @return boolean
     */
    private function isValidFilename($filename)
    {
        if (strpos($filename, "..") !== false) {
            Log::error("Cannot use cache with path containing two consecutive dots (..).", $this->context->getAddonName(), $this->context->getAddonType());

            // throw an exception to prevent whatever is happening from happening
            throw new Exception("Cannot use cache with path containing two consecutive dots (..).");
        }

        return true;
    }


    /**
     * Creates a new ContextualCache object
     *
     * @param Addon  $context  Context object
     * @return ContextualCache
     */
    public static function createObject(Addon $context)
    {
        return new ContextualCache($context);
    }
}



/**
 * ContextualCSS
 * Access CSS via an Addon context
 */
class ContextualCSS extends ContextualObject
{
    /**
     * Returns HTML to include one or more given $stylesheets
     *
     * @param mixed  $stylesheets  Stylesheet(s) to create HTML for
     * @return string
     */
    public function link($stylesheets)
    {
        $files = Helper::ensureArray($stylesheets);
        $html  = '';

        foreach ($files as $file) {
            $html .= HTML::includeStylesheet($this->get($file));
        }

        return $html;
    }


    /**
     * Returns HTML for inline CSS
     *
     * @param string  $css  CSS to return
     * @return string
     */
    public function inline($css)
    {
        return '<style>' . $css . '</style>';
    }


    /**
     * Returns the full path of a given stylesheet
     *
     * @param string  $file  Stylesheet file to find
     * @return string
     */
    public function get($file)
    {
        $bundle_location = "/core/bundles/" . $this->context->getAddonName() . "/";
        $file_location = Config::getAddOnPath($this->context->getAddonName()) . '/';

        if (File::exists(APP_PATH . $bundle_location . $file)) {
            return URL::assemble(Config::getSiteRoot(), $file_location . $file);
        } elseif (File::exists(APP_PATH . $bundle_location . 'css/' . $file)) {
            return URL::assemble(Config::getSiteRoot(), $file_location, 'css', $file);
        } elseif (File::exists(BASE_PATH . $file_location . $file)) {
            return URL::assemble(Config::getSiteRoot(), $file_location . $file);
        } elseif (File::exists(BASE_PATH . $file_location . 'css/' . $file)) {
            return URL::assemble(Config::getSiteRoot(), $file_location, 'css', $file);
        } elseif ( ! Pattern::endsWith($file, ".css", false)) {
            return $this->get($file . ".css");
        }

        Log::error("CSS file `" . $file . "` doesn't exist.", $this->context->getAddonName(), $this->context->getAddonType());
        return "";
    }


    /**
     * Creates a new ContextualCSS
     *
     * @param Addon  $context  Addon context for this object
     * @return ContextualCSS
     */
    public static function createObject(Addon $context)
    {
        return new ContextualCSS($context);
    }
}



/**
 * ContextualJS
 * Access JavaScript via an Addon context
 */
class ContextualJS extends ContextualObject
{
    /**
     * Returns HTML to include one or more given $scripts
     *
     * @param mixed  $scripts  Script(s) to create HTML for
     * @return string
     */
    public function link($scripts)
    {
        $files = Helper::ensureArray($scripts);
        $html  = '';

        foreach ($files as $file) {
            $html .= HTML::includeScript($this->get($file));
        }

        return $html;
    }


    /**
     * Returns HTML for inline JavaScript
     *
     * @param string  $js  JavaScript to return
     * @return string
     */
    public function inline($js)
    {
        return '<script type="text/javascript">' . $js . '</script>';
    }


    /**
     * Returns the full path of a given script
     *
     * @param string  $file  Script file to find
     * @return string
     */
    public function get($file)
    {
        $bundle_location = "/core/bundles/" . $this->context->getAddonName() . "/";
        $file_location = Config::getAddOnPath($this->context->getAddonName()) . '/';

        if (File::exists(APP_PATH . $bundle_location . $file)) {
            return URL::assemble(Config::getSiteRoot(), $file_location . $file);
        } elseif (File::exists(APP_PATH . $bundle_location . 'js/' . $file)) {
            return URL::assemble(Config::getSiteRoot(), $file_location, 'js', $file);
        } elseif (File::exists(BASE_PATH . $file_location . $file)) {
            return URL::assemble(Config::getSiteRoot(), $file_location . $file);
        } elseif (File::exists(BASE_PATH . $file_location . 'js/' . $file)) {
            return URL::assemble(Config::getSiteRoot(), $file_location, 'js', $file);
        } elseif ( ! Pattern::endsWith($file, ".js", false)) {
            return $this->get($file . ".js");
        }

        Log::error("JavaScript file `" . $file . "` doesn't exist.", $this->context->getAddonName(), $this->context->getAddonType());
        return "";
    }


    /**
     * Creates a new ContextualJS
     *
     * @param Addon  $context  Addon context for this object
     * @return ContextualJS
     */
    public static function createObject(Addon $context)
    {
        return new ContextualJS($context);
    }
}



/**
 * ContextualAssets
 * Access assets via an Addon context
 */
class ContextualAssets extends ContextualObject
{
    /**
     * Returns the full path of a given script
     *
     * @param string  $file  Script file to find
     * @return string
     */
    public function get($file)
    {
        $bundle_location = "/core/bundles/" . $this->context->getAddonName() . "/";
        $file_location = Config::getAddOnPath($this->context->getAddonName()) . '/';

        if (File::exists(APP_PATH . $bundle_location . $file)) {
            return URL::assemble(Config::getSiteRoot(), $file_location, $file);
        } elseif (File::exists(APP_PATH . $bundle_location . 'assets/' . $file)) {
            return URL::assemble(Config::getSiteRoot(), $file_location, 'assets', $file);
        } elseif (File::exists(BASE_PATH . $file_location . $file)) {
            return URL::assemble(Config::getSiteRoot(), $file_location, $file);
        } elseif (File::exists(BASE_PATH . $file_location . 'assets/' . $file)) {
            return URL::assemble(Config::getSiteRoot(), $file_location, 'assets', $file);
        }

        Log::error("Asset file `" . $file . "` doesn't exist.", $this->context->getAddonName(), $this->context->getAddonType());
        return "";
    }


    /**
     * Creates a new ContextualAssets
     *
     * @param Addon  $context  Addon context for this object
     * @return ContextualAssets
     */
    public static function createObject(Addon $context)
    {
        return new ContextualAssets($context);
    }
}


/**
 * ContextualInteroperability
 * Allows add-ons to talk to one another
 */
class ContextualInteroperability extends ContextualObject
{
    /**
     * Is a given $addon installed?
     *
     * @param string  $addon  Name of the addon to look up
     * @return boolean
     */
    public function isInstalled($addon)
    {
        return Addon::isInstalled($addon);
    }


    /**
     * Is a given $addon have an api available?
     *
     * @param string  $addon  Name of the addon to view
     * @return boolean
     */
    public function hasAPI($addon)
    {
        return Addon::hasAPI($addon);
    }


    /**
     * Use a given $addon's API
     *
     * @param string  $addon  Name of the addon to use
     * @return boolean
     * @throws Exception
     */
    public function api($addon)
    {
        return Addon::getAPI($addon);
    }


    /**
     * Creates a new ContextualInteroperability
     *
     * @param Addon  $context  Addon context for this object
     * @return ContextualInteroperability
     */
    public static function createObject(Addon $context)
    {
        return new ContextualInteroperability($context);
    }
}


/**
 * ContextualBlink
 * Store data only until the current page is done rendering
 */
class ContextualBlink extends ContextualObject
{
    /**
     * Where pocket blink gets stored
     */
    public static $data = array();


    /**
     * Gets blink data for a variable, or the $default if variable isn't set
     *
     * @param string  $key  Key to retrieve
     * @param mixed  $default  Default value to return
     * @return mixed
     */
    public function get($key, $default=null)
    {
        if ($this->exists($key)) {
            return self::$data[$key];
        }

        return $default;
    }


    /**
     * Sets blink data for a variable
     *
     * @param string  $key  Key to set
     * @param mixed  $value  Value to set
     * @return void
     */
    public function set($key, $value)
    {
        self::$data[$key] = $value;
    }


    /**
     * Checks if a $key exists in the blink data
     *
     * @param string  $key  Key to set
     * @return boolean
     */
    public function exists($key)
    {
        return isset(self::$data[$key]);
    }


    /**
     * Destroys all blink data
     *
     * @return void
     */
    public function destroy()
    {
        self::$data = array();
    }


    /**
     * Creates a new ContextualBlink
     *
     * @param Addon  $context  Addon context for this object
     * @return ContextualBlink
     */
    public static function createObject(Addon $context)
    {
        return new ContextualBlink($context);
    }
}



/**
 * ContextualTokens
 * Create and validate tokens
 */
class ContextualTokens extends ContextualObject
{
    /**
     * The type of session data being stored
     */
    protected $type = '_plugin_tokens';


    /**
     * Creates a new token, stores it in the session cache and returns it for use
     *
     * @return string
     */
    public function create()
    {
        // grab list of known tokens
        $tokens = $this->getTokens();

        // create a new token
        do {
            $new_token = Helper::getRandomString(64);
        } while (in_array($new_token, $tokens));

        // we have a new token, add it to the list
        $tokens[] = $new_token;

        // save that list to the session
        $this->setTokens($tokens);

        // return the token
        return $new_token;
    }


    /**
     * Validates a given $token, if found, removes it from internal list
     *
     * @param string  $token  Token to validate
     * @return boolean
     */
    public function validate($token)
    {
        // grab list of known tokens
        $tokens = $this->getTokens();

        // check the result
        $result = in_array($token, $tokens);

        // found it
        if ($result) {
            // remove it from the list of valid tokens
            $tokens = array_diff($tokens, array($token));

            // set the list back to the session
            $this->setTokens($tokens);

            // return that we found it
            return true;
        }

        // didn't find it
        return false;
    }


    /**
     * Retrieves a list of tokens
     *
     * @return array
     */
    private function getTokens()
    {
        return Session::get($this->type, $this->context->getAddonName(), 'tokens', array());
    }


    /**
     * Saves a list of tokens
     *
     * @param array  $tokens  Tokens to set
     * @return void
     */
    private function setTokens($tokens)
    {
        Session::set($this->type, $this->context->getAddonName(), 'tokens', $tokens);
    }


    /**
     * Creates a new ContextualTokens
     *
     * @param Addon  $context  Addon context for this object
     * @return ContextualTokens
     */
    public static function createObject(Addon $context)
    {
        return new ContextualTokens($context);
    }
}