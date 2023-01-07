<?php
/**
 * @brief whiteListCom, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugin
 *
 * @author Jean-Christian Denis
 *
 * @copyright Jean-Christian Denis
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

$install = implode('\\', ['Dotclear', 'Plugin', basename(__DIR__), 'Install']);
if ($install::init()) {
    return $install::process();
}

return null;
