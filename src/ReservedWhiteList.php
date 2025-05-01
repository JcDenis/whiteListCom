<?php

declare(strict_types=1);

namespace Dotclear\Plugin\whiteListCom;

use Dotclear\App;
use Dotclear\Core\Backend\Notices;
use Dotclear\Helper\Html\Form\{ Caption, Checkbox, Div, Form, Hidden, Label, Para, Submit, Table, Tbody, Td, Text, Th, Thead, Tr };
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Plugin\antispam\SpamFilter;
use Exception;

/**
 * @brief   whiteListCom resserved name antispam filter.
 * @ingroup whiteListCom
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class ReservedWhiteList extends SpamFilter
{
    public string $name  = 'Reserved names';
    public bool $has_gui = true;

    protected function setInfo(): void
    {
        $this->name        = __('Reserved names');
        $this->description = __('Whitelist of reserved names of users');
    }

    /**
     * @return  null|true
     */
    public function isSpam(string $type, ?string $author, ?string $email, ?string $site, ?string $ip, ?string $content, ?int $post_id, string &$status)
    {
        if ($type == 'comment' 
            && true === Utils::isReserved((string) $author, (string) $email)
        ) {
            $status = $this->name;

            if (Utils::stopReserved()) {
                # This message is show to author even if comments are moderated, comment is not saved
                throw new Exception(__('This name is reserved to an other user.'));
            }

            # Mark as spam and stop filters
            return true;
        }

        # Go through others filters
        return null;
    }

    public function gui(string $url): string
    {
        $comments = [];

        try {
            if (!empty($_POST['update_reserved'])) {
                Utils::emptyReserved();
                foreach ($_POST['reserved'] as $i => $name) {
                    Utils::addReserved($name, $_POST['reserved_email'][$i]);
                }
                Utils::setStopReserved(!empty($_POST['reserved_stop']));
                Utils::commit();
                Notices::addSuccessNotice(__('Reserved names have been successfully updated.'));
                Http::redirect($url);
            }

            $comments = Utils::getCommentsUsers();
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }

        $rows = [];
        foreach ($comments as $user) {
            $checked = null === Utils::isReserved($user['name'], $user['email']);

            $rows[] = (new Tr())
                ->class('line')
                ->cols([
                    (new Td())
                        ->class('nowrap')
                        ->items([
                            (new Checkbox(['reserved[' . (count($rows) + 1) . ']'], $checked))
                                ->value($user['name'])
                                ->label(new label($user['name'], Label::IL_FT)),
                            new Hidden(['reserved_email[' . (count($rows) + 1) . ']'], $user['email']),
                        ]),
                    (new Td())
                        ->class(['maximal','nowrap'])
                        ->text($user['email'])
                ]);
        }

        return (new Form('update_reserved_form'))
            ->method('post')
            ->action($url)
            ->fields([
                new Text('p', __('Check the users who have a reserved name (link to an email).')),
                (new Div())
                    ->class('table-outer')
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
                            ->tbody((new Tbody())->rows($rows)),
                    ]),
                (new Para())
                    ->items([
                        (new Checkbox('reserved_stop', Utils::stopReserved()))
                            ->value(1)
                            ->label(new label(__('Stop comment submission instead of mark it as spam'), Label::IL_FT)),
                    ]),
                (new Para())
                    ->items([
                        App::nonce()->formNonce(),
                        new Submit('update_reserved', __('Save')),
                    ]),
            ])
            ->render();
    }
}
