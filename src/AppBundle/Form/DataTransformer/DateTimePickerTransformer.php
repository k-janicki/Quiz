<?php


namespace AppBundle\Form\DataTransformer;


use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class DateTimePickerTransformer implements DataTransformerInterface
{

    /**
     * @inheritDoc
     */
    public function transform($dateTime)
    {
        if (null === $dateTime) {
            return '';
        }

        if (!$dateTime instanceof \DateTimeInterface) {
            throw new TransformationFailedException('Expected a \DateTimeInterface');
        }

        if (!$dateTime instanceof \DateTimeImmutable) {
            $dateTime = clone $dateTime;
        }

        return $dateTime->format('Y-m-d H:i:s');
    }

    /**
     * @inheritDoc
     */
    public function reverseTransform($dateTimeString)
    {
        if (empty($dateTimeString)) {
            return null;
        }
        try {
            $dateTime = new \DateTime($dateTimeString);
        } catch (\Exception $e) {
            throw new TransformationFailedException($e->getMessage(), $e->getCode(), $e);
        }

        return $dateTime;
    }
}