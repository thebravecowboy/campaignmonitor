<?php
/**
 * Created by PhpStorm.
 * User: btm
 * Date: 11/19/14
 * Time: 2:05 PM
 */

namespace Drupal\campaignmonitor\Form;


use Drupal\campaignmonitor\WebService\CampaignMonitorRestClient;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Utility\Token;
use Drupal\Component\Utility\String;
use Symfony\Component\HttpFoundation\RedirectResponse;


class CampaignMonitorSubscriptionForm extends FormBase{

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'campaign_monitor_subscription_form';
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
    $account = \Drupal::currentUser();
    $list_id = $form_state->getValue('list_id');
    $list_options = $this->config('campaignmonitor.' . $list_id)->get();


    // Get Campaign Monitor object.
    $cm = CampaignMonitorRestClient::getConnector();

    // Get lists from Campaign Monitor (or the local cache).
    $list = $cm->getExtendedList($list_id);
    // Set options for the form.
    $form = array(
      '#tree' => TRUE,
      '#attributes' => array(
        'class' => array(
          'campaignmonitor-subscribe-form',
          'campaignmonitor-subscribe-form-' . str_replace(' ', '-', Unicode::strtolower($list['name'])),
        ),
      ),
    );

    // Try to get the e-mail address from the user object.
    if ($account->uid != 0) {
      $email = $account->mail;
    }

    // Should the name field be displayed for this user.
    if (isset($list_options['display']['name']) && $list_options['display']['name']) {
      // Token replace if the token module is present.
      if (isset($list_options['tokens']['name'])  && $account->uid != 0) {
        $name = \Drupal::token()->replace($list_options['tokens']['name'], array(), array('clear'=> TRUE));
      }

      // Check if the user is subscribed and get name from Campaign Monitor.
      if (!empty($email) && $cm->isSubscribed($list_id, $email)) {
        // If subscribed, get her/his name from Campaign Monitor.
        $subscriber = $cm->getSubscriber($list_id, $email);
        $name = isset($subscriber['Name']) ? $subscriber['Name'] : $name;
      }

      $form['name'] = array(
        '#type' => 'textfield',
        '#title' => t('Name'),
        '#required' => TRUE,
        '#maxlength' => 200,
        '#default_value' => isset($name) ? $name : '',
      );
    }

    $form['email'] = array(
      '#type' => 'textfield',
      '#title' => t('Email'),
      '#required' => TRUE,
      '#maxlength' => 200,
      '#default_value' => isset($email) ? $email : '',
    );

