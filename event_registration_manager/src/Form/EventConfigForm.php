<?php

namespace Drupal\event_registration_manager\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\event_registration_manager\Service\EventStorageService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for creating and editing events.
 */
class EventConfigForm extends FormBase
{

    /**
     * The event storage service.
     *
     * @var \Drupal\event_registration_manager\Service\EventStorageService
     */
    protected EventStorageService $eventStorage;

    /**
     * Constructs an EventConfigForm object.
     *
     * @param \Drupal\event_registration_manager\Service\EventStorageService $event_storage
     *   The event storage service.
     */
    public function __construct(EventStorageService $event_storage)
    {
        $this->eventStorage = $event_storage;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('event_registration_manager.storage')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'event_registration_manager_event_config_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $event_id = NULL)
    {
        $event = NULL;
        if ($event_id) {
            $event = $this->eventStorage->getEvent((int) $event_id);
            if (!$event) {
                $this->messenger()->addError($this->t('Event not found.'));
                return $this->redirect('event_registration_manager.event_list');
            }
        }

        $form['#attached']['library'][] = 'event_registration_manager/admin_styles';

        $form['event_id'] = [
            '#type' => 'hidden',
            '#value' => $event_id,
        ];

        $form['event_details'] = [
            '#type' => 'details',
            '#title' => $this->t('Event Details'),
            '#open' => TRUE,
        ];

        $form['event_details']['event_name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Event Name'),
            '#description' => $this->t('Enter a descriptive name for the event.'),
            '#required' => TRUE,
            '#maxlength' => 255,
            '#default_value' => $event['event_name'] ?? '',
        ];

        $form['event_details']['category'] = [
            '#type' => 'select',
            '#title' => $this->t('Category of Event'),
            '#description' => $this->t('Select the type of event.'),
            '#required' => TRUE,
            '#options' => ['' => $this->t('- Select Category -')] + EventStorageService::CATEGORIES,
            '#default_value' => $event['category'] ?? '',
        ];

        $form['dates'] = [
            '#type' => 'details',
            '#title' => $this->t('Event Dates'),
            '#open' => TRUE,
        ];

        $form['dates']['event_date'] = [
            '#type' => 'date',
            '#title' => $this->t('Event Date'),
            '#description' => $this->t('The date the event will take place.'),
            '#required' => TRUE,
            '#default_value' => $event['event_date'] ?? '',
        ];

        $form['dates']['registration_start_date'] = [
            '#type' => 'date',
            '#title' => $this->t('Registration Start Date'),
            '#description' => $this->t('The date when registration opens.'),
            '#required' => TRUE,
            '#default_value' => $event['registration_start_date'] ?? '',
        ];

        $form['dates']['registration_end_date'] = [
            '#type' => 'date',
            '#title' => $this->t('Registration End Date'),
            '#description' => $this->t('The date when registration closes.'),
            '#required' => TRUE,
            '#default_value' => $event['registration_end_date'] ?? '',
        ];

        $form['actions'] = [
            '#type' => 'actions',
        ];

        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $event ? $this->t('Update Event') : $this->t('Create Event'),
            '#button_type' => 'primary',
        ];

        $form['actions']['cancel'] = [
            '#type' => 'link',
            '#title' => $this->t('Cancel'),
            '#url' => Url::fromRoute('event_registration_manager.event_list'),
            '#attributes' => [
                'class' => ['button'],
            ],
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        $event_name = $form_state->getValue('event_name');
        $registration_start = $form_state->getValue('registration_start_date');
        $registration_end = $form_state->getValue('registration_end_date');
        $event_date = $form_state->getValue('event_date');

        // Validate event name - no special characters.
        if (preg_match('/[^a-zA-Z0-9\s\-\_\.\,\&]/', $event_name)) {
            $form_state->setErrorByName('event_name', $this->t('Event name contains invalid characters. Only letters, numbers, spaces, hyphens, underscores, periods, commas, and ampersands are allowed.'));
        }

        // Validate date logic.
        if ($registration_start && $registration_end && strtotime($registration_start) > strtotime($registration_end)) {
            $form_state->setErrorByName('registration_end_date', $this->t('Registration end date must be on or after the start date.'));
        }

        if ($registration_end && $event_date && strtotime($registration_end) > strtotime($event_date)) {
            $form_state->setErrorByName('registration_end_date', $this->t('Registration must close on or before the event date.'));
        }

        parent::validateForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $event_id = $form_state->getValue('event_id');

        $data = [
            'event_name' => $form_state->getValue('event_name'),
            'category' => $form_state->getValue('category'),
            'event_date' => $form_state->getValue('event_date'),
            'registration_start_date' => $form_state->getValue('registration_start_date'),
            'registration_end_date' => $form_state->getValue('registration_end_date'),
        ];

        if ($event_id) {
            $this->eventStorage->updateEvent((int) $event_id, $data);
            $this->messenger()->addStatus($this->t('Event "@name" has been updated.', [
                '@name' => $data['event_name'],
            ]));
        } else {
            $this->eventStorage->createEvent($data);
            $this->messenger()->addStatus($this->t('Event "@name" has been created.', [
                '@name' => $data['event_name'],
            ]));
        }

        $form_state->setRedirect('event_registration_manager.event_list');
    }

}
