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
declare(strict_types=1);

namespace Dotclear\Plugin\whiteListCom;

/* dotclear ns */
use dcCore;
use dcNamespace;
use dcNsProcess;

/* php ns */
use Exception;

class Install extends dcNsProcess
{
    // Module specs
    private static $mod_conf = [
        [
            'unmoderated',
            '[]',
            'string',
            'Whitelist of unmoderated users on comments',
        ],
        [
            'reserved',
            '[]',
            'string',
            'Whitelist of reserved names on comments',
        ],
    ];

    public static function init(): bool
    {
        self::$init = defined('DC_CONTEXT_ADMIN') && dcCore::app()->newVersion(My::id(), dcCore::app()->plugins->moduleInfo(My::id(), 'version'));

        return self::$init;
    }

    public static function process(): bool
    {
        if (!self::$init) {
            return false;
        }

        try {
            // Upgrade
            self::growUp();

            // Set module settings
            foreach (self::$mod_conf as $v) {
                dcCore::app()->blog->settings->get(My::id())->put(
                    $v[0],
                    $v[1],
                    $v[2],
                    $v[3],
                    false,
                    true
                );
            }

            return true;
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());

            return false;
        }
    }

    public static function growUp(): void
    {
        $current = dcCore::app()->getVersion(My::id());

        // Update settings id, ns
        if ($current && version_compare($current, '1.0', '<')) {
            $record = dcCore::app()->con->select(
                'SELECT * FROM ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME . ' ' .
                "WHERE setting_ns = 'whiteListCom' "
            );

            while ($record->fetch()) {
                if (preg_match('/^whiteListCom(.*?)$/', $record->setting_id, $match)) {
                    $value              = @unserialize(@base64_decode($record->setting_value));
                    $cur                = dcCore::app()->con->openCursor(dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME);
                    $cur->setting_id    = $match[1];
                    $cur->setting_ns    = My::id();
                    $cur->setting_value = is_array($value) ? json_encode($value) : '[]';
                    $cur->update(
                        "WHERE setting_id = '" . $record->setting_id . "' and setting_ns = 'whiteListCom' " .
                        'AND blog_id ' . (null === $record->blog_id ? 'IS NULL ' : ("= '" . dcCore::app()->con->escape($record->blog_id) . "' "))
                    );
                }
            }
        }
    }
}
