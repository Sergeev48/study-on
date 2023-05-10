<?php

namespace App\DataFixtures;

use App\Entity\Course;
use App\Entity\Lesson;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $course_1 = new Course();
        $course_1
            ->setCode('Python-1')
            ->setName('Python с нуля')
            ->setDescription('На Python создают веб-приложения и нейросети, проводят научные вычисления и автоматизируют процессы. Вы научитесь программировать на востребованном языке с нуля, напишете Telegram-бота для турагентства и сможете начать карьеру в разработке.');

        $lesson = new Lesson();
        $lesson
            ->setName('Введение')
            ->setContent('Научитесь работать с онлайн-редактором кода. Напишете первую программу. Освоите работу с функцией print.')
            ->setNumber(1);
        $course_1->addLesson($lesson);

        $lesson = new Lesson();
        $lesson
            ->setName('Основы работы с Python')
            ->setContent('Изучите работу с переменными, оператором ввода input и строками.')
            ->setNumber(2);
        $course_1->addLesson($lesson);

        $lesson = new Lesson();
        $lesson
            ->setName('Операторы, выражения')
            ->setContent('Изучите арифметические операции с числами, порядок их выполнения, ввод чисел с клавиатуры, деление нацело и с остатком, а также сокращённые операторы.')
            ->setNumber(3);
        $course_1->addLesson($lesson);

        $course_2 = new Course();
        $course_2
            ->setCode('Java-1')
            ->setName('Java-разработчик')
            ->setDescription('Вы научитесь писать код и создавать сайты на самом популярном языке программирования. Разработаете блог, добавите сильный проект в портфолио и станете Java-программистом, которому рады в любой студии разработки.');

        $lesson = new Lesson();
        $lesson
            ->setName('Вводный модуль')
            ->setContent('Вы узнаете, где применяется язык Java и как выглядит программный код. Установите среду разработки и напишете первое консольное приложение.')
            ->setNumber(1);
        $course_2->addLesson($lesson);

        $lesson = new Lesson();
        $lesson
            ->setName('Синтаксис языка')
            ->setContent('Познакомитесь с основными переменными в языке Java, научитесь использовать операторы сравнения и циклы.')
            ->setNumber(2);
        $course_2->addLesson($lesson);

        $lesson = new Lesson();
        $lesson
            ->setName('Система контроля версий Git')
            ->setContent('Научитесь работать с Git: сможете сравнивать, менять и откатывать разные версии кода, научитесь создавать ветки и работать над одним проектом в команде.')
            ->setNumber(3);
        $course_2->addLesson($lesson);

        $course_3 = new Course();
        $course_3
            ->setCode('SQL-1')
            ->setName('SQL-разработчик')
            ->setDescription('Вы освоите язык запросов SQL и его процедурное расширение PL/SQL. Научитесь собирать, обрабатывать и предоставлять данные для анализа, сможете визуализировать информацию и поймёте, как использовать и настраивать свои базы данных для различных задач.');

        $lesson = new Lesson();
        $lesson
            ->setName('Введение')
            ->setContent('Узнаете, что такое базы данных и зачем они нужны. Научитесь создавать свои структуры данных, установите сервер Oracle. Познакомитесь с инструментом Oracle Apex и сможете с его помощью создавать приложения и визуализировать данные.')
            ->setNumber(1);
        $course_3->addLesson($lesson);

        $lesson = new Lesson();
        $lesson
            ->setName('Первые шаги в SQL')
            ->setContent('Изучите расширенные возможности Apex, познакомитесь с основными операторами SQL и напишете свои первые запросы к базе данных.')
            ->setNumber(2);
        $course_3->addLesson($lesson);

        $lesson = new Lesson();
        $lesson
            ->setName('Агрегатные функции')
            ->setContent('Научитесь суммировать данные, высчитывать среднее и определять количество строк с помощью функций sum, avg, count и других.')
            ->setNumber(3);
        $course_3->addLesson($lesson);

        $lesson = new Lesson();
        $lesson
            ->setName('Агрегатные функции по аналитическим разрезам')
            ->setContent('Продолжите знакомиться с функциями: научитесь высчитывать минимальное и максимальное значения, группировать и фильтровать данные с помощью функций max, min, distinct, having и других.')
            ->setNumber(4);
        $course_3->addLesson($lesson);

        $manager->persist($course_1);

        $manager->persist($course_2);

        $manager->persist($course_3);

        $manager->flush();
    }
}
