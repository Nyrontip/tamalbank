# TamalBank API Documentation

## Overview

API RESTful PHP para gestionar gastos y consultar saldo bancario desde API externa en puerto 8083.
El saldo se mantiene en sincronía con la API del banco.

**Base URL:** `http://localhost:8084/api`

---

## Autenticación

La API usa `personId` del header. Debe enviarse en cada request excepto login.

```
X-Person-Id: 240420241036
```

---

## Endpoints

### 1. Login / Iniciar Sesión

#### POST `/auth/login`

Identifica al usuario consultando la API externa.

**Request:**
```bash
curl -X POST http://localhost:8084/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"person_id": "240420241036"}'
```

**Response (200):**
```json
{
    "success": true,
    "user_exists": true,
    "person_id": "240420241036",
    "message": "Usuario validado correctamente"
}
```

---

### 2. Saldo Bancario

#### GET `/account/{personId}`

Obtiene el saldo desde la API externa.

```bash
curl http://localhost:8084/api/account/240420241036 \
  -H "X-Person-Id: 240420241036"
```

```json
{
    "person_id": "240420241036",
    "balance": 9855,
    "currency": "USD"
}
```

---

#### POST `/account/{personId}/deduct`

Descuenta un monto del saldo directamente (sin registrar en DB).

```bash
curl -X POST http://localhost:8084/api/account/240420241036/deduct \
  -H "Content-Type: application/json" \
  -H "X-Person-Id: 240420241036" \
  -d '{"amount": 50, "reason": "Gasto directo"}'
```

```json
{
    "success": true,
    "transaction_id": "txn_abc123",
    "previous_balance": 10000,
    "new_balance": 9950,
    "amount_deducted": 50
}
```

**Errores:**
- `400` - amount inválido o reason vacío
- `422` - saldo insuficiente
```

---

### 3. Productos

#### GET `/products`

Lista todos los productos.

```bash
curl http://localhost:8084/api/products
```

```json
{
    "products": [
        {"id": 1, "name": "Orejas de Pollo", "type": "alimentacion", "balance": 25, "gives_tamalbits": true},
        ...
    ]
}
```

---

### 4. Gastos

#### POST `/expenses`

Registra un gasto. **Endpoint principal.**

```bash
curl -X POST http://localhost:8084/api/expenses \
  -H "Content-Type: application/json" \
  -H "X-Person-Id: 240420241036" \
  -d '{"product_id": 1, "type": "alimentacion", "description": "Orejas de Pollo"}'
```

```json
{
    "success": true,
    "expense": {
        "id": 1,
        "product_id": 1,
        "product_name": "Orejas de Pollo",
        "amount": 25,
        "tamalbits_earned": 2,
        "api_deducted": true
    },
    "balance": {"previous": 10000, "current": 9975},
    "tamalbits": {"earned": 2, "total": 2}
}
```

---

#### GET `/expenses`

Historial de gastos.

```bash
curl http://localhost:8084/api/expenses -H "X-Person-Id: 240420241036"
```

---

### 5. Tamalbits

#### GET `/tamalbits`

Total de Tamalbits acumulados.

```bash
curl http://localhost:8084/api/tamalbits -H "X-Person-Id: 240420241036"
```

```json
{
    "tamalbits_total": 2,
    "calculation": "floor(amount / 10)",
    "rule": "1 Tamalbit por cada $10 gastados en productos con gives_tamalbits=true"
}
```

---

### 6. Estado

#### GET `/status`

```bash
curl http://localhost:8084/api/status
```

```json
{"status": "ok", "version": "1.0.0"}
```

---

##Notas

- La API está contenida en Docker
- Puerto externo: **8084**
- API bancaria: **8083** (interna)
- Los Tamalbits se calculan dinámicamente (no se almacenan)