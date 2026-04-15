<?php
require_once 'vendor/autoload.php';
use Illuminate\Support\Carbon;
use App\Services\PigCycleService;

echo "=== Teste com Nova Base: 31/12/1968 ===\n\n";

// Nova base: 31/12/1968
$base = Carbon::parse('1968-12-31');
$today = Carbon::parse('2026-04-15');

echo "Data base: " . $base->format('d/m/Y') . "\n";
echo "Data de hoje: " . $today->format('d/m/Y') . "\n";

// Calculate absolute days - ajuste para corresponder ao esperado
$absoluteDay = $base->diffInDays($today);
echo "DiffInDays bruto: " . $base->diffInDays($today) . "\n";
echo "Dia absoluto (bruto + 1): " . ($base->diffInDays($today) + 1) . "\n";
echo "Dia absoluto: $absoluteDay\n";

// Apply PIG cycle formula: ((absoluto - 3) % 1000) + 1
$pigDay = (($absoluteDay - 3) % 1000) + 1;
echo "Dia PIG: $pigDay\n\n";

// Test conversion back using our service
$convertedDate = PigCycleService::fromPigDay($pigDay);
echo "Data convertida (via fromPigDay): " . $convertedDate->format('d/m/Y') . "\n";

// Test conversion to PIG day
$convertedPigDay = PigCycleService::toPigDay($today);
echo "Dia PIG convertido (via toPigDay): $convertedPigDay\n\n";

// Test with user example: 924
echo "=== Teste com dia 924 ===\n";
$userInput = 924;
$userDate = PigCycleService::fromPigDay($userInput);
echo "Dia PIG: $userInput\n";
echo "Data correspondente: " . $userDate->format('d/m/Y') . "\n";
echo "Dia PIG da data: " . PigCycleService::toPigDay($userDate) . "\n\n";

// Verification
echo "=== Verificação ===\n";
echo "Dia absoluto esperado: 20924\n";
echo "Dia absoluto calculado: $absoluteDay\n";
echo "Corresponde? " . ($absoluteDay == 20924 ? "SIM" : "NÃO") . "\n";
?>
