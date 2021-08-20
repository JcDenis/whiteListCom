<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
#
# This file is part of whiteListCom, a plugin for Dotclear 2.
# 
# Copyright (c) 2009-2021 Jean-Christian Denis and contributors
# 
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK ------------------------------------

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