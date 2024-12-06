<?php

declare(strict_types=1);

namespace Dotclear\Plugin\whiteListCom;

use Dotclear\App;
use Dotclear\Core\Backend\Notices;
use Dotclear\Helper\Html\Form\{
    Checkbox,
    Hidden
};
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Plugin\antispam\SpamFilter;
use Exception;

/**
 * @brief   whiteListCom resserved name antispam filter.
 * @ingroup whiteListCom
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class ReservedWhiteList extends SpamFilter
{
    public string $name  = 'Reserved names';
    public bool $has_gui = true;

    protected function setInfo(): void
    {
        $this->name        = __('Reserved names');
        $this->description = __('Whitelist of reserved names of users');
    }

    /**
     * @return  void|null|bool
     */
    public function isSpam(string $type, ?string $author, ?string $email, ?string $site, ?string $ip, ?string $content, ?int $post_id, string &$status)
    {
        if ($type != 'comment') {
            return null;
        }

        $throw = false;

        try {
            if (true === Utils::isReserved($author, $email)) {
                $status = 'reserved name';
                //return true;
                $throw = true;
            } else {
                return null;
            }
        } catch (Exception $e) {
        }

        # This message is show to author even if comments are moderated, comment is not saved
        if ($throw) {
            throw new Exception(__('This name is reserved to an other user.'));
        }
    }

    public function getStatusMessage(string $status, ?int $comment_id): string
    {
        return __('This name is reserved to an other user.');
    }

    public function gui(string $url): string
    {
        $comments = [];

        try {
            if (!empty($_POST['update_reserved'])) {
                Utils::emptyReserved();
                foreach ($_POST['reserved'] as $i => $name) {
                    Utils::addReserved($name, $_POST['reserved_email'][$i]);
                }
                Utils::commit();
                Notices::addSuccessNotice(__('Reserved names have been successfully updated.'));
                Http::redirect($url);
            }

            $comments = Utils::getCommentsUsers();
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }

        $res = '<form action="' . Html::escapeURL($url) . '" method="post">' .
        '<p>' . __('Check the users who have a reserved name (link to an email).') . '</p>' .
        '<div class="table-outer">' .
        '<table class="clear">' .
        '<caption>' . __('Comments authors list') . '</caption>' .
        '<thead><tr><th>' . __('Author') . '</th><th>' . __('Email') . '</th></tr></thead>' .
        '<tbody>';

        $i = 0;
        foreach ($comments as $user) {
            $checked = null === Utils::isReserved($user['name'], $user['email']);
            $res .= '<tr class="line' . ($checked ? '' : ' offline') . '">' .
            '<td class="nowrap">' .
            (new Checkbox(['reserved[' . $i . ']'], $checked))->value($user['name'])->render() .
            (new Hidden(['reserved_email[' . $i . ']'], $user['email']))->render() .
            ' ' . $user['name'] . '</td>' .
            '<td class="nowrap maximal">' . $user['email'] . '</td>' .
            '</tr>';
            $i++;
        }

        $res .= '</tbody>' .
        '</table>' .
        '<p><input type="submit" name="update_reserved" value="' . __('Save') . '" />' .
        App::nonce()->getFormNonce() . '</p>' .
        '</form>';

        return $res;
    }
}
