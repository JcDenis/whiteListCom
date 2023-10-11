<?php

declare(strict_types=1);

namespace Dotclear\Plugin\whiteListCom;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Database\Cursor;

/**
 * @brief   whiteListCom prepend class.
 * @ingroup whiteListCom
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Prepend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::PREPEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        App::behavior()->addBehaviors([
            'AntispamInitFilters' => function (ArrayObject $spamfilters): void {
                $spamfilters[] = UnmoderatedWhiteList::class;
                $spamfilters[] = ReservedWhiteList::class;
            },
            'publicAfterCommentCreate' => function (Cursor $cur, int $id): void {
                if (!App::blog()->isDefined() || App::blog()->settings()->get('system')->get('comments_pub')) {
                    return;
                }

                if ($cur->__get('comment_spam_filter') == 'UnmoderatedWhiteList'
                 && $cur->__get('comment_spam_status') == 'unmoderated') {
                    App::con()->writeLock(App::con()->prefix() . App::blog()::COMMENT_TABLE_NAME);

                    $cur->__set('comment_status', 1);
                    $cur->__set('comment_spam_status', 0);
                    $cur->__set('comment_spam_filter', 0);
                    $cur->update('WHERE comment_id = ' . $id . ' ');

                    App::con()->unlock();

                    App::blog()->triggerComment($id);
                    App::blog()->triggerBlog();
                }
            },
        ]);

        return true;
    }
}
