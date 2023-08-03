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
    '1.3',
    [
        'requires' => [
            ['core', '2.27'],
            ['antispam', '2.0'],
        ],
        'permissions' => dcCore::app()->auth->makePermissions([
            dcCore::app()->auth::PERMISSION_USAGE,
            dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
        ]),
        'priority'   => 200,
        'type'       => 'plugin',
        'support'    => 'http://gitea.jcdenis.fr/Dotclear/whiteListCom',
        'details'    => 'http://gitea.jcdenis.fr/Dotclear/whiteListCom/src/branch/master/README.md',
        'repository' => 'http://gitea.jcdenis.fr/Dotclear/whiteListCom/raw/branch/master/dcstore.xml',
    ]
);
