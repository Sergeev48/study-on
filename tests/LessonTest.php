<?php

namespace App\Tests;

use App\DataFixtures\AppFixtures;
use App\Entity\Course;
use App\Entity\Lesson;

class LessonTest extends AbstractTest
{
    protected function getFixtures(): array
    {
        return [AppFixtures::class];
    }

    public function testGetActionsResponseOk(): void
    {
        $client = $this->getClient();
        $lessons = $this->getEntityManager()->getRepository(Lesson::class)->findAll();
        foreach ($lessons as $lesson) {
            // страница урока
            $client->request('GET', '/lessons/' . $lesson->getId());
            $this->assertResponseOk();

            // страница редактирования урока
            $client->request('GET', '/lessons/' . $lesson->getId() . '/edit');
            $this->assertResponseOk();
        }
    }

    public function testSuccessfulLessonCreating(): void
    {
        // список курсов
        $client = $this->getClient();
        $crawler = $client->request('GET', '/courses/');
        $this->assertResponseOk();

        // переход на первый курс
        $link = $crawler->filter('.course-show')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // переход на окно создания
        $link = $crawler->selectLink('Добавить урок')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();
        $form = $crawler->selectButton('Сохранить')->form();

        // сохранение id курса
        $courseId = $form['lesson[course]']->getValue();

        // заполнение формы корректными значениями
        $form['lesson[name]'] = 'name';
        $form['lesson[content]'] = 'content';
        $form['lesson[number]'] = '10';
        $client->submit($form);

        // редирект
        $crawler = $client->followRedirect();
        $this->assertRouteSame('app_course_show', ['id' => $courseId]);
        $this->assertResponseOk();

        // сравнение имени и переход на страницу урока
        $this->assertSame($crawler->filter('.lesson')->last()->text(), '10. name');
        $crawler = $client->click($crawler->filter('.lesson')->last()->link());
        $this->assertResponseOk();

        // сравнение данных
        $this->assertSame($crawler->filter('.lesson-name')->first()->text(), 'name');
        $this->assertSame($crawler->filter('.lesson-content')->first()->text(), 'content');
    }

    public function testLessonFailedCreating(): void
    {
        // список курсов
        $client = $this->getClient();
        $crawler = $client->request('GET', '/courses/');
        $this->assertResponseOk();

        // переход на первый курс
        $link = $crawler->filter('.course-show')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // переход на страницу создания урока
        $link = $crawler->selectLink('Добавить урок')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // заполнение формы корректными значениями (кроме номера)
        $lessonCreatingForm = $crawler->selectButton('Сохранить')->form([
            'lesson[name]' => 'name',
            'lesson[content]' => 'content',
            'lesson[number]' => '',
        ]);
        $client->submit($lessonCreatingForm);

        // сравнение текста ошибки
        $this->assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Порядковый номер урока не может быть пустым'
        );

        // заполнение формы корректными значениями (кроме названия)
        $lessonCreatingForm = $crawler->selectButton('Сохранить')->form([
            'lesson[name]' => '',
            'lesson[content]' => 'content',
            'lesson[number]' => '1',
        ]);
        $client->submit($lessonCreatingForm);

