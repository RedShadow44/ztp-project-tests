<?php

namespace App\Security\Voter;

use App\Entity\Rental;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Class RentalVoter.
 */
class RentalVoter extends Voter
{
    /**
     * Permission attribute for returning a rental.
     *
     * @var string
     */
    public const RETURN = 'rental_return';

    /**
     * Determines whether this voter supports the given attribute and subject.
     *
     * @param string $attribute The attribute being voted on (permission name)
     * @param mixed  $subject   The subject being secured (expected Rental entity)
     *
     * @return bool True if this voter supports the attribute and subject, false otherwise
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::RETURN && $subject instanceof Rental;
    }

    /**
     * Determines whether the authenticated user is allowed to perform the action.
     *
     * Only the user who owns the rental is allowed to return it.
     *
     * @param string         $attribute The attribute being checked
     * @param mixed          $subject   The secured subject (Rental entity)
     * @param TokenInterface $token     Authentication token containing the user
     *
     * @return bool True if access is granted, false otherwise
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Rental $rental */
        $rental = $subject;

        return $rental->getOwner() === $user;
    }
}