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
    '1.4.2',
    [
        'requires'    => [['core', '2.28']],
        'permissions' => 'My',
        'priority'    => 200,
        'type'        => 'plugin',
        'support'     => 'https://git.dotclear.watch/JcDenis/' . basename(__DIR__) . '/issues',
        'details'     => 'https://git.dotclear.watch/JcDenis/' . basename(__DIR__) . '/src/branch/master/README.md',
        'repository'  => 'https://git.dotclear.watch/JcDenis/' . basename(__DIR__) . '/raw/branch/master/dcstore.xml',
    ]
);
