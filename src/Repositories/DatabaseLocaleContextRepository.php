<?php

declare(strict_types=1);

namespace Tipi\Localization\Repositories;

use Illuminate\Support\Collection;
use Tipi\Localization\Config\LocalizationConfig;
use Tipi\Localization\Contexts\LocaleContextDefinition;
use Tipi\Localization\Contracts\LocaleContextRepository;
use Tipi\Localization\Models\LocaleContext;

/**
 * Source of truth: the database. Contexts declared in config are
 * "required": they always exist (using their config definition until a
 * row is stored) and cannot be deleted. Reading never writes.
 */
final readonly class DatabaseLocaleContextRepository implements LocaleContextRepository
{
    public function __construct(
        private LocalizationConfig $config,
        private ConfigLocaleContextRepository $declared,
    ) {}

    public function all(): Collection
    {
        $model = $this->config->contextModel;
        $instance = new $model;

        $rows = $model::query()->orderBy('name')->get()->keyBy('name');

        $memberships = $instance->getConnection()
            ->table($instance->membershipTable())
            ->orderBy('locale')
            ->get(['context', 'locale'])
            ->groupBy('context')
            ->map(fn (Collection $items): array => $items->pluck('locale')->all());

        $declared = $this->declared->all();

        $definitions = $declared->map(
            fn (LocaleContextDefinition $definition): LocaleContextDefinition => $rows->has($definition->name)
                ? $this->fromRow($rows->get($definition->name), $memberships, required: true)
                : $definition,
        );

        foreach ($rows as $name => $row) {
            if (! $definitions->has($name)) {
                $definitions->put($name, $this->fromRow($row, $memberships, required: false));
            }
        }

        return $definitions;
    }

    /**
     * @param  Collection<string, list<string>>  $memberships
     */
    private function fromRow(LocaleContext $row, Collection $memberships, bool $required): LocaleContextDefinition
    {
        return new LocaleContextDefinition(
            name: $row->name,
            locales: $row->all_locales ? null : ($memberships->get($row->name) ?? []),
            default: $row->default_locale,
            required: $required,
            persisted: true,
        );
    }
}
