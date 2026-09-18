<?php

declare(strict_types=1);

namespace Survos\SimpleDatatables\Components;

use Survos\SimpleDatatables\Model\Column;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\PreMount;

#[AsTwigComponent('simple_datatables', template: '@SurvosSimpleDatatables/components/grid.html.twig')]
class SimpleDatatablesComponent
{
    public function __construct(
        public string $stimulusController,
        public string $backend = 'simple',
    )
    {
    }

    public ?iterable $data = null;
    public array $columns = [];
    public bool $search = true;
    public bool $trans = true;
    public string|bool|null $domain = null;

    public int $perPage=5;

    public bool $useDatatables = true;
    public bool $info = false;
    public bool $condition = true;
    public string $scrollY = '70vh';
    public string $dom='?';
    public array $searchPanesFields=[];
    public ?string $tableId = null;
    public string $tableClasses = '';
    public ?string $remoteUrl=null;

    #[PreMount]
    public function preMount(array $parameters = []): array
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'data' => null,
            'perPage' => 10,
            'activate' => true,
            'tableId' => null,
            'remoteUrl' => null,
            'stimulusController' => $this->stimulusController,
            'backend' => $this->backend,
            'search' => true,
            'info' => false,
            'useDatatables' => true,
            'trans' => true,
            'tableClasses' => '',
            'scrollY' => '70vh',
            'condition' => true,
            'caller' => null,
            'columns' => [],
        ]);
        $resolver->setAllowedValues('backend', ['simple', 'ux']);
        $parameters = $resolver->resolve($parameters);
//        dd($parameters);
        return $parameters;
    }

    /** Options for upstream's client-side controller; rows come from the rendered DOM. */
    public function getUxOptions(): array
    {
        $options = [
            'serverSide' => false,
            'searching' => $this->search,
            'info' => $this->info,
            'pageLength' => $this->perPage,
            'scrollY' => $this->scrollY,
            'mutationsEnabled' => false,
        ];

        if ($this->remoteUrl) {
            $columns = array_values(iterator_to_array($this->normalizedColumns()));
            if ($columns === []) {
                throw new \LogicException('The ux backend requires explicit columns for remoteUrl.');
            }
            $options['ajax'] = ['url' => $this->remoteUrl, 'dataSrc' => ''];
            $options['columns'] = array_map(static fn (Column $column): array => [
                'name' => $column->name,
                'data' => $column->name,
                'title' => $column->title,
                'defaultContent' => '',
            ], $columns);
        }

        return $options;
    }

    /**
     * @return array<string, Column>
     */
    public function normalizedColumns(): iterable
    {
        $normalizedColumns = [];
        foreach ($this->columns as $c) {
            if (empty($c)) {
                continue;
            }
            if ($c instanceof Column) {
                if ($c->condition) {
                    $normalizedColumns[$c->name] = $c;
                }
                continue;
            }
            if (is_string($c)) {
                $c = [
                    'name' => $c,
                ];
            }
            assert(is_array($c));
            $column = new Column(...$c);
            if ($column->condition) {
                $normalizedColumns[$column->name] = $column;
            }
        }
        return $normalizedColumns;
    }

}
