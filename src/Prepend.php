<?php

declare(strict_types=1);

namespace Dotclear\Plugin\whiteListCom;

use ArrayObject;
use Dotclear\App;
use Dotclear\Helper\Process\TraitProcess;
use Dotclear\Database\Cursor;

/**
 * @brief   whiteListCom prepend class.
 * @ingroup whiteListCom
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Prepend
{
    use TraitProcess;

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
                $spamfilters[] = ReservedWhiteList::class;
                $spamfilters[] = UnmoderatedWhiteList::class;
            },
            'publicAfterCommentCreate' => function (Cursor $cur, int $id): void {
                if (App::blog()->isDefined()
                    && App::plugins()->moduleExists('antispam')
                    && $cur->getField('comment_spam_filter') == 'UnmoderatedWhiteList'
                    && $cur->getField('comment_spam_status') == __('Unmoderated authors')
                ) {
                    App::db()->con()->writeLock(App::db()->con()->prefix() . App::blog()::COMMENT_TABLE_NAME);

                    $cur->setField('comment_status', 1);
                    $cur->setField('comment_spam_status', 0);
                    $cur->setField('comment_spam_filter', 0);
                    $cur->update('WHERE comment_id = ' . $id . ' ');

                    App::db()->con()->unlock();

                    App::blog()->triggerComment($id);
                    App::blog()->triggerBlog();
                }
            },
        ]);

        return true;
    }
}
