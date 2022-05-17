<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Answer;
use AppBundle\Entity\Duel;
use AppBundle\Entity\DuelQuestion;
use AppBundle\Entity\Question;
use AppBundle\Entity\Quiz;
use AppBundle\Entity\Repository\DuelRepository;
use AppBundle\Entity\Repository\QuestionRepository;
use AppBundle\Entity\Repository\QuizRepository;
use AppBundle\Entity\Repository\UserAnswerRepository;
use AppBundle\Entity\UserAnswer;
use AppBundle\Form\AnswerType;
use AppBundle\Form\QuizType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use http\Client\Curl\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

//use Symfony\Component\HttpFoundation\JsonResponse;

class QuizController extends AbstractController
{
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
     * QuizController constructor.
     * @param DuelRepository $duelRepository
     * @param QuizRepository $quizRepository
     * @param QuestionRepository $questionRepository
     * @param UserAnswerRepository $userAnswerRepository
     */
    public function __construct(DuelRepository $duelRepository, QuizRepository $quizRepository, QuestionRepository $questionRepository, UserAnswerRepository $userAnswerRepository)
    {
        $this->duelRepository = $duelRepository;
        $this->quizRepository = $quizRepository;
        $this->questionRepository = $questionRepository;
        $this->userAnswerRepository = $userAnswerRepository;
    }

    public function frontAction(Request $request)
    {
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

    public function resolveDuel($quizId, $user)
    {
        //sprawdzenie czy użytkownik jest w pojedynku
        $quiz = $this->quizRepository->findOneBy(['id'=>$quizId]);
        if (null === $quiz) {
            return -1;
        }
        $criteria = Duel::getDuelCriteriaForUser($quiz, $user);
        $duel = $this->duelRepository->matching($criteria)->first();
        if (!$duel) {
            //todo poprawic
            $this->pairUsersForDuels($quiz, $duel, $user);
//            return -1;
        }

        return $duel;
    }

    public function pairUsersForDuels(Quiz $quiz, $duel, $user)
    {
        if (false === $duel) {
            //podepnij do wolnego, jeśli nie ma żadnych wolnych to wtedy wygeneruj
            //todo: podpiecie
            //tworzenie nowego
            $questions = $this->questionRepository->generateQuestionsForQuiz(5, $quiz);

            $duel = new Duel($quiz, $user, null);
            $em = $this->getDoctrine()->getManager();
            $em->persist($duel);
            $em->flush();
            $duelQuestions = new ArrayCollection();
            foreach ($questions as $questionIndex => $question) {
                $duelQuestion = new DuelQuestion($duel, $question, $questionIndex);
                $em->persist($duelQuestion);
                $duelQuestions->add($duelQuestion);
            }
            $duel->setQuestions($duelQuestions);
            $em->flush();
        }

        return $duel;
    }
}
