<?php

namespace App\Actions\Events;

use App\Models\Division;
use App\Models\DivisionTemplate;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CreateDivisionsFromTemplates
{
    public function __construct(private SaveDivision $saveDivision) {}

    /**
     * Create one division per template, skipping names the event already uses.
     *
     * @param  Collection<int, DivisionTemplate>  $templates
     * @return list<string> Names of the divisions created.
     */
    public function handle(Event $event, Collection $templates, User $actor): array
    {
        return DB::transaction(function () use ($event, $templates, $actor): array {
            $existingNames = $event->divisions()->pluck('name')->map(fn (string $name): string => mb_strtolower($name))->all();
            $nextSortOrder = (int) $event->divisions()->max('sort_order') + 10;
            $created = [];

            foreach ($templates as $template) {
                if (in_array(mb_strtolower($template->name), $existingNames, true)) {
                    continue;
                }

                $this->saveDivision->handle($event, new Division, [
                    'division_template_id' => $template->getKey(),
                    'name' => $template->name,
                    'description' => $template->description,
                    'responsibilities' => $template->default_responsibilities,
                    'sort_order' => $nextSortOrder,
                ], $actor);

                $existingNames[] = mb_strtolower($template->name);
                $created[] = $template->name;
                $nextSortOrder += 10;
            }

            return $created;
        });
    }
}
