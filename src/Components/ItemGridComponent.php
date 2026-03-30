<?php

namespace Survos\SimpleDatatables\Components;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Survos\SimpleDatatables\Model\Column;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\PostMount;
use Symfony\UX\TwigComponent\Attribute\PreMount;

#[AsTwigComponent('simple_item_grid', template: '@SurvosSimpleDatatables/components/item.html.twig')]
class ItemGridComponent
{
    public function __construct()
    {
    }

    public $data = null;

    public array $columns=[];
    public array|string $exclude;
    public ?string $stimulusController = '@survos/grid/item_grid';

    #[PreMount]
    public function preMount(array $parameters = []): array
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'data' => null,
            'class' => null,
            'caller' => null,
            'exclude' => [],
            'columns' => [],
        ]);
        $parameters = $resolver->resolve($parameters);
        $data = $parameters['data'];
        $exclude = $parameters['exclude'];
        if (is_object($data)) {
            $data = (array)$data;
        }
        // if no columns, get the keys from the first row of data
        if (count($parameters['columns']) === 0) {
            if (array_is_list($data) && count($data)) {

                if (is_string($exclude)) {
                    $exclude = explode(',', $exclude);
                }
                $columns = array_keys($data[0]);
                $columns = array_diff($columns, $exclude);
                $parameters['columns'] = $columns;
            }
        }
        return $parameters;
    }

    /**
     * @return array<string, Column>
     */
    public function normalizedColumns(): iterable
    {
        $normalizedColumns = [];
        foreach ($this->columns as $c) {
            if (is_string($c)) {
                $c = [
                    'name' => $c,
                ];
            }
            assert(is_array($c));
            $column = new Column(...$c);
            $normalizedColumns[$column->name] = $column;
        }
        return $normalizedColumns;
    }
}
