<?php
/**
 * @brief whiteListCom, a plugin for Dotclear 2
 * 
 * @package Dotclear
 * @subpackage Plugin
 * 
 * @author Jean-Christian Denis and Contributors
 * 
 * @copyright Jean-Christian Denis
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('DC_RC_PATH')) {
    return null;
}

$this->registerModule(
    'Whitelist comments',
    'Whitelists for comments moderation',
    'Jean-Christian Denis and Contributors',
    '0.8',
    [
        'requires' => [['core', '2.19']],
        'permissions' => 'admin',
        'priority' => 200,
        'type'=> 'plugin',
        'support'=> 'https://github.com/JcDenis/whiteListCom',
        'details'=> 'https://plugins.dotaddict.org/dc2/details/whiteListCom',
        'repository' => 'https://raw.githubusercontent.com/JcDenis/whiteListCom/master/dcstore.xml'
    ]
);