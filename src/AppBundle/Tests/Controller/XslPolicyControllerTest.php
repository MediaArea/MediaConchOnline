<?php

namespace AppBundle\Tests\Controller;

use AppBundle\Tests\Controller\AbstractAuthControllerTest;

class XslPolicyControllerTest extends AbstractAuthControllerTest
{
    public function testOldPolicy()
    {
        $crawler = $this->client->request('GET', '/xslPolicyTree/');

        $this->assertEquals(301, $this->client->getResponse()->getStatusCode());
    }

    public function testPolicy()
    {
        $crawler = $this->client->request('GET', '/policyEditor');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertCount(1, $crawler->filter('h1:contains("Policy editor")'));
    }

    public function testPublicPolicies()
    {
        $crawler = $this->client->request('GET', '/publicPolicies');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertCount(1, $crawler->filter('h1:contains("Public policies")'));
    }
}
