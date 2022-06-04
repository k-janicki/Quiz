<?php

namespace AppBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Stopwatch\Stopwatch;

class QuizControllerTest extends WebTestCase
{
//    private $stopwatch;
//
//    public function __construct(Stopwatch $stopwatch)
//    {
//        parent::__construct();
//        $this->stopwatch = $stopwatch;
//    }

    public function testOptimisticDuelResolving(): void
    {

//        $this->stopwatch->start('optimistic');
        $client = static::createClient();
        $client->enableProfiler();
        $client->request('POST','/duel_optimistic',['userId'=>12,'quizId'=>1,'tryNumber'=>0]);
        $this->assertResponseIsSuccessful($client->getResponse());
        $profile = $client->getProfile();
        print('token: '.$profile->getToken().' czas: '.$profile->getCollector('time')->getDuration());
        $response = $client->getResponse();
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals(0, $responseData['returned']);

        $client2 = static::createClient();
        $client2->enableProfiler();
        $client2->request('POST','/duel_pessimistic',['userId'=>13,'quizId'=>1,'tryNumber'=>0]);
        $response = $client2->getResponse();
        $this->assertResponseIsSuccessful($client2->getResponse());
        $profile = $client2->getProfile();
        print('token: '.$profile->getToken().' czas: '.$profile->getCollector('time')->getDuration());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals(0, $responseData['returned']);

        $client3 = static::createClient();
        $client3->enableProfiler();
        $client3->request('POST','/duel_pessimistic',['userId'=>14,'quizId'=>1,'tryNumber'=>0]);
        $this->assertResponseIsSuccessful($client3->getResponse());
        $profile = $client3->getProfile();
        print('token: '.$profile->getToken().' czas: '.$profile->getCollector('time')->getDuration());
        $response = $client3->getResponse();
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals(0, $responseData['returned']);
//        $this->stopwatch->stop('optimistic');
//        print($this->stopwatch->getEvent());
    }
    public function testPessimisticDuelResolving(): void
    {
        $client = static::createClient();
        $client2 = static::createClient();
        $client3 = static::createClient();
        $client->request('POST','/duel_pessimistic',['userId'=>12,'quizId'=>1,'tryNumber'=>0]);
        $client2->request('POST','/duel_pessimistic',['userId'=>13,'quizId'=>1,'tryNumber'=>0]);
        $client3->request('POST','/duel_pessimistic',['userId'=>14,'quizId'=>1,'tryNumber'=>0]);
        $this->assertResponseIsSuccessful($client->getResponse());
        $this->assertResponseIsSuccessful($client2->getResponse());
        $this->assertResponseIsSuccessful($client3->getResponse());
        $response = $client->getResponse();
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals(0, $responseData['returned']);
        $response = $client2->getResponse();
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals(0, $responseData['returned']);
        $response = $client3->getResponse();
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals(0, $responseData['returned']);
    }
    public function testQueueDuelResolving(): void
    {
        $client = static::createClient();
        $client->request('POST','/duel_queue',['userId'=>12,'quizId'=>1]);
        $this->assertResponseIsSuccessful();
        $response = $client->getResponse();
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals(0, $responseData['returned']);
    }
}
