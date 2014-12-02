<?php
/**
 * Created by PhpStorm.
 * User: btm
 * Date: 11/19/14
 * Time: 1:55 PM
 */

namespace Drupal\campaignmonitor\Form;


use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\campaignmonitor\WebService\CampaignMonitorRestClient;

class CampaignMonitorAddListForm extends ConfigFormBase{

  public function __construct($rest_client) {

    $this->rest_client = $rest_client;
  }


  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('campaignmonitor.rest_client')
    );

  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {

    return 'campaign_monitor_add_list_form';
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


    $form['listname'] = array(
      '#type' => 'textfield',
      '#title' => t('List name'),
      '#default_value' =>  '',
      '#required' => TRUE,
    );

    $form['UnsubscribePage'] = array(
      '#type' => 'textfield',
      '#title' => t('Unsubscribe page'),
      '#default_value' => '',
    );

    $form['ConfirmationSuccessPage'] = array(
      '#type' => 'textfield',
      '#title' => t('Confirmation success page'),
      '#default_value' => '',
    );

    $form['ConfirmedOptIn'] = array(
      '#type' => 'checkbox',
      '#title' => t('Confirmed Opt In'),
      '#default_value' =>  '',

    );


//    $form['#submit'] = array('campaignmonitor_admin_settings_list_create_form_submit');
//    $form['#validate'][] = 'campaignmonitor_admin_settings_list_create_form_validate';
    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $cm = CampaignMonitorRestClient::getConnector();

    $result = $cm->createList($form_state->getvalue('listname'),
      $form_state->getValue('UnsubscribePage'),
      $form_state->getValue('ConfirmedOptIn'),
      $form_state->getValue('ConfirmationSuccessPage'));

    if (!$result) {
      $error = $cm->getLatestError();
      $form_state->setErrorByName('listname', $error['message']);
      return FALSE;
    }

    return TRUE;
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
    _block_rehash();
    drupal_set_message(t('List has been created at Campaign monitor.'), 'status');
  }
}