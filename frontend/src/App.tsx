import { createSignal, createResource, Show, For, Component } from 'solid-js';
import { api, Product, Expense } from './api';

const COLORS = {
  primary: '#ca8a04',
  secondary: '#15803d',
  background: '#fefce8',
  card: '#ffffff',
  text: '#3f3f46',
  muted: '#71717a',
  success: '#15803d',
  border: '#fef08a',
};

const styles = {
  container: `max-width: 900px; margin: 0 auto; padding: 2rem; font-family: system-ui, sans-serif; color: ${COLORS.text};`,
  header: `text-align: center; margin-bottom: 2rem; color: ${COLORS.secondary};`,
  card: `background: ${COLORS.card}; border-radius: 16px; padding: 1.5rem; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);`,
  button: `background: ${COLORS.secondary}; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600; transition: transform 0.2s, opacity 0.2s;`,
  buttonOutline: `background: transparent; color: ${COLORS.secondary}; border: 2px solid ${COLORS.secondary}; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600;`,
  input: `width: 100%; box-sizing: border-box; padding: 0.875rem; border: 2px solid ${COLORS.border}; border-radius: 8px; font-size: 1rem; outline: none; transition: border-color 0.2s;`,
  label: `display: block; font-weight: 600; margin-bottom: 0.5rem; color: ${COLORS.text};`,
  select: `padding: 0.5rem 1rem; border: 2px solid ${COLORS.border}; border-radius: 8px; font-size: 0.9rem;`,
  product: `display: flex; justify-content: space-between; align-items: center; padding: 1rem; border: 1px solid ${COLORS.border}; border-radius: 12px; margin-bottom: 0.75rem; transition: transform 0.2s;`,
  expenseItem: `padding: 1rem 0; border-bottom: 1px solid ${COLORS.border};`,
  stat: `text-align: center;`,
  statValue: `font-size: 2rem; font-weight: 700; color: ${COLORS.secondary};`,
  statLabel: `color: ${COLORS.muted}; font-size: 0.875rem;`,
  grid: `display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;`,
  badge: `display: inline-block; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600;`,
  logout: `position: fixed; top: 1rem; right: 1rem; background: ${COLORS.secondary}; border: none; color: white; padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer; font-weight: 600;`,
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
        <div style={`font-size: 4rem; margin-bottom: 1rem;`}><i class="fas fa-store"></i></div>
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
            {loading() ? <><i class="fas fa-spinner fa-spin"></i> Ingresando...</> : <><i class="fas fa-sign-in-alt"></i> Entrar</>}
          </button>
          <Show when={error()}>
            <p style={`color: ${COLORS.secondary}; margin-top: 1rem;`}><i class="fas fa-exclamation-triangle"></i> {error()}</p>
          </Show>
        </form>
      </div>
    </div>
  );
};

const AccountCard: Component<{ personId: string; revision: () => number }> = (props) => {
  const [account] = createResource(
    () => ({ key: props.personId, revision: props.revision() }),
    ({ key }) => api.getAccount(key),
  );
  const [tamalbits] = createResource(
    () => ({ key: props.personId, revision: props.revision() }),
    ({ key }) => api.getTamalbits(key),
  );

  return (
    <div style={styles.card}>
      <h3 style={`color: ${COLORS.muted}; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 1rem;`}>
        <i class="fas fa-wallet" style={{ "margin-right": "0.5rem" }}></i>Tu Cuenta
      </h3>
      <div style={styles.stat}>
        <div style={styles.statValue}>${account()?.balance.toFixed(2) || '0.00'}</div>
        <div style={styles.statLabel}>Saldo disponible</div>
      </div>
      <div style={`display: flex; justify-content: center; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid ${COLORS.border};`}>
        <div style={styles.stat}>
          <div style={`font-size: 1.5rem; font-weight: 700; color: ${COLORS.secondary};`}>
            <i class="fas fa-coins" style={{ "margin-right": "0.5rem" }}></i>
            {tamalbits()?.tamalbits_total || 0}
          </div>
          <div style={styles.statLabel}>Tamalbits</div>
        </div>
      </div>
    </div>
  );
};

