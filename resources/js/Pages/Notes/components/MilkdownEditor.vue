<script setup>
import { MdEditor, config } from 'md-editor-v3';
import 'md-editor-v3/lib/style.css';

config({
    editorConfig: {
        languageUserDefined: {
            'ru-RU': {
                toolbarTips: {
                    bold: 'Жирный',
                    underline: 'Подчеркнутый',
                    italic: 'Курсив',
                    strikeThrough: 'Зачеркнутый',
                    title: 'Заголовок',
                    sub: 'Нижний индекс',
                    sup: 'Верхний индекс',
                    quote: 'Цитата',
                    unorderedList: 'Маркированный список',
                    orderedList: 'Нумерованный список',
                    task: 'Список задач',
                    codeRow: 'Встроенный код',
                    code: 'Блок кода',
                    link: 'Ссылка',
                    image: 'Изображение',
                    table: 'Таблица',
                    mermaid: 'Mermaid диаграмма',
                    katex: 'Формула',
                    revoke: 'Назад',
                    next: 'Вперед',
                    save: 'Сохранить',
                    prettier: 'Форматировать',
                    pageFullscreen: 'На весь блок',
                    fullscreen: 'На весь экран',
                    preview: 'Предпросмотр',
                    previewOnly: 'Только предпросмотр',
                    htmlPreview: 'HTML предпросмотр',
                    catalog: 'Оглавление',
                    github: 'Исходный код',
                },
                titleItem: {
                    h1: 'Заголовок 1',
                    h2: 'Заголовок 2',
                    h3: 'Заголовок 3',
                    h4: 'Заголовок 4',
                    h5: 'Заголовок 5',
                    h6: 'Заголовок 6',
                },
                imgTitleItem: {
                    link: 'Добавить ссылку на изображение',
                    upload: 'Загрузить изображение',
                    clip2upload: 'Обрезать и загрузить',
                },
                linkModalTips: {
                    linkTitle: 'Добавить ссылку',
                    imageTitle: 'Добавить изображение',
                    descLabel: 'Описание:',
                    descLabelPlaceHolder: 'Введите описание...',
                    urlLabel: 'Ссылка:',
                    urlLabelPlaceHolder: 'Введите URL...',
                    buttonOK: 'ОК',
                },
                clipModalTips: {
                    title: 'Обрезка изображения',
                    buttonUpload: 'Загрузить',
                },
                copyCode: {
                    text: 'Копировать',
                    successTips: 'Скопировано!',
                    failTips: 'Не удалось скопировать!',
                },
                mermaid: {
                    flow: 'Блок-схема',
                    sequence: 'Диаграмма последовательности',
                    gantt: 'Гантт',
                    class: 'Диаграмма классов',
                    state: 'Диаграмма состояний',
                    pie: 'Круговая диаграмма',
                    relationship: 'Диаграмма связей',
                    journey: 'Диаграмма пути',
                },
                katex: {
                    inline: 'Строчная формула',
                    block: 'Блочная формула',
                },
                footer: {
                    markdownTotal: 'Символов',
                    scrollAuto: 'Синхронная прокрутка',
                },
            },
        },
    },
});

const props = defineProps({
    vmodel: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:vmodel']);
</script>

<template>
    <div class="overflow-hidden bg-content rounded-2xl">
        <MdEditor
            :model-value="props.vmodel"
            language="ru-RU"
            :preview="false"
            :toolbars="['bold', 'underline', 'italic', 'strikeThrough', 'title', 'quote', 'unorderedList', 'orderedList', 'task', 'codeRow', 'code', 'link', 'table', 'mermaid', 'katex', 'fullscreen']"
            @update:model-value="emit('update:vmodel', $event)"
        />
    </div>
</template>

<style scoped>
:deep(.md-editor) {
    background-color: var(--bg-card);
    color: var(--content-primary);
    border: 0;
    box-shadow: none;
    --md-color: var(--content-primary);
    --md-hover-color: var(--accent);
    --md-bk-color: var(--bg-card);
    --md-bk-color-outstand: var(--bg-main);
    --md-bk-hover-color: color-mix(in srgb, var(--accent) 12%, transparent);
    --md-border-color: transparent;
    --md-border-hover-color: transparent;
    --md-border-active-color: transparent;
    --md-modal-mask: rgba(0, 0, 0, 0.45);
}

:deep(.md-editor-toolbar) {
    background-color: var(--bg-card);
    border-bottom: 0;
}

:deep(.md-editor-toolbar-item) {
    border: 0;
}

:deep(.md-editor-toolbar-item:hover) {
    background-color: color-mix(in srgb, var(--accent) 12%, transparent);
}

:deep(.md-editor-content) {
    background-color: var(--bg-card);
}

:deep(.md-editor-input-wrapper) {
    background-color: var(--bg-card);
}

:deep(.cm-editor),
:deep(.cm-scroller),
:deep(.cm-gutters) {
    background-color: var(--bg-card);
}

:deep(.cm-gutters) {
    border-right: 0;
}

:deep(.md-editor-input),
:deep(.md-editor-preview-wrapper) {
    font-size: 0.95rem;
}
</style>
