<?php

declare(strict_types=1);

namespace Dotclear\Plugin\whiteListCom;

use Dotclear\App;
use Dotclear\Core\Backend\Notices;
use Dotclear\Helper\Html\Form\Caption;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Email;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Table;
use Dotclear\Helper\Html\Form\Tbody;
use Dotclear\Helper\Html\Form\Td;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Th;
use Dotclear\Helper\Html\Form\Thead;
use Dotclear\Helper\Html\Form\Tr;
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
    public function isSpam(string $type, ?string $author, ?string $email, ?string $site, ?string $ip, ?string $content, ?int $post_id, string &$status): ?bool
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
        try {
            if (!empty($_POST['update_reserved'])) {
                Utils::emptyReserved();
                $names  = is_array($_POST['reserved'] ?? null) ? $_POST['reserved'] : [];
                $emails = is_array($_POST['reserved_email'] ?? null) ? $_POST['reserved_email'] : [];
                foreach ($names as $i => $name) {
                    if (is_string($name) && is_string($emails[$i] ?? null)) {
                        Utils::addReserved($name, $emails[$i]);
                    }
                }
                // New reserved name
                $new_name  = is_string($_POST['reserved_new_name'] ?? null) ? trim($_POST['reserved_new_name']) : '';
                $new_email = is_string($_POST['reserved_new_email'] ?? null) ? trim($_POST['reserved_new_email']) : '';
                if ($new_name !== '' || $new_email !== '') {
                    if ($new_name === '' || !filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
                        throw new Exception(__('A new reserved name needs a name and a valid email.'));
                    }
                    Utils::addReserved($new_name, $new_email);
                }
                Utils::setStopReserved(!empty($_POST['reserved_stop']));
                Utils::commit();
                Notices::addSuccessNotice(__('Reserved names have been successfully updated.'));
                Http::redirect($url);
            }
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }

        // Reserved names, then entries writers, then comments authors (each name once)
        $users = [];
        foreach (Utils::getReserved() as $name => $email) {
            $users[Utils::nameKey((string) $name)] = ['name' => (string) $name, 'email' => $email];
        }
        try {
            foreach ([...Utils::getPostsUsers(), ...Utils::getCommentsUsers()] as $user) {
                $key = Utils::nameKey($user['name']);
                if ($key !== '' && $user['email'] !== '' && !isset($users[$key])) {
                    $users[$key] = $user;
                }
            }
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }

        $rows = [];
        foreach ($users as $user) {
            $checked = null === Utils::isReserved($user['name'], $user['email']);
            $i       = count($rows) + 1;

            $rows[] = (new Tr())
                ->class('line')
                ->cols([
                    (new Td())
                        ->class('nowrap')
                        ->items([
                            (new Checkbox(['reserved[' . $i . ']', 'reserved_' . $i], $checked))
                                ->value($user['name'])
                                ->label(new label(Html::escapeHTML($user['name']), Label::IL_FT)),
                            new Hidden(['reserved_email[' . $i . ']'], $user['email']),
                        ]),
                    (new Td())
                        ->class(['maximal','nowrap'])
                        ->text(Html::escapeHTML($user['email']))
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
                            ->caption(new Caption(__('Reserved names, entries and comments authors') ))
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
                        (new Input('reserved_new_name'))
                            ->size(30)
                            ->maxlength(255)
                            ->label(new Label(__('Reserve a new name:'), Label::OL_TF)),
                    ]),
                (new Para())
                    ->items([
                        (new Email('reserved_new_email'))
                            ->size(30)
                            ->maxlength(255)
                            ->label(new Label(__('With email:'), Label::OL_TF)),
                    ]),
                (new Note())
                    ->class('form-note')
                    ->text(__('Names are compared whatever the case and the spaces.')),
                (new Para())
                    ->items([
                        (new Checkbox('reserved_stop', Utils::stopReserved()))
                            ->value(1)
                            ->label(new label(__('Stop comment submission instead of mark it as spam'), Label::IL_FT)),
                    ]),
                (new Note())
                    ->class('form-note')
                    ->text(__('If checked (default), the visitor is told that the name is reserved and the comment is not saved. Otherwise the comment is saved as spam and the visitor is told, as for any spam, that it will be published after validation.')),
                (new Para())
                    ->items([
                        App::nonce()->formNonce(),
                        new Submit('update_reserved', __('Save')),
                    ]),
            ])
            ->render();
    }
}
