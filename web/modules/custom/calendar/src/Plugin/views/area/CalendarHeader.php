<?php

namespace Drupal\calendar\Plugin\views\area;

use Drupal\calendar\CalendarHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\area\TokenizeAreaPluginBase;

/**
 * Views area Calendar Header area.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("calendar_header")
 */
class CalendarHeader extends TokenizeAreaPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    // Override defaults to from parent.
    $options['tokenize']['default'] = TRUE;
    $options['empty']['default'] = TRUE;
    // Provide our own defaults.
    $options['content'] = ['default' => ''];
    $options['pager_embed'] = ['default' => FALSE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['content'] = [
      '#title' => $this->t('Heading'),
      '#type' => 'textfield',
      '#default_value' => $this->options['content'],
    ];
    $form['pager_embed'] = [
      '#title' => $this->t('Use Pager'),
      '#type' => 'checkbox',
      '#default_value' => $this->options['pager_embed'],
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    if (!$empty || !empty($this->options['empty'])) {

      $argument = CalendarHelper::getDateArgumentHandler($this->view);

      $render = [];
      $header_text = $this->renderTextField($this->options['content']);

      if (!$this->options['pager_embed']) {
        $render = [
          '#theme' => 'calendar_header',
          '#title' => $header_text,
          '#empty' => $empty,
          '#granularity' => $argument->getGranularity(),
        ];
      }
      else {
        if ($this->view->display_handler->renderPager()) {
          $exposed_input = isset($this->view->exposed_raw_input) ? $this->view->exposed_raw_input : NULL;
          $render = $this->view->renderPager($exposed_input);
          // Override the exclude option of the pager.
          $render['#exclude'] = FALSE;
          $render['#items']['current'] = $header_text;
        }
      }
      return $render;

    }

    return [];
  }

  /**
   * Render a text area with \Drupal\Component\Utility\Xss::filterAdmin().
   */
  public function renderTextField($value) {
    if ($value) {
      return $this->sanitizeValue($this->tokenizeValue($value), 'xss_admin');
    }
    return '';
  }

}
