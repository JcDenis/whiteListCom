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

use dcBlog;
use dcCore;
use dcUtils;
use Dotclear\Database\Statement\{
    JoinStatement,
    SelectStatement,
};

/**
 * @ingroup DC_PLUGIN_WHITELISTCOM
 * @brief White list filters methods
 * @since 2.6
 */
class Utils
{
    /** @var    bool    $init   preload check */
    private static bool $init = false;

    /** @var    array   $unmoderated    List of unmoderated users */
    private static array $unmoderated = [];

    /** @var    array   $unmoderated    List of reserved name */
    private static array $reserved = [];

    /**
     * Initialize properties.
     */
    private static function init(): void
    {
        if (self::$init) {
            return;
        }

        if (is_null(dcCore::app()->blog)) {
            return;
        }

        $s = dcCore::app()->blog->settings->get(My::id());

        self::$unmoderated = self::decode($s->get('unmoderated'));
        self::$reserved    = self::decode($s->get('reserved'));

        self::$init = true;
    }

    /**
     * Save changes.
     */
    public static function commit(): void
    {
        if (is_null(dcCore::app()->blog)) {
            return;
        }

        self::init();

        $s = dcCore::app()->blog->settings->get(My::id());

        $s->put(
            'unmoderated',
            self::encode(self::$unmoderated),
            'string',
            'Whitelist of unmoderated users on comments',
            true,
            false
        );

        $s->put(
            'reserved',
            self::encode(self::$reserved),
            'string',
            'Whitelist of reserved names on comments',
            true,
            false
        );
    }

    /**
     * Check if a name is reserved.
     *
     * Return:
     * - true if it is a reserved name with wrong email
     * - false if it is not a reserved name
     * - null if it is a reserved name with right email
     *
     * @param   string  $author The author
     * @param   string  $email  The email
     *
     * @return  null|bool   The reserved state
     */
    public static function isReserved(string $author, string $email): ?bool
    {
        self::init();

        if (!isset(self::$reserved[$author])) {
            return false;
        } elseif (self::$reserved[$author] != $email) {
            return true;
        }

        return null;
    }

    /**
     * Add a reserved user.
     *
     * You must do a Utils::commit() to save this change
     *
     * @param   string  $author     The author
     * @param   string  $email      The email
     */
    public static function addReserved(string $author, string $email): void
    {
        self::init();

        self::$reserved[$author] = $email;
    }

    /**
     * Clean reserved names list.
     *
     * You must do a Utils::commit() to save this change
     */
    public static function emptyReserved(): void
    {
        self::init();

        self::$reserved = [];
    }

    /**
     * Check if an email is unmoderated.
     *
     * Return:
     * - true if it is known as an unmoderated email
     * - false else
     *
     * @param   string  $email  The email
     *
     * @return  bool    The reserved state
     */
    public static function isUnmoderated(string $email): bool
    {
        self::init();

        return in_array($email, self::$unmoderated);
    }

    /**
     * Add a unmoderated user.
     *
     * You must do a Utils::commit() to save this change
     *
     * @param   string  $email      The email
     */
    public static function addUnmoderated(string $email): void
    {
        self::init();

        if (!in_array($email, self::$unmoderated)) {
            self::$unmoderated[] = $email;
        }
    }

    /**
     * Clean unmoderated users list.
     *
     * You must do a Utils::commit() to save this change
     */
    public static function emptyUnmoderated(): void
    {
        self::init();

        self::$unmoderated = [];
    }

    /**
     * Get posts users.
     *
     * @return   array   The users name/email pairs
     */
    public static function getPostsUsers(): array
    {
        if (is_null(dcCore::app()->blog)) {
            return [];
        }

        $rs = dcCore::app()->blog->getPostsUsers();
        if ($rs->isEmpty()) {
            return [];
        }

        $users = [];
        while ($rs->fetch()) {
            $name = dcUtils::getUserCN(
                $rs->f('user_id'),
                $rs->f('user_name'),
                $rs->f('user_firstname'),
                $rs->f('user_displayname')
            );
            $users[] = [
                'name'  => $name,
                'email' => $rs->f('user_email'),
            ];
        }

        return $users;
    }

    /**
     * Get comments users.
     *
     * @return   array   The users name/email pairs
     */
    public static function getCommentsUsers(): array
    {
        if (is_null(dcCore::app()->blog)) {
            return [];
        }

        $sql = new SelectStatement();
        $rs  = $sql->from($sql->as(dcCore::app()->prefix . dcBlog::COMMENT_TABLE_NAME, 'C'))
            ->columns([
                'comment_author',
                'comment_email',
            ])
            ->join(
                (new JoinStatement())
                    ->left()
                    ->from($sql->as(dcCore::app()->prefix . dcBlog::POST_TABLE_NAME, 'P'))
                    ->on('C.post_id = P.post_id')
                    ->statement()
            )
            ->where('blog_id = ' . $sql->quote(dcCore::app()->blog->id))
            ->and('comment_trackback = 0')
            ->and("comment_email != ''")
            ->group('comment_email, comment_author') // Added author to fix postgreSql
            ->select();

        if (is_null($rs) || $rs->isEmpty()) {
            return [];
        }

        $users = [];
        while ($rs->fetch()) {
            $users[] = [
                'name'  => $rs->f('comment_author'),
                'email' => $rs->f('comment_email'),
            ];
        }

        return $users;
    }

    /**
     * Encode settings.
     *
     * @param   array|string    $x  The value to encode
     *
     * @return  string  The encoded value
     */
    public static function encode($x): string
    {
        $y = is_array($x) ? $x : [];

        return (string) json_encode($y);
    }

    /**
     * Decode settings.
     *
     * @param   string  $x  The value to decode
     *
     * @return  array  The decoded value
     */
    public static function decode($x): array
    {
        $y = json_decode($x, true);

        return is_array($y) ? $y : [];
    }
}
