<?php

namespace Formwork\Admin\Controllers;

use Formwork\Admin\Admin;
use Formwork\Admin\Security\CSRFToken;
use Formwork\Admin\Users\User;
use Formwork\Controllers\AbstractController as BaseAbstractController;
use Formwork\Formwork;
use Formwork\Parsers\JSON;
use Formwork\Parsers\PHP;
use Formwork\Site;
use Formwork\Utils\Date;
use Formwork\Utils\HTTPRequest;
use Formwork\View\View;

abstract class AbstractController extends BaseAbstractController
{
    /**
     * All loaded modals
     */
    protected array $modals = [];

    /**
     * Return admin instance
     */
    protected function admin(): Admin
    {
        return Formwork::instance()->admin();
    }

    /**
     * Return site instance
     */
    protected function site(): Site
    {
        return Formwork::instance()->site();
    }

    /*
     * Return default data passed to views
     *
     */
    protected function defaults(): array
    {
        return [
            'location'    => $this->name,
            'admin'       => $this->admin(),
            'csrfToken'   => CSRFToken::get(),
            'modals'      => implode('', $this->modals),
            'colorScheme' => $this->getColorScheme(),
            'appConfig'   => JSON::encode([
                'baseUri'   => $this->admin()->panelUri(),
                'DateInput' => [
                    'weekStarts' => Formwork::instance()->config()->get('date.week_starts'),
                    'format'     => Date::formatToPattern(Formwork::instance()->config()->get('date.format') . ' ' . Formwork::instance()->config()->get('date.time_format')),
                    'time'       => true,
                    'labels'     => [
                        'today'    => $this->admin()->translate('date.today'),
                        'weekdays' => ['long' => $this->admin()->translate('date.weekdays.long'), 'short' =>  $this->admin()->translate('date.weekdays.short')],
                        'months'   => ['long' => $this->admin()->translate('date.months.long'), 'short' =>  $this->admin()->translate('date.months.short')]
                    ]
                ],
                'DurationInput' => [
                    'labels' => [
                        'years'   => $this->admin()->translate('date.duration.years'),
                        'months'  => $this->admin()->translate('date.duration.months'),
                        'weeks'   => $this->admin()->translate('date.duration.weeks'),
                        'days'    => $this->admin()->translate('date.duration.days'),
                        'hours'   => $this->admin()->translate('date.duration.hours'),
                        'minutes' => $this->admin()->translate('date.duration.minutes'),
                        'seconds' => $this->admin()->translate('date.duration.seconds')
                    ]
                ]
            ])
        ];
    }

    /**
     * Get logged user
     */
    protected function user(): User
    {
        return $this->admin()->user();
    }

    /**
     * Ensure current user has a permission
     */
    protected function ensurePermission(string $permission): void
    {
        if (!$this->user()->permissions()->has($permission)) {
            $errors = new ErrorsController();
            $errors->forbidden()->send();
            exit;
        }
    }

    /**
     * Load a modal to be rendered later
     *
     * @param string $name Name of the modal
     * @param array  $data Data to pass to the modal
     */
    protected function modal(string $name, array $data = []): void
    {
        $this->modals[] = $this->view('modals.' . $name, $data, true);
    }

    /**
     * Render a view
     *
     * @param string $name   Name of the view
     * @param array  $data   Data to pass to the view
     * @param bool   $return Whether to return or render the view
     *
     * @return string|void
     */
    protected function view(string $name, array $data = [], bool $return = false)
    {
        $view = new View(
            $name,
            array_merge($this->defaults(), $data),
            Formwork::instance()->config()->get('views.paths.admin'),
            PHP::parseFile(ADMIN_PATH . 'helpers.php')
        );
        return $view->render($return);
    }

    /**
     * Get color scheme
     */
    private function getColorScheme(): string
    {
        $default = Formwork::instance()->config()->get('admin.color_scheme');
        if ($this->admin()->isLoggedIn()) {
            if ($this->user()->colorScheme() === 'auto') {
                return HTTPRequest::cookies()->get('formwork_preferred_color_scheme', $default);
            }
            return $this->user()->colorScheme();
        }
        return $default;
    }
}
