<?php

namespace Drupal\reference_blocked_users\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Attribute\EntityReferenceSelection;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\Plugin\EntityReferenceSelection\UserSelection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Reference blocked users in an entity reference field.
 */
#[EntityReferenceSelection(
  id: "default:reference_blocked_users",
  label: new TranslatableMarkup("Reference Blocked Users"),
  entity_types: ["user"],
  group: "default",
  weight: 10
)]
class ReferenceAllUsers extends UserSelection {

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
    $query->accessCheck(TRUE);
    // The user entity doesn't have a label column.
    $configuration = $this->getConfiguration();

    // Filter out the Anonymous user if the selection handler is configured to
    // exclude it.
    if (!$configuration['include_anonymous']) {
      $query->condition('uid', 0, '<>');
    }

    // The user entity doesn't have a label column. Match the typed text against
    // the username or the email address, so editors can find a user by either.
    // Core's UserSelection only matches the username.
    if (isset($match)) {
      $group = $query->orConditionGroup()
        ->condition('name', $match, $match_operator)
        ->condition('mail', $match, $match_operator);
      $query->condition($group);
    }

    // Filter by role.
    if (!empty($configuration['filter']['role'])) {
      $query->condition('roles', $configuration['filter']['role'], 'IN');
    }

    $query->condition('status', 0, '>=');

    return $query;
  }

}
