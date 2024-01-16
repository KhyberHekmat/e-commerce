<?php

namespace App\DataFixtures;

use App\Entity\Products;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProductsFixtures extends Fixture
{
    public function __construct(private SluggerInterface $slugger)
    {
    }
    public function load(ObjectManager $manager): void
    {
        $faker = Faker\Factory::create('en_us');
        for ($prod = 1; $prod <= 10; $prod++) {
            $product = new Products();
            $product->setName($faker->text(15));
            $product->setDescription($faker->text);
            $product->setSlug($this->slugger->slug($product->getName())->lower());
            $product->setPrice($faker->numberBetween(900, 15000));
            $product->setStock($faker->numberBetween(0, 10));
            $category = $this->getReference('cat-' . rand(1, 8));
            $product->setCategories($category);
            $this->setReference('prod-' . $prod, $product);
            $manager->persist($product);
        }

        $manager->flush();
    }
}