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

use dcCore;
use dcNamespace;
use Dotclear\Core\Process;
use Exception;

class Install extends Process
{
    // Module specs
    private static array $mod_conf = [
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
        return self::status(My::checkContext(My::INSTALL));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        try {
            // Upgrade
            self::growUp();

            // Set module settings
            foreach (self::$mod_conf as $v) {
                My::settings()->put(
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
                if (preg_match('/^whiteListCom(.*?)$/', $record->f('setting_id'), $match)) {
                    $value = @unserialize(@base64_decode($record->f('setting_value')));
                    $cur   = dcCore::app()->con->openCursor(dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME);
                    $cur->setField('setting_id', $match[1]);
                    $cur->setField('setting_ns', My::id());
                    $cur->setField('setting_value', is_array($value) ? json_encode($value) : '[]');
                    $cur->update(
                        "WHERE setting_id = '" . $record->f('setting_id') . "' and setting_ns = 'whiteListCom' " .
                        'AND blog_id ' . (null === $record->f('blog_id') ? 'IS NULL ' : ("= '" . dcCore::app()->con->escapeStr((string) $record->f('blog_id')) . "' "))
                    );
                }
            }
        }
    }
}
