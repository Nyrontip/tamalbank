import { createSignal, createResource, Show, For, Component } from 'solid-js';
import { api, Product, Expense } from './api';

const COLORS = {
  primary: '#e11d48',
  secondary: '#f43f5e',
  background: '#fff1f2',
  card: '#ffffff',
  text: '#1f2937',
  muted: '#6b7280',
  success: '#10b981',
  border: '#fecdd3',
};

const styles = {
  container: `max-width: 900px; margin: 0 auto; padding: 2rem; font-family: system-ui, sans-serif; color: ${COLORS.text};`,
  header: `text-align: center; margin-bottom: 2rem; color: ${COLORS.primary};`,
  card: `background: ${COLORS.card}; border-radius: 16px; padding: 1.5rem; box-shadow: 0 4px 20px rgba(225, 29, 72, 0.08);`,
  button: `background: ${COLORS.primary}; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600; transition: transform 0.2s, background 0.2s;`,
  buttonOutline: `background: transparent; color: ${COLORS.primary}; border: 2px solid ${COLORS.primary}; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600;`,
  input: `width: 100%; padding: 0.875rem; border: 2px solid ${COLORS.border}; border-radius: 8px; font-size: 1rem; outline: none; transition: border-color 0.2s;`,
  label: `display: block; font-weight: 600; margin-bottom: 0.5rem; color: ${COLORS.text};`,
  select: `padding: 0.5rem 1rem; border: 2px solid ${COLORS.border}; border-radius: 8px; font-size: 0.9rem;`,
  product: `display: flex; justify-content: space-between; align-items: center; padding: 1rem; border: 1px solid ${COLORS.border}; border-radius: 12px; margin-bottom: 0.75rem; transition: transform 0.2s;`,
  expenseItem: `padding: 1rem 0; border-bottom: 1px solid ${COLORS.border};`,
  stat: `text-align: center;`,
  statValue: `font-size: 2rem; font-weight: 700; color: ${COLORS.primary};`,
  statLabel: `color: ${COLORS.muted}; font-size: 0.875rem;`,
  grid: `display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;`,
  badge: `display: inline-block; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600;`,
  logout: `position: fixed; top: 1rem; right: 1rem; background: ${COLORS.card}; border: 1px solid ${COLORS.border}; padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer;`,
};

