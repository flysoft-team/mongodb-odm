<?php
namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class SimpleReferenceDiscriminatorsTest extends BaseTest
{
    public function testReferenceMany()
    {
        $quiz = new Quiz;
        list($q1, $q2) = $this->persistQuizWithQuestions($quiz);

        $receivedQuiz = $this->dm->find(get_class($quiz), $quiz->id);

        $this->assertCount(2, $receivedQuiz->questions);
        $this->assertContainsOnlyInstancesOf('Doctrine\ODM\MongoDB\Proxy\Proxy', $receivedQuiz->questions);
        $this->assertInstanceOf(get_class($q1), $receivedQuiz->questions[0]);
        $this->assertInstanceOf(get_class($q2), $receivedQuiz->questions[1]);
    }

    /**
     * @param Quiz $quiz
     *
     * @return array
     */
    private function persistQuizWithQuestions(Quiz $quiz)
    {
        $q1 = new SuperQuestion;
        $q2 = new BasicQuestion;
        $this->dm->persist($q1);
        $this->dm->persist($q2);
        $quiz->questions->add($q1);
        $quiz->questions->add($q2);
        $this->dm->persist($quiz);
        $this->dm->flush();
        $this->dm->clear();

        return array($q1, $q2);
    }
}

/**
 * @ODM\Document(collection="rdt_quiz_questions")
 * @ODM\InheritanceType("SINGLE_COLLECTION")
 * @ODM\DiscriminatorField(name="type")
 * @ODM\DiscriminatorMap({"basic"="BasicQuestion", "super"="SuperQuestion"})
 */
class BasicQuestion
{
    /** @ODM\Id */
    public $id;
    public $type = 'basic';
}

/** @ODM\Document */
class SuperQuestion extends BasicQuestion
{
    public $type = 'super';
}

/** @ODM\Document(collection="rdt_quiz") */
class Quiz
{
    /** @ODM\Id */
    public $id;

    /** @ODM\ReferenceMany(targetDocument="BasicQuestion", simple=true) */
    public $questions;

    public function __construct()
    {
        $this->questions = new ArrayCollection;
    }
}
