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

$this->registerModule(
    'Whitelist comments',
    'Whitelists for comments moderation',
    'Jean-Christian Denis and Contributors',
    '0.7',
    [
        'permissions' => 'admin',
        'priority' => 200,
        'type'          => 'plugin',
        'dc_min'        => '2.18',
        'support'       => 'https://github.com/JcDenis/whiteListCom',
        'details'       => 'https://plugins.dotaddict.org/dc2/details/whiteListCom'
    ]
);