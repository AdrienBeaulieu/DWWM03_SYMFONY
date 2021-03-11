<?php

namespace App\Controller;

use DOMXPath;
use DOMDocument;
use App\Entity\Tag;
use Knp\Snappy\Pdf;
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
use Knp\Bundle\SnappyBundle\Snappy\Response\PdfResponse;
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
                ['user' => $user->getId(),
                'isArchived' => false],

            );
        }
        return $this->render('task/index.html.twig', [
            'tasks' => $tasks,
        ]);
    }

    /**
     * @Route("/tasks/archives", name="tasks_archives")
     * @param Task $task
     * @return Response
     */
    public function archiveListing(): Response
    {
        // Récupérer les infos du user connecté
        $user = $this->getUser();
        if ($user->getRoles()[0] === 'ROLE_ADMIN') {
            // Récupérer les données du repository pour l'admin
            $tasks = $this->repository->findBy(
                ['isArchived' => true]
            );
        } else {
            // Récupérer les données du repository pour le user connecté
            $tasks = $this->repository->findBy(
                [
                    'user' => $user->getId(),
                    'isArchived' => true
                ]
            );
        }
        return $this->render('task/archives.html.twig', [
            'tasks' => $tasks
        ]);
    }

    /**
     *@Route ("/tasks/archive/{id}", name="task_archive", requirements={"id"="\d+"}))
     *
     * @param Task $task
     * @return Response
     */
    public  function archive(Task $task): Response
    {
        $task->setIsArchived(true);
        $this->manager->persist($task);
        $this->manager->flush();
        $this->addFlash('success', 'Votre tâche à bien été archivée');
        return $this->redirectToRoute('tasks_listing');
    }

    /**
     *@Route ("/tasks/republier/{id}", name="task_republier", requirements={"id"="\d+"}))
     *
     * @param Task $task
     * @return Response
     */
    public  function republier(Task $task): Response
    {
        $task->setIsArchived(false);
        $this->manager->persist($task);
        $this->manager->flush();
        $this->addFlash('success', 'Votre tâche à bien été publiée à nouveau');
        return $this->redirectToRoute('tasks_listing');
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
                ->setBeginAt($form['beginAt']->getData())
                ->setEndAt($form['endAt']->getData())
                ->setTag($form['tag']->getData())
                ->setUser($user)
                ->setAddress($form['address']->getData());

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
            "Date de fin: " . $task->getEndAt()->format('d-m-Y') . "\n";

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
        $debut = new \DateTime($datas['debut'] . ' 12:00:00'); // Adapte la date au format dateTimeInterface
        $fin = new \DateTime($datas['fin'] . ' 12:00:00');

        // Récupère l'objet task selon l'id
        $task = $this->getDoctrine()->getRepository(Task::class)->findOneBy(['id' => $id]);

        // Modifie ses propriétés
        $task->setName($name)->setDescription($desc)->setBeginAt($debut)->setEndAt($fin);

        // Persist and flush in DB
        $this->manager->persist($task);
        $this->manager->flush();


        return $this->redirectToRoute('task_calendar');
    }

    /**
     * @Route("/tasks/detail/{id}/pdf", name="task_pdf", requirements={"id"="\d+"})
     *
     * @param Task $task
     * @param Pdf $knpSnappyPdf
     * @return void
     */
    public function exportTaskToPdf(Task $task, Pdf $knpSnappyPdf)
    {
        $html = $this->renderView('task/detail.html.twig', [
            'task' => $task
        ]);
        $html = $this->prepareHTMLtoPDF($html);
        return new PdfResponse(
            $knpSnappyPdf->getOutputFromHtml($html),
            'todo' . $task->getId() . '.pdf'
        );
    }

    /**
     * @Route("/tasks/listing/pdf", name="tasks_list_pdf", requirements={"id"="\d+"})
     *
     * @param Pdf $knpSnappyPdf
     * @return void
     */
    public function exportTasksListToPdf(Pdf $knpSnappyPdf)
    {
        $html = $this->taskListing()->getContent();
        $html = $this->prepareHTMLtoPDF($html);
        return new PdfResponse(
            $knpSnappyPdf->getOutputFromHtml($html),
            'todolist.pdf'
        );
    }

    /**
     * Remove all nodes from Html content containing class "not-pdf"
     *
     * @param [type] $html
     * @return string
     */
    private function prepareHTMLtoPDF($html): string
    {
        // Using DOMDocument and DOMXPath
        $dom = new \DOMDocument;
        @$dom->loadHTML($html);
        $xPath = new DOMXPath($dom);
        $delNodes = $xPath->query('//*[contains(@class,"not-pdf")]');
        // Remove all nodes containing class "not-pdf"
        foreach ($delNodes as $node) {
            $node->parentNode->removeChild($node);
        }
        return $dom->saveHTML();
    }
}
