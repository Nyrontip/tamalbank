# TamalBank API Documentation

## Overview

API RESTful PHP para gestionar gastos y consultar saldo bancario. 
El saldo se sincroniza con la API externa del banco.

**Base URL:** `http://localhost:8084/api`

---

## Autenticación

Usuario se pasa en la **ruta** (path), no en header:
- `/expenses/{personId}`
- `/tamalbits/{personId}`
- `/account/{personId}`

---

## Endpoints

### 1. Auth

#### POST `/auth/login`

Identifica al usuario en la API externa.

```bash
curl -X POST http://localhost:8084/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"person_id": "240420241036"}'
```

**Respuestas:**
- 200: Usuario existe
- 404: Usuario no encontrado

---

### 2. Account

#### GET `/account/{personId}`

Obtiene el saldo desde la API externa.

```bash
curl http://localhost:8084/api/account/240420241036
```

```json
{
    "person_id": "240420241036",
    "balance": 10000,
    "currency": "USD"
}
```

**Errores:** 502 si API externa no responde

---

#### POST `/account/{personId}/deduct`

Descuenta monto directamente.

```bash
curl -X POST http://localhost:8084/api/account/240420241036/deduct \
  -H "Content-Type: application/json" \
  -d '{"amount": 50, "reason": "Gasto directo"}'
```

**Errores:** 400, 422, 502

---

### 3. Products (Público)

#### GET `/products`

Lista todos los productos con filtro opcional.

```bash
curl "http://localhost:8084/api/products?type=alimentacion"
```

**Query Parameters:**
| Param | Tipo | Descripción |
|-------|------|-------------|
| type | string | Filtrar por categoría (alimentacion, servicios, transporte, otros) |

#### GET `/products/{id}`

Producto específico.

---

### 4. Expenses

#### GET `/expenses/{personId}`

Historial de gastos del usuario con paginación.

```bash
curl "http://localhost:8084/api/expenses/240420241036?limit=20&offset=0&type=alimentacion"
```

**Query Parameters:**
| Param | Tipo | Default | Descripción |
|-------|------|---------|-------------|
| limit | int | 20 | Máx resultados (máx 100) |
| offset | int | 0 | Para paginación |
| type | string | null | Filtrar por tipo de gasto |

**Errores:** 401 si falta userId, 404 si usuario no existe

---

#### POST `/expenses/{personId}`

Registra un gasto.

```bash
curl -X POST http://localhost:8084/api/expenses/240420241036 \
  -H "Content-Type: application/json" \
  -d '{"product_id": 1, "type": "alimentacion", "description": "Orejas de Pollo"}'
```

**Errores:** 401, 404, 422, 502

---

### 5. Tamalbits

#### GET `/tamalbits/{personId}`

Total de Tamalbits acumulados.

```bash
curl http://localhost:8084/api/tamalbits/240420241036
```

```json
{
    "tamalbits_total": 2,
    "calculation": "floor(amount / 10)",
    "rule": "1 Tamalbit por cada $10 gastados en productos con gives_tamalbits=true"
}
```

**Errores:** 401 si falta userId, 404 si usuario no existe

---

### 6. Status (Público)

#### GET `/status`

Health check de la API.

```bash
curl http://localhost:8084/api/status
```

```json
{
    "status": "ok",
    "timestamp": "2026-05-07T03:30:00Z",
    "version": "1.0.0",
    "checks": {
        "database": "ok",
        "external_api": "ok"
    }
}
```

**Respuestas:**
- 200: Todo ok
- 503: Algún componente fallando

---

## Códigos de Error

| Código | Descripción |
|--------|-------------|
| 400 | Bad Request - datos inválidos |
| 401 | Unauthorized - falta userId |
| 404 | Not Found - endpoint o usuario no existe |
| 405 | Method Not Allowed |
| 422 | Error de negocio (saldo insuficiente) |
| 500 | Internal Server Error |
| 502 | Bad Gateway - API externa no responde |
| 503 | Service Unavailable |

---

## Notas

- Puerto externo: **8084**
- Puerto API externa: **8083**
- Los Tamalbits se calculan dinámicamente
- Usuario se valida contra API externa antes de acceder a expenses/tamalbits
- Health check verifica DB y API externa