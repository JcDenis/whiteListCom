<?php
/**
 * @file
 * @brief       The plugin whiteListCom definition
 * @ingroup     whiteListCom
 *
 * @defgroup    whiteListCom Plugin whiteListCom.
 *
 * Whitelists for comments moderation.
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

$this->registerModule(
    'Whitelist comments',
    'Whitelists for comments moderation',
    'Jean-Christian Denis and Contributors',
    '1.7',
    [
        'requires'    => [['core', '2.37']],
        'permissions' => 'My',
        'priority'    => 200,
        'type'        => 'plugin',
        'support'     => 'https://github.com/JcDenis/' . $this->id . '/issues',
        'details'     => 'https://github.com/JcDenis/' . $this->id . '/',
        'repository'  => 'https://raw.githubusercontent.com/JcDenis/' . $this->id . '/master/dcstore.xml',
        'date'        => '2025-09-11T19:00:45+00:00',
    ]
);
