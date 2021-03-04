<?php

namespace App\Controller;

use App\Entity\Tag;
use App\Entity\Task;
use App\Form\TaskType;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TaskController extends AbstractController
{

    /**
     * Undocumented variable
     *
     * @var TaskRepository
     */
    private $repository;

    /**
     * Undocumented variable
     *
     * @var EntityManagerInterface
     */
    private $manager;

    /**
     * Constructeur du taskcontroller pour injection de dependance 
     *
     * @param TaskRepository $repository
     * @param EntityManagerInterface $manager
     */
    public function __construct(TaskRepository $repository, EntityManagerInterface $manager)
    {
        $this->repository = $repository;
        $this->manager = $manager;
    }

    /**
     * @Route("/tasks/listing", name="tasks_listing")
     */
    public function TaskListing(): Response
    {
        // Récupérer les informations de l'utilisateur connecté
        // $user = $this->getUser();

        $task = $this->repository->findAll();


        return $this->render('task/index.html.twig', [
            'tasks' => $task,
        ]);
    }

    /**
     * @route("/tasks/create", name="task_create")
     * @route("/tasks/update/{id}", name="task_update", requirements={"id"="\d+"})
     * 
     * Undocumented function
     *
     * @param Request $request
     * @return Response
     */
    public function task(Task $task = null, Request $request): Response
    {

        if (!$task) {
            $task = new Task;
            $flag = true;
        } else {
            $flag = false;
        }

        $form = $this->createForm(TaskType::class, $task, []);

        // Feed object with our calculated datas
        if ($flag) $task->setCreatedAt(new \DateTime());


        $form->handleRequest($request);

        if ($form->isSubmitted() and $form->isValid()) {

            $task->setName($form['name']->getData())
                ->setDescription($form['description']->getData())
                ->setDueAt($form['dueAt']->getData())
                ->setTag($form['tag']->getData());

            $this->manager->persist($task);
            $this->manager->flush();

            return $this->redirectToRoute('tasks_listing');
        }

        return $this->render('task/create.html.twig', ['form' => $form->createView()]);
    }

    /**
     * @route("/tasks/delete/{id}", name="task_delete", requirements={"id"="\d+"})
     *
     * @param Task $id
     * @return Response
     */
    public function delete(Task $task): Response
    {

        $this->manager->remove($task);
        $this->manager->flush();

        return $this->redirectToRoute('tasks_listing');
    }

    /**
     * @Route("/task/calendar", name="task_calendar")
     *
     * @return Response
     */
    public function calendar(): Response
    {
        $tags = $this->getDoctrine()->getRepository(Tag::class)->findBy([], ['name' => 'ASC']);
//dd($tags);
        return $this->render('task/calendar.html.twig', [
            'tags' => $tags
        ]);
    }
}
