<?php

namespace App\DataFixtures;

use App\Entity\Tag;
use App\Entity\Task;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        // Création d'un nouvelle objet faker
        $faker = Factory::create('fr_FR');

        // Création de 5 catégories
        for ($c = 0; $c <= 5; $c++) {

            // création objet tag
            $tag = new Tag;

            // On ajouter un mot à notre catégorie
            $tag->setName($faker->colorName());

            $manager->persist($tag);
        }

        $manager->flush();

        //Récupérer les catégories créés
        $tTag = $manager->getRepository(Tag::class)->findAll();

        // Création entre 15 et 30 tâches aléatoirement
        for ($t = 0; $t < mt_rand(15,30); $t++) {

            // Créer un nouvel objet task
            $task = new Task;

            // On nourrit l'objet task
            $task->setName($faker->sentence(6))
                 ->setDescription($faker->paragraph(3))
                 ->setCreatedAt(new DateTime())
                 ->setDueAt($faker->dateTimeBetween('now', '16 months'))
                 ->setTag($faker->randomElement($tTag));
        
            // Faire persister les données
            $manager->persist($task);
        }

        // PUSH EN BDD
        $manager->flush();
    }
}
