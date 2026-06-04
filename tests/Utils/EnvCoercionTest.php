<?php

use PHPUnit\Framework\TestCase;
use Rockberpro\RestRouter\Utils\DotEnv;
use Rockberpro\RestRouter\Utils\IniEnv;

/**
 * DotEnv::get and IniEnv::get share the same getenv-backed boolean coercion
 * contract; these tests pin that behaviour for both, plus IniEnv file loading.
 */
class EnvCoercionTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        // These exception classes live in the global namespace, so neither the
        // composer PSR-4 map nor the project autoloader can resolve them on
        // demand; load them explicitly so the throw paths are exercisable.
        require_once __DIR__ . '/../../src/Utils/DotEnvException.php';
        require_once __DIR__ . '/../../src/Utils/IniEnvException.php';
    }

    /** @var array<int,string> env keys to clear after each test */
    private array $touched = [];

    protected function tearDown(): void
    {
        foreach ($this->touched as $key) {
            putenv($key);
        }
        $this->touched = [];
    }

    private function setEnv(string $key, string $value): void
    {
        putenv("{$key}={$value}");
        $this->touched[] = $key;
    }

    /**
     * @dataProvider truthyValues
     */
    public function testGetCoercesTruthyToTrue(string $raw): void
    {
        $this->setEnv('COERCE_T', $raw);

        $this->assertTrue(DotEnv::get('COERCE_T'));
        $this->assertTrue(IniEnv::get('COERCE_T'));
    }

    public function truthyValues(): array
    {
        return [['1'], ['true'], ['TRUE'], ['on'], ['yes'], ['y'], [' Yes ']];
    }

    /**
     * @dataProvider falsyValues
     */
    public function testGetCoercesFalsyToFalse(string $raw): void
    {
        $this->setEnv('COERCE_F', $raw);

        $this->assertFalse(DotEnv::get('COERCE_F'));
        $this->assertFalse(IniEnv::get('COERCE_F'));
    }

    public function falsyValues(): array
    {
        return [['0'], ['false'], ['FALSE'], ['off'], ['no'], ['n']];
    }

    public function testGetReturnsRawStringForOtherValues(): void
    {
        $this->setEnv('COERCE_S', 'postgres://localhost');

        $this->assertSame('postgres://localhost', DotEnv::get('COERCE_S'));
        $this->assertSame('postgres://localhost', IniEnv::get('COERCE_S'));
    }

    public function testDotEnvGetThrowsWhenMissing(): void
    {
        $this->expectException(DotEnvException::class);
        DotEnv::get('DEFINITELY_NOT_SET_' . uniqid());
    }

    public function testIniEnvGetThrowsWhenMissing(): void
    {
        $this->expectException(IniEnvException::class);
        IniEnv::get('DEFINITELY_NOT_SET_' . uniqid());
    }

    public function testIniEnvLoadThrowsForMissingFile(): void
    {
        $this->expectException(IniEnvException::class);
        IniEnv::load(sys_get_temp_dir() . '/does-not-exist-' . uniqid() . '.ini');
    }

    public function testIniEnvLoadPopulatesEnvIncludingBoolsAndArrays(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'ini');
        file_put_contents($path, <<<INI_CONTENT
        [app]
        INI_FLAG = true
        INI_NAME = hello
        INI_LIST[] = a
        INI_LIST[] = b
        INI_CONTENT
        );

        try {
            IniEnv::load($path);
            $this->touched[] = 'INI_FLAG';
            $this->touched[] = 'INI_NAME';
            $this->touched[] = 'INI_LIST';

            $this->assertTrue(IniEnv::get('INI_FLAG'));
            $this->assertSame('hello', IniEnv::get('INI_NAME'));
            // arrays are stored as JSON to avoid array-to-string conversion
            $this->assertSame(['a', 'b'], json_decode(IniEnv::get('INI_LIST'), true));
        } finally {
            unlink($path);
        }
    }
}
