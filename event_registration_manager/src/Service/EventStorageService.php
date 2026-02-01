<?php

/**
 * @file
 * Contains \Drupal\event_registration_manager\Service\EventStorageService.
 *
 * This service handles all database operations for the Event Registration
 * Manager module. It provides a clean abstraction layer between the database
 * and the business logic, following the repository pattern.
 *
 * Key responsibilities:
 * - CRUD operations for event_config table
 * - Registration storage and retrieval
 * - Duplicate registration checking
 * - Active event filtering based on registration dates
 *
 * @package Drupal\event_registration_manager\Service
 */

namespace Drupal\event_registration_manager\Service;

use Drupal\Core\Database\Connection;

/**
 * Service class for event storage operations.
 *
 * This service is registered in event_registration_manager.services.yml
 * and should be injected via dependency injection rather than called
 * statically with \Drupal::service().
 *
 * Example usage in a form or controller:
 * @code
 * public function __construct(EventStorageService $event_storage) {
 *   $this->eventStorage = $event_storage;
 * }
 * @endcode
 */
class EventStorageService
{

  /**
   * The database connection.
   *
   * Injected via the constructor from the service container.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * Available event categories.
   *
   * These are the predefined categories for events. The keys are machine
   * names stored in the database, and values are human-readable labels.
   *
   * @var array
   */
  public const CATEGORIES = [
    'online_workshop' => 'Online Workshop',
    'hackathon' => 'Hackathon',
    'conference' => 'Conference',
    'one_day_workshop' => 'One-day Workshop',
  ];

  /**
   * Constructs an EventStorageService object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection service, injected by the container.
   */
  public function __construct(Connection $database)
  {
    $this->database = $database;
  }

  /*
   * =========================================================================
   * EVENT CRUD OPERATIONS
   * =========================================================================
   */

  /**
   * Creates a new event in the database.
   *
   * @param array $data
   *   An associative array containing:
   *   - event_name: (string) The display name of the event.
   *   - category: (string) The event category machine name.
   *   - event_date: (string) The event date in YYYY-MM-DD format.
   *   - registration_start_date: (string) When registration opens.
   *   - registration_end_date: (string) When registration closes.
   *
   * @return int
   *   The ID of the newly created event.
   */
  public function createEvent(array $data): int
  {
    $now = \Drupal::time()->getRequestTime();

    return $this->database->insert('event_config')
      ->fields([
        'event_name' => $data['event_name'],
        'category' => $data['category'],
        'event_date' => $data['event_date'],
        'registration_start_date' => $data['registration_start_date'],
        'registration_end_date' => $data['registration_end_date'],
        'created' => $now,
        'changed' => $now,
      ])
      ->execute();
  }

  /**
   * Updates an existing event.
   *
   * @param int $id
   *   The event ID to update.
   * @param array $data
   *   The updated event data (same structure as createEvent).
   *
   * @return bool
   *   TRUE if the update affected at least one row.
   */
  public function updateEvent(int $id, array $data): bool
  {
    $result = $this->database->update('event_config')
      ->fields([
        'event_name' => $data['event_name'],
        'category' => $data['category'],
        'event_date' => $data['event_date'],
        'registration_start_date' => $data['registration_start_date'],
        'registration_end_date' => $data['registration_end_date'],
        'changed' => \Drupal::time()->getRequestTime(),
      ])
      ->condition('id', $id)
      ->execute();

    return $result > 0;
  }

  /**
   * Deletes an event from the database.
   *
   * Note: This will fail if there are registrations referencing this event
   * due to the foreign key constraint (ON DELETE RESTRICT).
   *
   * @param int $id
   *   The event ID to delete.
   *
   * @return bool
   *   TRUE if the delete was successful.
   */
  public function deleteEvent(int $id): bool
  {
    $result = $this->database->delete('event_config')
      ->condition('id', $id)
      ->execute();

    return $result > 0;
  }

  /**
   * Retrieves a single event by its ID.
   *
   * @param int $id
   *   The event ID.
   *
   * @return array|null
   *   The event data as an associative array, or NULL if not found.
   */
  public function getEvent(int $id): ?array
  {
    $result = $this->database->select('event_config', 'ec')
      ->fields('ec')
      ->condition('id', $id)
      ->execute()
      ->fetchAssoc();

    return $result ?: NULL;
  }

