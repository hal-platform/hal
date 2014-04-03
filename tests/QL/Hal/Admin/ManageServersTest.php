<?php
namespace QL\Hal\Admin;

use Slim\Http\Response;
use Twig_Template;
use QL\Hal\Services\EnvironmentService;

class ManageServersTest extends \PHPUnit_Framework_TestCase
{
    private $response;
    private $tpl;
    private $serverService;
    private $envService;

    public function testRenderIsCalledWithServersAndEnvs()
    {
        $this->markTestSkipped('This test is broken');
       /* $twigTemplateMock = $this->getMockBuilder('Twig_Template')
                    ->disableOriginalConstructor()
                    ->getMockForAbstractClass();
        $twigTemplateMock->expects($this->once())
                    ->method('render')
                    ->with($this->equalTo(['servers' => 'serversList', 'envs' => 'envList'])); */

        $twigTemplateMock = $this->getMockForAbstractClass(
                    'Twig_Template',
                    array(),
                    '',
                    false,
                    true,
                    true,
                    array('render')
        );
        $twigTemplateMock->expects($this->once())
                    ->method('render')
                    ->with($this->equalTo(['servers' =>'serversList', 'envs' =>'envList']));

        $serverServiceMock = $this->getMockBuilder('QL\\Hal\Services\\ServerService')
                    ->disableOriginalConstructor()
                    ->getMock();
        $serverServiceMock->expects($this->once())
                    ->method('listAll')
                    ->will($this->returnValue('serversList'));

        $envServiceMock = $this->getMockBuilder('QL\\Hal\\Services\\EnvironmentService')
                    ->disableOriginalConstructor()
                    ->getMock();
        $envServiceMock->expects($this->once())
                    ->method('listAll')
                    ->will($this->returnValue('envList'));

        $responseMock = $this->getMockBuilder('Slim\\Http\\Response')
                    ->disableOriginalConstructor()
                    ->getMock();
        $responseMock->expects($this->once())
                    ->method('body')
                    ->with($twigTemplateMock);


        $servers = new ManageServers($responseMock, $twigTemplateMock, $serverServiceMock, $envServiceMock);
        $servers();
    }
}
