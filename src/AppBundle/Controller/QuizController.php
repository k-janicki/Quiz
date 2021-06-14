<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Answer;
use AppBundle\Entity\Question;
use AppBundle\Entity\Quiz;
use AppBundle\Entity\UserAnswer;
use AppBundle\Form\AnswerType;
use AppBundle\Form\QuizType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Predis;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Polyfill\Apcu\Apcu;

//use Symfony\Component\HttpFoundation\JsonResponse;

class QuizController extends Controller
{
    private $client;

    public function __construct()
    {
        $this->client = new Predis\Client();
    }

    public function frontAction(Request $request)
    {
//        apcu_clear_cache();
        $link = $request->get('link') ? $request->get('link') : 'home';

        if ($this->client->get('view')) {
//            $view = $this->client->get('view');
//            return new Response($view);
        }
        $view = $this->renderView('layout.html.twig', [
            'link'    => $link,
        ]);
        $this->client->set('view', $view);

        return new Response($view);
    }

    public function quizListAction()
    {
        $quizzes = $this->getDoctrine()->getRepository(Quiz::class)->getActiveQuizzes();

        if ($this->client->get('quizList')) {
//            $view = $this->client->get('quizList');
//            return new Response($view);
        }
        $view = $this->renderView('@App/quizzes.html.twig', [
            'quizzes'    => $quizzes,
        ]);
        $this->client->set('view', $view);

        return new Response($view);
    }

    public function rankingListAction()
    {
        $ranking = $this->getDoctrine()->getRepository(UserAnswer::class)->getPointsForTry();

        if ($this->client->get('ranking')) {
//            $view = $this->client->get('ranking');
//            return new Response($view);
        }
        $view = $this->renderView('@App/ranking.html.twig', [
            'ranking'    => $ranking,
        ]);
        $this->client->set('ranking', $view);

        return new Response($view);
    }


    public function quizAction(Request $request, $id)
    {
        $current = 0;
        if (null !== $request->get('current')) {
            $current = $request->get('current') + 1;
        }
        $questions = $this->getDoctrine()->getRepository(Question::class)->findBy(['quiz' => $id]);
        $options = $request->get('option');
        $user = $this->getUser();
        if (is_array($options))
        {
            $options = implode(',', $options);
        }
        if (null !== $options) {
            $quiz = $this->getDoctrine()->getRepository(Quiz::class)->find($id);
            $question = $this->getDoctrine()->getRepository(Question::class)->find($request->get('question'));
            $ua = $this->getDoctrine()->getRepository(UserAnswer::class);
            $correct = [];
            $index = 0;
            foreach ($question->getAnswers() as $answer) {
                if (1 === $answer->getIsCorrect()) {
                    $index ++;
                    $correct[$index] = $answer->getId();
                }
            }
            $tryNumber = $ua->getQuestionTryNumber($user,$quiz,$question);
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

        if ('sortable' === $questions[$current]->getType()) {
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
}
