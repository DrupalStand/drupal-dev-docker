<?php

namespace Drupal\calendar\Plugin\Block;

use Drupal\calendar\CalendarHelper;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a "Calendar legend" block.
 * @Block(
 *   id = "calendar_legend_block",
 *   admin_label = @Translation("Calendar legend"),
 * )
 */
class CalendarLegend extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $options = CalendarHelper::listCalendarViews();

    $config = $this->getConfiguration();
    $form['calendar_legend_view'] = [
       '#type' => 'select',
       '#title' => $this->t('Legend View'),
       '#description' => $this->t('Choose the view display that contains the settings for the stripes that should be displayed in a legend in this block. Note that if you change the stripe values in that view you will need to clear the cache to pick up the new values in this block.'),
       '#default_value' => isset($config['calendar_legend_view_settings_view']) ? $config['calendar_legend_view_settings_view'] : '',
       '#options' => $options,
     ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('calendar_legend_view_settings_view', $form_state->getValue('calendar_legend_view'));
    drupal_set_message($this->t('The view for the calendar legend has been set.'));
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $view_and_display_id = $config['calendar_legend_view_settings_view'];

    // @todo don't return anything if no legend is needed

    $block = [
      '#theme' => 'calendar_stripe_legend',
      '#view_and_display_id' => $view_and_display_id,
      '#title' => $this->t('Calendar Legend'),
    ];

    return $block;
  }
}
