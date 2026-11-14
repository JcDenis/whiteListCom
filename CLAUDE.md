# whiteListCom

Notes for AI assistants working on the Dotclear plugin whiteListCom.

**Read first** the shared conventions of the author's Dotclear modules:
https://raw.githubusercontent.com/JcDenis/gh2da/master/CONVENTIONS.md
(if a web tool refuses the address, use `curl -s` on it from the shell).
The rules below take precedence for this module.

## Module

Set up a list of users which can publish comments without validation and a list of reserved names (pair of nickname / email ).

No admin page. Nothing on the public side.

Required Dotclear and PHP versions: see `_define.php`.

## Structure

* `src/Backend.php`: admin side: menu entry, favorites, behaviors registration.
* `src/Install.php`: install and update, default settings.
* `src/My.php`: module helpers (`My::id()`, settings, permissions).
* `src/Prepend.php`: loaded on every request: autoload, URL handlers, permission types.
* `src/ReservedWhiteList.php`: reserved name antispam filter.
* `src/Uninstall.php`: cleaners for the plugin Uninstaller.
* `src/UnmoderatedWhiteList.php`: unmoderated antispam filter.
* `src/Utils.php`: helpers.
* Behaviors used: `publicAfterCommentCreate`.

## Pitfalls

* None recorded yet: add here the traps met while working on the module.

## Testing

* CI browses the common admin and public pages; extra pages can be listed in `.github/dotclear-test.txt`.
