<?php

namespace App\DataFixtures;

use App\Entity\Cart;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixturesUser extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $user = new User();

        $cart = new Cart();
        $cart->setUser($user);

        $password = $this->passwordHasher->hashPassword($user, 'password');

        $user->setEmail('sebastienpetit27330@gmail.com');
        $user->setPassword($password);
        $user->setRoles(['ROLE_ADMIN']);
        $user->setCart($cart);

        $manager->persist($cart);
        $manager->persist($user);

        $manager->flush();
    }
}
