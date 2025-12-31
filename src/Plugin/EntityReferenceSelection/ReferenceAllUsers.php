<?php

namespace Drupal\reference_blocked_users\Plugin\EntityReferenceSelection;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Plugin\EntityReferenceSelection\UserSelection;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Reference blocked users in entity reference field .
 *
 * @EntityReferenceSelection(
 * id = "default:reference_blocked_users",
 * label = @Translation("Reference Blocked Users"),
 * entity_types = {"user"},
 * group = "default",
 * weight = 10
 * )
 */
class ReferenceAllUsers extends UserSelection {

  /**
   * Constructs a new UserSelection object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface|null $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface|null $entity_type_bundle_info
   *   The entity type bundle info service.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface|null $entity_repository
   *   The entity repository.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    ModuleHandlerInterface $module_handler,
    AccountInterface $current_user,
    Connection $connection,
    ?EntityFieldManagerInterface $entity_field_manager = NULL,
    ?EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL,
    ?EntityRepositoryInterface $entity_repository = NULL,
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $entity_type_manager,
      $module_handler,
      $current_user,
      $connection,
      $entity_field_manager,
      $entity_type_bundle_info,
      $entity_repository,
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('current_user'),
      $container->get('database'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity.repository'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    // For user with the referenced blocked users' permission.
    if (!$this->currentUser->hasPermission('administer users') &&
         $this->currentUser->hasPermission('reference blocked users')) {

      return $this->buildEntityQueryForAllUsers($match, $match_operator);

    }

    return parent::buildEntityQuery($match, $match_operator);
  }

  /**
   * Builds a custom EntityQuery to get referenced blocked users.
   *
   * @param string|null $match
   *   Text to match the label against.
   * @param string $match_operator
   *   The operation the matching should be done with.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The query object that can query the given entity type.
   */
  protected function buildEntityQueryForAllUsers(?string $match = NULL, string $match_operator = 'CONTAINS'): QueryInterface {
    $query = $this->entityTypeManager->getStorage('user')->getQuery();
    $query->accessCheck();
    // The user entity doesn't have a label column.
    $configuration = $this->getConfiguration();

    // Filter out the Anonymous user if the selection handler is configured to
    // exclude it.
    if (!$configuration['include_anonymous']) {
      $query->condition('uid', 0, '<>');
    }

    // The user entity doesn't have a label column.
    if (isset($match)) {
      $query->condition('name', $match, $match_operator);
    }

    // Filter by role.
    if (!empty($configuration['filter']['role'])) {
      $query->condition('roles', $configuration['filter']['role'], 'IN');
    }

    $query->condition('status', 0, '>=');

    return $query;
  }

}
