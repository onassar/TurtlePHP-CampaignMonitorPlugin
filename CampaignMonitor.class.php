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
         * @param  string $id
         * @param  string $type
         * @return CS_REST_Subscribers|
         */
        protected static function _getResource($id, $type)
        {
            $config = getConfig('TurtlePHP-CampaignMonitorPlugin');
            $apiKey = $config['credentials']['apiKey'];
            $auth = array('api_key' => $apiKey);
            if ($type === 'email') {
                return new \CS_REST_Transactional_SmartEmail($id, $auth);
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
         * @return array
         */
        protected static function _format(array $subscriber)
        {
            $formatted = array(
                'EmailAddress' => $subscriber['email'],
                'Resubscribe' => true
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
         * @return CS_REST_Wrapper_Result|false
         */
        protected static function _add($id, array $subscriber)
        {
            $resource = self::_getResource($id, 'subscriber');
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
         * @return CS_REST_Wrapper_Result|false
         */
        protected static function _import($id, array $subscribers)
        {
            $resource = self::_getResource($id, 'subscriber');
            set_error_handler(function() {});
            $response = $resource->import($subscribers, true);
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
         * @return CS_REST_Wrapper_Result|false
         */
        protected static function _remove($id, $email)
        {
            $resource = self::_getResource($id, 'subscriber');
            set_error_handler(function() {});
            $response = $resource->delete($email);
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
         * @return CS_REST_Wrapper_Result|false
         */
        protected static function _send($id, $email, array $data)
        {
            $resource = self::_getResource($id, 'email');
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
         * @return CS_REST_Wrapper_Result|false
         */
        protected static function _update($id, $email, array $subscriber)
        {
            $resource = self::_getResource($id, 'subscriber');
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
         * @return CS_REST_Wrapper_Result|false
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
         * import
         *
         * @static
         * @access public
         * @param  string|array $key
         * @param  array $subscribers
         * @return CS_REST_Wrapper_Result|false
         */
        public static function import($key, $subscribers)
        {
            $id = self::_getList($key);
            foreach ($subscribers as &$subscriber) {
                $subscriber = self::_format($subscriber);
            }
            $response = self::_import($id, $subscribers);
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
            if (is_null(self::$_initiated) === false) {
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
         * @return CS_REST_Wrapper_Result|false
         */
        public static function remove($key, $email)
        {
            $id = self::_getList($key);
            $response = self::_remove($id, $email);
            if ($response === false) {
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
         * @return CS_REST_Wrapper_Result|false
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
         * update
         *
         * @static
         * @access public
         * @param  string|array $key
         * @param  string $email
         * @param  array $subscriber
         * @return CS_REST_Wrapper_Result|false
         */
        public static function update($key, $email, array $subscriber)
        {
            $id = self::_getList($key);
            $subscriber = self::_format($subscriber);
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
         * stored in config.
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

    // Config
    $info = pathinfo(__DIR__);
    $parent = ($info['dirname']) . '/' . ($info['basename']);
    $configPath = ($parent) . '/config.inc.php';
    if (is_file($configPath)) {
        CampaignMonitor::setConfigPath($configPath);
    }
