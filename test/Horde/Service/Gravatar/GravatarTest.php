<?php
/**
 * Copyright 2011-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2011-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Service_Gravatar
 */
namespace Horde\Service\Gravatar;
use PHPUnit\Framework\TestCase;
use \Horde_Service_Gravatar;
use \Horde_Support_StringStream;
use \Horde_Http_Response_Mock;
use \Horde_Http_Request_Mock;
use \Horde_Http_Client;

/**
 * @author    Gunnar Wrobel <wrobel@pardus.de>
 * @category  Horde
 * @copyright 2011-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Service_Gravatar
 */
class GravatarTest extends TestCase
{
    public function testReturn()
    {
        $g = new Horde_Service_Gravatar();
        $this->assertIsString($g->getId('test'));
    }

    public function testAddress()
    {
        $g = new Horde_Service_Gravatar();
        $this->assertEquals(
            '0c17bf66e649070167701d2d3cd71711',
            $g->getId('test@example.org')
        );
    }

    /**
     * @dataProvider provideAddresses
     */
    public function testAddresses($mail, $id)
    {
        $g = new Horde_Service_Gravatar();
        $this->assertEquals($id, $g->getId($mail));
    }

    public function provideAddresses()
    {
        return array(
            array('test@example.org', '0c17bf66e649070167701d2d3cd71711'),
            array('x@example.org', 'ae46d8cbbb834a85db7287f8342d0c42'),
            array('test@example.com', '55502f40dc8b7c769880b10874abc9d0'),
        );
    }

    public function testIgnoreCase()
    {
        $g = new Horde_Service_Gravatar();
        $this->assertEquals(
            '0c17bf66e649070167701d2d3cd71711',
            $g->getId('Test@EXAMPLE.orG')
        );
    }

    public function testTrimming()
    {
        $g = new Horde_Service_Gravatar();
        $this->assertEquals(
            '0c17bf66e649070167701d2d3cd71711',
            $g->getId(' Test@Example.orG ')
        );
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidMail()
    {
        $this->expectException('InvalidArgumentException');
        $g = new Horde_Service_Gravatar();
        $g->getId(0.0);
    }

    public function testAvatarUrl()
    {
        $g = new Horde_Service_Gravatar();
        $this->assertEquals(
            'http://www.gravatar.com/avatar/0c17bf66e649070167701d2d3cd71711',
            $g->getAvatarUrl(' Test@Example.orG ')
        );
    }

    public function testAvatarUrlWithSize()
    {
        $g = new Horde_Service_Gravatar();
        $this->assertEquals(
            'http://www.gravatar.com/avatar/0c17bf66e649070167701d2d3cd71711?s=50',
            $g->getAvatarUrl('test@example.org', 50));
    }

    public function testProfileUrl()
    {
        $g = new Horde_Service_Gravatar();
        $this->assertEquals(
            'http://www.gravatar.com/0c17bf66e649070167701d2d3cd71711',
            $g->getProfileUrl(' Test@Example.orG ')
        );
    }

    public function testFlexibleBase()
    {
        $g = new Horde_Service_Gravatar(Horde_Service_Gravatar::SECURE);
        $this->assertEquals(
            'https://secure.gravatar.com/0c17bf66e649070167701d2d3cd71711',
            $g->getProfileUrl(' Test@Example.orG ')
        );
    }

    public function testFetchProfile()
    {
        $g = $this->_getMockedGravatar('RESPONSE');
        $this->assertEquals(
            'RESPONSE',
            $g->fetchProfile('test@example.org')
        );
    }

    public function testGetProfile()
    {
        $g = $this->_getMockedGravatar('{"test":"example"}');
        $this->assertEquals(
            array('test' => 'example'),
            $g->getProfile('test@example.org')
        );
    }

    private function _getMockedGravatar($response_string)
    {
        $response = $this->getMockBuilder('Horde_Http_Response')->setMethods(array('getBody'))->getMock();
        $response->expects($this->once())
            ->method('getBody')
            ->will($this->returnValue($response_string));

        $mock = $this->getMockBuilder('Horde_Http_Client')->setMethods(array('get'))->getMock();
        $mock->expects($this->once())
            ->method('get')
            ->will($this->returnValue($response));

        return new Horde_Service_Gravatar(
            Horde_Service_Gravatar::STANDARD,
            $mock
        );
    }

    public function testFetchImage()
    {
        $g = $this->_getStubbedGravatar('RESPONSE');
        $this->assertEquals(
            'RESPONSE',
            stream_get_contents($g->fetchAvatar('test@example.org'))
        );
    }

    private function _getStubbedGravatar($response_string)
    {
        $body = new Horde_Support_StringStream($response_string);
        $response = new Horde_Http_Response_Mock('', $body->fopen());
        $request = new Horde_Http_Request_Mock();
        $request->setResponse($response);
        return new Horde_Service_Gravatar(
            Horde_Service_Gravatar::STANDARD,
            new Horde_Http_Client(array('request' => $request))
        );
    }
}
