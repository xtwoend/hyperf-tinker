<?php

declare(strict_types=1);

namespace Gokure\HyperfTinker;

use Psy\Shell;
use Psy\Configuration;
use Psy\VersionUpdater\Checker;
use Hyperf\Contract\ConfigInterface;
use Psr\Container\ContainerInterface;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

/**
 * @Command
 */
class TinkerCommand extends HyperfCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct('tinker');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Interact with your application');
        $this->addArgument('include', InputArgument::IS_ARRAY, 'Include file(s) before starting tinker');
        $this->addOption('execute', null, InputOption::VALUE_OPTIONAL, 'Execute the given code using Tinker');
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->getApplication()->setCatchExceptions(false);

        $config = Configuration::fromInput($this->input);
        $config->setUpdateCheck(Checker::NEVER);
        $config->setUsePcntl(false);

        $config->getPresenter()->addCasters(
            $this->getCasters()
        );

        $shell = new Shell($config);
        $shell->setIncludes($this->input->getArgument('include'));

        if (isset($_ENV['COMPOSER_VENDOR_DIR'])) {
            $path = $_ENV['COMPOSER_VENDOR_DIR'];
        } else {
            $path = BASE_PATH . DIRECTORY_SEPARATOR . 'vendor';
        }

        $path .= '/composer/autoload_classmap.php';

        $config = $this->container->get(ConfigInterface::class);

        $loader = ClassAliasAutoloader::register(
            $shell, $path, $config->get('tinker.alias', []), $config->get('tinker.dont_alias', [])
        );

        if ($code = $this->input->getOption('execute')) {
            try {
                $shell->setOutput($this->output);
                $shell->execute($code);
            } finally {
                $loader->unregister();
            }

            return 0;
        }

        try {
            return $shell->run();
        } finally {
            $loader->unregister();
        }
    }

    /**
     * Get an array of Laravel tailored casters.
     *
     * @return array
     */
    protected function getCasters()
    {
        $casters = [
            'Hyperf\Utils\Collection' => 'Gokure\HyperfTinker\TinkerCaster::castCollection',
            'Hyperf\Utils\Stringable' => 'Gokure\HyperfTinker\TinkerCaster::castStringable',
        ];

        if (class_exists('Hyperf\Database\Model\Model')) {
            $casters['Hyperf\Database\Model\Model'] = 'Gokure\HyperfTinker\TinkerCaster::castModel';
        }

        $config = $this->container->get(ConfigInterface::class);

        return array_merge($casters, (array) $config->get('tinker.casters', []));
    }
}
