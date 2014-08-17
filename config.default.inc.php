<?php

    /**
     * Namespace
     * 
     */
    namespace Plugin\CampaignMonitor;

    /**
     * Data
     * 
     */

    // API credentials
    $credentials = array(
        'apiKey' => '***',
    );

    // Lists
    $lists = array(
        'all' => 'abcd'
    );

    /**
     * Config storage
     * 
     */

    // Store
    \Plugin\Config::add(
        'TurtlePHP-CampaignMonitorPlugin',
        array(
            'credentials' => $credentials,
            'lists' => $lists
        )
    );
