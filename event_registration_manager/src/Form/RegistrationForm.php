<?php

namespace Drupal\event_registration_manager\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\event_registration_manager\Service\EventStorageService;
use Drupal\event_registration_manager\Service\EventMailService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Public event registration form.
 */
class RegistrationForm extends FormBase
{

    /**
     * The event storage service.
     *
     * @var \Drupal\event_registration_manager\Service\EventStorageService
     */
    protected EventStorageService $eventStorage;

    /**
     * The mail service.
     *
     * @var \Drupal\event_registration_manager\Service\EventMailService
     */
    protected EventMailService $mailService;

    /**
     * Constructs a RegistrationForm object.
     *
     * @param \Drupal\event_registration_manager\Service\EventStorageService $event_storage
     *   The event storage service.
     * @param \Drupal\event_registration_manager\Service\EventMailService $mail_service
     *   The mail service.
     */
    public function __construct(
        EventStorageService $event_storage,
        EventMailService $mail_service
    ) {
        $this->eventStorage = $event_storage;
        $this->mailService = $mail_service;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('event_registration_manager.storage'),
            $container->get('event_registration_manager.mailer')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'event_registration_manager_registration_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        // Check if there are any active events.
        $active_categories = $this->eventStorage->getActiveCategories();

        if (empty($active_categories)) {
            $form['no_events'] = [
                '#type' => 'container',
                '#attributes' => [
                    'class' => ['event-registration-closed', 'messages', 'messages--warning'],
                ],
                '#markup' => $this->t('There are currently no events open for registration. Please check back later.'),
            ];
            return $form;
        }

        $form['#attached']['library'][] = 'event_registration_manager/registration_form';

        $form['intro'] = [
            '#type' => 'container',
            '#attributes' => [
                'class' => ['event-registration-intro'],
            ],
        ];

        $form['intro']['title'] = [
            '#markup' => '<h2>' . $this->t('Register for an Event') . '</h2>',
        ];

        $form['intro']['description'] = [
            '#markup' => '<p class="description">' . $this->t('Please fill out the form below to register for an upcoming event. All fields marked with an asterisk (*) are required.') . '</p>',
        ];

        // Personal Information Section.
        $form['personal_info'] = [
            '#type' => 'details',
            '#title' => $this->t('Personal Information'),
            '#open' => TRUE,
            '#attributes' => [
                'class' => ['event-registration-section'],
            ],
        ];

        $form['personal_info']['full_name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Full Name'),
            '#description' => $this->t('Enter your full name as it should appear on the registration.'),
            '#required' => TRUE,
            '#maxlength' => 255,
            '#attributes' => [
                'placeholder' => $this->t('Enter your full name'),
                'class' => ['event-registration-input'],
            ],
        ];

        $form['personal_info']['email'] = [
            '#type' => 'email',
            '#title' => $this->t('Email Address'),
            '#description' => $this->t('A confirmation email will be sent to this address.'),
            '#required' => TRUE,
            '#maxlength' => 255,
            '#attributes' => [
                'placeholder' => $this->t('Enter your email address'),
                'class' => ['event-registration-input'],
            ],
        ];

        // Academic Information Section.
        $form['academic_info'] = [
            '#type' => 'details',
            '#title' => $this->t('Academic Information'),
            '#open' => TRUE,
            '#attributes' => [
                'class' => ['event-registration-section'],
            ],
        ];

        $form['academic_info']['college_name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('College Name'),
            '#description' => $this->t('Enter the name of your college or institution.'),
            '#required' => TRUE,
            '#maxlength' => 255,
            '#attributes' => [
                'placeholder' => $this->t('Enter your college name'),
                'class' => ['event-registration-input'],
            ],
        ];

        $form['academic_info']['department'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Department'),
            '#description' => $this->t('Enter your department or field of study.'),
            '#required' => TRUE,
            '#maxlength' => 255,
            '#attributes' => [
                'placeholder' => $this->t('Enter your department'),
                'class' => ['event-registration-input'],
            ],
        ];

        // Event Selection Section.
        $form['event_selection'] = [
            '#type' => 'details',
            '#title' => $this->t('Event Selection'),
            '#open' => TRUE,
            '#attributes' => [
                'class' => ['event-registration-section'],
            ],
        ];

        $form['event_selection']['category'] = [
            '#type' => 'select',
            '#title' => $this->t('Category of Event'),
            '#description' => $this->t('Select the type of event you wish to attend.'),
            '#required' => TRUE,
            '#options' => ['' => $this->t('- Select Category -')] + $active_categories,
            '#ajax' => [
                'callback' => '::updateEventDates',
                'wrapper' => 'event-dates-wrapper',
                'event' => 'change',
                'progress' => [
                    'type' => 'throbber',
                    'message' => $this->t('Loading dates...'),
                ],
            ],
            '#attributes' => [
                'class' => ['event-registration-select'],
            ],
        ];

        // Get selected category.
        $selected_category = $form_state->getValue('category');

        // Event dates (AJAX dependent).
        $event_dates = [];
        if ($selected_category) {
            $event_dates = $this->eventStorage->getEventDatesByCategory($selected_category);
        }

        $form['event_selection']['event_date_wrapper'] = [
            '#type' => 'container',
            '#attributes' => ['id' => 'event-dates-wrapper'],
        ];

