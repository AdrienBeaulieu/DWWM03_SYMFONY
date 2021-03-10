<?php

namespace App\Controller;

use App\Entity\Tag;
use App\Entity\Task;
use App\Form\MailType;
use App\Form\TaskType;
use Symfony\Component\Mime\Email;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
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
     * 
     * @param EntityManagerInterface $manager
     */
    private $translator;

    /**
     * Constructeur du taskcontroller pour injection de dependance 
     *
     * @param TaskRepository $repository
     * @param EntityManagerInterface $manager
     */
    public function __construct(TaskRepository $repository, EntityManagerInterface $manager, TranslatorInterface $translator)
    {
        $this->repository = $repository;
        $this->manager = $manager;
        $this->translator = $translator;
    }

    /**
     * @Route("/tasks/listing", name="tasks_listing")
     */
    public function taskListing(): Response
    {
        // Récuperer les infos du user connecté
        $user = $this->getUser();
        if ($user->getRoles()[0] === 'ROLE_ADMIN') {
            // Recuperer les données du repository pour l'admin
            $tasks = $this->repository->findAll();
        } else {
            // Recuperer les données du repository pour le user connecté
            $tasks = $this->repository->findBy(
                ['user' => $user->getId()],
                ['id' => 'ASC']
            );
        }
        return $this->render('task/index.html.twig', [
            'tasks' => $tasks,
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
        // Récuperer les infos du user connecté
        $user = $this->getUser();

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
                ->setTag($form['tag']->getData())
                ->setUser($user);

            $this->manager->persist($task);
            $this->manager->flush();

            $this->addFlash('success', $flag ? "Votre tâche a bien été ajouté" : "Votre tache à bien été modifiée");

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

        $this->addFlash('success', 'Votre tâche à bien été supprimée');

        return $this->redirectToRoute('tasks_listing');
    }

    /**
     * @Route("/tasks/calendar", name="task_calendar")
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

    /**
     * @route("/tasks/detail/{id}", name="task_detail", requirements={"id"="\d+"})
     *
     * @param Task $task
     * @return Response
     */
    public function show(Task $task): Response
    {
        return $this->render('task/detail.html.twig', [
            'task' => $task,
        ]);
    }

    /**
     * @Route (" /tasks/email/{id}", name="task_email", requirements={"id"="\d+"}))
     *
     * @param Request $request
     * @param Task $task
     * @param MailerInterface $mailer
     * @return Response
     */
    public function sendEmail(Request $request, Task $task, MailerInterface $mailer): Response
    {
        $user = $this->getUser()->getEmail();
        $sub = "Vous avez reçu la tache: " . $task->getName();
        $text = "Son contenu est: " . $task->getDescription() . "\n" .
            "Date de début: " . $task->getCreatedAt()->format('d-m-Y') . "\n" .
            "Date de fin: " . $task->getDueAt()->format('d-m-Y') . "\n";

        //Creation du formulaire
        $form = $this->createForm(
            MailType::class,
            ['from' => $user, 'name' => $sub, 'description' => $text]
        );
        $form->handleRequest($request);
        if ($form->isSubmitted() and $form->isValid()) {
            $emailDest = $form['to']->getData();
            $message = (new Email())
                ->from($user)
                ->to($emailDest)
                ->subject($sub)
                ->text($text);
            $mailer->send($message);

            $this->addFlash('success', $this->translator->trans('flash.mail.success'));

            return $this->redirectToRoute('tasks_listing');
        }

        return $this->render('task/email.html.twig', ['form' => $form->createView()]);
    }

    /**
     * Undocumented function
     * @Route("/tasks/calendar/request", name="task_calendar_request")
     *
     * @param Request $request
     * @return void
     */
    public function HttpGetRequest(Request $request)
    {
        //dd($request);
        // Récupère les informations de la requête
        $datas = $request->query->all();
        $id = $datas['id'];
        $name = $datas['name_task'];
        $desc = $datas['desc'];
        $debut = new \DateTime($datas['debut'].' 12:00:00'); // Adapte la date au format dateTimeInterface
        $fin = new \DateTime($datas['fin'].' 12:00:00');
        
        // Récupère l'objet task selon l'id
        $task = $this->getDoctrine()->getRepository(Task::class)->findOneBy(['id'=> $id]);

        // Modifie ses propriétés
        $task->setName($name)->setDescription($desc)->setBeginAt($debut)->setEndAt($fin);

        // Persist and flush in DB
        $this->manager->persist($task);
        $this->manager->flush();


        return $this->redirectToRoute('task_calendar');
    }
}
