<?php

    // namespace
    namespace Plugin;

    // dependency check
    if (class_exists('\\Plugin\\Config') === false) {
        throw new \Exception(
            '*Config* class required. Please see ' .
            'https://github.com/onassar/TurtlePHP-ConfigPlugin'
        );
    }

    // dependency check
    if (class_exists('\\CS_REST_Subscribers') === false) {
        throw new \Exception(
            '*CS_REST_Subscribers* class required. Please see ' .
            'https://github.com/Znarkus/postmark-php'
        );
    }

    /**
     * CampaignMonitor
     * 
     * Campaign Monitor plugin for TurtlePHP
     * 
     * @author   Oliver Nassar <onassar@gmail.com>
     * @abstract
     */
    abstract class CampaignMonitor
    {
        /**
         * _configPath
         *
         * @var    string
         * @access protected
         * @static
         */
        protected static $_configPath = 'config.default.inc.php';

        /**
         * _initiated
         *
         * @var    boolean
         * @access protected
         * @static
         */
        protected static $_initiated = false;

        /**
         * _add
         *
         * @note   The set_error_handler and retore_error_handler calls below
         *         should allow the application logic to flow uninterrupted
         * @note   201 status check is because CM sends a 201 upon successful
         *         addition of an email address
         * @static
         * @access protected
         * @param  string $listId
         * @param  array $details
         * @return CS_REST_Wrapper_Result|false
         */
        public static function _add($listId, array $details)
        {
            // Config
            $config = getConfig('TurtlePHP-CampaignMonitorPlugin');
            $apiKey = $config['credentials']['apiKey'];
            $auth = array('api_key' => $apiKey);
            $wrapper = (new \CS_REST_Subscribers($listId, $auth));

            // Handle case where something (eg. connection) fails
            set_error_handler(function() {});
            $response = $wrapper->add($details);
            restore_error_handler();
            if (
                is_object($response)
                && (int) $response->http_status_code !== 201
            ) {
                error_log(print_r($response, true));
                return false;
            }
            return $response;
        }

        /**
         * _remove
         *
         * @note   The set_error_handler and retore_error_handler calls below
         *         should allow the application logic to flow uninterrupted
         * @note   200 status check is because CM sends a 201 upon successful
         *         addition of an email address
         * @static
         * @access protected
         * @param  string $listId
         * @param  string $email
         * @return CS_REST_Wrapper_Result|false
         */
        public static function _remove($listId, $email)
        {
            // Config
            $config = getConfig('TurtlePHP-CampaignMonitorPlugin');
            $apiKey = $config['credentials']['apiKey'];
            $auth = array('api_key' => $apiKey);
            $wrapper = (new \CS_REST_Subscribers($listId, $auth));

            // Handle case where something (eg. connection) fails
            set_error_handler(function() {});
            $response = $wrapper->delete($email);
            restore_error_handler();
            if (
                is_object($response)
                && (int) $response->http_status_code !== 200
            ) {
                error_log(print_r($response, true));
                return false;
            }
            return $response;
        }

        /**
         * add
         *
         * @static
         * @access public
         * @param  string $listKey
         * @param  array $details
         * @return CS_REST_Wrapper_Result|false
         */
        public static function add($listKey, array $details)
        {
            $config = getConfig('TurtlePHP-CampaignMonitorPlugin');
            $listId = $config['lists'][$listKey];
            $data = array(
                'EmailAddress' => $details['email'],
                'Resubscribe' => true
            );
            if (isset($details['name'])) {
                $data['Name'] = $details['name'];
            } else {
                if (isset($details['firstName'])) {
                    $data['Name'] = $details['firstName'];
                }
                if (isset($details['lastName'])) {
                    $data['Name'] .= ' ' . ($details['lastName']);
                }
            }
            $response = self::_add($listId, $data);
            if ($response === false) {
                error_log(
                    'Error when attempting to add *' . ($details['email']) .
                    '* to Campaign Monitor (list: ' . ($listId) . ')'
                );
            }
            return $response;
        }

        /**
         * remove
         *
         * @static
         * @access public
         * @param  string $listKey
         * @param  string $email
         * @return CS_REST_Wrapper_Result|false
         */
        public static function remove($listKey, $email)
        {
            $config = getConfig('TurtlePHP-CampaignMonitorPlugin');
            $listId = $config['lists'][$listKey];
            $response = self::_remove($listId, $email);
            if ($response === false) {
                error_log(
                    'Error when attempting to remove *' . ($email) .
                    '* from Campaign Monitor (list: ' . ($listId) . ')'
                );
            }
            return $response;
        }

        /**
         * init
         * 
         * @access public
         * @static
         * @return void
         */
        public static function init()
        {
            if (is_null(self::$_initiated) === false) {
                self::$_initiated = true;
                require_once self::$_configPath;
            }
        }

        /**
         * setConfigPath
         * 
         * @access public
         * @param  string $path
         * @return void
         */
        public static function setConfigPath($path)
        {
            self::$_configPath = $path;
        }
    }

    // Config
    $info = pathinfo(__DIR__);
    $parent = ($info['dirname']) . '/' . ($info['basename']);
    $configPath = ($parent) . '/config.inc.php';
    if (is_file($configPath)) {
        CampaignMonitor::setConfigPath($configPath);
    }
