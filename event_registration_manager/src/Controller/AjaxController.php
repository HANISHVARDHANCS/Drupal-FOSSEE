<?php

namespace Drupal\event_registration_manager\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\event_registration_manager\Service\EventStorageService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for AJAX callbacks.
 */
class AjaxController extends ControllerBase
{

    /**
     * The event storage service.
     *
     * @var \Drupal\event_registration_manager\Service\EventStorageService
     */
    protected EventStorageService $eventStorage;

    /**
     * Constructs an AjaxController object.
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
     * Gets event dates for a category.
     *
     * @param string $category
     *   The event category.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON response with event dates.
     */
    public function getEventDates(string $category): JsonResponse
    {
        $dates = $this->eventStorage->getEventDatesByCategory($category);

        $options = ['' => $this->t('- Select Date -')->render()];
        foreach ($dates as $date => $formatted) {
            $options[$date] = $formatted;
        }

        return new JsonResponse($options);
    }

    /**
     * Gets event names for a category and date.
     *
     * @param string $category
     *   The event category.
     * @param string $event_date
     *   The event date.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON response with event names.
     */
    public function getEventNames(string $category, string $event_date): JsonResponse
    {
        $events = $this->eventStorage->getEventsByCategoryAndDate($category, $event_date);

        $options = ['' => $this->t('- Select Event -')->render()];
        foreach ($events as $id => $name) {
            $options[$id] = $name;
        }

        return new JsonResponse($options);
    }

}
