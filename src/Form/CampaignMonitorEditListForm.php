<?php
/**
 * Created by PhpStorm.
 * User: btm
 * Date: 12/1/14
 * Time: 6:45 PM
 */

namespace Drupal\campaignmonitor\Form;


use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\campaignmonitor\WebService\CampaignMonitorRestClient;
use Drupal\Component\Utility\String;

class CampaignMonitorEditListForm extends ConfigFormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'campaignmonitor_edit_list';
  }

  protected function getParam($paramName = '') {
    $request = $this->getRequest();
    $param = $request->attributes->get($paramName);

    return $param;
  }

  protected function getList($list_id) {


    // Download WebService\CampaignMonitorRestClient information from Campaign Monitor.
//    $cm = \Drupal::service('campaignmonitor.rest_client');
    $cm = CampaignMonitorRestClient::getConnector();

    $list = $cm->getExtendedList($list_id);

    return $list;
  }

  /**
   * Form constructor.
   *
   * @param array                                $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $list = array();
    $list_id = $this->getParam('list');

    if ($list_id) {
      $list = $this->getList($list_id);
    }


    $form = array('#tree' => TRUE);


    // Add list id to the form.
    $form['listId'] = array(
      '#type'  => 'hidden',
      '#value' => $list_id,
    );

    // Set this form name (index).
    $form_key = 'list_' . $list_id;

    // Get previously saved list information.
    $defaults = $this->config('campaignmonitor.' . $form_key)->get();

    $form[$form_key]['status'] = array(
      '#type'        => 'fieldset',
      '#title'       => t('Enable list'),
      '#description' => t('Enable the list to configure it and use it on the site.'),
      '#collapsible' => FALSE,
      '#collapsed'   => FALSE,
    );

    $form[$form_key]['status']['enabled'] = array(
      '#type'          => 'checkbox',
      '#title'         => t('Enable'),
      '#default_value' => isset($defaults['status']['enabled']) ? $defaults['status']['enabled'] : 1,
      '#attributes'    => array('class' => array('enabled-list-checkbox')),
    );

    $form[$form_key]['options'] = array(
      '#type'        => 'fieldset',
      '#title'       => t('List options'),
      '#description' => t('Changing the values will result in an update of the values on the Campaign Monitor homepage.'),
      '#collapsible' => TRUE,
      '#collapsed'   => FALSE,
      '#states'      => array(
        'visible' => array(
          '.enabled-list-checkbox' => array('checked' => TRUE),
        ),
      ),
    );

    $form[$form_key]['options']['listname'] = array(
      '#type'          => 'textfield',
      '#title'         => t('List name'),
      '#default_value' => $list['name'],
      '#required'      => TRUE,
      '#states'        => array(
        'visible' => array(
          ':input[name="status[enabled]"]' => array('checked' => TRUE),
        ),
      ),
    );

    $form[$form_key]['options']['UnsubscribePage'] = array(
      '#type'          => 'textfield',
      '#title'         => t('Unsubscribe page'),
      '#default_value' => $list['details']['UnsubscribePage'],
    );

    $form[$form_key]['options']['ConfirmationSuccessPage'] = array(
      '#type'          => 'textfield',
      '#title'         => t('Confirmation success page'),
      '#default_value' => $list['details']['ConfirmationSuccessPage'],
    );

    $form[$form_key]['options']['ConfirmedOptIn'] = array(
      '#type'          => 'checkbox',
      '#title'         => t('Confirmed Opt In'),
      '#default_value' => $list['details']['ConfirmedOptIn'],
    );

    $form[$form_key]['display'] = array(
      '#type'        => 'fieldset',
      '#title'       => t('Display options'),
      '#collapsible' => TRUE,
      '#collapsed'   => FALSE,
      '#states'      => array(
        'visible' => array(
          '.enabled-list-checkbox' => array('checked' => TRUE),
        ),
      ),
    );

    $form[$form_key]['display']['name'] = array(
      '#type'          => 'checkbox',
      '#title'         => t('Display Name field'),
      '#description'   => t('Whether the Name field should be displayed when subscribing.'),
      '#default_value' => isset($defaults['display']['name']) ? $defaults['display']['name'] : 0,
      '#attributes'    => array('class' => array('tokenable', 'tokenable-name')),
    );

    // List custom fields.
    if (!empty($list['CustomFields'])) {
      $options = array();
      foreach ($list['CustomFields'] as $key => $field) {
        // Form API can't handle keys with [] in all cases.
        $token_form_key = str_replace(array('[', ']'), '', $key);
        $options[$token_form_key] = $field['FieldName'];
      }

      $form[$form_key]['CustomFields'] = array(
        '#type'        => 'fieldset',
        '#title'       => t('Custom fields'),
        '#collapsible' => TRUE,
        '#collapsed'   => FALSE,
        '#attributes'  => array('class' => array('tokenable', 'tokenable-custom-fields')),
        '#states'      => array(
          'visible' => array(
            '.enabled-list-checkbox' => array('checked' => TRUE),
          ),
        ),
      );

      $form[$form_key]['CustomFields']['selected'] = array(
        '#type'          => 'checkboxes',
        '#title'         => t('Available fields'),
        '#description'   => t('Select the fields that should be displayed on subscription forms.'),
        '#options'       => $options,
        '#default_value' => isset($defaults['CustomFields']['selected']) ? $defaults['CustomFields']['selected'] : array(),
      );
    }

    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $form[$form_key]['tokens'] = array(
        '#type'        => 'fieldset',
        '#title'       => t('Field tokens'),
        '#collapsible' => TRUE,
        '#collapsed'   => FALSE,
      );

      $form[$form_key]['tokens']['name'] = array(
        '#type'          => 'textfield',
        '#title'         => t('Name field'),
        '#default_value' => isset($defaults['tokens']['name']) ? $defaults['tokens']['name'] : '[current-user:name]',
        '#states'        => array(
          'visible' => array(
            '.tokenable-name' => array('checked' => TRUE),
          ),
        ),
      );

      if (!empty($list['CustomFields'])) {
        foreach ($list['CustomFields'] as $key => $field) {
          if ($field['DataType'] == 'MultiSelectMany') {
            // We can't handle this type of custom field (with tokens).
            continue;
          }

          // Form API can't handle keys with [] in all cases.
          $token_form_key = str_replace(array('[', ']'), '', $key);
          $form[$form_key]['tokens'][$token_form_key] = array(
            '#type'          => 'textfield',
            '#title'         => t('Custom field (@name)', array('@name' => $field['FieldName'])),
            '#default_value' => isset($defaults['tokens'][$token_form_key]) ? $defaults['tokens'][$token_form_key] : '',
            '#states'        => array(
              'visible' => array(
                ':input[name="' . $form_key . '[CustomFields][selected][' . $token_form_key . ']' . '"]' => array('checked' => TRUE),
              ),
            ),
          );
        }
      }

      $form[$form_key]['tokens']['token_tree'] = array(
        '#theme' => 'token_tree',
      );
    }

    // Give the form system look and feel.
    //$form = system_settings_form($form);

    // Add validation function.
    // $form['#validate'][] = 'campaignmonitor_admin_settings_list_edit_validate';

    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Build array with basic information.
    $values = $form_state->getValue('list_' . $form_state->getValue('listId'));
    $options = array(
      'Title'           => String::checkPlain($values['options']['listname']),
      'UnsubscribePage' => String::checkPlain($values['options']['UnsubscribePage']),
      'ConfirmedOptIn'  => String::checkPlain($values['options']['ConfirmedOptIn']) ? TRUE : FALSE,
          'ConfirmationSuccessPage' => String::checkPlain($values['options']['ConfirmationSuccessPage']),
    );

    // Get connected.
    $cm = CampaignMonitorRestClient::getConnector();

    // Update the information.
    if (!$cm->updateList($form_state->getValue('listId'), $options)) {
      $error = $cm->getLatestError();
      $form_state->setError($form, $error['message']);
      drupal_set_message(t('The list options were not updated correctly at Campaign Monitor.'), 'error');
      return FALSE;
    }

    // Remove list options.

   // unset($form_state['values']['campaignmonitor_list_' . $form_state['values']['listId']]['options']);


    // Save display options and custom field selection.
  }


  /**
   * Form submission handler.
   *
   * @param array                                $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $list_id = 'list_' . $form_state->getValue('listId');
    $config = $this->config('campaignmonitor.' . $list_id);

    $values = $form_state->getValues();

    foreach ($values as $key => $value) {
      $config->set($key, $value);
    }

    $config->save();

    $form_state->setRedirect('campaignmonitor.admin_lists');
    parent::submitForm($form, $form_state);
  }
}