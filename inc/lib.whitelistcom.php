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
    public $name = 'Unmoderated authors';
    public $has_gui = true;

    protected function setInfo()
    {
        $this->name = __('Unmoderated authors');
        $this->description = __('Whitelist of unmoderated authors');
    }

    public function isSpam($type, $author, $email, $site, $ip, $content, $post_id, &$status)
    {
        if ($type != 'comment'
         || $this->core->blog === null 
         || $this->core->blog->settings->system->comments_pub) {
            return null;
        }

        try {
            $wlc = new whiteListCom($this->core);
            if ($wlc->isUnmoderated($email)) {
                $status = 'unmoderated';

                # return true in order to change comment_status after
                return true; 
            } else {

                return null;
            }
        } catch (Exception $e) {

        }
    }

    public function gui($url)
    {
        try {
            $wlc = new whiteListCom($this->core);

            if (!empty($_POST['update_unmoderated'])) {
                $wlc->emptyUnmoderated();
                foreach($_POST['unmoderated'] as $email) {
                    $wlc->addUnmoderated($email);
                }
                $wlc->commit();
            }
            $posts = $wlc->getPostsUsers();
            $comments = $wlc->getCommentsUsers();
        } catch (Exception $e) {
            $this->core->error->add($e->getMessage());
        }

        $res = '';

        if ($this->core->blog->settings->system->comments_pub) {
            $res .= 
            '<p class="message">' .
            __('This filter is used only if comments are moderates') .
            '</p>';
        }

        $res .=
        '<form action="' . html::escapeURL($url) . '" method="post">' .
        '<p>' . __('Check the users who can make comments without being moderated.') . '</p>' .
        '<div class="two-cols">' .
        '<div class="col">' .
        '<p>' . __('Posts authors list') . '</p>' .
        '<table class="clear">' .
        '<thead><tr><th>' . __('Name') . '</th><th>' . __('Email') . '</th></tr></thead>' .
        '<tbody>';

        foreach($posts as $user) {
            $res .=
            '<tr class="line">' .
            '<td class="nowrap">' .
            form::checkbox(
                array('unmoderated[]'), 
                $user['email'],
                $wlc->isUnmoderated($user['email'])
            ) .
            ' ' . $user['name'] . '</td>' .
            '<td class="nowrap">' . $user['email'] . '</td>' .
            '</tr>';
        }

        $res .=
        '</tbody>' .
        '</table>' .
        '</div>' .
        '<div class="col">' .
        '<p>' . __('Comments authors list') . '</p>' .
        '<table class="clear">' .
        '<thead><tr><th>' . __('Author') . '</th><th>' . __('Email') . '</th></tr></thead>' .
        '<tbody>';

        foreach($comments as $user) {
            $res .=
            '<tr class="line">' .
            '<td class="nowrap">' .
            form::checkbox(
                array('unmoderated[]'), 
                $user['email'],
                $wlc->isUnmoderated($user['email'])
            ) .
            ' ' . $user['name'] . '</td>' .
            '<td class="nowrap">' . $user['email'] . '</td>' .
            '</tr>';
        }

        $res .=
        '</tbody>' .
        '</table>' .
        '</div>' .
        '</div>' .
        '<p><input type="submit" name="update_unmoderated" value="' . __('Save') . '" />' .
        $this->core->formNonce() . '</p>' .
        '</form>';

        return $res;
    }
}

/**
 * @ingroup DC_PLUGIN_WHITELISTCOM
 * @brief Filter for reserved names.
 * @since 2.6
 */
class whiteListComReservedFilter extends dcSpamFilter
{
    public $name = 'Reserved names';
    public $has_gui = true;

    protected function setInfo()
    {
        $this->name = __('Reserved names');
        $this->description = __('Whitelist of reserved names of users');
    }

    public function isSpam($type, $author, $email, $site, $ip, $content, $post_id, &$status)
    {
        if ($type != 'comment') {
            return null;
        }

        $throw = false;
        try {
            $wlc = new whiteListCom($this->core);

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
        if($throw) {
            throw new Exception(__('This name is reserved to an other user.'));
        }
    }

    public function getStatusMessage($status, $comment_id)
    {
        return __('This name is reserved to an other user.');
    }

    public function gui($url)
    {
        try {
            $wlc = new whiteListCom($this->core);

            if (!empty($_POST['update_reserved'])) {
                $wlc->emptyReserved();
                foreach($_POST['reserved'] as $email => $name) {
                    $wlc->addReserved($name, $email);
                }
                $wlc->commit();
            }
            $comments = $wlc->getCommentsUsers();
        } catch (Exception $e) {
            $this->core->error->add($e->getMessage());
        }

        $res =
        '<form action="' . html::escapeURL($url) . '" method="post">' .
        '<p>' . __('Check the users who can make comments without being moderated.') . '</p>' .
        '<p>' . __('Comments authors list') . '</p>' .
        '<table class="clear">' .
        '<thead><tr><th>' . __('Author') . '</th><th>' . __('Email') . '</th></tr></thead>' .
        '<tbody>';

        foreach($comments as $user) {
            $res .=
            '<tr class="line">' .
            '<td class="nowrap">' .
            form::checkbox(
                array('reserved[' . $user['email'] . ']'), 
                $user['name'],
                (null === $wlc->isReserved($user['name'], $user['email']))
            ) .
            ' ' . $user['name'] . '</td>' .
            '<td class="nowrap">' . $user['email'] . '</td>' .
            '</tr>';
        }

        $res .=
        '</tbody>' .
        '</table>' .
        '<p><input type="submit" name="update_reserved" value="' . __('Save') . '" />' .
        $this->core->formNonce() . '</p>' .
        '</form>';

        return $res;
    }
}

/**
 * @ingroup DC_PLUGIN_WHITELISTCOM
 * @brief White list filters methods
 * @since 2.6
 */
class whiteListCom
{
    public $core;
    public $con;
    public $blog;
    public $settings;

