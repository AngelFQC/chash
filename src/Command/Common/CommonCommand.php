<?php

namespace Chash\Command\Common;

use Chash\Helpers\ConfigurationHelper;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ConnectionException;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Command\Command as AbstractCommand;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml;

/**
 * Class CommonCommand.
 */
class CommonCommand extends AbstractCommand
{
    public $portalSettings;
    public $databaseSettings;
    public $adminSettings;
    public $rootSys;
    public $configurationPath;
    public $configuration;
    public $extraDatabaseSettings;
    public $configurationHelper;
    private $migrationConfigurationFile;
    private $manager;

    public function __construct(ConfigurationHelper $configurationHelper)
    {
        $this->configurationHelper = $configurationHelper;

        // you *must* call the parent constructor
        parent::__construct();
    }

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
     * @return string
     */
    public function getConfigurationPath()
    {
        return $this->configurationPath;
    }

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

    public function setDatabaseSettings(array $databaseSettings)
    {
        $user = isset($databaseSettings['dbuser']) ? $databaseSettings['dbuser'] : $databaseSettings['user'];
        $password = isset($databaseSettings['dbpassword']) ? $databaseSettings['dbpassword'] : '';

        // Try db_port
        $dbPort = isset($databaseSettings['db_port']) ? $databaseSettings['db_port'] : null;

        // Try port
        if (empty($dbPort)) {
            $dbPort = isset($databaseSettings['port']) ? $databaseSettings['port'] : null;
        }

        $hostParts = explode(':', $databaseSettings['host']);
        if (isset($hostParts[1]) && !empty($hostParts[1])) {
            $dbPort = $hostParts[1];
            $databaseSettings['host'] = str_replace(':'.$dbPort, '', $databaseSettings['host']);
        }
        $this->databaseSettings = $databaseSettings;

        if (!empty($dbPort)) {
            $this->databaseSettings['port'] = $dbPort;
        }
        $this->databaseSettings['user'] = $user;
        $this->databaseSettings['password'] = $password;
    }

    /**
     * @return array
     */
    public function getDatabaseSettings()
    {
        return $this->databaseSettings;
    }

    public function setExtraDatabaseSettings(array $databaseSettings)
    {
        $this->extraDatabaseSettings = $databaseSettings;
    }

    public function getExtraDatabaseSettings()
    {
        return $this->extraDatabaseSettings;
    }

    public function setAdminSettings(array $adminSettings)
    {
        $this->adminSettings = $adminSettings;
    }

    /**
     * @return array
     */
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

    public function getCourseSysPath(): ?string
    {
        if (is_dir($this->getRootSys().'courses')) {
            return $this->getRootSys().'courses';
        }

        if (is_dir($this->getRootSys().'app/courses')) {
            return $this->getRootSys().'app/courses';
        }

        return null;
    }

    /**
     * @return string
     */
    public function getInstallationFolder()
    {
        $chashFolder = dirname(dirname(__DIR__));

        return $chashFolder.'/Resources/Database/';
    }

    /**
     * Gets the installation version path.
     *
     * @param string $version
     *
     * @return string
     */
    public function getInstallationPath($version)
    {
        if ('master' === $version) {
            $version = $this->getLatestVersion();
        }

        return $this->getInstallationFolder().$version.'/';
    }

    /**
     * Gets the version name folders located in main/install.
     *
     * @return array
     */
    public function getAvailableVersions()
    {
        $installPath = $this->getInstallationFolder();
        $dir = new \DirectoryIterator($installPath);
        $dirList = [];
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
        return [
            'firstname' => [
                'attributes' => [
                    'label' => 'Firstname',
                    'data' => 'John',
                ],
                'type' => 'text',
            ],
            'lastname' => [
                'attributes' => [
                    'label' => 'Lastname',
                    'data' => 'Doe',
                ],
                'type' => 'text',
            ],
            'username' => [
                'attributes' => [
                    'label' => 'Username',
                    'data' => 'admin',
                ],
                'type' => 'text',
            ],
            'password' => [
                'attributes' => [
                    'label' => 'Password',
                    'data' => 'admin',
                ],
                'type' => 'password',
            ],
            'email' => [
                'attributes' => [
                    'label' => 'Email',
                    'data' => 'admin@example.org',
                ],
                'type' => 'email',
            ],
            'language' => [
                'attributes' => [
                    'label' => 'Language',
                    'data' => 'english',
                ],
                'type' => 'text',
            ],
            'phone' => [
                'attributes' => [
                    'label' => 'Phone',
                    'data' => '123456',
                ],
                'type' => 'text',
            ],
        ];
    }

