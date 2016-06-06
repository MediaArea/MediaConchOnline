<?php

namespace AppBundle\Tests\Controller;

use AppBundle\Tests\Controller\AbstractAuthControllerTest;

class UserControllerTest extends AbstractAuthControllerTest
{
    public function testSettings()
    {
        $crawler = $this->client->request('GET', '/settings');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertCount(1, $crawler->filter('h1:contains("Settings")'));
    }

    public function testProfileView()
    {
        $crawler = $this->client->request('GET', '/profile/');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertCount(1, $crawler->filter('p:contains("Username: test")'));
    }

    public function testProfileEdit()
    {
        $crawler = $this->client->request('GET', '/profile/edit');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertCount(1, $crawler->filter('label:contains("Username:")'));
    }

    public function testProfilePassword()
    {
        $crawler = $this->client->request('GET', '/profile/change-password');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertCount(1, $crawler->filter('label:contains("Current password:")'));
    }
}
