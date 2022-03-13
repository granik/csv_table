<?php

namespace Drupal\csv_table;

interface CsvTableHelperInterface {

  /**
   * Get CSV file by its path.
   *
   * @param string $path
   *   Path to CSV file.
   *
   * @return string|false
   */
  public function getCsvByPath(string $path);

  /**
   * Get remote CSV file.
   *
   * @param $url
   *   Remote file URL.
   *
   * @return mixed
   */
  public function getRemoteFileContents($url);

  /**
   * Get source CSV string.
   *
   * @param array $config
   *   Block Configuration.
   *
   * @return string
   */
  public function getCsvString(array $config);

  /**
   * Returns a Form API table element, with an executable header
   *
   * @param array $config
   *   Block configuration array.
   *
   * @return array|null
   */
  public function buildTable(array $config);

  /**
   * Decode the CSV into an Array no matter via which method it is received.
   *
   * @param array $config
   *   Block configuration.
   *
   * @return array|mixed
   */
  public function getCsvArray(array $config);

  /**
   * Make some actions if remote file is updated.
   *
   * @param array $config
   *   Block configuration.
   * @param int|null $lastmod
   *   Last known file modification timestamp.
   * @return bool|int
   */
  public function processRemoteFileUpdates(array $config, $lastmod);


  /**
   * Get cache tags names for block.
   *
   * @param array $config
   *
   * @return array
   */
  public function getCacheTagNames(array $config);

}