  /**
   * Gets all events ordered by date.
   *
   * @return array
   *   Array of all events, each as an associative array.
   */
  public function getAllEvents(): array
  {
    return $this->database->select('event_config', 'ec')
      ->fields('ec')
      ->orderBy('event_date', 'ASC')
      ->execute()
      ->fetchAll(\PDO::FETCH_ASSOC);
  }

  /*
   * =========================================================================
   * ACTIVE EVENT QUERIES
   * These methods filter events based on the current date to show only
   * events that are currently open for registration.
   * =========================================================================
   */

  /**
   * Gets events currently open for registration.
   *
   * An event is "active" when:
   * - Today's date >= registration_start_date
   * - Today's date <= registration_end_date
   *
   * @return array
   *   Array of active events.
   */
  public function getActiveEvents(): array
  {
    $today = date('Y-m-d');

    return $this->database->select('event_config', 'ec')
      ->fields('ec')
      ->condition('registration_start_date', $today, '<=')
      ->condition('registration_end_date', $today, '>=')
      ->orderBy('event_date', 'ASC')
      ->execute()
      ->fetchAll(\PDO::FETCH_ASSOC);
  }

  /**
   * Gets categories that have at least one active event.
   *
   * Used to populate the category dropdown on the registration form,
   * showing only categories that users can actually register for.
   *
   * @return array
   *   Associative array of category machine names to labels.
   */
  public function getActiveCategories(): array
  {
    $today = date('Y-m-d');

    $query = $this->database->select('event_config', 'ec')
      ->fields('ec', ['category'])
      ->condition('registration_start_date', $today, '<=')
      ->condition('registration_end_date', $today, '>=')
      ->distinct()
      ->execute()
      ->fetchCol();

    $categories = [];
    foreach ($query as $category) {
      if (isset(self::CATEGORIES[$category])) {
        $categories[$category] = self::CATEGORIES[$category];
      }
    }

    return $categories;
  }

  /**
   * Gets event dates for a specific category (active events only).
   *
   * This is called via AJAX when the user selects a category.
   * Returns only dates where registration is currently open.
   *
   * @param string $category
   *   The category machine name.
   *
   * @return array
   *   Associative array of date strings (YYYY-MM-DD) to formatted labels.
   */
  public function getEventDatesByCategory(string $category): array
  {
    $today = date('Y-m-d');

    $dates = $this->database->select('event_config', 'ec')
      ->fields('ec', ['event_date'])
      ->condition('category', $category)
      ->condition('registration_start_date', $today, '<=')
      ->condition('registration_end_date', $today, '>=')
      ->distinct()
      ->orderBy('event_date', 'ASC')
      ->execute()
      ->fetchCol();

    $options = [];
    foreach ($dates as $date) {
      // Format: "February 1, 2026"
      $formatted = date('F j, Y', strtotime($date));
      $options[$date] = $formatted;
    }

    return $options;
  }

  /**
   * Gets events by category and date (active events only).
   *
   * This is called via AJAX when the user selects a date.
   * Provides the final dropdown options for event selection.
   *
   * @param string $category
   *   The category machine name.
   * @param string $event_date
   *   The event date in YYYY-MM-DD format.
   *
   * @return array
   *   Associative array of event IDs to event names.
   */
  public function getEventsByCategoryAndDate(string $category, string $event_date): array
  {
    $today = date('Y-m-d');

    $events = $this->database->select('event_config', 'ec')
      ->fields('ec', ['id', 'event_name'])
      ->condition('category', $category)
      ->condition('event_date', $event_date)
      ->condition('registration_start_date', $today, '<=')
      ->condition('registration_end_date', $today, '>=')
      ->orderBy('event_name', 'ASC')
      ->execute()
      ->fetchAll(\PDO::FETCH_ASSOC);

    $options = [];
    foreach ($events as $event) {
      $options[$event['id']] = $event['event_name'];
    }

    return $options;
  }

  /*
   * =========================================================================
   * REGISTRATION OPERATIONS
   * =========================================================================
   */

