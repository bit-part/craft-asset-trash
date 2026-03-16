<?php

define('CRAFT_VENDOR_PATH', dirname(__DIR__) . '/vendor');

if (!is_dir(CRAFT_VENDOR_PATH)) {
    // When running outside the host project, use the plugin's own autoloader
    require_once dirname(__DIR__) . '/vendor/autoload.php';
} else {
    require_once CRAFT_VENDOR_PATH . '/autoload.php';
}
