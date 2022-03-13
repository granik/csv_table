<?php

namespace Drupal\csv_table\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'CsvTableBlock' block.
 *
 * @Block(
 *  id = "csv_table_block",
 *  admin_label = @Translation("CSV Table block"),
 * )
 */
class CsvTableBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'csv_method' => 'csv_file',
      'csv_file' => NULL,
      'path_to_csv' => '',
      'show_header' => '1',
      'show_filter' => '1',
      'table_height' => '100%',
      'table_max_height' => '',
      'use_sticky' => '1',
      'default_css' => '0',
      'unique_string' => NULL,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['csv_method'] = [
      '#type' => 'radios',
      '#title' => $this->t('Method'),
      '#description' => $this->t('Chose how the CSV will be provided.<br>
        Note the following formatting options:
        <ul>
          <li>delimiter char: ;</li>
          <li>enclosure char: "</li>
          <li>escape char: \</li>
          <li>key char: .</li>
        </ul>'),
      '#default_value' => $this->configuration['csv_method'],
      '#options' => [
        'csv_file' => $this->t('Upload CSV File'),
        'path_to_csv' => $this->t('Path to CSV (local or remote)'),
      ],
      '#attributes' => [
        'class' => ['field_csv_method'],
      ],
    ];
    $form['container_csv_file'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input.field_csv_method' => ['value' => 'csv_file'],
        ],
      ],
    ];
    $form['container_csv_file']['csv_file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('CSV Upload'),
      '#default_value' => $this->configuration['csv_file'],
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
      ],
      // @todo add a setting for this?
      '#upload_location' => 'private://csv_table/',
    ];
    $form['path_to_csv'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path to CSV'),
      '#default_value' => $this->configuration['path_to_csv'],
      '#maxlength' => 512,
      '#size' => 64,
      '#states' => [
        'visible' => [
          ':input.field_csv_method' => ['value' => 'path_to_csv'],
        ],
      ],
    ];
    $form['show_header'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Table header'),
      '#default_value' => $this->configuration['show_header'],
    ];
    $form['show_filter'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Filter input'),
      '#default_value' => $this->configuration['show_filter'],
    ];
    $form['table_height'] = [
      '#type' => 'textfield',
      '#size' => 60,
      '#maxlength' => 128,
      '#title' => $this->t('Inline CSS Height'),
      '#default_value' => $this->configuration['table_height'],
      '#description' => $this->t('Set if the table should always stay 
      the same height e.g. 90vh, 500em, etc'),
    ];
    $form['table_max_height'] = [
      '#type' => 'textfield',
      '#size' => 60,
      '#maxlength' => 128,
      '#title' => $this->t('Inline CSS Max-height'),
      '#default_value' => $this->configuration['table_max_height'],
      '#description' => $this->t('Set if the table should have a max height 
      and can be also smaller e.g. 90vh, 500em, etc'),
    ];
    $form['use_sticky'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Sticky Table Header'),
      '#default_value' => $this->configuration['use_sticky'],
      '#description' => $this->t('Uses advanced CSS'),
    ];
    $form['default_css'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Provide default CSS'),
      '#default_value' => $this->configuration['default_css'],
      '#description' => $this->t('Enable if you like to use CSS provided by module'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    parent::blockValidate($form, $form_state);

    if ($form_state->getValue('csv_method') === 'csv_file'
      && empty($form_state->getValue(['container_csv_file', 'csv_file']))) {
      $form_state->setErrorByName(
        'container_csv_file',
        $this->t('File required')
      );
    }

    if ($form_state->getValue('csv_method') === 'path_to_csv'
      && empty(trim($form_state->getValue('path_to_csv')))) {
      $form_state->setErrorByName(
        'path_to_csv',
        $this->t('Path required')
      );
    }

    $css_length_regex = "/^(\d+)(cm|mm|in|px|pt|pc|em|ex|ch|rem|vw|vh|vmin|vmax|%)$/";
    $heights_to_validate = [
      'table_max_height',
      'table_height',
    ];

    foreach ($heights_to_validate as $key) {
      $value = $form_state->getValue($key);
      if (!empty($value) && !preg_match($css_length_regex, $value)) {
        $form_state->setErrorByName($key, $this->t('The value is not valid'));
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['csv_method'] = $form_state->getValue('csv_method');
    $this->configuration['csv_file'] = $form_state->getValue(
      ['container_csv_file', 'csv_file']
    );
    $this->configuration['path_to_csv'] = trim($form_state->getValue('path_to_csv'));
    $this->configuration['show_header'] = $form_state->getValue('show_header');
    $this->configuration['show_filter'] = $form_state->getValue('show_filter');
    $this->configuration['table_height'] = $form_state->getValue('table_height');
    $this->configuration['table_max_height'] = $form_state->getValue('table_max_height');
    $this->configuration['use_sticky'] = $form_state->getValue('use_sticky');
    $this->configuration['default_css'] = $form_state->getValue('default_css');

    if ($this->configuration['csv_method'] === 'path_to_csv') {
      $this->configuration['unique_string'] = uniqid();
    }
    else {
      $this->configuration['unique_string'] = NULL;
    }

  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var \Drupal\csv_table\CsvTableHelperInterface $helper */
    $helper = \Drupal::service('csv_table.helper');

    $config = $this->getConfiguration();
    $build = [
      '#attached' => [
        'library' => [],
      ],
    ];

    if (!empty($config['show_filter'])) {
      $build[] = [
        '#theme' => 'csv_table__header',
      ];
      $build['#attached']['library'][] = 'csv_table/filter';
    }

    $table = $helper->buildTable($config);
    if (empty($this->configuration['show_header'])) {
      unset($table['#header']);
    }

    $build[] = $table;

    if (!empty($this->configuration['default_css'])) {
      $build['#attached']['library'][] = 'csv_table/default_css';
    }

    if (!empty($this->configuration['use_sticky'])) {
      $build['#attached']['library'][] = 'csv_table/sticky_header';
    }

    $build['#cache'] = [
      'tags' => $this->getCacheTags(),
      'contexts' => $this->getCacheContexts(),
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $config = $this->getConfiguration();
    /** @var \Drupal\csv_table\CsvTableHelperInterface $helper */
    $helper = \Drupal::service('csv_table.helper');

    if ($tags = $helper->getCacheTagNames($config)) {
      return Cache::mergeTags(parent::getCacheTags(), $tags);
    }
    return parent::getCacheTags();
  }

}
