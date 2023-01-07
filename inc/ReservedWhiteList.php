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
use dcSpamFilter;

/* clearbricks ns */
use form;
use html;

/* php ns */
use Exception;


/**
 * @ingroup DC_PLUGIN_WHITELISTCOM
 * @brief Filter for reserved names.
 * @since 2.6
 */
class whiteListComReservedFilter extends dcSpamFilter
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
                foreach ($_POST['reserved'] as $email => $name) {
                    $wlc->addReserved($name, $email);
                }
                $wlc->commit();
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

        foreach ($comments as $user) {
            $res .= '<tr class="line">' .
            '<td class="nowrap">' .
            form::checkbox(
                ['reserved[' . $user['email'] . ']'],
                $user['name'],
                (null === $wlc->isReserved($user['name'], $user['email']))
            ) .
            ' ' . $user['name'] . '</td>' .
            '<td class="nowrap">' . $user['email'] . '</td>' .
            '</tr>';
        }

        $res .= '</tbody>' .
        '</table>' .
        '<p><input type="submit" name="update_reserved" value="' . __('Save') . '" />' .
        dcCore::app()->formNonce() . '</p>' .
        '</form>';

        return $res;
    }
}
