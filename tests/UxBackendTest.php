<?php

declare(strict_types=1);

namespace SimpleDatatablesBundle\Tests;

use PHPUnit\Framework\TestCase;
use Survos\SimpleDatatables\Components\SimpleDatatablesComponent;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

final class UxBackendTest extends TestCase
{
    public function testTwigBlocksAndEscapingSurviveBackendSelection(): void
    {
        foreach (['simple', 'ux'] as $backend) {
            $component = new SimpleDatatablesComponent('legacy-table', $backend);
            $component->columns = ['name', 'price'];
            $component->data = [['name' => '<script>unsafe</script>', 'price' => 12.5]];
            $component->trans = false;

            $loader = new FilesystemLoader(dirname(__DIR__).'/templates');
            $twig = new Environment($loader, ['strict_variables' => true]);
            $twig->addExtension(new TranslationExtension());
            $twig->addFunction(new TwigFunction('path', static fn (): string => '/'));
            $twig->addFunction(new TwigFunction('survos_stimulus', static fn (): string => 'survos--simple-datatables-bundle--ux'));
            $twig->addFunction(new TwigFunction('stimulus_controller', static fn (string $name, array $values): string => sprintf(
                'data-controller="%s" data-options="%s"',
                htmlspecialchars($name, ENT_QUOTES),
                htmlspecialchars(json_encode($values, JSON_THROW_ON_ERROR), ENT_QUOTES),
            ), ['is_safe' => ['html']]));
            $html = $twig->createTemplate(<<<'TWIG'
                {% embed 'components/grid.html.twig' %}
                    {% block price %}<strong class="price">${{ row.price|number_format(2) }}</strong>{% endblock %}
                {% endembed %}
                TWIG)->render(['this' => $component]);
            $crawler = new Crawler($html);
            self::assertCount(2, $crawler->filter('tbody tr td'));
            self::assertSame('$12.50', $crawler->filter('td .price')->text());
            self::assertCount(0, $crawler->filter('script'));
            self::assertStringContainsString('&lt;script&gt;', $html);
            self::assertSame($backend === 'ux' ? 'survos--simple-datatables-bundle--ux' : 'legacy-table', $crawler->filter('table')->attr('data-controller'));
        }
    }

    public function testRemoteUxTableUsesClientSideArrayEndpoint(): void
    {
        $component = new SimpleDatatablesComponent('legacy-table', 'ux');
        $component->remoteUrl = '/rows.json';
        $component->columns = ['name', ['name' => 'secret', 'condition' => false], 'price'];
        $component->perPage = 7;
        $options = $component->getUxOptions();
        self::assertFalse($options['serverSide']);
        self::assertSame(7, $options['pageLength']);
        self::assertSame(['url' => '/rows.json', 'dataSrc' => ''], $options['ajax']);
        self::assertSame(['name', 'price'], array_column($options['columns'], 'data'));
    }

    public function testRemoteUxTableRequiresColumns(): void
    {
        $component = new SimpleDatatablesComponent('legacy-table', 'ux');
        $component->remoteUrl = '/rows.json';
        $this->expectException(\LogicException::class);
        $component->getUxOptions();
    }
}
