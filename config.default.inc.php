<?php

    /**
     * Namespace
     * 
     */
    namespace Plugin\CampaignMonitor;

    /**
     * Plugin Config Data
     * 
     */

    // API credentials
    $apiKey = '***';
    $clientId = '***';
    $credentials = compact('apiKey', 'clientId');

    // Transactional emails
    $emails = array(
        'shortcutKey' => '***'
    );

    // Lists
    $lists = array();
        'shortcutKey' => '***'
    );

    // Webhooks
    $webhooks = array();

    // Compact
    $pluginConfigData = compact('credentials', 'emails', 'lists', 'webhooks');

    /**
     * Storage
     * 
     */
    $key = 'TurtlePHP-CampaignMonitorPlugin';
    \Plugin\Config::add($key, $pluginConfigData);
