<?php

namespace App\Controller;

use App\Entity\Task;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TaskController extends AbstractController
{
    /**
     * @Route("/tasks/listing", name="task")
     */
    public function TaskListing(): Response
    {
        // Chercher par doctrine le repository de nos tâches
        $repository = $this->getDoctrine()->getRepository(Task::class);

        // dans ce repository nous récupérons toutes les données
        $task = $repository->findAll();

        //dd($task);

        return $this->render('task/index.html.twig', [
            'controller_name' => 'TaskController',
        ]);
    }
}
