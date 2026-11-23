<?php

namespace App\Application\Services;

use App\Domain\Acceso\AccesoRechazado;
use App\Domain\Acceso\EventoAcceso;
use App\Domain\Acceso\Ingreso\CadenaValidacionIngreso;
use App\Domain\Acceso\Ingreso\ContextoIngreso;
use App\Domain\Acceso\LecturaPlaca;
use App\Domain\Acceso\MotivoAcceso;
use App\Domain\Acceso\PoliticaConfianza;
use App\Domain\Acceso\ResultadoAcceso;
use App\Domain\Acceso\TipoEvento;
use App\Domain\Cobro\Cobro;
use App\Domain\Cobro\CobroNoCalculable;
use App\Domain\Cobro\EstadoCobro;
use App\Domain\Cobro\GeneradorCobro;
use App\Domain\Contracts\CobroRepository;
use App\Domain\Contracts\EventoAccesoRepository;
use App\Domain\Contracts\GestorTransacciones;
use App\Domain\Contracts\Reloj;
use App\Domain\Tiempo\DiaOperativo;
use App\Domain\Vehiculo\ClasificadorVehiculo;
use App\Domain\Vehiculo\TipoVehiculo;
use DateTimeImmutable;

/**
 * Fachada del carril (F-01, F-02, F-03, F-05, F-06, F-07): una sola operacion
 * por caso, que orquesta la lectura de la placa, las reglas de acceso, el
 * cobro y la decision de la barrera. Los controladores solo hablan con ella.
 */
final class ServicioAcceso
{
    public function __construct(
        private readonly ClasificadorVehiculo $clasificador,
        private readonly CadenaValidacionIngreso $validacionesIngreso,
        private readonly PoliticaConfianza $politicaConfianza,
        private readonly GeneradorCobro $generadorCobro,
        private readonly EventoAccesoRepository $eventos,
        private readonly CobroRepository $cobros,
        private readonly GestorTransacciones $transacciones,
        private readonly Reloj $reloj,
        private readonly DiaOperativo $diaOperativo,
    ) {}

    /**
     * Un vehiculo llega al carril de entrada.
     *
     * @throws AccesoRechazado|CobroNoCalculable
     */
    public function registrarIngreso(LecturaPlaca $lectura): ResultadoAcceso
    {
        $tipoVehiculo = $this->clasificar($lectura->placa);

        return $this->transacciones->ejecutar(function () use ($lectura, $tipoVehiculo) {
            $ahora = $this->reloj->ahora();
            $cobroDelDia = $this->cobroDelDia($lectura->placa, $lectura->sedeId, $ahora);

            $motivo = $this->validacionesIngreso->evaluar(new ContextoIngreso(
                sedeId: $lectura->sedeId,
                tipoVehiculo: $tipoVehiculo,
                confianza: $lectura->confianza,
                lecturaManual: false,
                cobroDelDia: $cobroDelDia,
            ));

            $evento = $this->eventos->registrar(
                $this->nuevoEvento($lectura, $tipoVehiculo, TipoEvento::Ingreso, $ahora, $motivo),
            );

            return new ResultadoAcceso($evento, $this->cobroDelIngreso($evento, $cobroDelDia));
        });
    }

    /**
     * Un vehiculo llega al carril de salida.
     *
     * @throws AccesoRechazado
     */
    public function registrarSalida(LecturaPlaca $lectura): ResultadoAcceso
    {
        $tipoVehiculo = $this->clasificar($lectura->placa);

        return $this->transacciones->ejecutar(function () use ($lectura, $tipoVehiculo) {
            $ahora = $this->reloj->ahora();

            [$motivo, $cobro] = $this->politicaConfianza->requiereValidacionManual($lectura->confianza)
                ? [MotivoAcceso::LecturaManual, null]
                : $this->decidirSalida($lectura->placa, $lectura->sedeId, $ahora);

            $evento = $this->eventos->registrar(
                $this->nuevoEvento($lectura, $tipoVehiculo, TipoEvento::Salida, $ahora, $motivo),
            );

            return new ResultadoAcceso($evento, $cobro);
        });
    }

