<?php

namespace App\Tests;

use App\DataFixtures\AppFixtures;
use App\Entity\Course;

class CourseControllerTest extends AbstractTest
{
    protected function getFixtures(): array
    {
        return [AppFixtures::class];
    }


    public function urlProviderSuccessful(): \Generator
    {
        yield ['/courses/'];
        yield ['/courses/new'];
    }

    /**
     * @dataProvider urlProviderSuccessful
     */
    public function testPageSuccessful($url): void
    {
        $client = static::getClient();
        $client->request('GET', $url);
        $this->assertResponseOk();
    }

    public function urlProviderNotFound(): \Generator
    {
        yield ['/fortest/'];
    }

    /**
     * @dataProvider urlProviderNotFound
     */

    public function testPageNotFound($url): void
    {
        $client = self::getClient();
        $client->request('GET', $url);
        $this->assertResponseNotFound();
    }

    public function testGetActionsResponseOk(): void
    {
        //проверка страниц всех курсов
        $client = $this->getClient();
        $courses = $this->getEntityManager()->getRepository(Course::class)->findAll();
        foreach ($courses as $course) {
            // страница курса
            $client->request('GET', '/courses/' . $course->getId());
            $this->assertResponseOk();

            // редактирование курса
            $client->request('GET', '/courses/' . $course->getId() . '/edit');
            $this->assertResponseOk();
        }
    }

    public function testSuccessfulCourseCreating(): void
    {
        // список курсов
        $client = $this->getClient();
        $crawler = $client->request('GET', '/courses/');
        $this->assertResponseOk();

        // переход на окно добавления курса
        $link = $crawler->selectLink('Добавить новый курс')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // заполнение формы корректными значениями
        $courseCreatingForm = $crawler->selectButton('Сохранить')->form([
            'course[code]' => 'code-1',
            'course[name]' => 'name',
            'course[description]' => 'description',
        ]);
        $client->submit($courseCreatingForm);

        // редирект
        $client->followRedirect();
        $this->assertResponseOk();

        // поиск новго курса
        $course = $this->getEntityManager()->getRepository(Course::class)->findOneBy([
            'code' => 'code-1',
        ]);
        $crawler = $client->request('GET', '/courses/' . $course->getId());
        $this->assertResponseOk();

        // сравнение данных
        $this->assertSame($crawler->filter('.course-name')->text(), ('name'));
        $this->assertSame($crawler->filter('.course-description')->text(), 'description');
    }
    public function testCourseFailCreating(): void
    {
        // список курсов
        $client = $this->getClient();
        $crawler = $client->request('GET', '/courses/');
        $this->assertResponseOk();

        // переход на окно добавления курса
        $link = $crawler->selectLink('Добавить новый курс')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // заполнение формы корректными значениями(кроме кода)
        $courseCreatingForm = $crawler->selectButton('Сохранить')->form([
            'course[code]' => '',
            'course[name]' => 'name',
            'course[description]' => 'description',
        ]);
        $client->submit($courseCreatingForm);
        $this->assertResponseCode(422);

        // сравнение текста ошибки
        $this->assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Символьный код не может быть пустым'
        );

        // заполнение формы корректными значениями(кроме названия)
        $courseCreatingForm = $crawler->selectButton('Сохранить')->form([
            'course[code]' => 'code-1',
            'course[name]' => '',
            'course[description]' => 'description',
        ]);
        $client->submit($courseCreatingForm);
        $this->assertResponseCode(422);

