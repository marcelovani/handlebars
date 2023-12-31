<?php

namespace Drupal\handlebars\Service;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
Use \Drupal\Core\File\FileUrlGeneratorInterface;
use Symfony\Component\Finder\Finder;

/**
 * Service for Handlebars templates.
 */
class HandlebarsService {

  public const HANDLEBARS_JS_DIR = 'public://handlebars';

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The list of translatable strings.
   *
   * @var array
   */
  protected $strings = [];

  /**
   * Constructor for Handlebars Service.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   File system service.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(
    FileSystemInterface $file_system,
    FileUrlGeneratorInterface $file_url_generator,
    ModuleHandlerInterface $module_handler,
    ConfigFactoryInterface $config_factory) {
    $this->fileSystem = $file_system;
    $this->fileUrlGenerator = $file_url_generator;
    $this->moduleHandler = $module_handler;
    $this->configFactory = $config_factory;
  }

  /**
   * Gets the relative path of a Uri.
   *
   * @param string $uri
   *   The file Uri.
   *
   * @return string
   *   The relative path.
   */
  protected function getRelativePath($uri) {
    $url = $this->fileUrlGenerator->generateString($uri);
    return $this->fileUrlGenerator->transformRelative($url);
  }

  /**
   * Returns the directory to store Handlebars js.
   *
   * @return string
   *   The public directory.
   */
  protected function getDir() {
    return self::HANDLEBARS_JS_DIR;
  }

  /**
   * Create folders for the parts of the path.
   *
   * @param string $path
   *   The initial path.
   *
   * @return string
   *   The string containing path.
   */
  protected function prepareDirectories($path) {
    $dir = $this->getDir();
    if (!file_exists($dir)) {
      $this->fileSystem->prepareDirectory($dir, FileSystemInterface::CREATE_DIRECTORY);
    }

    // Build list of folders.
    $dirs = explode('.', $path);

    // We don't need folders for the entire tree.
    $dirs = array_slice($dirs, 0, 2);

    foreach ($dirs as $subdir) {
      $dir = "$dir/$subdir";
      if (!file_exists($dir)) {
        $this->fileSystem->prepareDirectory($dir, FileSystemInterface::CREATE_DIRECTORY);
      }
    }

    return $dir;
  }

  /**
   * Checks if the string ends with searched string.
   *
   * @param string $haystack
   *   The string to search in.
   * @param string $needle
   *   The string to look for.
   *
   * @return bool
   *   TRUE/FALSE.
   */
  protected function endsWith($haystack, $needle) {
    return str_ends_with($haystack, $needle);
  }

  /**
   * Attaches Handlebars libraries.
   *
   * @param array $build
   *   The build array.
   * @param $context
   *   The context.
   */
  public function attachLibraries(array &$build, $context) {
    /** @var \Drupal\Core\Asset\LibraryDiscovery $library_discovery_service */
    // @todo Inject this
    $library_discovery_service = \Drupal::service('library.discovery');
    $handlebars_libraries = [];

    // Populate Handlebars libraries.
    foreach ($this->moduleHandler->getModuleList() as $extension) {
      $extension_name = $extension->getName();
      $libraries = $library_discovery_service->getLibrariesByExtension($extension_name);
      if (!empty($libraries)) {
        foreach ($libraries as $library_name => $library) {
          if ($library['type'] === 'handlebars_template') {
            // Associate library with extension;
            $handlebars_libraries[$library_name] = $extension_name;
          }
        }
      }
    }

    // Invoke hook to get default libraries.
    $other_handlebars_libraries = $this->moduleHandler->invokeAll('handlebars_templates', [$context]);

    // Merge list of libraries.
    $handlebars_libraries = array_merge($handlebars_libraries, $other_handlebars_libraries);

    // Allow other modules to alter libraries.
    $this->moduleHandler->alter('handlebars_templates', $handlebars_libraries, $context);

    // Attach libraries.
    foreach ($handlebars_libraries as $library_name => $extension) {
      $build['#attached']['library'][] = "$extension/$library_name";
    }
  }

