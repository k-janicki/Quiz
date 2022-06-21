<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Answer;
use AppBundle\Entity\Duel;
use AppBundle\Entity\Question;
use AppBundle\Entity\Quiz;
use AppBundle\Entity\Repository\QuizRepository;
use AppBundle\Entity\Repository\UserAnswerRepository;
use AppBundle\Entity\UserAnswer;
use AppBundle\Form\QuizType;
use AppBundle\Service\ResolveDuelService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

//use Symfony\Component\HttpFoundation\JsonResponse;

/**
 *
 */
class QuizController extends AbstractController
{
    /**
     * @var QuizRepository
     */
    private QuizRepository $quizRepository;

    /**
     * @var UserAnswerRepository
     */
    private UserAnswerRepository $userAnswerRepository;
    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $em;
    private ResolveDuelService $duelService;

    /**
     * QuizController constructor.
     * @param EntityManagerInterface $em
     * @param QuizRepository $quizRepository
     * @param UserAnswerRepository $userAnswerRepository
     */
    public function __construct(EntityManagerInterface $em, QuizRepository $quizRepository, UserAnswerRepository $userAnswerRepository, ResolveDuelService $duelService)
    {
        $this->quizRepository = $quizRepository;
        $this->userAnswerRepository = $userAnswerRepository;
        $this->em = $em;
        $this->duelService = $duelService;
    }

    public function frontAction(Request $request)
    {
        $quiz = $this->quizRepository->find(1);
        $user = $this->getUser();
        $tryIndex = 0;

//        do {
//            $returned = $this->duelService->resolveDuelOptimistic($quiz->getId(), $user->getId(), $tryIndex);
////            $returned = $this->duelService->resolveDuelPessimistic2($quiz->getId(), $user->getId(), $tryIndex);
////            $returned = $this->duelService->getDuelFromQueue($quiz->getId(), $user->getId());
//            $tryIndex += 1;
//            dump('returned: '.$returned);
//        } while ($returned !== 0 && $tryIndex <= 3);


        $quizzes = $this->quizRepository->getActiveQuizzes();
        $ranking = $this->getDoctrine()->getRepository(UserAnswer::class)->getPointsForTry();
        $link = $request->get('link') ? $request->get('link') : 'home';

        return $this->render('layout.html.twig', [
            'quizzes' => $quizzes,
            'ranking' => $ranking,
            'link'    => $link,
        ]);
    }

    public function quizAction(Request $request, $id)
    {
        $current = 0;
        if (null !== $request->get('current')) {
            $current = $request->get('current') + 1;
        } else {
            $user = $this->getUser();
            $duel = $this->resolveDuel($id, $user);
            $quiz = $this->quizRepository->findOneBy(['id' => $id]);

            $questions = $duel->getQuestions()->toArray();
        }

        $options = $request->get('option');
        if (is_array($options))
        {
            $options = implode(',', $options);
        }
        if (null !== $options) {
            $quiz = $this->quizRepository->find($id);
            $user = $this->getUser();
            $duelCriteria = Duel::getDuelCriteriaForUser($quiz, $user);
            $questions = $this->duelRepository->matching($duelCriteria)->first()->getQuestions()->toArray();
            $question = $questions[$current-1]->getQuestion();

            $correct = [];
            $index = 0;
            foreach ($question->getAnswers() as $answer) {
                if (1 === $answer->getIsCorrect()) {
                    $index ++;
                    $correct[$index] = $answer->getId();
                }
            }
            $tryNumber = $this->userAnswerRepository->getQuestionTryNumber($user,$quiz,$question);
            if (empty($tryNumber)) {
                $tryNumber = 1;
            } else {
                $tryNumber['tryNumber'] = $tryNumber['tryNumber']++;
            }

            $userAnswer = new UserAnswer();
            $userAnswer->setUser($user)
                ->setQuiz($quiz)
                ->setQuestion($question)
                ->setSelection($options)
                ->setTryNumber($tryNumber);
            if(count(array_intersect($correct, explode(',', $options))) == count($correct) && count($correct) == count(explode(',', $options))) {
                $userAnswer->setPoints($question->getPoints());
            }
            $em = $this->getDoctrine()->getManager();
            $em->persist($userAnswer);
            $em->flush();
        }
        if ($current >= count($questions)) {
            return $this->redirectToRoute('front');
        }

        if ('sortable' === $questions[$current]->getQuestion()->getType()) {
            $template = '@App/quiz.html.twig';
        } else {
            $template = '@App/quiz2.html.twig';
        }
        return $this->render($template, [
            'current' => $current,
            'questions' => $questions,
        ]);
    }

