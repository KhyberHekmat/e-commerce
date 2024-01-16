<?php

namespace App\DataFixtures;

use App\Entity\Categories;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\SluggerInterface;

class CatagoriesFixtures extends Fixture
{
    private $counter = 1;
    public function __construct(private SluggerInterface $slugger)
    {
    }
    public function load(ObjectManager $manager): void
    {
        $parent = $this->createCategory('Informatique', null, $manager);

        $this->createCategory('Desktop Computer', $parent, $manager);
        $this->createCategory('Screen', $parent, $manager);
        $this->createCategory('Mouse', $parent, $manager);

        $parent = $this->createCategory('Furniture', null, $manager);

        $this->createCategory('Chair', $parent, $manager);
        $this->createCategory('Sofa', $parent, $manager);
        $this->createCategory('Desk', $parent, $manager);

        $manager->flush();
    }

    public function createCategory(string $name, Categories $parent = null, ObjectManager $manager)
    {
        $category = new Categories();
        $category->setName($name);
        $category->setSlug($this->slugger->slug($category->getName())->lower());
        $category->setParent($parent);
        $manager->persist($category);

        $this->addReference('cat-' . $this->counter, $category);
        $this->counter++;

        return $category;
    }
}