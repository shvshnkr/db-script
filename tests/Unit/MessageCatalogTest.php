<?php
declare(strict_types=1);

use Dbscript\I18n\MessageCatalog;
use PHPUnit\Framework\TestCase;

final class MessageCatalogTest extends TestCase
{
    public function testParseLegacyCfgKeyValue(): void
    {
        $tmp = sys_get_temp_dir() . '/dbs-lang-' . bin2hex(random_bytes(4)) . '.cfg';
        file_put_contents($tmp, "SAVE;Save\nKEY:;Value with colon\n");
        $messages = MessageCatalog::parseLegacyCfg($tmp);
        unlink($tmp);

        $this->assertSame('Save', $messages['SAVE']);
        $this->assertSame('Value with colon', $messages['KEY']);
    }

    public function testJsonLoad(): void
    {
        $dir = sys_get_temp_dir() . '/dbs-lang2-' . bin2hex(random_bytes(4));
        mkdir($dir);
        file_put_contents($dir . '/english.json', json_encode([
            'meta' => ['name' => 'english'],
            'messages' => ['HELLO' => 'Hi'],
        ], JSON_THROW_ON_ERROR));

        $catalog = new MessageCatalog($dir, new \Dbscript\Config\TomlLoader(), 'english');
        $this->assertSame('Hi', $catalog->get('HELLO'));

        unlink($dir . '/english.json');
        rmdir($dir);
    }
}
