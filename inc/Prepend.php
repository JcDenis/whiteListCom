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

/* clearbricks ns */
use Clearbricks;

class Prepend
{
    private const LIBS = [
        'Core',
        'UnmoderatedWhiteList',
        'ReservedWhiteList',
        'Install',
        'Prepend',
    ];
    protected static $init = false;

    public static function init(): bool
    {
        self::$init = defined('DC_RC_PATH');

        return self::$init;
    }

    public static function process(): ?bool
    {
        if (!self::$init) {
            return false;
        }

        foreach (self::LIBS as $lib) {
            Clearbricks::lib()->autoload([
                __NAMESPACE__ . '\\' . $lib => __DIR__ . DIRECTORY_SEPARATOR . $lib . '.php',
            ]);
        }

        dcCore::app()->spamfilters[] = __NAMESPACE__ . '\\' . 'UnmoderatedWhiteList';
        dcCore::app()->spamfilters[] = __NAMESPACE__ . '\\' . 'ReservedWhiteList';

        dcCore::app()->addBehavior('publicAfterCommentCreate', function ($cur, $id) {
            if (dcCore::app()->blog === null
             || dcCore::app()->blog->settings->get('system')->get('comments_pub')) {
                return null;
            }

            if ($cur->__get('comment_spam_filter') == 'UnmoderatedWhiteList'
             && $cur->__get('comment_spam_status') == 'unmoderated') {
                dcCore::app()->con->writeLock(dcCore::app()->prefix . dcBlog::COMMENT_TABLE_NAME);

                $cur->__set('comment_status', 1);
                $cur->__set('comment_spam_status', 0);
                $cur->__set('comment_spam_filter', 0);
                $cur->update('WHERE comment_id = ' . $id . ' ');

                dcCore::app()->con->unlock();

                dcCore::app()->blog->triggerComment($id);
                dcCore::app()->blog->triggerBlog();
            }
        });

        return true;
    }
}