    private $unmoderated = array();
    private $reserved = array();

    public function __construct($core)
    {
        $this->core = $core;
        $this->con = $core->con;
        $this->blog = $core->con->escape($core->blog->id);

        $core->blog->settings->addNamespace('whiteListCom');
        $this->settings = $core->blog->settings->whiteListCom;

        $unmoderated = $this->settings->whiteListCom_unmoderated;
        $this->unmoderated = self::decode($unmoderated);

        $reserved = $this->settings->whiteListCom_reserved;
        $this->reserved = self::decode($reserved);
    }

    public function commit()
    {
        $this->settings->put(
            'whiteListCom_unmoderated',
            self::encode($this->unmoderated),
            'string',
            'Whitelist of unmoderated users on comments',
            true,false
        );

        $this->settings->put(
            'whiteListCom_reserved',
            self::encode($this->reserved),
            'string',
            'Whitelist of reserved names on comments',
            true,false
        );
    }

    # Return 
    # true if it is a reserved name with wrong email
    # false if it is not a reserved name
    # null if it is a reserved name with right email
    public function isReserved($author, $email)
    {
        if (!isset($this->reserved[$author])) {
            return false;
        } elseif ($this->reserved[$author] != $email) {
            return true;
        } else {
            return null;
        }
    }

    # You must do a commit to save this change
    public function addReserved($author, $email)
    {
        $this->reserved[$author] = $email;
        return true;
    }

    # You must do a commit to save this change
    public function emptyReserved()
    {
        $this->reserved = array();
    }

    # Return 
    # true if it is known as an unmoderated email else false
    public function isUnmoderated($email)
    {
        return in_array($email, $this->unmoderated);
    }

    # You must do a commit to save this change
    public function addUnmoderated($email)
    {
        if (!in_array($email,$this->unmoderated)) {
            $this->unmoderated[] = $email;

            return true;
        }

        return null;
    }

    # You must do a commit to save this change
    public function emptyUnmoderated()
    {
        $this->unmoderated = array();
    }

    public function getPostsUsers()
    {
        $users = array();
        $rs = $this->core->blog->getPostsUsers();
        while($rs->fetch()) {
            $name = dcUtils::getUserCN(
                $rs->user_id,
                $rs->user_name,
                $rs->user_firstname,
                $rs->user_displayname
            );
            $users[] = array(
                'name' => $name,
                'email' => $rs->user_email
            );
        }

        return $users;
    }

    public function getCommentsUsers()
    {
        $users = array();
        $rs = $this->con->select(
            'SELECT comment_author, comment_email ' .
            'FROM ' . $this->core->prefix . 'comment C ' .
            'LEFT JOIN ' . $this->core->prefix . 'post P ON C.post_id=P.post_id ' .
            "WHERE blog_id='" . $this->blog . "' AND comment_trackback=0 " .
            'GROUP BY comment_email, comment_author ' // Added author to fix postgreSql
        );
        while($rs->fetch()) {
            $users[] = array(
                'name' => $rs->comment_author,
                'email' => $rs->comment_email
            );
        }

        return $users;
    }

    public static function encode($x)
    {
        $y = is_array($x) ? $x : array();

        return base64_encode(serialize($y));
    }

    public static function decode($x)
    {
        $y = @unserialize(@base64_decode($x));

        return is_array($y) ? $y : array();
    }
}

/**
 * @ingroup DC_PLUGIN_WHITELISTCOM
 * @brief White list behaviors
 * @since 2.6
 */
class whiteListComBehaviors
{
    # from behavior publicAfterCommentCreate
    public static function switchStatus($cur, $id)
    {
        global $core;

        if ($core->blog === null 
         || $core->blog->settings->system->comments_pub) {
            return null;
        }

        if ($cur->comment_spam_filter == 'whiteListComModeratedFilter'
         && $cur->comment_spam_status == 'unmoderated') {
            $core->con->writeLock($core->prefix.'comment');

            $cur->comment_status = 1;
            $cur->comment_spam_status = 0;
            $cur->comment_spam_filter = 0;
            $cur->update('WHERE comment_id = ' . $id . ' ');

            $core->con->unlock();

            $core->blog->triggerComment($id);
            $core->blog->triggerBlog();
        }
    }
}