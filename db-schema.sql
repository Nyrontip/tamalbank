-- Tabla de Productos (Platos disponibles para comprar)
CREATE TABLE products (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type VARCHAR(50) NOT NULL, -- categoria: alimentacion, servicios, transporte, otros
    balance DECIMAL(15, 2) DEFAULT 0.00, -- PRECIO del producto
    gives_tamalbits BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de Movimientos (Gastos registrados)
CREATE TABLE movements (
    id SERIAL PRIMARY KEY,
    product_id INTEGER REFERENCES products(id) ON DELETE CASCADE,
    person_id VARCHAR(50) NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    type VARCHAR(20) NOT NULL, -- categoria del gasto
    description TEXT,
    api_transaction_id VARCHAR(100),
    api_deducted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Productos iniciales
INSERT INTO products (name, type, balance, gives_tamalbits) VALUES
('Orejas de Pollo', 'alimentacion', 25.00, TRUE),
('Arepa con Queso', 'alimentacion', 8.00, FALSE),
('Bandeja Paisa', 'alimentacion', 18.00, FALSE),
('Sancocho de Gallina', 'alimentacion', 15.00, FALSE),
('Servicio de Agua', 'servicios', 45.00, FALSE),
('Servicio de Luz', 'servicios', 120.00, FALSE),
('Pasaje Público', 'transporte', 2.50, FALSE),
('Varios', 'otros', 10.00, FALSE);