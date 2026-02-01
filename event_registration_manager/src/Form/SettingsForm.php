<?php

namespace Drupal\event_registration_manager\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Event Registration Manager settings.
 */
class SettingsForm extends ConfigFormBase
{

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames()
    {
        return ['event_registration_manager.settings'];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'event_registration_manager_settings_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $config = $this->config('event_registration_manager.settings');

        $form['#attached']['library'][] = 'event_registration_manager/admin_styles';

        $form['admin_settings'] = [
            '#type' => 'details',
            '#title' => $this->t('Administrator Notification Settings'),
            '#open' => TRUE,
        ];

        $form['admin_settings']['admin_email'] = [
            '#type' => 'email',
            '#title' => $this->t('Admin Notification Email'),
            '#description' => $this->t('Enter the email address to receive registration notifications.'),
            '#default_value' => $config->get('admin_email'),
            '#maxlength' => 255,
        ];

        $form['admin_settings']['admin_notification_enabled'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Enable Admin Email Notifications'),
            '#description' => $this->t('When enabled, the admin will receive an email for each new registration.'),
            '#default_value' => $config->get('admin_notification_enabled'),
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        $admin_enabled = $form_state->getValue('admin_notification_enabled');
        $admin_email = $form_state->getValue('admin_email');

        if ($admin_enabled && empty($admin_email)) {
            $form_state->setErrorByName('admin_email', $this->t('Admin email is required when notifications are enabled.'));
        }

        parent::validateForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $this->config('event_registration_manager.settings')
            ->set('admin_email', $form_state->getValue('admin_email'))
            ->set('admin_notification_enabled', (bool) $form_state->getValue('admin_notification_enabled'))
            ->save();

        parent::submitForm($form, $form_state);
    }

}
