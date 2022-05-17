<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Duel
 */
class DuelQuestion
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var Duel
     */
    private $duel;

    /**
     * @var Question
     */
    private $question;

    /**
     * @var integer
     */
    private $questionIndex;

    /**
     * DuelQuestion constructor.
     * @param Duel $duel
     * @param Question $question
     * @param int $questionIndex
     */
    public function __construct(Duel $duel, Question $question, int $questionIndex)
    {
        $this->duel = $duel;
        $this->question = $question;
        $this->questionIndex = $questionIndex;
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
     * @return Duel
     */
    public function getDuel(): Duel
    {
        return $this->duel;
    }

    /**
     * @param Duel $duel
     */
    public function setDuel(Duel $duel): void
    {
        $this->duel = $duel;
    }

    /**
     * @return Question
     */
    public function getQuestion(): Question
    {
        return $this->question;
    }

    /**
     * @param Question $question
     */
    public function setQuestion(Question $question): void
    {
        $this->question = $question;
    }

    /**
     * @return int
     */
    public function getQuestionIndex(): int
    {
        return $this->questionIndex;
    }

    /**
     * @param int $questionIndex
     */
    public function setQuestionIndex(int $questionIndex): void
    {
        $this->questionIndex = $questionIndex;
    }


}