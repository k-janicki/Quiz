<?php

namespace AppBundle\Entity;

/**
 * Point
 */
class Point
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var \AppBundle\Entity\Quiz
     */
    private $quiz;

    /**
     * @var \AppBundle\Entity\User
     */
    private $user;

    /**
     * @var integer
     */
    private $pointsAmount;

    /**
     * @var integer
     */
    private $timePointsAmount;

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
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @return int
     */
    public function getPointsAmount(): int
    {
        return $this->pointsAmount;
    }

    /**
     * @param int $pointsAmount
     */
    public function setPointsAmount(int $pointsAmount): void
    {
        $this->pointsAmount = $pointsAmount;
    }

    /**
     * @return int
     */
    public function getTimePointsAmount(): int
    {
        return $this->timePointsAmount;
    }

    /**
     * @param int $timePointsAmount
     */
    public function setTimePointsAmount(int $timePointsAmount): void
    {
        $this->timePointsAmount = $timePointsAmount;
    }

    public function generateQuizKey()
    {
        $quiz = $this->getQuiz();
        if (null !== $quiz) {

        }
    }
}

