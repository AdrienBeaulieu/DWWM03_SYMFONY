<?php

namespace App\DataFixtures;

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

        // Création entre 15 et 30 tâches aléatoirement
        for ($t = 0; $t < mt_rand(15,30); $t++) {

            // Créer un nouvel objet task
            $task = new Task;

            // On nourrit l'objet task
            $task->setName($faker->sentence(6))
                 ->setDescription($faker->paragraph(3))
                 ->setCreatedAt(new DateTime())
                 ->setDueAt($faker->dateTimeBetween('now', '16 months'));
        
            // Faire persister les données
            $manager->persist($task);
        }

        // PUSH EN BDD
        $manager->flush();
    }
}
