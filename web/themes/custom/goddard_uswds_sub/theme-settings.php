<?php
  
function goddard_uswds_sub_form_system_theme_settings_alter(&$form, \Drupal\Core\Form\FormStateInterface &$form_state, $form_id = NULL) {
  // Work-around for a core bug affecting admin themes. See issue #943212.
  if (isset($form_id)) {
    return;
  }

  $form['theme_headings'] = array(
    '#type' => 'details',
    '#title' => t('Theme headings and titles'),
    '#open' => FALSE,   
  );

  $form['theme_headings']['center_name'] = array(
    '#type'          => 'textfield',
    '#title'         => t('Center Name'),
    '#default_value' => theme_get_setting('center_name'),
    '#description'   => t("Appears just under the NASA branding. (Ex: Goddard Space Flight Center)"),
  );

  $form['theme_headings']['office_name'] = array(
    '#type'          => 'textfield',
    '#title'         => t('Office Name'),
    '#default_value' => theme_get_setting('office_name'),
    '#description'   => t("Appears over the picture just above the menu. (Ex: Office of Technology and Innovation)"),
  );

  $form['footer_text'] = array(
    '#type' => 'details',
    '#title' => t('Footer Text and Details'),
    '#open' => FALSE,   
  );

  $form['footer_text']['footer_info_line_one'] = array(
    '#type'          => 'textfield',
    '#title'         => t('Footer Info Line 1'),
    '#default_value' => theme_get_setting('footer_info_line_one'),
    '#format' => 'full_html',
  );

  $form['footer_text']['footer_info_line_two'] = array(
    '#type'          => 'textfield',
    '#title'         => t('Footer Info Line 2'),
    '#default_value' => theme_get_setting('footer_info_line_two'),
    '#format' => 'full_html',
  );
  
  $form['footer_text']['footer_info_line_three'] = array(
    '#type'          => 'textfield',
    '#title'         => t('Footer Info Line 3'),
    '#default_value' => theme_get_setting('footer_info_line_three'),
    '#description'   => t("This text appears just below the NASA logo in the footer."),
    '#format' => 'full_html',
  );  

  // Theme Settings style.
  unset($form['theme_settings']); 
  
  // Header style.
  //unset($form['header_style_fieldset']);

  // Footer style.
  unset($form['footer_style_fieldset']);

  // Menu Behavior style.
  unset($form['menu_fieldset']); 
  
  // Edge to edge style.
  unset($form['edge_to_edge_fieldset']);  
 
  // Logo style.
  unset($form['logo']);  
 
  // Favicon style.
  unset($form['favicon']);   
}