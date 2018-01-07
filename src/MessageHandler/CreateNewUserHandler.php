<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\User;
use App\Message\CreateNewUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class CreateNewUserHandler
{
    private $entityManager;
    private $passwordEncoder;

    public function __construct(EntityManagerInterface $em, UserPasswordEncoderInterface $encoder)
    {

        $this->entityManager = $em;
        $this->passwordEncoder = $encoder;

    }


    public function __invoke(CreateNewUser $command)
    {
        // create the user and encode its password
        $user = new User();
        $user->setFullName($command->getFullName());
        $user->setUsername($command->getUsername());
        $user->setEmail($command->getEmail());
        $user->setRoles($command->getRoles());

        // See https://symfony.com/doc/current/book/security.html#security-encoding-password
        $encodedPassword = $this->passwordEncoder->encodePassword($user, $command->getPassword());
        $user->setPassword($encodedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}