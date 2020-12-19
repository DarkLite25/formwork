<?php

namespace Formwork\View;

use Formwork\Core\Formwork;
use Formwork\Utils\FileSystem;
use Formwork\Parsers\PHP;
use BadMethodCallException;
use RuntimeException;

class View
{
    /**
     * View type
     *
     * @var string
     */
    protected const TYPE = 'view';

    /**
     * View name
     *
     * @var string
     */
    protected $name;

    /**
     * View variables
     *
     * @var array
     */
    protected $vars = [];

    /**
     * View blocks
     *
     * @var array
     */
    protected $blocks = [];

    /**
     * View incomplete blocks
     *
     * @var array
     */
    protected $incompleteBlocks = [];

    /**
     * Layout view
     *
     * @var static
     */
    protected $layout;

    /**
     * Helper functions to be used in views
     *
     * @var array
     */
    protected static $helpers = [];

    /**
     * Whether the view is being rendered
     *
     * @var bool
     */
    protected $rendering = false;

    /**
     * Create a new View instance
     */
    public function __construct(string $name, array $vars = [])
    {
        $this->name = $name;
        $this->vars = array_merge($this->defaults(), $vars);
        $this->initializeHelpers();
    }

    /**
     * Get view name
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Get view path
     */
    public function path(): string
    {
        return Formwork::instance()->config()->get('views.paths.system');
    }

    /**
     * Set view layout
     */
    public function layout(string $name): void
    {
        if ($this->layout !== null) {
            throw new RuntimeException('The layout for the ' . static::TYPE . ' "' . $this->name . '" is already set');
        }
        $this->layout = $this->createLayoutView($name);
    }

    /**
     * Insert a view
     */
    public function insert(string $name, array $vars = []): void
    {
        if (!$this->rendering) {
            throw new RuntimeException(__METHOD__ . '() is allowed only in rendering context');
        }

        $file = $this->path() . str_replace('.', DS, $name) . '.php';

        if (!FileSystem::exists($file)) {
            throw new RuntimeException(ucfirst(static::TYPE) . ' "' . $name . '" not found');
        }

        Renderer::load($file, array_merge($this->vars, $vars), $this);
    }

    /**
     * Render the view
     */
    public function render(bool $return = false)
    {
        if ($this->rendering) {
            throw new RuntimeException(__METHOD__ . '() not allowed while rendering');
        }

        ob_start();

        $this->rendering = true;

        $this->insert($this->name);

        if ($this->layout !== null) {
            $this->layout->vars = $this->vars;
            $this->layout->blocks['content'] = ob_get_contents();
            ob_clean(); // Clean but don't end output buffer
            $this->layout->render();
        }

        $this->rendering = false;

        if ($this->incompleteBlocks !== []) {
            throw new RuntimeException('Incomplete blocks found: "' . implode('", "', $this->incompleteBlocks) . '". Use "$this->end()" to properly close them');
        }

        if ($return) {
            return ob_get_clean();
        }
        ob_end_flush();
    }

    /**
     * Start the capturing of a block
     */
    public function define(string $block): void
    {
        if (!$this->rendering) {
            throw new RuntimeException(__METHOD__ . '() is allowed only in rendering context');
        }

        if ($block === 'content') {
            throw new RuntimeException('The block "content" is reserved');
        }
        $this->incompleteBlocks[] = $block;
        ob_start();
    }

    /**
     * End the capturing of last block
     */
    public function end(): void
    {
        if (!$this->rendering) {
            throw new RuntimeException(__METHOD__ . '() is allowed only in rendering context');
        }
        if ($this->incompleteBlocks === []) {
            throw new RuntimeException('There are no blocks to end');
        }
        $block = array_pop($this->incompleteBlocks);
        $this->blocks[$block] = ob_get_clean();
    }

    /**
     * Get the content of a given block
     */
    public function block(string $name): string
    {
        if (!$this->rendering) {
            throw new RuntimeException(__METHOD__ . '() is allowed only in rendering context');
        }
        if (!isset($this->blocks[$name])) {
            throw new RuntimeException('The block "' . $name . '" is undefined');
        }
        return $this->blocks[$name];
    }

    /**
     * Get the layout content
     */
    public function content(): string
    {
        return $this->block('content');
    }

    /**
     * Return an array containing the default data
     */
    protected function defaults(): array
    {
        return [
            'formwork' => Formwork::instance(),
            'site'     => Formwork::instance()->site()
        ];
    }

    /**
     * Return an array containing view helpers
     */
    protected function helpers(): array
    {
        return PHP::parseFile(FORMWORK_PATH . 'helpers.php');
    }

    /**
     * Initialize view helpers
     */
    protected function initializeHelpers(): void
    {
        if (empty(static::$helpers)) {
            static::$helpers = $this->helpers();
        }
    }

    /**
     * Return the layout view instance
     */
    protected function createLayoutView(string $name): View
    {
        return new static('layouts' . DS . $name, $this->vars);
    }

    public function __call(string $name, array $arguments)
    {
        if ($this->rendering && isset(static::$helpers[$name])) {
            return static::$helpers[$name](...$arguments);
        }
        throw new BadMethodCallException('Call to undefined method ' . static::class . '::' . $name . '()');
    }
}