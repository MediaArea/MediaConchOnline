<?php

namespace AppBundle\Tests\Controller;

use AppBundle\Tests\Controller\AbstractAuthControllerTest;

class XslPolicyControllerTest extends AbstractAuthControllerTest
{
    public function testPolicy()
    {
        $crawler = $this->client->request('GET', '/xslPolicy/');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertCount(1, $crawler->filter('h1:contains("Policies")'));
    }
}
