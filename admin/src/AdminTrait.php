<?php

namespace Formwork\Admin;

use Formwork\Admin\Utils\Language;
use Formwork\Admin\Utils\Log;
use Formwork\Admin\Utils\Notification;
use Formwork\Admin\Utils\Registry;
use Formwork\Core\Formwork;
use Formwork\Utils\Header;
use Formwork\Utils\HTTPRequest;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Uri;

trait AdminTrait
{
    public function uri($subpath)
    {
        return HTTPRequest::root() . ltrim($subpath, '/');
    }

    public function siteUri()
    {
        return rtrim(FileSystem::dirname(HTTPRequest::root()), '/') . '/';
    }

    public function pageUri($page)
    {
        return $this->siteUri() . ltrim($page->slug(), '/');
    }

    public function redirect($uri, $code = 302, $exit = false)
    {
        Header::redirect($this->uri($uri), $code, $exit);
    }

    public function redirectToSite($code = 302, $exit = false)
    {
        Header::redirect($this->siteUri(), $code, $exit);
    }

    public function redirectToReferer($code = 302, $exit = false, $default = null)
    {
        Header::redirect(HTTPRequest::referer() ?: $this->uri($default ?: '/'), $code, $exit);
    }

    public function registry($name)
    {
        return new Registry(LOGS_PATH . $name . '.json');
    }

    public function log($name)
    {
        return new Log(LOGS_PATH . $name . '.json');
    }

    public function notification()
    {
        return Notification::exists() ? Notification::get() : null;
    }

    public function notify($text, $type)
    {
        Notification::send($text, $type);
    }

    public function language()
    {
        return Formwork::instance()->option('admin.lang');
    }

    protected function label($key)
    {
        return call_user_func_array(array(Language::class, 'get'), func_get_args());
    }
}