const Login: Component<{ onLogin: (id: string) => void }> = (props) => {
  const [personId, setPersonId] = createSignal('');
  const [error, setError] = createSignal('');
  const [loading, setLoading] = createSignal(false);

  const handleSubmit = async (e: Event) => {
    e.preventDefault();
    setLoading(true);
    setError('');
    try {
      await api.login(personId());
      props.onLogin(personId());
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Login failed');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div style={styles.container}>
      <div style={`${styles.card} text-align: center;`}>
        <div style={`font-size: 4rem; margin-bottom: 1rem;`}>🌮</div>
        <h1 style={styles.header}>TamalStore</h1>
        <p style={`color: ${COLORS.muted}; margin-bottom: 2rem;`}>Ingresa tu identificador para comenzar</p>
        <form onSubmit={handleSubmit}>
          <div style={`text-align: left; margin-bottom: 1.5rem;`}>
            <label style={styles.label}>Person ID</label>
            <input
              type="text"
              value={personId()}
              onInput={(e) => setPersonId(e.currentTarget.value)}
              placeholder="Ej: 240420241036"
              style={styles.input}
              required
            />
          </div>
          <button type="submit" disabled={loading()} style={`${styles.button} width: 100%;`}>
            {loading() ? 'Ingresando...' : 'Entrar'}
          </button>
          <Show when={error()}>
            <p style={`color: ${COLORS.primary}; margin-top: 1rem;`}>⚠️ {error()}</p>
          </Show>
        </form>
      </div>
    </div>
  );
};

const AccountCard: Component<{ personId: string }> = (props) => {
  const [account, { refetch: refetchAccount }] = createResource(() => props.personId, api.getAccount);
  const [tamalbits] = createResource(() => props.personId, api.getTamalbits);

  const handleDeduct = async () => {
    const amount = prompt('Monto a descontar:');
    if (!amount || isNaN(parseFloat(amount))) return;
    try {
      await api.deductAccount(props.personId, parseFloat(amount), 'Descuento manual');
      refetchAccount();
    } catch (err) {
      alert(err instanceof Error ? err.message : 'Error');
    }
  };

  return (
    <div style={styles.card}>
      <h3 style={`color: ${COLORS.muted}; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 1rem;`}>Tu Cuenta</h3>
      <div style={styles.stat}>
        <div style={styles.statValue}>${account()?.balance.toFixed(2) || '0.00'}</div>
        <div style={styles.statLabel}>Saldo disponible</div>
      </div>
      <div style={`display: flex; justify-content: space-between; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid ${COLORS.border};`}>
        <div style={styles.stat}>
          <div style={`font-size: 1.5rem; font-weight: 700; color: ${COLORS.success};`}>🎁 {tamalbits()?.tamalbits_total || 0}</div>
          <div style={styles.statLabel}>Tamalbits</div>
        </div>
        <button onClick={handleDeduct} style={styles.buttonOutline}>
          Descontar
        </button>
      </div>
    </div>
  );
};

const ProductsList: Component<{ personId: string }> = (props) => {
  const [products, { refetch }] = createResource(api.getProducts);
  const [filter, setFilter] = createSignal<string>('');

  const filteredProducts = () => {
    const list = products();
    if (!list) return [];
    return filter() ? list.filter(p => p.type === filter()) : list;
  };

  const handleBuy = async (product: Product) => {
    try {
      await api.createExpense(props.personId, {
        product_id: product.id,
        type: product.type,
        description: product.name,
      });
      alert(`¡Compraste ${product.name}! 🎉`);
    } catch (err) {
      alert(err instanceof Error ? err.message : 'Error');
    }
  };

  const getTypeBadge = (type: string) => {
    const colors: Record<string, string> = {
      alimentacion: '#fef3c7 #d97706',
      servicios: '#dbeafe #2563eb',
      transporte: '#d1fae5 #059669',
      otros: '#e5e7eb #6b7280',
    };
    const [bg, text] = colors[type] || colors.otros;
    return `background: ${bg}; color: ${text};`;
  };

  return (
    <div style={styles.card}>
      <div style={`display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;`}>
        <h3 style={`color: ${COLORS.muted}; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 1px;`}>Productos</h3>
        <select onChange={(e) => setFilter(e.currentTarget.value)} style={styles.select}>
          <option value="">Todos</option>
          <option value="alimentacion">🍔 Alimentación</option>
          <option value="servicios">🔧 Servicios</option>
          <option value="transporte">🚕 Transporte</option>
          <option value="otros">📦 Otros</option>
        </select>
      </div>
      <Show when={products.loading}><p style={`text-align: center; color: ${COLORS.muted};`}>Cargando productos...</p></Show>
      <Show when={filteredProducts().length === 0 && products()}>
        <p style={`text-align: center; color: ${COLORS.muted};`}>No hay productos</p>
      </Show>
      <For each={filteredProducts()}>
        {(product) => (
          <div style={styles.product}>
            <div>
              <div style={`font-weight: 600; font-size: 1rem;`}>{product.name}</div>
              <span style={`${styles.badge} ${getTypeBadge(product.type)} margin-top: 0.25rem; display: inline-block;`}>
                {product.type}
              </span>
              {product.gives_tamalbits && <span style={`margin-left: 0.5rem;`}>🎁</span>}
            </div>
            <div style={`display: flex; align-items: center; gap: 1rem;`}>
              <span style={`font-weight: 700; font-size: 1.25rem; color: ${COLORS.primary};`}>${product.price}</span>
              <button onClick={() => handleBuy(product)} style={styles.button}>
                Comprar
              </button>
            </div>
          </div>
        )}
      </For>
    </div>
  );
};

const ExpensesList: Component<{ personId: string }> = (props) => {
  const [expenses] = createResource(() => props.personId, () => api.getExpenses(props.personId, { limit: 10 }));

  return (
    <div style={styles.card}>
      <h3 style={`color: ${COLORS.muted}; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 1rem;`}>🧾 Gastos Recientes</h3>
      <Show when={expenses.loading}><p style={`text-align: center; color: ${COLORS.muted};`}>Cargando...</p></Show>
      <Show when={expenses()?.length === 0}>
        <p style={`text-align: center; color: ${COLORS.muted};`}>No hay gastos aún</p>
      </Show>
      <For each={expenses()}>
        {(expense) => (
          <div style={styles.expenseItem}>
            <div style={`display: flex; justify-content: space-between;`}>
              <span style={`font-weight: 600;`}>{expense.description}</span>
              <span style={`font-weight: 700; color: ${COLORS.primary};`}>-${expense.amount}</span>
            </div>
            <div style={`display: flex; justify-content: space-between; margin-top: 0.25rem;`}>
              <span style={`color: ${COLORS.muted}; font-size: 0.875rem;`}>{expense.type}</span>
              <span style={`color: ${COLORS.muted}; font-size: 0.875rem;`}>{new Date(expense.created_at).toLocaleDateString()}</span>
            </div>
          </div>
        )}
      </For>
    </div>
  );
};

const App: Component = () => {
  const [personId, setPersonId] = createSignal<string | null>(null);

  return (
    <div style={`min-height: 100vh; background: ${COLORS.background};`}>
      <Show when={personId()}>
        <button onClick={() => setPersonId(null)} style={styles.logout}>
          🚪 Salir
        </button>
      </Show>

      <div style={styles.container}>
        <Show when={!personId()}>
          <Login onLogin={setPersonId} />
        </Show>

        <Show when={personId()}>
          <h1 style={styles.header}>🌮 TamalStore</h1>
          <div style={styles.grid}>
            <AccountCard personId={personId()!} />
            <ProductsList personId={personId()!} />
          </div>
          <div style={`margin-top: 1.5rem;`}>
            <ExpensesList personId={personId()!} />
          </div>
        </Show>
      </div>
    </div>
  );
};

export default App;