    /**
     * F-06: el operador confirma o corrige la placa de una lectura con
     * confianza baja y se repite el flujo de ingreso o salida con la placa
     * corregida, sin volver a revisar la confianza.
     *
     * @throws AccesoRechazado|CobroNoCalculable
     */
    public function validarLectura(int $eventoId, string $placa, int $usuarioId): ResultadoAcceso
    {
        $tipoVehiculo = $this->clasificar($placa);

        return $this->transacciones->ejecutar(function () use ($eventoId, $placa, $usuarioId, $tipoVehiculo) {
            $evento = $this->eventos->obtenerParaActualizar($eventoId)
                ?? throw AccesoRechazado::eventoNoEncontrado($eventoId);

            if ($evento->motivo !== MotivoAcceso::LecturaManual) {
                throw AccesoRechazado::eventoYaValidado($eventoId);
            }

            if ($evento->tipo === TipoEvento::Salida) {
                [$motivo, $cobro] = $this->decidirSalida($placa, $evento->sedeId, $evento->fechaHora);

                $validado = $evento->validadoPor($usuarioId, $placa, $tipoVehiculo, $motivo);
                $this->eventos->actualizar($validado);

                return new ResultadoAcceso($validado, $cobro);
            }

            $cobroDelDia = $this->cobroDelDia($placa, $evento->sedeId, $evento->fechaHora);

            $motivo = $this->validacionesIngreso->evaluar(new ContextoIngreso(
                sedeId: $evento->sedeId,
                tipoVehiculo: $tipoVehiculo,
                confianza: $evento->confianza ?? 0.0,
                lecturaManual: true,
                cobroDelDia: $cobroDelDia,
            ));

            $validado = $evento->validadoPor($usuarioId, $placa, $tipoVehiculo, $motivo);
            $this->eventos->actualizar($validado);

            return new ResultadoAcceso($validado, $this->cobroDelIngreso($validado, $cobroDelDia));
        });
    }

    /**
     * F-03 / RN-04: si la barrera abre se usa el cobro del dia (pagado o no) y,
     * si todavia no existe, se genera. Si la barrera no abre, no hay cobro.
     */
    private function cobroDelIngreso(EventoAcceso $evento, ?Cobro $cobroDelDia): ?Cobro
    {
        if (! $evento->motivo->abreBarrera()) {
            return null;
        }

        return $cobroDelDia ?? $this->generadorCobro->generar(
            sedeId: $evento->sedeId,
            tipoVehiculo: $evento->tipoVehiculo,
            placa: $evento->placa,
            momento: $evento->fechaHora,
            eventoIngresoId: (int) $evento->id,
        );
    }

    /**
     * F-05 / RN-06: la barrera abre solo si el cobro esta pagado o no queda
     * nada por pagar. Se revisa el ultimo cobro de la placa en la sede, no solo
     * el del dia, para que un vehiculo que entro de noche no salga sin pagar
     * despues de las 06:00.
     *
     * @return array{MotivoAcceso, ?Cobro}
     */
    private function decidirSalida(string $placa, int $sedeId, DateTimeImmutable $momento): array
    {
        $cobro = $this->cobros->ultimoDeLaPlaca($placa, $sedeId);

        if ($cobro === null) {
            return [MotivoAcceso::Ok, null];
        }

        if ($cobro->estado === EstadoCobro::Pendiente && $cobro->requierePago()) {
            return [MotivoAcceso::CobroPendiente, $cobro];
        }

        $esDelDia = $cobro->diaOperativo->format('Y-m-d') === $this->diaOperativo->fechaDe($momento)->format('Y-m-d');

        if ($cobro->estado === EstadoCobro::Pagado && $esDelDia) {
            return [MotivoAcceso::PaseVigente, $cobro];
        }

        return [MotivoAcceso::Ok, $cobro];
    }

    private function cobroDelDia(string $placa, int $sedeId, DateTimeImmutable $momento): ?Cobro
    {
        return $this->cobros->buscarDelDia($placa, $sedeId, $this->diaOperativo->fechaDe($momento));
    }

    private function nuevoEvento(
        LecturaPlaca $lectura,
        TipoVehiculo $tipoVehiculo,
        TipoEvento $tipo,
        DateTimeImmutable $momento,
        MotivoAcceso $motivo,
    ): EventoAcceso {
        return new EventoAcceso(
            id: null,
            sedeId: $lectura->sedeId,
            carrilId: $lectura->carrilId,
            tipoVehiculo: $tipoVehiculo,
            usuarioId: null, // Lectura automatica: aun no interviene un operador.
            placa: $lectura->placa,
            tipo: $tipo,
            confianza: $lectura->confianza,
            lecturaManual: false,
            fechaHora: $momento,
            motivo: $motivo,
        );
    }

    private function clasificar(string $placa): TipoVehiculo
    {
        return $this->clasificador->clasificar($placa)
            ?? throw AccesoRechazado::placaNoReconocida($placa);
    }
}
