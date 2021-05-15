<?php

namespace AppBundle\Entity;

/**
 * UserAnswer
 */
class UserAnswer
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $tryNumber = '1';

    /**
     * @var integer
     */
    private $points = '0';

    /**
     * @return int
     */
    public function getPoints()
    {
        return $this->points;
    }

    /**
     * @param int $points
     */
    public function setPoints($points)
    {
        $this->points = $points;
    }

    /**
     * @var \AppBundle\Entity\Answer
     */
    private $answer;

    /**
     * @var \AppBundle\Entity\User
     */
    private $user;

    /**
     * @var \AppBundle\Entity\Question
     */
    private $question;

    /**
     * @var \AppBundle\Entity\Quiz
     */
    private $quiz;

    /**
     * @var string
     */
    private $selection;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set tryNumber
     *
     * @param integer $tryNumber
     *
     * @return UserAnswer
     */
    public function setTryNumber($tryNumber)
    {
        $this->tryNumber = $tryNumber;

        return $this;
    }

    /**
     * Get tryNumber
     *
     * @return integer
     */
    public function getTryNumber()
    {
        return $this->tryNumber;
    }

    /**
     * Set answer
     *
     * @param \AppBundle\Entity\Answer $answer
     *
     * @return UserAnswer
     */
    public function setAnswer(\AppBundle\Entity\Answer $answer = null)
    {
        $this->answer = $answer;

        return $this;
    }

    /**
     * Get answer
     *
     * @return \AppBundle\Entity\Answer
     */
    public function getAnswer()
    {
        return $this->answer;
    }

    /**
     * Set user
     *
     * @param \AppBundle\Entity\User $user
     *
     * @return UserAnswer
     */
    public function setUser(\AppBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \AppBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set question
     *
     * @param \AppBundle\Entity\Question $question
     *
     * @return UserAnswer
     */
    public function setQuestion(\AppBundle\Entity\Question $question = null)
    {
        $this->question = $question;

        return $this;
    }

    /**
     * Get question
     *
     * @return \AppBundle\Entity\Question
     */
    public function getQuestion()
    {
        return $this->question;
    }

    /**
     * Set quiz
     *
     * @param \AppBundle\Entity\Quiz $quiz
     *
     * @return UserAnswer
     */
    public function setQuiz(\AppBundle\Entity\Quiz $quiz = null)
    {
        $this->quiz = $quiz;

        return $this;
    }

    /**
     * Get quiz
     *
     * @return \AppBundle\Entity\Quiz
     */
    public function getQuiz()
    {
        return $this->quiz;
    }

    /**
     * Get selection
     *
     * @return string
     */
    public function getSelection()
    {
        return $this->selection;
    }

    /**
     * Set selection
     *
     * @param string $selection
     *
     * @return UserAnswer
     */
    public function setSelection($selection)
    {
        $this->selection = $selection;

        return $this;
    }
}

