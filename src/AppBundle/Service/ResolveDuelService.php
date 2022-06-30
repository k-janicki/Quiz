<?php

namespace AppBundle\Service;

use AppBundle\Entity\Duel;
use AppBundle\Entity\DuelQuestion;
use AppBundle\Entity\Question;
use AppBundle\Entity\Quiz;
use AppBundle\Entity\Repository\DuelRepository;
use AppBundle\Entity\Repository\QuestionRepository;
use AppBundle\Entity\Repository\QuizRepository;
use AppBundle\Entity\Repository\UserAnswerRepository;
use AppBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\OptimisticLockException;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;
use PhpAmqpLib\Message\AMQPMessage;

class ResolveDuelService
{
    const EXPECTED_VERSION = 1;
    const OPTIMISTIC_TRY_INDEX_LIMIT = 10;
    const PESSIMISTIC_TRY_INDEX_LIMIT = 10;
    const QUEUE_TRY_INDEX_LIMIT = 10;
    /**
     * @var DuelRepository
     */
    private DuelRepository $duelRepository;
    /**
     * @var QuizRepository
     */
    private QuizRepository $quizRepository;
    /**
     * @var QuestionRepository
     */
    private QuestionRepository $questionRepository;
    /**
     * @var UserAnswerRepository
     */
    private UserAnswerRepository $userAnswerRepository;
    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $em;
    private \Doctrine\Persistence\ManagerRegistry $managerRegistry;

    /**
     * QuizController constructor.
     * @param DuelRepository $duelRepository
     * @param QuizRepository $quizRepository
     * @param QuestionRepository $questionRepository
     * @param UserAnswerRepository $userAnswerRepository
     */
    public function __construct(\Doctrine\Persistence\ManagerRegistry $managerRegistry,
                                DuelRepository $duelRepository,
                                QuizRepository $quizRepository, QuestionRepository $questionRepository, UserAnswerRepository $userAnswerRepository)
    {
        $this->duelRepository = $duelRepository;
        $this->quizRepository = $quizRepository;
        $this->questionRepository = $questionRepository;
        $this->userAnswerRepository = $userAnswerRepository;
        $this->managerRegistry = $managerRegistry;
        $this->em = $managerRegistry->getManager();
    }

    /**
     * @param int $quizId
     * @param int $userId
     * @param $tryIndex
     * @return int|void|array
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Doctrine\ORM\ORMException
     */
    public function resolveDuelOptimistic(int $quizId, int $userId, $tryIndex = 0)
    {
        $em = $this->em;
        $returned = $this->checkUserDuels($em, $quizId, $userId);
        if (true === $returned['hasDuels']) {
            return 0;
        }
        $quiz = $returned['quiz'];
        $user = $returned['user'];
        $duel = $this->duelRepository->findOneBy(['user2' => null, 'version'=>1],['id'=>'desc']);
        if (null === $duel || self::OPTIMISTIC_TRY_INDEX_LIMIT == $tryIndex) {
            return $this->generateDuel($em, $quiz, $user, $tryIndex);
        } else {
            $em->getConnection()->beginTransaction();
            try {
                $duelId = $duel->getId();
                $toUpdate = $this->duelRepository->find($duelId, LockMode::OPTIMISTIC, self::EXPECTED_VERSION );
                $toUpdate->setUser2($user);
                $em->persist($toUpdate);
                $em->flush();
                $em->getConnection()->commit();
            } catch (\Exception $e) {
                $em->getConnection()->rollback();
                $em = $this->managerRegistry->resetManager();
                $em->clear();
                if (is_a($e, OptimisticLockException::class)) {
                    return [$e->getMessage(),'duelId' => $duelId];
                }
                return [1,$e->getMessage()];
            }
        }

        return 0;
    }

    /**
     * @param int $quizId
     * @param int $userId
     * @param $tryIndex
     * @return int
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Doctrine\ORM\ORMException
     */
    public function resolveDuelPessimistic(int $quizId, int $userId, $tryIndex = 0)
    {

        $em = $this->em;
        $returned = $this->checkUserDuels($em, $quizId, $userId);
        if (true === $returned['hasDuels']) {
            return 0;
        }
        $quiz = $returned['quiz'];
        $user = $returned['user'];
        //sprawdzenie czy istnieje jakis pojedynek z wolnym miejscem bez modyfikacji
        $em->getConnection()->beginTransaction();
        try {
            if (self::PESSIMISTIC_TRY_INDEX_LIMIT !== $tryIndex) {
                $duel = $this->duelRepository->customQueryPessimistic();
            } else {
                $duel = null;
            }
            if (null === $duel) {
                $duelNew = new Duel($quiz, $user, null);
                $questions = $em->getRepository(Question::class)->generateQuestionsForQuiz(5, $quiz);
                $this->createDuel($em, $duelNew, $questions);
            } else {
                $duelId = $duel->getId();
                if (null === $duel || $duel->getUser2() !== null) {
                    return -2;
                }
                $duel->setUser2($user);
                $em->persist($duel);
                $em->flush();
                $em->getConnection()->commit();
            }
        } catch (\Exception $e) {
            $em->getConnection()->rollback();
            $em = $this->managerRegistry->resetManager();
            $em->clear();
            return -2;
        }
        return 0;
    }