        $form['event_selection']['event_date_wrapper']['event_date'] = [
            '#type' => 'select',
            '#title' => $this->t('Event Date'),
            '#description' => $this->t('Select the date of the event.'),
            '#required' => TRUE,
            '#options' => ['' => $this->t('- Select Date -')] + $event_dates,
            '#validated' => TRUE,
            '#ajax' => [
                'callback' => '::updateEventNames',
                'wrapper' => 'event-names-wrapper',
                'event' => 'change',
                'progress' => [
                    'type' => 'throbber',
                    'message' => $this->t('Loading events...'),
                ],
            ],
            '#attributes' => [
                'class' => ['event-registration-select'],
            ],
        ];

        // Get selected date.
        $selected_date = $form_state->getValue('event_date');

        // Event names (AJAX dependent).
        $event_names = [];
        if ($selected_category && $selected_date) {
            $event_names = $this->eventStorage->getEventsByCategoryAndDate($selected_category, $selected_date);
        }

        $form['event_selection']['event_name_wrapper'] = [
            '#type' => 'container',
            '#attributes' => ['id' => 'event-names-wrapper'],
        ];

        $form['event_selection']['event_name_wrapper']['event_id'] = [
            '#type' => 'select',
            '#title' => $this->t('Event Name'),
            '#description' => $this->t('Select the specific event you wish to register for.'),
            '#required' => TRUE,
            '#options' => ['' => $this->t('- Select Event -')] + $event_names,
            '#validated' => TRUE,
            '#attributes' => [
                'class' => ['event-registration-select'],
            ],
        ];

        $form['actions'] = [
            '#type' => 'actions',
            '#attributes' => [
                'class' => ['event-registration-actions'],
            ],
        ];

        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Register for Event'),
            '#button_type' => 'primary',
            '#attributes' => [
                'class' => ['event-registration-submit'],
            ],
        ];

        return $form;
    }

    /**
     * AJAX callback to update event dates based on selected category.
     */
    public function updateEventDates(array &$form, FormStateInterface $form_state): array
    {
        return $form['event_selection']['event_date_wrapper'];
    }

    /**
     * AJAX callback to update event names based on selected date.
     */
    public function updateEventNames(array &$form, FormStateInterface $form_state): array
    {
        return $form['event_selection']['event_name_wrapper'];
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        // Validate full name - no special characters.
        $full_name = $form_state->getValue('full_name');
        if (!empty($full_name) && preg_match('/[^a-zA-Z0-9\s\.\-]/', $full_name)) {
            $form_state->setErrorByName('full_name', $this->t('Full name contains invalid characters. Only letters, numbers, spaces, periods, and hyphens are allowed.'));
        }

        // Validate college name - no special characters.
        $college_name = $form_state->getValue('college_name');
        if (!empty($college_name) && preg_match('/[^a-zA-Z0-9\s\.\-\,\&\']/', $college_name)) {
            $form_state->setErrorByName('college_name', $this->t('College name contains invalid characters. Only letters, numbers, spaces, periods, hyphens, commas, apostrophes, and ampersands are allowed.'));
        }

        // Validate department - no special characters.
        $department = $form_state->getValue('department');
        if (!empty($department) && preg_match('/[^a-zA-Z0-9\s\.\-\,\&\']/', $department)) {
            $form_state->setErrorByName('department', $this->t('Department contains invalid characters. Only letters, numbers, spaces, periods, hyphens, commas, apostrophes, and ampersands are allowed.'));
        }

        // Validate email format (additional validation).
        $email = $form_state->getValue('email');
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $form_state->setErrorByName('email', $this->t('Please enter a valid email address.'));
        }

        // Validate event selection.
        $event_id = $form_state->getValue('event_id');
        $event_date = $form_state->getValue('event_date');

        if (empty($event_id)) {
            $form_state->setErrorByName('event_id', $this->t('Please select an event.'));
        }

        // Check for duplicate registration.
        if (!empty($email) && !empty($event_date)) {
            if ($this->eventStorage->checkDuplicateRegistration($email, $event_date)) {
                $form_state->setErrorByName('email', $this->t('You have already registered for an event on this date. Each email can only be registered once per event date.'));
            }
        }

        parent::validateForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $event_id = $form_state->getValue('event_id');
        $event = $this->eventStorage->getEvent((int) $event_id);

        if (!$event) {
            $this->messenger()->addError($this->t('An error occurred. Please try again.'));
            return;
        }

        $registration_data = [
            'full_name' => $form_state->getValue('full_name'),
            'email' => $form_state->getValue('email'),
            'college_name' => $form_state->getValue('college_name'),
            'department' => $form_state->getValue('department'),
            'event_id' => $event_id,
            'category' => $form_state->getValue('category'),
            'event_date' => $form_state->getValue('event_date'),
            'event_name' => $event['event_name'],
        ];

        // Create the registration.
        $registration_id = $this->eventStorage->createRegistration($registration_data);

        if ($registration_id) {
            // Send confirmation email to user.
            $this->mailService->sendUserConfirmation($registration_data);

            // Send notification to admin if enabled.
            $this->mailService->sendAdminNotification($registration_data);

            $this->messenger()->addStatus($this->t('Thank you for registering! A confirmation email has been sent to @email.', [
                '@email' => $registration_data['email'],
            ]));

            // Reset the form.
            $form_state->setRebuild(FALSE);
        } else {
            $this->messenger()->addError($this->t('An error occurred during registration. Please try again.'));
        }
    }

}
