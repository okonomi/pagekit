<?php

namespace Pagekit\Theme;

use Pagekit\Application as App;
use Pagekit\View\Section\SectionManager;
use Pagekit\View\ViewInterface;
use Symfony\Component\Translation\Translator;

class Theme
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var array
     */
    protected $parameters = [];

    /**
     * @var string
     */
    protected $layout = '/templates/template.razr';

    /**
     * Constructor.
     *
     * @param string $name
     * @param string $path
     * @param array  $config
     */
    public function __construct($name, $path, array $config = [])
    {
        $this->name   = $name;
        $this->path   = $path;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function boot(App $app)
    {
        $app['system']->loadLanguages($this->getPath());

        if ($this->getConfig('parameters.settings')) {

            if (is_array($defaults = $this->getConfig('parameters.settings.defaults'))) {
                $this->parameters = array_replace($this->parameters, $defaults);
            }

            if (is_array($settings = App::option("{$this->name}:settings"))) {
                $this->parameters = array_replace($this->parameters, $settings);
            }
        }

        $app->on('system.site', function() use ($app) {
            $this->registerRenderer($app['sections'], $app['view']);
        });

        $app->on('system.positions', function($event) {
            foreach ($this->getConfig('positions', []) as $id => $position) {
                list($name, $description) = array_merge((array) $position, ['']);
                $event->register($id, $name, $description);
            }
        });
    }

    /**
     * Returns the theme name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the theme absolute path.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Returns the theme layout absolute path.
     *
     * @return string|false
     */
    public function getLayout()
    {
        return $this->path.$this->layout;
    }

    /**
     * Returns the theme's config.
     *
     * @param  mixed $key
     * @param  mixed $default
     * @return array
     */
    public function getConfig($key = null, $default = null)
    {
        return $this->fetch($this->config, $key, $default);
    }

    /**
     * Returns the theme's parameters.
     *
     * @param  mixed $key
     * @param  mixed $default
     * @return array
     */
    public function getParams($key = null, $default = null)
    {
        return $this->fetch($this->parameters, $key, $default);
    }

    /**
     * Returns a value from given array.
     *
     * @param  array $array
     * @param  mixed $key
     * @param  mixed $default
     * @return array
     */
    protected function fetch($array, $key, $default)
    {
        if (null === $key) {
            return $array;
        }

        if (isset($array[$key])) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {

            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }

            $array = $array[$segment];
        }

        return $array;
    }

    /**
     * Adds section renderer.
     *
     * @param SectionManager $sections
     * @param ViewInterface  $view
     */
    public function registerRenderer(SectionManager $sections, ViewInterface $view)
    {
        foreach ($this->getConfig('renderer', []) as $name => $template) {
            $sections->addRenderer($name, function($name, $value, $options = []) use ($template, $view) {
                return $view->render($template, compact('name', 'value', 'options'));
            });
        }
    }
}
