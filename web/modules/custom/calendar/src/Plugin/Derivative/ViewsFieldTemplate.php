<?php

namespace Drupal\calendar\Plugin\Derivative;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\views\ViewsData;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Derivative class to find all field and properties for calendar View Builders.
 */
class ViewsFieldTemplate implements ContainerDeriverInterface {

  /**
   * List of derivative definitions.
   *
   * @var array
   */
  protected $derivatives = [];

  /**
   * The base plugin ID.
   *
   * @var string
   */
  protected $basePluginId;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * The views data service.
   *
   * @var \Drupal\views\ViewsData
   */
  protected $viewsData;

  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $field_manager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static (
      $base_plugin_id,
      $container->get('entity_type.manager'),
      $container->get('views.views_data'),
      $container->get('entity_field.manager')
    );

  }

  /**
   * Constructs a ViewsBlock object.
   *
   * @param string $base_plugin_id
   *   The base plugin ID.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $manager
   * @param ViewsData $views_data
   *   The entity storage to load views.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager
   */
  public function __construct($base_plugin_id, EntityTypeManagerInterface $manager, ViewsData $views_data, EntityFieldManagerInterface $field_manager) {
    $this->basePluginId = $base_plugin_id;
    $this->entityManager = $manager;
    $this->viewsData = $views_data;
    $this->field_manager = $field_manager;
  }


  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinition($derivative_id, $base_plugin_definition) {
    if (!empty($this->derivatives) && !empty($this->derivatives[$derivative_id])) {
      return $this->derivatives[$derivative_id];
    }
    $this->getDerivativeDefinitions($base_plugin_definition);
    return $this->derivatives[$derivative_id];
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    /**
     * @var \Drupal\Core\Entity\EntityTypeInterface $entity_type
     */
    foreach ($this->entityManager->getDefinitions() as $entity_type_id => $entity_type) {
      // Just add support for entity types which have a views integration.
      if (($base_table = $entity_type->getBaseTable()) && $this->viewsData->get($base_table) && $this->entityManager->hasHandler($entity_type_id, 'view_builder')) {
        $entity_views_tables = [$base_table => $this->viewsData->get($base_table)];
        if ($data_table = $entity_type->getDataTable()) {
          $entity_views_tables[$data_table] = $this->viewsData->get($data_table);
        }
        foreach ($entity_views_tables as $table_id => $entity_views_table) {
          foreach ($entity_views_table as $key => $field_info) {
            if ($this->isDateField($field_info)) {
              $derivative = [
                'replacements' => [
                  'entity_label' => $entity_type->getLabel(),
                  'entity_type' => $entity_type_id,
                  'field_id' => $field_info['entity field'],
                  'base_table' => $table_id,
                  'base_field' => $this->getTableBaseField($entity_views_table),
                  'default_field_id' => $this->getTableDefaultField($entity_views_table, $entity_type_id),
                  'field_label' => $field_info['title'],
                ],
                'view_template_id' => 'calendar_base_field',
              ];
              $this->setDerivative($derivative, $base_plugin_definition);
            }
          }
        }
        // @todo Loop through all fields attached to this entity type.
        // The have different base tables that are joined to this table.
        $this->setConfigurableFieldsDerivatives($entity_type, $base_plugin_definition);
      }

    }
    return $this->derivatives;
  }

  /**
   * Set all derivatives for an entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   * @param array $base_plugin_definition
   */
  protected function setConfigurableFieldsDerivatives(EntityTypeInterface $entity_type, array $base_plugin_definition) {
    /** @var \Drupal\Core\Field\FieldStorageDefinitionInterface $field_storage */
    $field_storages = $this->field_manager->getFieldStorageDefinitions($entity_type->id());

    foreach ($field_storages as $field_id => $field_storage) {
      $type = $field_storage->getType();
      $field_definition = \Drupal::service('plugin.manager.field.field_type')->getDefinition($type);
      $class = $field_definition['class'];
      $classes = [];
      $classes[$type] = [];
      $classes[$type][] = $class;
      while ($class !== FALSE) {
        $classes[$type][] = get_parent_class($class);
        $class = end($classes[$type]);
      }
      if (in_array("Drupal\datetime\Plugin\Field\FieldType\DateTimeItem", $classes[$type])) {
        $entity_type_id = $entity_type->id();
        $views_data = $this->viewsData->get();
        foreach ($views_data as $key => $data) {
          if (strstr($key, $field_id) && isset($data[$field_id])) {
            $field_table = $key;
            $field_table_data = $data;
            break;
          }
        }
        if (isset($field_table_data)) {
          $derivative = [];
          $field_info = $field_table_data[$field_id];
          $derivative['field_id'] = $field_id;
          $join_tables = array_keys($field_table_data['table']['join']);
          // @todo Will there ever be more than 1 tables here?
          $join_table = array_pop($join_tables);
          $join_table_data = $this->viewsData->get($join_table);
          $derivative = [
            'replacements' => [
              'field_id' => $field_id,
              'entity_type' => $entity_type_id,
              'entity_label' => $entity_type->getLabel(),
              'field_label' => $field_info['title'],
              'base_table' => $join_table,
              'field_table' => $field_table,
              'default_field_id' => $this->getTableDefaultField($join_table_data, $entity_type_id),
              'base_field' => $this->getTableBaseField($join_table_data),
            ],
            'view_template_id' => 'calendar_config_field',
          ];
          $this->setDerivative($derivative, $base_plugin_definition);
          //$this->setDerivative($field_info, $entity_type, $field_table_data, $base_plugin_definition);
        }

      }


    }
  }

  /**
   * Determine if a field is an date field.
   * @param array $field_info
   *  Field array form ViewsData.
   *
   * @return bool
   */
  protected function isDateField($field_info) {
    if (!empty($field_info['field']['id']) && $field_info['field']['id'] == 'field') {
      if (!empty($field_info['argument']['id']) && $field_info['argument']['id'] == 'date') {
        return TRUE;
      }
    }
    return FALSE;
  }

  protected function setDerivative(array $derivative, array $base_plugin_definition) {

    $info = $derivative['replacements'];

    $derivative_id = $info['entity_type'] . '__' . $info['field_id'];
    // Move some replacements values to root of derivative also.
    $derivative['entity_type'] = $info['entity_type'];
    $derivative['field_id'] = $info['field_id'];
    // Create base path
    if ($derivative['entity_type'] == 'node') {
      $base_path = 'calendar-' .$derivative['field_id'];
    }
    else {
      $base_path = "calendar-{$derivative['entity_type']}-{$derivative['field_id']}";
    }
    $derivative['replacements']['base_path'] = $base_path;
    $derivative['id'] = $base_plugin_definition['id'] . ':' . $derivative_id;
    $derivative += $base_plugin_definition;

    $this->derivatives[$derivative_id] = $derivative;
  }


  /**
   * Return the default field from a View table array.
   *
   * @param array $table_data
   *
   * @return null|string
   */
  private function getTableDefaultField(array $table_data, $entity_type_id = NULL) {
    $default_field_id = NULL;
    if (!empty($table_data['table']['base']['defaults']['field'])) {
      $default_field_id = $table_data['table']['base']['defaults']['field'];
    }
    if (empty($default_field_id) && $entity_type_id) {
      // @todo Why doesn't user have a default field? Is there another way to get it?
      if ($entity_type_id == 'user') {
        $default_field_id = 'name';
      }
    }
    return $default_field_id;
  }

  /**
   * Return the base field from a View tabel array.
   *
   * @param array $table_data
   *
   * @return null|string
   */
  private function getTableBaseField(array $table_data) {
    if (!empty($table_data['table']['base']['field'])) {
      return $table_data['table']['base']['field'];
    }
    return NULL;
  }


}
