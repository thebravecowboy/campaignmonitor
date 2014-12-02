<?php
/**
 * Created by PhpStorm.
 * User: btm
 * Date: 11/19/14
 * Time: 1:53 PM
 */

namespace Drupal\campaignmonitor\Controller;


use Drupal\Core\Controller\ControllerBase;
use Drupal\campaignmonitor\WebService;
use Drupal\Core\Url;

class CampaignMonitorListController extends ControllerBase{

  public function viewLists() {

    $account = $this->config('campaignmonitor.settings')->get('account');

    if (!$account) {
      drupal_set_message(t('You have not entered your account information yet, hence lists from Campaign Monitor can not be downloaded.'), 'error');
      return '';
    }

    // Download WebService\CampaignMonitorRestClient information from Campaign Monitor.
//    $cm = \Drupal::service('campaignmonitor.rest_client');
    $cm = WebService\CampaignMonitorRestClient::getConnector($account['api_key'],$account['client_id']);
    $lists = $cm->getLists();
    $error = $cm->getLatestError();
    if ($error['code'] != 1) {
      drupal_set_message($error['message'], 'error');
    }

    $header = array(
      array('data' => t('Title'), 'field' => 'title', 'sort' => 'asc'),
      array('data' => t('List ID'), 'field' => 'id'),
      array('data' => t('Subscribed / Unsubscribed'), 'field' => 'status'),
      array('data' => t('Operations'), 'field' => 'Operations'),
    );

    $rows = array();
    if ($lists) {
      foreach ($lists as $id => $list) {
        // Define supported operations.
        $operations = array(
          'Edit' => \Drupal::l(t('Edit'), new Url('campaignmonitor.admin_lists_edit',  ['list' => $id])),
          'Delete' => \Drupal::l(t('Delete'), new Url('campaignmonitor.admin_lists_delete', ['list' => $id]))
        );



        // Load local list options.
        $list_options = \Drupal::config('campaignmonitor')->get('campaignmonitor_list_' . $id);
        $class = 'campaignmonitor-list-enabled';
        if (isset($list_options['status']['enabled']) && !$list_options['status']['enabled']) {
          // Add enable operation.
          $class = 'campaignmonitor-list-disabled';
          $operations['enable'] = \Drupal::l(t('Enable'), new Url('campaignmonitor.admin_lists_enable', ['list'=>$id]));
        }
        else {
          // Add disable operation.
          $class = 'campaignmonitor-list-enabled';
          $operations['disable'] = \Drupal::l(t('Disable'), new Url('campaignmonitor.admin_lists_disable', ['list'=>$id]));


        }



        // Allow other modules to add more operations.
        \Drupal::moduleHandler()->alter('campaignmonitor_operations', $operations);
        $stats = $cm->getListStats($id);
        $rows[] = array(
          'data' => array(
            'name' => $list['name'],
            'id' => $id,
            'status' => $stats['TotalActiveSubscribers'] . ' / ' . $stats['TotalUnsubscribes'],
            'Operations' => implode(' ', $operations)

          ),
          'class' => array('class' => $class),
        );
      }
    }

    $output = array(
      '#type' => 'table',

      '#header' => $header,
      '#empty' => t('Lists not found or not yet created...'),
      '#rows' => $rows,
    );

//    kpr($output);die;
//


    // Add a pager to the table, for sites that have a great many lists.
    //$html .= theme('pager', array('tags' => array()));



    return $output;

  }

  public function enableList() {

  }

  public function disableList() {

  }
  public function deleteList() {

  }


  public function isListEnabled() {

  }

} 