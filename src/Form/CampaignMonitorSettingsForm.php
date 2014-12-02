<?php
/**
 * Created by PhpStorm.
 * User: btm
 * Date: 11/19/14
 * Time: 1:44 PM
 */

namespace Drupal\campaignmonitor\Form;


use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\campaignmonitor\WebService;

class CampaignMonitorSettingsForm extends ConfigFormBase {
  public function __construct($rest_client) {

    $this->rest_client = $rest_client;
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'campaign_monitor_settings_form';
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('campaignmonitor.rest_client')
    );

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
    // Get account details.


    $account = $this->config($this->getConfigKey())->get('account');

    // Test if the library has been installed. If it has not been installed an
    // error message will be shown.
//    $cm = WebService\CampaignMonitorRestClient::getConnector();
   // $library_path = $cm->getLibraryPath();

    $form['account'] = array(
      '#type' => 'fieldset',
      '#title' => t('Account details'),
      '#description' => t('Enter your Campaign Monitor account information. See !link for more information.', array('!link' => \Drupal::l(t('the Campaign Monitor API documentation'), \Drupal\Core\Url::fromUri('http://www.campaignmonitor.com/api/required/')))),
      '#collapsible' => empty($account) ? FALSE : TRUE,
      '#collapsed' => empty($account) ? FALSE : TRUE,
      '#tree' => TRUE,
    );

    $form['account']['api_key'] = array(
      '#type' => 'textfield',
      '#title' => t('API Key'),
      '#description' => t('Your Campaign Monitor API Key. See <a href="http://www.campaignmonitor.com/api/required/">documentation</a>.'),
      '#default_value' => isset($account['api_key']) ? $account['api_key'] : '',
      '#required' => TRUE,
    );

    $form['account']['client_id'] = array(
      '#type' => 'textfield',
      '#title' => t('Client ID'),
      '#description' => t('Your Campaign Monitor Client ID. See <a href="http://www.campaignmonitor.com/api/required/">documentation</a>.'),
      '#default_value' => isset($account['client_id']) ? $account['client_id'] : '',
      '#required' => TRUE,
    );

    if (!empty($account)) {
      $defaults = $this->config($this->getConfigKey())->get('general');
      $form['general'] = array(
        '#type' => 'fieldset',
        '#title' => t('General settings'),
        '#collapsible' => TRUE,
        '#collapsed' => FALSE,
        '#tree' => TRUE,
      );

      $form['general']['cache_timeout'] = array(
        '#type' => 'textfield',
        '#title' => t('Cache timeout'),
        '#description' => t('Cache timeout in seconds for stats, subscribers and archive information.'),
        '#size' => 4,
        '#default_value' => isset($defaults['cache_timeout']) ? $defaults['cache_timeout'] : '360',
      );

//      $form['campaignmonitor_general']['library_path'] = array(
//        '#type' => 'textfield',
//        '#title' => t('Library path'),
//        '#description' => t('The installation path of the Campaign Monitor library, relative to the Drupal root.'),
//        '#default_value' => $library_path ? $library_path : (isset($defaults['library_path']) ? $defaults['library_path'] : ''),
//      );

      $form['general']['archive'] = array(
        '#type' => 'checkbox',
        '#title' => t('Newsletter archive'),
        '#description' => t('Create a block with links to HTML versions of past campaigns.'),
        '#default_value' => isset($defaults['archive']) ? $defaults['archive'] : 0,
      );

      $form['general']['logging'] = array(
        '#type' => 'checkbox',
        '#title' => t('Log errors'),
        '#description' => t('Log communication errors with the Campaign Monitor service, if any.'),
        '#default_value' => isset($defaults['logging']) ? $defaults['logging'] : 0,
      );

      $form['general']['instructions'] = array(
        '#type' => 'textfield',
        '#title' => t('Newsletter instructions'),
        '#description' => t('This message will be displayed to the user when subscribing to newsletters.'),
        '#default_value' => isset($defaults['instructions']) ? $defaults['instructions'] : t('Select the newsletters you want to subscribe to.'),
      );

      // Add cache clear button.
      $form['clear_cache'] = array(
        '#type' => 'fieldset',
        '#title' => t('Clear cached data'),
        '#description' => t('The information downloaded from Campaign Monitor is cached to speed up the website. The lists details, custom fields and other data may become outdated if these are changed at Campaign Monitor. Clear the cache to refresh this information.'),
      );

      $form['clear_cache']['clear'] = array(
        '#type' => 'submit',
        '#value' => t('Clear cached data'),
        '#submit' => array('campaignmonitor_clear_cache_submit'),
      );
    }

   // $form =  parent::buildForm($form, $form_state);
    //$form['#submit'][] = 'campaignmonitor_admin_settings_general_submit';
    //$form['#validate'][] = 'campaignmonitor_admin_settings_general_validate';

    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  public function getConfigKey() {
    return 'campaignmonitor.settings';
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
    $values = $form_state->getValues();
    $config = $this->config($this->getConfigKey());

    foreach($values as $key => $value) {
      $config->set($key, $value);
    }

    $config->save();


    parent::submitForm($form, $form_state);
    // If archive block has been selected, rehash the block cache.
//    if ((isset($form_state['values']['campaignmonitor_general']['archive']) && $form_state['values']['campaignmonitor_general']['archive']) ||
//      $form['campaignmonitor_account']['api_key']['#default_value'] != $form_state['values']['campaignmonitor_account']['api_key'] ||
//      $form['campaignmonitor_account']['client_id']['#default_value'] != $form_state['values']['campaignmonitor_account']['client_id']) {
//      _block_rehash();
//    }
  }
    // TODO: Implement submitForm() method.

}