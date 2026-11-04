<?php

declare(strict_types=1);

namespace Dotclear\Plugin\whiteListCom;

use Dotclear\App;
use Dotclear\Helper\Process\TraitProcess;
use Dotclear\Database\Cursor;

/**
 * @brief       whiteListCom module backend process.
 * @ingroup     whiteListCom
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class Backend
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        App::behavior()->addBehaviors([
            // Add newly registered user (from frontend session) to reserved name
            'FrontendSessionAfterSignup' => function (Cursor $cur)  {
                Utils::addReserved(
                    App::users()->getUserCN(
                        is_string($user_id = $cur->getField('user_id')) ? $user_id : '',
                        is_string($user_name = $cur->getField('user_name')) ? $user_name : null,
                        is_string($user_firstname = $cur->getField('user_firstname')) ? $user_firstname : null,
                        is_string($user_displayname = $cur->getField('user_displayname')) ? $user_displayname : null
                    ),
                    is_string($user_email = $cur->getField('user_email')) ? $user_email : ''
                );
                Utils::commit();
            },
        ]);

        return true;
    }
}
