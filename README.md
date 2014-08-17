TurtlePHP-CampaignMonitorPlugin
======================

``` php
require_once APP . '/plugins/TurtlePHP-ConfigPlugin/Config.class.php';
require_once APP . '/vendors/campaignmonitor-createsend-php/csrest_subscribers.php';
require_once APP . '/plugins/TurtlePHP-CampaignMonitorPlugin/CampaignMonitor.class.php';
\Plugin\CampaignMonitor::init();
```

``` php
...
\Plugin\CampaignMonitor::setConfigPath('/path/to/config/file.inc.php');
\Plugin\CampaignMonitor::init();
```
