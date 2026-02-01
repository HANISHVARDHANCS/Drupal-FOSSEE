<?php

/**
 * @file
 * Contains \Drupal\event_registration_manager\Service\EventMailService.
 *
 * This service handles all email-related operations for the module.
 * It integrates with Drupal's Mail API to send confirmation emails
 * to users and notification emails to administrators.
 *
 * Mail templates are defined in hook_mail() within the .module file.
 *
 * @package Drupal\event_registration_manager\Service
 */

namespace Drupal\event_registration_manager\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service class for sending event-related emails.
 *
 * This service uses Drupal's Mail API for sending emails. The actual
 * email content is defined in event_registration_manager_mail() hook.
 *
 * Configuration for admin notifications is stored in:
 * - event_registration_manager.settings.admin_email
 * - event_registration_manager.settings.admin_notification_enabled
 */
class EventMailService
{

  /**
   * The mail manager service.
   *
   * Handles the actual sending of emails through Drupal's mail system.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected MailManagerInterface $mailManager;

  /**
   * The config factory service.
   *
   * Used to retrieve module configuration for admin email settings.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The logger service.
   *
   * Used for logging email success/failure for debugging and auditing.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Constructs an EventMailService object.
   *
   * All dependencies are injected via the service container as defined
   * in event_registration_manager.services.yml.
   *
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory service.
   */
  public function __construct(
    MailManagerInterface $mail_manager,
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->mailManager = $mail_manager;
    $this->configFactory = $config_factory;
    // Get a dedicated logger channel for this module.
    $this->logger = $logger_factory->get('event_registration_manager');
  }

  /**
   * Sends a confirmation email to the registered user.
   *
   * This email is sent immediately after a successful registration.
   * It confirms the registration and provides event details.
   *
   * The email template is defined in hook_mail() with key
   * 'registration_confirmation'.
   *
   * @param array $registration
   *   The registration data containing:
   *   - full_name: The registrant's name.
   *   - email: The registrant's email (recipient).
   *   - event_name: The name of the event.
   *   - event_date: The date of the event (YYYY-MM-DD).
   *   - category: The event category machine name.
   *   - college_name: The registrant's college.
   *   - department: The registrant's department.
   *
   * @return bool
   *   TRUE if the email was sent successfully, FALSE otherwise.
   */
  public function sendUserConfirmation(array $registration): bool
  {
    $module = 'event_registration_manager';
    $key = 'registration_confirmation';
    $to = $registration['email'];
    $langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();

    // Prepare parameters for the mail template.
    // These values are used in hook_mail() to build the email.
    $params = [
      'full_name' => $registration['full_name'],
      'email' => $registration['email'],
      'event_name' => $registration['event_name'],
      // Format date for readability in email.
      'event_date' => date('F j, Y', strtotime($registration['event_date'])),
      // Convert category machine name to label.
      'category' => $this->getCategoryLabel($registration['category']),
      'college_name' => $registration['college_name'],
      'department' => $registration['department'],
    ];

    // Send the email via the mail manager.
    // The fourth parameter (reply) is NULL, fifth (send) is TRUE.
    $result = $this->mailManager->mail($module, $key, $to, $langcode, $params, NULL, TRUE);

    // Log the result for debugging and auditing.
    if ($result['result'] !== TRUE) {
      $this->logger->error('Failed to send confirmation email to @email for event @event.', [
        '@email' => $to,
        '@event' => $registration['event_name'],
      ]);
      return FALSE;
    }

    $this->logger->info('Confirmation email sent to @email for event @event.', [
      '@email' => $to,
      '@event' => $registration['event_name'],
    ]);

    return TRUE;
  }

  /**
   * Sends a notification email to the administrator.
   *
   * This email is sent after a successful registration IF:
   * 1. Admin notifications are enabled in module settings.
   * 2. An admin email address is configured.
   *
   * The email template is defined in hook_mail() with key
   * 'admin_notification'.
   *
   * @param array $registration
   *   The registration data (same structure as sendUserConfirmation).
   *
   * @return bool
   *   TRUE if email was sent (or notifications disabled), FALSE on failure.
   */
  public function sendAdminNotification(array $registration): bool
  {
    // Retrieve module configuration.
    $config = $this->configFactory->get('event_registration_manager.settings');

    // Check if admin notifications are enabled.
    // If disabled, return TRUE (not a failure, just skipped).
    if (!$config->get('admin_notification_enabled')) {
      return TRUE;
    }

    // Get the configured admin email address.
    $admin_email = $config->get('admin_email');

    // Log warning if enabled but no email configured.
    if (empty($admin_email)) {
      $this->logger->warning('Admin notification enabled but no admin email configured.');
      return FALSE;
    }

    $module = 'event_registration_manager';
    $key = 'admin_notification';
    $langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();

    // Prepare parameters for the admin notification template.
    $params = [
      'full_name' => $registration['full_name'],
      'email' => $registration['email'],
      'event_name' => $registration['event_name'],
      'event_date' => date('F j, Y', strtotime($registration['event_date'])),
      'category' => $this->getCategoryLabel($registration['category']),
      'college_name' => $registration['college_name'],
      'department' => $registration['department'],
    ];

    // Send the email.
    $result = $this->mailManager->mail($module, $key, $admin_email, $langcode, $params, NULL, TRUE);

    // Log the result.
    if ($result['result'] !== TRUE) {
      $this->logger->error('Failed to send admin notification to @email for registration by @user.', [
        '@email' => $admin_email,
        '@user' => $registration['email'],
      ]);
      return FALSE;
    }

    $this->logger->info('Admin notification sent to @email for registration by @user.', [
      '@email' => $admin_email,
      '@user' => $registration['email'],
    ]);

    return TRUE;
  }

  /**
   * Converts a category machine name to its human-readable label.
   *
   * @param string $category
   *   The category machine name (e.g., 'online_workshop').
   *
   * @return string
   *   The human-readable label (e.g., 'Online Workshop').
   *   Returns the machine name if no mapping exists.
   */
  protected function getCategoryLabel(string $category): string
  {
    $categories = EventStorageService::CATEGORIES;
    return $categories[$category] ?? $category;
  }

}
