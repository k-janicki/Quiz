<?php

namespace AppBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class QuizControllerTest extends WebTestCase
{
    public function testOptimisticDuelResolving(): void
    {
        $client = static::createClient();
        $client->request('POST','/duel_optimistic',['userId'=>12,'quizId'=>1,'tryNumber'=>0]);
        $this->assertResponseIsSuccessful();
        $response = $client->getResponse();
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals(0, $responseData['returned']);
    }
    public function testPessimisticDuelResolving(): void
    {

    }
    public function testQueueDuelResolving(): void
    {

    }
}
