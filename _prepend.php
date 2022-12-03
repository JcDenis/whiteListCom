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

$d = __DIR__ . '/inc/lib.whitelistcom.php';

Clearbricks::lib()->autoload([
    'whiteListCom'                => $d,
    'whiteListComBehaviors'       => $d,
    'whiteListComReservedFilter'  => $d,
    'whiteListComModeratedFilter' => $d,
]);

dcCore::app()->spamfilters[] = 'whiteListComModeratedFilter';

dcCore::app()->addBehavior(
    'publicAfterCommentCreate',
    ['whiteListComBehaviors', 'switchStatus']
);

dcCore::app()->spamfilters[] = 'whiteListComReservedFilter';
