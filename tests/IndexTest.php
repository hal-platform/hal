<?php

require '../vendor/autoload.php';
Slim::autoload('Slim_Environment');

class IndexTest extends PHPUnit_Framework_TestCase 
{
    public function testLoginGet() {
        $this->get('/login');
        $this->assertEquals('303', $this->response->status());
        $this->assertEquals('http://bridget.gitbertslim.local/login', $this->response['Location']);
    }
}
