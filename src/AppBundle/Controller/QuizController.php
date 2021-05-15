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

//use Symfony\Component\HttpFoundation\JsonResponse;

class QuizController extends Controller
{
    public function frontAction(Request $request)
    {
        $quizzes = $this->getDoctrine()->getRepository(Quiz::class)->getActiveQuizzes();
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
            $tryNumber = $ua->getQuestionTryNumber($user,$quiz,$question)['tryNumber'];
            if (!$tryNumber) {
                $tryNumber = 1;
            } else {
                $tryNumber++;
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

//    public function quizAjaxAction(Request $request)
//    {
//        print_r($_POST);
//        $data = ($request->get('data'));
//        dump($_POST['selection']);
//        dump($_POST['data']);
//        dump($data);
//        dump($request->request->get('data'));
//        dump($request->get('selection'));
//        return new JsonResponse(['succes'=>true]);
//
//    }

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
