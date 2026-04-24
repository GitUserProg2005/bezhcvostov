<?php

namespace Database\Seeders;

use App\Models\Folder;
use App\Models\Note;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotesFoldersSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->where('id', 1)->first();

        if (! $user) {
            return;
        }

        // Повторный сид должен быть идемпотентным и не работать по "битым" nestedset данным.
        Note::query()->where('user_id', $user->id)->delete();
        Folder::query()->where('user_id', $user->id)->delete();

        $structure = [
            'Учеба' => [
                'Математика' => ['Алгебра', 'Геометрия', 'Подготовка к контрольной'],
                'Физика' => ['Механика', 'Электричество'],
                'Русский язык' => ['Орфография', 'Сочинения'],
                'Английский' => ['Грамматика', 'Словарь'],
            ],
            'Олимпиады' => [
                'Всерос' => ['Муниципальный этап', 'Региональный этап'],
                'Перечневые' => ['Ломоносов', 'Высшая проба'],
            ],
            'Проекты' => [
                'Научный проект' => ['Гипотеза', 'Эксперименты', 'Презентация'],
                'IT-проекты' => ['Telegram-бот', 'Веб-приложение'],
            ],
            'Личное развитие' => [
                'Навыки' => ['Тайм-менеджмент', 'Конспектирование'],
                'Книги' => ['Нон-фикшн', 'Художественная литература'],
            ],
            'Быт' => [
                'Покупки' => ['Школа', 'Дом'],
                'Планы на неделю' => ['Будни', 'Выходные'],
            ],
        ];

        $tree = [];
        foreach ($structure as $rootTitle => $children) {
            $childNodes = [];
            foreach ($children as $childTitle => $grandChildren) {
                $leafNodes = [];
                foreach ($grandChildren as $leafTitle) {
                    $leafNodes[] = [
                        'title' => $leafTitle,
                        'user_id' => $user->id,
                    ];
                }

                $childNodes[] = [
                    'title' => $childTitle,
                    'user_id' => $user->id,
                    'children' => $leafNodes,
                ];
            }

            $tree[] = [
                'title' => $rootTitle,
                'user_id' => $user->id,
                'children' => $childNodes,
            ];
        }

        Folder::query()->rebuildTree($tree, false);

        $templates = [
            [
                'title' => 'План занятия',
                'content' => <<<MD
# План занятия

## Цель
Понять ключевую тему и закрепить ее на практике.

## Шаги
- Прочитать теорию (20-30 минут)
- Выписать 5 ключевых тезисов
- Решить минимум 3 задания

## Рефлексия
Что получилось лучше всего и что нужно повторить?
MD,
            ],
            [
                'title' => 'Краткий конспект',
                'content' => <<<MD
# Краткий конспект

## Основные определения
- Термин 1
- Термин 2

## Формулы / правила
```text
Правило: сначала анализ условия, затем решение по шагам.
```

## Примеры
1. Пример базового уровня
2. Пример повышенной сложности
MD,
            ],
            [
                'title' => 'Чеклист подготовки',
                'content' => <<<MD
# Чеклист подготовки

- [ ] Повторить тему за прошлую неделю
- [ ] Сделать домашнее задание
- [ ] Подготовить вопросы учителю
- [ ] Решить 1 пробный вариант

> Маленькие шаги каждый день дают стабильный результат.
MD,
            ],
            [
                'title' => 'Идеи и заметки',
                'content' => <<<MD
# Идеи и заметки

## Что улучшить
- Добавить больше практики
- Разделить большие задачи на подзадачи

## Вопросы на потом
- Какие темы требуют повторения?
- Где возникают ошибки чаще всего?
MD,
            ],
        ];

        $folders = Folder::query()
            ->where('user_id', $user->id)
            ->defaultOrder()
            ->get();

        foreach ($folders as $index => $folder) {
            $template = $templates[$index % count($templates)];

            Note::query()->create([
                'user_id' => $user->id,
                'folder_id' => $folder->id,
                'title' => $template['title'].' — '.$folder->title,
                'content' => $template['content'],
            ]);
        }
    }

}
