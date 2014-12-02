<?php
/**
 * Created by PhpStorm.
 * User: btm
 * Date: 11/19/14
 * Time: 2:12 PM
 */

namespace Drupal\campaignmonitor\Plugin\Block;

use Drupal\Core\block\BlockBase;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
/**
 * Provides a test block.
 *
 * @Block(
 *  id = "list_subscription_block",
 *  admin_label = @Translation("List Subscription")
 * )
 */
class CampaignMonitorListSubscriptionBlock extends BlockBase {

  /**
   * Builds and returns the renderable array for this block plugin.
   *
   * @return array
   *   A renderable array representing the content of the block.
   *
   * @see \Drupal\block\BlockViewBuilder
   */
  public function build() {
    return array(
      '#children' => 'this is a block'
    );
  }
}