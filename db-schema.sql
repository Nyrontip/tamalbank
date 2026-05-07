-- Tabla de Productos (Cuentas bancarias)
CREATE TABLE products (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type VARCHAR(50) NOT NULL, -- Ejemplo: 'Ahorros', 'Corriente', 'Tarjeta de Crédito'
    balance DECIMAL(15, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de Movimientos (Transacciones)
CREATE TABLE movements (
    id SERIAL PRIMARY KEY,
    product_id INTEGER REFERENCES products(id) ON DELETE CASCADE,
    amount DECIMAL(15, 2) NOT NULL,
    type VARCHAR(20) NOT NULL, -- Ejemplo: 'Consignación', 'Retiro', 'Transferencia'
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Datos de ejemplo
INSERT INTO products (name, type, balance) VALUES 
('Cuenta Principal', 'Ahorros', 1500000),
('Tarjeta Tamal Oro', 'Tarjeta de Crédito', 500000);

INSERT INTO movements (product_id, amount, type, description) VALUES 
(1, 1500000, 'Consignación', 'Saldo inicial'),
(1, -50000, 'Retiro', 'Compra de tamales para la oficina');
