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
    if (class_exists('\\CS_REST_Clients') === false) {
        throw new \Exception(
            '*CS_REST_Clients* class required. Please see ' .
            'https://github.com/campaignmonitor/createsend-php'
        );
    }

    // dependency check
    if (class_exists('\\CS_REST_Subscribers') === false) {
        throw new \Exception(
            '*CS_REST_Subscribers* class required. Please see ' .
            'https://github.com/campaignmonitor/createsend-php'
        );
    }

    // dependency check
    if (class_exists('\\CS_REST_Lists') === false) {
        throw new \Exception(
            '*CS_REST_Lists* class required. Please see ' .
            'https://github.com/campaignmonitor/createsend-php'
        );
    }

    // dependency check
    if (class_exists('\\CS_REST_Transactional_SmartEmail') === false) {
        throw new \Exception(
            '*CS_REST_Transactional_SmartEmail* class required. Please see ' .
            'https://github.com/campaignmonitor/createsend-php'
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
         * _getResource
         *
         * @static
         * @access protected
         * @param  string $type
         * @param  false|string $id (default: false)
         * @return CS_REST_Subscribers|
         */
        protected static function _getResource($type, $id = false)
        {
            $config = getConfig('TurtlePHP-CampaignMonitorPlugin');
            $apiKey = $config['credentials']['apiKey'];
            $clientId = $config['credentials']['clientId'];
            $auth = array('api_key' => $apiKey);
            if ($type === 'email') {
                return new \CS_REST_Transactional_SmartEmail($id, $auth);
            } elseif ($type === 'client') {
                return new \CS_REST_Clients($clientId, $auth);
            }
            return new \CS_REST_Subscribers($id, $auth);
        }

        /**
         * _getList
         *
         * @static
         * @access protected
         * @param  string|array $key
         * @param  string $type (default: 'lists')
         * @return string
         */
        protected static function _getList($key, $type = 'lists')
        {
            $config = getConfig('TurtlePHP-CampaignMonitorPlugin');
            $lists = $config[$type];
            foreach ((array) $key as $sub) {
                $id = $lists[$sub];
                $lists = $lists[$sub];
            }
            return $id;
        }

        /**
         * _format
         *
         * @static
         * @access protected
         * @param  array $subscriber
         * @param  boolean $resubscribe (default: true)
         * @return array
         */
        protected static function _format(
            array $subscriber,
            $resubscribe = true
        ) {
            $formatted = array(
                'EmailAddress' => $subscriber['email'],
                'Resubscribe' => $resubscribe
            );
            if (isset($subscriber['custom'])) {
                $formatted['CustomFields'] = array();
                foreach ($subscriber['custom'] as $key => $value) {
                    if (is_array($value)) {
                        foreach ($value as $subvalue) {
                            $formatted['CustomFields'][] = array(
                                'Key' => $key,
                                'Value' => $subvalue
                            );
                        }
                    } else {
                        $formatted['CustomFields'][] = array(
                            'Key' => $key,
                            'Value' => $value
                        );
                    }
                }
            }
            if (isset($subscriber['name'])) {
                $formatted['Name'] = $subscriber['name'];
            } else {
                if (isset($subscriber['firstName'])) {
                    $formatted['Name'] = $subscriber['firstName'];
                }
                if (isset($subscriber['lastName'])) {
                    $formatted['Name'] .= ' ' . ($subscriber['lastName']);
                }
            }
            return $formatted;
        }

        /**
         * _add
         *
         * @note   The set_error_handler and retore_error_handler calls below
         *         should allow the application logic to flow uninterrupted
         * @note   201 status check is because CM sends a 201 upon successful
         *         addition of an email address
         * @static
         * @access protected
         * @param  string $id
         * @param  array $subscriber
         * @return false|CS_REST_Wrapper_Result
         */
        protected static function _add($id, array $subscriber)
        {
            $resource = self::_getResource('subscriber', $id);
            set_error_handler(function() {});
            $response = $resource->add($subscriber);
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
         * _details
         *
         * @static
         * @access protected
         * @param  string $id
         * @param  string $email
         * @param  boolean $verbose (default: true)
         * @return false|CS_REST_Wrapper_Result
         */
        protected static function _details($id, $email, $verbose = true)
        {
            $resource = self::_getResource('subscriber', $id);
            set_error_handler(function() {});
            $response = $resource->get($email);
            restore_error_handler();
            if (
                is_object($response)
                && (int) $response->http_status_code !== 200
            ) {
                if ($verbose === true) {
                    error_log(print_r($response, true));
                }
                return false;
            }
            return $response;
        }

        /**
         * _import
         *
         * @note   The set_error_handler and retore_error_handler calls below
         *         should allow the application logic to flow uninterrupted
         * @note   201 status check is because CM sends a 201 upon successful
         *         addition of an email address
         * @static
         * @access protected
         * @param  string $id
         * @param  array $subscribers
         * @param  boolean $resubscribe
         * @return false|CS_REST_Wrapper_Result
         */
        protected static function _import($id, array $subscribers, $resubscribe)
        {
            $resource = self::_getResource('subscriber', $id);
            set_error_handler(function() {});
            $response = $resource->import($subscribers, $resubscribe);
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
         * @param  string $id
         * @param  string $email
         * @param  boolean $verbose (default: true)
         * @return false|CS_REST_Wrapper_Result
         */
        protected static function _remove($id, $email, $verbose = true)
        {
            $resource = self::_getResource('subscriber', $id);
            set_error_handler(function() {});
            $response = $resource->delete($email);
            restore_error_handler();
            if (
                is_object($response)
                && (int) $response->http_status_code !== 200
            ) {
                if ($verbose === true) {
                    error_log(print_r($response, true));
                }
                return false;
            }
            return $response;
        }

        /**
         * _send
         *
         * @note   The set_error_handler and retore_error_handler calls below
         *         should allow the application logic to flow uninterrupted
         * @note   200 status check is because CM sends a 201 upon successful
         *         addition of an email address
         * @static
         * @access protected
         * @param  string $id
         * @param  string $email
         * @param  array $data
         * @return false|CS_REST_Wrapper_Result
         */
        protected static function _send($id, $email, array $data)
        {
            $resource = self::_getResource('email', $id);
            $message = array(
                'To' => $email,
                'Data' => $data
            );
            set_error_handler(function() {});
            $response = $resource->send($message);
            restore_error_handler();
            if (
                is_object($response)
                && (int) $response->http_status_code !== 202
            ) {
                error_log(print_r($response, true));
                return false;
            }
            return $response;
        }

        /**
         * _suppress
         *
         * @static
         * @access protected
         * @param  string|array $emails
         * @param  boolean $verbose (default: true)
         * @return false|CS_REST_Wrapper_Result
         */
        protected static function _suppress($emails, $verbose = true)
        {
            $resource = self::_getResource('client');
            set_error_handler(function() {});
            $response = $resource->suppress((array) $emails);
            restore_error_handler();
            if (
                is_object($response)
                && (int) $response->http_status_code !== 200
            ) {
                if ($verbose === true) {
                    error_log(print_r($response, true));
                }
                return false;
            }
            return $response;
        }

        /**
         * _unsubscribe
         *
         * @note   The set_error_handler and retore_error_handler calls below
         *         should allow the application logic to flow uninterrupted
         * @note   200 status check is because CM sends a 201 upon successful
         *         addition of an email address
         * @static
         * @access protected
         * @param  string $id
         * @param  string $email
         * @param  boolean $verbose (default: true)
         * @return false|CS_REST_Wrapper_Result
         */
        protected static function _unsubscribe($id, $email, $verbose = true)
        {
            $resource = self::_getResource('subscriber', $id);
            set_error_handler(function() {});
            $response = $resource->unsubscribe($email);
            restore_error_handler();
            if (
                is_object($response)
                && (int) $response->http_status_code !== 200
            ) {
                if ($verbose === true) {
                    error_log(print_r($response, true));
                }
                return false;
            }
            return $response;
        }

        /**
         * _unsuppress
         *
         * @static
         * @access protected
         * @param  string $email
         * @param  boolean $verbose (default: true)
         * @return false|CS_REST_Wrapper_Result
         */
        protected static function _unsuppress($email, $verbose = true)
        {
            $resource = self::_getResource('client');
            set_error_handler(function() {});
            $response = $resource->unsuppress($email);
            restore_error_handler();
            if (
                is_object($response)
                && (int) $response->http_status_code !== 200
            ) {
                if ($verbose === true) {
                    error_log(print_r($response, true));
                }
                return false;
            }
            return $response;
        }

        /**
         * _update
         *
         * @note   The set_error_handler and retore_error_handler calls below
         *         should allow the application logic to flow uninterrupted
         * @note   200 status check is because CM sends a 200 upon successful
         *         update of an email address
         * @static
         * @access protected
         * @param  string $id
         * @param  string $email
         * @param  array $subscriber
         * @return false|CS_REST_Wrapper_Result
         */
        protected static function _update($id, $email, array $subscriber)
        {
            $resource = self::_getResource('subscriber', $id);
            set_error_handler(function() {});
            $response = $resource->update($email, $subscriber);
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
         * @param  string|array $key
         * @param  array $subscriber
         * @return false|CS_REST_Wrapper_Result
         */
        public static function add($key, array $subscriber)
        {
            $id = self::_getList($key);
            $subscriber = self::_format($subscriber);
            $response = self::_add($id, $subscriber);
            if ($response === false) {
                $email = $subscriber['EmailAddress'];
                error_log(
                    'Error when attempting to add *' . ($email) . '* to ' .
                    'Campaign Monitor (list: ' . ($id) . ')'
                );
            }
            return $response;
        }

        /**
         * details
         *
         * @static
         * @access public
         * @param  string|array $key
         * @param  string $email
         * @param  boolean $verbose (default: true)
         * @return false|CS_REST_Wrapper_Result
         */
        public static function details($key, $email, $verbose = true)
        {
            $id = self::_getList($key);
            $response = self::_details($id, $email, $verbose);
            if ($response === false && $verbose === true) {
                error_log(
                    'Error when attempting to get details for *' . ($email) .
                    '* from Campaign Monitor (list: ' . ($id) . ')'
                );
            }
            return $response;
        }

        /**
         * import
         *
         * @static
         * @access public
         * @param  string|array $key
         * @param  array $subscribers
         * @param  boolean $resubscribe (default: true)
         * @return false|CS_REST_Wrapper_Result
         */
        public static function import($key, $subscribers, $resubscribe = true)
        {
            $id = self::_getList($key);
            foreach ($subscribers as &$subscriber) {
                $subscriber = self::_format($subscriber);
            }
            $response = self::_import($id, $subscribers, $resubscribe);
            if ($response === false) {
                error_log(
                    'Error when attempting to import to Campaign Monitor ' .
                    '(list: ' . ($id) . ')'
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
            if (self::$_initiated === false) {
                self::$_initiated = true;
                require_once self::$_configPath;
            }
        }

        /**
         * remove
         *
         * @static
         * @access public
         * @param  string|array $key
         * @param  string $email
         * @param  boolean $verbose (default: true)
         * @return false|CS_REST_Wrapper_Result
         */
        public static function remove($key, $email, $verbose = true)
        {
            $id = self::_getList($key);
            $response = self::_remove($id, $email, $verbose);
            if ($response === false && $verbose === true) {
                error_log(
                    'Error when attempting to remove *' . ($email) .
                    '* from Campaign Monitor (list: ' . ($id) . ')'
                );
            }
            return $response;
        }

        /**
         * send
         * 
         * @access public
         * @param  string|array $key
         * @param  string $email
         * @param  array $data (default: array())
         * @return false|CS_REST_Wrapper_Result
         */
        public static function send($key, $email, array $data = array())
        {
            $id = self::_getList($key, 'emails');
            $response = self::_send($id, $email, $data);
            if ($response === false) {
                error_log(
                    'Error when attempting to email *' . ($email) . '* from ' .
                    'Campaign Monitor (id: ' . ($id) . ')'
                );
            }
            return $response;
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

        /**
         * subscribe
         *
         * @static
         * @access public
         * @param  string $email
         * @param  boolean $verbose (default: true)
         * @return false|CS_REST_Wrapper_Result
         */
        public static function subscribe($email, $verbose = true)
        {
            $response = self::_suppress($email, $verbose);
            if ($response === false && $verbose === true) {
                error_log(
                    'Error when attempting to suppress *' . ($email) .
                    '* from Campaign Monitor'
                );
            }
            return $response;
        }

        /**
         * suppress
         *
         * @static
         * @access public
         * @param  string $email
         * @param  boolean $verbose (default: true)
         * @return false|CS_REST_Wrapper_Result
         */
        public static function suppress($email, $verbose = true)
        {
            $response = self::_suppress($email, $verbose);
            if ($response === false && $verbose === true) {
                error_log(
                    'Error when attempting to suppress *' . ($email) .
                    '* from Campaign Monitor'
                );
            }
            return $response;
        }

        /**
         * unsubscribe
         *
         * @static
         * @access public
         * @param  string|array $key
         * @param  string $email
         * @param  boolean $verbose (default: true)
         * @return false|CS_REST_Wrapper_Result
         */
        public static function unsubscribe($key, $email, $verbose = true)
        {
            $id = self::_getList($key);
            $response = self::_unsubscribe($id, $email, $verbose);
            if ($response === false && $verbose === true) {
                error_log(
                    'Error when attempting to unsubscribe *' . ($email) .
                    '* from Campaign Monitor (list: ' . ($id) . ')'
                );
            }
            return $response;
        }

        /**
         * unsuppress
         *
         * @static
         * @access public
         * @param  string $email
         * @param  boolean $verbose (default: true)
         * @return false|CS_REST_Wrapper_Result
         */
        public static function unsuppress($email, $verbose = true)
        {
            $response = self::_unsuppress($email, $verbose);
            if ($response === false && $verbose === true) {
                error_log(
                    'Error when attempting to unsuppress *' . ($email) .
                    '* from Campaign Monitor'
                );
            }
            return $response;
        }

        /**
         * update
         *
         * @static
         * @access public
         * @param  string|array $key
         * @param  string $email
         * @param  array $subscriber
         * @param  boolean $resubscribe (default: true)
         * @return false|CS_REST_Wrapper_Result
         */
        public static function update(
            $key,
            $email,
            array $subscriber,
            $resubscribe = true
        ) {
            $id = self::_getList($key);
            $subscriber = self::_format($subscriber, $resubscribe);
            $response = self::_update($id, $email, $subscriber);
            if ($response === false) {
                error_log(
                    'Error when attempting to update *' . ($email) . '* to ' .
                    'Campaign Monitor (list: ' . ($id) . ')'
                );
            }
            return $response;
        }

        /**
         * webhooks
         *
         * Loops over webhooks and removes any previously added. Then adds those
         * stored in config. I check for an empty response because when CM is
         * down, response is empty. So don't do anything drastic when that is
         * the case.
         *
         * @static
         * @access public
         * @return void
         */
        public static function webhooks()
        {
            $config = getConfig('TurtlePHP-CampaignMonitorPlugin');
            $collection = $config['webhooks'];
            $key = $config['credentials']['apiKey'];
            $auth = array('api_key' => $key);
            foreach ($collection as $list => $webhooks) {
                $list = self::_getList($list);
                $list = new \CS_REST_Lists($list, $auth);
                $already = $list->get_webhooks();
                if ($already->response !== '') {
                    foreach ($already->response as $webhook) {
                        $id = $webhook->WebhookID;
                        $list->delete_webhook($id);
                    }
                    foreach ($webhooks as $webhook) {
                        $list->create_webhook($webhook);
                    }
                }
            }
        }
    }

    // Config
    $info = pathinfo(__DIR__);
    $parent = ($info['dirname']) . '/' . ($info['basename']);
    $configPath = ($parent) . '/config.inc.php';
    if (is_file($configPath)) {
        CampaignMonitor::setConfigPath($configPath);
    }
