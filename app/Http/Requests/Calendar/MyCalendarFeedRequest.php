<?php

namespace App\Http\Requests\Calendar;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The period Kalender Saya asks for, and whose items: the person's own events (mine), or
 * every event of the organization for those who may oversee them (events.view).
 */
class MyCalendarFeedRequest extends CalendarFeedRequest
{
    public function authorize(): bool
    {
        return ! $this->wantsOrganization() || $this->user()->can('events.view');
    }

    /**
     * @return array<string, array<int, ValidationRule|Closure|string>>
     */
    public function rules(): array
    {
        return [
            ...$this->rangeRules(),
            'scope' => ['nullable', 'in:mine,organization'],
        ];
    }

    public function wantsOrganization(): bool
    {
        return $this->input('scope') === 'organization';
    }
}
