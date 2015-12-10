<?php

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class RoboFile
 */
class RoboFile extends \Robo\Tasks {

  use \Boedah\Robo\Task\Drush\loadTasks;

  CONST DEV = 'development';
  CONST PROD = 'production';

  /**
   * @var \Twig_Environment
   */
  private $twig;

  /**
   * Class constructor.
   */
  public function __construct() {
    $loader = new Twig_Loader_Filesystem('build/templates');
    $this->twig = new Twig_Environment($loader);
  }

  /**
   * Build local environment.
   *
   * @param bool $interactive
   *
   * @throws \Robo\Exception\TaskException
   */
  public function buildDev($interactive = FALSE) {
    $properties = $this->loadProperties(RoboFile::DEV);

    $this->setupFilesystem($properties, RoboFile::DEV);
    $this->runMigrations($properties, RoboFile::DEV);

    if ($interactive) {
      $this->taskOpenBrowser($properties['domain'])->run();
    }
  }

  /**
   * Build prod environment.
   *
   * @throws \Robo\Exception\TaskException
   */
  public function buildProd() {
    $properties = $this->loadProperties(RoboFile::PROD);

    $this->setupFilesystem($properties, RoboFile::PROD);
    $this->runMigrations($properties, RoboFile::PROD);
  }

  /**
   * Recreate files directory and remove old settings.php file.
   */
  private function setupFilesystem($properties, $env) {
    $this->say('Setup filesystem');

    $config = $this->templateRender($env . '.html.twig', $properties);
    $this->taskWriteToFile('config/' . $env . '.yml')
      ->line($config)->run();

    $phinx = $this->templateRender('phinx.html.twig', $properties);
    $this->taskWriteToFile('phinx.yml')
      ->line($phinx)->run();

    $htaccess = $this->templateRender($env . '_htaccess.html.twig', $properties);
    $this->taskWriteToFile('web/.htaccess')
      ->line($htaccess)->run();
  }

  /**
   * Runs phinx migrations.
   */
  private function runMigrations($properties, $env) {
    $this->say('Run migrations');

    $this->taskExec('vendor/bin/phinx migrate --environment=' . $env)->run();
  }

  /**
   * Renders a template
   *
   * @param string $template
   * @param array $variables
   *
   * @return string
   */
  private function templateRender($template, $variables) {
    return $this->twig->render($template, $variables);
  }

  /**
   * Loads properties from file.
   *
   * @param $env
   *
   * @return array
   */
  private function loadProperties($env) {
    return Yaml::parse(file_get_contents('build/build.' . $env . '.yml'));
  }
}
