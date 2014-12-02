<?php
/**
 * Created by PhpStorm.
 * User: btm
 * Date: 11/19/14
 * Time: 2:02 PM
 */

namespace Drupal\campaignmonitor\Controller;


class CampaignMonitorStatsController {
  public function viewStats() {
    return array(
      '#markup' => 'stats!'
    );
  }
} 