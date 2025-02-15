<?php

namespace Formwork;

use Formwork\Admin\Admin;
use Formwork\Admin\Statistics;
use Formwork\Cache\FilesCache;
use Formwork\Languages\Languages;
use Formwork\Parsers\PHP;
use Formwork\Parsers\YAML;
use Formwork\Router\Router;
use Formwork\Schemes\Schemes;
use Formwork\Traits\SingletonTrait;
use Formwork\Translations\Translations;
use Formwork\Utils\Header;
use Formwork\Utils\HTTPRequest;
use Formwork\Utils\Str;
use Formwork\Utils\Uri;

final class Formwork
{
    use SingletonTrait;

    /**
     * Current Formwork version
     */
    public const VERSION = '2.0.0-dev';

    /**
     * Formwork config
     */
    protected Config $config;

    /**
     * Current request URI
     */
    protected string $request;

    /**
     * Languages instance
     */
    protected Languages $languages;

    protected Translations $translations;

    protected Schemes $schemes;

    /**
     * Site instance
     */
    protected Site $site;

    /**
     * Router instance
     */
    protected Router $router;

    /**
     * Cache instance
     */
    protected FilesCache $cache;

    /**
     * Admin instance
     */
    protected ?Admin $admin;

    /**
     * Create a new Formwork instance
     */
    public function __construct()
    {
        $this->initializeSingleton();

        $this->request = Uri::removeQuery(HTTPRequest::uri());

        $this->loadConfig();
        $this->loadErrorHandlers();
        $this->loadLanguages();
        $this->loadTranslations();
        $this->loadSchemes();
        $this->loadSite();
        $this->loadCache();
        $this->loadRouter();
        $this->loadAdmin();
        $this->loadRoutes();
    }

    /**
     * Get system options
     */
    public function config(): Config
    {
        return $this->config;
    }

    /**
     * Get current request
     */
    public function request(): string
    {
        return $this->request;
    }

    /**
     * Return site instance
     */
    public function site(): Site
    {
        return $this->site;
    }

    /**
     * Return router instance
     */
    public function router(): Router
    {
        return $this->router;
    }

    /**
     * Return cache instance
     */
    public function cache(): FilesCache
    {
        return $this->cache;
    }

    /**
     * Return languages instance
     */
    public function languages(): Languages
    {
        return $this->languages;
    }

    /**
     * Return translations instance
     */
    public function translations(): Translations
    {
        return $this->translations;
    }

    /**
     * Return translations instance
     */
    public function schemes(): Schemes
    {
        return $this->schemes;
    }

    /**
     * Return admin instance
     */
    public function admin(): ?Admin
    {
        return $this->admin;
    }

    /**
     * Return default options
     */
    public function defaults(): array
    {
        return PHP::parseFile(FORMWORK_PATH . 'defaults.php');
    }

    /**
     * Run Formwork
     */
    public function run(): void
    {
        $response = $this->router->dispatch();

        $response->send();

        if ($this->config()->get('statistics.enabled') && $this->site->currentPage() !== null && !$this->site->currentPage()->isErrorPage()) {
            $statistics = new Statistics();
            $statistics->trackVisit();
        }
    }

    /**
     * Load options
     */
    protected function loadConfig(): void
    {
        // Default config is needed to parse YAML
        $this->config = new Config($this->defaults());

        $data = YAML::parseFile(CONFIG_PATH . 'system.yml');

        if ($data !== []) {
            if (isset($data['admin.root'])) {
                // Trim slashes from admin.root
                $data['admin.root'] = trim($data['admin.root'], '/');
            }
            $this->config = new Config(array_replace_recursive($this->defaults(), $data));
        }

        date_default_timezone_set($this->config->get('date.timezone'));
    }

    /**
     * Load error handlers
     */
    protected function loadErrorHandlers(): void
    {
        if ($this->config()->get('errors.set_handlers')) {
            Errors::setHandlers();
        }
    }

    /**
     * Load language from request
     */
    protected function loadLanguages(): void
    {
        $this->languages = Languages::fromRequest($this->request);

        if ($this->languages->requested() !== null) {
            $this->request = Str::removeStart($this->request, '/' . $this->languages->current());
        } elseif ($this->languages->preferred() !== null) {
            // Don't redirect if we are in Admin
            if (!Str::startsWith($this->request, '/' . $this->config()->get('admin.root'))) {
                Header::redirect(HTTPRequest::root() . $this->languages->preferred() . $this->request);
            }
        }
    }

    /**
     * Load translations
     */
    protected function loadTranslations(): void
    {
        $this->translations = Translations::fromPath($this->config()->get('translations.paths.system'));
        $this->translations->setCurrent($this->languages->current() ?? $this->config()->get('translations.fallback'));
    }

    protected function loadSchemes(): void
    {
        $this->schemes = Schemes::fromPath('config', $this->config()->get('schemes.paths.config'));
        $this->schemes->loadFromPath('pages', $this->config()->get('schemes.paths.pages'));
    }

    /**
     * Load site
     */
    protected function loadSite(): void
    {
        $config = YAML::parseFile(CONFIG_PATH . 'site.yml');
        $this->site = new Site($config);
        $this->site->setLanguages($this->languages);
    }

    /**
     * Load cache
     */
    protected function loadCache(): void
    {
        $this->cache = new FilesCache($this->config()->get('cache.path'), $this->config()->get('cache.time'));
    }

    protected function loadRouter(): void
    {
        $this->router = new Router();
    }

    protected function loadAdmin(): void
    {
        if ($this->config->get('admin.enabled')) {
            $this->admin = new Admin();
            $this->admin->load();
        }
    }

    /**
     * Load routes
     */
    protected function loadRoutes(): void
    {
        $this->router->loadFromFile($this->config()->get('routes.files.system'));
    }
}
