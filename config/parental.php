<?php

return [

    /*
    | Сколько часов от «сейчас» вперёд учитывать при поиске задач с приближающимся дедлайном
    | (см. App\Processes\NotifyParentsDeadline). Раньше было жёстко 7ч — в тот же день
    | дедлайны в обед/вечер не попадали при ночных прогонах.
    */
    'deadline_reminder_window_hours' => (int) env('PARENTAL_DEADLINE_REMINDER_HOURS', 24),

];
