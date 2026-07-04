<?php

!defined('DEBUG') AND exit('Access Denied.');

include_once APP_PATH . 'plugin/ddys_open/source/bootstrap.php';
ddys_open_bootstrap();
ddys_open_uninstall();
