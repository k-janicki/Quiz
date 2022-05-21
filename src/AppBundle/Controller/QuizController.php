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
use AppBundle\Entity\User;
use AppBundle\Entity\UserAnswer;
use AppBundle\Form\QuizType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

//use Symfony\Component\HttpFoundation\JsonResponse;

/**
 *
 */
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
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $em;

    /**
     * QuizController constructor.
     * @param EntityManagerInterface $em
     * @param DuelRepository $duelRepository
     * @param QuizRepository $quizRepository
     * @param QuestionRepository $questionRepository
     * @param UserAnswerRepository $userAnswerRepository
     */
    public function __construct(EntityManagerInterface $em, DuelRepository $duelRepository, QuizRepository $quizRepository, QuestionRepository $questionRepository, UserAnswerRepository $userAnswerRepository)
    {
        $this->duelRepository = $duelRepository;
        $this->quizRepository = $quizRepository;
        $this->questionRepository = $questionRepository;
        $this->userAnswerRepository = $userAnswerRepository;
        $this->em = $em;
    }

    public function frontAction(Request $request)
    {
        $quiz = $this->quizRepository->find(1);
        $user = $this->getUser();
        $tryIndex = 0;
        do {
            $returned = $this->resolveDuelOptimistic($quiz->getId(), $user->getId(), $tryIndex);
            $tryIndex += 1;
            dump('returned: '.$returned);
        } while ($returned !== 0);


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

    public function assignDuel($quizId, $user)
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

        return 1;
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

    public function resolveDuelOptimistic(int $quizId, int $userId, $tryIndex = 0)
    {
        $em = $this->getDoctrine()->getManager();
        $quiz = $em->getReference(Quiz::class,$quizId);
        $user = $em->getReference(User::class,$userId);
        $criteria = Duel::getDuelCriteriaForUser($quiz, $user);
        $duel = $this->duelRepository->matching($criteria)->first();

        if (false !== $duel) {
            //sprawdzenie czy bierze udział w pojedynku
            return 0;
        }
        //sprawdzenie czy istnieje jakis pojedynek z wolnym miejscem
        $duel = $this->duelRepository->findOneBy(['user2' => null],['id'=>'asc']);
        //wygenerowanie nowego pojedynku jesli nie ma zadnego pustego
        if (null === $duel) {
            $duelId = count($this->duelRepository->findAll()) + 1; //jak nie znajdzie zadnego duela to tworzy id dla konkretnej encji aby przy insercie bic sie o miejsce w bazie
            $questions = $em->getRepository(Question::class)->generateQuestionsForQuiz(5, $quiz);

            $em->getConnection()->beginTransaction();
            try {
                //utworzenie pojedynku - 3 proby zanim wrzuci domyslny autoincrement
                $duelNew = new Duel($quiz, $user, null);
                if ($tryIndex <= 3) {
                    $duelNew->setId($duelId);
                    $metadata = $em->getClassMetadata(get_class($duelNew));
                    $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);
                }
                $em->persist($duelNew);
                //utworzenie pytan quizowych dla pojedynku
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
                $em = $this->getDoctrine()->resetManager();
                $em->clear();
                if (is_a($e, UniqueConstraintViolationException::class)) {
                    $sqlState = $e->getSQLState();
                    //SQLSTATE[23000] - fail na insercie jesli klucz jest zduplikowany
                    if ($sqlState == '23000') {
                        return -1; //ponowne wywołanie
                    }
                }
            }
        } else {
            //jesli pojedynek z wolnym miejscem istnieje i uzytkownik nie jest w zadnym pojedynku
            $expectedVersion = 1;
            $em->getConnection()->beginTransaction();
            try {
                $duelId = $duel->getId();
                $toUpdate = $this->duelRepository->find($duelId, LockMode::OPTIMISTIC, $expectedVersion );
                $toUpdate->setUser2($user);
                $em->persist($toUpdate);
                $em->flush();
                $em->getConnection()->commit();
            } catch (\Exception $e) {
                $em->getConnection()->rollback();
                //todo: obsluga bledu
                return -1;
            }
        }
        //jak wszystko git to zwroc 0
        return 0;
    }
}