    public function testAction()
    {
        return $this->render('@App/test.html.twig');
    }

    public function newAction(Request $request)
    {
        $quiz = new Quiz();
        $form = $this->createForm(QuizType::class,$quiz);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $quiz->getQuestions()->map(function (Question $question) use ($quiz){
                $question->setQuiz($quiz);
                $question->getAnswers()->map(function (Answer $answer) use ($question) {
                    $answer->setQuiz($question->getQuiz());
                    $answer->setQuestion($question);
                });
            });

            $em = $this->getDoctrine()->getManager();
            $em->persist($quiz);
            $em->flush();

            return $this->redirectToRoute('front');
        }
        return $this->render(
            '@App/create_quiz.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    public function assignDuelOptimisticAction(Request $request)
    {
        $quizId = $request->get('quizId');
        $userId = $request->get('userId');
        $tryNumber = $request->get('tryNumber') ?? 0;
        $returnedData = [];
        if ($quizId && $userId) {
            do {
                $returned = $this->duelService->resolveDuelOptimistic($quizId, $userId, $tryNumber);
                $tryNumber += 1;
                $returnedData[] = ['returned'=>$returned, 'tryNumber' => $tryNumber];
            } while ($returned !== 0 && $tryNumber <= $this->duelService::OPTIMISTIC_TRY_INDEX_LIMIT);
            $response = new Response();
            $response->setContent(json_encode($returnedData));
            if ($returned === 0) {
                $response->setStatusCode(Response::HTTP_OK);
            } else {
                $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            return $response;
        } else {
            throw $this->createNotFoundException('Not found');
        }
    }

    public function assignDuelPessimistic2Action(Request $request)
    {
        $quizId = $request->get('quizId');
        $userId = $request->get('userId');
        $tryNumber = $request->get('tryNumber') ?? 0;
        if ($quizId && $userId) {
            do {
                $returned = $this->duelService->resolveDuelPessimistic2($quizId, $userId, $tryNumber);
                $tryNumber += 1;
                if ($returned === 0 && $tryNumber >= ($this->duelService::PESSIMISTIC_TRY_INDEX_LIMIT/2)) {
                    break;
                }
            } while ($returned !== 0 && $tryNumber <= $this->duelService::PESSIMISTIC_TRY_INDEX_LIMIT);
            $response = new Response();
            $response->setContent(json_encode([
                'returned' => $returned,
                'tryNumber' => $tryNumber
            ]));
            if ($returned === 0) {
                $response->setStatusCode(Response::HTTP_OK);
            } else {
                $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            return $response;
        } else {
            throw $this->createNotFoundException('Not found');
        }
    }

    public function assignDuelQueueAction(Request $request)
    {
        $quizId = $request->get('quizId');
        $userId = $request->get('userId');
        $tryNumber = 0;
        if ($quizId && $userId) {
            do {
                $returned = $this->duelService->getDuelFromQueue($quizId, $userId, $tryNumber);
                $tryNumber += 1;
            } while ($returned !== 0 && $tryNumber <= $this->duelService::QUEUE_TRY_INDEX_LIMIT);
            $response = new Response();
            $response->setContent(json_encode([
                'returned' => $returned,
            ]));
            if ($returned === 0) {
                $response->setStatusCode(Response::HTTP_OK);
            } else {
                $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            return $response;
        } else {
            throw $this->createNotFoundException('Not found');
        }
    }

    public function resetDuelsAction()
    {
        $repo = $this->getDoctrine()->getManager()->getRepository(Duel::class);
        $repo->resetDuels();
        return new Response('OK', Response::HTTP_OK);
    }

    public function obtainResolvedDuelsDataAction()
    {
        $result = $this->getDoctrine()->getManager()->getRepository(Duel::class)->countPairedDuels();
        $response = new Response();
        $response->setContent(json_encode([
            $result,
        ]));
        return $response;
    }
}
