<?php

namespace Drupal\event_registration_manager\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Url;
use Drupal\event_registration_manager\Service\EventStorageService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controller for admin registration listing page.
 */
class AdminListingController extends ControllerBase
{

    /**
     * The event storage service.
     *
     * @var \Drupal\event_registration_manager\Service\EventStorageService
     */
    protected EventStorageService $eventStorage;

    /**
     * Constructs an AdminListingController object.
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
     * Lists all registrations with filters.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The request object.
     *
     * @return array
     *   A render array.
     */
    public function listRegistrations(Request $request): array
    {
        $build = [];

        $build['#attached']['library'][] = 'event_registration_manager/admin_styles';
        $build['#attached']['library'][] = 'event_registration_manager/admin_listing';

        // Get filter values from query parameters.
        $filter_date = $request->query->get('event_date', '');
        $filter_event = $request->query->get('event_name', '');

        $filters = [];
        if (!empty($filter_date)) {
            $filters['event_date'] = $filter_date;
        }
        if (!empty($filter_event)) {
            $filters['event_name'] = $filter_event;
        }

        // Build filter form.
        $build['filters'] = $this->buildFilterForm($filter_date, $filter_event);

        // Get registrations.
        $registrations = $this->eventStorage->getRegistrations($filters);
        $total_count = count($registrations);

        // Registration count display.
        $build['count'] = [
            '#type' => 'container',
            '#attributes' => [
                'class' => ['registration-count-wrapper'],
                'id' => 'registration-count',
            ],
        ];

        $build['count']['content'] = [
            '#markup' => '<div class="registration-count">' .
                $this->t('Total Participants: <strong>@count</strong>', ['@count' => $total_count]) .
                '</div>',
        ];

        // Export button.
        $export_url = Url::fromRoute('event_registration_manager.export_csv', [], [
            'query' => $filters,
        ]);

        $build['actions'] = [
            '#type' => 'container',
            '#attributes' => [
                'class' => ['registration-actions'],
            ],
        ];

        $build['actions']['export'] = [
            '#type' => 'link',
            '#title' => $this->t('Export as CSV'),
            '#url' => $export_url,
            '#attributes' => [
                'class' => ['button', 'button--primary', 'export-csv-button'],
                'id' => 'export-csv-btn',
            ],
        ];

        // Build registration table.
        $header = [
            $this->t('Name'),
            $this->t('Email'),
            $this->t('Event Date'),
            $this->t('Event Name'),
            $this->t('College Name'),
            $this->t('Department'),
            $this->t('Submission Date'),
        ];

        $rows = [];
        foreach ($registrations as $registration) {
            $rows[] = [
                $registration['full_name'],
                $registration['email'],
                date('F j, Y', strtotime($registration['event_date'])),
                $registration['event_name'],
                $registration['college_name'],
                $registration['department'],
                date('F j, Y g:i A', $registration['created']),
            ];
        }

        $build['table_wrapper'] = [
            '#type' => 'container',
            '#attributes' => [
                'id' => 'registrations-table-wrapper',
                'class' => ['registrations-table-wrapper'],
            ],
        ];

        $build['table_wrapper']['table'] = [
            '#type' => 'table',
            '#header' => $header,
            '#rows' => $rows,
            '#empty' => $this->t('No registrations found matching the selected filters.'),
            '#attributes' => [
                'class' => ['registrations-table'],
            ],
        ];

        return $build;
    }

    /**
     * Builds the filter form.
     *
     * @param string $current_date
     *   Currently selected date.
     * @param string $current_event
     *   Currently selected event name.
     *
     * @return array
     *   A render array for the filter form.
     */
    protected function buildFilterForm(string $current_date, string $current_event): array
    {
        $event_dates = $this->eventStorage->getRegistrationEventDates();

        $event_names = [];
        if (!empty($current_date)) {
            $event_names = $this->eventStorage->getRegistrationEventNamesByDate($current_date);
        }

        $form = [
            '#type' => 'container',
            '#attributes' => [
                'class' => ['registration-filters'],
                'id' => 'registration-filters',
            ],
        ];

        $form['title'] = [
            '#markup' => '<h3>' . $this->t('Filter Registrations') . '</h3>',
        ];

        $form['filters_row'] = [
            '#type' => 'container',
            '#attributes' => [
                'class' => ['filters-row'],
            ],
        ];

        $form['filters_row']['event_date'] = [
            '#type' => 'select',
            '#title' => $this->t('Event Date'),
            '#options' => ['' => $this->t('- All Dates -')] + $event_dates,
            '#default_value' => $current_date,
            '#attributes' => [
                'id' => 'filter-event-date',
                'class' => ['filter-select'],
            ],
        ];

        $form['filters_row']['event_name'] = [
            '#type' => 'select',
            '#title' => $this->t('Event Name'),
            '#options' => ['' => $this->t('- All Events -')] + $event_names,
            '#default_value' => $current_event,
            '#attributes' => [
                'id' => 'filter-event-name',
                'class' => ['filter-select'],
            ],
        ];

        $form['filters_row']['submit'] = [
            '#type' => 'html_tag',
            '#tag' => 'button',
            '#value' => $this->t('Apply Filters'),
            '#attributes' => [
                'type' => 'button',
                'id' => 'apply-filters',
                'class' => ['button', 'button--primary'],
            ],
        ];

        $form['filters_row']['reset'] = [
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
     * AJAX callback to get event names by date.
     *
     * @param string $event_date
     *   The event date.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON response with event names.
     */
    public function getEventNamesByDate(string $event_date): JsonResponse
    {
        $event_names = $this->eventStorage->getRegistrationEventNamesByDate($event_date);

        $options = ['' => $this->t('- All Events -')->render()];
        foreach ($event_names as $name) {
            $options[$name] = $name;
        }

        return new JsonResponse($options);
    }

    /**
     * Exports registrations as CSV.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The request object.
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     *   A streamed CSV response.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $filter_date = $request->query->get('event_date', '');
        $filter_event = $request->query->get('event_name', '');

        $filters = [];
        if (!empty($filter_date)) {
            $filters['event_date'] = $filter_date;
        }
        if (!empty($filter_event)) {
            $filters['event_name'] = $filter_event;
        }

        $registrations = $this->eventStorage->getRegistrations($filters);

        $response = new StreamedResponse(function () use ($registrations) {
            $handle = fopen('php://output', 'w');

            // Write headers.
            fputcsv($handle, [
                'ID',
                'Full Name',
                'Email',
                'College Name',
                'Department',
                'Event Name',
                'Event Date',
                'Category',
                'Registration Date',
            ]);

            // Write data rows.
            foreach ($registrations as $registration) {
                $category_label = EventStorageService::CATEGORIES[$registration['category']] ?? $registration['category'];

                fputcsv($handle, [
                    $registration['id'],
                    $registration['full_name'],
                    $registration['email'],
                    $registration['college_name'],
                    $registration['department'],
                    $registration['event_name'],
                    date('Y-m-d', strtotime($registration['event_date'])),
                    $category_label,
                    date('Y-m-d H:i:s', $registration['created']),
                ]);
            }

            fclose($handle);
        });

        $filename = 'event_registrations_' . date('Y-m-d_His') . '.csv';

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }

}