    /**
     * @return array
     */
    public function getPortalSettingsParams()
    {
        return [
            'sitename' => [
                'attributes' => [
                    'label' => 'Site name',
                    'data' => 'Campus Chamilo',
                ],
                'type' => 'text',
            ],
            'site_url' => [
                'attributes' => [
                    'label' => 'URL of site to install',
                    'data' => 'http://localhost/',
                ],
                'type' => 'text',
            ],
            'institution' => [
                'attributes' => [
                    'data' => 'Chamilo',
                ],
                'type' => 'text',
            ],
            'institution_url' => [
                'attributes' => [
                    'label' => 'Website of the institution',
                    'data' => 'https://chamilo.org/',
                ],
                'type' => 'text',
            ],
            'encrypt_method' => [
                'attributes' => [
                    'choices' => [
                        'bcrypt' => 'bcrypt',
                        'sha1' => 'sha1',
                        'md5' => 'md5',
                        'none' => 'none',
                    ],
                    'data' => 'sha1',
                ],
                'type' => 'choice',
            ],
            'permissions_for_new_directories' => [
                'attributes' => [
                    'data' => '0777',
                ],
                'type' => 'text',
            ],
            'permissions_for_new_files' => [
                'attributes' => [
                    'data' => '0666',
                ],
                'type' => 'text',
            ],
        ];
    }

    /**
     * Database parameters that are going to be parsed during the console/browser installation.
     *
     * @return array
     */
    public function getDatabaseSettingsParams()
    {
        return [
            'driver' => [
                'attributes' => [
                    'choices' => [
                            'pdo_mysql' => 'pdo_mysql',
                            'pdo_sqlite' => 'pdo_sqlite',
                            'pdo_pgsql' => 'pdo_pgsql',
                            'pdo_oci' => 'pdo_oci',
                            'ibm_db2' => 'ibm_db2',
                            'pdo_ibm' => 'pdo_ibm',
                            'pdo_sqlsrv' => 'pdo_sqlsrv',
                        ],
                    'data' => 'pdo_mysql',
                ],
                'type' => 'choice',
            ],
            'host' => [
                'attributes' => [
                    'label' => 'Host',
                    'data' => 'localhost',
                ],
                'type' => 'text',
            ],
            'port' => [
                'attributes' => [
                    'label' => 'Port',
                    'data' => '3306',
                ],
                'type' => 'text',
            ],
            'dbname' => [
                'attributes' => [
                    'label' => 'Database name',
                    'data' => 'chamilo',
                ],
                'type' => 'text',
            ],
            'dbuser' => [
                'attributes' => [
                    'label' => 'Database user',
                    'data' => 'root',
                ],
                'type' => 'text',
            ],
            'dbpassword' => [
                'attributes' => [
                    'label' => 'Database password',
                    'data' => 'root',
                ],
                'type' => 'password',
            ],
        ];
    }

    /**
     * @return string
     */
    public function getLatestVersion()
    {
        return '1.11.x';
    }

    /**
     * Gets the content of a version from the available versions.
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
     * Gets the min version available to migrate with this command.
     */
    public function getMinVersionSupportedByInstall()
    {
        return key($this->availableVersions());
    }

    /**
     * Gets an array with the supported versions to migrate.
     */
    public function getVersionNumberList(): array
    {
        $versionList = $this->availableVersions();
        $versionNumberList = [];
        foreach ($versionList as $version => $info) {
            $versionNumberList[] = $version;
        }

        return $versionNumberList;
    }

    /**
     * Gets an array with the settings for every supported version.
     */
    public function availableVersions(): array
    {
        return [
            '1.8.7' => [
                'require_update' => false,
            ],
            '1.8.8' => [
                'require_update' => true,
                'pre' => 'migrate-db-1.8.7-1.8.8-pre.sql',
                'post' => null,
                'update_db' => 'update-db-1.8.7-1.8.8.inc.php',
                //'update_files' => 'update-files-1.8.7-1.8.8.inc.php',
                'hook_to_doctrine_version' => '8', //see ChamiloLMS\Migrations\Version8.php file
            ],
            '1.8.8.2' => [
                'require_update' => false,
                'parent' => '1.8.8',
            ],
            '1.8.8.4' => [
                'require_update' => false,
                'parent' => '1.8.8',
            ],
            '1.8.8.6' => [
                'require_update' => false,
                'parent' => '1.8.8',
            ],
            '1.9.0' => [
                'require_update' => true,
                'pre' => 'migrate-db-1.8.8-1.9.0-pre.sql',
                'post' => null,
                'update_db' => 'update-db-1.8.8-1.9.0.inc.php',
                'update_files' => 'update-files-1.8.8-1.9.0.inc.php',
                'hook_to_doctrine_version' => '9',
            ],
            '1.9.2' => [
                'require_update' => false,
                'parent' => '1.9.0',
            ],
            '1.9.4' => [
                'require_update' => false,
                'parent' => '1.9.0',
            ],
            '1.9.6.1' => [
                'require_update' => false,
                'parent' => '1.9.0',
            ],
            '1.9.6' => [
                'require_update' => false,
                'parent' => '1.9.0',
            ],
            '1.9.8' => [
                'require_update' => false,
                'parent' => '1.9.0',
            ],
            '1.9.10' => [
                'require_update' => false,
                'parent' => '1.9.0',
            ],
            '1.9.10.2' => [
                'require_update' => false,
                'parent' => '1.9.0',
            ],
            '1.9.x' => [
              'require_update' => false,
              'parent' => '1.9.0',
            ],
            '1.10.0' => [
                'require_update' => true,
                'hook_to_doctrine_version' => '20160808110200',
                'migrations_directory' => 'app/Migrations/Schema/V110',
                'migrations_namespace' => 'Application\Migrations\Schema\V110',
                'migrations_yml' => 'V110.yml',
                'update_files' => 'update.php',
            ],
            '1.10.2' => [
                'require_update' => false,
                'parent' => '1.10.0',
            ],
            '1.10.4' => [
                'require_update' => false,
                'parent' => '1.10.0',
            ],
            '1.10.6' => [
                'require_update' => false,
                'parent' => '1.10.0',
            ],
            '1.10.8' => [
                'require_update' => false,
                'parent' => '1.10.0',
            ],
            '1.10.x' => [
                'require_update' => false,
                'parent' => '1.10.0',
            ],
            '1.11.0' => [
                'require_update' => true,
                'hook_to_doctrine_version' => '20161028123400',
                'migrations_directory' => 'app/Migrations/Schema/V111',
                'migrations_namespace' => 'Application\Migrations\Schema\V111',
                'migrations_yml' => 'V111.yml',
                'update_files' => 'update.php',
            ],
            '1.11.2' => [
                'require_update' => false,
                'parent' => '1.11.0',
            ],
            '1.11.4' => [
                'require_update' => false,
                'parent' => '1.11.0',
            ],
            '1.11.6' => [
               'require_update' => false,
                'parent' => '1.11.0',
            ],
            '1.11.x' => [
                'require_update' => false,
                'parent' => '1.11.0',
            ],
            '2.0' => [
                'require_update' => true,
                'update_files' => null,
                'hook_to_doctrine_version' => '2',
                'parent' => '2.0',
            ],
            'master' => [
                'require_update' => true,
                'update_files' => null,
                'hook_to_doctrine_version' => '2',
                'parent' => '2.0',
            ],
        ];
    }

