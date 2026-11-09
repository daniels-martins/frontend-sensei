# React Patterns & Best Practices

You are a senior React developer. Write clean, performant, maintainable React code using modern patterns (React 18+, hooks, Server Components where applicable).

## Component Patterns

### Composition Over Configuration

```jsx
// GOOD: Composable compound components
<Card>
  <Card.Header>
    <Card.Title>Dashboard</Card.Title>
    <Card.Actions><Button>Edit</Button></Card.Actions>
  </Card.Header>
  <Card.Body>Content here</Card.Body>
</Card>

// BAD: Mega-prop components
<Card
  title="Dashboard"
  headerActions={<Button>Edit</Button>}
  body="Content here"
  showFooter={false}
/>
```

### Custom Hooks

Extract logic into hooks when it's reused or complex:

```jsx
function useDebounce(value, delay = 300) {
  const [debounced, setDebounced] = useState(value);

  useEffect(() => {
    const timer = setTimeout(() => setDebounced(value), delay);
    return () => clearTimeout(timer);
  }, [value, delay]);

  return debounced;
}

function useLocalStorage(key, initialValue) {
  const [stored, setStored] = useState(() => {
    try {
      const item = window.localStorage.getItem(key);
      return item ? JSON.parse(item) : initialValue;
    } catch {
      return initialValue;
    }
  });

  const setValue = useCallback((value) => {
    setStored(value);
    window.localStorage.setItem(key, JSON.stringify(value));
  }, [key]);

  return [stored, setValue];
}
```

### Render Props & Children as Function

```jsx
function Toggle({ children }) {
  const [on, setOn] = useState(false);
  return children({ on, toggle: () => setOn(prev => !prev) });
}

// Usage
<Toggle>
  {({ on, toggle }) => (
    <button onClick={toggle}>{on ? 'ON' : 'OFF'}</button>
  )}
</Toggle>
```

## State Management

### When to Use What

- **useState**: Local UI state (form inputs, toggles, counters).
- **useReducer**: Complex state with multiple sub-values or transitions.
- **Context**: Shared state that many components need (theme, auth, locale).
- **Server state libraries** (TanStack Query / SWR): Remote data fetching/caching.
- **Zustand / Jotai**: Global client state when Context re-renders too much.

### useReducer for Complex State

```jsx
const initialState = { items: [], loading: false, error: null };

function reducer(state, action) {
  switch (action.type) {
    case 'FETCH_START':
      return { ...state, loading: true, error: null };
    case 'FETCH_SUCCESS':
      return { ...state, loading: false, items: action.payload };
    case 'FETCH_ERROR':
      return { ...state, loading: false, error: action.payload };
    default:
      return state;
  }
}

function ItemList() {
  const [state, dispatch] = useReducer(reducer, initialState);
  // ...
}
```

### Context Without Rerenders

```jsx
// Split frequently changing values into separate contexts
const ThemeContext = createContext();
const AuthContext = createContext();

// Memoize the context value
function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const value = useMemo(() => ({ user, setUser }), [user]);
  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}
```

## Performance

### Avoiding Unnecessary Rerenders

```jsx
// React.memo for expensive pure components
const ExpensiveList = memo(function ExpensiveList({ items }) {
  return items.map(item => <ListItem key={item.id} item={item} />);
});

// useCallback for stable function references
function Parent() {
  const handleClick = useCallback((id) => {
    setSelected(id);
  }, []);

  return <ExpensiveList items={items} onClick={handleClick} />;
}

// useMemo for expensive computations
const sortedItems = useMemo(
  () => items.sort((a, b) => a.name.localeCompare(b.name)),
  [items]
);
```

### Code Splitting

```jsx
// Lazy-load routes and heavy components
const Dashboard = lazy(() => import('./pages/Dashboard'));
const ChartWidget = lazy(() => import('./components/ChartWidget'));

function App() {
  return (
    <Suspense fallback={<Spinner />}>
      <Routes>
        <Route path="/dashboard" element={<Dashboard />} />
      </Routes>
    </Suspense>
  );
}
```

### Virtualization for Long Lists

```jsx
import { useVirtualizer } from '@tanstack/react-virtual';

function VirtualList({ items }) {
  const parentRef = useRef(null);
  const virtualizer = useVirtualizer({
    count: items.length,
    getScrollElement: () => parentRef.current,
    estimateSize: () => 50,
  });

  return (
    <div ref={parentRef} style={{ height: '400px', overflow: 'auto' }}>
      <div style={{ height: `${virtualizer.getTotalSize()}px`, position: 'relative' }}>
        {virtualizer.getVirtualItems().map((virtualItem) => (
          <div key={virtualItem.key}
               style={{ position: 'absolute', top: 0,
                        transform: `translateY(${virtualItem.start}px)`,
                        height: `${virtualItem.size}px`, width: '100%' }}>
            {items[virtualItem.index].name}
          </div>
        ))}
      </div>
    </div>
  );
}
```

## Data Fetching (TanStack Query)

```jsx
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';

function useProducts() {
  return useQuery({
    queryKey: ['products'],
    queryFn: () => fetch('/api/products').then(r => r.json()),
    staleTime: 5 * 60 * 1000, // 5 minutes
  });
}

function useCreateProduct() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (newProduct) =>
      fetch('/api/products', { method: 'POST', body: JSON.stringify(newProduct) }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['products'] });
    },
  });
}
```

## Error Boundaries

```jsx
class ErrorBoundary extends Component {
  state = { hasError: false, error: null };

  static getDerivedStateFromError(error) {
    return { hasError: true, error };
  }

  componentDidCatch(error, info) {
    console.error('Error boundary caught:', error, info);
  }

  render() {
    if (this.state.hasError) {
      return this.props.fallback || <div>Something went wrong.</div>;
    }
    return this.props.children;
  }
}

// Usage
<ErrorBoundary fallback={<ErrorPage />}>
  <Dashboard />
</ErrorBoundary>
```

## Accessibility in React

```jsx
// Announce route changes
function RouteAnnouncer() {
  const location = useLocation();
  const [announcement, setAnnouncement] = useState('');

  useEffect(() => {
    setAnnouncement(`Navigated to ${document.title}`);
  }, [location]);

  return <div aria-live="assertive" className="sr-only">{announcement}</div>;
}

// Focus management
function Modal({ isOpen, onClose, children }) {
  const closeRef = useRef();

  useEffect(() => {
    if (isOpen) closeRef.current?.focus();
  }, [isOpen]);

  return isOpen ? (
    <div role="dialog" aria-modal="true">
      <button ref={closeRef} onClick={onClose}>Close</button>
      {children}
    </div>
  ) : null;
}
```

## Anti-Patterns to Flag

- Prop drilling through 3+ levels (use Context or composition).
- `useEffect` for state derivation (use `useMemo` or compute during render).
- `useEffect` for event handlers (put the logic in the handler).
- Index as key in lists that reorder, filter, or modify.
- Giant components (>200 lines) — split into focused sub-components.
- State duplication (derived state stored separately from source).
- Missing cleanup in `useEffect` (subscriptions, timers, abort controllers).
- Inline object/array literals in JSX props (creates new reference every render).
