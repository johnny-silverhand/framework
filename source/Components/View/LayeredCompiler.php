<?php
/**
 * Spiral Framework, SpiralScout LLC.
 *
 * @package   spiralFramework
 * @author    Anton Titov (Wolfy-J)
 * @copyright ©2009-2011
 */

namespace Spiral\Components\View;

use Spiral\Core\Component;
use Spiral\Core\Container;

class LayeredCompiler extends Component implements CompilerInterface
{
    /**
     * Instance of ViewManager component.
     *
     * @invisible
     * @var ViewManager
     */
    protected $viewManager = null;

    /**
     * Container instance.
     *
     * @invisible
     * @var Container
     */
    protected $container = null;

    /**
     * View namespace.
     *
     * @var string
     */
    protected $namespace = '';

    /**
     * View name.
     *
     * @var string
     */
    protected $view = '';

    /**
     * Non compiled view source.
     *
     * @var string
     */
    protected $source = '';

    /**
     * Original view filename.
     *
     * @var string
     */
    protected $input = '';

    /**
     * Cached view filename (can be empty or non exists).
     *
     * @var string
     */
    protected $output = '';

    /**
     * View processors. Processors used to pre-process view source and save it to cache, in normal
     * operation mode processors will be called only once and never during user request.
     *
     * @var array|ProcessorInterface[]
     */
    protected $processors = [];

    /**
     * Instance of view compiler. Compilers used to pre-process view files for faster rendering in
     * runtime environment.
     *
     * @param ViewManager $manager
     * @param string      $source     Non-compiled source.
     * @param string      $namespace  View namespace.
     * @param string      $view       View name.
     * @param string      $input      View filename.
     * @param string      $output     Cached view filename (can be empty or not exists).
     * @param Container   $container
     * @param array       $processors Layered compiler processors.
     */
    public function __construct(
        ViewManager $manager,
        $source,
        $namespace,
        $view,
        $input = '',
        $output = '',
        Container $container = null,
        array $processors = []
    )
    {
        $this->viewManager = $manager;
        $this->container = $container;

        $this->namespace = $namespace;
        $this->view = $view;
        $this->source = $source;
        $this->input = $input;
        $this->output = $output;
        $this->processors = $processors;
    }

    /**
     * Get view processor by name, processor will be loaded and configured automatically. Processors
     * are created only for pre-processing view source to create static cache, this means you should't
     * expect too high performance and optimizations inside, due it's more important to have good
     * functionality and reliable results.
     *
     * You should never user view component in production with disabled cache, this will slow down
     * your website dramatically.
     *
     * @param string $name
     * @return ProcessorInterface
     */
    public function getProcessor($name)
    {
        if (isset($this->processors[$name]) && is_object($this->processors[$name]))
        {
            return $this->processors[$name];
        }

        $config = $this->processors[$name];

        return $this->processors[$name] = $this->container->get($config['class'],
            [
                'compiler'    => $this,
                'viewManager' => $this->viewManager,
                'options'     => $config
            ]
        );
    }

    /**
     * Get processor names.
     *
     * @return array
     */
    public function getProcessors()
    {
        return array_keys($this->processors);
    }

    /**
     * Compile original view file to plain php code.
     *
     * @return string
     */
    public function compile()
    {
        $source = $this->source;
        foreach ($this->getProcessors() as $processor)
        {
            benchmark('view::' . $processor, $this->namespace . ':' . $this->view);

            //Compiling
            $source = $this->getProcessor($processor)->processSource(
                $source,
                $this->namespace,
                $this->view,
                $this->input,
                $this->output
            );

            benchmark('view::' . $processor, $this->namespace . ':' . $this->view);
        }

        return $source;
    }
}