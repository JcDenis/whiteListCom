<?php

declare(strict_types=1);

namespace Dotclear\Plugin\whiteListCom;

use Dotclear\App;
use Dotclear\Database\Statement\JoinStatement;
use Dotclear\Database\Statement\SelectStatement;

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

        self::$unmoderated  = self::decode(My::settings()->getStr('unmoderated', false));
        self::$reserved     = self::decode(My::settings()->getStr('reserved', false));
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
    /**
     * Check if a name is reserved.
     *
     * Names are compared whatever the case and the spaces, emails whatever the case.
     *
     * @return  null|bool   null if the name is reserved to this email, true if reserved to another email, false if not reserved
     */
    public static function isReserved(string $author, string $email): ?bool
    {
        self::init();

        $key = self::nameKey($author);
        foreach (self::$reserved as $name => $reserved_email) {
            if (self::nameKey((string) $name) === $key) {
                return strcasecmp($reserved_email, trim($email)) === 0 ? null : true;
            }
        }

        return false;
    }

    /**
     * Check if an email is linked to a reserved name, but with another name.
     */
    public static function isReservedEmailWithOtherName(string $author, string $email): bool
    {
        self::init();

        $key = self::nameKey($author);
        foreach (self::$reserved as $name => $reserved_email) {
            if (strcasecmp($reserved_email, trim($email)) === 0 && self::nameKey((string) $name) !== $key) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get reserved names.
     *
     * @return  array<int|string, string>   The names/emails
     */
    public static function getReserved(): array
    {
        self::init();

        return self::$reserved;
    }

    /**
     * Normalize a name to compare it.
     */
    public static function nameKey(string $name): string
    {
        return mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $name)));
    }

    public static function addReserved(string $author, string $email): void
    {
        self::init();

        $author = trim((string) preg_replace('/\s+/u', ' ', $author));
        $email  = trim($email);
        if ($author !== '' && $email !== '') {
            self::$reserved[$author] = $email;
        }
    }

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

        $email = trim($email);
        foreach (self::$unmoderated as $unmoderated) {
            if (strcasecmp($unmoderated, $email) === 0) {
                return $email !== '';
            }
        }

        return false;
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

        $email = trim($email);
        if ($email !== '' && !self::isUnmoderated($email)) {
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
                $rs->strField('user_id'),
                $rs->strField('user_name'),
                $rs->strField('user_firstname'),
                $rs->strField('user_displayname')
            );
            $users[] = [
                'name'  => $name,
                'email' => $rs->strField('user_email'),
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
        $rs  = $sql->from($sql->as(App::db()->con()->prefix() . App::blog()::COMMENT_TABLE_NAME, 'C'))
            ->columns([
                'comment_author',
                'comment_email',
            ])
            ->join(
                (new JoinStatement())
                    ->left()
                    ->from($sql->as(App::db()->con()->prefix() . App::blog()::POST_TABLE_NAME, 'P'))
                    ->on('C.post_id = P.post_id')
                    ->statement()
            )
            ->where('blog_id = ' . $sql->quote(App::blog()->id()))
            ->and('comment_trackback = 0')
            // Refused or junk comments (like a usurper) are not listed
            ->and('comment_status <> ' . App::status()->comment()::JUNK)
            ->and("comment_email != ''")
            ->group('comment_email, comment_author') // Added author to fix postgreSql
            ->select();

        if (is_null($rs) || $rs->isEmpty()) {
            return [];
        }

        $users = [];
        while ($rs->fetch()) {
            $users[] = [
                'name'  => $rs->strField('comment_author'),
                'email' => $rs->strField('comment_email'),
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

        return is_array($y) ? array_filter($y, is_string(...)) : [];
    }
}
