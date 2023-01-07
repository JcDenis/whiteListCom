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
use dcBlog;
use dcCore;
use dcUtils;

/**
 * @ingroup DC_PLUGIN_WHITELISTCOM
 * @brief White list filters methods
 * @since 2.6
 */
class Core
{
    public $con;
    public $blog;
    public $settings;

    private $unmoderated = [];
    private $reserved    = [];

    public function __construct()
    {
        $this->con         = dcCore::app()->con;
        $this->blog        = dcCore::app()->con->escape(dcCore::app()->blog->id);
        $this->settings    = dcCore::app()->blog->settings->get(basename(dirname(__DIR__)));
        $unmoderated       = $this->settings->get('unmoderated');
        $this->unmoderated = self::decode($unmoderated);
        $reserved          = $this->settings->get('reserved');
        $this->reserved    = self::decode($reserved);
    }

    public function commit()
    {
        $this->settings->put(
            'unmoderated',
            self::encode($this->unmoderated),
            'string',
            'Whitelist of unmoderated users on comments',
            true,
            false
        );

        $this->settings->put(
            'reserved',
            self::encode($this->reserved),
            'string',
            'Whitelist of reserved names on comments',
            true,
            false
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
        }

        return null;
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
        $this->reserved = [];
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
        if (!in_array($email, $this->unmoderated)) {
            $this->unmoderated[] = $email;

            return true;
        }

        return null;
    }

    # You must do a commit to save this change
    public function emptyUnmoderated()
    {
        $this->unmoderated = [];
    }

    public function getPostsUsers()
    {
        $users = [];
        $rs    = dcCore::app()->blog->getPostsUsers();
        while ($rs->fetch()) {
            $name = dcUtils::getUserCN(
                $rs->__get('user_id'),
                $rs->__get('user_name'),
                $rs->__get('user_firstname'),
                $rs->__get('user_displayname')
            );
            $users[] = [
                'name'  => $name,
                'email' => $rs->__get('user_email'),
            ];
        }

        return $users;
    }

    public function getCommentsUsers()
    {
        $users = [];
        $rs    = $this->con->select(
            'SELECT comment_author, comment_email ' .
            'FROM ' . dcCore::app()->prefix . dcBlog::COMMENT_TABLE_NAME . ' C ' .
            'LEFT JOIN ' . dcCore::app()->prefix . 'post P ON C.post_id=P.post_id ' .
            "WHERE blog_id='" . $this->blog . "' AND comment_trackback=0 " .
            'GROUP BY comment_email, comment_author ' // Added author to fix postgreSql
        );
        while ($rs->fetch()) {
            $users[] = [
                'name'  => $rs->__get('comment_author'),
                'email' => $rs->__get('comment_email'),
            ];
        }

        return $users;
    }

    public static function encode($x)
    {
        $y = is_array($x) ? $x : [];

        return json_encode($y);
    }

    public static function decode($x)
    {
        $y = json_decode($x, true);

        return is_array($y) ? $y : [];
    }
}
