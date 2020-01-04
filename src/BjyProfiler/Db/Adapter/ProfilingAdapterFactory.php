<?php
/**
 * Created by Inditel Meedia OÜ
 * User: Oliver Leisalu
 */

namespace BjyProfiler\Db\Adapter;


use BjyProfiler\Db\Profiler\Profiler;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Interop\Container\ContainerInterface;
use BjyProfiler\Db\Profiler\LoggingProfiler;
use Zend\Log\Logger;
use Zend\Log\Writer\Stream;


class ProfilingAdapterFactory implements FactoryInterface
{


      public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
      {

        $config = $container->get('config');

        $adapter = new ProfilingAdapter(array(
            'driver'    => 'pdo',
            'dsn'       => 'mysql:dbname='.$config['db']['database'].';charset=utf8;host='.$config['db']['hostname'],
            'database'  => $config['db']['database'],
            'username'  => $config['db']['username'],
            'password'  => $config['db']['password'],
            'hostname'  => $config['db']['hostname'],
        ));

        if (php_sapi_name() == 'cli') {
            $logger = new Logger();
            // write queries profiling info to stdout in CLI mode
            $writer = new Stream('php://output');
            $logger->addWriter($writer, Logger::DEBUG);
            $adapter->setProfiler(new LoggingProfiler($logger));
        } else {
            $adapter->setProfiler(new Profiler());
        }
        if (isset($dbParams['options']) && is_array($dbParams['options'])) {
            $options = $dbParams['options'];
        } else {
            $options = array();
        }
        $adapter->injectProfilingStatementPrototype($options);
        return $adapter;
      }


    /**
     * @param ServiceLocatorInterface $serviceLocator
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Configuration');
        $dbParams = $config['db'];
        $adapter = new ProfilingAdapter($dbParams);

        $adapter->setProfiler(new Profiler);
        if (isset($config['db']['buffer_results'])) {
            $options = array('buffer_results' => $config['db']['buffer_results']);
        } else {
            $options = array();
        }
        $adapter->injectProfilingStatementPrototype($options);
        return $adapter;
    }
}