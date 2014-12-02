<?php
/**
 * Created by PhpStorm.
 * User: btm
 * Date: 11/19/14
 * Time: 2:42 PM
 */

namespace Drupal\campaignmonitor\Controller;


use Drupal\Core\Controller\ControllerBase;

class HelloWorldController extends ControllerBase{
  public function viewHello() {
    return array(
      '#markup' => 'hello world'
    );
  }

} 