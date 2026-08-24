<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Storage;

echo "--- Testando Conexão com o Storage (R2/S3) ---" . PHP_EOL;
echo "FILESYSTEM_DISK: " . env('FILESYSTEM_DISK') . PHP_EOL;
echo "AWS_BUCKET: " . env('AWS_BUCKET') . PHP_EOL;
echo "AWS_ENDPOINT: " . env('AWS_ENDPOINT') . PHP_EOL;

try {
    $filename = 'test-folder/teste_r2_' . time() . '.txt';
    $content = 'Conexão Cloudflare R2 / S3 via Laravel realizada com sucesso!';

    echo "Tentando fazer upload do arquivo '{$filename}' no disco 'public'..." . PHP_EOL;
    $uploaded = Storage::disk('public')->put($filename, $content);

    if ($uploaded) {
        echo "✅ Upload concluído com sucesso!" . PHP_EOL;
        echo "Tentando gerar a URL do arquivo..." . PHP_EOL;
        $url = Storage::disk('public')->url($filename);
        echo "🔗 URL gerada: " . $url . PHP_EOL;

        echo "Tentando excluir o arquivo de teste..." . PHP_EOL;
        Storage::disk('public')->delete($filename);
        echo "✅ Exclusão concluída com sucesso!" . PHP_EOL;
    } else {
        echo "❌ Falha no upload." . PHP_EOL;
    }
} catch (\Exception $e) {
    echo "❌ Erro ao conectar/salvar: " . $e->getMessage() . PHP_EOL;
}
