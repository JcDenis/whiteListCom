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
use Dotclear\COre\Backend\Notices;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Plugin\antispam\SpamFilter;
use Exception;

/**
 * @ingroup DC_PLUGIN_WHITELISTCOM
 * @brief Filter for unmoderated authors.
 * @since 2.6
 *
 * This filter is used only if comments are moderates
 */
class UnmoderatedWhiteList extends SpamFilter
{
    public $name    = 'Unmoderated authors';
    public $has_gui = true;

    /**
     * @return  void
     */
    protected function setInfo()
    {
        $this->name        = __('Unmoderated authors');
        $this->description = __('Whitelist of unmoderated authors');
    }

    /**
     * @return  void|null|bool
     */
    public function isSpam($type, $author, $email, $site, $ip, $content, $post_id, &$status)
    {
        if ($type != 'comment'
         || dcCore::app()->blog === null
         || dcCore::app()->blog->settings->get('system')->get('comments_pub')) {
            return null;
        }

        try {
            if (Utils::isUnmoderated($email)) {
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
        $wlc   = new Utils();
        $posts = $comments = [];

        try {
            if (!empty($_POST['update_unmoderated'])) {
                Utils::emptyUnmoderated();
                foreach ($_POST['unmoderated'] as $email) {
                    Utils::addUnmoderated($email);
                }
                Utils::commit();
                Notices::addSuccessNotice(__('Unmoderated names have been successfully updated.'));
                Http::redirect($url);
            }
            $posts    = Utils::getPostsUsers();
            $comments = Utils::getCommentsUsers();
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        $res = '';

        if (!is_null(dcCore::app()->blog) && dcCore::app()->blog->settings->get('system')->get('comments_pub')) {
            $res .= '<p class="message">' .
            __('This filter is used only if comments are moderates') .
            '</p>';
        }

        $res .= '<form action="' . Html::escapeURL($url) . '" method="post">' .
        '<p>' . __('Check the users who can make comments without being moderated.') . '</p>' .
        '<div class="two-boxes">' .
        '<div class="box odd">' .
        '<div class="table-outer">' .
        '<table class="clear">' .
        '<caption>' . __('Posts authors list') . '</caption>' .
        '<thead><tr><th>' . __('Name') . '</th><th>' . __('Email') . '</th></tr></thead>' .
        '<tbody>';

        foreach ($posts as $user) {
            $checked = Utils::isUnmoderated($user['email']);
            $res .= '<tr class="line' . ($checked ? '' : ' offline') . '">' .
            '<td class="nowrap">' .
            (new Checkbox(['unmoderated[]'], $checked))->value($user['email'])->render() .
            ' ' . $user['name'] . '</td>' .
            '<td class="nowrap">' . $user['email'] . '</td>' .
            '</tr>';
        }

        $res .= '</tbody>' .
        '</table></div>' .
        '</div>' .
        '<div class="box even">' .
        '<div class="table-outer">' .
        '<table class="clear">' .
        '<caption>' . __('Comments authors list') . '</caption>' .
        '<thead><tr><th>' . __('Author') . '</th><th>' . __('Email') . '</th></tr></thead>' .
        '<tbody>';

        foreach ($comments as $user) {
            $checked = Utils::isUnmoderated($user['email']);
            $res .= '<tr class="line' . ($checked ? '' : ' offline') . '">' .
            '<td class="nowrap">' .
            (new Checkbox(['unmoderated[]'], $checked))->value($user['email'])->render() .
            ' ' . $user['name'] . '</td>' .
            '<td class="nowrap">' . $user['email'] . '</td>' .
            '</tr>';
        }

        $res .= '</tbody>' .
        '</table></div>' .
        '</div>' .
        '</div>' .
        '<p><input type="submit" id="update_unmoderated" name="update_unmoderated" value="' . __('Save') . '" />' .
        dcCore::app()->formNonce() . '</p>' .
        '</form>';

        return $res;
    }
}
