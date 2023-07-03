<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Exception\BillingUnavailableException;
use App\Form\CourseType;
use App\Form\LessonType;
use App\Repository\CourseRepository;
use App\Repository\LessonRepository;
use App\Service\BillingClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use App\Security\User;

#[Route('/courses')]
class CourseController extends AbstractController
{
    private CourseRepository $courseRepository;
    private BillingClient $billingClient;
    private LessonRepository $lessonRepository;


    public function __construct(CourseRepository $courseRepository, BillingClient $billingClient, LessonRepository $lessonRepository,
    )
    {
        $this->courseRepository = $courseRepository;
        $this->billingClient = $billingClient;
        $this->lessonRepository = $lessonRepository;
    }

    /**
     * @throws BillingUnavailableException
     * @throws \JsonException
     */
    #[Route('/', name: 'app_course_index', methods: ['GET'])]
    public function index(): Response
    {
        $courses = $this->courseRepository->findAll();
        $transactions = [];
        if ($this->getUser() !== null) {
            $user = $this->getUser();
            $response = $this->billingClient->getTransactions(
                $user->getToken(),
                ['skip_expired' => true, 'type' => 'payment']
            );
            foreach ($response as $item) {
                if (isset($item['expires_at'])) {
                    $transactions[$item['course_code']]['type'] = 'rent';
                    $transactions[$item['course_code']]['expires_at'] = $item['expires_at'];
                } else {
                    $transactions[$item['course_code']]['type'] = 'buy';
                }
            }
        }
        return $this->render('course/index.html.twig', [
            'courses' => $courses,
            'transactions' => $transactions
        ]);
    }

    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route('/new', name: 'app_course_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $course = new Course();
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->courseRepository->save($course, true);

            return $this->redirectToRoute(
                'app_course_show',
                ['id' => $course->getId()],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->renderForm('course/new.html.twig', [
            'course' => $course,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_course_show', methods: ['GET'])]
    public function show(Course $course, Request $request): Response
    {
        $owned = false;
        $disabled = true;
        if ($this->getUser() !== null) {
            $user = $this->getUser();
            $courseBilling = $this->billingClient->getCourse($course->getCode());
            if (isset($courseBilling['type'])) {
                if ($courseBilling['type'] === 'free') {
                    $owned = true;
                } else {
                    $transactions = $this->billingClient->getTransactions(
                        $user->getToken(),
                        ['skip_expired' => true, 'course_code' => $course->getCode()]
                    );
                    if (isset($transactions[0])) {
                        $owned = true;
                    } else {
                        $currentUser = $this->billingClient->getBillingUser($user->getToken());
                        if ($currentUser['balance'] >= $courseBilling['price']) {
                            $disabled = false;
                        }
                    }
                }
            } else {
                $owned = true;
            }
        }

        return $this->render('course/show.html.twig', [
            'course' => $course,
            'owned' => $owned,
            'disabled' => $disabled
        ]);
    }

    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route('/{id}/edit', name: 'app_course_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Course $course): Response
    {
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->courseRepository->save($course, true);

            return $this->redirectToRoute(
                'app_course_show',
                ['id' => $course->getId()],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->renderForm('course/edit.html.twig', [
            'course' => $course,
            'form' => $form,
        ]);
    }

    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route('/{id}', name: 'app_course_delete', methods: ['POST'])]
    public function delete(Request $request, Course $course): Response
    {
        if ($this->isCsrfTokenValid('delete' . $course->getId(), $request->request->get('_token'))) {
            $this->courseRepository->remove($course, true);
        }

        return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
    }

    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route('/{id}/new/lesson', name: 'app_lesson_new', methods: ['GET', 'POST'])]
    public function newLesson(Request $request, Course $course): Response
    {
        $lesson = new Lesson();
        $lesson->setCourse($course);
        $form = $this->createForm(LessonType::class, $lesson, [
            'course' => $course,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->lessonRepository->save($lesson, true);

            return $this->redirectToRoute(
                'app_course_show',
                ['id' => $course->getId()],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->renderForm('lesson/new.html.twig', [
            'lesson' => $lesson,
            'form' => $form,
            'course' => $course,
        ]);
    }

    /**
     * @throws BillingUnavailableException
     * @throws \JsonException
     */
    #[IsGranted('ROLE_USER')]
    #[Route('/buy/{id}', name: 'app_course_buy', methods: ['POST'])]
    public function buy(Course $course, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        try {
            $this->billingClient->buyCourse($user->getToken(), $course->getCode());
            $this->addFlash('success', 'Курс успешно оплачен');
        } catch (BillingUnavailableException|\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }
        return $this->redirectToRoute('app_course_show', ['id' => $course->getId()], Response::HTTP_SEE_OTHER);
    }
}
