<?php

namespace App\DataFixtures;

use DateTime;
use Faker\Factory;
use App\Entity\Tag;
use App\Entity\Task;
use App\Entity\User;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppFixtures extends Fixture
{
    /**
     * Undocumented variable
     *
     * @var UserPasswordEncoderInterface
     */
    private $encoder;

    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }
    
    
    public function load(ObjectManager $manager)
    {
        // Création d'un nouvelle objet faker
        $faker = Factory::create('fr_FR');

        // Création de 5 catégories
        for ($c = 0; $c < 5; $c++) {

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
                 ->setBeginAt($faker->dateTimeBetween('now', '2 months'))
                 ->setDueAt($faker->dateTimeInInterval($task->getBeginAt(), '+2 days'))
                 ->setEndAt($task->getDueAt())
                 ->setTag($faker->randomElement($tTag));
        
            // Faire persister les données
            $manager->persist($task);
        }

        // Crate 5 users
        for ($u = 0; $u < 5;$u++) {

            // Create new object User
            $user = new User;

            // Hash password with security parameters of $user
            // in /config/packages/security.yaml
            $hash = $this->encoder->encodePassword($user, "password");

            // Si premier utilisateur crée on lui donne le role admin et on lui force son adresse mail
            if ($u === 0){
                $user->setRoles(["ROLE_ADMIN"])
                     ->setEmail("admin@admin.local");

            } else {
                $user->setEmail($faker->safeEmail());
            }

            // Pour tout le monde
            $user->setPassword($hash);

            // Faire persister les données
            $manager->persist($user);
        }

        // PUSH EN BDD
        $manager->flush();
    }
}