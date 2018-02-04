<?php

namespace AppBundle\Tests\Controller;

class CheckerControllerTest extends AbstractAuthControllerTest
{
    public function testChecker()
    {
        $crawler = $this->client->request('GET', '/checker');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertCount(1, $crawler->filter('h1:contains("Check files")'));
    }
}
