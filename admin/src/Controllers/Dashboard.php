<?php

namespace Formwork\Admin\Controllers;

use Formwork\Admin\Statistics;

class Dashboard extends AbstractController
{
    /**
     * Dashboard@index action
     */
    public function index(): void
    {
        $this->ensurePermission('dashboard');

        $statistics = new Statistics();

        $this->modal('newPage', [
            'templates' => $this->site()->templates(),
            'pages'     => $this->site()->descendants()->sort('path')
        ]);

        $this->modal('deletePage');

        $this->view('admin', [
            'title'   => $this->label('dashboard.dashboard'),
            'content' => $this->view('dashboard.index', [
                'lastModifiedPages' => $this->view('pages.list', [
                    'pages'    => $this->site()->descendants()->sort('lastModifiedTime', SORT_DESC)->slice(0, 5),
                    'subpages' => false,
                    'class'    => 'pages-list-top',
                    'parent'   => null,
                    'sortable' => false,
                    'headers'  => true
                    ], true),
                'statistics' => $statistics->getChartData()
            ], true)
        ]);
    }
}
