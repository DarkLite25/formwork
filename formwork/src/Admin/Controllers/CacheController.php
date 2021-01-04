<?php

namespace Formwork\Admin\Controllers;

use Formwork\Formwork;
use Formwork\Response\JSONResponse;

class CacheController extends AbstractController
{
    /**
     * Cache@clear action
     */
    public function clear(): void
    {
        $this->ensurePermission('cache.clear');
        if (Formwork::instance()->config()->get('cache.enabled')) {
            Formwork::instance()->cache()->clear();
        }
        JSONResponse::success($this->admin()->translate('admin.cache.cleared'))->send();
    }
}