    /**
     * Gets the Doctrine configuration file path.
     *
     * @return string
     */
    public function getMigrationConfigurationFile()
    {
        return $this->migrationConfigurationFile;
    }

    /**
     * @param string $file
     */
    public function setMigrationConfigurationFile($file)
    {
        $this->migrationConfigurationFile = $file;
    }

    /**
     * @return ConfigurationHelper
     */
    public function getConfigurationHelper()
    {
        return $this->configurationHelper;
    }

    /**
     * @todo move to configurationhelper
     *
     * @param string $path
     */
    public function setRootSysDependingConfigurationPath($path)
    {
        $configurationPath = $this->getConfigurationHelper()->getNewConfigurationPath($path);

        if (false == $configurationPath) {
            //  Seems an old installation!
            $configurationPath = $this->getConfigurationHelper()->getConfigurationPath($path);
            $this->setRootSys(realpath($configurationPath.'/../../../').'/');
        } else {
            // Chamilo installations >= 10
            $this->setRootSys(realpath($configurationPath.'/../../').'/');
        }
    }

    /**
     * Writes the configuration file for the first time (install command).
     *
     * @param string $version
     * @param string $path
     * @param object $output  Output handler to print info messages
     *
     * @return bool|int
     */
    public function writeConfiguration($version, $path, $output)
    {
        $output->writeln('');
        $output->writeln('<comment>Starting the writeConfiguration process.</comment>');
        $portalSettings = $this->getPortalSettings();
        $databaseSettings = $this->getDatabaseSettings();
        $configurationPath = $this->getConfigurationHelper()->getConfigurationPath($path);
        $output->writeln('<comment>Recovered all info. Reviewing.</comment>');

        // Creates a YML File
        $configuration = [];
        $configuration['db_host'] = $databaseSettings['host'];
        $configuration['db_port'] = $databaseSettings['port'];
        $configuration['db_user'] = $databaseSettings['dbuser'];
        $configuration['db_password'] = $databaseSettings['dbpassword'];
        $configuration['main_database'] = $databaseSettings['dbname'];
        $configuration['driver'] = $databaseSettings['driver'];
        $configuration['root_web'] = $portalSettings['site_url'];
        $configuration['root_sys'] = $this->getRootSys();
        $configuration['security_key'] = md5(uniqid(rand().time()));

        // Hash function method
        $configuration['password_encryption'] = $portalSettings['encrypt_method'];
        // Session lifetime
        $configuration['session_lifetime'] = 3600;
        // Activation for multi-url access
        $configuration['multiple_access_urls'] = false;
        //Deny the elimination of users
        $configuration['deny_delete_users'] = false;
        //Prevent all admins from using the "login_as" feature
        $configuration['login_as_forbidden_globally'] = false;

        // Version settings
        $configuration['system_version'] = $version;
        $output->writeln('<comment>Data reviewed. Checking where to write to...</comment>');

        if (file_exists($this->getRootSys().'config/parameters.yml.dist')) {
            $output->writeln('<comment>parameters.yml.dist file found.</comment>');

            $file = $this->getRootSys().'config/parameters.yml';
            if (!file_exists($file)) {
                $contents = file_get_contents($file);
                $yamlParser = new Parser();
                $expectedValues = $yamlParser->parse($contents);

                $expectedValues['database_driver'] = $configuration['driver'];
                $expectedValues['database_host'] = $configuration['db_host'];
                $expectedValues['database_port'] = $configuration['db_port'];
                $expectedValues['database_name'] = $configuration['main_database'];
                $expectedValues['database_user'] = $configuration['db_user'];
                $expectedValues['database_password'] = $configuration['db_password'];
                $expectedValues['password_encryption'] = $configuration['password_encryption'];

                $result = file_put_contents($file, Yaml::dump(['parameters' => $expectedValues], 99));
            } else {
                return true;
            }
        } else {
            // Try the old one
            $output->writeln('<comment>Looking for main/install/configuration.dist.php.</comment>');

            $contents = file_get_contents($this->getRootSys().'main/install/configuration.dist.php');

            $config['{DATE_GENERATED}'] = date('r');
            $config['{DATABASE_HOST}'] = $configuration['db_host'];
            $config['{DATABASE_PORT}'] = $configuration['db_port'];
            $config['{DATABASE_USER}'] = $configuration['db_user'];
            $config['{DATABASE_PASSWORD}'] = $configuration['db_password'];
            $config['{DATABASE_MAIN}'] = $configuration['main_database'];
            $config['{DATABASE_DRIVER}'] = $configuration['driver'];

            $config['{COURSE_TABLE_PREFIX}'] = '';
            $config['{DATABASE_GLUE}'] = '`.`'; // keeping for backward compatibility
            $config['{DATABASE_PREFIX}'] = '';
            $config['{DATABASE_STATS}'] = $configuration['main_database'];
            $config['{DATABASE_SCORM}'] = $configuration['main_database'];
            $config['{DATABASE_PERSONAL}'] = $configuration['main_database'];
            $config['TRACKING_ENABLED'] = "'true'";
            $config['SINGLE_DATABASE'] = 'false';

            $config['{ROOT_WEB}'] = $portalSettings['site_url'];
            $config['{ROOT_SYS}'] = $this->getRootSys();

            $config['{URL_APPEND_PATH}'] = '';
            $config['{SECURITY_KEY}'] = $configuration['security_key'];
            $config['{ENCRYPT_PASSWORD}'] = $configuration['password_encryption'];

            $config['SESSION_LIFETIME'] = 3600;
            $config['{NEW_VERSION}'] = $version;
            $config['NEW_VERSION_STABLE'] = 'true';

            foreach ($config as $key => $value) {
                $contents = str_replace($key, $value, $contents);
            }

            $newConfigurationFile = $configurationPath.'configuration.php';
            $output->writeln(sprintf('<comment>Writing config to %s</comment>', $newConfigurationFile));

            $result = file_put_contents($newConfigurationFile, $contents);
            $output->writeln('<comment>Config file written.</comment>');
        }

        return $result;
    }

