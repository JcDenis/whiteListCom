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

$d = dirname(__FILE__).'/inc/lib.whitelistcom.php';

$__autoload['whiteListCom'] = $d;
$__autoload['whiteListComBehaviors'] = $d;
$__autoload['whiteListComReservedFilter'] = $d;
$__autoload['whiteListComModeratedFilter'] = $d;

$core->spamfilters[] = 'whiteListComModeratedFilter';

$core->addBehavior(
    'publicAfterCommentCreate',
    ['whiteListComBehaviors', 'switchStatus']
);

$core->spamfilters[] = 'whiteListComReservedFilter';