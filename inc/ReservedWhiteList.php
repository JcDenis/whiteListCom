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
use dcPage;
use dcSpamFilter;

/* clearbricks ns */
use form;
use html;
use http;

/* php ns */
use Exception;


/**
 * @ingroup DC_PLUGIN_WHITELISTCOM
 * @brief Filter for reserved names.
 * @since 2.6
 */
class ReservedWhiteList extends dcSpamFilter
{
    public $name    = 'Reserved names';
    public $has_gui = true;

    protected function setInfo()
    {
        $this->name        = __('Reserved names');
        $this->description = __('Whitelist of reserved names of users');
    }

    public function isSpam($type, $author, $email, $site, $ip, $content, $post_id, &$status)
    {
        if ($type != 'comment') {
            return null;
        }

        $throw = false;

        try {
            $wlc = new Core();

            if (true === $wlc->isReserved($author, $email)) {
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

    public function getStatusMessage($status, $comment_id)
    {
        return __('This name is reserved to an other user.');
    }

    public function gui(string $url): string
    {
        $wlc      = new Core();
        $comments = [];

        try {
            if (!empty($_POST['update_reserved'])) {
                $wlc->emptyReserved();
                foreach ($_POST['reserved'] as $i => $name) {
                    $wlc->addReserved($name, $_POST['reserved_email'][$i]);
                }
                $wlc->commit();
                dcPage::addSuccessNotice(__('Reserved name have been successfully updated.'));
                http::redirect($url);
            }

            $comments = $wlc->getCommentsUsers();
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        $res = '<form action="' . html::escapeURL($url) . '" method="post">' .
        '<p>' . __('Check the users who can make comments without being moderated.') . '</p>' .
        '<p>' . __('Comments authors list') . '</p>' .
        '<table class="clear">' .
        '<thead><tr><th>' . __('Author') . '</th><th>' . __('Email') . '</th></tr></thead>' .
        '<tbody>';

        $i = 0;
        foreach ($comments as $user) {
            $res .= '<tr class="line">' .
            '<td class="nowrap">' .
            form::checkbox(
                ['reserved[' . $i . ']'],
                $user['name'],
                (null === $wlc->isReserved($user['name'], $user['email']))
            ) .
            form::hidden(['reserved_email[' . $i . ']'], $user['email']) .
            ' ' . $user['name'] . '</td>' .
            '<td class="nowrap">' . $user['email'] . '</td>' .
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