    /**
     * Updates the configuration.yml file.
     *
     * @param bool  $dryRun
     * @param array $newValues
     *
     * @return bool
     */
    public function updateConfiguration(OutputInterface $output, $dryRun, $newValues)
    {
        $this->getConfigurationPath();

        $_configuration = $this->getConfigurationArray();

        // Merging changes
        if (!empty($newValues)) {
            $_configuration = array_merge($_configuration, $newValues);
        }

        $paramsToRemove = [
            'tracking_enabled',
            //'single_database', // still needed fro version 1.9.8
            //'table_prefix',
            //'db_glue',
            'db_prefix',
            //'url_append',
            'statistics_database',
            'user_personal_database',
            'scorm_database',
        ];

        foreach ($_configuration as $key => $value) {
            if (in_array($key, $paramsToRemove)) {
                unset($_configuration[$key]);
            }
        }

        // See http://zf2.readthedocs.org/en/latest/modules/zend.config.introduction.html
        $config = new \Zend\Config\Config($_configuration, true);
        $writer = new \Zend\Config\Writer\PhpArray();
        $content = $writer->toString($config);

        $content = str_replace('return', '$_configuration = ', $content);
        $configurationPath = $this->getConfigurationPath();
        $newConfigurationFile = $configurationPath.'configuration.php';

        if (false == $dryRun) {
            if (version_compare($newValues['system_version'], '1.10', '>=') ||
                ('1.10.x' == $newValues['system_version'] || '1.11.x' == $newValues['system_version'])
            ) {
                $configurationPath = $_configuration['root_sys'].'app/config/';
                $newConfigurationFile = $configurationPath.'configuration.php';
            }
            file_put_contents($newConfigurationFile, $content);
            $output->writeln("<comment>File updated: $newConfigurationFile</comment>");
        } else {
            $output->writeln("<comment>File to be updated (dry-run is on): $newConfigurationFile</comment>");
            $output->writeln($content);
        }

        return file_exists($newConfigurationFile);
    }

