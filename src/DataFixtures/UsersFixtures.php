<?php

namespace App\DataFixtures;

use App\Entity\Users;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Faker;

class UsersFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordEncoder, private SluggerInterface $slugger)
    {
    }
    public function load(ObjectManager $manager): void
    {


        $faker = Faker\Factory::create('en_us');
        for ($usr = 1; $usr <= 5; $usr++) {
            $admin = new Users();
            $admin->setEmail($faker->email);
            $admin->setFirstname($faker->firstName);
            $admin->setLastname($faker->lastName);
            $admin->setAddress($faker->address);
            $zipCode = $faker->postcode;
            $admin->setZipcode(str_replace($zipCode, '', 5));
            $admin->setCity($faker->city);
            $admin->setPassword($this->passwordEncoder->hashPassword($admin, 'secret'));
            $admin->setRoles(['ROLE_USER']);

            $manager->persist($admin);
        }
        $manager->flush();
    }
}