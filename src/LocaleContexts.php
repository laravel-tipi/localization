<?php

declare(strict_types=1);

namespace Tipi\Localization;

use Illuminate\Support\Collection;
use Tipi\Localization\Config\LocalizationConfig;
use Tipi\Localization\Contexts\LocaleContextDefinition;
use Tipi\Localization\Contexts\LocaleContextValidator;
use Tipi\Localization\Contracts\LocaleContextRepository;
use Tipi\Localization\Exceptions\InvalidLocaleContextException;
use Tipi\Localization\Exceptions\LocaleContextNotFoundException;
use Tipi\Support\Locale;

/**
 * Resolves context definitions against the global registry and tracks
 * which context the current request or job runs in.
 *
 * Scoped: the current context resets to the default context for every
 * request and queued job.
 */
final class LocaleContexts
{
    /**
     * @var Collection<string, LocaleContextDefinition>|null
     */
    private ?Collection $definitions = null;

    /**
     * @var array<string, LocaleContext>
     */
    private array $resolved = [];

    private ?string $current = null;

    public function __construct(
        private readonly LocaleContextRepository $repository,
        private readonly LocaleRegistry $locales,
        private readonly LocaleContextValidator $validator,
        private readonly LocalizationConfig $config,
    ) {}

    /**
     * @throws LocaleContextNotFoundException
     * @throws InvalidLocaleContextException
     */
    public function get(string $name): LocaleContext
    {
        return $this->resolved[$name] ??= $this->resolve($this->definition($name));
    }

    public function has(string $name): bool
    {
        return $this->definitions()->has($name);
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return $this->definitions()->keys()->all();
    }

    /**
     * @return Collection<string, LocaleContext>
     */
    public function all(): Collection
    {
        return $this->definitions()->map(
            fn (LocaleContextDefinition $definition): LocaleContext => $this->get($definition->name),
        );
    }

    /**
     * The stored definition, before resolution. Useful for management UIs.
     *
     * @throws LocaleContextNotFoundException
     */
    public function definition(string $name): LocaleContextDefinition
    {
        return $this->definitions()->get($name) ?? throw new LocaleContextNotFoundException($name);
    }

    /**
     * @return Collection<string, LocaleContextDefinition>
     */
    public function definitions(): Collection
    {
        return $this->definitions ??= $this->repository->all();
    }

    /**
     * The context used by localized routes, console commands and jobs.
     */
    public function default(): LocaleContext
    {
        return $this->get($this->config->defaultContext);
    }

    public function current(): LocaleContext
    {
        return $this->get($this->current ?? $this->config->defaultContext);
    }

    /**
     * Make the given context current for the rest of the request or job.
     * Normally called by the UseLocaleContext middleware.
     */
    public function setCurrent(string $name): LocaleContext
    {
        $context = $this->get($name);

        $this->current = $name;

        return $context;
    }

    public function flush(): void
    {
        $this->definitions = null;
        $this->resolved = [];
    }

    private function resolve(LocaleContextDefinition $definition): LocaleContext
    {
        $this->validator->validate($definition);

        $supported = $this->locales->active()->filter(
            fn (Locale $locale): bool => $definition->includes($locale->code),
        );

        $default = $definition->default === null ? null : $supported->get($definition->default);

        return new LocaleContext(
            name: $definition->name,
            locales: $supported,
            default: $default ?? $this->locales->default(),
            includesAllLocales: $definition->includesAllLocales(),
        );
    }
}
