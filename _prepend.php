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
if (!defined('DC_RC_PATH')) {
    return null;
}

Clearbricks::lib()->autoload([
    'whiteListCom'                => __DIR__ . '/inc/Core.php',
    'whiteListComReservedFilter'  => __DIR__ . '/inc/ReservedFilter.php',
    'whiteListComModeratedFilter' => __DIR__ . '/inc/ModeratedFilter.php',
]);

dcCore::app()->spamfilters[] = 'whiteListComModeratedFilter';
dcCore::app()->spamfilters[] = 'whiteListComReservedFilter';

dcCore::app()->addBehavior('publicAfterCommentCreate', function ($cur, $id) {
    if (dcCore::app()->blog === null
     || dcCore::app()->blog->settings->get('system')->get('comments_pub')) {
        return null;
    }

    if ($cur->__get('comment_spam_filter') == 'whiteListComModeratedFilter'
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
