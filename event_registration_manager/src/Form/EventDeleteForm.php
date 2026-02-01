<?php

namespace Drupal\event_registration_manager\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\event_registration_manager\Service\EventStorageService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for deleting an event.
 */
class EventDeleteForm extends ConfirmFormBase
{

    /**
     * The event storage service.
     *
     * @var \Drupal\event_registration_manager\Service\EventStorageService
     */
    protected EventStorageService $eventStorage;

    /**
     * The event to delete.
     *
     * @var array|null
     */
    protected ?array $event = NULL;

    /**
     * Constructs an EventDeleteForm object.
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
        return 'event_registration_manager_event_delete_form';
    }

    /**
     * {@inheritdoc}
     */
    public function getQuestion()
    {
        return $this->t('Are you sure you want to delete the event "@name"?', [
            '@name' => $this->event['event_name'] ?? 'Unknown',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->t('This action cannot be undone. Any registrations for this event will remain in the database.');
    }

    /**
     * {@inheritdoc}
     */
    public function getCancelUrl()
    {
        return Url::fromRoute('event_registration_manager.event_list');
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $event_id = NULL)
    {
        if ($event_id) {
            $this->event = $this->eventStorage->getEvent((int) $event_id);
            if (!$this->event) {
                $this->messenger()->addError($this->t('Event not found.'));
                return $this->redirect('event_registration_manager.event_list');
            }
        }

        $form['event_id'] = [
            '#type' => 'hidden',
            '#value' => $event_id,
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $event_id = $form_state->getValue('event_id');

        if ($event_id && $this->event) {
            $this->eventStorage->deleteEvent((int) $event_id);
            $this->messenger()->addStatus($this->t('Event "@name" has been deleted.', [
                '@name' => $this->event['event_name'],
            ]));
        }

        $form_state->setRedirect('event_registration_manager.event_list');
    }

}
