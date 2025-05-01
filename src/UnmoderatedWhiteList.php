<?php

declare(strict_types=1);

namespace Dotclear\Plugin\whiteListCom;

use Dotclear\App;
use Dotclear\Core\Backend\Notices;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Plugin\antispam\SpamFilter;
use Exception;

/**
 * @brief   whiteListCom unmoderated antispam filter.
 * @ingroup whiteListCom
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class UnmoderatedWhiteList extends SpamFilter
{
    public string $name  = 'Unmoderated authors';
    public bool $has_gui = true;

    protected function setInfo(): void
    {
        $this->name        = __('Unmoderated authors');
        $this->description = __('Whitelist of unmoderated authors');
    }

    /**
     * @return  null|true
     */
    public function isSpam(string $type, ?string $author, ?string $email, ?string $site, ?string $ip, ?string $content, ?int $post_id, string &$status)
    {
        if ($type == 'comment'
            && Utils::isUnmoderated((string) $email)
            && !App::blog()->settings()->get('system')->get('comments_pub')
        ) {
            $status = $this->name;

            # Mark as spam to change status later and stop filters
            # To check email/author couple use filter RerservedWhiteList before
            return true;
        }

        # Go through others filters
        return null;
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
            App::error()->add($e->getMessage());
        }

        $res = '';

        if (App::blog()->isDefined() && App::blog()->settings()->get('system')->get('comments_pub')) {
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
        App::nonce()->getFormNonce() . '</p>' .
        '</form>';

        return $res;
    }
}
