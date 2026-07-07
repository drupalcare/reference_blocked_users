<?php

declare(strict_types=1);

namespace Drupal\Tests\reference_blocked_users\Kernel;

use Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the "Reference Blocked Users" entity reference selection handler.
 */
#[Group('reference_blocked_users')]
#[RunTestsInSeparateProcesses]
class ReferenceBlockedUsersSelectionTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['reference_blocked_users'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // User 1 bypasses every access check. Create a throwaway root user so the
    // accounts created in the individual tests are regular, non-superuser
    // accounts and the permission gating is actually exercised.
    $this->createUser();
  }

  /**
   * Builds the module's selection handler for the current user.
   */
  protected function getHandler(): SelectionInterface {
    return $this->container->get('plugin.manager.entity_reference_selection')->getInstance([
      'target_type' => 'user',
      'handler' => 'default:reference_blocked_users',
      'handler_settings' => [
        // Keep the anonymous user out of the results to simplify assertions.
        'include_anonymous' => FALSE,
      ],
    ]);
  }

  /**
   * Returns the referenceable user IDs for a handler and optional match string.
   *
   * @return int[]
   *   The user IDs the handler would offer as references.
   */
  protected function referenceableIds(SelectionInterface $handler, ?string $match = NULL): array {
    $result = $handler->getReferenceableEntities($match);
    return array_map('intval', array_keys($result['user'] ?? []));
  }

  /**
   * A holder of the permission can reference active and blocked users.
   */
  public function testPermissionHolderSeesBlockedUsers(): void {
    $active = $this->createUser([], 'active_alice');
    $blocked = $this->createUser([], 'blocked_bob');
    $blocked->block();
    $blocked->save();

    // A holder of the module permission that is NOT a user administrator.
    $editor = $this->createUser(['reference blocked users']);
    $this->assertFalse($editor->hasPermission('administer users'));
    $this->setCurrentUser($editor);

    $ids = $this->referenceableIds($this->getHandler());
    $this->assertContains((int) $active->id(), $ids, 'Active user is referenceable.');
    $this->assertContains((int) $blocked->id(), $ids, 'Blocked user is referenceable for a permission holder.');
  }

  /**
   * Without the permission, blocked users fall back to core behaviour.
   */
  public function testWithoutPermissionBlockedUsersAreHidden(): void {
    $active = $this->createUser([], 'active_alice');
    $blocked = $this->createUser([], 'blocked_bob');
    $blocked->block();
    $blocked->save();

    // A user with neither "administer users" nor the module permission.
    $editor = $this->createUser(['access content']);
    $this->setCurrentUser($editor);

    $ids = $this->referenceableIds($this->getHandler());
    $this->assertContains((int) $active->id(), $ids, 'Active user is still referenceable.');
    $this->assertNotContains((int) $blocked->id(), $ids, 'Blocked user is hidden without the permission.');
  }

  /**
   * Autocomplete matches on the email address, not only the username.
   */
  public function testMatchesOnEmail(): void {
    // A blocked user whose username shares no substring with the email.
    $user = $this->createUser([], 'zoe', FALSE, ['mail' => 'findme@example.com']);
    $user->block();
    $user->save();

    $editor = $this->createUser(['reference blocked users']);
    $this->setCurrentUser($editor);
    $handler = $this->getHandler();

    // The username does not contain the search term, only the email does.
    $ids = $this->referenceableIds($handler, 'findme@');
    $this->assertContains((int) $user->id(), $ids, 'User is found by an email fragment.');

    // Matching by username continues to work.
    $ids = $this->referenceableIds($handler, 'zoe');
    $this->assertContains((int) $user->id(), $ids, 'User is still found by username.');
  }

}