const ProductsList: Component<{ personId: string; onPurchase: () => void }> = (props) => {
  const [products] = createResource(() => api.getProducts());
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
      props.onPurchase();
      alert(`¡Compraste ${product.name}! 🎉`);
    } catch (err) {
      alert(err instanceof Error ? err.message : 'Error');
    }
  };

  const getTypeBadge = (type: string) => {
    const colors: Record<string, string> = {
      alimentacion: '#fef9c3 #ca8a04',
      servicios: '#dcfce7 #15803d',
      transporte: '#bbf7d0 #16a34a',
      otros: '#f4f4f5 #71717a',
    };
    const [bg, text] = colors[type] || colors.otros;
    return `background: ${bg}; color: ${text};`;
  };

  const getTypeIcon = (type: string) => {
    const icons: Record<string, string> = {
      alimentacion: 'fa-utensils',
      servicios: 'fa-wrench',
      transporte: 'fa-bus',
      otros: 'fa-box',
    };
    return icons[type] || 'fa-box';
  };

  return (
    <div style={styles.card}>
      <div style={`display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;`}>
        <h3 style={`color: ${COLORS.muted}; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 1px;`}>
          <i class="fas fa-tags" style={{ "margin-right": "0.5rem" }}></i>Productos
        </h3>
        <select onChange={(e) => setFilter(e.currentTarget.value)} style={styles.select}>
          <option value="">Todos</option>
          <option value="alimentacion"><i class="fas fa-utensils"></i> Alimentación</option>
          <option value="servicios"><i class="fas fa-wrench"></i> Servicios</option>
          <option value="transporte"><i class="fas fa-bus"></i> Transporte</option>
          <option value="otros"><i class="fas fa-box"></i> Otros</option>
        </select>
      </div>
      <Show when={products.loading}><p style={`text-align: center; color: ${COLORS.muted};`}><i class="fas fa-spinner fa-spin"></i> Cargando productos...</p></Show>
      <Show when={filteredProducts().length === 0 && products()}>
        <p style={`text-align: center; color: ${COLORS.muted};`}>No hay productos</p>
      </Show>
      <For each={filteredProducts()}>
        {(product) => (
          <div style={styles.product}>
            <div>
              <div style={`font-weight: 600; font-size: 1rem;`}>{product.name}</div>
              <span style={`${styles.badge} ${getTypeBadge(product.type)} margin-top: 0.25rem; display: inline-block;`}>
                <i class={`fas ${getTypeIcon(product.type)}`} style={{ "margin-right": "0.25rem" }}></i>
                {product.type}
              </span>
              {product.gives_tamalbits && <span style={`margin-left: 0.5rem;`}><i class="fas fa-gift" style={{ color: COLORS.secondary }}></i></span>}
            </div>
            <div style={`display: flex; align-items: center; gap: 1rem;`}>
              <span style={`font-weight: 700; font-size: 1.25rem; color: ${COLORS.secondary};`}>${product.balance}</span>
              <button onClick={() => handleBuy(product)} style={styles.button}>
                <i class="fas fa-shopping-cart"></i> Comprar
              </button>
            </div>
          </div>
        )}
      </For>
    </div>
  );
};

const ExpensesList: Component<{ personId: string; revision: () => number }> = (props) => {
  const [expenses] = createResource(
    () => ({ key: props.personId, revision: props.revision() }),
    ({ key }) => api.getExpenses(key, { limit: 10 }),
  );

  return (
    <div style={styles.card}>
      <h3 style={`color: ${COLORS.muted}; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 1rem;`}>
        <i class="fas fa-receipt" style={{ "margin-right": "0.5rem" }}></i>Gastos Recientes
      </h3>
      <Show when={expenses.loading}><p style={`text-align: center; color: ${COLORS.muted};`}><i class="fas fa-spinner fa-spin"></i> Cargando...</p></Show>
      <Show when={expenses()?.length === 0}>
        <p style={`text-align: center; color: ${COLORS.muted};`}>No hay gastos aún</p>
      </Show>
      <For each={expenses()}>
        {(expense) => (
          <div style={styles.expenseItem}>
            <div style={`display: flex; justify-content: space-between;`}>
              <span style={`font-weight: 600;`}>{expense.description}</span>
              <span style={`font-weight: 700; color: ${COLORS.secondary};`}>-${expense.amount}</span>
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
  const [revision, setRevision] = createSignal(0);

  return (
    <div style={`min-height: 100vh; background: ${COLORS.background};`}>
      <Show when={personId()}>
        <button onClick={() => setPersonId(null)} style={styles.logout}>
          <i class="fas fa-sign-out-alt"></i> Salir
        </button>
      </Show>

      <div style={styles.container}>
        <Show when={!personId()}>
          <Login onLogin={setPersonId} />
        </Show>

        <Show when={personId()}>
          <h1 style={styles.header}><i class="fas fa-store"></i> TamalStore</h1>
          <div style={styles.grid}>
            <AccountCard personId={personId()!} revision={revision} />
            <ProductsList personId={personId()!} onPurchase={() => setRevision(r => r + 1)} />
          </div>
          <div style={`margin-top: 1.5rem;`}>
            <ExpensesList personId={personId()!} revision={revision} />
          </div>
        </Show>
      </div>
    </div>
  );
};

export default App;