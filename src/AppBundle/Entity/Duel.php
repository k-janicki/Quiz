<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;

/**
 * Duel
 */
class Duel
{
    const DUEL_PAIRED = 0;
    const DUEL_REMOVED = -1;
    const DUEL_PAIRING = 1;
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $result;

    /**
     * @var integer
     */
    private $status;

    /**
     * @var integer
     */
    private $quizKey;

    /**
     * @var \AppBundle\Entity\Quiz
     */
    private $quiz;

    /**
     * @var Collection
     */
    private $questions;

    /**
     * @var \AppBundle\Entity\User
     */
    private $user1;

    /**
     * @var \AppBundle\Entity\User|null
     */
    private $user2;

    /**
     * @return User
     */
    public function getUser1(): User
    {
        return $this->user1;
    }

    /**
     * @param User $user1
     */
    public function setUser1(User $user1): void
    {
        $this->user1 = $user1;
    }

    /**
     * @return User|null
     */
    public function getUser2(): ?User
    {
        return $this->user2;
    }

    /**
     * @param User|null $user2
     */
    public function setUser2(?User $user2): void
    {
        $this->user2 = $user2;
    }


    public function __construct(Quiz $quiz, User $user1, ?Collection $questions)
    {
        $this->quiz = $quiz;
        $this->user1 = $user1;
        $this->status = self::DUEL_PAIRING;
        if (null === $questions) {
            $this->questions = new ArrayCollection();
        } else {
            $this->questions = $questions;
        }
    }
    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getResult(): int
    {
        return $this->result;
    }

    /**
     * @param int $result
     */
    public function setResult(int $result): void
    {
        $this->result = $result;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @param int $status
     */
    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    /**
     * @return int
     */
    public function getQuizKey(): int
    {
        return $this->quizKey;
    }

    /**
     * @param int $quizKey
     */
    public function setQuizKey(int $quizKey): void
    {
        $this->quizKey = $quizKey;
    }

    /**
     * @return Quiz
     */
    public function getQuiz(): Quiz
    {
        return $this->quiz;
    }

    /**
     * @param Quiz $quiz
     */
    public function setQuiz(Quiz $quiz): void
    {
        $this->quiz = $quiz;
    }

    /**
     * @param Collection $questions
     */
    public function setQuestions(Collection $questions): void
    {
        $this->questions = $questions;
    }


    /**
     * @return Collection
     */
    public function getQuestions()
    {
        return $this->questions;
    }

    public function isPaired()
    {
        return $this->user2 === null;
    }

    public static function getDuelCriteriaForUser($quiz, $user)
    {
        $criteria = new Criteria();
        $expr = Criteria::expr();
        $criteria
            ->where(
                $expr->andX(
                    $expr->eq('quiz', $quiz),
                    $expr->orX(
                        $expr->eq('user1', $user),
                        $expr->eq('user2', $user)
                    )
                )
            );

        return $criteria;
    }
}

