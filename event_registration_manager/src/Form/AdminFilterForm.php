<?php

namespace Drupal\event_registration_manager\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\event_registration_manager\Service\EventStorageService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Filter form for admin registration listing.
 */
class AdminFilterForm extends FormBase
{

    /**
     * The event storage service.
     *
     * @var \Drupal\event_registration_manager\Service\EventStorageService
     */
    protected EventStorageService $eventStorage;

    /**
     * The request stack.
     *
     * @var \Symfony\Component\HttpFoundation\RequestStack
     */
    protected RequestStack $requestStack;

    /**
     * Constructs an AdminFilterForm object.
     *
     * @param \Drupal\event_registration_manager\Service\EventStorageService $event_storage
     *   The event storage service.
     * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
     *   The request stack.
     */
    public function __construct(
        EventStorageService $event_storage,
        RequestStack $request_stack
    ) {
        $this->eventStorage = $event_storage;
        $this->requestStack = $request_stack;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('event_registration_manager.storage'),
            $container->get('request_stack')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'event_registration_manager_admin_filter_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $request = $this->requestStack->getCurrentRequest();

        $current_date = $request->query->get('event_date', '');
        $current_event = $request->query->get('event_name', '');

        $event_dates = $this->eventStorage->getRegistrationEventDates();

        $event_names = [];
        if (!empty($current_date)) {
            $event_names = $this->eventStorage->getRegistrationEventNamesByDate($current_date);
        }

        $form['#attached']['library'][] = 'event_registration_manager/admin_listing';

        $form['filters'] = [
            '#type' => 'container',
            '#attributes' => [
                'class' => ['registration-filters'],
            ],
        ];

        $form['filters']['title'] = [
            '#markup' => '<h3>' . $this->t('Filter Registrations') . '</h3>',
        ];

        $form['filters']['filter_row'] = [
            '#type' => 'container',
            '#attributes' => [
                'class' => ['filters-row'],
            ],
        ];

        $form['filters']['filter_row']['event_date'] = [
            '#type' => 'select',
            '#title' => $this->t('Event Date'),
            '#options' => ['' => $this->t('- All Dates -')] + $event_dates,
            '#default_value' => $current_date,
            '#attributes' => [
                'id' => 'filter-event-date',
                'class' => ['filter-select'],
            ],
        ];

        $form['filters']['filter_row']['event_name'] = [
            '#type' => 'select',
            '#title' => $this->t('Event Name'),
            '#options' => ['' => $this->t('- All Events -')] + $event_names,
            '#default_value' => $current_event,
            '#attributes' => [
                'id' => 'filter-event-name',
                'class' => ['filter-select'],
            ],
        ];

        $form['filters']['filter_row']['actions'] = [
            '#type' => 'actions',
        ];

        $form['filters']['filter_row']['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Apply Filters'),
            '#button_type' => 'primary',
        ];

        $form['filters']['filter_row']['actions']['reset'] = [
            '#type' => 'link',
            '#title' => $this->t('Reset'),
            '#url' => Url::fromRoute('event_registration_manager.admin_listing'),
            '#attributes' => [
                'class' => ['button', 'reset-filters'],
            ],
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $event_date = $form_state->getValue('event_date');
        $event_name = $form_state->getValue('event_name');

        $query = [];
        if (!empty($event_date)) {
            $query['event_date'] = $event_date;
        }
        if (!empty($event_name)) {
            $query['event_name'] = $event_name;
        }

        $form_state->setRedirect('event_registration_manager.admin_listing', [], [
            'query' => $query,
        ]);
    }

}