        // сравнение текста ошибки
        $this->assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Название не может быть пустым'
        );

        // получение кода последнего курса
        $courses = $this->getEntityManager()->getRepository(Course::class)->findAll();
        $last_course = $courses[count($courses) - 1];

        // заполнение формы корректными значениями(с тем же кодом)
        $courseCreatingForm = $crawler->selectButton('Сохранить')->form([
            'course[code]' => $last_course->getCode(),
            'course[name]' => 'name',
            'course[description]' => 'description',
        ]);
        $client->submit($courseCreatingForm);
        $this->assertResponseCode(422);

        // сравнение текста ошибки
        $this->assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Такой символьный код уже существует'
        );

        // заполнение формы корректными значениями(кроме кода)
        $courseCreatingForm = $crawler->selectButton('Сохранить')->form([
            'course[code]' => str_repeat("test", 64),
            'course[name]' => 'name',
            'course[description]' => 'description',
        ]);
        $client->submit($courseCreatingForm);
        $this->assertResponseCode(422);

        // сравнение текста ошибки
        $this->assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Символьный код должен быть не более 255 символов'
        );

        // заполнение формы корректными значениями(кроме названия)
        $courseCreatingForm = $crawler->selectButton('Сохранить')->form([
            'course[code]' => 'code-1',
            'course[name]' => str_repeat("test", 64),
            'course[description]' => 'description',
        ]);
        $client->submit($courseCreatingForm);
        $this->assertResponseCode(422);

        // сравнение текста ошибки
        $this->assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Название должно быть не более 255 символов'
        );

        // заполнение формы корректными значениями(кроме описания)
        $courseCreatingForm = $crawler->selectButton('Сохранить')->form([
            'course[code]' => 'code-1',
            'course[name]' => 'name',
            'course[description]' => str_repeat("test", 251),
        ]);
        $client->submit($courseCreatingForm);
        $this->assertResponseCode(422);

        // сравнение текста ошибки
        $this->assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Описание должно быть не более 1000 символов'
        );
    }

        public function testCourseSuccessfulEditing(): void
        {
            // список курсов
            $client = $this->getClient();
            $crawler = $client->request('GET', '/courses/');
            $this->assertResponseOk();

            // переход на первый курс
            $link = $crawler->filter('.course-show')->first()->link();
            $crawler = $client->click($link);
            $this->assertResponseOk();

            // переход на окно редактирования
            $link = $crawler->selectLink('Изменить курс')->link();
            $crawler = $client->click($link);
            $this->assertResponseOk();
            $form = $crawler->selectButton('Сохранить')->form();

            // сохранение id редактируемого курса
            $courseId = $this->getEntityManager()
                ->getRepository(Course::class)
                ->findOneBy(['code' => $form['course[code]']->getValue()])->getId();

            // заполнение формы корректными значениями
            $form['course[code]'] = 'code-1';
            $form['course[name]'] = 'name';
            $form['course[description]'] = 'description';
            $client->submit($form);

            // редирект
            $crawler = $client->followRedirect();
            $this->assertRouteSame('app_course_show', ['id' => $courseId]);
            $this->assertResponseOk();

            // сравнение данных
            $this->assertSame($crawler->filter('.course-name')->text(), 'name');
            $this->assertSame($crawler->filter('.course-description')->text(), 'description');
        }

    public function testCourseFailedEditing(): void
    {
        // список курсов
        $client = $this->getClient();
        $crawler = $client->request('GET', '/courses/');
        $this->assertResponseOk();

        // переход на первый курс
        $link = $crawler->filter('.course-show')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // переход на окно редактирования
        $link = $crawler->selectLink('Изменить курс')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();
        $submitButton = $crawler->selectButton('Сохранить');
        $form = $submitButton->form();

        // получение кода последнего курса
        $courses = $this->getEntityManager()->getRepository(Course::class)->findAll();
        $last_course = $courses[count($courses) - 1];

        // заполнение формы корректными значениями(с тем же кодом)
        $form['course[code]'] = $last_course->getCode();
        $form['course[name]'] = 'name';
        $form['course[description]'] = 'description';
        $client->submit($form);
        $this->assertResponseCode(422);

        // сравнение текста ошибки
        $this->assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Такой символьный код уже существует'
        );

        // заполнение формы корректными значениями(кроме кода)
        $form['course[code]'] = str_repeat("test", 64);
        $form['course[name]'] = 'name';
        $form['course[description]'] = 'description';
        $client->submit($form);
        $this->assertResponseCode(422);

        // сравнение текста ошибки
        $this->assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Символьный код должен быть не более 255 символов'
        );

        // заполнение формы корректными значениями(кроме названия)
        $form['course[code]'] = 'code-1';
        $form['course[name]'] = str_repeat("test", 64);
        $form['course[description]'] = 'description';
        $client->submit($form);
        $this->assertResponseCode(422);

        // сравнение текста ошибки
        $this->assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Название должно быть не более 255 символов'
        );

        // заполнение формы корректными значениями(кроме описания)
        $form['course[name]'] = 'Course name for test';
        $form['course[description]'] = str_repeat("test", 251);
        $client->submit($form);
        $this->assertResponseCode(422);

        // сравнение текста ошибки
        $this->assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Описание должно быть не более 1000 символов'
        );
    }

    public function testCourseDeleting(): void
    {
        // список курсов
        $client = $this->getClient();
        $crawler = $client->request('GET', '/courses/');
        $this->assertResponseOk();

        // сохранение количества крусов
        $coursesCount = count($this->getEntityManager()->getRepository(Course::class)->findAll());

        // переход на первый курс
        $link = $crawler->filter('.course-show')->first()->link();
        $client->click($link);
        $this->assertResponseOk();
        $client->submitForm('Удалить курс');
        $this->assertSame($client->getResponse()->headers->get('location'), '/courses/');

        // редирект
        $crawler = $client->followRedirect();

        // сохранение количества курсов после удаления
        $coursesCountAfterDelete = count($this->getEntityManager()->getRepository(Course::class)->findAll());

        // проверка количества курсов
        $this->assertSame($coursesCount - 1, $coursesCountAfterDelete);
        $this->assertCount($coursesCountAfterDelete, $crawler->filter('.courses'));
    }
}