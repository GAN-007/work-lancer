<?php
namespace App\Galika\Services;
class DiscoveryService{
 public function __construct(private SourceBrokerService $broker){}
 public function discover():int{return $this->broker->discover();}
}
