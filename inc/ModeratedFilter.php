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

/**
 * @ingroup DC_PLUGIN_WHITELISTCOM
 * @brief Filter for unmoderated authors.
 * @since 2.6
 *
 * This filter is used only if comments are moderates
 */
class whiteListComModeratedFilter extends dcSpamFilter
{
    public $name    = 'Unmoderated authors';
    public $has_gui = true;

    protected function setInfo()
    {
        $this->name        = __('Unmoderated authors');
        $this->description = __('Whitelist of unmoderated authors');
    }

    public function isSpam($type, $author, $email, $site, $ip, $content, $post_id, &$status)
    {
        if ($type != 'comment'
         || dcCore::app()->blog === null
         || dcCore::app()->blog->settings->system->comments_pub) {
            return null;
        }

        try {
            $wlc = new whiteListCom();
            if ($wlc->isUnmoderated($email)) {
                $status = 'unmoderated';

                # return true in order to change comment_status after
                return true;
            }

            return null;
        } catch (Exception $e) {
        }
    }

    public function gui(string $url): string
    {
        $wlc   = new whiteListCom();
        $posts = $comments = [];

        try {
            if (!empty($_POST['update_unmoderated'])) {
                $wlc->emptyUnmoderated();
                foreach ($_POST['unmoderated'] as $email) {
                    $wlc->addUnmoderated($email);
                }
                $wlc->commit();
            }
            $posts    = $wlc->getPostsUsers();
            $comments = $wlc->getCommentsUsers();
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        $res = '';

        if (dcCore::app()->blog->settings->system->comments_pub) {
            $res .= '<p class="message">' .
            __('This filter is used only if comments are moderates') .
            '</p>';
        }

        $res .= '<form action="' . html::escapeURL($url) . '" method="post">' .
        '<p>' . __('Check the users who can make comments without being moderated.') . '</p>' .
        '<div class="two-cols">' .
        '<div class="col">' .
        '<p>' . __('Posts authors list') . '</p>' .
        '<table class="clear">' .
        '<thead><tr><th>' . __('Name') . '</th><th>' . __('Email') . '</th></tr></thead>' .
        '<tbody>';

        foreach ($posts as $user) {
            $res .= '<tr class="line">' .
            '<td class="nowrap">' .
            form::checkbox(
                ['unmoderated[]'],
                $user['email'],
                $wlc->isUnmoderated($user['email'])
            ) .
            ' ' . $user['name'] . '</td>' .
            '<td class="nowrap">' . $user['email'] . '</td>' .
            '</tr>';
        }

        $res .= '</tbody>' .
        '</table>' .
        '</div>' .
        '<div class="col">' .
        '<p>' . __('Comments authors list') . '</p>' .
        '<table class="clear">' .
        '<thead><tr><th>' . __('Author') . '</th><th>' . __('Email') . '</th></tr></thead>' .
        '<tbody>';

        foreach ($comments as $user) {
            $res .= '<tr class="line">' .
            '<td class="nowrap">' .
            form::checkbox(
                ['unmoderated[]'],
                $user['email'],
                $wlc->isUnmoderated($user['email'])
            ) .
            ' ' . $user['name'] . '</td>' .
            '<td class="nowrap">' . $user['email'] . '</td>' .
            '</tr>';
        }

        $res .= '</tbody>' .
        '</table>' .
        '</div>' .
        '</div>' .
        '<p><input type="submit" name="update_unmoderated" value="' . __('Save') . '" />' .
        dcCore::app()->formNonce() . '</p>' .
        '</form>';

        return $res;
    }
}