    /**
     * Gets the SQL files relation with versions.
     *
     * @return array
     */
    public function getDatabaseMap()
    {
        $defaultCourseData = [
            [
                'name' => 'course1',
                'sql' => [
                    'db_course1.sql',
                ],
            ],
            [
                'name' => 'course2',
                'sql' => [
                    'db_course2.sql',
                ],
            ],
        ];

        return [
            '1.8.7' => [
                'section' => [
                    'main' => [
                        [
                            'name' => 'chamilo',
                            'sql' => [
                                'db_main.sql',
                                'db_stats.sql',
                                'db_user.sql',
                            ],
                        ],
                    ],
                    'course' => $defaultCourseData,
                ],
            ],
            '1.8.8' => [
                'section' => [
                    'main' => [
                        [
                            'name' => 'chamilo',
                            'sql' => [
                                'db_main.sql',
                                'db_stats.sql',
                                'db_user.sql',
                            ],
                        ],
                    ],
                    'course' => $defaultCourseData,
                ],
            ],
            '1.9.0' => [
                'section' => [
                    'main' => [
                        [
                            'name' => 'chamilo',
                            'sql' => [
                                'db_course.sql',
                                'db_main.sql',
                                'db_stats.sql',
                                'db_user.sql',
                            ],
                        ],
                    ],
                ],
            ],
            '1.10.0' => [
                'section' => [
                    'migrations' => 'Version110',
                ],
            ],
            '1.11.0' => [
                'section' => [
                    'migrations' => 'Version111',
                ],
            ],
            '2.0' => [
                'section' => [
                    'migrations' => 'Version200',
                ],
            ],
            'master' => [
                'section' => [
                    'migrations' => 'Version200',
                ],
            ],
        ];
    }

    /**
     * @param Finder $files
     *
     * @return int
     */
    public function removeFiles($files, OutputInterface $output)
    {
        $dryRun = $this->getConfigurationHelper()->getDryRun();

        if (empty($files)) {
            $output->writeln('<comment>No files found.</comment>');

            return 0;
        }

        if ($files->count() < 1) {
            $output->writeln('<comment>No files found.</comment>');

            return 0;
        }

        $fs = new Filesystem();

        try {
            if ($dryRun) {
                $output->writeln('<comment>Files to be removed (--dry-run is on).</comment>');
                foreach ($files as $file) {
                    $output->writeln($file->getPathName());
                }
            } else {
                $output->writeln('<comment>Removing start.</comment>');
                /* foreach ($files as $file) {
                    $output->writeln($file->getPathName());
                }*/
                $fs->remove($files);
                $output->writeln('<comment>Removing files end.</comment>');
            }
        } catch (IOException $e) {
            echo "\n An error occurred while removing the directory: ".$e->getMessage()."\n ";
        }

        return 0;
    }

    /**
     * @return array
     */
    public function getParamsFromOptions(InputInterface $input, array $params)
    {
        $filledParams = [];

        foreach ($params as $key => $value) {
            $newValue = $input->getOption($key);
            $filledParams[$key] = $newValue;
        }

        return $filledParams;
    }

    /**
     * @param string $version
     * @param string $updateInstallation
     * @param string $defaultTempFolder
     *
     * @return int|string|null
     */
    public function getPackage(OutputInterface $output, $version, $updateInstallation, $defaultTempFolder)
    {
        $fs = new Filesystem();
        $versionTag = $version;

        // Download the chamilo package from from github:
        if (empty($updateInstallation)) {
            $updateInstallation = 'https://github.com/chamilo/chamilo-lms/archive/v'.$version.'.zip';

            switch ($version) {
                case 'master':
                    $updateInstallation = 'https://github.com/chamilo/chamilo-lms/archive/master.zip';

                    break;
                case '1.9.x':
                    $updateInstallation = 'https://github.com/chamilo/chamilo-lms/archive/1.9.x.zip';

                    break;
                case '1.10.x':
                    $updateInstallation = 'https://github.com/chamilo/chamilo-lms/archive/1.10.x.zip';

                    break;
                case '1.11.x':
                    $updateInstallation = 'https://github.com/chamilo/chamilo-lms/archive/1.11.x.zip';

                    break;
            }
        }

        if (!empty($updateInstallation)) {
            // Check temp folder
            if (!is_writable($defaultTempFolder)) {
                $output->writeln("<comment>We don't have permissions to write in the temp folder: $defaultTempFolder</comment>");

                return 0;
            }

            // Download file?
            if (false === strpos($updateInstallation, 'http')) {
                if (!file_exists($updateInstallation)) {
                    $output->writeln("<comment>File does not exists: $updateInstallation</comment>");

                    return 0;
                }
            } else {
                $urlInfo = parse_url($updateInstallation);

                $updateInstallationLocalName = $defaultTempFolder.'/'.basename($urlInfo['path']);
                if (!file_exists($updateInstallationLocalName)) {
                    $output->writeln("<comment>Executing</comment> <info>wget -O $updateInstallationLocalName '$updateInstallation'</info>");
                    $output->writeln('');

                    $execute = 'wget -O '.$updateInstallationLocalName." '$updateInstallation'\n";

                    $systemOutput = shell_exec($execute);

                    $systemOutput = str_replace("\n", "\n\t", $systemOutput);
                    $output->writeln($systemOutput);
                } else {
                    $output->writeln('<comment>Seems that the chamilo v'.$version." has been already downloaded. File location:</comment> <info>$updateInstallationLocalName</info>");
                }

                $updateInstallation = $updateInstallationLocalName;

                if (!file_exists($updateInstallationLocalName)) {
                    $output->writeln("<error>Can't download the file!</error>");
                    $output->writeln("<comment>Check if you can download this file in your browser first:</comment> <info>$updateInstallation</info>");

                    return 0;
                }
            }

            if (file_exists($updateInstallation)) {
                $zip = new \ZipArchive();
                $res = $zip->open($updateInstallation);

                $folderPath = $defaultTempFolder.'/chamilo-v'.$version.'-'.date('y-m-d');

                if (!is_dir($folderPath)) {
                    $fs->mkdir($folderPath);
                } else {
                    // Load from cache
                    $chamiloPath = $folderPath.'/chamilo-lms-CHAMILO_'.$versionTag.'_STABLE/main/inc/global.inc.php';
                    if (file_exists($chamiloPath)) {
                        $output->writeln('<comment>Files have been already extracted here: </comment><info>'.$folderPath.'/chamilo-lms-CHAMILO_'.$versionTag.'_STABLE/'.'</info>');

                        return $folderPath.'/chamilo-lms-CHAMILO_'.$versionTag.'_STABLE/';
                    }
                }

                $chamiloLocationPath = '';
                if (is_dir($folderPath) && $res) {
                    $output->writeln("<comment>Extracting files here:</comment> <info>$folderPath</info>");

                    $zip->extractTo($folderPath);

                    $finder = new Finder();
                    $files = $finder->in($folderPath)->depth(0);
                    /** @var \SplFileInfo $file */
                    foreach ($files as $file) {
                        $chamiloLocationPath = $file->getRealPath();

                        break;
                    }
                }

                if (empty($chamiloLocationPath)) {
                    $output->writeln('<error>Chamilo folder structure not found in package.</error>');

                    return 0;
                }

                return $chamiloLocationPath;
            } else {
                $output->writeln("<comment>File doesn't exist.</comment>");

                return 0;
            }
        }

        return 0;
    }

