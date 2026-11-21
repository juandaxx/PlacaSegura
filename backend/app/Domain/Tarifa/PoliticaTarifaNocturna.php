<?php

namespace App\Domain\Tarifa;

/**
 * RN-02: para una misma sede y tipo de vehiculo, la tarifa nocturna debe ser
 * menor que la diurna.
 *
 * Excepcion: ambas en $0 se permite, porque RN-01 deja la tarifa de moto en
 * $0 configurable y de lo contrario no se podria configurar su franja nocturna.
 */
final class PoliticaTarifaNocturna
{
    /**
     * @param  list<string>  $valoresFranjaContraria  Tarifas ya configuradas en la otra franja.
     *
     * @throws TarifaRechazada
     */
    public function validar(NombreFranja $franja, string $valor, array $valoresFranjaContraria): void
    {
        foreach ($valoresFranjaContraria as $valorContrario) {
            [$diurna, $nocturna] = $franja === NombreFranja::Diurna
                ? [$valor, $valorContrario]
                : [$valorContrario, $valor];

            if (! $this->nocturnaEsValida($diurna, $nocturna)) {
                throw TarifaRechazada::nocturnaNoMenorQueDiurna($diurna, $nocturna);
            }
        }
    }

    private function nocturnaEsValida(string $diurna, string $nocturna): bool
    {
        $centavosDiurna = $this->centavos($diurna);
        $centavosNocturna = $this->centavos($nocturna);

        $ambasGratuitas = $centavosDiurna === 0 && $centavosNocturna === 0;

        return $ambasGratuitas || $centavosNocturna < $centavosDiurna;
    }

    /**
     * Compara en centavos enteros para evitar errores de redondeo con decimales.
     */
    private function centavos(string $valor): int
    {
        return (int) round((float) $valor * 100);
    }
}
