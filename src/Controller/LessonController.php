<?php

namespace App\Controller;

use App\Entity\Lesson;
use App\Entity\Course;
use App\Form\LessonType;
use App\Repository\CourseRepository;
use App\Repository\LessonRepository;
use App\Service\BillingClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Security\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/lessons')]
class LessonController extends AbstractController
{
    private LessonRepository $lessonRepository;
    private BillingClient $billingClient;

    public function __construct(
        LessonRepository $lessonRepository,
        BillingClient    $billingClient
    )
    {
        $this->lessonRepository = $lessonRepository;
        $this->billingClient = $billingClient;
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/{id}', name: 'app_lesson_show', methods: ['GET'])]
    public function show(Lesson $lesson): Response
    {
        $user = $this->getUser();
        $billingCourse = $this->billingClient->getCourse($lesson->getCourse()->getCode());

        if (isset($billingCourse['type'])) {
            if ($billingCourse['type'] !== 'free') {
                $response = $this->billingClient->getTransactions(
                    $user->getToken(),
                    ['skip_expired' => true, 'course_code' => $lesson->getCourse()->getCode()]
                );
                if (!isset($response[0])) {
                    throw new AccessDeniedException('Вы должны приобрести курс!');
                }
            }
        }

        return $this->render('lesson/show.html.twig', [
            'lesson' => $lesson,
        ]);
    }

    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route('/{id}/edit', name: 'app_lesson_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Lesson $lesson): Response
    {
        $form = $this->createForm(LessonType::class, $lesson);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->lessonRepository->save($lesson, true);

            return $this->redirectToRoute(
                'app_course_show',
                ['id' => $lesson->getCourse()->getId()],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->renderForm('lesson/edit.html.twig', [
            'lesson' => $lesson,
            'form' => $form,
        ]);
    }

    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route('/{id}', name: 'app_lesson_delete', methods: ['POST'])]
    public function delete(Request $request, Lesson $lesson): Response
    {
        $courseId = $lesson->getCourse()->getId();
        if ($this->isCsrfTokenValid('delete' . $lesson->getId(), $request->request->get('_token'))) {
            $this->lessonRepository->remove($lesson, true);
        }

        return $this->redirectToRoute(
            'app_course_show',
            ['id' => $courseId],
            Response::HTTP_SEE_OTHER
        );
    }
}
