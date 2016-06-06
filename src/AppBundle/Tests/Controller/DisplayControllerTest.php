<?php

namespace AppBundle\Tests\Controller;

use AppBundle\Tests\Controller\AbstractAuthControllerTest;

class DisplayControllerTest extends AbstractAuthControllerTest
{
    public function testDisplay()
    {
        $crawler = $this->client->request('GET', '/display/');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertCount(1, $crawler->filter('h1:contains("Display")'));
    }
}
