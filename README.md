# PlacaSegura

MVP de un parqueadero con reconocimiento de placas (LPR): registra el ingreso y la salida de vehículos a partir de la placa, calcula el cobro, valida cupos y controla una barrera simulada. Proyecto de Ingeniería de Software II (tercer corte), UAM.

## Stack

| Capa | Tecnología |
|---|---|
| Frontend | Angular + Tailwind CSS |
| Backend | Laravel (API REST + Sanctum) |
| Base de datos | PostgreSQL |
| Mensajería | RabbitMQ |
| Caché | Redis |
| Contenedores | Docker Compose |

## Arquitectura

```
Navegador → Angular (:4200) → API Laravel (:8000) → PostgreSQL
                                     ├→ RabbitMQ (eventos)
                                     └→ Redis (caché de pases)
```

El backend se organiza por capas: `Domain` (reglas de negocio), `Application` (casos de uso), `Infrastructure` (implementaciones) y `Http` (controladores). Patrones aplicados: Strategy, State, Chain of Responsibility, Adapter, Facade y Factory.

## Cómo ejecutar

Requisitos: Docker y Docker Compose.

```bash
git clone <URL_DEL_REPOSITORIO>
cd placasegura
cp .env.example .env
cp backend/.env.example backend/.env
docker compose up -d --build
docker compose exec backend php artisan key:generate
```

| Servicio | URL |
|---|---|
| Aplicación (Angular) | http://localhost:4200 |
| API (Laravel) | http://localhost:8000 |
| Panel RabbitMQ | http://localhost:15672 |

## Variables de entorno

Se definen en `.env` y `backend/.env`; las plantillas están en los archivos `.env.example`. Las principales:

| Variable | Descripción |
|---|---|
| `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Credenciales de PostgreSQL |
| `RABBITMQ_USER`, `RABBITMQ_PASSWORD` | Credenciales de RabbitMQ |
| `FRONTEND_URL` | Origen permitido por CORS |

## Usuarios de prueba

| Rol | Correo | Contraseña |
|---|---|---|
| Administrador | admin@placasegura.com | (completar) |
| Operador | operador@placasegura.com | (completar) |

## Pruebas

```bash
docker compose exec backend php artisan test
```

## Estructura

```
placasegura/
├── backend/             API Laravel
├── frontend/            Aplicación Angular
├── docs/                Documentación y diagramas
└── docker-compose.yml
```