        // сравнение текста ошибки
        $this->assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Название не может быть пустым'
        );

        // заполнение формы корректными значениями (кроме контента)
        $lessonCreatingForm = $crawler->selectButton('Сохранить')->form([
            'lesson[name]' => 'name',
            'lesson[content]' => '',
            'lesson[number]' => '1',
        ]);
        $client->submit($lessonCreatingForm);

        // сравнение текста ошибки
        $this->assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Содержимое урока не может быть пустым'
        );

        // заполнение формы корректными значениями (кроме номера)
        $lessonCreatingForm = $crawler->selectButton('Сохранить')->form([
            'lesson[name]' => 'name',
            'lesson[content]' => 'content',
            'lesson[number]' => '10001',
        ]);
        $client->submit($lessonCreatingForm);

        // сравнение текста ошибки
        $this->assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Значение поля должно быть в пределах от 1 до 10000'
        );

        // заполнение формы корректными значениями (кроме названия)
        $lessonCreatingForm = $crawler->selectButton('Сохранить')->form([
            'lesson[name]' => str_repeat("test", 64),
            'lesson[content]' => 'content',
            'lesson[number]' => '1',
        ]);
        $client->submit($lessonCreatingForm);

        // сравнение текста ошибки
        $this->assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Название урока должно быть не более 255 символов'
        );
    }

    public function testLessonSuccessfulEditing(): void
    {
        // список курсов
        $client = $this->getClient();
        $crawler = $client->request('GET', '/courses/');
        $this->assertResponseOk();

        // переход на первый курс
        $link = $crawler->filter('.course-show')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // переход на первый урок
        $link = $crawler->filter('.lesson')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // переход на окно редактирования урока
        $link = $crawler->selectLink('Изменить урок')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();
        $form = $crawler->selectButton('Сохранить')->form();

        // сохранение id курса
        $courseId = $this->getEntityManager()
            ->getRepository(Course::class)
            ->findOneBy([
                'id' => $form['lesson[course]']->getValue(),
            ])->getId();

        // заполнение формы корректными значениями
        $form['lesson[name]'] = 'name';
        $form['lesson[content]'] = 'content';
        $form['lesson[number]'] = '10';
        $client->submit($form);

        // редирект
        $crawler = $client->followRedirect();
        $this->assertRouteSame('app_course_show', ['id' => $courseId]);
        $this->assertResponseOk();

        // сравнение имени и переход на страницу урока
        $this->assertSame($crawler->filter('.lesson')->last()->text(), '10. name');
        $link = $crawler->filter('.lesson')->last()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // сравнение данных
        $this->assertSame($crawler->filter('.lesson-name')->first()->text(), 'name');
        $this->assertSame($crawler->filter('.lesson-content')->first()->text(), 'content');
    }

    public function testLessonFailedEditing(): void
    {
        // список курсов
        $client = $this->getClient();
        $crawler = $client->request('GET', '/courses/');
        $this->assertResponseOk();

        // переход на первый курс
        $link = $crawler->filter('.course-show')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // переход на первый урок
        $link = $crawler->filter('.lesson')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // переход на окно редактирования урока
        $link = $crawler->selectLink('Изменить урок')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // заполнение формы корректными значениями (кроме контента)
        $lessonEditingForm = $crawler->selectButton('Сохранить изменения')->form([
            'lesson[name]' => 'name',
            'lesson[content]' => 'content',
            'lesson[number]' => '10001',
        ]);
        $client->submit($lessonEditingForm);
        $this->assertResponseCode(422);

        // сравнение текста ошибки
        $this->assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Значение поля должно быть в пределах от 1 до 10000'
        );

        // заполнение формы корректными значениями (кроме имени)
        $lessonEditingForm = $crawler->selectButton('Сохранить изменения')->form([
            'lesson[name]' => str_repeat("test", 64),
            'lesson[content]' => 'content',
            'lesson[number]' => '10',
        ]);
        $client->submit($lessonEditingForm);
        $this->assertResponseCode(422);

        // сравнение текста ошибки
        $this->assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Название урока должно быть не более 255 символов'
        );
    }

    public function testLessonDeleting(): void
    {
        // список курсов
        $client = $this->getClient();
        $crawler = $client->request('GET', '/courses/');
        $this->assertResponseOk();

        // переход на первый курс
        $link = $crawler->filter('.course-show')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // переход на первый урок
        $link = $crawler->filter('.lesson')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // переход на страницу редактирования
        $crawler = $client->click($crawler->selectLink('Изменить урок')->link());
        $this->assertResponseOk();
        // сохранение информации о курсе
        $form = $crawler->selectButton('Сохранить')->form();
        $course = $this->getEntityManager()
            ->getRepository(Course::class)
            ->findOneBy(['id' => $form['lesson[course]']->getValue()]);
        // сохранение количества уроков
        $countBeforeDeleting = count($course->getLessons());

        // переход назад к уроку
        $crawler = $client->click($crawler->selectLink('Вернуться к уроку')->link());
        $this->assertResponseOk();

        //удаление урока
        $client->submitForm('Удалить');
        $this->assertSame($client->getResponse()->headers->get('location'), '/courses/' . $course->getId());
        $crawler = $client->followRedirect();

        // сравнение количества уроков
        $this->assertCount($countBeforeDeleting - 1, $crawler->filter('.lesson'));
    }
}
