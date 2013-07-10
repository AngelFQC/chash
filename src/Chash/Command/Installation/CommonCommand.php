<?php

namespace Chash\Command\Installation;

use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Parser;
use Doctrine\DBAL\Migrations\Tools\Console\Command\AbstractCommand;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;

class CommonCommand extends AbstractCommand
{
    public $portalSettings;
    public $databaseSettings;
    public $adminSettings;
    public $rootSys;
    public $configurationPath = null;
    public $configuration = array();
    public $extraDatabaseSettings;

    /**
     * @param array $configuration
     */
    public function setConfigurationArray(array $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @return array
     */
    public function getConfigurationArray()
    {
        return $this->configuration;
    }

    /**
     * @param string $path
     */
    public function setConfigurationPath($path)
    {
        $this->configurationPath = $path;
    }

    /**
     * @return null
     */
    public function getConfigurationPath()
    {
        return $this->configurationPath;
    }

    /**
     * @param array $portalSettings
     */
    public function setPortalSettings(array $portalSettings)
    {
        $this->portalSettings = $portalSettings;
    }

    /**
     * @return array
     */
    public function getPortalSettings()
    {
        return $this->portalSettings;
    }

    /**
     * @param array $databaseSettings
     */
    public function setDatabaseSettings(array $databaseSettings)
    {
        $this->databaseSettings = $databaseSettings;
    }

    /**
     * @return array
     */
    public function getDatabaseSettings()
    {
        return $this->databaseSettings;
    }

        /**
     * @param array $databaseSettings
     */
    public function setExtraDatabaseSettings(array $databaseSettings)
    {
        $this->extraDatabaseSettings = $databaseSettings;
    }

    /**
     * @return array
     */
    public function getExtraDatabaseSettings()
    {
        return $this->extraDatabaseSettings;
    }


    /**
     * @param array $adminSettings
     */
    public function setAdminSettings(array $adminSettings)
    {
        $this->adminSettings = $adminSettings;
    }

    public function getAdminSettings()
    {
        return $this->adminSettings;
    }

    /**
     * @param string $path
     */
    public function setRootSys($path)
    {
        $this->rootSys = $path;
    }

    /**
     * @return string
     */
    public function getRootSys()
    {
        return $this->rootSys;
    }

    /**
     * @return string
     */
    public function getCourseSysPath()
    {
        if (is_dir($this->getRootSys().'courses')) {
            return $this->getRootSys().'courses';
        }

        if (is_dir($this->getRootSys().'data/courses')) {
            return $this->getRootSys().'data/courses';
        }

        return null;
    }

    /**
     * @return string
     */
    public function getInstallationFolder()
    {
        return realpath(__DIR__.'/../../Resources/Database').'/';
    }

    /**
     * Gets the version name folders located in main/install
     *
     * @return array
     */
    public function getAvailableVersions()
    {
        $installPath = $this->getInstallationFolder();
        $dir = new \DirectoryIterator($installPath);
        $dirList = array();
        foreach ($dir as $fileInfo) {
            if ($fileInfo->isDir() && !$fileInfo->isDot()) {
                $dirList[$fileInfo->getFilename()] = $fileInfo->getFilename();
            }
        }
        natsort($dirList);
        return $dirList;
    }

    /**
     * @return array
     */
    public function getAdminSettingsParams()
    {
        return array(
            'firstname' => array(
                'attributes' => array(
                    'label' => 'Firstname',
                    'data' =>  'John'
                ),
                'type' => 'text'
            ),
            'lastname' =>  array(
                'attributes' => array(
                    'label' => 'Lastname',
                    'data' =>  'Doe'
                ),
                'type' => 'text'
            ),
            'username' => array(
                'attributes' => array(
                    'label' => 'Username',
                    'data' =>  'admin'
                ),
                'type' => 'text'
            ),
            'password' => array(
                'attributes' => array(
                    'label' => 'Password',
                    'data' =>  'admin'
                ),
                'type' => 'password'
            ),
            'email' => array(
                'attributes' => array(
                    'label' => 'Email',
                    'data' =>  'admin@example.org'
                ),
                'type' => 'email'
            ),
            'language' => array(
                'attributes' => array(
                    'label' => 'Language',
                    'data' =>  'english'
                ),
                'type' => 'text'
            ),
            'phone' => array(
                'attributes' => array(
                    'label' => 'Phone',
                    'data' =>  '123456'
                ),
                'type' => 'text'
            )
        );
    }

    /**
     * @return array
     */
    public function getPortalSettingsParams()
    {
        return array(
            'sitename' => array(
                'attributes' => array(
                    'label' => 'Site name',
                    'data' => 'Campus Chamilo',
                ),
                'type' => 'text'
            ),
            'institution' => array(
                'attributes' => array(
                    'data' => 'Chamilo',
                ),
                'type' => 'text'
            ),
            'institution_url' => array(
                'attributes' => array(
                    'label' => 'URL',
                    'data' => 'http://localhost/',
                ),
                'type' => 'text'
            ),
            'encrypt_method' => array(
                'attributes' => array(
                    'choices' => array(
                        'sha1' => 'sha1',
                        'md5' => 'md5',
                        'none' => 'none'
                    ),
                    'data' => 'sha1'
                ),

                'type' => 'choice'
            ),
            'permissions_for_new_directories' => array(
                'attributes' => array(
                    'data' => '0777',
                ),
                'type' => 'text'
            ),
            'permissions_for_new_files' => array(
                'attributes' => array(
                    'data' => '0666',
                ),
                'type' => 'text'
            ),

        );
    }

    /**
     * Database parameters that are going to be parsed during the console/browser installation
     * @return array
     */
    public function getDatabaseSettingsParams()
    {
        return array(
            'driver' => array(
                'attributes' => array(
                    'choices' =>
                        array(
                            'pdo_mysql' => 'pdo_mysql',
                            'pdo_sqlite' => 'pdo_sqlite',
                            'pdo_pgsql' => 'pdo_pgsql',
                            'pdo_oci' => 'pdo_oci',
                            'ibm_db2' => 'ibm_db2',
                            'pdo_ibm' => 'pdo_ibm',
                            'pdo_sqlsrv' => 'pdo_sqlsrv'
                        ),
                    'data' => 'pdo_mysql'
                ),
                'type' => 'choice'
            ),
            'host' => array(
                'attributes' => array(
                    'label' => 'Host',
                    'data' => 'localhost',
                ),
                'type' => 'text'
            ),
            'dbname' => array(
                'attributes' => array(
                    'label' => 'Database name',
                    'data' => 'chamilo',
                ),
                'type' => 'text'
            ),
            'user' => array(
                'attributes' => array(
                    'label' => 'User',
                    'data' => 'root',
                ),
                'type' => 'text'
            ),
            'password' => array(
                'attributes' => array(
                    'label' => 'Password',
                    'data' => 'root',
                ),
                'type' => 'password'
            )
        );
    }
    /**
     * Gets the installation version path
     *
     * @param string $version
     *
     * @return string
     */
    public function getInstallationPath($version)
    {
        return __DIR__.'/../../Resources/Database/'.$version.'/';
    }

    /**
     * Gets the content of a version from the available versions
     *
     * @param string $version
     *
     * @return bool
     */
    public function getAvailableVersionInfo($version)
    {
        $versionList = $this->availableVersions();
        foreach ($versionList as $versionName => $versionInfo) {
            if ($version == $versionName) {
                return $versionInfo;
            }
        }

        return false;
    }

    /**
     * Gets the min version available to migrate with this command
     * @return mixed
     */
    public function getMinVersionSupportedByInstall()
    {
        return key($this->availableVersions());
    }

    /**
     * Gets an array with the supported versions to migrate
     * @return array
     */
    public function getVersionNumberList()
    {
        $versionList = $this->availableVersions();
        $versionNumberList = array();
        foreach ($versionList as $version => $info) {
            $versionNumberList[] = $version;
        }

        return $versionNumberList;
    }

    /**
     * Gets an array with the settings for every supported version
     *
     * @return array
     */
    public function availableVersions()
    {
        $versionList = array(
            '1.8.7' => array(
                'require_update' => false,
            ),
            '1.8.8' => array(
                'require_update' => true,
                'pre' => 'migrate-db-1.8.7-1.8.8-pre.sql',
                'post' => null,
                'update_db' => 'update-db-1.8.7-1.8.8.inc.php',
                //'update_files' => 'update-files-1.8.7-1.8.8.inc.php',
                'hook_to_doctrine_version' => '8' //see ChamiloLMS\Migrations\Version8.php file
            ),
            '1.8.8.2' => array(
                'require_update' => false,
                'parent' => '1.8.8'
            ),
            '1.8.8.4' => array(
                'require_update' => false,
                'parent' => '1.8.8'
            ),
            '1.8.8.6' => array(
                'require_update' => false,
                'parent' => '1.8.8'
            ),
            '1.9.0' => array(
                'require_update' => true,
                'pre' => 'migrate-db-1.8.8-1.9.0-pre.sql',
                'post' => null,
                'update_db' => 'update-db-1.8.8-1.9.0.inc.php',
                'update_files' => 'update-files-1.8.8-1.9.0.inc.php',
                'hook_to_doctrine_version' => '9'
            ),
            '1.9.2' => array(
                'require_update' => false,
                'parent' => '1.9.0'
            ),
            '1.9.4' => array(
                'require_update' => false,
                'parent' => '1.9.0'
            ),
            '1.9.6' => array(
                'require_update' => false,
                'parent' => '1.9.0'
            ),
            '1.9.8' => array(
                'require_update' => false,
                'parent' => '1.9.0'
            ),
            '1.10.0'  => array(
                'require_update' => true,
                'pre' => 'migrate-db-1.9.0-1.10.0-pre.sql',
                'post' => 'migrate-db-1.9.0-1.10.0-post.sql',
                'update_db' => 'update-db-1.9.0-1.10.0.inc.php',
                'update_files' => null,
                'hook_to_doctrine_version' => '10'
            )
        );

        return $versionList;
    }


    /**
     * Gets the Doctrine configuration file path
     * @return string
     */
    public function getMigrationConfigurationFile()
    {
        return realpath(__DIR__.'/../../Migrations/migrations.yml');
        //return $this->getRootSys().'src/ChamiloLMS/Migrations/migrations.yml';
    }

    /**
     *
     * @return \Chash\Helpers\ConfigurationHelper
     */
    public function getConfigurationHelper()
    {
        return $this->getHelper('configuration');
    }

    /**
     * @todo move to configurationhelper
     * @param string $path
     */
    public function setRootSysDependingConfigurationPath($path)
    {
        $configurationPath = $this->getConfigurationHelper()->getNewConfigurationPath($path);

        if ($configurationPath == false) {
            //  Seems an old installation!
            $configurationPath = $this->getConfigurationHelper()->getConfigurationPath($path);
            $this->setRootSys(realpath($configurationPath.'/../../../').'/');
        } else {
            // Chamilo installations > 1.10
            $this->setRootSys(realpath($configurationPath.'/../').'/');
        }
    }

    /**
     * Writes the configuration file a yml file
     * @param string $version
     * @param string $path
     * @return bool
     *
     */
    public function writeConfiguration($version, $path)
    {
        $portalSettings = $this->getPortalSettings();
        $databaseSettings = $this->getDatabaseSettings();

        $configurationPath = $this->getConfigurationHelper()->getConfigurationPath($path);

        // Creates a YML File

        $configuration = array();

        $configuration['system_version'] = $version;

        $configuration['db_host'] = $databaseSettings['host'];
        $configuration['db_user'] = $databaseSettings['user'];
        $configuration['db_password'] = $databaseSettings['password'];
        $configuration['main_database'] = $databaseSettings['dbname'];
        $configuration['driver'] = $databaseSettings['driver'];

        $configuration['root_web'] = $portalSettings['institution_url'];
        $configuration['root_sys'] = $this->getRootSys();

        $configuration['security_key'] = md5(uniqid(rand().time()));

        // Hash function method
        $configuration['password_encryption']      = $portalSettings['encrypt_method'];
        // You may have to restart your web server if you change this
        $configuration['session_stored_in_db']     = false;
        // Session lifetime
        $configuration['session_lifetime']         = 3600;
        // Activation for multi-url access
        $_configuration['multiple_access_urls']   = false;
        //Deny the elimination of users
        $configuration['deny_delete_users']        = false;
        //Prevent all admins from using the "login_as" feature
        $configuration['login_as_forbidden_globally'] = false;

        // Version settings
        $configuration['system_version']           = '1.10.0';

        /*
        $dumper = new Dumper();
        $yaml = $dumper->dump($configuration, 2);

        $newConfigurationFile = $configurationPath.'configuration.yml';
        file_put_contents($newConfigurationFile, $yaml);

        return file_exists($newConfigurationFile);*/

        // Create a configuration.php

        if (file_exists($this->getRootSys().'config/configuration.dist.php')) {
            $contents = file_get_contents($this->getRootSys().'config/configuration.dist.php');
        } else {
            // Try the old one
            //$contents = file_get_contents($this->getRootSys().'main/inc/conf/configuration.dist.php');
            $contents = file_get_contents($this->getRootSys().'main/install/configuration.dist.php');
        }

        $configuration['{DATE_GENERATED}'] = date('r');
        $config['{DATABASE_HOST}'] = $configuration['db_host'];
        $config['{DATABASE_USER}'] = $configuration['db_user'];
        $config['{DATABASE_PASSWORD}'] = $configuration['db_password'];
        $config['{DATABASE_MAIN}'] = $configuration['main_database'];
        $config['{DATABASE_DRIVER}'] = $configuration['driver'];

        $config['{ROOT_WEB}'] = $portalSettings['institution_url'];
        $config['{ROOT_SYS}'] = $this->getRootSys();

        //$config['{URL_APPEND_PATH}'] = $urlAppendPath;
        $config['{SECURITY_KEY}'] = $configuration['security_key'];
        $config['{ENCRYPT_PASSWORD}'] = $configuration['password_encryption'];

        $config['SESSION_LIFETIME'] = 3600;
        $config['{NEW_VERSION}'] = $this->getLatestVersion();
        $config['NEW_VERSION_STABLE'] = 'true';

        foreach ($config as $key => $value) {
            $contents = str_replace($key, $value, $contents);
        }
        $newConfigurationFile = $configurationPath.'configuration.php';


        return file_put_contents($newConfigurationFile, $contents);
    }


    /**
     * Updates the configuration.yml file
     * @param string $version
     *
     * @return bool
     */
    public function updateConfiguration($output, $dryRun, $newValues)
    {
        global $userPasswordCrypted, $storeSessionInDb;

        $_configuration = $this->getConfigurationArray();

        // See http://zf2.readthedocs.org/en/latest/modules/zend.config.introduction.html

        if (!isset($_configuration['password_encryption']) && isset($userPasswordCrypted)) {
            $newValues['password_encryption'] = $userPasswordCrypted;
        }

        if (!empty($newValues)) {
            $_configuration = array_merge($_configuration, $newValues);
        }

        $config = new \Zend\Config\Config($_configuration, true);
        $writer = new \Zend\Config\Writer\PhpArray();
        $content = $writer->toString($config);

        $content = str_replace('return', '$_configuration = ', $content);
        $configurationPath = $this->getConfigurationPath();
        $newConfigurationFile = $configurationPath.'configuration.php';

        if ($dryRun == false) {
            file_put_contents($newConfigurationFile, $content);
            $output->writeln("<comment>File updated: $newConfigurationFile</comment>");
        } else {
            $output->writeln("<comment>File to be updated (dry-run is on): $newConfigurationFile</comment>");
            $output->writeln($content);
        }
        return file_exists($newConfigurationFile);
    }

    /**
     * Gets the SQL files relation with versions
     * @return array
     */
    public function getDatabaseMap()
    {

        $defaultCourseData = array(
            array(
                'name' => 'course1',
                'sql' => array(
                    'db_course1.sql',
                ),
            ),
            array(
                'name' => 'course2',
                'sql' => array(
                    'db_course2.sql'
                )
            ),
        );

        return array(
            '1.8.7' => array(
                'section' => array(
                    'main' => array(
                        array(
                            'name' => 'chamilo',
                            'sql' => array(
                                'db_main.sql',
                                'db_stats.sql',
                                'db_user.sql'
                            ),
                        ),
                    ),
                    'course' => $defaultCourseData
                ),
            ),
            '1.8.8' => array(
                'section' => array(
                    'main' => array(
                        array(
                            'name' => 'chamilo',
                            'sql' => array(
                                'db_main.sql',
                                'db_stats.sql',
                                'db_user.sql'
                            ),
                        ),
                    ),
                    'course' => $defaultCourseData
                ),
            ),
            '1.9.0' => array(
                'section' => array(
                    'main' => array(
                        array(
                            'name' => 'chamilo',
                            'sql' => array(
                                'db_course.sql',
                                'db_main.sql',
                                'db_stats.sql',
                                'db_user.sql'
                            ),
                        ),
                    ),
                )
            ),
            '1.10.0' => array(
                'section' => array(
                    'main' => array(
                        array(
                            'name' => 'chamilo',
                            'sql' => array(
                                'db_course.sql',
                                'db_main.sql'
                            ),
                        ),
                    ),
                )
            )
        );
    }

    /**
     * Set Doctrine settings
     */
    protected function setDoctrineSettings()
    {
        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $reader = new AnnotationReader();

        $driverImpl = new \Doctrine\ORM\Mapping\Driver\AnnotationDriver($reader, array());
        $config->setMetadataDriverImpl($driverImpl);
        $config->setProxyDir(__DIR__ . '/Proxies');
        $config->setProxyNamespace('Proxies');

        $em = \Doctrine\ORM\EntityManager::create($this->getDatabaseSettings(), $config);

        // Fixes some errors
        $platform = $em->getConnection()->getDatabasePlatform();
        $platform->registerDoctrineTypeMapping('enum', 'string');
        $platform->registerDoctrineTypeMapping('set', 'string');

        $helpers = array(
            'db' => new \Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper($em->getConnection()),
            'em' => new \Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper($em),
            'configuration' => new \Chash\Helpers\ConfigurationHelper()
        );

        foreach ($helpers as $name => $helper) {
            $this->getApplication()->getHelperSet()->set($helper, $name);
        }
    }

    /**
     * @param string $path
     * @param array $databaseList
     */
    protected function setConnections($path, $databaseList)
    {
        $_configuration = $this->getHelper('configuration')->getConfiguration($path);

        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $reader = new AnnotationReader();

        $driverImpl = new \Doctrine\ORM\Mapping\Driver\AnnotationDriver($reader, array());
        $config->setMetadataDriverImpl($driverImpl);
        $config->setProxyDir(__DIR__ . '/Proxies');
        $config->setProxyNamespace('Proxies');

        foreach ($databaseList as $section => &$dbList) {
            foreach ($dbList as &$dbInfo) {
                $params = $this->getDatabaseSettings();
                $evm = new \Doctrine\Common\EventManager;

                if ($section == 'course') {
                    $tablePrefix = new \Chash\DoctrineExtensions\TablePrefix($_configuration['table_prefix']);
                    $evm->addEventListener(\Doctrine\ORM\Events::loadClassMetadata, $tablePrefix);

                    $params['dbname'] = str_replace('_chamilo_course_', '', $dbInfo['database']);
                    $em = \Doctrine\ORM\EntityManager::create($params, $config, $evm);
                } else {
                    $em = \Doctrine\ORM\EntityManager::create($params, $config);
                }

                $helper = new \Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper($em->getConnection());
                /*var_dump($section);
                var_dump($dbInfo['database']);
                var_dump($em->getConnection()->getDatabase());*/
                $this->getApplication()->getHelperSet()->set($helper, $dbInfo['database']);
            }
        }
        //exit;
    }

    public function removeFiles($files, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        $dryRun = $this->getConfigurationHelper()->getDryRun();

        if (empty($files)) {
            $output->writeln('<comment>No files found.</comment>');
            return 0;
        }

        $fs = new Filesystem();
        try {
            if ($dryRun) {
                $output->writeln('<comment>Files to be removed:</comment>');
                foreach ($files as $file) {
                    $output->writeln($file->getPathName());
                }
            } else {
                $output->writeln('<comment>Removing files:</comment>');
                foreach ($files as $file) {
                    $output->writeln($file->getPathName());
                }
                $fs->remove($files);
            }

        } catch (IOException $e) {
            echo "\n An error occurred while removing the directory: ".$e->getMessage()."\n ";
        }
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param array $params
     */
    public function getParamsFromOptions(\Symfony\Component\Console\Input\InputInterface $input, array $params)
    {
        $filledParams = array();

        foreach ($params as $key => $value) {
            $newValue = $input->getOption($key);
            $filledParams[$key] = $newValue;
        }

        return $filledParams;
    }

}