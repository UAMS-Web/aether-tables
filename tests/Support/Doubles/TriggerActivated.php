<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Tables\Tests\Support\Doubles;

use Statamic\Contracts\Entries\Entry;

/**
 * Stands in for the host site's trigger event in tests: the same three public
 * properties the listeners read, which the add-on is configured to listen for
 * through `tables.trigger_event`.
 */
class TriggerActivated
{
    /**
     * @param  ?Entry  $entry  The entry associated with the trigger, or null for a new entry
     * @param  string  $triggerHandle  The handle of the trigger field
     * @param  array<string, mixed>  $formValues  The current form values from the Control Panel
     */
    public function __construct(
        public ?Entry $entry,
        public string $triggerHandle,
        public array $formValues = [],
    ) {}
}
