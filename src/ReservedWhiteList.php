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
use dcPage;
use Dotclear\Helper\Html\Form\{
    Checkbox,
    Hidden
};
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Plugin\antispam\SpamFilter;
use Exception;

/**
 * @ingroup DC_PLUGIN_WHITELISTCOM
 * @brief Filter for reserved names.
 * @since 2.6
 */
class ReservedWhiteList extends SpamFilter
{
    public $name    = 'Reserved names';
    public $has_gui = true;

    /**
     * @return  void
     */
    protected function setInfo()
    {
        $this->name        = __('Reserved names');
        $this->description = __('Whitelist of reserved names of users');
    }

    /**
     * @return  void|null|bool
     */
    public function isSpam($type, $author, $email, $site, $ip, $content, $post_id, &$status)
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

    /**
     * @return  string
     */
    public function getStatusMessage($status, $comment_id)
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
                dcPage::addSuccessNotice(__('Reserved names have been successfully updated.'));
                Http::redirect($url);
            }

            $comments = Utils::getCommentsUsers();
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        $res = '<form action="' . Html::escapeURL($url) . '" method="post">' .
        '<p>' . __('Check the users who can make comments without being moderated.') . '</p>' .
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
        dcCore::app()->formNonce() . '</p>' .
        '</form>';

        return $res;
    }
}
