TurtlePHP-CampaignMonitorPlugin
======================

``` php
require_once APP . '/plugins/TurtlePHP-ConfigPlugin/Config.class.php';
require_once APP . '/vendors/campaignmonitor-createsend-php/csrest_subscribers.php';
require_once APP . '/plugins/TurtlePHP-CampaignMonitorPlugin/CampaignMonitor.class.php';
TurtlePHP\Plugin\CampaignMonitor::init();
```

``` php
...
TurtlePHP\Plugin\CampaignMonitor::setConfigPath('/path/to/config/file.inc.php');
TurtlePHP\Plugin\CampaignMonitor::init();
```
