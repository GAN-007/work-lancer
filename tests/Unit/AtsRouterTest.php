<?php
namespace Tests\Unit;
use App\Galika\Services\AtsRouter;
use App\Models\GalikaOpportunity;
use PHPUnit\Framework\TestCase;
class AtsRouterTest extends TestCase {
 /** @dataProvider routes */
 public function test_detects_first_class_ats(string $url,string $expected):void{$o=new GalikaOpportunity(['url'=>$url]);$r=new AtsRouter();$this->assertSame($expected,$r->detect($o));$this->assertTrue($r->browserFirstClass($expected));}
 public function routes():array{return [
  ['https://boards.greenhouse.io/acme/jobs/1','GREENHOUSE'],['https://jobs.lever.co/acme/1','LEVER'],['https://jobs.ashbyhq.com/acme/1','ASHBY'],['https://acme.wd1.myworkdayjobs.com/job/1','WORKDAY'],['https://www.linkedin.com/jobs/view/1','LINKEDIN'],['https://jobs.ceipal.com/acme','CEIPAL'],['https://careers.example.com/jobs/1','EMPLOYER_FORM'],
 ];}
}