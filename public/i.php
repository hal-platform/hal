<?php

if (isset($_GET['apc']) && function_exists('apc_clear_cache')) {
    if (isset($_GET['user'])) {
        if (function_exists('apc_clear_cache') && version_compare(PHP_VERSION, '5.5.0', '>=') && apc_clear_cache()) {
            echo '<br>User Cache: success';
        } elseif (function_exists('apc_clear_cache') && version_compare(PHP_VERSION, '5.5.0', '<') && apc_clear_cache('user')) {
            echo '<br>User Cache: success';
        } else {
            echo '<br>User Cache: failure';
        }
    }

    if (isset($_GET['opcode'])) {
        if (function_exists('opcache_reset') && opcache_reset()) {
            echo '<br>Opcode Cache: success';
        } elseif (function_exists('apc_clear_cache') && version_compare(PHP_VERSION, '5.5.0', '<') && apc_clear_cache('opcode')) {
            echo '<br>Opcode Cache: success';
        }
        else {
            echo '<br>Opcode Cache: failure';
        }
    }
} else {
    phpinfo();
}
