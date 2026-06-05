<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    public function getCacheDir(): string
    {
        if ($this->environment === 'prod') {
            return sys_get_temp_dir().'/redcall/cache/';
        }

        return $this->getProjectDir().'/var/cache/'.$this->environment;
    }

    public function getLogDir(): string
    {
        if ($this->environment === 'prod') {
            return sys_get_temp_dir().'/redcall/log/';
        }

        return $this->getProjectDir().'/var/log';
    }

    public function registerBundles(): iterable
    {
        $contents = require $this->getProjectDir().'/config/bundles.php';
        foreach ($contents as $class => $envs) {
            if (isset($envs['all']) || isset($envs[$this->environment])) {
                yield new $class();
            }
        }
    }

    protected function configureContainer(ContainerConfigurator $container, LoaderInterface $loader, ContainerBuilder $builder): void
    {
        $builder->setParameter('container.autowiring.strict_mode', true);
        $builder->setParameter('container.dumper.inline_class_loader', true);

        $confDir = $this->getProjectDir().'/config';

        $container->import($confDir.'/international/languages.yaml');
        $container->import($confDir.'/international/phones.yaml');

        $container->import($confDir.'/{packages}/*'.self::CONFIG_EXTS);
        $container->import($confDir.'/{packages}/'.$this->environment.'/**/*'.self::CONFIG_EXTS);
        $container->import($confDir.'/{services}'.self::CONFIG_EXTS);
        $container->import($confDir.'/{services}_'.$this->environment.self::CONFIG_EXTS);
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $confDir = $this->getProjectDir().'/config';

        $routes->import($confDir.'/{routes}/*'.self::CONFIG_EXTS);
        $routes->import($confDir.'/{routes}/'.$this->environment.'/**/*'.self::CONFIG_EXTS);
        $routes->import($confDir.'/{routes}'.self::CONFIG_EXTS);
    }
}
