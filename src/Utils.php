<?php

declare(strict_types=1);

namespace Dotclear\Plugin\whiteListCom;

use Dotclear\App;
use Dotclear\Database\Statement\{
    JoinStatement,
    SelectStatement,
};

/**
 * @brief   whiteListCom utils.
 * @ingroup whiteListCom
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Utils
{
    /**
     * Preload check.
     *
     *  @var    bool    $init
     */
    private static bool $init = false;

    /**
     * List of unmoderated users.
     *
     * @var     array<int|string,string>   $unmoderated
     */
    private static array $unmoderated = [];

    /**
     * List of reserved name.
     *
     * @var     array<int|string,string>    $reserved
     */
    private static array $reserved = [];

    /**
     * Stop submission on reserved name.
     *
     * @var     bool    $stopreserved
     */
    private static bool $stopreserved = false;

    /**
     * Initialize properties.
     */
    private static function init(): void
    {
        if (self::$init) {
            return;
        }

        self::$unmoderated  = self::decode(My::settings()->get('unmoderated'));
        self::$reserved     = self::decode(My::settings()->get('reserved'));
        self::$stopreserved = (bool) My::settings()->get('stopreserved');

        self::$init = true;
    }

    /**
     * Save changes.
     */
    public static function commit(): void
    {
        self::init();

        My::settings()->put(
            'unmoderated',
            self::encode(self::$unmoderated),
            'string',
            'Whitelist of unmoderated users on comments',
            true,
            false
        );

        My::settings()->put(
            'reserved',
            self::encode(self::$reserved),
            'string',
            'Whitelist of reserved names on comments',
            true,
            false
        );

        My::settings()->put(
            'stopreserved',
            self::$stopreserved,
            'boolean',
            'Stop submission rather than mark as spam',
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
     * Check if submission stop on reserved name.
     */
    public static function stopReserved(): bool
    {
        self::init();

        return self::$stopreserved;
    }

    /**
     * Set submission stop on reserved name.
     *
     * You must do a Utils::commit() to save this change
     */
    public static function setStopReserved(bool $stop): void
    {
        self::init();

        self::$stopreserved = $stop;
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
     * @return   array<int, array<string,string>>   The users name/email pairs
     */
    public static function getPostsUsers(): array
    {
        if (!App::blog()->isDefined()) {
            return [];
        }

        $rs = App::blog()->getPostsUsers();
        if ($rs->isEmpty()) {
            return [];
        }

        $users = [];
        while ($rs->fetch()) {
            $name = App::users()->getUserCN(
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
     * @return   array<int, array<string,string>>   The users name/email pairs
     */
    public static function getCommentsUsers(): array
    {
        if (!App::blog()->isDefined()) {
            return [];
        }

        $sql = new SelectStatement();
        $rs  = $sql->from($sql->as(App::con()->prefix() . App::blog()::COMMENT_TABLE_NAME, 'C'))
            ->columns([
                'comment_author',
                'comment_email',
            ])
            ->join(
                (new JoinStatement())
                    ->left()
                    ->from($sql->as(App::con()->prefix() . App::blog()::POST_TABLE_NAME, 'P'))
                    ->on('C.post_id = P.post_id')
                    ->statement()
            )
            ->where('blog_id = ' . $sql->quote(App::blog()->id()))
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
     * @param   array<int|string,string>|string    $x  The value to encode
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
     * @return  array<int|string,string>    The decoded value
     */
    public static function decode($x): array
    {
        $y = json_decode($x, true);

        return is_array($y) ? $y : [];
    }
}
