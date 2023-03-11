<?php

namespace NormanHuth\SymfonyBladeTemplates;

use Illuminate\Container\Container;
use Illuminate\Contracts\View\View;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Factory as ViewFactory;
use Illuminate\View\FileViewFinder;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Response;

class SymfonyView
{
    /**
     * The View Factory instance.
     *
     * @var ViewFactory
     */
    protected $factory;

    /**
     * The path of the project.
     *
     * @var string
     */
    protected $projectPath;

    /**
     * The path of the compiled views.
     *
     * @var string
     */
    protected $cachePath;

    /**
     * The array of active view paths.
     *
     * @var array
     */
    protected $paths;

    /**
     * The FileViewFinder instance.
     *
     * @var FileViewFinder
     */
    public $finder;

    /**
     * The Filesystem instance.
     *
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * The Events Dispatcher instance.
     *
     * @var Dispatcher
     */
    protected $events;

    /**
     * The SymfonyView constructor.
     *
     * @param array|string $paths Active view path(s).
     */
    public function __construct($paths = [])
    {
        $this->initFilePaths();
        $this->initInstances();
        $this->initViewsPaths($paths);
        $this->createFinder($paths);
        $this->createFactory();
    }

    /**
     * Initialise file paths.
     *
     * @return void
     */
    protected function initFilePaths(): void
    {
        $this->projectPath = $this->getProjectPath();
        $this->cachePath = $this->getCachePath();
    }

    /**
     * Create Filesystem and Events Dispatcher instances.
     *
     * @return void
     */
    protected function initInstances(): void
    {
        $this->filesystem = new Filesystem();
        $this->events = new Dispatcher(new Container());
    }

    /**
     * Initialise view file path(s).
     *
     * @param array|string $paths
     * @return void
     */
    protected function initViewsPaths($paths): void
    {
        if (empty($paths)) {
            $paths = rtrim($this->projectPath, '\\/').DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'views';

            try {
                if (!$this->filesystem->isDirectory(dirname($paths))) {
                    $this->filesystem->makeDirectory(dirname($paths));
                }
                if (!$this->filesystem->isDirectory($paths)) {
                    $this->filesystem->makeDirectory($paths);
                }
            } catch (\Exception $exception) {
                // Silent
            }
        }

        $this->paths = (array) $paths;
    }

    /**
     * Create the FileViewFinder instance.
     *
     * @param array|string|null $paths
     * @param array|null        $extensions Register a view extension with the finder. Default: ['blade.php', 'php', 'css', 'html']
     * @return void
     */
    protected function createFinder($paths = null, array $extensions = null): void
    {
        if (empty($paths)) {
            $paths = $this->paths;
        }

        if (!is_array($paths)) {
            $paths = (array) $paths;
        }

        $this->finder = new FileViewFinder($this->filesystem, $paths, $extensions);
    }

    /**
     * Create the ViewFactory instance.
     *
     * @return void
     */
    protected function createFactory(): void
    {
        $engines = new EngineResolver();
        $bladeCompiler = new BladeCompiler($this->filesystem, $this->cachePath);

        $engines->register('blade', function () use ($bladeCompiler) {
            return new CompilerEngine($bladeCompiler);
        });

        $engines->register('php', function () {
            return new PhpEngine($this->filesystem);
        });

        $this->factory = new ViewFactory($engines, $this->finder, $this->events);
    }

    /**
     * Get the path of the compiled views.
     *
     * @return string
     */
    protected function getCachePath(): string
    {
        return rtrim($this->projectPath, '\\/').DIRECTORY_SEPARATOR.'var'.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'views';
    }

    /**
     * Get Path of the current project.
     *
     * @return string
     */
    protected function getProjectPath(): string
    {
        if (!class_exists('\Composer\Autoload\ClassLoader')) {
            return dirname(__DIR__, 4);
        }

        $reflection = new ReflectionClass(\Composer\Autoload\ClassLoader::class);

        return dirname($reflection->getFileName(), 3);
    }

    /**
     * Get the evaluated contents of the object.
     *
     * @param string $view
     * @param array  $data
     * @param array  $mergeData
     * @return View
     */
    public function render(string $view, array $data = [], array $mergeData = []): View
    {
        return $this->factory->make($view, $data, $mergeData);
    }

    /**
     * Get the evaluated contents of the object and create a new HTTP response.
     *
     * @param string $view
     * @param array  $data
     * @param array  $mergeData
     * @param int    $status
     * @param array  $headers
     * @return Response
     */
    public function response(string $view, array $data = [], array $mergeData = [], int $status = 200, array $headers = []): Response
    {
        $content = $this->render($view, $data, $mergeData);

        return new Response(
            $content,
            $status,
            $headers
        );
    }
}
