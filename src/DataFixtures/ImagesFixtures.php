<?php

namespace App\DataFixtures;

use App\Entity\Images;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker;

class ImagesFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Faker\Factory::create('en_us');

        for ($img = 1; $img <= 100; $img++) {
            $image = new Images();
            $image->setName($faker->image(null, 640, 480));
            $product = $this->getReference('prod-' . rand(1, 10));
            $image->setProducts($product);
            $manager->persist($image);
        }
        // $product = new Product();
        // $manager->persist($product);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ProductsFixtures::class
        ];
    }
}