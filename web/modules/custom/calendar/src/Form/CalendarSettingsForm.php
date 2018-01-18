<?php

namespace Drupal\calendar\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the admin configuration form for the calendar module.
 */
class CalendarSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'calendar.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'calendar_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $calendar_config = $this->config('calendar.settings');

    $form['calendar_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Calendar Administration'),
      '#open' => TRUE,
    ];

    $form['calendar_settings']['track_date'] = [
      '#type' => 'radios',
      '#title' => $this->t('Track current date in session'),
      '#default_value' => $calendar_config->get('track_date'),
      '#options' => [
        0 => $this->t("Never"),
        1 => $this->t('For authenticated users'),
        2 => $this->t('For all users'),
      ],
      '#description' => $this->t("Store session information about the user's current date as they move back and forth through the calendar. Without this setting users will revert to the current day each time they choose a new calendar period (year, month, week, or day). With this option set they will move to a day that conforms to the time period they were viewing before they switched. Requires session tracking which is not ordinarily enabled for anonymous users."),
    ];

    $form['calendar_settings']['add_colorbox'] = [
      '#type' => 'radios',
      '#title' => $this->t('Add Colorbox settings to Node calendar templates'),
      '#default_value' => $calendar_config->get('add_colorbox'),
      '#options' => [
        0 => $this->t('No'),
        1 => $this->t('Yes'),
      ],
      '#description' => $this->t('To try the Colorbox settings, choose the option to add Colorbox settings to the calendar templates. Install and enable the Colorbox module, following the instructions on the Colorbox project page, then create a new calendar from a template using any date field in the Node base table. The calendar will be set up to display the calendar items in a Colorbox.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('calendar.settings')
      ->set('track_date', $form_state->getValue('track_date'))
      ->set('add_colorbox', $form_state->getValue('add_colorbox'))
      ->save();

  }

}