    private function putDuelInQueue($quiz, $user, $connection, $channel)
    {
        $connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
        $channel = $connection->channel();
        $channel->queue_declare('duelQueue', false, false, false, false);

        $this->em->beginTransaction();
        try {
            $duelNew = new Duel($quiz, $user, null);
            $questions = $this->em->getRepository(Question::class)->generateQuestionsForQuiz(5, $quiz);
            $this->createDuel($this->em, $duelNew, $questions);
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->managerRegistry->resetManager();
            $this->em->clear();
            return -1;
        }
        $msg = new AMQPMessage($duelNew->getId());
        $channel->basic_publish($msg, '', 'duelQueue');

        $channel->close();
        $connection->close();
        return 0;
    }

    /**
     * @param $quizId
     * @param $userId
     * @return int
     * @throws AMQPProtocolChannelException
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Doctrine\ORM\ORMException
     */
    public function getDuelFromQueue($quizId, $userId, $tryNumber)
    {
        $em = $this->em;
        $returned = $this->checkUserDuels($em, $quizId, $userId);
        if (true === $returned['hasDuels']) {
            return 0;
        }
        $quiz = $returned['quiz'];
        $user = $returned['user'];

        $connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
        $channel = $connection->channel();
        try {
            $result = $channel->basic_get('duelQueue', true, null);
        } catch (AMQPProtocolChannelException $exception) {
            if (404 === $exception->getCode()) {
                if ($tryNumber < self::QUEUE_TRY_INDEX_LIMIT) {
                    return -1;
                } else {
                    return $this->putDuelInQueue($quiz, $user, $connection, $channel);
                }
            } else {
                throw $exception;
            }

        }
        if (null === $result) {
            return $this->putDuelInQueue($quiz, $user, $connection, $channel);
        }
        $resultBody = $result->getBody();
        $duel = $this->duelRepository->findOneBy(['id'=>$resultBody]);

        $this->em->getConnection()->beginTransaction();
        try {
            if (null === $duel || $duel->getUser2() !== null) {
                return -1;
            }
            $duel->setUser2($user);
            $this->em->persist($duel);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollback();
            $this->managerRegistry->resetManager();
            $this->em->clear();
            $msg = new AMQPMessage($resultBody);
            $channel->basic_publish($msg, '', 'duelQueue');
            return -2;
        }
        $channel->close();
        $connection->close();

        return 0;
    }

    /**
     * @param $em
     * @param Duel $duelNew
     * @param $questions
     * @return void
     */
    private function createDuel($em, Duel $duelNew, $questions): void
    {
        $em->persist($duelNew);
        $duelQuestions = new ArrayCollection();
        foreach ($questions as $questionIndex => $question) {
            $duelQuestion = new DuelQuestion($duelNew, $question, $questionIndex);
            $em->persist($duelQuestion);
            $duelQuestions->add($duelQuestion);
        }
        $duelNew->setQuestions($duelQuestions);
        $em->flush();
        $em->getConnection()->commit();
    }

    private function generateDuel($em, $quiz, $user, $tryIndex)
    {
        $duelId = count($this->duelRepository->findAll()) + 1; //jak nie znajdzie zadnego duela to tworzy id dla konkretnej encji aby przy insercie bic sie o miejsce w bazie
        $questions = $em->getRepository(Question::class)->generateQuestionsForQuiz(5, $quiz);

        $em->getConnection()->beginTransaction();
        try {
            //utworzenie pojedynku - 3 proby zanim wrzuci domyslny autoincrement
            $duelNew = new Duel($quiz, $user, null);
            if ($tryIndex <= 1) {
                $duelNew->setId($duelId);
                $metadata = $em->getClassMetadata(get_class($duelNew));
                $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);
            }
            $em->persist($duelNew);
            $duelQuestions = new ArrayCollection();
            foreach ($questions as $questionIndex => $question) {
                $duelQuestion = new DuelQuestion($duelNew, $question, $questionIndex);
                $em->persist($duelQuestion);
                $duelQuestions->add($duelQuestion);
            }
            $duelNew->setQuestions($duelQuestions);
            $em->flush();
            $em->getConnection()->commit();
        } catch (\Exception $e) {
            $em->getConnection()->rollback();
            $em = $this->managerRegistry->resetManager();
            $em->clear();
            if (is_a($e, UniqueConstraintViolationException::class)) {
                $sqlState = $e->getSQLState();
                //SQLSTATE[23000] - fail na insercie jesli klucz jest zduplikowany
                if ($sqlState == '23000') {
                    return -1; //ponowne wywołanie, jesli skonczy sie z -1 - cos poszlo nie tak
                } else {
                    return 1;
                }
            }
        }
        return 0;
    }

    public function checkUserDuels($em, $quizId, $userId)
    {
        $quiz = $em->getReference(Quiz::class, $quizId);
        $user = $em->getReference(User::class, $userId);
        $criteria = Duel::getDuelCriteriaForUser($quiz, $user);
        $duel = $this->duelRepository->matching($criteria)->first();

        if (false !== $duel) {
            //sprawdzenie czy bierze udział w pojedynku
            return ['hasDuels' => true];
        } else {
            return ['hasDuels' => false, 'user' => $user, 'quiz' => $quiz];
        }
    }
}