  /**
   * Creates a new registration record.
   *
   * Stores denormalized data (category, event_date, event_name) for
   * faster querying and to preserve historical data if events change.
   *
   * @param array $data
   *   Registration data including:
   *   - full_name: (string) Registrant's name.
   *   - email: (string) Registrant's email.
   *   - college_name: (string) Institution name.
   *   - department: (string) Department name.
   *   - event_id: (int) Reference to event_config.id.
   *   - category: (string) Event category.
   *   - event_date: (string) Event date.
   *   - event_name: (string) Event name.
   *
   * @return int
   *   The ID of the new registration.
   */
  public function createRegistration(array $data): int
  {
    return $this->database->insert('event_registration')
      ->fields([
        'full_name' => $data['full_name'],
        'email' => $data['email'],
        'college_name' => $data['college_name'],
        'department' => $data['department'],
        'event_id' => $data['event_id'],
        'category' => $data['category'],
        'event_date' => $data['event_date'],
        'event_name' => $data['event_name'],
        'created' => \Drupal::time()->getRequestTime(),
      ])
      ->execute();
  }

  /**
   * Checks if a duplicate registration exists.
   *
   * Duplicate is defined as: same email + same event_date.
   * This prevents a user from registering for multiple events
   * on the same day.
   *
   * @param string $email
   *   The email address to check.
   * @param string $event_date
   *   The event date in YYYY-MM-DD format.
   *
   * @return bool
   *   TRUE if a duplicate registration exists.
   */
  public function checkDuplicateRegistration(string $email, string $event_date): bool
  {
    $count = $this->database->select('event_registration', 'er')
      ->condition('email', $email)
      ->condition('event_date', $event_date)
      ->countQuery()
      ->execute()
      ->fetchField();

    return $count > 0;
  }

  /**
   * Gets registrations with optional filters.
   *
   * @param array $filters
   *   Optional filters:
   *   - event_date: (string) Filter by event date.
   *   - event_name: (string) Filter by event name.
   *
   * @return array
   *   Array of registration records.
   */
  public function getRegistrations(array $filters = []): array
  {
    $query = $this->database->select('event_registration', 'er')
      ->fields('er')
      ->orderBy('created', 'DESC');

    // Apply optional filters.
    if (!empty($filters['event_date'])) {
      $query->condition('event_date', $filters['event_date']);
    }
    if (!empty($filters['event_name'])) {
      $query->condition('event_name', $filters['event_name']);
    }

    return $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
  }

  /**
   * Gets the count of registrations with optional filters.
   *
   * Used for displaying "Total Participants: X" on the admin listing.
   *
   * @param array $filters
   *   Same filter structure as getRegistrations().
   *
   * @return int
   *   Count of matching registrations.
   */
  public function getRegistrationCount(array $filters = []): int
  {
    $query = $this->database->select('event_registration', 'er');

    if (!empty($filters['event_date'])) {
      $query->condition('event_date', $filters['event_date']);
    }
    if (!empty($filters['event_name'])) {
      $query->condition('event_name', $filters['event_name']);
    }

    return (int) $query->countQuery()->execute()->fetchField();
  }

  /**
   * Gets all unique event dates from existing registrations.
   *
   * Used for the admin listing filter dropdown.
   *
   * @return array
   *   Associative array of dates to formatted labels.
   */
  public function getRegistrationEventDates(): array
  {
    $dates = $this->database->select('event_registration', 'er')
      ->fields('er', ['event_date'])
      ->distinct()
      ->orderBy('event_date', 'DESC')
      ->execute()
      ->fetchCol();

    $options = [];
    foreach ($dates as $date) {
      $formatted = date('F j, Y', strtotime($date));
      $options[$date] = $formatted;
    }

    return $options;
  }

  /**
   * Gets event names by date from existing registrations.
   *
   * Used for AJAX population of the event name filter when
   * a date is selected on the admin listing page.
   *
   * @param string $event_date
   *   The event date in YYYY-MM-DD format.
   *
   * @return array
   *   Array of event names (both key and value are the name).
   */
  public function getRegistrationEventNamesByDate(string $event_date): array
  {
    return $this->database->select('event_registration', 'er')
      ->fields('er', ['event_name'])
      ->condition('event_date', $event_date)
      ->distinct()
      ->orderBy('event_name', 'ASC')
      ->execute()
      ->fetchAllKeyed(0, 0);
  }

}
