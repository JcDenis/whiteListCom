<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
#
# This file is part of whiteListCom, a plugin for Dotclear 2.
# 
# Copyright (c) 2009-2013 Jean-Christian Denis and contributors
# contact@jcdenis.fr http://jcd.lv
# 
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK ------------------------------------

if (!defined('DC_RC_PATH')){return;}
 
$this->registerModule(
	/* Name */
	"Whitelist comments",
	/* Description*/
	"Whitelists for comments moderation",
	/* Author */
	"Jean-Christian Denis",
	/* Version */
	'0.6',
	array(
		'permissions' => 'admin',
		'priority' => 200,
		'type' => 'plugin',
		'dc_min' => '2.6',
		'support' => 'http://jcd.lv/q=whiteListCom',
		'details' => 'http://plugins.dotaddict.org/dc2/details/whiteListCom'
	)
);
