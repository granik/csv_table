<?php

namespace Drupal\csv_table;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\file\Entity\File;
use GuzzleHttp\ClientInterface;
use Symfony\Component\Serializer\Encoder\CsvEncoder;

/**
 * CSV Table Helper service class.
 */
class CsvTableHelper implements CsvTableHelperInterface {

  /**
   * Tag name for CSV file.
   */
  const FILE_CACHE_TAG = 'csv_file';

  /**
   * Http Client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Cache Tags Invalidator service.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * CsvTableHelper constructor.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   Http Client service.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   Cache tag invalidator.
   */
  public function __construct(ClientInterface $http_client, CacheTagsInvalidatorInterface $cache_tags_invalidator) {
    $this->httpClient = $http_client;
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
  }

  /**
   * {@inheritDoc}
   */
  public function getCsvByPath(string $path) {
    $is_local_path = substr($path, 0, 1) === '/';
    if ($is_local_path) {
      $path_local = DRUPAL_ROOT . $path;
      if (!file_exists($path_local)) {
        return FALSE;
      }

      return file_get_contents($path_local);
    }
    $file = $this->getRemoteFileContents($path);

    return $file;
  }

  /**
   * {@inheritDoc}
   */
  public function getRemoteFileContents($url) {
    $client = $this->httpClient;
    try {
      $request = $client->get($url);
      $contents = $request->getBody()->getContents();
    }
    catch (\Exception $e) {
      return FALSE;
    }

    return $contents;
  }

  /**
   * {@inheritDoc}
   */
  public function buildTable(array $config) {
    $csv = $this->getCsvArray($config);
    if (empty($csv) || !is_array($csv)) {
      // @todo watchdog log that the csv wasn't able to be loaded
      return NULL;
    }

    $table = [
      '#type' => 'table',
      '#header' => array_keys($csv[0]),
      '#rows' => $csv,
      '#caption' => 'CsvTableBlock',
    ];

    if (!empty($config['default_css'])) {
      $table['#attributes']['class'][] = 'csv-table__table';
    }

    $style = '';
    if (!empty($config['table_max_height'])) {
      $style .= 'max-height:' . $config['table_max_height'] . ';';
    }
    if (!empty($config['table_height'])) {
      $style .= 'height:' . $config['table_height'] . ';';
    }

    $table['#attributes']['style'] = !empty($style) ? $style : NULL;

    return $table;
  }

  /**
   * {@inheritDoc}
   */
  public function getCsvString(array $config) {
    $csv_str = NULL;

    if ($config['csv_method'] === 'path_to_csv') {
      $csv_str = $this->getCsvByPath($config['path_to_csv']);
    }

    if ($config['csv_method'] === 'csv_file' && isset($config['csv_file'][0])) {
      $file = File::load($config['csv_file'][0]);
      if ($file) {
        $csv_str = file_get_contents($file->getFileUri());
      }
    }
    if (!$csv_str) {
      return '';
    }

    // @todo check if it's a valid CSV string.
    return $csv_str;
  }

  /**
   * {@inheritDoc}
   */
  public function getCsvArray(array $config) {
    $csv_str = $this->getCsvString($config);
    $csvEncoder = new CsvEncoder();
    return $csvEncoder->decode(
      $csv_str,
      '',
      [
        CsvEncoder::DELIMITER_KEY => ';',
        CsvEncoder::ENCLOSURE_KEY => '"',
        CsvEncoder::ESCAPE_CHAR_KEY => '\\',
        CsvEncoder::KEY_SEPARATOR_KEY => '.',
      ]
    );
  }

  /**
   * {@inheritDoc}
   */
  public function processRemoteFileUpdates(array $config, $lastmod = NULL) {
    if ($config['csv_method'] !== 'path_to_csv') {
      return FALSE;
    }
    $lastmod_new = $this->getRemoteFileLastModified($config['path_to_csv']);

    // @todo log msg if the remote file doesn't exist anymore.
    // @todo if the remote file exists again -> invalidate tag (do we need it?)
    if ($lastmod < $lastmod_new || !$lastmod || !$lastmod_new) {
      $this->cacheTagsInvalidator->invalidateTags(
          $this->getCacheTagNames($config)
      );
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function getCacheTagNames($config) {
    $tags = [];
    if ($unique_id = $config['unique_string']) {
      $tags[] = self::FILE_CACHE_TAG . ':' . $unique_id;
    }
    return $tags;
  }

  /**
   * Gets last-modified header's value for remote file.
   *
   * @param string $url
   *   Remote file URL.
   *
   * @return int|false
   *   Timestamp or false.
   *
   * @throws \Exception
   */
  protected function getRemoteFileLastModified($url) {
    try {
      $request = $this->httpClient->head($url);
      $last_modified = $request->getHeader('last-modified')[0];
    }
    catch (\Exception $e) {
      return FALSE;
    }
    $date_object = new \DateTime($last_modified);

    return $date_object->getTimestamp();
  }

}