    /**
     * @param array  $_configuration
     * @param string $courseDatabase
     *
     * @return string|null
     */
    public function getTablePrefix($_configuration, $courseDatabase = null)
    {
        $singleDatabase = isset($_configuration['single_database']) ? $_configuration['single_database'] : false;
        $tablePrefix = isset($_configuration['table_prefix']) ? $_configuration['table_prefix'] : null;

        if ($singleDatabase) {
            // the $courseDatabase already contains the $db_prefix;
            $prefix = $tablePrefix.$courseDatabase.'_';
        } else {
            $prefix = $tablePrefix;
        }

        return $prefix;
    }

    /**
     * @param string $chamiloLocationPath
     * @param string $destinationPath
     *
     * @return int
     */
    public function copyPackageIntoSystem(
        OutputInterface $output,
        $chamiloLocationPath,
        $destinationPath
    ) {
        $fileSystem = new Filesystem();

        if (empty($destinationPath)) {
            $destinationPath = $this->getRootSys();
        }

        if (empty($chamiloLocationPath)) {
            $output->writeln('<error>The chamiloLocationPath variable is empty<error>');

            return 0;
        }

        $output->writeln("<comment>Copying files from </comment><info>$chamiloLocationPath</info><comment> to </comment><info>".$destinationPath.'</info>');

        if (empty($destinationPath)) {
            $output->writeln('<error>The root path was not set.<error>');

            return 0;
        } else {
            $fileSystem->mirror($chamiloLocationPath, $destinationPath, null, ['override' => true]);
            $output->writeln('<comment>Copy finished.<comment>');

            return 1;
        }
    }

    /**
     * @param string $title
     */
    public function writeCommandHeader(OutputInterface $output, $title)
    {
        $output->writeln('<comment>-----------------------------------------------</comment>');
        $output->writeln('<comment>'.$title.'</comment>');
        $output->writeln('<comment>-----------------------------------------------</comment>');
    }

    /**
     * Returns the config file list.
     *
     * @return array
     */
    public function getConfigFiles()
    {
        return [
            'portfolio.conf.dist.php',
            'events.conf.dist.php',
            'add_course.conf.dist.php',
            'mail.conf.dist.php',
            'auth.conf.dist.php',
            'profile.conf.dist.php',
            'course_info.conf.php',
        ];
    }

    public function generateConfFiles(OutputInterface $output)
    {
        $confDir = $this->getConfigurationPath();
        $fs = new Filesystem();

        $configList = $this->getConfigFiles();
        foreach ($configList as $file) {
            if (file_exists($confDir.$file)) {
                $newConfFile = $confDir.str_replace('dist.', '', $file);
                if (!file_exists($newConfFile)) {
                    $fs->copy($confDir.$file, $newConfFile);
                    $output->writeln("<comment>File generated:</comment> <info>$newConfFile</info>");
                }
            }
        }
    }

