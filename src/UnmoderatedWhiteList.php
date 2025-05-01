<?php

declare(strict_types=1);

namespace Dotclear\Plugin\whiteListCom;

use Dotclear\App;
use Dotclear\Core\Backend\Notices;
use Dotclear\Helper\Html\Form\{ Caption, Checkbox, Div, Form, Label, Para, Submit, Table, Tbody, Td, Text, Th, Thead, Tr };
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Plugin\antispam\SpamFilter;
use Exception;

/**
 * @brief   whiteListCom unmoderated antispam filter.
 * @ingroup whiteListCom
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class UnmoderatedWhiteList extends SpamFilter
{
    public string $name  = 'Unmoderated authors';
    public bool $has_gui = true;

    protected function setInfo(): void
    {
        $this->name        = __('Unmoderated authors');
        $this->description = __('Whitelist of unmoderated authors');
    }

    /**
     * @return  null|true
     */
    public function isSpam(string $type, ?string $author, ?string $email, ?string $site, ?string $ip, ?string $content, ?int $post_id, string &$status)
    {
        if ($type == 'comment'
            && Utils::isUnmoderated((string) $email)
        ) {
            $status = $this->name;

            # Mark as spam to change status later and stop filters
            # To check email/author couple use filter RerservedWhiteList before
            return true;
        }

        # Go through others filters
        return null;
    }

    public function gui(string $url): string
    {
        $wlc   = new Utils();
        $posts = $comments = [];

        try {
            if (!empty($_POST['update_unmoderated'])) {
                Utils::emptyUnmoderated();
                foreach ($_POST['unmoderated'] as $email) {
                    Utils::addUnmoderated($email);
                }
                Utils::commit();
                Notices::addSuccessNotice(__('Unmoderated names have been successfully updated.'));
                Http::redirect($url);
            }
            $posts    = Utils::getPostsUsers();
            $comments = Utils::getCommentsUsers();
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }

        $rows_post = $rows_comment = [];
        foreach ($posts as $user) {
            $checked = Utils::isUnmoderated($user['email']);

            $rows_post[] = (new Tr())
                ->class('line' . ($checked ? '' : ' offline'))
                ->cols([
                    (new Td())
                        ->class('nowrap')
                        ->items([
                            (new Checkbox(['unmoderated[]'], $checked))
                                ->value($user['email'])
                                ->label(new label($user['name'], Label::IL_FT)),
                        ]),
                    (new Td())
                        ->class('nowrap')
                        ->text($user['email'])
                ]);
        }
        foreach ($comments as $user) {
            $checked = Utils::isUnmoderated($user['email']);
            $rows_comment[] = (new Tr())
                ->class('line' . ($checked ? '' : ' offline'))
                ->cols([
                    (new Td())
                        ->class('nowrap')
                        ->items([
                            (new Checkbox(['unmoderated[]'], $checked))
                                ->value($user['email'])
                                ->label(new label($user['name'], Label::IL_FT)),
                        ]),
                    (new Td())
                        ->class('nowrap')
                        ->text($user['email'])
                ]);
        }

        return (new Form('update_unmoderated_form'))
            ->method('post')
            ->action($url)
            ->fields([
                (new Text('p',__('Check the users who can make comments without being moderated.'))),
                (new Div())
                    ->class('one-box')
                    ->items([
                        (new Div())
                            ->class(['two-boxes'])
                            ->items([
                                (new Table())
                                    ->caption(new Caption(__('Posts authors list') ))
                                    ->thead((new Thead())
                                        ->rows([(new Tr())
                                            ->cols([
                                                (new Th())->text(__('Author')),
                                                (new Th())->text(__('Email'))
                                            ])
                                        ])
                                    )
                                    ->tbody((new Tbody())->rows($rows_post)),
                            ]),
                        (new Div())
                            ->class(['two-boxes'])
                            ->items([
                                (new Table())
                                    ->caption(new Caption(__('Comments authors list') ))
                                    ->thead((new Thead())
                                        ->rows([(new Tr())
                                            ->cols([
                                                (new Th())->text(__('Author')),
                                                (new Th())->text(__('Email'))
                                            ])
                                        ])
                                    )
                                    ->tbody((new Tbody())->rows($rows_comment)),
                            ]),
                    ]),
                (new Para())
                    ->items([
                        App::nonce()->formNonce(),
                        new Submit('update_unmoderated', __('Save')),
                    ]),
            ])
            ->render();
    }
}
