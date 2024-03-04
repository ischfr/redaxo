<?php

use PHPUnit\Framework\Attributes\DataProvider;
use Redaxo\Core\Core;

require_once __DIR__ . '/var_test_base.php';

class rex_var_config_test extends rex_var_test_base
{
    protected function setUp(): void
    {
        Core::setConfig('myCoreConfig', 'myCoreConfigValue');
        rex_addon::get('project')->setConfig('myPackageConfig', 'myPackageConfigValue');
    }

    protected function tearDown(): void
    {
        Core::removeConfig('myCoreConfig');
        rex_addon::get('project')->removeConfig('tests');
    }

    /** @return list<array{string, string}> */
    public static function configReplaceProvider(): array
    {
        return [
            ['REX_CONFIG[key=myCoreConfig]', 'myCoreConfigValue'],
            ['REX_CONFIG[namespace=project key=myPackageConfig]', 'myPackageConfigValue'],
        ];
    }

    #[DataProvider('configReplaceProvider')]
    public function testConfigReplace(string $content, string $expectedOutput): void
    {
        $this->assertParseOutputEquals($expectedOutput, $content);
    }
}