  /**
   * Scans templates to find translatable strings.
   *
   * @param string $contents
   *   The contents of Handlebars templates.
   *
   * @return string
   *   The translatable strings.
   */
  protected function scanTranslatableStrings(string $contents) {
    $regex = "~
      {{\s*                                  # match opening brackets
      t\s\s*                                 # match t() helper
      [\"|'](.*?)[\"|']\s*                   # capture string
      ~sx";

    preg_match_all($regex, $contents, $matches);

    $strings = [];
    foreach ($matches[1] as $string) {
      $strings[] = "Drupal.t('$string');";
    }

    return implode('', array_unique($strings));
  }

  /**
   * Dynamically generates the js script.
   *
   * @param string $extension
   *   The module name.
   * @param string $id
   *   The library id.
   * @param string $path
   *   The path to the template.
   *
   * @return string
   *   The generated script.
   */
  protected function generateScript($extension, $id, $path) {
    // Load template.
    $module_path = $this->moduleHandler->getModule($extension)->getPath();
    $contents = file_get_contents("$module_path/$path");

    // Get Translation strings.
    $strings = $this->scanTranslatableStrings($contents);

    // Check if we can use precompiled handlebar templates.
    if ($this->preCompileEnabled()) {
      $path = str_replace(["handlebars/", ".handlebars"], ["dist/", '.js'], $path);
      $script = file_get_contents("$module_path/$path");
      $script .= "\n// $strings\n";
      return $script;
    }

    // Prepare script.
    $json = json_encode($contents, JSON_UNESCAPED_UNICODE);
    $script = "window.HandlebarsTemplates = window.HandlebarsTemplates || {};\n";
    $script .= "window.HandlebarsTemplates['$id'] = $json\n// $strings\n";

    return $script;
  }

  /**
   * Renders Handlebars templates as javascript libraries.
   *
   * @param array $libraries
   *   The list of libraries.
   * @param string $extension
   *   The module name.
   */
  public function libraryInfoAlter(array &$libraries, $extension) {
    foreach ($libraries as $id => &$library) {
      if (empty($library['js'])) {
        continue;
      }
      // Mark if the current library contains handlebars templates.
      $is_handlebars_library = NULL;
      foreach ($library['js'] as $path => $details) {
        // Check if this is a Handlebars template.
        if (!$this->endsWith($path, '.handlebars')) {
          continue;
        }
        $is_handlebars_library ??= TRUE;
        // Make sure that folder structure is created.
        $dir = $this->prepareDirectories("$extension/$id");
        $uri = $dir . '/' . basename($path) . '.js';
        $script = $this->generateScript($extension, $id, $path);
        $this->fileSystem->saveData($script, $uri, 1);

        // Replace the library path.
        $libraries[$id]['js'][$this->getRelativePath($uri)] = $details;
        unset($libraries[$id]['js'][$path]);
      }
      if ($is_handlebars_library) {
        $library['dependencies'] = empty($library['dependencies'])
          ? ['handlebars/main']
          : array_merge(
              $library['dependencies'],
              ['handlebars/main']
            );
      }
    }
  }

  /**
   * Checks if the setting to precompile templates is enabled.
   *
   * @return bool
   *   True if precompile is enabled.
   */
  public function preCompileEnabled() {
    // @todo: Create this config + schema + Admin form.
    $precompile = \Drupal::config('handlebars.settings')->get('precompile');
    $js_preprocess = \Drupal::config('system.performance')->get('js.preprocess');

    return ($precompile && $js_preprocess);
  }

  /**
   * Used to pre-compile Handlebars templates.
   * @todo: Call this function when Caches are cleared
   *    AND the handlebars compiler is available in the .bin folder
   */
  public function preCompileHandlebars() {
    if (!$this->preCompileEnabled()) {
      return;
    }

    // Find handlebar templates.
    $finder = new Finder();
    $finder->name('*.handlebars');
    $finder->in(DRUPAL_ROOT . '/modules');
    $finder->in(DRUPAL_ROOT . '/profiles');

    // Execute pre-compile command in all folders.
    foreach ($finder as $file) {
      // Prepare directory.
      $dist_dir = $this->fileUrlGenerator->generateString($this::HANDLEBARS_JS_DIR . '/dist');
      if (!file_exists($dist_dir)) {
        $this->fileSystem->prepareDirectory($dist_dir, FileSystemInterface::CREATE_DIRECTORY);
      }

      $docroot = DRUPAL_ROOT;
      $real_filename = $file->getRealPath();
      shell_exec("cd $docroot &&
        ./modules/contrib/handlebars/node_modules/.bin/handlebars $real_filename -f $dist_dir -n window.HandlebarsTemplates"
      );
    }
  }
}