    /**
     * Copy files from main/inc/conf to the new location config.
     */
    public function copyConfigFilesToNewLocation(OutputInterface $output)
    {
        $output->writeln('<comment>Copy files to new location</comment>');
        // old config main/inc/conf
        $confDir = $this->getConfigurationPath();

        $configurationPath = $this->getConfigurationHelper()->convertOldConfigurationPathToNewPath($confDir);

        $fs = new Filesystem();
        $configList = $this->getConfigFiles();
        $configList[] = 'configuration.dist.php';
        foreach ($configList as $file) {
            // This file contains a get_lang that cause a fatal error.
            if (in_array($file, ['events.conf.dist.php', 'mail.conf.dist.php'])) {
                continue;
            }
            $configFile = str_replace('dist.', '', $file);

            if (file_exists($confDir.$configFile)) {
                $output->writeln('<comment> Moving file from: </comment>'.$confDir.$configFile);
                $output->writeln('<comment> to: </comment>'.$configurationPath.$configFile);
                if (!file_exists($configurationPath.$configFile)) {
                    $fs->copy($confDir.$configFile, $configurationPath.$configFile);
                }
            } else {
                $output->writeln('<comment> File not found: </comment>'.$confDir.$configFile);
            }
        }

        $backupConfPath = str_replace('inc/conf', 'inc/conf_old', $confDir);
        if ($confDir != $backupConfPath) {
            if (!is_dir($backupConfPath)) {
                $fs->rename($confDir, $backupConfPath);
            } else {
                $output->writeln('<comment>Removing previous old conf :</comment>'.$backupConfPath.'');
                $fs->remove($backupConfPath);
                $fs->rename($confDir, $backupConfPath);
            }
            $output->writeln('<comment>Renaming conf folder: </comment>'.$confDir.' to '.$backupConfPath.'');
        } else {
            $output->writeln('<comment>No need to rename the conf folder: </comment>'.$confDir.' = '.$backupConfPath.'');
        }
        $this->setConfigurationPath($configurationPath);
    }

    /**
     * @param $path
     * @param bool|string|string[]|null $path
     */
    public function removeUnUsedFiles(OutputInterface $output, $path)
    {
        $output->writeln('<comment>Removing unused files</comment>');
        $fs = new Filesystem();

        $list = [
            'archive',
            'config/course_info.conf.php',
        ];

        foreach ($list as $file) {
            $filePath = $path.'/'.$file;
            if ($fs->exists($filePath)) {
                $output->writeln('<comment>Removing: </comment>'.$filePath);
                $fs->remove($filePath);
            }
        }
    }

    public function setPortalSettingsInChamilo(OutputInterface $output, Connection $connection)
    {
        // Admin settings
        $adminSettings = $this->getAdminSettings();

        $connection->update(
            'settings_current',
            ['selected_value' => $adminSettings['email']],
            ['variable' => 'emailAdministrator']
        );
        $connection->update(
            'settings_current',
            ['selected_value' => $adminSettings['lastname']],
            ['variable' => 'administratorSurname']
        );
        $connection->update(
            'settings_current',
            ['selected_value' => $adminSettings['firstname']],
            ['variable' => 'administratorName']
        );
        $connection->update(
            'settings_current',
            ['selected_value' => $adminSettings['language']],
            ['variable' => 'platformLanguage']
        );

        // Portal settings.
        $settings = $this->getPortalSettings();

        $connection->update(
            'settings_current',
            ['selected_value' => 1],
            ['variable' => 'allow_registration']
        );

        $connection->update(
            'settings_current',
            ['selected_value' => 1],
            ['variable' => 'allow_registration_as_teacher']
        );

        $connection->update(
            'settings_current',
            ['selected_value' => $settings['permissions_for_new_directories']],
            ['variable' => 'permissions_for_new_directories']
        );

        $connection->update(
            'settings_current',
            ['selected_value' => $settings['permissions_for_new_files']],
            ['variable' => 'permissions_for_new_files']
        );

        $connection->update(
            'settings_current',
            ['selected_value' => $settings['institution']],
            ['variable' => 'Institution']
        );

        $connection->update(
            'settings_current',
            ['selected_value' => $settings['institution_url']],
            ['variable' => 'InstitutionUrl']
        );

        $connection->update(
            'settings_current',
            ['selected_value' => $settings['sitename']],
            ['variable' => 'siteName']
        );
    }

    public function setAdminSettingsInChamilo(OutputInterface $output, Connection $connection)
    {
        $settings = $this->getAdminSettings();
        // Password already set by the Chamilo
        //$settings['password'] = $this->getEncryptedPassword($settings['password']);

        $connection->update('user', ['auth_source' => 'platform'], ['user_id' => '1']);
        $connection->update('user', ['username' => $settings['username']], ['user_id' => '1']);
        $connection->update('user', ['firstname' => $settings['firstname']], ['user_id' => '1']);
        $connection->update('user', ['lastname' => $settings['lastname']], ['user_id' => '1']);
        $connection->update('user', ['phone' => $settings['phone']], ['user_id' => '1']);
        //$connection->update('user', array('password' => $settings['password']), array('user_id' => '1'));
        $connection->update('user', ['email' => $settings['email']], ['user_id' => '1']);
        // Admin user.
        $connection->update('user', ['language' => $settings['language']], ['user_id' => '1']);
        // Anonymous user.
        $connection->update('user', ['language' => $settings['language']], ['user_id' => '2']);
    }

