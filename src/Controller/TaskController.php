<?php

namespace App\Controller;

use App\Entity\Task;
use App\Form\TaskType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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
            'tasks' => $task,
        ]);
    }

    /**
     * @route("/tasks/create", name="task_create")
     * 
     * Undocumented function
     *
     * @param Request $request
     * @return Response
     */
    public function createTask(Request $request) : Response
    {
        // Create new object Task
        $task = new Task;

        // Feed object with our calculated datas
        $task->setCreatedAt(new \DateTime());

        $form = $this->createForm(TaskType::class, $task, []);

        return $this->render('task/create.html.twig', ['form' => $form->createView()]);
    } 
}
