@php
    $statePath = $getStatePath();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="{
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            toggleDate(date) {
                if (! date) {
                    return
                }

                const dates = Array.isArray(this.state) ? this.state : []
                const exists = dates.some((item) => (item?.date ?? item) === date)

                this.state = exists
                    ? dates.filter((item) => (item?.date ?? item) !== date)
                    : [...dates, { date }].sort((left, right) => {
                        return (left?.date ?? left).localeCompare(right?.date ?? right)
                    })
            },
            removeDate(date) {
                this.state = (Array.isArray(this.state) ? this.state : [])
                    .filter((item) => (item?.date ?? item) !== date)
            },
            dateValue(item) {
                return item?.date ?? item
            },
            formatDate(date) {
                return new Intl.DateTimeFormat('ru-RU', {
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric',
                }).format(new Date(`${date}T12:00:00`))
            },
        }"
        x-init="state = Array.isArray(state) ? state : []"
        class="space-y-3"
    >
        <x-filament::input.wrapper :valid="! $errors->has($statePath)">
            <input
                type="date"
                class="fi-input"
                x-on:change="toggleDate($event.target.value); $event.target.value = ''"
            />
        </x-filament::input.wrapper>

        <div
            x-show="state.length"
            class="flex flex-wrap gap-2"
        >
            <template
                x-for="item in state"
                x-bind:key="dateValue(item)"
            >
                <button
                    type="button"
                    x-on:click="removeDate(dateValue(item))"
                    class="inline-flex items-center gap-2 rounded-full bg-primary-500/10 px-4 py-2 text-sm font-semibold text-primary-600 transition hover:bg-danger-500/10 hover:text-danger-600 dark:text-primary-400"
                    title="Удалить дату"
                >
                    <span x-text="formatDate(dateValue(item))"></span>
                    <span aria-hidden="true">&times;</span>
                </button>
            </template>
        </div>

        <p
            x-show="! state.length"
            class="text-sm text-gray-500 dark:text-gray-400"
        >
            Даты не выбраны.
        </p>
    </div>
</x-dynamic-component>