    /**
     * Generates password.
     *
     * @param string $password
     * @param string $salt
     *
     * @return string
     */
    public function getEncryptedPassword($password, $salt = null)
    {
        $configuration = $this->getConfigurationArray();
        $encryptionMethod = isset($configuration['password_encryption']) ? $configuration['password_encryption'] : null;

        switch ($encryptionMethod) {
            case 'sha1':
                return empty($salt) ? sha1($password) : sha1($password.$salt);
            case 'none':
                return $password;
            case 'md5':
            default:
                return empty($salt) ? md5($password) : md5($password.$salt);
        }
    }

    /**
     * @return EntityManager
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * @param EntityManager $manager
     */
    public function setManager($manager)
    {
        $this->manager = $manager;
    }

    /**
     * Set Doctrine settings.
     *
     * @return \Doctrine\ORM\EntityManager
     */
    protected function setDoctrineSettings(HelperSet $helperSet)
    {
        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache());
        $reader = new AnnotationReader();
        $driverImpl = new \Doctrine\ORM\Mapping\Driver\AnnotationDriver(
            $reader,
            []
        );
        $config->setMetadataDriverImpl($driverImpl);
        $config->setProxyDir(__DIR__.'/Proxies');
        $config->setProxyNamespace('Proxies');
        $settings = $this->getDatabaseSettings();
        $dbName = $settings['dbname'];
        unset($settings['dbname']);

        $em = \Doctrine\ORM\EntityManager::create(
            $settings,
            $config
        );

        try {
            $connection = $em->getConnection();
            $dbList = $connection->getSchemaManager()->listDatabases();
            // Check in db exists in list.
            if (in_array($dbName, $dbList)) {
                $settings['dbname'] = $dbName;
                $em = \Doctrine\ORM\EntityManager::create(
                    $settings,
                    $config
                );
            }
        } catch (ConnectionException $e) {
            echo $e->getMessage();
        }

        $platform = $em->getConnection()->getDatabasePlatform();
        $platform->registerDoctrineTypeMapping('enum', 'string');
        $platform->registerDoctrineTypeMapping('set', 'string');

        $helpers = [
            'db' => new \Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper(
                $em->getConnection()
            ),
            'em' => new \Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper(
                $em
            ),
            //'configuration' => new \Chash\Helpers\ConfigurationHelper()
        ];

        foreach ($helpers as $name => $helper) {
            $helperSet->set(
                $helper,
                $name
            );
        }

        return $em;
    }

    /**
     * @param string $version
     * @param string $path
     * @param array  $databaseList
     */
    protected function setConnections($version, $path, $databaseList)
    {
        $_configuration = $this->getHelper('configuration')->getConfiguration($path);

        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache());
        $reader = new AnnotationReader();

        $driverImpl = new \Doctrine\ORM\Mapping\Driver\AnnotationDriver($reader, []);
        $config->setMetadataDriverImpl($driverImpl);
        $config->setProxyDir(__DIR__.'/Proxies');
        $config->setProxyNamespace('Proxies');

        foreach ($databaseList as $section => &$dbList) {
            foreach ($dbList as &$dbInfo) {
                $params = $this->getDatabaseSettings();

                if (isset($_configuration['single_database']) && true == $_configuration['single_database']) {
                    $em = \Doctrine\ORM\EntityManager::create($params, $config);
                } else {
                    if ('course' == $section) {
                        if (version_compare($version, '10', '<=')) {
                            if (false === strpos($dbInfo['database'], '_chamilo_course_')) {
                                //$params['dbname'] = $params['dbname'];
                            } else {
                                $params['dbname'] = str_replace('_chamilo_course_', '', $dbInfo['database']);
                            }
                        }
                        $em = \Doctrine\ORM\EntityManager::create($params, $config);
                    } else {
                        $databaseName = $params['dbname'];
                        switch ($dbInfo['database']) {
                            case 'statistics_database':
                                $databaseName = isset($_configuration['statistics_database']) ? $_configuration['statistics_database'] : $databaseName;

                                break;
                            case 'user_personal_database':
                                $databaseName = isset($_configuration['user_personal_database']) ? $_configuration['user_personal_database'] : $databaseName;

                                break;
                        }
                        $params['dbname'] = $databaseName;
                        $em = \Doctrine\ORM\EntityManager::create($params, $config);
                    }
                }

                if (!empty($em)) {
                    $platform = $em->getConnection()->getDatabasePlatform();
                    $platform->registerDoctrineTypeMapping('enum', 'string');
                    $platform->registerDoctrineTypeMapping('set', 'string');
                }

                $helper = new \Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper($em->getConnection());
                $this->getApplication()->getHelperSet()->set($helper, $dbInfo['database']);
            }
        }
    }
}
