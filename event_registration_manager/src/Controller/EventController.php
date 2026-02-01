<?php

namespace Drupal\event_registration_manager\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\event_registration_manager\Service\EventStorageService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for event management pages.
 */
class EventController extends ControllerBase
{

    /**
     * The event storage service.
     *
     * @var \Drupal\event_registration_manager\Service\EventStorageService
     */
    protected EventStorageService $eventStorage;

    /**
     * Constructs an EventController object.
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
     * Lists all events.
     *
     * @return array
     *   A render array.
     */
    public function listEvents(): array
    {
        $events = $this->eventStorage->getAllEvents();

        $build = [];

        $build['#attached']['library'][] = 'event_registration_manager/admin_styles';

        if (empty($events)) {
            $build['empty'] = [
                '#type' => 'container',
                '#attributes' => [
                    'class' => ['event-list-empty', 'messages', 'messages--warning'],
                ],
                '#markup' => $this->t('No events have been configured yet. Click "Add Event" to create your first event.'),
            ];
            return $build;
        }

        $header = [
            $this->t('Event Name'),
            $this->t('Category'),
            $this->t('Event Date'),
            $this->t('Registration Opens'),
            $this->t('Registration Closes'),
            $this->t('Status'),
            $this->t('Operations'),
        ];

        $rows = [];
        $today = date('Y-m-d');

        foreach ($events as $event) {
            // Determine status.
            $status = $this->t('Closed');
            $status_class = 'status-closed';

            if ($event['registration_start_date'] <= $today && $event['registration_end_date'] >= $today) {
                $status = $this->t('Open');
                $status_class = 'status-open';
            } elseif ($event['registration_start_date'] > $today) {
                $status = $this->t('Upcoming');
                $status_class = 'status-upcoming';
            }

            // Build operations links.
            $operations = [
                '#type' => 'operations',
                '#links' => [
                    'edit' => [
                        'title' => $this->t('Edit'),
                        'url' => Url::fromRoute('event_registration_manager.event_edit', [
                            'event_id' => $event['id'],
                        ]),
                    ],
                    'delete' => [
                        'title' => $this->t('Delete'),
                        'url' => Url::fromRoute('event_registration_manager.event_delete', [
                            'event_id' => $event['id'],
                        ]),
                    ],
                ],
            ];

            $category_label = EventStorageService::CATEGORIES[$event['category']] ?? $event['category'];

            $rows[] = [
                $event['event_name'],
                $category_label,
                date('F j, Y', strtotime($event['event_date'])),
                date('F j, Y', strtotime($event['registration_start_date'])),
                date('F j, Y', strtotime($event['registration_end_date'])),
                [
                    'data' => [
                        '#markup' => '<span class="event-status ' . $status_class . '">' . $status . '</span>',
                    ],
                ],
                ['data' => $operations],
            ];
        }

        $build['events_table'] = [
            '#type' => 'table',
            '#header' => $header,
            '#rows' => $rows,
            '#empty' => $this->t('No events found.'),
            '#attributes' => [
                'class' => ['event-list-table'],
            ],
        ];

        return $build;
    }

}
