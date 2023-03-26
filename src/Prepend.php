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

use dcBlog;
use dcCore;
use dcNsProcess;

class Prepend extends dcNsProcess
{
    public static function init(): bool
    {
        static::$init = true;

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        dcCore::app()->spamfilters[] = UnmoderatedWhiteList::class;
        dcCore::app()->spamfilters[] = ReservedWhiteList::class;

        dcCore::app()->addBehavior('publicAfterCommentCreate', function ($cur, $id): void {
            if (dcCore::app()->blog === null
             || dcCore::app()->blog->settings->get('system')->get('comments_pub')) {
                return;
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