    foreach ($list['CustomFields'] as $key => $field) {
      // Form API can't handle keys with [] in all cases.
      $form_key = str_replace(array('[', ']'), '', $key);

      // Check if field should be displayed.
      if (isset($list_options['CustomFields']) && !$list_options['CustomFields']['selected'][$form_key]) {
        // Field is not selected, so continue.
        continue;
      }

      // Token replace default value, if the token module is present.
      $token = '';
      if (isset($list_options['tokens'][$form_key])) {
        $token = \Drupal::token()->replace($list_options['tokens'][$form_key]);
      }

      switch ($field['DataType']) {
        case 'Text':
          $form['CustomFields'][$form_key] = array(
            '#type' => 'textfield',
            '#title' => String::checkPlain($field['FieldName']),
            '#maxlength' => 200,
            '#default_value' => isset($subscriber['CustomFields'][$field['FieldName']]) ? $subscriber['CustomFields'][$field['FieldName']] : $token,
          );
          break;

        case 'MultiSelectOne':
          $options = array();
          foreach ($field['FieldOptions'] as $option) {
            $options[$option] = $option;
          }

          $form['CustomFields'][$form_key] = array(
            '#type' => 'select',
            '#title' => String::checkPlain($field['FieldName']),
            '#options' => $options,
            '#default_value' => isset($subscriber['CustomFields'][$field['FieldName']]) ? $subscriber['CustomFields'][$field['FieldName']] : $token,
          );
          break;

        case 'MultiSelectMany':
          $options = array();
          foreach ($field['FieldOptions'] as $option) {
            $options[$option] = $option;
          }

          // If one value was selected, default is a string else an array.
          $cm_default = isset($subscriber['CustomFields'][$field['FieldName']]) ? $subscriber['CustomFields'][$field['FieldName']] : array();
          $is_array = is_array($cm_default); // Exspensive.
          $default = array();
          foreach ($options as $value) {
            if ($is_array) {
              if (in_array($value, $cm_default)) {
                $default[$value] = $value;
              }
            }
            elseif ($cm_default == $value) {
              $default[$cm_default] = $cm_default;
            }
            else {
              $default[$value] = 0;
            }
          }

          $form['CustomFields'][$form_key] = array(
            '#type' => 'checkboxes',
            '#title' => String::checkPlain($field['FieldName']),
            '#options' => $options,
            '#default_value' => $default,
          );
          break;

        case 'Number':
          $form['CustomFields'][$form_key] = array(
            '#type' => 'textfield',
            '#title' => String::checkPlain($field['FieldName']),
            '#default_value' => isset($subscriber['CustomFields'][$field['FieldName']]) ? $subscriber['CustomFields'][$field['FieldName']] : $token,
          );
          break;

        case 'Date':
          // Load jQuery datepicker to ensure the right date format.
//          drupal_add_library('system','ui.datepicker');
          $form['CustomFields'][$form_key] = array(
            '#type' => 'textfield',
            '#title' => String::checkPlain($field['FieldName']),
            '#default_value' => isset($subscriber['CustomFields'][$field['FieldName']]) ? $subscriber['CustomFields'][$field['FieldName']] : $token,
            '#attributes' => array('class' => array('campaignmonitor-date')),
            '#attached' => array(
              'js' => array(
                'type' => 'file',
                'data' => drupal_get_path('module', 'campaignmonitor') . '/js/campaignmonitor.js',
              ),
            ),
          );
          break;
      }
    }

    $form['list_id'] = array(
      '#type' => 'hidden',
      '#default_value' => $list_id,
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Subscribe'),
    );

    return $form;
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
// Get a Campaign Monitor object.
    $cm = CampaignMonitorRestClient::getConnector();

    $custom_fields = array();
    if ($form_state->getValue('CustomFields')) {
      foreach ($form_state->getValue('CustomFields') as $key => $field) {
        if (is_array($field)) {
          // Filter out non-selected values.
          $field = array_filter($field);
          // Transform two level array into one level.
          foreach ($field as $value) {
            $custom_fields[] = array(
              'Key' => String::checkPlain($key),
              'Value' => String::checkPlain($value)
            );
          }
        }
        else {
          // Add non-array custom fields.
          $custom_fields[] = array(
            'Key' => String::checkPlain($key),
            'Value' => String::checkPlain($field)
          );
        }
      }
    }

    $list_id = $form_state->getValue('list_id');
    $name = $form_state->getValue('name') ? String::checkPlain($form_state->getValue('name')) : '';
    $email = String::checkPlain($form_state->getValue('email'));

    // Update subscriber information or add new subscriber to the list.
    if (!$cm->subscribe($list_id, $email, $name, $custom_fields)) {
      $form_state->setError($form, t('You were not subscribed to the list, please try again.'));

      return FALSE;
    }

    // Check if the user should be sent to a subscribe page.
    $lists = $cm->getLists();
    if (isset($lists[$list_id]['details']['ConfirmationSuccessPage']) && !empty($lists[$list_id]['details']['ConfirmationSuccessPage'])) {
        $form_state->setRedirect($lists[$list_id]['details']['ConfirmationSuccessPage']);
    }
    else {
      drupal_set_message(t('You are now subscribed to the "@list" list.', array('@list' => $lists[$list_id]['name'])), 'status');
    }

    return TRUE;
  }
}