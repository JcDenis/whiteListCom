<?php

declare(strict_types=1);

namespace Dotclear\Plugin\whiteListCom;

use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Database\Cursor;

/**
 * @brief       whiteListCom module backend process.
 * @ingroup     whiteListCom
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class Backend extends Process
{
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
                        $cur->user_id,
                        $cur->user_name,
                        $cur->user_firstname,
                        $cur->user_displayname
                    ),
                    $cur->user_email
                );
                Utils::commit();
            },
        ]);

        return true;
    }
}
